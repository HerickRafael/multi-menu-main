<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/FinancialReport.php';
require_once __DIR__ . '/../models/FinancialSettings.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/ExpenseCategory.php';
require_once __DIR__ . '/../services/CostCalculatorService.php';

/**
 * Controller Mobile para Financeiro e Despesas
 */
class MobileAdminFinancialController extends Controller
{
    private function guard(): array
    {
        Auth::start();
        $user = Auth::user();
        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

        if (!$user) {
            header('Location: /login');
            exit;
        }

        $company = Company::findBySlug($slug);
        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            exit;
        }

        if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        return [$user, $company, $slug];
    }

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
        return ($months[$m] ?? $m) . '/' . $y;
    }

    // ========================
    // FINANCEIRO
    // ========================

    /**
     * GET /financial - Dashboard financeiro
     */
    public function dashboard(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $currentMonth = date('Y-m');
        $previousMonth = date('Y-m', strtotime('-1 month'));

        $monthlySummary = FinancialReport::getMonthlySummary($companyId, $currentMonth);
        $comparison = FinancialReport::comparePeriods($companyId, $currentMonth, $previousMonth);
        $monthlyTrend = FinancialReport::getMonthlyTrend($companyId, 6);
        $topProducts = FinancialReport::getTopProducts($companyId, $currentMonth, 5);
        $profitableProducts = FinancialReport::getMostProfitableProducts($companyId, $currentMonth, 5);
        $currentMonthLabel = self::formatMonthName($currentMonth);

        $pageTitle = 'Financeiro';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/financial/dashboard.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * GET /financial/monthly - Relatório mensal
     */
    public function monthly(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

        $monthlySummary = FinancialReport::getMonthlySummary($companyId, $month);
        $topProducts = FinancialReport::getTopProducts($companyId, $month, 10);
        $dailySales = FinancialReport::getDailySales($companyId, $month);

        $availableMonths = [];
        for ($i = 0; $i < 12; $i++) {
            $m = date('Y-m', strtotime("-$i months"));
            $availableMonths[$m] = self::formatMonthName($m);
        }
        $monthLabel = self::formatMonthName($month);

        $pageTitle = 'Relatório Mensal';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/financial/monthly.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    // ========================
    // DESPESAS
    // ========================

    /**
     * GET /expenses - Lista de despesas
     */
    public function expenses(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

        $expenses = Expense::listByCompany($companyId, $month);
        $summary = Expense::getMonthlySummary($companyId, $month);
        $categories = ExpenseCategory::listByCompany($companyId);

        $availableMonths = [];
        for ($i = 0; $i < 12; $i++) {
            $m = date('Y-m', strtotime("-$i months"));
            $availableMonths[$m] = self::formatMonthName($m);
        }
        $monthLabel = self::formatMonthName($month);

        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $pageTitle = 'Despesas';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/financial/expenses.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * GET /expenses/create - Formulário nova despesa
     */
    public function expenseCreate(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $categories = ExpenseCategory::listByCompany($companyId);
        $expense = null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $pageTitle = 'Nova Despesa';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/financial/expense_form.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * POST /expenses - Salvar despesa
     */
    public function expenseStore(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $referenceMonth = $_POST['reference_month'] ?? date('Y-m');
        $expenseDate = !empty($_POST['payment_date']) ? $_POST['payment_date'] : $referenceMonth . '-01';

        $data = [
            'company_id' => $companyId,
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'description' => trim($_POST['description'] ?? ''),
            'amount' => (float)str_replace(',', '.', $_POST['amount'] ?? '0'),
            'expense_date' => $expenseDate,
            'payment_method' => $_POST['payment_method'] ?? null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ];

        if (empty($data['description'])) {
            $_SESSION['flash_error'] = 'Informe a descrição da despesa';
            header('Location: /expenses/create');
            exit;
        }
        if ($data['amount'] <= 0) {
            $_SESSION['flash_error'] = 'Informe um valor válido';
            header('Location: /expenses/create');
            exit;
        }

        Expense::create($data);
        $_SESSION['flash_success'] = 'Despesa adicionada!';
        header('Location: /expenses?month=' . $referenceMonth);
        exit;
    }

    /**
     * GET /expenses/{id}/edit - Editar despesa
     */
    public function expenseEdit(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);

        $expense = Expense::findForCompany($companyId, $id);
        if (!$expense) {
            header('Location: /expenses');
            exit;
        }

        $categories = ExpenseCategory::listByCompany($companyId);
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $pageTitle = 'Editar Despesa';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/financial/expense_form.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * POST /expenses/{id} - Atualizar despesa
     */
    public function expenseUpdate(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);

        $existing = Expense::findForCompany($companyId, $id);
        if (!$existing) {
            header('Location: /expenses');
            exit;
        }

        $referenceMonth = $_POST['reference_month'] ?? date('Y-m');
        $expenseDate = !empty($_POST['payment_date']) ? $_POST['payment_date'] : $referenceMonth . '-01';

        Expense::update($id, [
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'description' => trim($_POST['description'] ?? ''),
            'amount' => (float)str_replace(',', '.', $_POST['amount'] ?? '0'),
            'expense_date' => $expenseDate,
            'payment_method' => $_POST['payment_method'] ?? null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);

        $_SESSION['flash_success'] = 'Despesa atualizada!';
        header('Location: /expenses?month=' . $referenceMonth);
        exit;
    }

    /**
     * POST /expenses/{id}/delete - Excluir despesa
     */
    public function expenseDelete(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $month = $_GET['month'] ?? date('Y-m');

        $existing = Expense::findForCompany((int)$company['id'], $id);
        if ($existing) {
            Expense::delete($id);
            $_SESSION['flash_success'] = 'Despesa excluída!';
        }

        header('Location: /expenses?month=' . $month);
        exit;
    }

    /**
     * GET /expenses/categories - Categorias de despesas
     */
    public function expenseCategories(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $categories = ExpenseCategory::listByCompany($companyId);
        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $pageTitle = 'Categorias de Despesas';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/financial/expense_categories.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * POST /expenses/categories - Salvar categoria
     */
    public function expenseCategoryStore(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Informe o nome da categoria';
            header('Location: /expenses/categories');
            exit;
        }

        ExpenseCategory::create([
            'company_id' => $companyId,
            'name' => $name,
            'type' => in_array($_POST['type'] ?? '', ['fixed', 'variable']) ? $_POST['type'] : 'fixed',
            'description' => trim($_POST['description'] ?? '') ?: null,
        ]);

        $_SESSION['flash_success'] = 'Categoria criada!';
        header('Location: /expenses/categories');
        exit;
    }

    /**
     * POST /expenses/categories/{id}/delete - Excluir categoria
     */
    public function expenseCategoryDelete(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);

        $existing = ExpenseCategory::findForCompany((int)$company['id'], $id);
        if ($existing) {
            ExpenseCategory::delete($id);
            $_SESSION['flash_success'] = 'Categoria excluída!';
        }

        header('Location: /expenses/categories');
        exit;
    }

    /**
     * GET /expenses/categories/seed - Inicializar categorias padrão
     */
    public function expenseCategorySeed(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $count = ExpenseCategory::seedDefaults((int)$company['id']);

        $_SESSION['flash_success'] = "$count categorias padrão criadas!";
        header('Location: /expenses/categories');
        exit;
    }

    /**
     * GET /financial/yearly — Relatório anual
     */
    public function yearly(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $year = (int)($_GET['year'] ?? date('Y'));
        $report = FinancialReport::getYearlySummary($companyId, $year);

        $this->view('admin/mobile/financial/yearly', [
            'pageTitle' => 'Relatório Anual',
            'activeNav' => 'settings',
            'year' => $year,
            'report' => $report,
            'company' => $company,
            'user' => $user,
        ]);
    }

    /**
     * GET /financial/settings — Configurações financeiras
     */
    public function settings(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $settings = FinancialSettings::get($companyId);
        $success = $_GET['success'] ?? null;
        $error = $_GET['error'] ?? null;

        $this->view('admin/mobile/financial/settings', [
            'pageTitle' => 'Config. Financeiras',
            'activeNav' => 'settings',
            'settings' => $settings,
            'success' => $success,
            'error' => $error,
            'company' => $company,
            'user' => $user,
        ]);
    }

    /**
     * POST /financial/settings — Salvar configurações
     */
    public function saveSettings(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
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

        header('Location: /financial/settings?success=1');
        exit;
    }

    /**
     * POST /financial/recalculate — Recalcular custos (AJAX)
     */
    public function recalculateCosts(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $costService = new CostCalculatorService($companyId);
        $updated = $costService->updateAllProductSnapshots();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $updated . ' produto(s) atualizado(s)',
            'count' => $updated
        ]);
        exit;
    }
}
