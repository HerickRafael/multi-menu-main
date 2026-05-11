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
        $activeSlug = $slug;
        
        // Lista todos os insumos (ativos e inativos para admin)
        $supplies = PackagingSupply::listByCompany($companyId, false);
        
        // Adicionar contagem de produtos para cada insumo
        foreach ($supplies as &$supply) {
            $supply['product_count'] = PackagingSupply::countProductsUsing((int)$supply['id']);
        }
        unset($supply); // IMPORTANTE: remover a referência para evitar bugs no foreach da view
        
        $success = $_GET['success'] ?? null;
        $error = $_GET['error'] ?? null;

        require __DIR__ . '/../views/admin/packaging/index.php';
    }

    /**
     * Formulário de criação
     */
    public function create(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        $activeSlug = $slug;
        
        $supply = null;
        $isEdit = false;

        require __DIR__ . '/../views/admin/packaging/form.php';
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
        $activeSlug = $slug;
        
        $supply = PackagingSupply::findByCompany($id, $companyId);
        
        if (!$supply) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/packaging?error=notfound'));
            exit;
        }
        
        $isEdit = true;
        $products = PackagingSupply::getProductsUsing($id);

        require __DIR__ . '/../views/admin/packaging/form.php';
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
