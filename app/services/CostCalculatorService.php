<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/FinancialSettings.php';
require_once __DIR__ . '/../models/ProductAdditionalCost.php';

/**
 * Serviço para cálculo de custos de produção
 * Calcula custos dos produtos baseado em ingredientes + custos adicionais + configurações
 */
class CostCalculatorService
{
    private int $companyId;
    private ?array $settings = null;
    
    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Obtém as configurações financeiras da empresa
     */
    private function getSettings(): array
    {
        if ($this->settings === null) {
            $this->settings = FinancialSettings::get($this->companyId);
        }
        return $this->settings;
    }

    /**
     * Calcula o custo de produção de um produto
     * 
     * @param int $productId
     * @param bool $includeChannelFees Se true, inclui taxas de delivery apps
     * @param string $channel Canal de venda (counter, ifood, rappi, etc)
     * @return array Detalhamento dos custos
     */
    public function calculateProductCost(int $productId, bool $includeChannelFees = false, string $channel = 'counter'): array
    {
        $pdo = db();
        $settings = $this->getSettings();

        // 1. Custo dos ingredientes
        $ingredientCost = $this->getIngredientsCost($productId);

        // 2. Custo de embalagens (SEMPRE calcular em tempo real para refletir preços atuais)
        require_once __DIR__ . '/../models/PackagingSupply.php';
        $packagingCost = PackagingSupply::getProductPackagingCost($productId);
        
        // 3. Outros custos adicionais do produto específico
        $additionalCosts = ProductAdditionalCost::getByProduct($productId);
        $laborCost = (float)($additionalCosts['labor_cost'] ?? $settings['labor_cost_per_item'] ?? 0);
        $taxRate = (float)($additionalCosts['tax_rate'] ?? $settings['default_tax_rate'] ?? 0);
        $otherCosts = (float)($additionalCosts['other_costs'] ?? 0);

        // 4. Preço de venda do produto
        $prodSt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $prodSt->execute([$productId]);
        $salePrice = (float)$prodSt->fetchColumn();

        // 4. Custo de produção (sem taxas de canal)
        $productionCost = $ingredientCost + $packagingCost + $laborCost + $otherCosts;

        // 5. Taxas de canal (se aplicável)
        $channelFee = 0;
        $channelFeePercent = 0;
        if ($includeChannelFees) {
            $channelFeePercent = match($channel) {
                'ifood' => (float)($settings['ifood_fee'] ?? 0),
                'rappi' => (float)($settings['rappi_fee'] ?? 0),
                'delivery_app' => (float)($settings['delivery_app_fee'] ?? 0),
                'counter' => (float)($settings['counter_fee'] ?? 0),
                default => 0
            };
            $channelFee = $salePrice * ($channelFeePercent / 100);
        }

        // 6. Impostos sobre a venda
        $taxAmount = $salePrice * ($taxRate / 100);

        // 7. Custo total
        $totalCost = $productionCost + $channelFee + $taxAmount;

        // 8. Lucro e margem
        $profit = $salePrice - $totalCost;
        $profitMargin = $salePrice > 0 ? ($profit / $salePrice) * 100 : 0;

        return [
            'product_id' => $productId,
            'sale_price' => round($salePrice, 2),
            
            // Custos de produção
            'ingredient_cost' => round($ingredientCost, 2),
            'packaging_cost' => round($packagingCost, 2),
            'labor_cost' => round($laborCost, 2),
            'other_costs' => round($otherCosts, 2),
            'production_cost' => round($productionCost, 2),
            
            // Taxas e impostos
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'channel' => $channel,
            'channel_fee_percent' => $channelFeePercent,
            'channel_fee' => round($channelFee, 2),
            
            // Totais
            'total_cost' => round($totalCost, 2),
            'profit' => round($profit, 2),
            'profit_margin' => round($profitMargin, 2),
        ];
    }

