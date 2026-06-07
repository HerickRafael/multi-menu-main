<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Middleware específico
require_once __DIR__ . '/../middleware/ApiSecurity.php';

use App\Middleware\ApiSecurity;

class ApiController extends Controller
{
    private ApiSecurity $apiSecurity;
    private array $authData;
    private ?array $company = null;

    public function __construct()
    {
        // Configura o middleware de segurança da API
        if (!class_exists('SecurityRequirements', false)) {
            require_once __DIR__ . '/../config/SecurityRequirements.php';
        }

        $this->apiSecurity = new ApiSecurity([
            'auth_methods' => ['bearer', 'api_key'],
            'require_auth' => true,
            'jwt_secret' => SecurityRequirements::resolveJwtSecret(),
            'cors_enabled' => true,
            'rate_limit_enabled' => true,
            'rate_limit_requests' => 1000, // 1000 requests per minute for API
            'rate_limit_window' => 60,
            'log_requests' => true,
        ], db());

        try {
            // Autentica a requisição
            $result = $this->apiSecurity->handle();
            $this->authData = $result['auth_data'];
            
            // Responde headers CORS se for preflight
            if ($result['preflight'] ?? false) {
                $this->sendResponse(['message' => 'CORS preflight OK'], 200);
                exit;
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            $statusCode = is_int($code) && $code > 0 ? $code : 401;
            $this->sendError($e->getMessage(), $statusCode);
            exit;
        }
    }

    /**
     * Valida e carrega a empresa pelo slug
     */
    private function loadCompany(string $slug): void
    {
        $this->company = Company::findBySlug($slug);
        
        if (!$this->company || !$this->company['active']) {
            $this->sendError('Empresa não encontrada ou inativa', 404);
            exit;
        }
    }

    /**
     * Retorna informações da empresa
     */
    public function getCompany($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        // Remove dados sensíveis
        $companyData = $this->company;
        unset($companyData['created_at'], $companyData['updated_at']);

        $this->sendResponse([
            'company' => $companyData,
            'authenticated_user' => $this->authData['user_id'] ?? null
        ]);
    }

    /**
     * Lista todas as categorias da empresa
     */
    public function getCategories($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        $categories = Category::listByCompany($this->company['id']);

        $this->sendResponse([
            'categories' => $categories,
            'total' => count($categories)
        ]);
    }

    /**
     * Carrega uma categoria garantindo que pertence à empresa atual.
     * Responde 404 e retorna null se não existir/for de outra empresa.
     */
    private function authorizeCategory($id): ?array
    {
        $cat = Category::find((int) $id);
        if (!$cat || (int) $cat['company_id'] !== (int) $this->company['id']) {
            $this->sendError('Categoria não encontrada', 404);
            return null;
        }
        return $cat;
    }

    /** GET /api/{slug}/categories/{id} */
    public function getCategory($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $cat = $this->authorizeCategory($params['id'] ?? 0);
        if ($cat === null) return;
        $this->sendResponse(['category' => $cat]);
    }

    /** POST /api/{slug}/categories */
    public function createCategory($params): void
    {
        $this->loadCompany($params['slug'] ?? '');

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $this->sendError('Nome é obrigatório', 400);
            return;
        }

        $id = Category::create([
            'company_id' => (int) $this->company['id'],
            'name'       => $name,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'active'     => isset($input['active']) ? (int) (bool) $input['active'] : 1,
        ]);

        $this->sendResponse(['message' => 'Categoria criada', 'category' => Category::find($id)], 201);
    }

