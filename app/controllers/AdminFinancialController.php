<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelos e serviços específicos
require_once __DIR__ . '/../models/FinancialReport.php';
require_once __DIR__ . '/../models/FinancialSettings.php';
require_once __DIR__ . '/../services/CostCalculatorService.php';

/**
 * Controller para Dashboard e Relatórios Financeiros
 */
class AdminFinancialController extends Controller
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
     * Dashboard principal financeiro
     */
    public function dashboard(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        $currentMonth = date('Y-m');
        $previousMonth = date('Y-m', strtotime('-1 month'));

        $monthlySummary = FinancialReport::getMonthlySummary($companyId, $currentMonth);
        $comparison = FinancialReport::comparePeriods($companyId, $currentMonth, $previousMonth);
        $monthlyTrend = FinancialReport::getMonthlyTrend($companyId, 6);
        $topProducts = FinancialReport::getTopProducts($companyId, $currentMonth, 5);
        $profitableProducts = FinancialReport::getMostProfitableProducts($companyId, $currentMonth, 5);
        $lowMarginProducts = FinancialReport::getLowMarginProducts($companyId, 5);
        $hourlySales = FinancialReport::getHourlySales($companyId, $currentMonth);
        $dailySales = FinancialReport::getDailySales($companyId, $currentMonth);

        $toFloat = static function ($v): float {
            return $v === null || $v === '' ? 0.0 : (float)$v;
        };
        $toInt = static function ($v): int {
            return $v === null || $v === '' ? 0 : (int)$v;
        };

        $payload = [
            'current_month' => $currentMonth,
            'previous_month' => $previousMonth,
            'summary' => [
                'total_orders' => $toInt($monthlySummary['total_orders'] ?? 0),
                'completed_orders' => $toInt($monthlySummary['completed_orders'] ?? 0),
                'cancelled_orders' => $toInt($monthlySummary['cancelled_orders'] ?? 0),
                'gross_revenue' => $toFloat($monthlySummary['gross_revenue'] ?? 0),
                'products_revenue' => $toFloat($monthlySummary['products_revenue'] ?? 0),
                'delivery_fees' => $toFloat($monthlySummary['delivery_fees'] ?? 0),
                'discounts' => $toFloat($monthlySummary['discounts'] ?? 0),
                'cancelled_value' => $toFloat($monthlySummary['cancelled_value'] ?? 0),
                'total_items' => $toInt($monthlySummary['total_items'] ?? 0),
                'avg_ticket' => $toFloat($monthlySummary['avg_ticket'] ?? 0),
            ],
            'comparison' => [
                'revenue_growth' => $toFloat($comparison['revenue_growth'] ?? 0),
                'orders_growth' => $toFloat($comparison['orders_growth'] ?? 0),
                'ticket_growth' => $toFloat($comparison['ticket_growth'] ?? 0),
            ],
            'monthly_trend' => array_map(static function ($row) use ($toFloat, $toInt) {
                return [
                    'month' => (string)($row['month'] ?? ''),
                    'gross_revenue' => $toFloat($row['gross_revenue'] ?? 0),
                    'total_orders' => $toInt($row['total_orders'] ?? 0),
                ];
            }, $monthlyTrend),
            'top_products' => array_map(static function ($row) use ($toFloat, $toInt) {
                return [
                    'id' => $toInt($row['id'] ?? 0),
                    'name' => (string)($row['name'] ?? ''),
                    'total_sold' => $toInt($row['total_sold'] ?? 0),
                    'total_revenue' => $toFloat($row['total_revenue'] ?? 0),
                    'image' => (string)($row['image'] ?? ''),
                ];
            }, $topProducts),
            'profitable_products' => array_map(static function ($row) use ($toFloat, $toInt) {
                return [
                    'id' => $toInt($row['id'] ?? 0),
                    'name' => (string)($row['name'] ?? ''),
                    'total_sold' => $toInt($row['total_sold'] ?? 0),
                    'total_profit' => $toFloat($row['total_profit'] ?? 0),
                    'margin_pct' => $toFloat($row['margin_pct'] ?? 0),
                ];
            }, $profitableProducts),
            'low_margin_products' => array_map(static function ($row) use ($toFloat, $toInt) {
                return [
                    'id' => $toInt($row['id'] ?? 0),
                    'name' => (string)($row['name'] ?? ''),
                    'price' => $toFloat($row['price'] ?? 0),
                    'cost' => $toFloat($row['cost'] ?? 0),
                    'margin_pct' => $toFloat($row['margin_pct'] ?? 0),
                ];
            }, $lowMarginProducts),
            'hourly_sales' => array_map(static function ($row) use ($toFloat, $toInt) {
                return [
                    'hour' => $toInt($row['hour'] ?? 0),
                    'orders' => $toInt($row['orders'] ?? 0),
                    'revenue' => $toFloat($row['revenue'] ?? 0),
                ];
            }, $hourlySales),
            'daily_sales' => array_map(static function ($row) use ($toFloat, $toInt) {
                return [
                    'date' => (string)($row['date'] ?? ''),
                    'orders' => $toInt($row['orders'] ?? 0),
                    'revenue' => $toFloat($row['revenue'] ?? 0),
                ];
            }, $dailySales),
            'urls' => [
                'monthly' => '/admin/' . rawurlencode($slug) . '/financial/monthly',
                'yearly' => '/admin/' . rawurlencode($slug) . '/financial/yearly',
                'settings' => '/admin/' . rawurlencode($slug) . '/financial/settings',
                'expenses' => '/admin/' . rawurlencode($slug) . '/expenses',
                'product_costs' => '/admin/' . rawurlencode($slug) . '/product-costs',
                'chart_data' => '/admin/' . rawurlencode($slug) . '/financial/chart-data',
                'recalculate' => '/admin/' . rawurlencode($slug) . '/financial/recalculate',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_FINANCIAL__', $payload);
    }

    /**
     * Relatório mensal detalhado
     */
    public function monthly(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $monthlySummary = FinancialReport::getMonthlySummary($companyId, $month);
        $topProducts = FinancialReport::getTopProducts($companyId, $month, 20);
        $profitableProducts = FinancialReport::getMostProfitableProducts($companyId, $month, 20);
        $dailySales = FinancialReport::getDailySales($companyId, $month);
        $hourlySales = FinancialReport::getHourlySales($companyId, $month);

        $availableMonths = [];
        for ($i = 0; $i < 24; $i++) {
            $m = date('Y-m', strtotime("-$i months"));
            $availableMonths[] = ['value' => $m, 'label' => self::formatMonthName($m)];
        }

        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;
        $toInt = static fn($v) => $v === null || $v === '' ? 0 : (int)$v;

        $payload = [
            'month' => $month,
            'month_label' => self::formatMonthName($month),
            'available_months' => $availableMonths,
            'summary' => [
                'total_orders' => $toInt($monthlySummary['total_orders'] ?? 0),
                'completed_orders' => $toInt($monthlySummary['completed_orders'] ?? 0),
                'cancelled_orders' => $toInt($monthlySummary['cancelled_orders'] ?? 0),
                'gross_revenue' => $toFloat($monthlySummary['gross_revenue'] ?? 0),
                'products_revenue' => $toFloat($monthlySummary['products_revenue'] ?? 0),
                'delivery_fees' => $toFloat($monthlySummary['delivery_fees'] ?? 0),
                'discounts' => $toFloat($monthlySummary['discounts'] ?? 0),
                'cancelled_value' => $toFloat($monthlySummary['cancelled_value'] ?? 0),
                'total_items' => $toInt($monthlySummary['total_items'] ?? 0),
                'avg_ticket' => $toFloat($monthlySummary['avg_ticket'] ?? 0),
                'production_cost' => $toFloat($monthlySummary['production_cost'] ?? 0),
                'products_profit' => $toFloat($monthlySummary['products_profit'] ?? 0),
                'fixed_expenses' => $toFloat($monthlySummary['fixed_expenses'] ?? 0),
                'variable_expenses' => $toFloat($monthlySummary['variable_expenses'] ?? 0),
                'total_expenses' => $toFloat($monthlySummary['total_expenses'] ?? 0),
                'net_profit' => $toFloat($monthlySummary['net_profit'] ?? 0),
                'profit_margin' => $toFloat($monthlySummary['profit_margin'] ?? 0),
            ],
            'top_products' => array_map(static function ($p) use ($toFloat, $toInt) {
                return [
                    'id' => $toInt($p['id'] ?? 0),
                    'name' => (string)($p['name'] ?? ''),
                    'price' => $toFloat($p['price'] ?? 0),
                    'quantity_sold' => $toInt($p['quantity_sold'] ?? 0),
                    'total_revenue' => $toFloat($p['total_revenue'] ?? 0),
                    'total_profit' => $toFloat($p['total_profit'] ?? 0),
                    'avg_margin' => $toFloat($p['avg_margin'] ?? 0),
                ];
            }, $topProducts),
            'profitable_products' => array_map(static function ($p) use ($toFloat, $toInt) {
                return [
                    'id' => $toInt($p['id'] ?? 0),
                    'name' => (string)($p['name'] ?? ''),
                    'total_sold' => $toInt($p['total_sold'] ?? $p['quantity_sold'] ?? 0),
                    'total_profit' => $toFloat($p['total_profit'] ?? 0),
                    'margin_pct' => $toFloat($p['margin_pct'] ?? $p['avg_margin'] ?? 0),
                ];
            }, $profitableProducts),
            'daily_sales' => array_map(static function ($d) use ($toFloat, $toInt) {
                return [
                    'date' => (string)($d['date'] ?? ''),
                    'orders' => $toInt($d['orders'] ?? 0),
                    'revenue' => $toFloat($d['revenue'] ?? 0),
                ];
            }, $dailySales),
            'hourly_sales' => array_map(static function ($h) use ($toFloat, $toInt) {
                return [
                    'hour' => $toInt($h['hour'] ?? 0),
                    'orders' => $toInt($h['orders'] ?? 0),
                    'revenue' => $toFloat($h['revenue'] ?? 0),
                ];
            }, $hourlySales),
            'urls' => [
                'dashboard' => '/admin/' . rawurlencode($slug) . '/financial',
                'yearly' => '/admin/' . rawurlencode($slug) . '/financial/yearly',
                'settings' => '/admin/' . rawurlencode($slug) . '/financial/settings',
                'self' => '/admin/' . rawurlencode($slug) . '/financial/monthly',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_FINANCIAL_MONTHLY__', $payload);
    }

    /**
     * Relatório anual
     */
    public function yearly(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $year = (int)($_GET['year'] ?? date('Y'));
        $yearlySummary = FinancialReport::getYearlySummary($companyId, $year);
        $monthlyTrend = FinancialReport::getMonthlyTrend($companyId, 12);

        $currentYear = (int)date('Y');
        $availableYears = range($currentYear, $currentYear - 5);

        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;
        $toInt = static fn($v) => $v === null || $v === '' ? 0 : (int)$v;

        $payload = [
            'year' => $year,
            'available_years' => array_values($availableYears),
            'summary' => [
                'total_orders' => $toInt($yearlySummary['total_orders'] ?? 0),
                'completed_orders' => $toInt($yearlySummary['completed_orders'] ?? 0),
                'gross_revenue' => $toFloat($yearlySummary['gross_revenue'] ?? 0),
                'products_revenue' => $toFloat($yearlySummary['products_revenue'] ?? 0),
                'delivery_fees' => $toFloat($yearlySummary['delivery_fees'] ?? 0),
                'discounts' => $toFloat($yearlySummary['discounts'] ?? 0),
                'total_expenses' => $toFloat($yearlySummary['total_expenses'] ?? 0),
                'net_profit' => $toFloat($yearlySummary['net_profit'] ?? 0),
                'profit_margin' => $toFloat($yearlySummary['profit_margin'] ?? 0),
                'avg_ticket' => $toFloat($yearlySummary['avg_ticket'] ?? 0),
            ],
            'monthly_trend' => array_map(static function ($row) use ($toFloat, $toInt) {
                return [
                    'month' => (string)($row['month'] ?? ''),
                    'gross_revenue' => $toFloat($row['gross_revenue'] ?? 0),
                    'total_orders' => $toInt($row['total_orders'] ?? 0),
                    'net_profit' => $toFloat($row['net_profit'] ?? 0),
                ];
            }, $monthlyTrend),
            'urls' => [
                'dashboard' => '/admin/' . rawurlencode($slug) . '/financial',
                'monthly' => '/admin/' . rawurlencode($slug) . '/financial/monthly',
                'settings' => '/admin/' . rawurlencode($slug) . '/financial/settings',
                'self' => '/admin/' . rawurlencode($slug) . '/financial/yearly',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_FINANCIAL_YEARLY__', $payload);
    }

    /**
     * Configurações financeiras
     */
    public function settings(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $settings = FinancialSettings::get($companyId);
        $success = isset($_GET['success']);

        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;

        $payload = [
            'settings' => [
                'default_tax_percentage' => $toFloat($settings['default_tax_percentage'] ?? 0),
                'ifood_fee_percentage' => $toFloat($settings['ifood_fee_percentage'] ?? 0),
                'rappi_fee_percentage' => $toFloat($settings['rappi_fee_percentage'] ?? 0),
                'ubereats_fee_percentage' => $toFloat($settings['ubereats_fee_percentage'] ?? 0),
                'own_delivery_fee_percentage' => $toFloat($settings['own_delivery_fee_percentage'] ?? 0),
                'hourly_labor_cost' => $toFloat($settings['hourly_labor_cost'] ?? 0),
                'target_profit_margin' => $toFloat($settings['target_profit_margin'] ?? 30),
                'monthly_revenue_goal' => $toFloat($settings['monthly_revenue_goal'] ?? 0),
                'monthly_profit_goal' => $toFloat($settings['monthly_profit_goal'] ?? 0),
            ],
            'flash' => ['success' => $success ? 'Configurações salvas com sucesso.' : null],
            'urls' => [
                'submit' => '/admin/' . rawurlencode($slug) . '/financial/settings',
                'dashboard' => '/admin/' . rawurlencode($slug) . '/financial',
                'recalculate' => '/admin/' . rawurlencode($slug) . '/financial/recalculate',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_FINANCIAL_SETTINGS__', $payload);
    }

    /**
     * Salvar configurações financeiras
     */
    public function saveSettings(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $data = [
            'default_tax_percentage' => (float)($_POST['default_tax_percentage'] ?? 0),
            'ifood_fee_percentage' => (float)($_POST['ifood_fee_percentage'] ?? 0),
            'rappi_fee_percentage' => (float)($_POST['rappi_fee_percentage'] ?? 0),
            'ubereats_fee_percentage' => (float)($_POST['ubereats_fee_percentage'] ?? 0),
            'own_delivery_fee_percentage' => (float)($_POST['own_delivery_fee_percentage'] ?? 0),
            'hourly_labor_cost' => (float)($_POST['hourly_labor_cost'] ?? 0),
            'target_profit_margin' => (float)($_POST['target_profit_margin'] ?? 30),
            'monthly_revenue_goal' => (float)($_POST['monthly_revenue_goal'] ?? 0),
            'monthly_profit_goal' => (float)($_POST['monthly_profit_goal'] ?? 0),
        ];

        FinancialSettings::save($companyId, $data);

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/financial/settings?success=1'));
        exit;
    }

    /**
     * Recalcular custos dos produtos
     */
    public function recalculateCosts(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        
        $costService = new CostCalculatorService($companyId);
        $updated = $costService->updateAllProductSnapshots();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "$updated produto(s) atualizado(s)",
            'count' => $updated
        ]);
        exit;
    }

    /**
     * API: Dados para gráficos
     */
    public function chartData(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $type = $_GET['type'] ?? 'monthly_trend';
        $month = $_GET['month'] ?? date('Y-m');

        $data = match($type) {
            'monthly_trend' => FinancialReport::getMonthlyTrend($companyId, 12),
            'daily_sales' => FinancialReport::getDailySales($companyId, $month),
            'hourly_sales' => FinancialReport::getHourlySales($companyId, $month),
            'top_products' => FinancialReport::getTopProducts($companyId, $month, 10),
            default => []
        };

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    /**
     * Formata nome do mês em português
     */
    private static function formatMonthName(string $month): string
    {
        $months = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
            '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
            '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro',
        ];
        
        $parts = explode('-', $month);
        $m = $parts[1] ?? '01';
        $y = $parts[0] ?? date('Y');
        
        return ($months[$m] ?? $m) . ' de ' . $y;
    }
}
