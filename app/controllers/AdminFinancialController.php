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
        $activeSlug = $slug;
        $currentMonth = date('Y-m');
        $previousMonth = date('Y-m', strtotime('-1 month'));

        // Resumo do mês atual
        $monthlySummary = FinancialReport::getMonthlySummary($companyId, $currentMonth);
        
        // Comparação com mês anterior
        $comparison = FinancialReport::comparePeriods($companyId, $currentMonth, $previousMonth);

        // Tendência dos últimos 6 meses
        $monthlyTrend = FinancialReport::getMonthlyTrend($companyId, 6);

        // Top 5 produtos mais vendidos
        $topProducts = FinancialReport::getTopProducts($companyId, $currentMonth, 5);

        // Top 5 produtos mais lucrativos
        $profitableProducts = FinancialReport::getMostProfitableProducts($companyId, $currentMonth, 5);

        // Produtos com menor margem
        $lowMarginProducts = FinancialReport::getLowMarginProducts($companyId, 5);

        // Vendas por hora
        $hourlySales = FinancialReport::getHourlySales($companyId, $currentMonth);

        // Vendas diárias
        $dailySales = FinancialReport::getDailySales($companyId, $currentMonth);

        require __DIR__ . '/../views/admin/financial/dashboard.php';
    }

    /**
     * Relatório mensal detalhado
     */
    public function monthly(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        $activeSlug = $slug;

        $month = $_GET['month'] ?? date('Y-m');

        // Validar formato do mês
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $monthlySummary = FinancialReport::getMonthlySummary($companyId, $month);
        $topProducts = FinancialReport::getTopProducts($companyId, $month, 20);
        $profitableProducts = FinancialReport::getMostProfitableProducts($companyId, $month, 20);
        $dailySales = FinancialReport::getDailySales($companyId, $month);
        $hourlySales = FinancialReport::getHourlySales($companyId, $month);

        // Lista de meses disponíveis (últimos 24 meses)
        $availableMonths = [];
        for ($i = 0; $i < 24; $i++) {
            $m = date('Y-m', strtotime("-$i months"));
            $availableMonths[$m] = self::formatMonthName($m);
        }

        require __DIR__ . '/../views/admin/financial/monthly.php';
    }

    /**
     * Relatório anual
     */
    public function yearly(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        $activeSlug = $slug;

        $year = (int)($_GET['year'] ?? date('Y'));

        $yearlySummary = FinancialReport::getYearlySummary($companyId, $year);
        $monthlyTrend = FinancialReport::getMonthlyTrend($companyId, 12);

        // Anos disponíveis
        $currentYear = (int)date('Y');
        $availableYears = range($currentYear, $currentYear - 5);

        require __DIR__ . '/../views/admin/financial/yearly.php';
    }

    /**
     * Configurações financeiras
     */
    public function settings(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        $activeSlug = $slug;

        $settings = FinancialSettings::get($companyId);
        $success = $_GET['success'] ?? null;
        $error = $_GET['error'] ?? null;

        require __DIR__ . '/../views/admin/financial/settings.php';
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