    /** POST /api/{slug}/categories/{id} (edição) */
    public function updateCategory($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $cat = $this->authorizeCategory($params['id'] ?? 0);
        if ($cat === null) return;

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? $cat['name']));
        if ($name === '') {
            $this->sendError('Nome não pode ser vazio', 400);
            return;
        }

        Category::update((int) $cat['id'], [
            'company_id' => (int) $this->company['id'],
            'name'       => $name,
            'sort_order' => isset($input['sort_order']) ? (int) $input['sort_order'] : (int) $cat['sort_order'],
            'active'     => isset($input['active']) ? (int) (bool) $input['active'] : (int) $cat['active'],
        ]);

        $this->sendResponse(['message' => 'Categoria atualizada', 'category' => Category::find((int) $cat['id'])]);
    }

    /** POST /api/{slug}/categories/{id}/toggle */
    public function toggleCategory($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $cat = $this->authorizeCategory($params['id'] ?? 0);
        if ($cat === null) return;

        Category::update((int) $cat['id'], [
            'company_id' => (int) $this->company['id'],
            'name'       => $cat['name'],
            'sort_order' => (int) $cat['sort_order'],
            'active'     => (int) $cat['active'] === 1 ? 0 : 1,
        ]);

        $this->sendResponse(['message' => 'Categoria atualizada', 'category' => Category::find((int) $cat['id'])]);
    }

    /** DELETE /api/{slug}/categories/{id} */
    public function deleteCategory($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $cat = $this->authorizeCategory($params['id'] ?? 0);
        if ($cat === null) return;

        Category::delete((int) $cat['id']);
        $this->sendResponse(['message' => 'Categoria excluída', 'id' => (int) $cat['id']]);
    }

    /**
     * POST /api/{slug}/categories/reorder
     * Body: { "order": [ {"id":1,"sort_order":0}, ... ] }
     */
    public function reorderCategories($params): void
    {
        $this->loadCompany($params['slug'] ?? '');

        $input = json_decode(file_get_contents('php://input'), true);
        $order = $input['order'] ?? null;
        if (!is_array($order) || $order === []) {
            $this->sendError('Lista "order" é obrigatória', 400);
            return;
        }

        $companyId = (int) $this->company['id'];
        $updated = 0;
        foreach ($order as $row) {
            $id = (int) ($row['id'] ?? 0);
            $cat = Category::find($id);
            if (!$cat || (int) $cat['company_id'] !== $companyId) {
                continue; // ignora ids inválidos/de outra empresa
            }
            Category::update($id, [
                'company_id' => $companyId,
                'name'       => $cat['name'],
                'sort_order' => (int) ($row['sort_order'] ?? $cat['sort_order']),
                'active'     => (int) $cat['active'],
            ]);
            $updated++;
        }

        $this->sendResponse(['message' => 'Ordem atualizada', 'updated' => $updated]);
    }

    /** Carrega um ingrediente garantindo posse pela empresa atual (ou 404). */
    private function authorizeIngredient($id): ?array
    {
        $ing = Ingredient::findForCompany((int) $this->company['id'], (int) $id);
        if (!$ing) {
            $this->sendError('Ingrediente não encontrado', 404);
            return null;
        }
        return $ing;
    }

    /** GET /api/{slug}/ingredients?search= */
    public function getIngredients($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
        $items = Ingredient::listByCompany(
            (int) $this->company['id'],
            null,
            $search !== '' ? $search : null
        );
        $this->sendResponse(['ingredients' => $items, 'total' => count($items)]);
    }

    /** GET /api/{slug}/ingredients/{id} */
    public function getIngredient($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $ing = $this->authorizeIngredient($params['id'] ?? 0);
        if ($ing === null) return;
        $this->sendResponse(['ingredient' => $ing]);
    }

    /** POST /api/{slug}/ingredients */
    public function createIngredient($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $this->sendError('Nome é obrigatório', 400);
            return;
        }
        if (Ingredient::existsByName($companyId, $name)) {
            $this->sendError('Já existe um ingrediente com esse nome', 409);
            return;
        }

        $id = Ingredient::create([
            'company_id'    => $companyId,
            'name'          => $name,
            'internal_name' => $input['internal_name'] ?? null,
            'cost'          => (float) ($input['cost'] ?? 0),
            'sale_price'    => (float) ($input['sale_price'] ?? 0),
            'unit'          => (string) ($input['unit'] ?? ''),
            'unit_value'    => (float) ($input['unit_value'] ?? 1),
            'min_qty'       => (int) ($input['min_qty'] ?? 0),
            'max_qty'       => (int) ($input['max_qty'] ?? 1),
            'image_path'    => $input['image_path'] ?? null,
        ]);

        $this->sendResponse(
            ['message' => 'Ingrediente criado', 'ingredient' => Ingredient::find($id)],
            201
        );
    }

    /** POST /api/{slug}/ingredients/{id} (edição) */
    public function updateIngredient($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        $ing = $this->authorizeIngredient($params['id'] ?? 0);
        if ($ing === null) return;

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? $ing['name']));
        if ($name === '') {
            $this->sendError('Nome não pode ser vazio', 400);
            return;
        }
        if (Ingredient::existsByName($companyId, $name, (int) $ing['id'])) {
            $this->sendError('Já existe outro ingrediente com esse nome', 409);
            return;
        }

        Ingredient::update((int) $ing['id'], [
            'name'          => $name,
            'internal_name' => $input['internal_name'] ?? $ing['internal_name'],
            'cost'          => isset($input['cost']) ? (float) $input['cost'] : (float) $ing['cost'],
            'sale_price'    => isset($input['sale_price']) ? (float) $input['sale_price'] : (float) $ing['sale_price'],
            'unit'          => $input['unit'] ?? $ing['unit'],
            'unit_value'    => isset($input['unit_value']) ? (float) $input['unit_value'] : (float) $ing['unit_value'],
            'min_qty'       => isset($input['min_qty']) ? (int) $input['min_qty'] : (int) $ing['min_qty'],
            'max_qty'       => isset($input['max_qty']) ? (int) $input['max_qty'] : (int) $ing['max_qty'],
            'image_path'    => array_key_exists('image_path', $input) ? $input['image_path'] : $ing['image_path'],
        ]);

        $this->sendResponse([
            'message'    => 'Ingrediente atualizado',
            'ingredient' => Ingredient::findForCompany($companyId, (int) $ing['id']),
        ]);
    }

    /** POST /api/{slug}/ingredients/{id}/toggle */
    public function toggleIngredient($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $new = Ingredient::toggleActive($companyId, (int) ($params['id'] ?? 0));
        if ($new === null) {
            $this->sendError('Ingrediente não encontrado', 404);
            return;
        }

        $this->sendResponse([
            'message'    => 'Ingrediente atualizado',
            'active'     => $new,
            'ingredient' => Ingredient::findForCompany($companyId, (int) $params['id']),
        ]);
    }

    /** DELETE /api/{slug}/ingredients/{id} */
    public function deleteIngredient($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        $ing = $this->authorizeIngredient($params['id'] ?? 0);
        if ($ing === null) return;

        Ingredient::delete($companyId, (int) $ing['id']);
        $this->sendResponse(['message' => 'Ingrediente excluído', 'id' => (int) $ing['id']]);
    }

    /**
     * GET /api/{slug}/settings/delivery
     * Cidades + zonas (bairros com taxa) + config de frete grátis.
     */
    public function getDelivery($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/DeliveryCity.php';
        require_once __DIR__ . '/../models/DeliveryZone.php';

        $cities = array_map(static fn(array $c): array => [
            'id'   => (int) $c['id'],
            'name' => $c['name'],
        ], DeliveryCity::allByCompany($companyId));

        $zones = array_map(static fn(array $z): array => [
            'id'           => (int) $z['id'],
            'city_id'      => (int) $z['city_id'],
            'city_name'    => $z['city_name'] ?? null,
            'neighborhood' => $z['neighborhood'],
            'fee'          => (float) $z['fee'],
        ], DeliveryZone::allByCompany($companyId));

        $this->sendResponse([
            'cities'        => $cities,
            'zones'         => $zones,
            'free_shipping' => [
                'free_enabled'    => (int) ($this->company['delivery_free_enabled'] ?? 0) === 1,
                'free_min_value'  => (float) ($this->company['delivery_free_min_value'] ?? 0),
                'after_hours_fee' => (float) ($this->company['delivery_after_hours_fee'] ?? 0),
            ],
        ]);
    }

    /** POST /api/{slug}/settings/delivery/cities */
    public function createDeliveryCity($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/DeliveryCity.php';

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $this->sendError('Nome da cidade é obrigatório', 400);
            return;
        }
        if (DeliveryCity::existsByName($companyId, $name)) {
            $this->sendError('Já existe uma cidade com esse nome', 409);
            return;
        }

        $id = DeliveryCity::create(['company_id' => $companyId, 'name' => $name]);
        $this->sendResponse(['message' => 'Cidade criada', 'city' => ['id' => $id, 'name' => $name]], 201);
    }

    /** POST /api/{slug}/settings/delivery/cities/{id} */
    public function updateDeliveryCity($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/DeliveryCity.php';

        $city = DeliveryCity::findForCompany((int) ($params['id'] ?? 0), $companyId);
        if (!$city) {
            $this->sendError('Cidade não encontrada', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $this->sendError('Nome da cidade é obrigatório', 400);
            return;
        }
        if (DeliveryCity::existsByNameExcept($companyId, $name, (int) $city['id'])) {
            $this->sendError('Já existe outra cidade com esse nome', 409);
            return;
        }

        DeliveryCity::update((int) $city['id'], $companyId, $name);
        $this->sendResponse(['message' => 'Cidade atualizada', 'city' => ['id' => (int) $city['id'], 'name' => $name]]);
    }

    /** DELETE /api/{slug}/settings/delivery/cities/{id} */
    public function deleteDeliveryCity($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/DeliveryCity.php';

        $city = DeliveryCity::findForCompany((int) ($params['id'] ?? 0), $companyId);
        if (!$city) {
            $this->sendError('Cidade não encontrada', 404);
            return;
        }

        DeliveryCity::delete((int) $city['id'], $companyId);
        $this->sendResponse(['message' => 'Cidade excluída', 'id' => (int) $city['id']]);
    }

    /** POST /api/{slug}/settings/delivery/zones */
    public function createDeliveryZone($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/DeliveryCity.php';
        require_once __DIR__ . '/../models/DeliveryZone.php';

        $input = json_decode(file_get_contents('php://input'), true);
        $cityId = (int) ($input['city_id'] ?? 0);
        $neighborhood = trim((string) ($input['neighborhood'] ?? ''));
        $fee = (float) ($input['fee'] ?? 0);

        if (!DeliveryCity::findForCompany($cityId, $companyId)) {
            $this->sendError('Cidade inválida para esta loja', 400);
            return;
        }
        if ($neighborhood === '') {
            $this->sendError('Bairro é obrigatório', 400);
            return;
        }
        if (DeliveryZone::existsForCity($companyId, $cityId, $neighborhood)) {
            $this->sendError('Esse bairro já existe nesta cidade', 409);
            return;
        }

        $id = DeliveryZone::create([
            'company_id'   => $companyId,
            'city_id'      => $cityId,
            'neighborhood' => $neighborhood,
            'fee'          => $fee,
        ]);
        $this->sendResponse(
            ['message' => 'Bairro criado', 'zone' => DeliveryZone::findForCompany($id, $companyId)],
            201
        );
    }

    /** POST /api/{slug}/settings/delivery/zones/{id} */
    public function updateDeliveryZone($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/DeliveryCity.php';
        require_once __DIR__ . '/../models/DeliveryZone.php';

        $zone = DeliveryZone::findForCompany((int) ($params['id'] ?? 0), $companyId);
        if (!$zone) {
            $this->sendError('Bairro não encontrado', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $cityId = isset($input['city_id']) ? (int) $input['city_id'] : (int) $zone['city_id'];
        $neighborhood = array_key_exists('neighborhood', $input) ? trim((string) $input['neighborhood']) : $zone['neighborhood'];
        $fee = isset($input['fee']) ? (float) $input['fee'] : (float) $zone['fee'];

        if (!DeliveryCity::findForCompany($cityId, $companyId)) {
            $this->sendError('Cidade inválida para esta loja', 400);
            return;
        }
        if ($neighborhood === '') {
            $this->sendError('Bairro é obrigatório', 400);
            return;
        }
        if (DeliveryZone::existsForCityExcept($companyId, $cityId, $neighborhood, (int) $zone['id'])) {
            $this->sendError('Esse bairro já existe nesta cidade', 409);
            return;
        }

        DeliveryZone::update((int) $zone['id'], $companyId, [
            'city_id'      => $cityId,
            'neighborhood' => $neighborhood,
            'fee'          => $fee,
        ]);
        $this->sendResponse([
            'message' => 'Bairro atualizado',
            'zone'    => DeliveryZone::findForCompany((int) $zone['id'], $companyId),
        ]);
    }

    /** DELETE /api/{slug}/settings/delivery/zones/{id} */
    public function deleteDeliveryZone($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/DeliveryZone.php';

        $zone = DeliveryZone::findForCompany((int) ($params['id'] ?? 0), $companyId);
        if (!$zone) {
            $this->sendError('Bairro não encontrado', 404);
            return;
        }

        DeliveryZone::delete((int) $zone['id'], $companyId);
        $this->sendResponse(['message' => 'Bairro excluído', 'id' => (int) $zone['id']]);
    }

    /**
     * POST /api/{slug}/settings/delivery/adjust-fees
     * Ajusta TODAS as taxas de zona por um delta (não deixa negativo).
     * Body: { "delta": 2.0 }  (ou negativo)
     */
    public function adjustDeliveryFees($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/DeliveryZone.php';

        $input = json_decode(file_get_contents('php://input'), true);
        $delta = (float) ($input['delta'] ?? 0);
        if ($delta === 0.0) {
            $this->sendError('Informe um delta diferente de zero', 400);
            return;
        }

        DeliveryZone::adjustFees($companyId, $delta);
        $this->sendResponse([
            'message' => 'Taxas ajustadas',
            'zones'   => array_map(static fn(array $z): array => [
                'id'  => (int) $z['id'],
                'neighborhood' => $z['neighborhood'],
                'fee' => (float) $z['fee'],
            ], DeliveryZone::allByCompany($companyId)),
        ]);
    }

    /**
     * POST /api/{slug}/settings/delivery/free-shipping
     * Frete grátis promocional acima de um valor. Body: { "free_min_value": 50 }.
     * Valor > 0 ativa a promo e desativa o frete grátis geral.
     */
    public function updateFreeShipping($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        $minValue = (float) ($input['free_min_value'] ?? 0);
        if ($minValue < 0) {
            $minValue = 0;
        }

        $data = ['delivery_free_min_value' => $minValue];
        if ($minValue > 0) {
            $data['delivery_free_enabled'] = 0; // promo desativa o frete grátis geral
        }
        Company::updateSettings($companyId, $data);

        $fresh = Company::find($companyId) ?? $this->company;
        $this->sendResponse([
            'message'       => 'Frete grátis atualizado',
            'free_shipping' => [
                'free_enabled'   => (int) ($fresh['delivery_free_enabled'] ?? 0) === 1,
                'free_min_value' => (float) ($fresh['delivery_free_min_value'] ?? 0),
            ],
        ]);
    }

    /** Curadoria de um método de pagamento (decodifica meta JSON). */
    private function curatePayment(array $p): array
    {
        return [
            'id'           => (int) $p['id'],
            'name'         => $p['name'] ?? null,
            'type'         => $p['type'] ?? 'others',
            'pix_key'      => $p['pix_key'] ?? null,
            'instructions' => $p['instructions'] ?? null,
            'icon'         => $p['icon'] ?? null,
            'sort_order'   => (int) ($p['sort_order'] ?? 0),
            'active'       => (int) ($p['active'] ?? 0) === 1,
            'meta'         => !empty($p['meta']) ? json_decode((string) $p['meta'], true) : null,
        ];
    }

    /** GET /api/{slug}/settings/payments */
    public function getPayments($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        require_once __DIR__ . '/../models/PaymentMethod.php';
        $items = array_map([$this, 'curatePayment'], PaymentMethod::allByCompany((int) $this->company['id']));
        $this->sendResponse(['payment_methods' => $items, 'total' => count($items)]);
    }

    /** POST /api/{slug}/settings/payments */
    public function createPayment($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $this->sendError('Nome é obrigatório', 400);
            return;
        }

        require_once __DIR__ . '/../models/PaymentMethod.php';
        $data = [
            'company_id'   => $companyId,
            'name'         => $name,
            'type'         => trim((string) ($input['type'] ?? 'others')) ?: 'others',
            'pix_key'      => $input['pix_key'] ?? null,
            'instructions' => $input['instructions'] ?? null,
            'icon'         => $input['icon'] ?? null,
            'active'       => isset($input['active']) ? (bool) $input['active'] : true,
            'meta'         => $input['meta'] ?? null,
        ];
        if (isset($input['sort_order'])) {
            $data['sort_order'] = (int) $input['sort_order'];
        }

        $id = PaymentMethod::create($data);
        $this->sendResponse(
            ['message' => 'Pagamento criado', 'payment_method' => $this->curatePayment(PaymentMethod::findForCompany($id, $companyId))],
            201
        );
    }

    /** POST /api/{slug}/settings/payments/{id} (edição) */
    public function updatePayment($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        require_once __DIR__ . '/../models/PaymentMethod.php';
        $pm = PaymentMethod::findForCompany((int) ($params['id'] ?? 0), $companyId);
        if (!$pm) {
            $this->sendError('Método de pagamento não encontrado', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? $pm['name']));
        if ($name === '') {
            $this->sendError('Nome não pode ser vazio', 400);
            return;
        }

        $metaVal = array_key_exists('meta', $input)
            ? $input['meta']
            : (!empty($pm['meta']) ? json_decode((string) $pm['meta'], true) : null);

        PaymentMethod::update((int) $pm['id'], $companyId, [
            'name'         => $name,
            'instructions' => array_key_exists('instructions', $input) ? $input['instructions'] : $pm['instructions'],
            'sort_order'   => isset($input['sort_order']) ? (int) $input['sort_order'] : (int) $pm['sort_order'],
            'active'       => isset($input['active']) ? (bool) $input['active'] : ((int) $pm['active'] === 1),
            'type'         => $input['type'] ?? $pm['type'],
            'meta'         => $metaVal,
            'icon'         => array_key_exists('icon', $input) ? $input['icon'] : $pm['icon'],
            'pix_key'      => array_key_exists('pix_key', $input) ? $input['pix_key'] : $pm['pix_key'],
        ]);

        $this->sendResponse([
            'message'        => 'Pagamento atualizado',
            'payment_method' => $this->curatePayment(PaymentMethod::findForCompany((int) $pm['id'], $companyId)),
        ]);
    }

    /** POST /api/{slug}/settings/payments/{id}/toggle */
    public function togglePayment($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        require_once __DIR__ . '/../models/PaymentMethod.php';
        $pm = PaymentMethod::findForCompany((int) ($params['id'] ?? 0), $companyId);
        if (!$pm) {
            $this->sendError('Método de pagamento não encontrado', 404);
            return;
        }

        $metaVal = !empty($pm['meta']) ? json_decode((string) $pm['meta'], true) : null;
        PaymentMethod::update((int) $pm['id'], $companyId, [
            'name'         => $pm['name'],
            'instructions' => $pm['instructions'],
            'sort_order'   => (int) $pm['sort_order'],
            'active'       => (int) $pm['active'] === 1 ? false : true,
            'type'         => $pm['type'],
            'meta'         => $metaVal,
            'icon'         => $pm['icon'],
            'pix_key'      => $pm['pix_key'],
        ]);

        $this->sendResponse([
            'message'        => 'Pagamento atualizado',
            'payment_method' => $this->curatePayment(PaymentMethod::findForCompany((int) $pm['id'], $companyId)),
        ]);
    }

    /** DELETE /api/{slug}/settings/payments/{id} */
    public function deletePayment($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        require_once __DIR__ . '/../models/PaymentMethod.php';
        $pm = PaymentMethod::findForCompany((int) ($params['id'] ?? 0), $companyId);
        if (!$pm) {
            $this->sendError('Método de pagamento não encontrado', 404);
            return;
        }

        PaymentMethod::delete((int) $pm['id'], $companyId);
        $this->sendResponse(['message' => 'Pagamento excluído', 'id' => (int) $pm['id']]);
    }

    /** Curadoria de uma despesa (tipos numéricos/booleanos). */
    private function curateExpense(array $e): array
    {
        return [
            'id'              => (int) $e['id'],
            'description'     => $e['description'] ?? null,
            'amount'          => (float) ($e['amount'] ?? 0),
            'expense_date'    => $e['expense_date'] ?? null,
            'reference_month' => $e['reference_month'] ?? null,
            'category_id'     => isset($e['category_id']) ? (int) $e['category_id'] : null,
            'category_name'   => $e['category_name'] ?? null,
            'category_color'  => $e['category_color'] ?? null,
            'payment_method'  => $e['payment_method'] ?? null,
            'is_recurring'    => (int) ($e['is_recurring'] ?? 0) === 1,
            'recurrence_type' => $e['recurrence_type'] ?? null,
            'notes'           => $e['notes'] ?? null,
        ];
    }

    /** Carrega despesa garantindo posse pela empresa (ou 404). */
    private function authorizeExpense($id): ?array
    {
        require_once __DIR__ . '/../models/Expense.php';
        $e = Expense::findForCompany((int) $this->company['id'], (int) $id);
        if (!$e) {
            $this->sendError('Despesa não encontrada', 404);
            return null;
        }
        return $e;
    }

    /** Valida que category_id (se informado) pertence à empresa. */
    private function validExpenseCategory($categoryId): bool
    {
        if ($categoryId === null || $categoryId === '' || (int) $categoryId === 0) {
            return true;
        }
        require_once __DIR__ . '/../models/ExpenseCategory.php';
        if (!ExpenseCategory::findForCompany((int) $this->company['id'], (int) $categoryId)) {
            $this->sendError('Categoria de despesa inválida para esta loja', 400);
            return false;
        }
        return true;
    }

    /** GET /api/{slug}/expenses?reference_month=YYYY-MM&category_id= */
    public function getExpenses($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        require_once __DIR__ . '/../models/Expense.php';

        $month = isset($_GET['reference_month']) ? trim((string) $_GET['reference_month']) : null;
        $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;

        $items = array_map([$this, 'curateExpense'], Expense::listByCompany(
            (int) $this->company['id'],
            $month ?: null,
            null,
            $categoryId ?: null
        ));
        $this->sendResponse(['expenses' => $items, 'total' => count($items)]);
    }

    /** GET /api/{slug}/expenses/{id} */
    public function getExpense($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $e = $this->authorizeExpense($params['id'] ?? 0);
        if ($e === null) return;
        $this->sendResponse(['expense' => $this->curateExpense($e)]);
    }

    /** POST /api/{slug}/expenses */
    public function createExpense($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/Expense.php';

        $input = json_decode(file_get_contents('php://input'), true);
        $description = trim((string) ($input['description'] ?? ''));
        if ($description === '') {
            $this->sendError('Descrição é obrigatória', 400);
            return;
        }
        if (!isset($input['amount']) || !is_numeric($input['amount']) || (float) $input['amount'] <= 0) {
            $this->sendError('Valor (amount) deve ser maior que zero', 400);
            return;
        }
        $expenseDate = trim((string) ($input['expense_date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expenseDate) || !strtotime($expenseDate)) {
            $this->sendError('Data inválida (use YYYY-MM-DD)', 400);
            return;
        }
        if (!$this->validExpenseCategory($input['category_id'] ?? null)) return;

        $id = Expense::create([
            'company_id'      => $companyId,
            'category_id'     => $input['category_id'] ?? null,
            'description'     => $description,
            'amount'          => (float) $input['amount'],
            'expense_date'    => $expenseDate,
            'is_recurring'    => !empty($input['is_recurring']) ? 1 : 0,
            'recurrence_type' => in_array($input['recurrence_type'] ?? null, ['monthly', 'yearly'], true) ? $input['recurrence_type'] : null,
            'payment_method'  => $input['payment_method'] ?? null,
            'notes'           => $input['notes'] ?? null,
        ]);

        $this->sendResponse(
            ['message' => 'Despesa criada', 'expense' => $this->curateExpense(Expense::findForCompany($companyId, $id))],
            201
        );
    }

    /** POST /api/{slug}/expenses/{id} (edição) */
    public function updateExpense($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/Expense.php';

        $e = $this->authorizeExpense($params['id'] ?? 0);
        if ($e === null) return;

        $input = json_decode(file_get_contents('php://input'), true);
        $description = trim((string) ($input['description'] ?? $e['description']));
        if ($description === '') {
            $this->sendError('Descrição não pode ser vazia', 400);
            return;
        }
        $amount = isset($input['amount']) ? (float) $input['amount'] : (float) $e['amount'];
        if ($amount <= 0) {
            $this->sendError('Valor deve ser maior que zero', 400);
            return;
        }
        $expenseDate = trim((string) ($input['expense_date'] ?? $e['expense_date']));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expenseDate) || !strtotime($expenseDate)) {
            $this->sendError('Data inválida (use YYYY-MM-DD)', 400);
            return;
        }
        if (array_key_exists('category_id', $input) && !$this->validExpenseCategory($input['category_id'])) return;

        Expense::update((int) $e['id'], [
            'category_id'     => array_key_exists('category_id', $input) ? $input['category_id'] : ($e['category_id'] ?? null),
            'description'     => $description,
            'amount'          => $amount,
            'expense_date'    => $expenseDate,
            'is_recurring'    => isset($input['is_recurring']) ? (!empty($input['is_recurring']) ? 1 : 0) : (int) ($e['is_recurring'] ?? 0),
            'recurrence_type' => array_key_exists('recurrence_type', $input)
                ? (in_array($input['recurrence_type'], ['monthly', 'yearly'], true) ? $input['recurrence_type'] : null)
                : ($e['recurrence_type'] ?? null),
            'payment_method'  => array_key_exists('payment_method', $input) ? $input['payment_method'] : ($e['payment_method'] ?? null),
            'notes'           => array_key_exists('notes', $input) ? $input['notes'] : ($e['notes'] ?? null),
        ]);

        $this->sendResponse([
            'message' => 'Despesa atualizada',
            'expense' => $this->curateExpense(Expense::findForCompany($companyId, (int) $e['id'])),
        ]);
    }

    /** DELETE /api/{slug}/expenses/{id} */
    public function deleteExpense($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        require_once __DIR__ . '/../models/Expense.php';
        $e = $this->authorizeExpense($params['id'] ?? 0);
        if ($e === null) return;

        Expense::delete((int) $e['id']);
        $this->sendResponse(['message' => 'Despesa excluída', 'id' => (int) $e['id']]);
    }

    /** GET /api/{slug}/expenses/categories?type=fixed|variable */
    public function getExpenseCategories($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        require_once __DIR__ . '/../models/ExpenseCategory.php';
        $type = isset($_GET['type']) && in_array($_GET['type'], ['fixed', 'variable'], true) ? $_GET['type'] : null;
        $items = ExpenseCategory::listByCompany((int) $this->company['id'], $type);
        $this->sendResponse(['categories' => $items, 'total' => count($items)]);
    }

    /** POST /api/{slug}/expenses/categories */
    public function createExpenseCategory($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/ExpenseCategory.php';

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $this->sendError('Nome é obrigatório', 400);
            return;
        }
        $type = in_array($input['type'] ?? 'fixed', ['fixed', 'variable'], true) ? $input['type'] : 'fixed';

        $id = ExpenseCategory::create([
            'company_id'  => $companyId,
            'name'        => $name,
            'type'        => $type,
            'description' => $input['description'] ?? null,
            'color'       => $input['color'] ?? '#3B82F6',
            'icon'        => $input['icon'] ?? 'currency-dollar',
        ]);

        $this->sendResponse(
            ['message' => 'Categoria criada', 'category' => ExpenseCategory::findForCompany($companyId, $id)],
            201
        );
    }

    /** POST /api/{slug}/expenses/categories/{id} (edição) */
    public function updateExpenseCategory($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/ExpenseCategory.php';

        $cat = ExpenseCategory::findForCompany($companyId, (int) ($params['id'] ?? 0));
        if (!$cat) {
            $this->sendError('Categoria não encontrada', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? $cat['name']));
        if ($name === '') {
            $this->sendError('Nome não pode ser vazio', 400);
            return;
        }

        ExpenseCategory::update((int) $cat['id'], [
            'name'        => $name,
            'type'        => in_array($input['type'] ?? null, ['fixed', 'variable'], true) ? $input['type'] : $cat['type'],
            'description' => array_key_exists('description', $input) ? $input['description'] : ($cat['description'] ?? null),
            'color'       => $input['color'] ?? ($cat['color'] ?? '#3B82F6'),
            'icon'        => $input['icon'] ?? ($cat['icon'] ?? 'currency-dollar'),
        ]);

        $this->sendResponse([
            'message'  => 'Categoria atualizada',
            'category' => ExpenseCategory::findForCompany($companyId, (int) $cat['id']),
        ]);
    }

    /** DELETE /api/{slug}/expenses/categories/{id} (soft delete) */
    public function deleteExpenseCategory($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/ExpenseCategory.php';

        $cat = ExpenseCategory::findForCompany($companyId, (int) ($params['id'] ?? 0));
        if (!$cat) {
            $this->sendError('Categoria não encontrada', 404);
            return;
        }

        ExpenseCategory::delete((int) $cat['id']);
        $this->sendResponse(['message' => 'Categoria removida', 'id' => (int) $cat['id']]);
    }

    /** POST /api/{slug}/expenses/categories/seed (cria categorias padrão) */
    public function seedExpenseCategories($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../models/ExpenseCategory.php';

        $seeded = ExpenseCategory::seedDefaults($companyId);
        $this->sendResponse([
            'message'    => 'Categorias padrão criadas',
            'seeded'     => $seeded,
            'categories' => ExpenseCategory::listByCompany($companyId),
        ]);
    }

    /** GET /api/{slug}/pause/status */
    public function pauseStatus($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        require_once __DIR__ . '/../services/ScheduledPauseService.php';
        $svc = new ScheduledPauseService(db());
        $this->sendResponse(['pause' => $svc->getPauseStatus((int) $this->company['id'])]);
    }

    /**
     * POST /api/{slug}/pause/enable
     * Body: { "minutes": 30 } | { "until": "2026-05-31 23:00:00" } | { "indefinite": true }, "reason"?
     */
    public function pauseEnable($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../services/ScheduledPauseService.php';
        $svc = new ScheduledPauseService(db());

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $reason = isset($input['reason']) ? trim((string) $input['reason']) : null;

        try {
            if (!empty($input['until'])) {
                $svc->enableScheduledPause($companyId, (string) $input['until'], $reason);
            } elseif (!empty($input['indefinite'])) {
                $svc->enableIndefinitePause($companyId, $reason);
            } else {
                $minutes = (int) ($input['minutes'] ?? 30);
                if ($minutes <= 0) {
                    $this->sendError('minutes deve ser maior que zero', 400);
                    return;
                }
                $svc->enableTimedPause($companyId, $minutes, $reason);
            }
        } catch (\Throwable $e) {
            $this->sendError('Erro ao pausar: ' . $e->getMessage(), 500);
            return;
        }

        $this->sendResponse(['message' => 'Loja pausada', 'pause' => $svc->getPauseStatus($companyId)]);
    }

    /** POST /api/{slug}/pause/disable */
    public function pauseDisable($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../services/ScheduledPauseService.php';
        $svc = new ScheduledPauseService(db());

        $svc->disablePause($companyId);
        $this->sendResponse(['message' => 'Pausa removida', 'pause' => $svc->getPauseStatus($companyId)]);
    }

    /**
     * POST /api/{slug}/pause/extend
     * Estende a pausa atual em N minutos. Body: { "minutes": 15 }.
     * Só funciona com pausa por tempo/agendada (com pause_until).
     */
    public function pauseExtend($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        require_once __DIR__ . '/../services/ScheduledPauseService.php';
        $svc = new ScheduledPauseService(db());

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $minutes = (int) ($input['minutes'] ?? 0);
        if ($minutes <= 0) {
            $this->sendError('minutes deve ser maior que zero', 400);
            return;
        }

        $status = $svc->getPauseStatus($companyId);
        if (empty($status['is_paused']) || empty($status['pause_until'])) {
            $this->sendError('A loja não está em pausa com prazo definido para estender', 400);
            return;
        }

        try {
            $until = new \DateTime((string) $status['pause_until'], new \DateTimeZone('America/Sao_Paulo'));
            $until->modify("+{$minutes} minutes");
            $svc->enableScheduledPause($companyId, $until->format('Y-m-d H:i:s'), $status['pause_reason'] ?? null);
        } catch (\Throwable $e) {
            $this->sendError('Erro ao estender pausa: ' . $e->getMessage(), 500);
            return;
        }

        $this->sendResponse(['message' => 'Pausa estendida', 'pause' => $svc->getPauseStatus($companyId)]);
    }

    /** GET /api/{slug}/settings/profile */
    public function getProfile($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $userId = (int) ($this->authData['user_id'] ?? 0);
        $user = User::findById($userId);
        if (!$user) {
            $this->sendError('Usuário não encontrado', 404);
            return;
        }
        $this->sendResponse(['profile' => [
            'id'    => (int) $user['id'],
            'name'  => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'role'  => $user['role'] ?? null,
        ]]);
    }

    /**
     * POST /api/{slug}/settings/profile
     * Body: { "name", "email", "current_password"?, "new_password"? }
     * Troca de senha exige current_password correta e new_password >= 6.
     */
    public function updateProfile($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $userId = (int) ($this->authData['user_id'] ?? 0);
        $user = User::findById($userId);
        if (!$user) {
            $this->sendError('Usuário não encontrado', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->sendError('Dados JSON inválidos', 400);
            return;
        }

        $name = trim((string) ($input['name'] ?? $user['name']));
        $email = trim((string) ($input['email'] ?? $user['email']));

        if ($name === '' || $email === '') {
            $this->sendError('Nome e e-mail são obrigatórios', 400);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendError('E-mail inválido', 400);
            return;
        }
        if (User::emailTakenByOther($email, $userId)) {
            $this->sendError('Este e-mail já está em uso', 409);
            return;
        }

        $passwordHash = null;
        $newPassword = (string) ($input['new_password'] ?? '');
        if ($newPassword !== '') {
            $current = (string) ($input['current_password'] ?? '');
            if ($current === '' || !password_verify($current, (string) $user['password_hash'])) {
                $this->sendError('Senha atual incorreta', 403);
                return;
            }
            if (strlen($newPassword) < 6) {
                $this->sendError('A nova senha deve ter ao menos 6 caracteres', 400);
                return;
            }
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        User::updateProfile($userId, $name, $email, $passwordHash);

        $this->sendResponse(['message' => 'Perfil atualizado', 'profile' => [
            'id'    => $userId,
            'name'  => $name,
            'email' => $email,
            'role'  => $user['role'] ?? null,
        ]]);
    }

    /** Normaliza horário "HH:MM"/"HH:MM:SS" → "HH:MM:SS" (ou null). */
    private function parseHourTime($t): ?string
    {
        $t = trim((string) $t);
        if ($t === '') return null;
        if (preg_match('/^\d{1,2}:\d{2}$/', $t)) return $t . ':00';
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $t)) return $t;
        return null;
    }

    /** Carrega os 7 dias de company_hours (cria defaults se não existirem). */
    private function loadCompanyHours(int $companyId): array
    {
        $pdo = db();
        $sql = 'SELECT weekday, is_open, open1, close1, open2, close2
                FROM company_hours WHERE company_id = ? ORDER BY weekday';
        $st = $pdo->prepare($sql);
        $st->execute([$companyId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            for ($d = 1; $d <= 7; $d++) {
                $pdo->prepare('INSERT INTO company_hours (company_id, weekday, is_open) VALUES (?, ?, 0)')
                    ->execute([$companyId, $d]);
            }
            $st->execute([$companyId]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        }

        return array_map(static fn(array $r): array => [
            'weekday' => (int) $r['weekday'],
            'is_open' => (int) $r['is_open'] === 1,
            'open1'   => $r['open1'],
            'close1'  => $r['close1'],
            'open2'   => $r['open2'],
            'close2'  => $r['close2'],
        ], $rows);
    }

    /**
     * GET /api/{slug}/settings/hours
     * Retorna os 7 dias (weekday 1=Seg ... 7=Dom).
     */
    public function getHours($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $this->sendResponse(['hours' => $this->loadCompanyHours((int) $this->company['id'])]);
    }

    /**
     * POST /api/{slug}/settings/hours
     * Body: { "hours": [ { "weekday":1, "is_open":true, "open1":"18:00", "close1":"23:00", "open2":null, "close2":null }, ... ] }
     * Dias não enviados permanecem como estão. Se is_open=false, limpa horários.
     */
    public function updateHours($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        $list = $input['hours'] ?? null;
        if (!is_array($list) || $list === []) {
            $this->sendError('Lista "hours" é obrigatória', 400);
            return;
        }

        $pdo = db();
        $sql = 'INSERT INTO company_hours (company_id, weekday, is_open, open1, close1, open2, close2)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    is_open = VALUES(is_open), open1 = VALUES(open1), close1 = VALUES(close1),
                    open2 = VALUES(open2), close2 = VALUES(close2)';
        $stmt = $pdo->prepare($sql);

        $updated = 0;
        foreach ($list as $h) {
            if (!is_array($h)) continue;
            $weekday = (int) ($h['weekday'] ?? 0);
            if ($weekday < 1 || $weekday > 7) continue;

            $isOpen = !empty($h['is_open']) ? 1 : 0;
            $open1 = $this->parseHourTime($h['open1'] ?? null);
            $close1 = $this->parseHourTime($h['close1'] ?? null);
            $open2 = $this->parseHourTime($h['open2'] ?? null);
            $close2 = $this->parseHourTime($h['close2'] ?? null);

            if (!$isOpen) {
                $open1 = $close1 = $open2 = $close2 = null;
            }

            $stmt->execute([$companyId, $weekday, $isOpen, $open1, $close1, $open2, $close2]);
            $updated++;
        }

        $this->sendResponse([
            'message' => 'Horários atualizados',
            'updated' => $updated,
            'hours'   => $this->loadCompanyHours($companyId),
        ]);
    }

    /** Monta o objeto de configurações da loja a partir da linha companies. */
    private function curateStore(array $c): array
    {
        return [
            'name'                  => $c['name'] ?? null,
            'whatsapp'              => $c['whatsapp'] ?? null,
            'address'               => $c['address'] ?? null,
            'min_order'             => isset($c['min_order']) ? (float) $c['min_order'] : null,
            'avg_delivery_min_from' => isset($c['avg_delivery_min_from']) ? (int) $c['avg_delivery_min_from'] : null,
            'avg_delivery_min_to'   => isset($c['avg_delivery_min_to']) ? (int) $c['avg_delivery_min_to'] : null,
            'logo'                  => $c['logo'] ?? null,
            'banner'                => $c['banner'] ?? null,
            'colors'                => [
                'menu_header_text_color'      => $c['menu_header_text_color'] ?? null,
                'menu_header_button_color'    => $c['menu_header_button_color'] ?? null,
                'menu_header_bg_color'        => $c['menu_header_bg_color'] ?? null,
                'menu_logo_border_color'      => $c['menu_logo_border_color'] ?? null,
                'menu_group_title_bg_color'   => $c['menu_group_title_bg_color'] ?? null,
                'menu_group_title_text_color' => $c['menu_group_title_text_color'] ?? null,
                'menu_welcome_bg_color'       => $c['menu_welcome_bg_color'] ?? null,
                'menu_welcome_text_color'     => $c['menu_welcome_text_color'] ?? null,
            ],
        ];
    }

    /** Valida cor hexadecimal (#RGB ou #RRGGBB); retorna null se inválida. */
    private function normalizeHexColor($value): ?string
    {
        $v = trim((string) $value);
        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v) ? $v : null;
    }

    /**
     * Faz upload seguro de uma imagem (campo $_FILES) para public/uploads.
     * @return array{path: string|null, error: string|null}
     */
    private function uploadStoreImage(?array $file, string $prefix): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => 'Nenhum arquivo enviado (use o campo "image")'];
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'Erro no upload (código ' . $file['error'] . ')'];
        }
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return ['path' => null, 'error' => 'Formato inválido. Use JPG, PNG ou WEBP.'];
        }
        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            return ['path' => null, 'error' => 'Arquivo temporário inexistente'];
        }

        $name = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dir = __DIR__ . '/../../public/uploads';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return ['path' => null, 'error' => 'Falha ao salvar o arquivo'];
        }
        return ['path' => 'uploads/' . $name, 'error' => null];
    }

    /** Trata POST de logo/banner: faz upload, atualiza companies e remove o antigo. */
    private function handleStoreImageUpload(array $params, string $field): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $result = $this->uploadStoreImage($_FILES['image'] ?? null, $field);
        if ($result['error'] !== null) {
            $this->sendError($result['error'], 422);
            return;
        }

        $old = $this->company[$field] ?? null;
        Company::updateSettings($companyId, [$field => $result['path']]);

        if ($old && str_starts_with((string) $old, 'uploads/')) {
            $oldFile = __DIR__ . '/../../public/' . $old;
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        $this->sendResponse(['message' => 'Imagem atualizada', $field => $result['path']]);
    }

    /** POST /api/{slug}/settings/store/logo  (multipart, campo "image") */
    public function uploadStoreLogo($params): void
    {
        $this->handleStoreImageUpload($params, 'logo');
    }

    /** POST /api/{slug}/settings/store/banner  (multipart, campo "image") */
    public function uploadStoreBanner($params): void
    {
        $this->handleStoreImageUpload($params, 'banner');
    }

    /** GET /api/{slug}/settings/store */
    public function getStoreSettings($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $this->sendResponse(['store' => $this->curateStore($this->company)]);
    }

    /**
     * POST /api/{slug}/settings/store
     * Atualiza dados da loja (campos omitidos preservam o valor atual).
     * Cores podem vir em "colors": { menu_header_bg_color: "#RRGGBB", ... }.
     */
    public function updateStoreSettings($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->sendError('Dados JSON inválidos', 400);
            return;
        }

        $data = [];

        if (array_key_exists('name', $input)) {
            $name = trim((string) $input['name']);
            if ($name === '') {
                $this->sendError('Nome não pode ser vazio', 400);
                return;
            }
            $data['name'] = $name;
        }
        if (array_key_exists('whatsapp', $input)) {
            // Normaliza para apenas dígitos.
            $data['whatsapp'] = preg_replace('/\D+/', '', (string) $input['whatsapp']);
        }
        if (array_key_exists('address', $input)) {
            $data['address'] = trim((string) $input['address']);
        }
        if (array_key_exists('min_order', $input)) {
            $mo = $input['min_order'];
            $data['min_order'] = ($mo === null || $mo === '') ? null : (float) str_replace(',', '.', (string) $mo);
        }
        foreach (['avg_delivery_min_from', 'avg_delivery_min_to'] as $f) {
            if (array_key_exists($f, $input)) {
                $data[$f] = ($input[$f] === null || $input[$f] === '') ? null : (int) $input[$f];
            }
        }

        // Cores (aceita tanto top-level quanto dentro de "colors").
        $colorSource = is_array($input['colors'] ?? null) ? $input['colors'] : $input;
        $colorCols = [
            'menu_header_text_color', 'menu_header_button_color', 'menu_header_bg_color',
            'menu_logo_border_color', 'menu_group_title_bg_color', 'menu_group_title_text_color',
            'menu_welcome_bg_color', 'menu_welcome_text_color',
        ];
        foreach ($colorCols as $col) {
            if (array_key_exists($col, $colorSource)) {
                $color = $this->normalizeHexColor($colorSource[$col]);
                if ($color === null && trim((string) $colorSource[$col]) !== '') {
                    $this->sendError("Cor inválida em {$col} (use #RRGGBB)", 400);
                    return;
                }
                $data[$col] = $color;
            }
        }

        if (empty($data)) {
            $this->sendError('Nenhum campo para atualizar', 400);
            return;
        }

        Company::updateSettings($companyId, $data);

        $fresh = Company::find($companyId) ?? $this->company;
        $this->sendResponse(['message' => 'Configurações atualizadas', 'store' => $this->curateStore($fresh)]);
    }

    /** Carrega um grupo de cross-sell garantindo posse pela empresa (ou 404). */
    private function authorizeCrossSell($id): ?array
    {
        require_once __DIR__ . '/../models/CrossSellGroup.php';
        $g = CrossSellGroup::findById((int) $id);
        if (!$g || (int) $g['company_id'] !== (int) $this->company['id']) {
            $this->sendError('Grupo de cross-sell não encontrado', 404);
            return null;
        }
        return $g;
    }

    /** GET /api/{slug}/cross-sell */
    public function getCrossSell($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        require_once __DIR__ . '/../models/CrossSellGroup.php';
        $groups = CrossSellGroup::getByCompany((int) $this->company['id']);
        $this->sendResponse(['groups' => $groups, 'total' => count($groups)]);
    }

    /** GET /api/{slug}/cross-sell/{id} */
    public function getCrossSellGroup($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $g = $this->authorizeCrossSell($params['id'] ?? 0);
        if ($g === null) return;
        $this->sendResponse(['group' => $g]);
    }

    /**
     * POST /api/{slug}/cross-sell  (upsert por categoria-gatilho)
     * Body: { "trigger_category_id": 1, "recommendations": [ { "category_id": 2, "section_title": "Bebidas" } ] }
     */
    public function saveCrossSell($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->sendError('Dados JSON inválidos', 400);
            return;
        }

        $triggerId = (int) ($input['trigger_category_id'] ?? 0);
        if ($triggerId <= 0) {
            $this->sendError('trigger_category_id é obrigatório', 400);
            return;
        }
        $tc = Category::find($triggerId);
        if (!$tc || (int) $tc['company_id'] !== $companyId) {
            $this->sendError('Categoria disparadora inválida para esta loja', 400);
            return;
        }

        // Normaliza/valida recomendações: categoria da loja, título e != gatilho.
        $recommendations = [];
        foreach (($input['recommendations'] ?? []) as $rec) {
            if (!is_array($rec)) continue;
            $cid = (int) ($rec['category_id'] ?? 0);
            $title = trim((string) ($rec['section_title'] ?? ''));
            if ($cid <= 0 || $cid === $triggerId || $title === '') continue;
            $c = Category::find($cid);
            if (!$c || (int) $c['company_id'] !== $companyId) continue;
            $recommendations[] = ['category_id' => $cid, 'section_title' => $title];
        }

        if (empty($recommendations)) {
            $this->sendError('Inclua ao menos uma categoria recomendada válida (com título)', 400);
            return;
        }

        require_once __DIR__ . '/../models/CrossSellGroup.php';
        try {
            $id = CrossSellGroup::createOrUpdate($companyId, $triggerId, $recommendations);
        } catch (\Throwable $e) {
            $this->sendError('Erro ao salvar cross-sell: ' . $e->getMessage(), 500);
            return;
        }

        // Re-busca de forma confiável (evita depender do lastInsertId).
        $group = $id > 0
            ? CrossSellGroup::findById($id)
            : CrossSellGroup::findByTriggerCategory($companyId, $triggerId);

        $this->sendResponse(['message' => 'Cross-sell salvo', 'group' => $group]);
    }

    /** POST /api/{slug}/cross-sell/{id}/toggle */
    public function toggleCrossSell($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $g = $this->authorizeCrossSell($params['id'] ?? 0);
        if ($g === null) return;

        CrossSellGroup::toggleActive((int) $g['id']);
        $this->sendResponse([
            'message' => 'Status atualizado',
            'group'   => CrossSellGroup::findById((int) $g['id']),
        ]);
    }

    /** DELETE /api/{slug}/cross-sell/{id} */
    public function deleteCrossSell($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $g = $this->authorizeCrossSell($params['id'] ?? 0);
        if ($g === null) return;

        CrossSellGroup::delete((int) $g['id']);
        $this->sendResponse(['message' => 'Cross-sell excluído', 'id' => (int) $g['id']]);
    }

    /** Mapeia uma linha de produto para o array completo que Product::update espera. */
    private function productRowToData(array $p): array
    {
        return [
            'category_id'    => $p['category_id'],
            'name'           => $p['name'],
            'description'    => $p['description'],
            'price'          => $p['price'],
            'promo_price'    => $p['promo_price'],
            'promo_start_at' => $p['promo_start_at'] ?? null,
            'promo_end_at'   => $p['promo_end_at'] ?? null,
            'sku'            => $p['sku'],
            'image'          => $p['image'],
            'type'           => $p['type'],
            'price_mode'     => $p['price_mode'],
            'allow_customize' => $p['allow_customize'],
            'active'         => $p['active'],
            'sort_order'     => $p['sort_order'],
        ];
    }

    /** Valida que category_id (se informado) pertence à empresa. Retorna false e responde 400 se inválido. */
    private function validProductCategory($categoryId): bool
    {
        if ($categoryId === null || $categoryId === '' || (int) $categoryId === 0) {
            return true; // sem categoria é permitido
        }
        $cat = Category::find((int) $categoryId);
        if (!$cat || (int) $cat['company_id'] !== (int) $this->company['id']) {
            $this->sendError('Categoria inválida para esta loja', 400);
            return false;
        }
        return true;
    }

    /** POST /api/{slug}/products */
    public function createProduct($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $this->sendError('Nome é obrigatório', 400);
            return;
        }
        if (!isset($input['price']) || !is_numeric($input['price'])) {
            $this->sendError('Preço é obrigatório', 400);
            return;
        }
        $categoryId = $input['category_id'] ?? null;
        if (!$this->validProductCategory($categoryId)) return;

        $type = in_array($input['type'] ?? 'simple', ['simple', 'combo'], true) ? $input['type'] : 'simple';
        $priceMode = in_array($input['price_mode'] ?? 'fixed', ['fixed', 'sum'], true) ? $input['price_mode'] : 'fixed';

        $id = Product::create([
            'company_id'      => $companyId,
            'category_id'     => $categoryId,
            'name'            => $name,
            'description'     => $input['description'] ?? null,
            'price'           => (float) $input['price'],
            'promo_price'     => $input['promo_price'] ?? null,
            'sku'             => $input['sku'] ?? null,
            'type'            => $type,
            'price_mode'      => $priceMode,
            'allow_customize' => !empty($input['allow_customize']),
            'active'          => isset($input['active']) ? (int) (bool) $input['active'] : 1,
            'sort_order'      => (int) ($input['sort_order'] ?? 0),
        ]);

        $this->sendResponse(
            ['message' => 'Produto criado', 'product' => Product::findByCompanyAndId($companyId, $id)],
            201
        );
    }

    /** POST /api/{slug}/products/{id} (edição) */
    public function updateProduct($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $prod = Product::findByCompanyAndId($companyId, (int) ($params['id'] ?? 0));
        if (!$prod) {
            $this->sendError('Produto não encontrado', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (array_key_exists('category_id', $input) && !$this->validProductCategory($input['category_id'])) {
            return;
        }
        if (isset($input['price']) && !is_numeric($input['price'])) {
            $this->sendError('Preço inválido', 400);
            return;
        }

        $data = $this->productRowToData($prod);
        // Aplica somente os campos enviados (demais preservam o valor atual).
        if (array_key_exists('category_id', $input)) $data['category_id'] = $input['category_id'];
        if (isset($input['name']) && trim((string) $input['name']) !== '') $data['name'] = trim((string) $input['name']);
        if (array_key_exists('description', $input)) $data['description'] = $input['description'];
        if (isset($input['price'])) $data['price'] = (float) $input['price'];
        if (array_key_exists('promo_price', $input)) $data['promo_price'] = $input['promo_price'];
        if (array_key_exists('sku', $input)) $data['sku'] = $input['sku'];
        if (in_array($input['type'] ?? null, ['simple', 'combo'], true)) $data['type'] = $input['type'];
        if (in_array($input['price_mode'] ?? null, ['fixed', 'sum'], true)) $data['price_mode'] = $input['price_mode'];
        if (isset($input['allow_customize'])) $data['allow_customize'] = !empty($input['allow_customize']) ? 1 : 0;
        if (isset($input['active'])) $data['active'] = (int) (bool) $input['active'];
        if (isset($input['sort_order'])) $data['sort_order'] = (int) $input['sort_order'];

        Product::update((int) $prod['id'], $data);

        $this->sendResponse([
            'message' => 'Produto atualizado',
            'product' => Product::findByCompanyAndId($companyId, (int) $prod['id']),
        ]);
    }

    /** POST /api/{slug}/products/{id}/toggle */
    public function toggleProduct($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $prod = Product::findByCompanyAndId($companyId, (int) ($params['id'] ?? 0));
        if (!$prod) {
            $this->sendError('Produto não encontrado', 404);
            return;
        }

        $data = $this->productRowToData($prod);
        $data['active'] = (int) $prod['active'] === 1 ? 0 : 1;
        Product::update((int) $prod['id'], $data);

        $this->sendResponse([
            'message' => 'Produto atualizado',
            'product' => Product::findByCompanyAndId($companyId, (int) $prod['id']),
        ]);
    }

    /** DELETE /api/{slug}/products/{id} */
    public function deleteProduct($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];

        $prod = Product::findByCompanyAndId($companyId, (int) ($params['id'] ?? 0));
        if (!$prod) {
            $this->sendError('Produto não encontrado', 404);
            return;
        }

        // Soft delete: a FK order_items->products é CASCADE, então um hard delete
        // apagaria o histórico de pedidos. Marcamos deleted_at — some do catálogo,
        // mas os pedidos antigos permanecem íntegros.
        Product::softDelete($companyId, (int) $prod['id']);

        $this->sendResponse(['message' => 'Produto excluído', 'id' => (int) $prod['id']]);
    }

    /**
     * POST /api/{slug}/products/{id}/image  (multipart/form-data, campo "image")
     * Faz upload da imagem do produto (JPG/PNG/WEBP), atualiza products.image
     * e remove a imagem anterior.
     */
    public function uploadProductImage($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        $prod = Product::findByCompanyAndId($companyId, (int) ($params['id'] ?? 0));
        if (!$prod) {
            $this->sendError('Produto não encontrado', 404);
            return;
        }

        require_once __DIR__ . '/../modules/products/ProductImageService.php';
        $result = ProductImageService::upload($_FILES['image'] ?? null);

        if (($result['error'] ?? null) !== null) {
            $this->sendError($result['error'], 422);
            return;
        }
        if (($result['path'] ?? null) === null) {
            $this->sendError('Nenhuma imagem enviada (use o campo "image")', 400);
            return;
        }

        $oldImage = $prod['image'] ?? null;

        $data = $this->productRowToData($prod);
        $data['image'] = $result['path'];
        Product::update((int) $prod['id'], $data);

        // Remove a imagem antiga (somente dentro de public/uploads/).
        if ($oldImage && str_starts_with((string) $oldImage, 'uploads/')) {
            $oldFile = __DIR__ . '/../../public/' . $oldImage;
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        $this->sendResponse(['message' => 'Imagem atualizada', 'image' => $result['path']]);
    }

    /**
     * GET /api/{slug}/products/{id}/customization
     * Retorna os grupos de personalização do produto (para edição).
     */
    public function getProductCustomization($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        $prod = Product::findByCompanyAndId($companyId, (int) ($params['id'] ?? 0));
        if (!$prod) {
            $this->sendError('Produto não encontrado', 404);
            return;
        }

        require_once __DIR__ . '/../models/ProductCustomization.php';
        $groups = ProductCustomization::loadForAdmin((int) $prod['id']);

        $this->sendResponse([
            'product_id' => (int) $prod['id'],
            'enabled'    => count($groups) > 0,
            'groups'     => $groups,
        ]);
    }

    /**
     * POST /api/{slug}/products/{id}/customization
     * Substitui os grupos de personalização do produto.
     * Body: { "enabled": true, "groups": [ { "name", "mode":"extra|choice|pool",
     *         "choice":{"min","max"}, "pool":{"min","max"}, "sort_order",
     *         "items":[ { "ingredient_id", "min_qty", "max_qty", "default", "default_qty" } ] } ] }
     * Itens são validados por ingredient_id (precisa pertencer à loja); o label
     * vem do ingrediente. Sincroniza o flag allow_customize do produto.
     */
    public function saveProductCustomization($params): void
    {
        $this->loadCompany($params['slug'] ?? '');
        $companyId = (int) $this->company['id'];
        $prod = Product::findByCompanyAndId($companyId, (int) ($params['id'] ?? 0));
        if (!$prod) {
            $this->sendError('Produto não encontrado', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->sendError('Dados JSON inválidos', 400);
            return;
        }

        require_once __DIR__ . '/../models/ProductCustomization.php';
        try {
            $sanitized = ProductCustomization::sanitizePayload($input, $companyId);
            ProductCustomization::save((int) $prod['id'], $sanitized);

            // Mantém allow_customize coerente com a existência de personalização.
            $data = $this->productRowToData($prod);
            $data['allow_customize'] = $sanitized['enabled'] ? 1 : 0;
            Product::update((int) $prod['id'], $data);
        } catch (\Throwable $e) {
            $this->sendError('Erro ao salvar personalização: ' . $e->getMessage(), 500);
            return;
        }

        $this->sendResponse([
            'message' => 'Personalização salva',
            'enabled' => $sanitized['enabled'],
            'groups'  => ProductCustomization::loadForAdmin((int) $prod['id']),
        ]);
    }

    /**
     * Lista todos os produtos da empresa ou de uma categoria específica
     */
    public function getProducts($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        $categoryId = $_GET['category_id'] ?? null;
        $search = $_GET['search'] ?? null;
        $active = $_GET['active'] ?? '1';

        if ($categoryId) {
            $products = Product::listByCategory($this->company['id'], (int)$categoryId, $search);
        } else {
            $products = Product::listByCompany($this->company['id'], $search, $active === '1');
        }

        $this->sendResponse([
            'products' => $products,
            'total' => count($products),
            'filters' => [
                'category_id' => $categoryId,
                'search' => $search,
                'active' => $active === '1'
            ]
        ]);
    }

    /**
     * Lista produtos simples para busca autocomplete
     */
    public function getSimpleProducts($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        $search = $_GET['search'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 20), 50); // Max 50 por request

        // Busca produtos simples ativos
        $db = db();
        $query = "
            SELECT p.id, p.name, p.sku, p.price, p.allow_customize,
                   COUNT(pi.ingredient_id) as ingredient_count
            FROM products p
            LEFT JOIN product_ingredients pi ON p.id = pi.product_id
            JOIN categories c ON p.category_id = c.id
            WHERE c.company_id = ? 
            AND p.type = 'simple' 
            AND p.active = 1
        ";
        
        $params = [$this->company['id']];
        
        // Filtro de busca por nome, SKU ou ID
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query .= " AND (
                p.name LIKE ? OR 
                p.sku LIKE ? OR 
                CAST(p.id AS CHAR) LIKE ?
            )";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " 
            GROUP BY p.id, p.name, p.sku, p.price, p.allow_customize
            ORDER BY p.name ASC 
            LIMIT ?
        ";
        $params[] = $limit;

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatar preços para exibição
        foreach ($products as &$product) {
            $product['price_formatted'] = 'R$ ' . number_format((float)$product['price'], 2, ',', '.');
            $product['ingredient_count'] = (int)$product['ingredient_count'];
            $product['allow_customize'] = (bool)$product['allow_customize'];
        }

        $this->sendResponse([
            'products' => $products,
            'total' => count($products),
            'search' => $search
        ]);
    }

    /**
     * Retorna detalhes de um produto específico
     */
    public function getProduct($params): void
    {
        $slug = $params['slug'] ?? null;
        $productId = $params['id'] ?? null;

        if (!$slug || !$productId) {
            $this->sendError('Slug da empresa e ID do produto são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);

        // Product::find já escopa por company_id; ignora produtos soft-deletados.
        $product = Product::find((int)$productId, true, (int)$this->company['id']);

        if (!$product || !empty($product['deleted_at'])) {
            $this->sendError('Produto não encontrado', 404);
            return;
        }

        // Categoria é opcional (produto pode não ter categoria).
        $category = !empty($product['category_id'])
            ? Category::find((int) $product['category_id'])
            : null;

        $this->sendResponse([
            'product' => $product,
            'category' => $category,
        ]);
    }

    /**
     * Lista pedidos da empresa
     */
    public function getOrders($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        // Parâmetros de filtro
        $status = $_GET['status'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        // Origem (manual/website/ifood) e busca por #, cliente ou telefone.
        // O Order::listByCompany já suporta ambos; o app mobile passa via query.
        $source = isset($_GET['source']) && trim((string) $_GET['source']) !== ''
            ? trim((string) $_GET['source']) : null;
        $search = isset($_GET['q']) && trim((string) $_GET['q']) !== ''
            ? trim((string) $_GET['q']) : null;
        $limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100 por request
        $offset = max((int)($_GET['offset'] ?? 0), 0);

        $companyId = (int) $this->company['id'];
        $orders = Order::listByCompany(
            db(),
            $companyId,
            $status,
            $limit,
            $offset,
            $search,
            $source
        );

        $total = Order::countByCompany(db(), $companyId, $status, $search, $source);

        $this->sendResponse([
            'orders' => $orders,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'filters' => [
                'status' => $status,
                'source' => $source,
                'q' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ]);
    }

    /**
     * Retorna detalhes de um pedido específico
     */
    public function getOrder($params): void
    {
        $slug = $params['slug'] ?? null;
        $orderId = $params['id'] ?? null;

        if (!$slug || !$orderId) {
            $this->sendError('Slug da empresa e ID do pedido são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);

        $order = Order::findBasic(db(), (int)$orderId, $this->company['id']);
        
        if (!$order) {
            $this->sendError('Pedido não encontrado', 404);
            return;
        }

        // Carrega pedido com itens
        $orderWithItems = Order::findWithItems(db(), (int)$orderId, $this->company['id']);

        $this->sendResponse([
            'order' => $orderWithItems['order'] ?? $order,
            'items' => $orderWithItems['items'] ?? []
        ]);
    }

    /**
     * Cria um novo pedido via API
     */
    public function createOrder($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        // Recebe dados JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->sendError('Dados JSON inválidos', 400);
            return;
        }

        // Validações básicas
        $required = ['customer_name', 'customer_phone', 'items'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Campo obrigatório: {$field}", 400);
                return;
            }
        }

        if (empty($input['items']) || !is_array($input['items'])) {
            $this->sendError('Items do pedido são obrigatórios', 400);
            return;
        }

        $rawAddress = trim((string)($input['delivery_address'] ?? $input['customer_address'] ?? $input['address'] ?? ''));
        if ($rawAddress === '') {
            $this->sendError('Endereço de entrega é obrigatório', 400);
            return;
        }

        try {
            // Calcula totais dos itens
            $subtotal = 0;
            foreach ($input['items'] as $item) {
                $qty = (int)($item['quantity'] ?? 1);
                $price = (float)($item['unit_price'] ?? 0);
                $subtotal += $qty * $price;
            }

            // Cria o pedido
            $orderData = [
                'company_id' => $this->company['id'],
                'customer_name' => $input['customer_name'],
                'customer_phone' => normalizePhone($input['customer_phone']),
                'subtotal' => $subtotal,
                'delivery_fee' => (float)($input['delivery_fee'] ?? 0),
                'discount' => (float)($input['discount'] ?? 0),
                'total' => $subtotal + (float)($input['delivery_fee'] ?? 0) - (float)($input['discount'] ?? 0),
                'status' => 'pending',
                'notes' => $input['notes'] ?? null,
                'customer_address' => $rawAddress
            ];

            $orderId = Order::create(db(), $orderData);

            // Adiciona os itens
            foreach ($input['items'] as $item) {
                Order::addItem(db(), $orderId, [
                    'product_id' => (int)$item['product_id'],
                    'quantity' => (int)($item['quantity'] ?? 1),
                    'unit_price' => (float)($item['unit_price'] ?? 0),
                    'line_total' => (int)($item['quantity'] ?? 1) * (float)($item['unit_price'] ?? 0),
                    'combo_data' => $item['combo_data'] ?? null,
                    'customization_data' => $item['customization_data'] ?? null,
                    'notes' => $item['notes'] ?? null
                ]);
            }

            $orderWithItems = Order::findWithItems(db(), $orderId, $this->company['id']);

            // Enviar Web Push Notification para os dispositivos cadastrados
            try {
                require_once __DIR__ . '/../services/WebPushService.php';
                $webPushService = new \App\Services\WebPushService();
                $webPushService->notifyNewOrder($this->company['id'], [
                    'id' => $orderId,
                    'customer_name' => $orderData['customer_name'],
                    'customer_phone' => $orderData['customer_phone'],
                    'total' => $orderData['total'],
                ]);
            } catch (\Throwable $e) {
                error_log("Erro ao enviar Web Push: " . $e->getMessage());
            }

            // Enviar Push nativo (FCM) para os devices do app mobile.
            try {
                require_once __DIR__ . '/../services/FcmPushService.php';
                (new \App\Services\FcmPushService())->notifyNewOrder($this->company['id'], [
                    'id' => $orderId,
                    'order_number' => $orderWithItems['order_number'] ?? $orderId,
                    'customer_name' => $orderData['customer_name'],
                    'total' => $orderData['total'],
                ]);
            } catch (\Throwable $e) {
                error_log("Erro ao enviar Push FCM: " . $e->getMessage());
            }

            $this->sendResponse([
                'message' => 'Pedido criado com sucesso',
                'order' => $orderWithItems
            ], 201);

        } catch (Exception $e) {
            $this->sendError('Erro ao criar pedido: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualiza status de um pedido
     */
    public function updateOrderStatus($params): void
    {
        $slug = $params['slug'] ?? null;
        $orderId = $params['id'] ?? null;

        if (!$slug || !$orderId) {
            $this->sendError('Slug da empresa e ID do pedido são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['status'])) {
            $this->sendError('Status é obrigatório', 400);
            return;
        }

        $order = Order::findBasic(db(), (int)$orderId, $this->company['id']);
        
        if (!$order) {
            $this->sendError('Pedido não encontrado', 404);
            return;
        }

        try {
            $success = Order::updateStatus(db(), (int)$orderId, $this->company['id'], $input['status']);

            if (!$success) {
                $this->sendError('Status inválido ou erro ao atualizar', 400);
                return;
            }

            $updatedOrder = Order::findBasic(db(), (int)$orderId, $this->company['id']);

            $this->sendResponse([
                'message' => 'Status do pedido atualizado com sucesso',
                'order' => $updatedOrder
            ]);

        } catch (Exception $e) {
            $this->sendError('Erro ao atualizar status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Gera um novo token JWT para o usuário autenticado
     */
    public function generateToken($params): void
    {
        if (!isset($this->authData['user_id'])) {
            $this->sendError('ID do usuário não encontrado nos dados de autenticação', 400);
            return;
        }

        $payload = [
            'sub' => $this->authData['user_id'],
            'scopes' => $this->authData['scopes'] ?? ['read', 'write'],
            'company_access' => $params['slug'] ?? null
        ];

        try {
            $token = $this->apiSecurity->generateJwt($payload);

            $this->sendResponse([
                'token' => $token,
                'expires_in' => 3600, // 1 hora
                'token_type' => 'Bearer'
            ]);

        } catch (Exception $e) {
            $this->sendError('Erro ao gerar token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/{slug}/auth/me
     * Retorna o usuário autenticado (pelo Bearer JWT), a empresa e as permissões.
     * Usado pelo app mobile para montar o menu e aplicar guardas de tela.
     */
    public function me($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $userId = $this->authData['user_id'] ?? null;
        if (!$userId) {
            $this->sendError('Token sem usuário associado', 401);
            return;
        }

        $this->loadCompany($slug);

        $user = User::findById((int) $userId);
        if (!$user || (int) ($user['active'] ?? 0) !== 1) {
            $this->sendError('Usuário inativo ou não encontrado', 403);
            return;
        }

        // Acesso à loja: usuário da própria empresa ou root (acesso global).
        $role = (string) ($user['role'] ?? '');
        $isAdmin = in_array($role, ['root', 'owner'], true);
        $isRoot = $role === 'root';

        if (!$isRoot && (int) ($user['company_id'] ?? 0) !== (int) $this->company['id']) {
            $this->sendError('Usuário sem acesso a esta loja', 403);
            return;
        }

        $this->sendResponse([
            'user' => [
                'id'    => (int) $user['id'],
                'name'  => $user['name'] ?? null,
                'email' => $user['email'] ?? null,
                'role'  => $role,
            ],
            'company' => [
                'id'    => (int) $this->company['id'],
                'slug'  => $this->company['slug'] ?? $slug,
                'name'  => $this->company['name'] ?? null,
                'theme' => Company::themeColors($this->company),
            ],
            'is_admin'    => $isAdmin,
            'permissions' => $this->permissionsForRole($role),
        ]);
    }

    /**
     * Permissões (módulos acessíveis) derivadas do papel do usuário.
     * root/owner = acesso completo; staff = subconjunto operacional.
     * Vocabulário alinhado aos módulos do painel mobile.
     */
    private function permissionsForRole(string $role): array
    {
        $all = [
            'orders', 'kds', 'products', 'ingredients', 'customization_templates',
            'cross_sell', 'customers', 'coupons', 'ifood', 'analytics',
            'financial', 'expenses', 'product_costs', 'packaging', 'settings',
        ];

        if (in_array($role, ['root', 'owner'], true)) {
            return $all;
        }

        // staff: operação do dia a dia, sem gestão/configuração.
        return ['orders', 'kds', 'products', 'customers'];
    }

    /**
     * GET /api/{slug}/dashboard
     * Tela inicial do lojista: números do dia, status da loja e pedidos recentes.
     * Espelha os indicadores do dashboard mobile web.
     */
    public function dashboard($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        $db = db();
        $companyId = (int) $this->company['id'];

        try {
            $stats = [
                'today_orders'    => Order::countTodayByCompany($db, $companyId),
                'today_revenue'   => Order::sumTodayByCompany($db, $companyId),
                'pending_orders'  => Order::countPendingByCompany($db, $companyId),
                'active_products' => Product::countActiveByCompany($companyId),
                'total_categories' => count(Category::listByCompany($companyId)),
            ];

            // Status da loja (pausa programada) — falha graciosa se indisponível.
            $store = ['is_paused' => false, 'pause_until' => null, 'pause_remaining_minutes' => null];
            try {
                require_once __DIR__ . '/../services/ScheduledPauseService.php';
                $pause = (new ScheduledPauseService($db))->getPauseStatus($companyId);
                $store = [
                    'is_paused'               => (bool) ($pause['is_paused'] ?? false),
                    'pause_until'             => $pause['pause_until'] ?? null,
                    'pause_remaining_minutes' => $pause['remaining_minutes'] ?? null,
                ];
            } catch (\Throwable $e) {
                error_log('Dashboard pause status indisponível: ' . $e->getMessage());
            }

            $recent = array_map(static function (array $o): array {
                return [
                    'id'            => (int) $o['id'],
                    'customer_name' => $o['customer_name'] ?? null,
                    'total'         => (float) ($o['total'] ?? 0),
                    'status'        => $o['status'] ?? null,
                    'created_at'    => $o['created_at'] ?? null,
                ];
            }, Order::listRecentByCompany($companyId, 10));

            $this->sendResponse([
                'stats'         => $stats,
                'store'         => $store,
                'recent_orders' => $recent,
                'generated_at'  => date('c'),
            ]);
        } catch (Exception $e) {
            $this->sendError('Erro ao gerar dashboard: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/{slug}/orders/{id}
     * Edita um pedido PENDENTE (campos omitidos mantêm o valor atual).
     * Recusa pedidos já processados ou vindos do iFood.
     */
    public function updateOrder($params): void
    {
        $slug = $params['slug'] ?? null;
        $orderId = $params['id'] ?? null;
        if (!$slug || !$orderId) {
            $this->sendError('Slug e ID do pedido são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);
        $db = db();
        $companyId = (int) $this->company['id'];

        $existing = Order::findBasic($db, (int) $orderId, $companyId);
        if (!$existing) {
            $this->sendError('Pedido não encontrado', 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->sendError('Dados JSON inválidos', 400);
            return;
        }

        $items = $input['items'] ?? null;
        if (empty($items) || !is_array($items)) {
            $this->sendError('Items do pedido são obrigatórios', 400);
            return;
        }

        $address = trim((string) ($input['delivery_address'] ?? $input['customer_address'] ?? $input['address'] ?? ''));

        // Campos omitidos preservam o valor atual (null => updatePendingOrder usa o existente).
        $orderData = [
            'customer_name'     => $input['customer_name'] ?? null,
            'customer_phone'    => isset($input['customer_phone']) ? normalizePhone($input['customer_phone']) : null,
            'customer_address'  => $address !== '' ? $address : null,
            'payment_method_id' => $input['payment_method_id'] ?? null,
            'notes'             => $input['notes'] ?? null,
            'delivery_fee'      => isset($input['delivery_fee']) ? (float) $input['delivery_fee'] : (float) ($existing['delivery_fee'] ?? 0),
            'discount'          => isset($input['discount']) ? (float) $input['discount'] : (float) ($existing['discount'] ?? 0),
        ];

        try {
            $ok = Order::updatePendingOrder($db, (int) $orderId, $companyId, $orderData, $items);
            if (!$ok) {
                $this->sendError('Não foi possível editar: apenas pedidos pendentes (não-iFood) com itens válidos podem ser alterados', 409);
                return;
            }

            $updated = Order::findWithItems($db, (int) $orderId, $companyId);
            $this->sendResponse([
                'message' => 'Pedido atualizado com sucesso',
                'order'   => $updated,
            ]);
        } catch (Exception $e) {
            $this->sendError('Erro ao editar pedido: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/{slug}/orders/{id}
     * Exclui um pedido e libera o número para reutilização.
     */
    public function deleteOrder($params): void
    {
        $slug = $params['slug'] ?? null;
        $orderId = $params['id'] ?? null;
        if (!$slug || !$orderId) {
            $this->sendError('Slug e ID do pedido são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);
        $db = db();
        $companyId = (int) $this->company['id'];

        $existing = Order::findBasic($db, (int) $orderId, $companyId);
        if (!$existing) {
            $this->sendError('Pedido não encontrado', 404);
            return;
        }

        try {
            if (!Order::delete($db, (int) $orderId, $companyId)) {
                $this->sendError('Erro ao excluir pedido', 500);
                return;
            }

            $this->sendResponse([
                'message' => 'Pedido excluído com sucesso',
                'id'      => (int) $orderId,
            ]);
        } catch (Exception $e) {
            $this->sendError('Erro ao excluir pedido: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/{slug}/orders/{id}/receipt
     * Comprovante estruturado do pedido (para impressão térmica/compartilhar no app).
     */
    public function getReceipt($params): void
    {
        $slug = $params['slug'] ?? null;
        $orderId = $params['id'] ?? null;
        if (!$slug || !$orderId) {
            $this->sendError('Slug e ID do pedido são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);
        $db = db();
        $companyId = (int) $this->company['id'];

        $order = Order::findWithItems($db, (int) $orderId, $companyId);
        if (!$order) {
            $this->sendError('Pedido não encontrado', 404);
            return;
        }

        // Nome do método de pagamento (se houver).
        $paymentName = null;
        if (!empty($order['payment_method_id'])) {
            $stmt = $db->prepare('SELECT name FROM payment_methods WHERE id = ? AND company_id = ?');
            $stmt->execute([(int) $order['payment_method_id'], $companyId]);
            $paymentName = $stmt->fetchColumn() ?: null;
        }

        $items = array_map(static function (array $it): array {
            return [
                'name'       => $it['product_name'] ?? $it['name'] ?? '',
                'quantity'   => (int) ($it['quantity'] ?? 0),
                'unit_price' => (float) ($it['unit_price'] ?? 0),
                'line_total' => (float) ($it['line_total'] ?? 0),
                'notes'      => $it['notes'] ?? null,
            ];
        }, $order['items'] ?? []);

        $this->sendResponse([
            'receipt' => [
                'store' => [
                    'name'     => $this->company['name'] ?? null,
                    'whatsapp' => $this->company['whatsapp'] ?? null,
                    'address'  => $this->company['address'] ?? null,
                ],
                'order' => [
                    'id'               => (int) $order['id'],
                    'order_number'     => (int) ($order['order_number'] ?? $order['id']),
                    'status'           => $order['status'] ?? null,
                    'created_at'       => $order['created_at'] ?? null,
                    'customer_name'    => $order['customer_name'] ?? null,
                    'customer_phone'   => $order['customer_phone'] ?? null,
                    'customer_address' => $order['customer_address'] ?? null,
                    'notes'            => $order['notes'] ?? null,
                    'payment_method'   => $paymentName,
                ],
                'items'  => $items,
                'totals' => [
                    'subtotal'         => (float) ($order['subtotal'] ?? 0),
                    'delivery_fee'     => (float) ($order['delivery_fee'] ?? 0),
                    'discount'         => (float) ($order['discount'] ?? 0),
                    'loyalty_discount' => (float) ($order['loyalty_discount'] ?? 0),
                    'total'            => (float) ($order['total'] ?? 0),
                ],
            ],
        ]);
    }

    /**
     * GET /api/{slug}/kds/orders
     * Painel de cozinha com sincronização incremental.
     * Query: since=<sync_token> (omitir = full refresh).
     * App: guarda sync_token, faz polling com ?since=, aplica orders (upsert) e removed_ids.
     */
    public function kdsOrders($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);
        $db = db();
        $companyId = (int) $this->company['id'];

        $since = isset($_GET['since']) ? trim((string) $_GET['since']) : '';

        if ($since !== '') {
            $delta = Order::snapshotDelta($db, $companyId, $since);
        } else {
            $delta = [
                'orders'       => Order::snapshot($db, $companyId),
                'removed_ids'  => [],
                'full_refresh' => true,
            ];
        }

        $serverTime = gmdate('c');
        $syncToken = Order::latestChangeToken($db, $companyId) ?: $serverTime;

        $this->sendResponse([
            'orders'       => $delta['orders'],
            'removed_ids'  => $delta['removed_ids'] ?? [],
            'full_refresh' => !empty($delta['full_refresh']),
            'server_time'  => $serverTime,
            'sync_token'   => $syncToken,
        ]);
    }

    /**
     * POST /api/{slug}/kds/orders/{id}/status
     * Avança o status do pedido pela tela da cozinha.
     * Body: { "status": "pending|paid|completed|canceled" }
     */
    public function kdsUpdateStatus($params): void
    {
        $slug = $params['slug'] ?? null;
        $orderId = $params['id'] ?? null;
        if (!$slug || !$orderId) {
            $this->sendError('Slug e ID do pedido são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);
        $db = db();
        $companyId = (int) $this->company['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        $status = strtolower(trim((string) ($input['status'] ?? '')));
        if ($status === '') {
            $this->sendError('Status é obrigatório', 400);
            return;
        }

        $existing = Order::findBasic($db, (int) $orderId, $companyId);
        if (!$existing) {
            $this->sendError('Pedido não encontrado', 404);
            return;
        }

        if (!Order::updateStatus($db, (int) $orderId, $companyId, $status)) {
            $this->sendError('Status inválido ou erro ao atualizar', 400);
            return;
        }

        $this->sendResponse([
            'message' => 'Status atualizado com sucesso',
            'order'   => Order::findForKds($db, (int) $orderId, $companyId),
        ]);
    }

    /**
     * POST /api/{slug}/push/devices
     * Registra (upsert) o device do usuário autenticado para push (FCM/APNs).
     * Body: { "fcm_token": "...", "platform": "android|ios", "app_version": "...", "device_name": "..." }
     */
    public function registerDevice($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        $input = json_decode(file_get_contents('php://input'), true);
        $fcmToken = trim((string) ($input['fcm_token'] ?? ''));
        $platform = strtolower(trim((string) ($input['platform'] ?? '')));

        if ($fcmToken === '') {
            $this->sendError('fcm_token é obrigatório', 400);
            return;
        }
        if (!in_array($platform, ['android', 'ios'], true)) {
            $this->sendError('platform deve ser "android" ou "ios"', 400);
            return;
        }

        require_once __DIR__ . '/../models/DeviceToken.php';
        $result = DeviceToken::register(
            (int) $this->company['id'],
            isset($this->authData['user_id']) ? (int) $this->authData['user_id'] : null,
            $fcmToken,
            $platform,
            [
                'app_version' => $input['app_version'] ?? null,
                'device_name' => $input['device_name'] ?? null,
                'device_id'   => $input['device_id'] ?? null,
            ]
        );

        $this->sendResponse(['message' => 'Device registrado', 'device_id' => $result['id']], 201);
    }

    /**
     * DELETE /api/{slug}/push/devices
     * Remove (desativa) o device — usar no logout.
     * Body: { "fcm_token": "..." }
     */
    public function unregisterDevice($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        $input = json_decode(file_get_contents('php://input'), true);
        $fcmToken = trim((string) ($input['fcm_token'] ?? ''));
        if ($fcmToken === '') {
            $this->sendError('fcm_token é obrigatório', 400);
            return;
        }

        require_once __DIR__ . '/../models/DeviceToken.php';
        DeviceToken::deactivate($fcmToken);

        // Sempre 200 (não revela se o token existia).
        $this->sendResponse(['message' => 'Device removido']);
    }

    /**
     * POST /api/{slug}/push/test
     * Dispara uma notificação de teste para os devices da loja.
     */
    public function pushTest($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        require_once __DIR__ . '/../services/FcmPushService.php';
        $result = (new \App\Services\FcmPushService())->sendTest((int) $this->company['id']);

        $this->sendResponse(['result' => $result]);
    }

    /**
     * Retorna estatísticas da empresa
     */
    public function getStats($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        try {
            // Contagem básica de pedidos
            $totalOrders = Order::countByCompany(db(), (int) $this->company['id']);
            
            // Contagem de produtos e categorias via SQL direto
            $db = db();
            
            $stmt = $db->prepare('SELECT COUNT(*) FROM products WHERE company_id = ? AND active = 1');
            $stmt->execute([$this->company['id']]);
            $totalProducts = (int)$stmt->fetchColumn();
            
            $stmt = $db->prepare('SELECT COUNT(*) FROM categories WHERE company_id = ? AND active = 1');
            $stmt->execute([$this->company['id']]);
            $totalCategories = (int)$stmt->fetchColumn();
            
            // Pedidos recentes
            $recentOrders = Order::listRecentByCompany($this->company['id'], 5);
            
            $stats = [
                'total_orders' => $totalOrders,
                'total_products' => $totalProducts,
                'total_categories' => $totalCategories,
                'recent_orders' => count($recentOrders),
                'company_info' => [
                    'name' => $this->company['name'],
                    'slug' => $this->company['slug'],
                    'active' => (bool)$this->company['active']
                ]
            ];

            $this->sendResponse([
                'stats' => $stats,
                'generated_at' => date('c')
            ]);

        } catch (Exception $e) {
            $this->sendError('Erro ao gerar estatísticas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/{slug}/track-interaction
     * Registra interação do cliente com produto (machine learning)
     */
    public function trackInteraction($params): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendError('Método não permitido. Use POST.', 405);
                return;
            }

            $this->loadCompany($params['slug'] ?? '');

            $data = json_decode(file_get_contents('php://input'), true);
            
            $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
            $eventType = $data['event_type'] ?? '';
            $customerId = isset($data['customer_id']) ? (int)$data['customer_id'] : null;
            $sessionId = $data['session_id'] ?? null;

            if ($productId <= 0) {
                $this->sendError('product_id é obrigatório', 400);
                return;
            }

            if (!in_array($eventType, ['view', 'add_to_cart', 'purchase'], true)) {
                $this->sendError('event_type inválido. Use: view, add_to_cart ou purchase', 400);
                return;
            }

            require_once __DIR__ . '/../services/RecommendationEngine.php';
            $engine = new RecommendationEngine($this->db());

            $success = $engine->trackInteraction(
                (int)$this->company['id'],
                $productId,
                $eventType,
                $customerId,
                $sessionId
            );

            if ($success) {
                $this->sendResponse([
                    'message' => 'Interação registrada com sucesso',
                    'product_id' => $productId,
                    'event_type' => $eventType
                ]);
            } else {
                $this->sendError('Erro ao registrar interação', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Erro ao registrar interação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Envia resposta JSON de sucesso
     */
    private function sendResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Envia resposta JSON de erro
     */
    private function sendError(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status
            ],
            'timestamp' => date('c')
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}