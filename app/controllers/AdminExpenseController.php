<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelos específicos
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/ExpenseCategory.php';

/**
 * Controller para gestão de Despesas
 */
class AdminExpenseController extends Controller
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
     * Lista de despesas
     */
    public function index(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $expenses = Expense::listByCompany($companyId, $month);
        $summary = Expense::getMonthlySummary($companyId, $month);
        $categories = ExpenseCategory::listByCompany($companyId);

        $availableMonths = [];
        for ($i = 0; $i < 12; $i++) {
            $m = date('Y-m', strtotime("-$i months"));
            $availableMonths[] = ['value' => $m, 'label' => self::formatMonthName($m)];
        }

        $success = isset($_GET['success']) ? (string)$_GET['success'] : null;
        $error = isset($_GET['error']) ? (string)$_GET['error'] : null;

        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;
        $toInt = static fn($v) => $v === null || $v === '' ? 0 : (int)$v;

        $payload = [
            'month' => $month,
            'month_label' => self::formatMonthName($month),
            'available_months' => $availableMonths,
            'expenses' => array_map(static function ($e) use ($toFloat, $toInt) {
                return [
                    'id' => $toInt($e['id'] ?? 0),
                    'category_id' => isset($e['category_id']) && $e['category_id'] !== null ? $toInt($e['category_id']) : null,
                    'category_name' => (string)($e['category_name'] ?? ''),
                    'category_type' => (string)($e['category_type'] ?? ''),
                    'description' => (string)($e['description'] ?? ''),
                    'amount' => $toFloat($e['amount'] ?? 0),
                    'expense_date' => (string)($e['expense_date'] ?? ''),
                    'reference_month' => (string)($e['reference_month'] ?? ''),
                    'payment_method' => (string)($e['payment_method'] ?? ''),
                    'notes' => (string)($e['notes'] ?? ''),
                ];
            }, $expenses),
            'summary' => [
                'total' => $toFloat($summary['total'] ?? 0),
                'fixed_total' => $toFloat($summary['fixed_total'] ?? 0),
                'variable_total' => $toFloat($summary['variable_total'] ?? 0),
                'count' => $toInt($summary['count'] ?? count($expenses)),
            ],
            'categories' => array_map(static function ($c) use ($toInt) {
                return [
                    'id' => $toInt($c['id'] ?? 0),
                    'name' => (string)($c['name'] ?? ''),
                    'type' => (string)($c['type'] ?? 'fixed'),
                    'description' => (string)($c['description'] ?? ''),
                ];
            }, $categories),
            'flash' => ['success' => $success, 'error' => $error],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/expenses',
                'create' => '/admin/' . rawurlencode($slug) . '/expenses/create',
                'store' => '/admin/' . rawurlencode($slug) . '/expenses/store',
                'edit_base' => '/admin/' . rawurlencode($slug) . '/expenses/',
                'destroy_base' => '/admin/' . rawurlencode($slug) . '/expenses/',
                'categories' => '/admin/' . rawurlencode($slug) . '/expenses/categories',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_EXPENSES__', $payload);
    }

    /**
     * Formulário de nova despesa
     */
    public function create(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        $categories = ExpenseCategory::listByCompany($companyId);

        $this->renderExpenseFormSpa($slug, $company, null, $categories);
    }

    private function renderExpenseFormSpa(string $slug, array $company, ?array $expense, array $categories): void
    {
        $isEdit = $expense !== null;
        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;
        $toInt = static fn($v) => $v === null || $v === '' ? 0 : (int)$v;

        $payload = [
            'is_edit' => $isEdit,
            'expense' => $isEdit ? [
                'id' => $toInt($expense['id'] ?? 0),
                'category_id' => isset($expense['category_id']) && $expense['category_id'] !== null ? $toInt($expense['category_id']) : null,
                'description' => (string)($expense['description'] ?? ''),
                'amount' => $toFloat($expense['amount'] ?? 0),
                'reference_month' => (string)($expense['reference_month'] ?? date('Y-m')),
                'expense_date' => (string)($expense['expense_date'] ?? ''),
                'payment_method' => (string)($expense['payment_method'] ?? ''),
                'notes' => (string)($expense['notes'] ?? ''),
            ] : [
                'id' => null,
                'category_id' => null,
                'description' => '',
                'amount' => 0,
                'reference_month' => date('Y-m'),
                'expense_date' => '',
                'payment_method' => '',
                'notes' => '',
            ],
            'categories' => array_map(static function ($c) use ($toInt) {
                return [
                    'id' => $toInt($c['id'] ?? 0),
                    'name' => (string)($c['name'] ?? ''),
                    'type' => (string)($c['type'] ?? 'fixed'),
                ];
            }, $categories),
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/expenses',
                'categories' => '/admin/' . rawurlencode($slug) . '/expenses/categories',
                'submit' => $isEdit
                    ? '/admin/' . rawurlencode($slug) . '/expenses/' . $toInt($expense['id'] ?? 0) . '/update'
                    : '/admin/' . rawurlencode($slug) . '/expenses/store',
                'destroy' => $isEdit
                    ? '/admin/' . rawurlencode($slug) . '/expenses/' . $toInt($expense['id'] ?? 0) . '/delete'
                    : null,
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_EXPENSE_FORM__', $payload);
    }

    /**
     * Salvar nova despesa
     */
    public function store(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $referenceMonth = $_POST['reference_month'] ?? date('Y-m');
        // Usar expense_date como primeiro dia do mês de referência ou data de pagamento
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
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/create?error=description'));
            exit;
        }

        if ($data['amount'] <= 0) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/create?error=amount'));
            exit;
        }

        $result = Expense::create($data);

        if ($result) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses?success=created&month=' . $referenceMonth));
        } else {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/create?error=1'));
        }
        exit;
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

        $expense = Expense::findForCompany($companyId, $id);

        if (!$expense) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses?error=notfound'));
            exit;
        }

        $categories = ExpenseCategory::listByCompany($companyId);
        $this->renderExpenseFormSpa($slug, $company, $expense, $categories);
    }

    /**
     * Atualizar despesa
     */
    public function update(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        // Verificar se pertence à empresa
        $existing = Expense::findForCompany($companyId, $id);
        if (!$existing) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses?error=notfound'));
            exit;
        }

        $referenceMonth = $_POST['reference_month'] ?? date('Y-m');
        $expenseDate = !empty($_POST['payment_date']) ? $_POST['payment_date'] : $referenceMonth . '-01';

        $data = [
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'description' => trim($_POST['description'] ?? ''),
            'amount' => (float)str_replace(',', '.', $_POST['amount'] ?? '0'),
            'expense_date' => $expenseDate,
            'payment_method' => $_POST['payment_method'] ?? null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ];

        Expense::update($id, $data);

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses?success=updated&month=' . $referenceMonth));
        exit;
    }

    /**
     * Excluir despesa
     */
    public function destroy(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $month = $_GET['month'] ?? date('Y-m');

        // Verificar se pertence à empresa
        $existing = Expense::findForCompany($companyId, $id);
        if (!$existing) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses?error=notfound&month=' . $month));
            exit;
        }

        Expense::delete($id);

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses?success=deleted&month=' . $month));
        exit;
    }

    /**
     * Gestão de categorias
     */
    public function categories(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $categories = ExpenseCategory::listByCompany($companyId);
        $success = isset($_GET['success']) ? (string)$_GET['success'] : null;
        $error = isset($_GET['error']) ? (string)$_GET['error'] : null;

        $payload = [
            'categories' => array_map(static function ($c): array {
                return [
                    'id' => (int)($c['id'] ?? 0),
                    'name' => (string)($c['name'] ?? ''),
                    'type' => (string)($c['type'] ?? 'fixed'),
                    'description' => (string)($c['description'] ?? ''),
                    'active' => (int)($c['active'] ?? 1) === 1,
                ];
            }, $categories),
            'flash' => ['success' => $success, 'error' => $error],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/expenses/categories',
                'expenses' => '/admin/' . rawurlencode($slug) . '/expenses',
                'store' => '/admin/' . rawurlencode($slug) . '/expenses/categories/store',
                'update_base' => '/admin/' . rawurlencode($slug) . '/expenses/categories/',
                'destroy_base' => '/admin/' . rawurlencode($slug) . '/expenses/categories/',
                'seed' => '/admin/' . rawurlencode($slug) . '/expenses/categories/seed',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_EXPENSE_CATEGORIES__', $payload);
    }

    /**
     * Salvar nova categoria
     */
    public function storeCategory(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $data = [
            'company_id' => $companyId,
            'name' => trim($_POST['name'] ?? ''),
            'type' => in_array($_POST['type'] ?? '', ['fixed', 'variable']) ? $_POST['type'] : 'fixed',
            'description' => trim($_POST['description'] ?? '') ?: null,
        ];

        if (empty($data['name'])) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/categories?error=name'));
            exit;
        }

        $result = ExpenseCategory::create($data);

        if ($result) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/categories?success=created'));
        } else {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/categories?error=1'));
        }
        exit;
    }

    /**
     * Atualizar categoria
     */
    public function updateCategory(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        // Verificar se pertence à empresa
        $existing = ExpenseCategory::findForCompany($companyId, $id);
        if (!$existing) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/categories?error=notfound'));
            exit;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => in_array($_POST['type'] ?? '', ['fixed', 'variable']) ? $_POST['type'] : 'fixed',
            'description' => trim($_POST['description'] ?? '') ?: null,
        ];

        ExpenseCategory::update($id, $data);

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/categories?success=updated'));
        exit;
    }

    /**
     * Excluir categoria
     */
    public function destroyCategory(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        // Verificar se pertence à empresa
        $existing = ExpenseCategory::findForCompany($companyId, $id);
        if (!$existing) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/categories?error=notfound'));
            exit;
        }

        ExpenseCategory::delete($id);

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/categories?success=deleted'));
        exit;
    }

    /**
     * Inicializar categorias padrão
     */
    public function seedCategories(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $count = ExpenseCategory::seedDefaults($companyId);

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/expenses/categories?success=seeded&count=' . $count));
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
        
        return ($months[$m] ?? $m) . '/' . $y;
    }
}
