<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelo específico
require_once __DIR__ . '/../models/PackagingSupply.php';

/**
 * Controller para gestão de Insumos/Embalagens
 */
class AdminPackagingController extends Controller
{
    /**
     * Garante autenticação e contexto de empresa
     */
    private function guard(string $slug): array
    {
        Auth::start();

        if (!Auth::checkAdmin()) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company || empty($company['id'])) {
            http_response_code(404);
            echo 'Empresa inválida';
            exit;
        }

        $u = Auth::user();
        $isRoot = ($u['role'] === 'root');

        if (!$isRoot && (int)$u['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        $this->ensureCompanyContext((int)$company['id'], $slug);

        return [$u, $company];
    }

    /**
     * Lista de insumos/embalagens
     */
    public function index(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $supplies = PackagingSupply::listByCompany($companyId, false);
        foreach ($supplies as &$supply) {
            $supply['product_count'] = PackagingSupply::countProductsUsing((int)$supply['id']);
        }
        unset($supply);

        $success = isset($_GET['success']) ? (string)$_GET['success'] : null;
        $error = isset($_GET['error']) ? (string)$_GET['error'] : null;

        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;

        $payload = [
            'supplies' => array_map(static function ($s) use ($toFloat) {
                $stock = $toFloat($s['stock_quantity'] ?? 0);
                $minStock = $toFloat($s['min_stock_alert'] ?? 0);
                return [
                    'id' => (int)($s['id'] ?? 0),
                    'name' => (string)($s['name'] ?? ''),
                    'description' => (string)($s['description'] ?? ''),
                    'unit' => (string)($s['unit'] ?? 'un'),
                    'cost_per_unit' => $toFloat($s['cost_per_unit'] ?? 0),
                    'stock_quantity' => $stock,
                    'min_stock_alert' => $minStock,
                    'supplier' => (string)($s['supplier'] ?? ''),
                    'active' => (int)($s['active'] ?? 0) === 1,
                    'product_count' => (int)($s['product_count'] ?? 0),
                    'low_stock' => $minStock > 0 && $stock <= $minStock,
                ];
            }, $supplies),
            'flash' => ['success' => $success, 'error' => $error],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/packaging',
                'create' => '/admin/' . rawurlencode($slug) . '/packaging/create',
                'store' => '/admin/' . rawurlencode($slug) . '/packaging/store',
                'edit_base' => '/admin/' . rawurlencode($slug) . '/packaging/',
                'destroy_base' => '/admin/' . rawurlencode($slug) . '/packaging/',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_PACKAGING__', $payload);
    }

    /**
     * Formulário de criação
     */
    public function create(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $this->renderPackagingFormSpa($slug, $company, null, []);
    }

    /**
     * Formulário de edição
     */
    public function edit(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $supply = PackagingSupply::findByCompany($id, $companyId);
        if (!$supply) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/packaging?error=notfound'));
            exit;
        }

        $products = PackagingSupply::getProductsUsing($id);
        $this->renderPackagingFormSpa($slug, $company, $supply, $products);
    }

    /**
     * @param array<string, mixed> $company
     * @param array<string, mixed>|null $supply
     * @param array<int, array<string, mixed>> $products
     */
    private function renderPackagingFormSpa(string $slug, array $company, ?array $supply, array $products): void
    {
        $isEdit = $supply !== null;
        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;

        $payload = [
            'is_edit' => $isEdit,
            'supply' => $isEdit ? [
                'id' => (int)($supply['id'] ?? 0),
                'name' => (string)($supply['name'] ?? ''),
                'description' => (string)($supply['description'] ?? ''),
                'unit' => (string)($supply['unit'] ?? 'un'),
                'cost_per_unit' => $toFloat($supply['cost_per_unit'] ?? 0),
                'stock_quantity' => $toFloat($supply['stock_quantity'] ?? 0),
                'min_stock_alert' => $toFloat($supply['min_stock_alert'] ?? 0),
                'supplier' => (string)($supply['supplier'] ?? ''),
                'active' => (int)($supply['active'] ?? 1) === 1,
            ] : [
                'id' => null,
                'name' => '',
                'description' => '',
                'unit' => 'un',
                'cost_per_unit' => 0,
                'stock_quantity' => 0,
                'min_stock_alert' => 0,
                'supplier' => '',
                'active' => true,
            ],
            'products' => array_map(static function ($p) {
                return [
                    'id' => (int)($p['id'] ?? $p['product_id'] ?? 0),
                    'name' => (string)($p['name'] ?? $p['product_name'] ?? ''),
                ];
            }, $products),
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/packaging',
                'submit' => '/admin/' . rawurlencode($slug) . '/packaging/store',
                'destroy' => $isEdit
                    ? '/admin/' . rawurlencode($slug) . '/packaging/' . (int)$supply['id'] . '/delete'
                    : null,
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_PACKAGING_FORM__', $payload);
    }

    /**
     * Salvar (criar ou atualizar)
     */
    public function store(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/packaging?error=name'));
            exit;
        }
        
        // Verificar duplicidade
        if (PackagingSupply::existsByName($companyId, $name, $id ?: null)) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/packaging?error=duplicate'));
            exit;
        }
        
        $data = [
            'company_id' => $companyId,
            'name' => $name,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'unit' => trim($_POST['unit'] ?? 'un'),
            'cost_per_unit' => (float)str_replace(',', '.', $_POST['cost_per_unit'] ?? '0'),
            'stock_quantity' => (float)str_replace(',', '.', $_POST['stock_quantity'] ?? '0'),
            'min_stock_alert' => (float)str_replace(',', '.', $_POST['min_stock_alert'] ?? '0'),
            'supplier' => trim($_POST['supplier'] ?? '') ?: null,
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        
        if ($id > 0) {
            // Verificar se pertence à empresa
            $existing = PackagingSupply::findByCompany($id, $companyId);
            if (!$existing) {
                header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/packaging?error=notfound'));
                exit;
            }
            
            // Verificar se o preço mudou para recalcular custos dos produtos
            $priceChanged = (float)$existing['cost_per_unit'] !== $data['cost_per_unit'];
            
            PackagingSupply::update($id, $data);
            
            // Se o preço mudou, recalcular custos de todos os produtos que usam esse insumo
            if ($priceChanged) {
                PackagingSupply::recalculateProductCostsForSupply($id, $companyId);
            }
            
            $message = 'updated';
        } else {
            $id = PackagingSupply::create($data);
            $message = 'created';
        }
        
        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/packaging?success=' . $message));
        exit;
    }

    /**
     * Excluir insumo
     */
    public function delete(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        
        $supply = PackagingSupply::findByCompany($id, $companyId);
        
        if (!$supply) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/packaging?error=notfound'));
            exit;
        }
        
        PackagingSupply::delete($id);
        
        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/packaging?success=deleted'));
        exit;
    }

    /**
     * API: Lista insumos para select
     */
    public function apiList(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        
        $supplies = PackagingSupply::listByCompany($companyId, true);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $supplies
        ]);
        exit;
    }

    /**
     * API: Salvar embalagens de um produto
     */
    public function apiSaveProductPackaging(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $productId = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        
        // Verificar se produto pertence à empresa
        $pdo = db();
        $st = $pdo->prepare('SELECT id FROM products WHERE id = ? AND company_id = ?');
        $st->execute([$productId, $companyId]);
        
        if (!$st->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Produto não encontrado']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $packaging = $input['packaging'] ?? [];
        
        PackagingSupply::syncProductPackaging($productId, $packaging);
        
        // Recalcular custo total
        $totalCost = PackagingSupply::getProductPackagingCost($productId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Embalagens salvas com sucesso',
            'total_cost' => $totalCost
        ]);
        exit;
    }
}