    /**
     * Calcula o custo dos ingredientes de um produto
     */
    public function getIngredientsCost(int $productId): float
    {
        $pdo = db();
        $totalCost = 0.0;
        
        // Primeiro buscar IDs de ingredientes que estão em grupos de personalização com is_default=1
        // Estes terão prioridade sobre os ingredientes nativos (para evitar duplicação)
        $stCustomIds = $pdo->prepare("
            SELECT DISTINCT pci.ingredient_id
            FROM product_custom_groups pcg
            JOIN product_custom_items pci ON pci.group_id = pcg.id
            WHERE pcg.product_id = ? 
              AND pci.is_default = 1 
              AND pci.ingredient_id IS NOT NULL
        ");
        $stCustomIds->execute([$productId]);
        $customIngredientIds = $stCustomIds->fetchAll(PDO::FETCH_COLUMN);
        
        // 1. Ingredientes nativos do produto (excluindo os que já estão nos grupos de personalização)
        $nativeSql = "
            SELECT COALESCE(SUM(i.cost * pni.quantity), 0) as total
            FROM product_native_ingredients pni
            JOIN ingredients i ON i.id = pni.ingredient_id
            WHERE pni.product_id = ?
        ";
        
        // Excluir ingredientes que já estão nos grupos de personalização
        if (!empty($customIngredientIds)) {
            $placeholders = implode(',', array_fill(0, count($customIngredientIds), '?'));
            $nativeSql .= " AND pni.ingredient_id NOT IN ($placeholders)";
        }
        
        $st = $pdo->prepare($nativeSql);
        $params = [$productId];
        if (!empty($customIngredientIds)) {
            $params = array_merge($params, $customIngredientIds);
        }
        $st->execute($params);
        $totalCost += (float)$st->fetchColumn();
        
        // 2. Ingredientes padrão dos grupos de personalização (product_custom_items com is_default=1)
        $st2 = $pdo->prepare("
            SELECT COALESCE(SUM(i.cost * pci.default_qty), 0) as total
            FROM product_custom_groups pcg
            JOIN product_custom_items pci ON pci.group_id = pcg.id
            JOIN ingredients i ON i.id = pci.ingredient_id
            WHERE pcg.product_id = ? 
              AND pci.is_default = 1 
              AND pci.ingredient_id IS NOT NULL
        ");
        $st2->execute([$productId]);
        $totalCost += (float)$st2->fetchColumn();
        
        return $totalCost;
    }

    /**
     * Retorna lista detalhada de ingredientes de um produto
     */
    public function getIngredientsDetail(int $productId): array
    {
        $pdo = db();
        $ingredients = [];
        
        // Primeiro buscar IDs de ingredientes que estão em grupos de personalização com is_default=1
        // Estes terão prioridade sobre os ingredientes nativos (para evitar duplicação)
        $stCustomIds = $pdo->prepare("
            SELECT DISTINCT pci.ingredient_id
            FROM product_custom_groups pcg
            JOIN product_custom_items pci ON pci.group_id = pcg.id
            WHERE pcg.product_id = ? 
              AND pci.is_default = 1 
              AND pci.ingredient_id IS NOT NULL
        ");
        $stCustomIds->execute([$productId]);
        $customIngredientIds = $stCustomIds->fetchAll(PDO::FETCH_COLUMN);
        
        // 1. Ingredientes nativos do produto (excluindo os que já estão nos grupos de personalização)
        $nativeSql = "
            SELECT 
                i.id,
                i.name,
                i.unit,
                i.cost as unit_cost,
                pni.quantity,
                (i.cost * pni.quantity) as total_cost,
                'native' as source
            FROM product_native_ingredients pni
            JOIN ingredients i ON i.id = pni.ingredient_id
            WHERE pni.product_id = ?
        ";
        
        // Excluir ingredientes que já estão nos grupos de personalização
        if (!empty($customIngredientIds)) {
            $placeholders = implode(',', array_fill(0, count($customIngredientIds), '?'));
            $nativeSql .= " AND pni.ingredient_id NOT IN ($placeholders)";
        }
        
        $nativeSql .= " ORDER BY pni.sort_order, i.name";
        
        $st = $pdo->prepare($nativeSql);
        $params = [$productId];
        if (!empty($customIngredientIds)) {
            $params = array_merge($params, $customIngredientIds);
        }
        $st->execute($params);
        $native = $st->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($native as $ing) {
            $ingredients[] = $ing;
        }
        
        // 2. Ingredientes padrão dos grupos de personalização
        $st2 = $pdo->prepare("
            SELECT 
                i.id,
                i.name,
                i.unit,
                i.cost as unit_cost,
                pci.default_qty as quantity,
                (i.cost * pci.default_qty) as total_cost,
                'custom' as source
            FROM product_custom_groups pcg
            JOIN product_custom_items pci ON pci.group_id = pcg.id
            JOIN ingredients i ON i.id = pci.ingredient_id
            WHERE pcg.product_id = ? 
              AND pci.is_default = 1 
              AND pci.ingredient_id IS NOT NULL
            ORDER BY pci.sort_order, i.name
        ");
        $st2->execute([$productId]);
        $custom = $st2->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($custom as $ing) {
            $ingredients[] = $ing;
        }
        
        return $ingredients;
    }

    /**
     * Retorna variações de custo baseadas em grupos 'single' (escolha única)
     * Mostra impacto no custo se cliente escolher opção diferente do default
     */
    public function getSingleChoiceVariations(int $productId): array
    {
        $pdo = db();
        
        // Busca todos os grupos 'single' do produto com seus itens
        $st = $pdo->prepare("
            SELECT 
                pcg.id as group_id,
                pcg.name as group_name,
                pci.id as item_id,
                pci.label as item_name,
                pci.is_default,
                pci.default_qty,
                pci.ingredient_id,
                i.name as ingredient_name,
                COALESCE(i.cost, 0) as ingredient_cost
            FROM product_custom_groups pcg
            JOIN product_custom_items pci ON pci.group_id = pcg.id
            LEFT JOIN ingredients i ON i.id = pci.ingredient_id
            WHERE pcg.product_id = ? AND pcg.type = 'single'
            ORDER BY pcg.sort_order, pci.sort_order
        ");
        $st->execute([$productId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $groups = [];
        foreach ($rows as $row) {
            $gid = (int)$row['group_id'];
            if (!isset($groups[$gid])) {
                $groups[$gid] = [
                    'id' => $gid,
                    'name' => $row['group_name'],
                    'default_cost' => 0,
                    'items' => []
                ];
            }
            
            $itemCost = (float)$row['ingredient_cost'] * (float)($row['default_qty'] ?: 1);
            
            if ($row['is_default']) {
                $groups[$gid]['default_cost'] = $itemCost;
            }
            
            $groups[$gid]['items'][] = [
                'id' => (int)$row['item_id'],
                'name' => $row['item_name'] ?: $row['ingredient_name'],
                'ingredient_id' => $row['ingredient_id'],
                'cost' => $itemCost,
                'is_default' => (bool)$row['is_default'],
            ];
        }
        
        // Calcular delta de cada item em relação ao default
        foreach ($groups as &$group) {
            foreach ($group['items'] as &$item) {
                $item['cost_delta'] = $item['cost'] - $group['default_cost'];
            }
        }
        
        return array_values($groups);
    }

    /**
     * Calcula custos de todos os produtos de uma empresa
     */
    public function calculateAllProductsCosts(): array
    {
        $pdo = db();
        
        $st = $pdo->prepare("SELECT id, name, price, updated_at FROM products WHERE company_id = ? AND active = 1");
        $st->execute([$this->companyId]);
        $products = $st->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($products as $product) {
            $cost = $this->calculateProductCost((int)$product['id']);
            $cost['name'] = $product['name'];
            $cost['updated_at'] = $product['updated_at'];
            $results[] = $cost;
        }

        // Ordenar: primeiro itens que precisam de atenção (sem custo de ingredientes), depois por data de alteração
        usort($results, function($a, $b) {
            // Prioridade 1: Itens sem custo de ingredientes (precisam atenção)
            $aAttention = ($a['ingredient_cost'] <= 0) ? 1 : 0;
            $bAttention = ($b['ingredient_cost'] <= 0) ? 1 : 0;
            
            if ($aAttention !== $bAttention) {
                return $bAttention <=> $aAttention; // Sem custo primeiro
            }
            
            // Prioridade 2: Data de alteração mais recente
            return ($b['updated_at'] ?? '') <=> ($a['updated_at'] ?? '');
        });

        return $results;
    }

    /**
     * Registra custo no momento da venda (order_item_costs)
     */
    public function recordOrderItemCost(int $orderId, int $orderItemId, int $productId, int $quantity, string $channel = 'counter'): bool
    {
        $pdo = db();
        
        // Calcular custos do produto
        $costs = $this->calculateProductCost($productId, true, $channel);
        
        // Registrar na tabela order_item_costs
        $st = $pdo->prepare("
            INSERT INTO order_item_costs (
                company_id, order_id, order_item_id, product_id, quantity,
                sale_price, base_ingredient_cost, packaging_cost, labor_cost, other_costs,
                tax_rate, tax_amount, channel_fee, total_cost, total_profit, profit_margin,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        return $st->execute([
            $this->companyId,
            $orderId,
            $orderItemId,
            $productId,
            $quantity,
            $costs['sale_price'],
            $costs['ingredient_cost'],
            $costs['packaging_cost'],
            $costs['labor_cost'],
            $costs['other_costs'],
            $costs['tax_rate'],
            $costs['tax_amount'],
            $costs['channel_fee'],
            $costs['total_cost'],
            $costs['profit'] * $quantity,
            $costs['profit_margin']
        ]);
    }

    /**
     * Atualiza snapshot de custo do produto (para histórico)
     */
    public function updateProductCostSnapshot(int $productId): bool
    {
        $pdo = db();
        
        $costs = $this->calculateProductCost($productId);

        // Verificar se já existe snapshot
        $checkSt = $pdo->prepare("SELECT id FROM product_cost_snapshots WHERE product_id = ?");
        $checkSt->execute([$productId]);
        $existing = $checkSt->fetchColumn();

        if ($existing) {
            // Update
            $st = $pdo->prepare("
                UPDATE product_cost_snapshots SET
                    sale_price = ?,
                    ingredient_cost = ?,
                    packaging_cost = ?,
                    labor_cost = ?,
                    other_costs = ?,
                    production_cost = ?,
                    tax_rate = ?,
                    tax_amount = ?,
                    total_cost = ?,
                    profit = ?,
                    profit_margin = ?,
                    updated_at = NOW()
                WHERE product_id = ?
            ");
            return $st->execute([
                $costs['sale_price'],
                $costs['ingredient_cost'],
                $costs['packaging_cost'],
                $costs['labor_cost'],
                $costs['other_costs'],
                $costs['production_cost'],
                $costs['tax_rate'],
                $costs['tax_amount'],
                $costs['total_cost'],
                $costs['profit'],
                $costs['profit_margin'],
                $productId
            ]);
        } else {
            // Insert
            $st = $pdo->prepare("
                INSERT INTO product_cost_snapshots (
                    company_id, product_id, sale_price, ingredient_cost, packaging_cost,
                    labor_cost, other_costs, production_cost, tax_rate, tax_amount,
                    total_cost, profit, profit_margin, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            return $st->execute([
                $this->companyId,
                $productId,
                $costs['sale_price'],
                $costs['ingredient_cost'],
                $costs['packaging_cost'],
                $costs['labor_cost'],
                $costs['other_costs'],
                $costs['production_cost'],
                $costs['tax_rate'],
                $costs['tax_amount'],
                $costs['total_cost'],
                $costs['profit'],
                $costs['profit_margin']
            ]);
        }
    }

    /**
     * Atualiza snapshots de todos os produtos
     */
    public function updateAllProductSnapshots(): int
    {
        $pdo = db();
        
        $st = $pdo->prepare("SELECT id FROM products WHERE company_id = ? AND active = 1");
        $st->execute([$this->companyId]);
        $productIds = $st->fetchAll(PDO::FETCH_COLUMN);

        $updated = 0;
        foreach ($productIds as $productId) {
            if ($this->updateProductCostSnapshot((int)$productId)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Calcula o custo de um item personalizado (com adicionais/remoções)
     * 
     * @param int $productId ID do produto base
     * @param array $customizations Array de customizações do pedido
     * @return array Custo detalhado
     */
    public function calculateCustomizedItemCost(int $productId, array $customizations = []): array
    {
        $pdo = db();
        $baseCost = $this->calculateProductCost($productId);
        
        $additionalIngredientCost = 0;
        $removedIngredientCost = 0;

        // Processar customizações (adicionais/remoções de ingredientes)
        foreach ($customizations as $custom) {
            if (isset($custom['ingredient_id'])) {
                $ingId = (int)$custom['ingredient_id'];
                $qty = (int)($custom['quantity'] ?? 1);
                
                // Buscar custo do ingrediente
                $ingSt = $pdo->prepare("SELECT cost FROM ingredients WHERE id = ?");
                $ingSt->execute([$ingId]);
                $ingCost = (float)$ingSt->fetchColumn();

                if (($custom['type'] ?? 'add') === 'add') {
                    $additionalIngredientCost += $ingCost * $qty;
                } else {
                    $removedIngredientCost += $ingCost * $qty;
                }
            }
        }

        // Ajustar custo
        $adjustedIngredientCost = $baseCost['ingredient_cost'] + $additionalIngredientCost - $removedIngredientCost;
        $adjustedProductionCost = $adjustedIngredientCost + $baseCost['packaging_cost'] + $baseCost['labor_cost'] + $baseCost['other_costs'];
        $adjustedTotalCost = $adjustedProductionCost + $baseCost['tax_amount'] + $baseCost['channel_fee'];
        
        // Novo lucro (considerando que adicionais podem ter preço adicional)
        $additionalRevenue = 0;
        foreach ($customizations as $custom) {
            if (isset($custom['price'])) {
                $additionalRevenue += (float)$custom['price'];
            }
        }
        
        $adjustedSalePrice = $baseCost['sale_price'] + $additionalRevenue;
        $adjustedProfit = $adjustedSalePrice - $adjustedTotalCost;
        $adjustedMargin = $adjustedSalePrice > 0 ? ($adjustedProfit / $adjustedSalePrice) * 100 : 0;

        return [
            'product_id' => $productId,
            'sale_price' => round($adjustedSalePrice, 2),
            'base_ingredient_cost' => round($baseCost['ingredient_cost'], 2),
            'additional_ingredient_cost' => round($additionalIngredientCost, 2),
            'removed_ingredient_cost' => round($removedIngredientCost, 2),
            'ingredient_cost' => round($adjustedIngredientCost, 2),
            'packaging_cost' => round($baseCost['packaging_cost'], 2),
            'labor_cost' => round($baseCost['labor_cost'], 2),
            'other_costs' => round($baseCost['other_costs'], 2),
            'production_cost' => round($adjustedProductionCost, 2),
            'tax_amount' => round($baseCost['tax_amount'], 2),
            'channel_fee' => round($baseCost['channel_fee'], 2),
            'total_cost' => round($adjustedTotalCost, 2),
            'profit' => round($adjustedProfit, 2),
            'profit_margin' => round($adjustedMargin, 2),
        ];
    }

    /**
     * Sugere preço de venda baseado na margem desejada
     */
    public function suggestPriceForMargin(int $productId, float $desiredMargin): array
    {
        $costs = $this->calculateProductCost($productId);
        $productionCost = $costs['production_cost'];
        $taxRate = $costs['tax_rate'];

        // Fórmula: Preço = CustoProdução / (1 - MargemDesejada - TaxaImposto)
        $divisor = 1 - ($desiredMargin / 100) - ($taxRate / 100);
        
        if ($divisor <= 0) {
            return [
                'success' => false,
                'message' => 'Margem muito alta para os custos atuais',
                'current_price' => $costs['sale_price'],
                'current_margin' => $costs['profit_margin'],
            ];
        }

        $suggestedPrice = $productionCost / $divisor;

        return [
            'success' => true,
            'current_price' => $costs['sale_price'],
            'current_margin' => $costs['profit_margin'],
            'suggested_price' => round($suggestedPrice, 2),
            'target_margin' => $desiredMargin,
            'production_cost' => $productionCost,
        ];
    }
}
