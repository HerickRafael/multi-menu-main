<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Model para Relatórios Financeiros
 * Queries agregadas para dashboard e relatórios
 */
class FinancialReport
{
    /**
     * Resumo financeiro de um mês
     */
    public static function getMonthlySummary(int $companyId, string $month): array
    {
        $pdo = db();
        
        // Vendas do mês
        $salesSt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status IN ('paid', 'completed') THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as cancelled_orders,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN total ELSE 0 END), 0) as gross_revenue,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN subtotal ELSE 0 END), 0) as products_revenue,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN delivery_fee ELSE 0 END), 0) as delivery_fees,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN discount + loyalty_discount ELSE 0 END), 0) as discounts,
                COALESCE(SUM(CASE WHEN status = 'canceled' THEN total ELSE 0 END), 0) as cancelled_value
            FROM orders
            WHERE company_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $salesSt->execute([$companyId, $month]);
        $sales = $salesSt->fetch(PDO::FETCH_ASSOC);

        // Itens vendidos
        $itemsSt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(oi.quantity), 0) as total_items
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.company_id = ? 
              AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
              AND o.status IN ('paid', 'completed')
        ");
        $itemsSt->execute([$companyId, $month]);
        $items = $itemsSt->fetch(PDO::FETCH_ASSOC);

        // Despesas do mês
        $expensesSt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN ec.type = 'fixed' THEN e.amount ELSE 0 END), 0) as fixed_expenses,
                COALESCE(SUM(CASE WHEN ec.type = 'variable' THEN e.amount ELSE 0 END), 0) as variable_expenses,
                COALESCE(SUM(e.amount), 0) as total_expenses
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            WHERE e.company_id = ? AND e.reference_month = ?
        ");
        $expensesSt->execute([$companyId, $month]);
        $expenses = $expensesSt->fetch(PDO::FETCH_ASSOC);

        // Custos de produção (se disponível)
        $costsSt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(oic.total_cost * oic.quantity), 0) as production_cost,
                COALESCE(SUM(oic.base_ingredient_cost * oic.quantity), 0) as ingredient_cost,
                COALESCE(SUM(oic.packaging_cost * oic.quantity), 0) as packaging_cost,
                COALESCE(SUM(oic.labor_cost * oic.quantity), 0) as labor_cost,
                COALESCE(SUM(oic.total_profit), 0) as products_profit
            FROM order_item_costs oic
            JOIN orders o ON o.id = oic.order_id
            WHERE oic.company_id = ? 
              AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
              AND o.status IN ('paid', 'completed')
        ");
        $costsSt->execute([$companyId, $month]);
        $costs = $costsSt->fetch(PDO::FETCH_ASSOC);

        // Calcular métricas
        $grossRevenue = (float)($sales['gross_revenue'] ?? 0);
        $completedOrders = (int)($sales['completed_orders'] ?? 0);
        $totalExpenses = (float)($expenses['total_expenses'] ?? 0);
        $productionCost = (float)($costs['production_cost'] ?? 0);

        $netRevenue = $grossRevenue - (float)($sales['discounts'] ?? 0);
        $grossProfit = $netRevenue - $productionCost;
        $netProfit = $grossProfit - $totalExpenses;
        $avgTicket = $completedOrders > 0 ? $grossRevenue / $completedOrders : 0;
        $profitMargin = $grossRevenue > 0 ? ($netProfit / $grossRevenue) * 100 : 0;

        return [
            // Vendas
            'total_orders' => (int)($sales['total_orders'] ?? 0),
            'completed_orders' => $completedOrders,
            'cancelled_orders' => (int)($sales['cancelled_orders'] ?? 0),
            'total_items_sold' => (int)($items['total_items'] ?? 0),
            
            // Receitas
            'gross_revenue' => $grossRevenue,
            'net_revenue' => $netRevenue,
            'products_revenue' => (float)($sales['products_revenue'] ?? 0),
            'delivery_fees' => (float)($sales['delivery_fees'] ?? 0),
            'discounts' => (float)($sales['discounts'] ?? 0),
            'cancelled_value' => (float)($sales['cancelled_value'] ?? 0),
            
            // Custos de Produção
            'production_cost' => $productionCost,
            'ingredient_cost' => (float)($costs['ingredient_cost'] ?? 0),
            'packaging_cost' => (float)($costs['packaging_cost'] ?? 0),
            'labor_cost' => (float)($costs['labor_cost'] ?? 0),
            
            // Despesas Operacionais
            'fixed_expenses' => (float)($expenses['fixed_expenses'] ?? 0),
            'variable_expenses' => (float)($expenses['variable_expenses'] ?? 0),
            'total_expenses' => $totalExpenses,
            
            // Lucros
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'products_profit' => (float)($costs['products_profit'] ?? 0),
            
            // Métricas
            'avg_ticket' => $avgTicket,
            'profit_margin' => $profitMargin,
        ];
    }

    /**
     * Resumo dos últimos N meses
     */
    public static function getMonthlyTrend(int $companyId, int $months = 12): array
    {
        $pdo = db();
        
        $st = $pdo->prepare("
            SELECT 
                DATE_FORMAT(o.created_at, '%Y-%m') as month,
                COUNT(*) as total_orders,
                SUM(CASE WHEN o.status IN ('paid', 'completed') THEN 1 ELSE 0 END) as completed_orders,
                COALESCE(SUM(CASE WHEN o.status IN ('paid', 'completed') THEN o.total ELSE 0 END), 0) as revenue
            FROM orders o
            WHERE o.company_id = ? 
              AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $st->execute([$companyId, $months]);
        $salesData = $st->fetchAll(PDO::FETCH_ASSOC);

        // Despesas por mês
        $expSt = $pdo->prepare("
            SELECT 
                reference_month as month,
                COALESCE(SUM(amount), 0) as expenses
            FROM expenses
            WHERE company_id = ? 
              AND reference_month >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ? MONTH), '%Y-%m')
            GROUP BY reference_month
            ORDER BY reference_month ASC
        ");
        $expSt->execute([$companyId, $months]);
        $expenseData = [];
        foreach ($expSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $expenseData[$row['month']] = (float)$row['expenses'];
        }

        // Combinar dados
        $result = [];
        foreach ($salesData as $row) {
            $month = $row['month'];
            $revenue = (float)$row['revenue'];
            $expenses = $expenseData[$month] ?? 0;
            
            $result[] = [
                'month' => $month,
                'month_label' => self::formatMonthLabel($month),
                'orders' => (int)$row['completed_orders'],
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $revenue - $expenses,
            ];
        }

        return $result;
    }

    /**
     * Produtos mais vendidos
     */
    public static function getTopProducts(int $companyId, string $month, int $limit = 10): array
    {
        $pdo = db();
        
        $st = $pdo->prepare("
            SELECT 
                p.id,
                p.name,
                p.price,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.line_total) as total_revenue,
                COALESCE(SUM(oic.total_profit), 0) as total_profit,
                COALESCE(AVG(oic.profit_margin), 0) as avg_margin
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            LEFT JOIN order_item_costs oic ON oic.order_item_id = oi.id
            WHERE o.company_id = ? 
              AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
              AND o.status NOT IN ('canceled', 'cancelled')
            GROUP BY p.id
            ORDER BY quantity_sold DESC
            LIMIT ?
        ");
        $st->execute([$companyId, $month, $limit]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Produtos mais lucrativos
     */
    public static function getMostProfitableProducts(int $companyId, string $month, int $limit = 10): array
    {
        $pdo = db();
        
        $st = $pdo->prepare("
            SELECT 
                p.id,
                p.name,
                p.price,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.line_total) as total_revenue,
                COALESCE(SUM(oic.total_profit), 0) as total_profit,
                COALESCE(AVG(oic.profit_margin), 0) as avg_margin
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            LEFT JOIN order_item_costs oic ON oic.order_item_id = oi.id
            WHERE o.company_id = ? 
              AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
              AND o.status IN ('paid', 'completed')
            GROUP BY p.id
            HAVING total_profit > 0
            ORDER BY total_profit DESC
            LIMIT ?
        ");
        $st->execute([$companyId, $month, $limit]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Produtos com menor margem (atenção)
     */
    public static function getLowMarginProducts(int $companyId, int $limit = 10): array
    {
        $pdo = db();
        
        $st = $pdo->prepare("
            SELECT 
                p.id,
                p.name,
                p.price,
                pcs.production_cost,
                pcs.total_cost,
                pcs.profit,
                pcs.profit_margin
            FROM product_cost_snapshots pcs
            JOIN products p ON p.id = pcs.product_id
            WHERE pcs.company_id = ? AND p.active = 1
            ORDER BY pcs.profit_margin ASC
            LIMIT ?
        ");
        $st->execute([$companyId, $limit]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Resumo anual
     */
    public static function getYearlySummary(int $companyId, int $year): array
    {
        $pdo = db();
        
        $st = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status IN ('paid', 'completed') THEN 1 ELSE 0 END) as completed_orders,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN total ELSE 0 END), 0) as gross_revenue,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN subtotal ELSE 0 END), 0) as products_revenue,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN delivery_fee ELSE 0 END), 0) as delivery_fees,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN discount + loyalty_discount ELSE 0 END), 0) as discounts
            FROM orders
            WHERE company_id = ? AND YEAR(created_at) = ?
        ");
        $st->execute([$companyId, $year]);
        $sales = $st->fetch(PDO::FETCH_ASSOC);

        // Despesas do ano
        $expSt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM expenses
            WHERE company_id = ? AND reference_month LIKE ?
        ");
        $expSt->execute([$companyId, $year . '-%']);
        $expenses = (float)$expSt->fetchColumn();

        $grossRevenue = (float)($sales['gross_revenue'] ?? 0);
        $completedOrders = (int)($sales['completed_orders'] ?? 0);
        $avgTicket = $completedOrders > 0 ? $grossRevenue / $completedOrders : 0;

        return [
            'year' => $year,
            'total_orders' => (int)($sales['total_orders'] ?? 0),
            'completed_orders' => $completedOrders,
            'gross_revenue' => $grossRevenue,
            'products_revenue' => (float)($sales['products_revenue'] ?? 0),
            'delivery_fees' => (float)($sales['delivery_fees'] ?? 0),
            'discounts' => (float)($sales['discounts'] ?? 0),
            'total_expenses' => $expenses,
            'net_profit' => $grossRevenue - $expenses,
            'avg_ticket' => $avgTicket,
        ];
    }

    /**
     * Comparação entre dois períodos
     */
    public static function comparePeriods(int $companyId, string $period1, string $period2): array
    {
        $summary1 = self::getMonthlySummary($companyId, $period1);
        $summary2 = self::getMonthlySummary($companyId, $period2);

        $changes = [];
        foreach ($summary1 as $key => $value1) {
            $value2 = $summary2[$key] ?? 0;
            $change = $value2 != 0 ? (($value1 - $value2) / abs($value2)) * 100 : ($value1 > 0 ? 100 : 0);
            
            $changes[$key] = [
                'current' => $value1,
                'previous' => $value2,
                'change' => $value1 - $value2,
                'change_percent' => round($change, 1),
            ];
        }

        return $changes;
    }

    /**
     * Vendas por dia do mês
     */
    public static function getDailySales(int $companyId, string $month): array
    {
        $pdo = db();
        
        $st = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN total ELSE 0 END), 0) as revenue
            FROM orders
            WHERE company_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $st->execute([$companyId, $month]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Vendas por hora (para análise de pico)
     */
    public static function getHourlySales(int $companyId, string $month): array
    {
        $pdo = db();
        
        $st = $pdo->prepare("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as orders,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN total ELSE 0 END), 0) as revenue
            FROM orders
            WHERE company_id = ? 
              AND DATE_FORMAT(created_at, '%Y-%m') = ?
              AND status IN ('paid', 'completed')
            GROUP BY HOUR(created_at)
            ORDER BY hour ASC
        ");
        $st->execute([$companyId, $month]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Formata label do mês
     */
    private static function formatMonthLabel(string $month): string
    {
        $months = [
            '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
            '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez',
        ];
        
        $parts = explode('-', $month);
        $m = $parts[1] ?? '01';
        $y = substr($parts[0] ?? date('Y'), 2, 2);
        
        return ($months[$m] ?? $m) . '/' . $y;
    }
}
