<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelos específicos
require_once __DIR__ . '/../models/DeliveryCity.php';
require_once __DIR__ . '/../models/DeliveryZone.php';

class AdminDeliveryFeeController extends Controller
{
    /** Protege a rota e retorna [user, company] */
    private function guard(string $slug): array
    {
        Auth::start();
        $user = Auth::user();

        if (!$user) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company) {
            echo 'Empresa inválida';
            exit;
        }

        if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
            echo 'Acesso negado';
            exit;
        }

        return [$user, $company];
    }

    /** Redireciona para index com flash opcional ?status= */
    private function redirectToIndex(array $company, array $query = []): void
    {
        $base = base_url('admin/' . rawurlencode($company['slug']) . '/delivery-fees');

        if ($query) {
            $base .= '?' . http_build_query($query);
        }
        header('Location: ' . $base);
        exit;
    }

    /** Lê status (?status=) e retorna mensagem de sucesso */
    private function resolveSuccessFromQuery(): ?string
    {
        $status = trim((string)($_GET['status'] ?? ''));

        if ($status === '') {
            return null;
        }

        $messages = [
          'city-created'   => 'Cidade cadastrada com sucesso.',
          'city-updated'   => 'Cidade atualizada com sucesso.',
          'city-removed'   => 'Cidade excluída.',
          'zone-created'   => 'Taxa cadastrada com sucesso.',
          'zone-updated'   => 'Taxa atualizada com sucesso.',
          'zone-removed'   => 'Taxa excluída.',
          'fees-adjusted'  => 'Todas as taxas foram atualizadas.',
          'options-saved'  => 'Preferências de entrega atualizadas.',
        ];

        return $messages[$status] ?? null;
    }

    /**
     * Renderiza a página principal (lista + formulários) com suporte a busca/edição por GET.
     */
    private function renderPage(array $company, array $options = [])
    {
        $companyId = (int)$company['id'];

        $cityErrors   = $options['cityErrors']   ?? [];
        $zoneErrors   = $options['zoneErrors']   ?? [];
        $optionErrors = $options['optionErrors'] ?? [];
        $bulkErrors   = $options['bulkErrors']   ?? [];

        $citySearch = trim((string)($options['citySearch'] ?? ($_GET['city_search'] ?? '')));
        $zoneSearch = trim((string)($options['zoneSearch'] ?? ($_GET['zone_search'] ?? '')));

        $editCityId = (int)($options['editCityId'] ?? ($_GET['edit_city'] ?? 0));
        $editZoneId = (int)($options['editZoneId'] ?? ($_GET['edit_zone'] ?? 0));

        // Listas (com busca opcional)
        $cities = DeliveryCity::allByCompany($companyId, $citySearch !== '' ? $citySearch : null);
        $zones  = DeliveryZone::allByCompany($companyId, $zoneSearch !== '' ? $zoneSearch : null);

        // Repopulação cidade (edição)
        $oldCity = $options['oldCity'] ?? [];

        if ($editCityId > 0 && !$oldCity) {
            $city = DeliveryCity::findForCompany($editCityId, $companyId);

            if ($city) {
                $oldCity = ['id' => $city['id'], 'name' => $city['name']];
            } else {
                $editCityId = 0;
            }
        }
        $oldCity = $oldCity + ['id' => '', 'name' => ''];

        // Repopulação zona (edição)
        $oldZone = $options['oldZone'] ?? [];

        if ($editZoneId > 0 && !$oldZone) {
            $zone = DeliveryZone::findForCompany($editZoneId, $companyId);

            if ($zone) {
                $oldZone = [
                  'id'           => $zone['id'],
                  'city_id'      => $zone['city_id'],
                  'neighborhood' => $zone['neighborhood'],
                  'fee'          => number_format((float)$zone['fee'], 2, '.', ''),
                ];
            } else {
                $editZoneId = 0;
            }
        }
        $oldZone = $oldZone + ['id' => '', 'city_id' => '', 'neighborhood' => '', 'fee' => ''];

        // Opções de entrega na Company
        $optionValues = $options['optionValues'] ?? [];

        if (!array_key_exists('after_hours_fee', $optionValues)) {
            $optionValues['after_hours_fee'] = number_format((float)($company['delivery_after_hours_fee'] ?? 0), 2, '.', '');
        }

        if (!array_key_exists('free_delivery', $optionValues)) {
            $optionValues['free_delivery'] = (int)($company['delivery_free_enabled'] ?? 0);
        }

        $bulkValue = $options['bulkValue'] ?? '';
        $success   = $options['success'] ?? $this->resolveSuccessFromQuery();
        $error     = $options['error'] ?? null;

        $slug = (string)$company['slug'];

        $payload = [
            'cities' => array_map(static function (array $c): array {
                return [
                    'id' => (int)$c['id'],
                    'name' => (string)($c['name'] ?? ''),
                ];
            }, $cities),
            'zones' => array_map(static function (array $z): array {
                return [
                    'id' => (int)$z['id'],
                    'city_id' => (int)$z['city_id'],
                    'city_name' => (string)($z['city_name'] ?? ''),
                    'neighborhood' => (string)($z['neighborhood'] ?? ''),
                    'fee' => (float)($z['fee'] ?? 0),
                ];
            }, $zones),
            'options' => [
                'after_hours_fee' => (float)($optionValues['after_hours_fee'] ?? 0),
                'free_delivery' => (int)($optionValues['free_delivery'] ?? 0) === 1,
            ],
            'errors' => [
                'city' => array_values(array_map('strval', $cityErrors)),
                'zone' => array_values(array_map('strval', $zoneErrors)),
                'option' => array_values(array_map('strval', $optionErrors)),
                'bulk' => array_values(array_map('strval', $bulkErrors)),
            ],
            'flash' => ['success' => $success, 'error' => $error],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/delivery-fees',
                'cities_store' => '/admin/' . rawurlencode($slug) . '/delivery-fees/cities',
                'cities_update_base' => '/admin/' . rawurlencode($slug) . '/delivery-fees/cities/',
                'cities_destroy_base' => '/admin/' . rawurlencode($slug) . '/delivery-fees/cities/',
                'zones_store' => '/admin/' . rawurlencode($slug) . '/delivery-fees/zones',
                'zones_update_base' => '/admin/' . rawurlencode($slug) . '/delivery-fees/zones/',
                'zones_destroy_base' => '/admin/' . rawurlencode($slug) . '/delivery-fees/zones/',
                'zones_adjust' => '/admin/' . rawurlencode($slug) . '/delivery-fees/zones/adjust',
                'options' => '/admin/' . rawurlencode($slug) . '/delivery-fees/options',
                'free_shipping' => '/admin/' . rawurlencode($slug) . '/delivery-fees/free-shipping',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_DELIVERY_FEES__', $payload);
    }

    /** GET /delivery-fees */
    public function index($params)
    {
        [, $company] = $this->guard($params['slug']);

        return $this->renderPage($company);
    }

    /** POST /delivery-fees/cities (criar cidade) */
    public function storeCity($params)
    {
        [, $company] = $this->guard($params['slug']);
        $companyId   = (int)$company['id'];

        $name   = trim((string)($_POST['name'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors[] = 'Informe o nome da cidade.';
        } elseif (DeliveryCity::existsByName($companyId, $name)) {
            $errors[] = 'Esta cidade já está cadastrada.';
        }

        if ($errors) {
            return $this->renderPage($company, [
              'cityErrors' => $errors,
              'oldCity'    => ['name' => $name],
            ]);
        }

        DeliveryCity::create([
          'company_id' => $companyId,
          'name'       => $name,
        ]);

        $this->redirectToIndex($company, ['status' => 'city-created']);
    }

    /** POST /delivery-fees/cities/{id} (atualizar cidade) */
    public function updateCity($params)
    {
        [, $company] = $this->guard($params['slug']);
        $companyId   = (int)$company['id'];
        $id          = (int)($params['id'] ?? 0);

        if ($id <= 0 || !DeliveryCity::findForCompany($id, $companyId)) {
            $this->redirectToIndex($company);
        }

        $name   = trim((string)($_POST['name'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors[] = 'Informe o nome da cidade.';
        } elseif (DeliveryCity::existsByNameExcept($companyId, $name, $id)) {
            $errors[] = 'Esta cidade já está cadastrada.';
        }

        if ($errors) {
            return $this->renderPage($company, [
              'cityErrors' => $errors,
              'oldCity'    => ['id' => $id, 'name' => $name],
              'editCityId' => $id,
            ]);
        }

        DeliveryCity::update($id, $companyId, $name);
        $this->redirectToIndex($company, ['status' => 'city-updated']);
    }

    /** POST /delivery-fees/cities/{id}/del (excluir cidade) */
    public function destroyCity($params)
    {
        [, $company] = $this->guard($params['slug']);
        $id = (int)($params['id'] ?? 0);

        if ($id > 0) {
            DeliveryCity::delete($id, (int)$company['id']);
        }

        $this->redirectToIndex($company, ['status' => 'city-removed']);
    }

    /** POST /delivery-fees/zones (criar bairro/taxa) */
    public function storeZone($params)
    {
        [, $company] = $this->guard($params['slug']);
        $companyId   = (int)$company['id'];

        $cityId       = (int)($_POST['city_id'] ?? 0);
        $neighborhood = trim((string)($_POST['neighborhood'] ?? ''));
        $feeRaw       = trim((string)($_POST['fee'] ?? ''));

        $errors        = [];
        $feeNormalized = str_replace(',', '.', $feeRaw);

        // cidade
        $city = $cityId > 0 ? DeliveryCity::findForCompany($cityId, $companyId) : null;

        if (!$city) {
            $errors[] = 'Selecione uma cidade válida.';
        }

        // bairro
        if ($neighborhood === '') {
            $errors[] = 'Informe o bairro.';
        }

        // taxa
        if ($feeRaw === '') {
            $errors[] = 'Informe o valor da taxa de entrega.';
        } elseif (!is_numeric($feeNormalized)) {
            $errors[] = 'Valor da taxa inválido.';
        } elseif ((float)$feeNormalized < 0) {
            $errors[] = 'A taxa não pode ser negativa.';
        }

        // duplicidade
        if (!$errors && DeliveryZone::existsForCity($companyId, $cityId, $neighborhood)) {
            $errors[] = 'Este bairro já está cadastrado para a cidade selecionada.';
        }

        if ($errors) {
            return $this->renderPage($company, [
              'zoneErrors' => $errors,
              'oldZone'    => [
                'city_id'      => $cityId ?: '',
                'neighborhood' => $neighborhood,
                'fee'          => $feeRaw,
              ],
            ]);
        }

        $feeValue = number_format((float)$feeNormalized, 2, '.', '');

        DeliveryZone::create([
          'company_id'   => $companyId,
          'city_id'      => $cityId,
          'neighborhood' => $neighborhood,
          'fee'          => $feeValue,
        ]);

        $this->redirectToIndex($company, ['status' => 'zone-created']);
    }

    /** POST /delivery-fees/zones/{id} (atualizar bairro/taxa) */
    public function updateZone($params)
    {
        [, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];
        $id        = (int)($params['id'] ?? 0);

        $zone = $id > 0 ? DeliveryZone::findForCompany($id, $companyId) : null;

        if (!$zone) {
            $this->redirectToIndex($company);
        }

        $cityId       = (int)($_POST['city_id'] ?? 0);
        $neighborhood = trim((string)($_POST['neighborhood'] ?? ''));
        $feeRaw       = trim((string)($_POST['fee'] ?? ''));

        $errors        = [];
        $feeNormalized = str_replace(',', '.', $feeRaw);

        $city = $cityId > 0 ? DeliveryCity::findForCompany($cityId, $companyId) : null;

        if (!$city) {
            $errors[] = 'Selecione uma cidade válida.';
        }

        if ($neighborhood === '') {
            $errors[] = 'Informe o bairro.';
        }

        if ($feeRaw === '') {
            $errors[] = 'Informe o valor da taxa de entrega.';
        } elseif (!is_numeric($feeNormalized)) {
            $errors[] = 'Valor da taxa inválido.';
        } elseif ((float)$feeNormalized < 0) {
            $errors[] = 'A taxa não pode ser negativa.';
        }

        if (!$errors && DeliveryZone::existsForCityExcept($companyId, $cityId, $neighborhood, $id)) {
            $errors[] = 'Este bairro já está cadastrado para a cidade selecionada.';
        }

        if ($errors) {
            return $this->renderPage($company, [
              'zoneErrors' => $errors,
              'oldZone'    => [
                'id'           => $id,
                'city_id'      => $cityId ?: '',
                'neighborhood' => $neighborhood,
                'fee'          => $feeRaw,
              ],
              'editZoneId' => $id,
            ]);
        }

        $feeValue = number_format((float)$feeNormalized, 2, '.', '');

        DeliveryZone::update($id, $companyId, [
          'city_id'      => $cityId,
          'neighborhood' => $neighborhood,
          'fee'          => $feeValue,
        ]);

        $this->redirectToIndex($company, ['status' => 'zone-updated']);
    }

    /** POST /delivery-fees/zones/adjust (ajuste em massa) */
    public function adjustZones($params)
    {
        [, $company] = $this->guard($params['slug']);
        $companyId   = (int)$company['id'];

        $deltaRaw = trim((string)($_POST['delta'] ?? ''));
        $errors   = [];

        if ($deltaRaw === '') {
            $errors[] = 'Informe o valor para ajuste.';
        }

        $normalized = str_replace(',', '.', $deltaRaw);

        if ($deltaRaw !== '' && !is_numeric($normalized)) {
            $errors[] = 'Valor inválido para ajuste das taxas.';
        }

        if ($errors) {
            return $this->renderPage($company, [
              'bulkErrors' => $errors,
              'bulkValue'  => $deltaRaw,
            ]);
        }

        $delta = (float)$normalized;
        DeliveryZone::adjustFees($companyId, $delta);

        $this->redirectToIndex($company, ['status' => 'fees-adjusted']);
    }

    /** POST /delivery-fees/zones/{id}/del (excluir bairro/taxa) */
    public function destroyZone($params)
    {
        [, $company] = $this->guard($params['slug']);
        $id = (int)($params['id'] ?? 0);

        if ($id > 0) {
            DeliveryZone::delete($id, (int)$company['id']);
        }

        $this->redirectToIndex($company, ['status' => 'zone-removed']);
    }

    /** POST /delivery-fees/options (salva opções de entrega na Company) */
    public function updateOptions($params)
    {
        [, $company] = $this->guard($params['slug']);
        $companyId   = (int)$company['id'];

        $afterRaw = trim((string)($_POST['after_hours_fee'] ?? '0'));
        $freeFlag = isset($_POST['free_delivery']) && (int)$_POST['free_delivery'] === 1;

        $errors     = [];
        $normalized = str_replace(',', '.', $afterRaw);

        if ($afterRaw === '') {
            $afterRaw   = '0';
            $normalized = '0';
        }

        if (!is_numeric($normalized)) {
            $errors[] = 'Informe um valor válido para a taxa após as 18h.';
        } elseif ((float)$normalized < 0) {
            $errors[] = 'A taxa adicional não pode ser negativa.';
        }

        if ($errors) {
            return $this->renderPage($company, [
              'optionErrors' => $errors,
              'optionValues' => [
                'after_hours_fee' => $afterRaw,
                'free_delivery'   => $freeFlag ? 1 : 0,
              ],
            ]);
        }

        Company::updateDeliveryOptions($companyId, (float)$normalized, $freeFlag);

        $this->redirectToIndex($company, ['status' => 'options-saved']);
    }

    /** POST /delivery-fees/free-shipping (salva valor mínimo para frete grátis) */
    public function updateFreeShipping($params)
    {
        [, $company] = $this->guard($params['slug']);
        $companyId   = (int)$company['id'];

        $freeShippingRaw = trim((string)($_POST['delivery_free_min_value'] ?? '0'));
        
        $errors     = [];
        $normalized = str_replace(',', '.', $freeShippingRaw);

        if ($freeShippingRaw === '') {
            $freeShippingRaw = '0';
            $normalized      = '0';
        }

        if (!is_numeric($normalized)) {
            $errors[] = 'Informe um valor válido para o frete grátis.';
        } elseif ((float)$normalized < 0) {
            $errors[] = 'O valor mínimo para frete grátis não pode ser negativo.';
        }

        if ($errors) {
            return $this->renderPage($company, [
              'optionErrors' => $errors,
            ]);
        }

        $db = $this->db();
        
        // Se ativar frete grátis promocional (valor > 0), desativar taxa gratuita para todos
        if ((float)$normalized > 0) {
            $stmt = $db->prepare('UPDATE companies SET delivery_free_min_value = ?, delivery_free_enabled = 0 WHERE id = ?');
            $stmt->execute([(float)$normalized, $companyId]);
        } else {
            // Apenas atualizar o campo delivery_free_min_value
            $stmt = $db->prepare('UPDATE companies SET delivery_free_min_value = ? WHERE id = ?');
            $stmt->execute([(float)$normalized, $companyId]);
        }

        $this->redirectToIndex($company, ['status' => 'free-shipping-saved']);
    }
}
