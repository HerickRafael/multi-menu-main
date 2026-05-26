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

        $costService = new CostCalculatorService($companyId);
        $productsCosts = $costService->calculateAllProductsCosts();

        $success = isset($_GET['success']) ? (string)$_GET['success'] : null;
        $error = isset($_GET['error']) ? (string)$_GET['error'] : null;

        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;

        $payload = [
            'products' => array_map(static function ($p) use ($toFloat) {
                $price = $toFloat($p['price'] ?? $p['sale_price'] ?? 0);
                $totalCost = $toFloat($p['total_cost'] ?? 0);
                $profit = $price - $totalCost;
                $margin = $price > 0 ? ($profit / $price) * 100 : 0;
                return [
                    'id' => (int)($p['id'] ?? $p['product_id'] ?? 0),
                    'name' => (string)($p['name'] ?? $p['product_name'] ?? ''),
                    'price' => $price,
                    'ingredient_cost' => $toFloat($p['ingredient_cost'] ?? 0),
                    'packaging_cost' => $toFloat($p['packaging_cost'] ?? 0),
                    'labor_cost' => $toFloat($p['labor_cost'] ?? 0),
                    'waste_cost' => $toFloat($p['waste_cost'] ?? 0),
                    'tax_cost' => $toFloat($p['tax_cost'] ?? 0),
                    'platform_fee_cost' => $toFloat($p['platform_fee_cost'] ?? 0),
                    'other_costs' => $toFloat($p['other_costs'] ?? 0),
                    'total_cost' => $totalCost,
                    'profit' => $profit,
                    'profit_margin' => $margin,
                ];
            }, $productsCosts),
            'flash' => ['success' => $success, 'error' => $error],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/product-costs',
                'edit_base' => '/admin/' . rawurlencode($slug) . '/product-costs/',
                'bulk_update' => '/admin/' . rawurlencode($slug) . '/product-costs/bulk-update',
                'recalculate' => '/admin/' . rawurlencode($slug) . '/financial/recalculate',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_PRODUCT_COSTS__', $payload);
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

        $pdo = db();
        $st = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ? AND company_id = ?");
        $st->execute([$productId, $companyId]);
        $product = $st->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/product-costs?error=notfound'));
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

        $success = isset($_GET['success']);

        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;
        $toInt = static fn($v) => $v === null || $v === '' ? 0 : (int)$v;

        $price = $toFloat($product['price'] ?? 0);
        $totalCost = $toFloat($costBreakdown['total_cost'] ?? 0);
        $profit = $price - $totalCost;
        $margin = $price > 0 ? ($profit / $price) * 100 : 0;

        $payload = [
            'product' => [
                'id' => $toInt($product['id']),
                'name' => (string)($product['name'] ?? ''),
                'price' => $price,
            ],
            'breakdown' => [
                'ingredient_cost' => $toFloat($ingredientCost),
                'packaging_cost' => $toFloat($packagingCostFromLinks),
                'labor_cost' => $toFloat($costBreakdown['labor_cost'] ?? 0),
                'waste_cost' => $toFloat($costBreakdown['waste_cost'] ?? 0),
                'tax_cost' => $toFloat($costBreakdown['tax_cost'] ?? 0),
                'platform_fee_cost' => $toFloat($costBreakdown['platform_fee_cost'] ?? 0),
                'other_costs' => $toFloat($costBreakdown['other_costs'] ?? $additionalCosts['other_costs'] ?? 0),
                'total_cost' => $totalCost,
                'profit' => $profit,
                'profit_margin' => $margin,
            ],
            'additional_costs' => [
                'packaging_cost' => $toFloat($additionalCosts['packaging_cost'] ?? 0),
                'packaging_description' => (string)($additionalCosts['packaging_description'] ?? ''),
                'labor_cost' => $toFloat($additionalCosts['labor_cost'] ?? 0),
                'labor_minutes' => $toInt($additionalCosts['labor_minutes'] ?? 0),
                'waste_percentage' => $toFloat($additionalCosts['waste_percentage'] ?? 0),
                'tax_percentage' => $toFloat($additionalCosts['tax_percentage'] ?? 0),
                'platform_fee_percentage' => $toFloat($additionalCosts['platform_fee_percentage'] ?? 0),
                'other_costs' => $toFloat($additionalCosts['other_costs'] ?? 0),
                'other_costs_description' => (string)($additionalCosts['other_costs_description'] ?? ''),
                'notes' => (string)($additionalCosts['notes'] ?? ''),
            ],
            'ingredients' => array_map(static function ($i) use ($toFloat) {
                return [
                    'id' => isset($i['id']) ? (int)$i['id'] : (isset($i['ingredient_id']) ? (int)$i['ingredient_id'] : 0),
                    'name' => (string)($i['name'] ?? $i['ingredient_name'] ?? ''),
                    'quantity' => $toFloat($i['quantity'] ?? 1),
                    'unit_cost' => $toFloat($i['unit_cost'] ?? $i['cost'] ?? 0),
                    'total_cost' => $toFloat($i['total_cost'] ?? $i['cost'] ?? 0),
                    'unit' => (string)($i['unit'] ?? ''),
                ];
            }, $ingredients),
            'single_choice_variations' => array_map(static function ($v) use ($toFloat) {
                return [
                    'group_name' => (string)($v['group_name'] ?? ''),
                    'option_name' => (string)($v['option_name'] ?? $v['name'] ?? ''),
                    'cost_delta' => $toFloat($v['cost_delta'] ?? $v['cost'] ?? 0),
                    'total_cost' => $toFloat($v['total_cost'] ?? 0),
                ];
            }, $singleChoiceVariations),
            'available_packaging' => array_map(static function ($p) use ($toFloat) {
                return [
                    'id' => (int)($p['id'] ?? 0),
                    'name' => (string)($p['name'] ?? ''),
                    'unit' => (string)($p['unit'] ?? 'un'),
                    'cost_per_unit' => $toFloat($p['cost_per_unit'] ?? 0),
                    'supplier' => (string)($p['supplier'] ?? ''),
                ];
            }, $availablePackaging),
            'product_packaging' => array_map(static function ($p) use ($toFloat) {
                return [
                    'supply_id' => (int)($p['supply_id'] ?? $p['id'] ?? 0),
                    'name' => (string)($p['name'] ?? ''),
                    'quantity' => $toFloat($p['quantity'] ?? 1),
                    'unit' => (string)($p['unit'] ?? 'un'),
                    'cost_per_unit' => $toFloat($p['cost_per_unit'] ?? 0),
                ];
            }, $productPackaging),
            'packaging_cost_from_links' => $toFloat($packagingCostFromLinks),
            'flash' => ['success' => $success ? 'Custos atualizados.' : null],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/product-costs',
                'submit' => '/admin/' . rawurlencode($slug) . '/product-costs/' . $productId . '/update',
                'update_packaging' => '/admin/' . rawurlencode($slug) . '/product-costs/' . $productId . '/update-packaging',
                'product_edit' => '/admin/' . rawurlencode($slug) . '/products/' . $productId . '/edit',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_PRODUCT_COST_EDIT__', $payload);
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
