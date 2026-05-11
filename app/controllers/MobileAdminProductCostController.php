<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/ProductAdditionalCost.php';
require_once __DIR__ . '/../models/PackagingSupply.php';
require_once __DIR__ . '/../services/CostCalculatorService.php';

/**
 * MobileAdminProductCostController
 * 
 * Custos de produtos otimizado para mobile.
 * Reutiliza CostCalculatorService, ProductAdditionalCost, PackagingSupply.
 */
class MobileAdminProductCostController extends Controller
{
    private function guard(): array
    {
        Auth::start();

        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

        if (!Auth::checkAdmin()) {
            header('Location: /login');
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company || empty($company['id'])) {
            http_response_code(404);
            echo 'Empresa inválida';
            exit;
        }

        $u = Auth::user();
        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        $this->ensureCompanyContext((int)$company['id'], $slug);

        return [$u, $company];
    }

    /**
     * GET /product-costs — Lista de produtos com custos
     */
    public function index(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];

        $costService = new CostCalculatorService($companyId);
        $productsCosts = $costService->calculateAllProductsCosts();

        $this->view('admin/mobile/product-costs/index', [
            'pageTitle' => 'Custos de Produtos',
            'activeNav' => 'settings',
            'productsCosts' => $productsCosts,
            'company' => $company,
            'user' => $u,
        ]);
    }

    /**
     * GET /product-costs/{id}/edit — Editar custos de um produto
     */
    public function edit(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];
        $productId = (int)($params['id'] ?? 0);

        $pdo = db();
        $st = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ? AND company_id = ?");
        $st->execute([$productId, $companyId]);
        $product = $st->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['flash_error'] = 'Produto não encontrado.';
            header('Location: /product-costs');
            exit;
        }

        $additionalCosts = ProductAdditionalCost::getByProduct($productId);
        $costService = new CostCalculatorService($companyId);
        $costBreakdown = $costService->calculateProductCost($productId);
        $ingredientCost = $costService->getIngredientsCost($productId);
        $ingredients = $costService->getIngredientsDetail($productId);
        $singleChoiceVariations = $costService->getSingleChoiceVariations($productId);
        $availablePackaging = PackagingSupply::listByCompany($companyId, true);
        $productPackaging = PackagingSupply::getByProduct($productId);
        $packagingCostFromLinks = PackagingSupply::getProductPackagingCost($productId);

        $this->view('admin/mobile/product-costs/edit', [
            'pageTitle' => 'Custos: ' . $product['name'],
            'activeNav' => 'settings',
            'product' => $product,
            'additionalCosts' => $additionalCosts,
            'costBreakdown' => $costBreakdown,
            'ingredientCost' => $ingredientCost,
            'ingredients' => $ingredients,
            'singleChoiceVariations' => $singleChoiceVariations,
            'availablePackaging' => $availablePackaging,
            'productPackaging' => $productPackaging,
            'packagingCostFromLinks' => $packagingCostFromLinks,
            'company' => $company,
            'user' => $u,
        ]);
    }

    /**
     * POST /product-costs/{id}/update — Salvar embalagens e custos
     */
    public function update(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];
        $productId = (int)($params['id'] ?? 0);

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

        PackagingSupply::syncProductPackaging($productId, $packagingData);
        $packagingCost = PackagingSupply::getProductPackagingCost($productId);

        $data = [
            'packaging_cost' => $packagingCost,
            'labor_cost' => 0,
            'tax_percentage' => 0,
            'other_costs' => 0,
            'notes' => null,
        ];

        ProductAdditionalCost::save($productId, $companyId, $data);

        $costService = new CostCalculatorService($companyId);
        $costService->updateProductCostSnapshot($productId);

        header('Location: /product-costs/' . $productId . '/edit?success=1');
        exit;
    }

    /**
     * POST /product-costs/{id}/update-packaging — AJAX autosave
     */
    public function updatePackaging(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];
        $productId = (int)($params['id'] ?? 0);

        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $packaging = $input['packaging'] ?? [];

            $packagingData = [];
            foreach ($packaging as $pkg) {
                if (!empty($pkg['supply_id']) && ($pkg['quantity'] ?? 0) > 0) {
                    $packagingData[] = [
                        'supply_id' => (int)$pkg['supply_id'],
                        'quantity' => (int)$pkg['quantity'],
                    ];
                }
            }

            PackagingSupply::syncProductPackaging($productId, $packagingData);
            $packagingCost = PackagingSupply::getProductPackagingCost($productId);

            $data = [
                'packaging_cost' => $packagingCost,
                'labor_cost' => 0,
                'tax_percentage' => 0,
                'other_costs' => 0,
                'notes' => null,
            ];

            ProductAdditionalCost::save($productId, $companyId, $data);

            $costService = new CostCalculatorService($companyId);
            $costService->updateProductCostSnapshot($productId);

            echo json_encode([
                'success' => true,
                'packaging_cost' => $packagingCost,
                'message' => 'Embalagens atualizadas'
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
     * POST /product-costs/bulk-update — Aplicar custos em lote (AJAX)
     */
    public function bulkUpdate(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];

        $pdo = db();
        $input = json_decode(file_get_contents('php://input'), true);

        $packagingCost = (float)($input['packaging_cost'] ?? 0);
        $laborCost = (float)($input['labor_cost'] ?? 0);
        $taxRate = (float)($input['tax_rate'] ?? 0);
        $productIds = $input['product_ids'] ?? [];

        if (empty($productIds)) {
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

        $costService = new CostCalculatorService($companyId);
        $costService->updateAllProductSnapshots();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $updated . ' produto(s) atualizado(s)',
            'count' => $updated
        ]);
        exit;
    }

    /**
     * GET /product-costs/{id}/calculate — API de cálculo
     */
    public function calculate(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];
        $productId = (int)($params['id'] ?? 0);
        $channel = $_GET['channel'] ?? 'counter';

        $costService = new CostCalculatorService($companyId);
        $costs = $costService->calculateProductCost($productId, true, $channel);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $costs]);
        exit;
    }
}
