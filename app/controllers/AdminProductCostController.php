<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelos e serviços específicos
require_once __DIR__ . '/../models/ProductAdditionalCost.php';
require_once __DIR__ . '/../models/PackagingSupply.php';
require_once __DIR__ . '/../services/CostCalculatorService.php';

/**
 * Controller para gestão de Custos Adicionais de Produtos
 */
class AdminProductCostController extends Controller
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
     * Lista de produtos com custos
     */
    public function index(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        $activeSlug = $slug;
        
        $costService = new CostCalculatorService($companyId);
        $productsCosts = $costService->calculateAllProductsCosts();
        
        $success = $_GET['success'] ?? null;
        $error = $_GET['error'] ?? null;

        require __DIR__ . '/../views/admin/product-costs/index.php';
    }

    /**
     * Editar custos adicionais de um produto
     */
    public function edit(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $productId = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        $activeSlug = $slug;

        $pdo = db();

        // Verificar se produto pertence à empresa
        $st = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ? AND company_id = ?");
        $st->execute([$productId, $companyId]);
        $product = $st->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/product-costs?error=notfound'));
            exit;
        }

        // Custos adicionais atuais
        $additionalCosts = ProductAdditionalCost::getByProduct($productId);

        // Calcular custo atual
        $costService = new CostCalculatorService($companyId);
        $costBreakdown = $costService->calculateProductCost($productId);

        // Custo dos ingredientes
        $ingredientCost = $costService->getIngredientsCost($productId);
        
        // Lista detalhada de ingredientes
        $ingredients = $costService->getIngredientsDetail($productId);
        
        // Variações de custo por escolha single (ex: Mussarela vs Cheddar)
        $singleChoiceVariations = $costService->getSingleChoiceVariations($productId);
        
        // Embalagens disponíveis e vinculadas ao produto
        $availablePackaging = PackagingSupply::listByCompany($companyId, true);
        $productPackaging = PackagingSupply::getByProduct($productId);
        $packagingCostFromLinks = PackagingSupply::getProductPackagingCost($productId);

        $success = $_GET['success'] ?? null;
        $error = $_GET['error'] ?? null;

        require __DIR__ . '/../views/admin/product-costs/edit.php';
    }

    /**
     * Salvar custos adicionais
     */
    public function update(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $productId = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        // Processar embalagens vinculadas
        $packagingData = [];
        $supplyIds = $_POST['packaging_supply_id'] ?? [];
        $quantities = $_POST['packaging_quantity'] ?? [];
        
        foreach ($supplyIds as $index => $supplyId) {
            if (!empty($supplyId) && isset($quantities[$index]) && (float)$quantities[$index] > 0) {
                $packagingData[] = [
                    'supply_id' => (int)$supplyId,
                    'quantity' => (float)str_replace(',', '.', $quantities[$index]),
                ];
            }
        }
        
        // Salvar vínculos de embalagens
        PackagingSupply::syncProductPackaging($productId, $packagingData);
        
        // Calcular custo total de embalagens automaticamente
        $packagingCost = PackagingSupply::getProductPackagingCost($productId);

        $data = [
            'packaging_cost' => $packagingCost,
            'labor_cost' => 0,
            'tax_percentage' => 0,
            'other_costs' => 0,
            'notes' => null,
        ];

        ProductAdditionalCost::save($productId, $companyId, $data);

        // Atualizar snapshot
        $costService = new CostCalculatorService($companyId);
        $costService->updateProductCostSnapshot($productId);

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/product-costs/' . $productId . '/edit?success=1'));
        exit;
    }

    /**
     * Atualizar embalagens via AJAX (autosave)
     */
    public function updatePackaging(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $productId = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $packaging = $input['packaging'] ?? [];

            // Processar dados de embalagens
            $packagingData = [];
            foreach ($packaging as $pkg) {
                if (!empty($pkg['supply_id']) && ($pkg['quantity'] ?? 0) > 0) {
                    $packagingData[] = [
                        'supply_id' => (int)$pkg['supply_id'],
                        'quantity' => (int)$pkg['quantity'],
                    ];
                }
            }

            // Salvar vínculos de embalagens
            PackagingSupply::syncProductPackaging($productId, $packagingData);

            // Calcular custo total de embalagens
            $packagingCost = PackagingSupply::getProductPackagingCost($productId);

            // Atualizar custos adicionais
            $data = [
                'packaging_cost' => $packagingCost,
                'labor_cost' => 0,
                'tax_percentage' => 0,
                'other_costs' => 0,
                'notes' => null,
            ];

            ProductAdditionalCost::save($productId, $companyId, $data);

            // Atualizar snapshot
            $costService = new CostCalculatorService($companyId);
            $costService->updateProductCostSnapshot($productId);

            echo json_encode([
                'success' => true,
                'packaging_cost' => $packagingCost,
                'message' => 'Embalagens atualizadas com sucesso'
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao salvar: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Aplicar custos em lote
     */
    public function bulkUpdate(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $pdo = db();

        $input = json_decode(file_get_contents('php://input'), true);
        
        $packagingCost = (float)($input['packaging_cost'] ?? 0);
        $laborCost = (float)($input['labor_cost'] ?? 0);
        $taxRate = (float)($input['tax_rate'] ?? 0);
        $productIds = $input['product_ids'] ?? [];

        if (empty($productIds)) {
            // Aplicar a todos os produtos
            $st = $pdo->prepare("SELECT id FROM products WHERE company_id = ? AND active = 1");
            $st->execute([$companyId]);
            $productIds = $st->fetchAll(PDO::FETCH_COLUMN);
        }

        $updated = 0;
        foreach ($productIds as $productId) {
            $data = [
                'packaging_cost' => $packagingCost,
                'labor_cost' => $laborCost,
                'tax_percentage' => $taxRate,
            ];

            ProductAdditionalCost::save((int)$productId, $companyId, $data);
            $updated++;
        }

        // Atualizar snapshots
        $costService = new CostCalculatorService($companyId);
        $costService->updateAllProductSnapshots();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "$updated produto(s) atualizado(s)",
            'count' => $updated
        ]);
        exit;
    }

    /**
     * Calcular e retornar custos de um produto (API)
     */
    public function calculate(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $productId = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $channel = $_GET['channel'] ?? 'counter';

        $costService = new CostCalculatorService($companyId);
        $costs = $costService->calculateProductCost($productId, true, $channel);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $costs]);
        exit;
    }

    /**
     * Sugerir preço para margem desejada (API)
     */
    public function suggestPrice(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $productId = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $margin = (float)($_GET['margin'] ?? 30);

        $costService = new CostCalculatorService($companyId);
        $costs = $costService->calculateProductCost($productId, true);
        
        $totalCost = $costs['total_cost'] ?? 0;
        
        // Preço sugerido para alcançar a margem desejada
        // margem = (preço - custo) / preço * 100
        // preço = custo / (1 - margem/100)
        $suggestedPrice = $totalCost / (1 - $margin / 100);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'current_cost' => $totalCost,
                'desired_margin' => $margin,
                'suggested_price' => round($suggestedPrice, 2),
            ]
        ]);
        exit;
    }
}
