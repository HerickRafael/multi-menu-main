<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/SuperAdminMiddleware.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';
require_once __DIR__ . '/../services/SmartCache.php';

class SuperAdminController extends Controller
{
    private const FLASH_KEY = 'superadmin_flash';
    private const OLD_KEY = 'superadmin_old_input';

    private function bustCompanyCache(int $companyId, ?string $slugOld, ?string $slugNew): void
    {
        SmartCache::forget('company:id:' . $companyId);
        if ($slugOld !== null && $slugOld !== '') {
            SmartCache::forget('company:slug:' . $slugOld);
        }
        if ($slugNew !== null && $slugNew !== '' && $slugNew !== $slugOld) {
            SmartCache::forget('company:slug:' . $slugNew);
        }
        SmartCache::forgetByPattern('companies:*');
    }

    private function redirectWithFlash(string $type, string $message, string $to): void
    {
        $_SESSION[self::FLASH_KEY] = ['type' => $type, 'message' => $message];
        header('Location: ' . base_url($to));
        exit;
    }

    private function parseCompanyScope(array $companies): array
    {
        $companyId = max(0, (int)($_GET['company_id'] ?? 0));
        $selectedCompany = null;

        if ($companyId > 0) {
            foreach ($companies as $company) {
                if ((int)($company['id'] ?? 0) === $companyId) {
                    $selectedCompany = $company;
                    break;
                }
            }
            if (!$selectedCompany) {
                $companyId = 0;
            }
        }

        return [$companyId, $selectedCompany];
    }

    /** GET /superadmin/catalog */
    public function catalog(array $params): void
    {
        SuperAdminMiddleware::enforce();

        $pdo = $this->db();
        $companies = $pdo->query('SELECT id, name, slug, active FROM companies ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
        [$companyId, $selectedCompany] = $this->parseCompanyScope($companies);

        $where = '';
        $bind = [];
        if ($companyId > 0) {
            $where = ' WHERE p.company_id = ?';
            $bind[] = $companyId;
        }

        $stStats = $pdo->prepare(
            'SELECT
                COUNT(*) AS total_products,
                SUM(CASE WHEN p.active = 1 THEN 1 ELSE 0 END) AS active_products,
                SUM(CASE WHEN p.active = 0 THEN 1 ELSE 0 END) AS inactive_products,
                SUM(CASE WHEN (p.image IS NULL OR p.image = \'\') THEN 1 ELSE 0 END) AS no_image_products
             FROM products p' . $where
        );
        $stStats->execute($bind);
        $stats = $stStats->fetch(PDO::FETCH_ASSOC) ?: [];

        if ($companyId > 0) {
            $stCat = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE company_id = ?');
            $stCat->execute([$companyId]);
            $totalCategories = (int)$stCat->fetchColumn();
        } else {
            $totalCategories = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        }

        $stRows = $pdo->prepare(
            'SELECT p.id, p.name, p.price, p.active, p.image,
                    c.name AS category_name,
                    co.id AS company_id, co.name AS company_name, co.slug AS company_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             INNER JOIN companies co ON co.id = p.company_id' .
             $where .
            ' ORDER BY p.updated_at DESC, p.id DESC
              LIMIT 50'
        );
        $stRows->execute($bind);
        $rows = $stRows->fetchAll(PDO::FETCH_ASSOC);

        $this->view('super-admin/catalog', [
            'title' => 'Cardápios e Produtos',
            'companies' => $companies,
            'selectedCompany' => $selectedCompany,
            'selectedCompanyId' => $companyId,
            'statsTotalProducts' => (int)($stats['total_products'] ?? 0),
            'statsActiveProducts' => (int)($stats['active_products'] ?? 0),
            'statsInactiveProducts' => (int)($stats['inactive_products'] ?? 0),
            'statsNoImageProducts' => (int)($stats['no_image_products'] ?? 0),
            'statsTotalCategories' => $totalCategories,
            'rows' => $rows,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** GET /superadmin/orders-live */
    public function ordersLive(array $params): void
    {
        SuperAdminMiddleware::enforce();

        $pdo = $this->db();
        $companies = $pdo->query('SELECT id, name, slug, active FROM companies ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
        [$companyId, $selectedCompany] = $this->parseCompanyScope($companies);

        $status = trim((string)($_GET['status'] ?? ''));
        $validStatus = ['pending', 'paid', 'completed', 'canceled'];
        if (!in_array($status, $validStatus, true)) {
            $status = '';
        }

        $conditions = [];
        $bind = [];
        if ($companyId > 0) {
            $conditions[] = 'o.company_id = ?';
            $bind[] = $companyId;
        }
        if ($status !== '') {
            $conditions[] = 'o.status = ?';
            $bind[] = $status;
        }
        $where = $conditions ? (' WHERE ' . implode(' AND ', $conditions)) : '';

        $stTotals = $pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN o.status = \'pending\' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN o.status = \'paid\' THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN o.status = \'completed\' THEN 1 ELSE 0 END) AS completed_count,
                SUM(CASE WHEN o.status = \'canceled\' THEN 1 ELSE 0 END) AS canceled_count,
                COALESCE(SUM(o.total), 0) AS total_value
             FROM orders o' . $where
        );
        $stTotals->execute($bind);
        $totals = $stTotals->fetch(PDO::FETCH_ASSOC) ?: [];

        $stRows = $pdo->prepare(
            'SELECT o.id, o.company_id, o.customer_name, o.customer_phone, o.total, o.status, o.created_at,
                    c.name AS company_name, c.slug AS company_slug
             FROM orders o
             INNER JOIN companies c ON c.id = o.company_id' .
             $where .
            ' ORDER BY o.created_at DESC
              LIMIT 80'
        );
        $stRows->execute($bind);
        $rows = $stRows->fetchAll(PDO::FETCH_ASSOC);

        $this->view('super-admin/orders-live', [
            'title' => 'Pedidos em Tempo Real',
            'companies' => $companies,
            'selectedCompany' => $selectedCompany,
            'selectedCompanyId' => $companyId,
            'selectedStatus' => $status,
            'statsTotalOrders' => (int)($totals['total'] ?? 0),
            'statsPendingOrders' => (int)($totals['pending_count'] ?? 0),
            'statsPaidOrders' => (int)($totals['paid_count'] ?? 0),
            'statsCompletedOrders' => (int)($totals['completed_count'] ?? 0),
            'statsCanceledOrders' => (int)($totals['canceled_count'] ?? 0),
            'statsTotalValue' => (float)($totals['total_value'] ?? 0),
            'rows' => $rows,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** GET /superadmin/operators */
    public function operators(array $params): void
    {
        SuperAdminMiddleware::enforce();

        $pdo = $this->db();
        $companies = $pdo->query('SELECT id, name, slug, active FROM companies ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
        [$companyId, $selectedCompany] = $this->parseCompanyScope($companies);

        $role = trim((string)($_GET['role'] ?? ''));
        $validRoles = ['owner', 'staff', 'root'];
        if (!in_array($role, $validRoles, true)) {
            $role = '';
        }

        $conditions = [];
        $bind = [];
        if ($companyId > 0) {
            $conditions[] = 'u.company_id = ?';
            $bind[] = $companyId;
        }
        if ($role !== '') {
            $conditions[] = 'u.role = ?';
            $bind[] = $role;
        }
        $where = $conditions ? (' WHERE ' . implode(' AND ', $conditions)) : '';

        $stTotals = $pdo->prepare(
            'SELECT
                COUNT(*) AS total_users,
                SUM(CASE WHEN u.active = 1 THEN 1 ELSE 0 END) AS active_users,
                SUM(CASE WHEN u.active = 0 THEN 1 ELSE 0 END) AS inactive_users,
                SUM(CASE WHEN u.role = \'owner\' THEN 1 ELSE 0 END) AS owner_users,
                SUM(CASE WHEN u.role = \'staff\' THEN 1 ELSE 0 END) AS staff_users,
                SUM(CASE WHEN u.role = \'root\' THEN 1 ELSE 0 END) AS root_users
             FROM users u' . $where
        );
        $stTotals->execute($bind);
        $totals = $stTotals->fetch(PDO::FETCH_ASSOC) ?: [];

        $stRows = $pdo->prepare(
            'SELECT u.id, u.name, u.email, u.role, u.active, u.created_at,
                    c.id AS company_id, c.name AS company_name, c.slug AS company_slug
             FROM users u
             LEFT JOIN companies c ON c.id = u.company_id' .
             $where .
            ' ORDER BY u.created_at DESC, u.id DESC
              LIMIT 80'
        );
        $stRows->execute($bind);
        $rows = $stRows->fetchAll(PDO::FETCH_ASSOC);

        $this->view('super-admin/operators', [
            'title' => 'Usuários e Operadores',
            'companies' => $companies,
            'selectedCompany' => $selectedCompany,
            'selectedCompanyId' => $companyId,
            'selectedRole' => $role,
            'statsTotalUsers' => (int)($totals['total_users'] ?? 0),
            'statsActiveUsers' => (int)($totals['active_users'] ?? 0),
            'statsInactiveUsers' => (int)($totals['inactive_users'] ?? 0),
            'statsOwnerUsers' => (int)($totals['owner_users'] ?? 0),
            'statsStaffUsers' => (int)($totals['staff_users'] ?? 0),
            'statsRootUsers' => (int)($totals['root_users'] ?? 0),
            'rows' => $rows,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** GET /superadmin */
    public function dashboard(array $params): void
    {
        SuperAdminMiddleware::enforce();

        // Render the React SPA instead of PHP view
        $this->view('super-admin/spa', [
            'title' => 'Super Admin - Dashboard',
            'superAdminName' => $_SESSION['super_admin_name'] ?? 'Admin',
        ]);
        return;

        // JOIN-based approach: eliminates N+1 correlated subqueries
        $aggregateJoin = '
            LEFT JOIN (
                SELECT company_id, MIN(id) AS min_id FROM users GROUP BY company_id
            ) u_min ON u_min.company_id = c.id
            LEFT JOIN users u_first ON u_first.id = u_min.min_id
            LEFT JOIN (
                SELECT company_id, COUNT(*) AS order_count FROM orders GROUP BY company_id
            ) oc ON oc.company_id = c.id';

        if ($search !== '') {
            $like = '%' . $search . '%';
            $stCnt = $pdo->prepare('SELECT COUNT(*) FROM companies WHERE name LIKE ? OR slug LIKE ?');
            $stCnt->execute([$like, $like]);
            $cnt = (int)$stCnt->fetchColumn();

            $stRows = $pdo->prepare(
                'SELECT c.*, u_first.email AS admin_email, COALESCE(oc.order_count, 0) AS order_count
                 FROM companies c' . $aggregateJoin . '
                 WHERE c.name LIKE ? OR c.slug LIKE ?
                 ORDER BY c.id DESC
                 LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset
            );
            $stRows->execute([$like, $like]);
            $rows = $stRows->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $cnt = (int)$pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
            $stRows = $pdo->prepare(
                'SELECT c.*, u_first.email AS admin_email, COALESCE(oc.order_count, 0) AS order_count
                 FROM companies c' . $aggregateJoin . '
                 ORDER BY c.id DESC
                 LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset
            );
            $stRows->execute([]);
            $rows = $stRows->fetchAll(PDO::FETCH_ASSOC);
        }

        $flash = $_SESSION[self::FLASH_KEY] ?? null;
        unset($_SESSION[self::FLASH_KEY]);

        $totalPages = max(1, (int)ceil($cnt / $per));

        $this->view('super-admin/dashboard', [
            'title' => 'Dashboard',
            'rows' => $rows,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $cnt,
            'flash' => $flash,
            'searchQuery' => $search,
            'statsTotal' => $statsTotal,
            'statsActive' => $statsActive,
            'statsInactive' => $statsInactive,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** GET /superadmin/companies/create */
    public function create(array $params): void
    {
        SuperAdminMiddleware::enforce();

        $errors = $_SESSION['superadmin_errors'] ?? [];
        $old = $_SESSION[self::OLD_KEY] ?? [];
        unset($_SESSION['superadmin_errors'], $_SESSION[self::OLD_KEY]);

        $flash = $_SESSION[self::FLASH_KEY] ?? null;
        unset($_SESSION[self::FLASH_KEY]);

        $this->view('super-admin/company-form', [
            'title' => 'Nova loja',
            'editMode' => false,
            'company' => null,
            'errors' => $errors,
            'old' => $old,
            'flash' => $flash,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** POST /superadmin/companies */
    public function store(array $params): void
    {
        SuperAdminMiddleware::enforce();

        $name = trim((string)($_POST['company_name'] ?? ''));
        $slug = strtolower(trim((string)($_POST['slug'] ?? '')));
        $whatsappRaw = trim((string)($_POST['whatsapp'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $adminName = trim((string)($_POST['admin_name'] ?? ''));
        $adminEmail = strtolower(trim((string)($_POST['admin_email'] ?? '')));
        $adminPass = (string)($_POST['admin_password'] ?? '');

        $whatsapp = preg_replace('/\D/', '', $whatsappRaw);
        if ($whatsapp === '') {
            $whatsapp = null;
        }

        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) {
            $errors['company_name'] = 'Nome da loja é obrigatório (máx. 150 caracteres).';
        }
        if ($slug === '' || strlen($slug) > 100 || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors['slug'] = 'Slug obrigatório: apenas letras minúsculas, números e hífen (máx. 100).';
        }
        if (Company::findBySlug($slug)) {
            $errors['slug'] = 'Este slug já está em uso.';
        }
        if ($adminName === '' || mb_strlen($adminName) > 150) {
            $errors['admin_name'] = 'Nome do admin é obrigatório (máx. 150 caracteres).';
        }
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['admin_email'] = 'Email do admin inválido.';
        } elseif (User::findByEmail($adminEmail)) {
            $errors['admin_email'] = 'Este email já está cadastrado.';
        }
        if (strlen($adminPass) < 8) {
            $errors['admin_password'] = 'Senha deve ter no mínimo 8 caracteres.';
        }

        if ($errors) {
            $_SESSION['superadmin_errors'] = $errors;
            $_SESSION[self::OLD_KEY] = $_POST;
            header('Location: ' . base_url('superadmin/companies/create'));
            exit;
        }

        $hash = password_hash($adminPass, PASSWORD_BCRYPT);

        $pdo = $this->db();
        $pdo->beginTransaction();

        try {
            $st = $pdo->prepare(
                'INSERT INTO companies (
                    slug, name, whatsapp, address, active, created_at,
                    delivery_after_hours_fee, delivery_free_enabled
                ) VALUES (?, ?, ?, ?, 1, NOW(), 0.00, 0)'
            );
            $st->execute([
                $slug,
                $name,
                $whatsapp,
                $address !== '' ? $address : null,
            ]);

            $companyId = (int)$pdo->lastInsertId();

            $u = $pdo->prepare(
                'INSERT INTO users (company_id, name, email, password_hash, role, active, created_at)
                 VALUES (?, ?, ?, ?, \'owner\', 1, NOW())'
            );
            $u->execute([$companyId, $adminName, $adminEmail, $hash]);

            $h = $pdo->prepare(
                'INSERT INTO company_hours (company_id, weekday, is_open, open1, close1, open2, close2)
                 VALUES (?, ?, 0, NULL, NULL, NULL, NULL)'
            );
            for ($w = 1; $w <= 7; $w++) {
                $h->execute([$companyId, $w]);
            }

            $pdo->commit();

            $this->bustCompanyCache($companyId, null, $slug);

            $_SESSION[self::FLASH_KEY] = [
                'type' => 'success',
                'message' => 'Loja criada. Cardápio em ' . base_url($slug),
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[SuperAdmin] store: ' . $e->getMessage());
            $_SESSION['superadmin_errors'] = ['_global' => 'Erro ao salvar. Tente novamente.'];
            $_SESSION[self::OLD_KEY] = $_POST;
            header('Location: ' . base_url('superadmin/companies/create'));
            exit;
        }

        header('Location: ' . base_url('superadmin'));
        exit;
    }

    /** GET /superadmin/companies/{id} */
    public function edit(array $params): void
    {
        SuperAdminMiddleware::enforce();

        $id = (int)($params['id'] ?? 0);
        if ($id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin');
        }

        $pdo = $this->db();
        $st = $pdo->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $company = $st->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            $this->redirectWithFlash('error', 'Loja não encontrada.', 'superadmin');
        }

        $u = $pdo->prepare(
            'SELECT email FROM users WHERE company_id = ? ORDER BY id ASC LIMIT 1'
        );
        $u->execute([$id]);
        $adminEmailRow = $u->fetch(PDO::FETCH_ASSOC);

        $errors = $_SESSION['superadmin_errors'] ?? [];
        $old = $_SESSION[self::OLD_KEY] ?? [];
        unset($_SESSION['superadmin_errors'], $_SESSION[self::OLD_KEY]);

        $flash = $_SESSION[self::FLASH_KEY] ?? null;
        unset($_SESSION[self::FLASH_KEY]);

        $this->view('super-admin/company-form', [
            'title' => 'Editar loja',
            'editMode' => true,
            'company' => $company,
            'adminEmailDisplay' => $adminEmailRow['email'] ?? '',
            'errors' => $errors,
            'old' => $old,
            'flash' => $flash,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** POST /superadmin/companies/{id} */
    public function update(array $params): void
    {
        SuperAdminMiddleware::enforce();

        $id = (int)($params['id'] ?? 0);
        if ($id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin');
        }

        $pdo = $this->db();
        $st = $pdo->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $existing = $st->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $this->redirectWithFlash('error', 'Loja não encontrada.', 'superadmin');
        }

        $name = trim((string)($_POST['company_name'] ?? ''));
        $slug = strtolower(trim((string)($_POST['slug'] ?? '')));
        $whatsappRaw = trim((string)($_POST['whatsapp'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $active = isset($_POST['company_active']) ? 1 : 0;
        $newPass = (string)($_POST['admin_password_new'] ?? '');

        $whatsapp = preg_replace('/\D/', '', $whatsappRaw);
        if ($whatsapp === '') {
            $whatsapp = null;
        }

        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) {
            $errors['company_name'] = 'Nome da loja é obrigatório (máx. 150 caracteres).';
        }
        if ($slug === '' || strlen($slug) > 100 || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors['slug'] = 'Slug inválido.';
        } else {
            $chk = $pdo->prepare('SELECT id FROM companies WHERE slug = ? AND id <> ? LIMIT 1');
            $chk->execute([$slug, $id]);
            if ($chk->fetchColumn()) {
                $errors['slug'] = 'Este slug já está em uso.';
            }
        }

        if ($newPass !== '' && strlen($newPass) < 8) {
            $errors['admin_password_new'] = 'Nova senha deve ter no mínimo 8 caracteres.';
        }

        if ($errors) {
            $_SESSION['superadmin_errors'] = $errors;
            $_SESSION[self::OLD_KEY] = $_POST;
            header('Location: ' . base_url('superadmin/companies/' . $id));
            exit;
        }

        $slugOld = (string)$existing['slug'];

        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare(
                'UPDATE companies SET name = ?, slug = ?, whatsapp = ?, address = ?, active = ? WHERE id = ?'
            );
            $up->execute([
                $name,
                $slug,
                $whatsapp,
                $address !== '' ? $address : null,
                $active,
                $id,
            ]);

            if ($newPass !== '') {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $owner = $pdo->prepare(
                    'SELECT id FROM users WHERE company_id = ? ORDER BY id ASC LIMIT 1'
                );
                $owner->execute([$id]);
                $ownerId = (int)$owner->fetchColumn();
                if ($ownerId > 0) {
                    $pu = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $pu->execute([$hash, $ownerId]);
                }
            }

            $pdo->commit();

            $this->bustCompanyCache($id, $slugOld, $slug);

            $_SESSION[self::FLASH_KEY] = ['type' => 'success', 'message' => 'Loja atualizada.'];
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[SuperAdmin] update: ' . $e->getMessage());
            $_SESSION['superadmin_errors'] = ['_global' => 'Erro ao atualizar.'];
            $_SESSION[self::OLD_KEY] = $_POST;
            header('Location: ' . base_url('superadmin/companies/' . $id));
            exit;
        }

        header('Location: ' . base_url('superadmin'));
        exit;
    }

    /** POST /superadmin/companies/{id}/toggle */
    public function toggle(array $params): void
    {
        SuperAdminMiddleware::enforce();

        $id = (int)($params['id'] ?? 0);
        if ($id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin');
        }

        $pdo = $this->db();
        $st = $pdo->prepare('SELECT id, slug, active FROM companies WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->redirectWithFlash('error', 'Loja não encontrada.', 'superadmin');
        }

        $newActive = !empty($row['active']) ? 0 : 1;
        $slug = (string)$row['slug'];

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE companies SET active = ? WHERE id = ?')->execute([$newActive, $id]);
            $pdo->prepare('UPDATE users SET active = ? WHERE company_id = ?')->execute([$newActive, $id]);
            $pdo->commit();

            $this->bustCompanyCache($id, $slug, $slug);
            $_SESSION[self::FLASH_KEY] = [
                'type' => 'success',
                'message' => $newActive ? 'Loja e usuários ativados.' : 'Loja e usuários desativados.',
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[SuperAdmin] toggle: ' . $e->getMessage());
            $_SESSION[self::FLASH_KEY] = ['type' => 'error', 'message' => 'Erro ao alterar status.'];
        }

        header('Location: ' . base_url('superadmin'));
        exit;
    }

    // ============================================================================
    // FASE 1: Dashboard Real-time + Gestão de Lojas
    // ============================================================================

    /** GET /superadmin/dashboard-home - Dashboard global com KPIs */
    public function dashboardHome(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/MetricsService.php';

        $summary = MetricsService::getDashboardSummary();

        $flash = $_SESSION['superadmin_flash'] ?? null;
        unset($_SESSION['superadmin_flash']);

        $this->view('super-admin/dashboard-home', [
            'title' => 'Dashboard Global',
            'summary' => $summary,
            'flash' => $flash,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** GET /superadmin/stores - Lista todas as lojas com filtros */
    public function storesIndex(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/MetricsService.php';

        $page = max(1, (int)($_GET['page'] ?? 1));
        $per_page = 20;
        $status = trim((string)($_GET['status'] ?? ''));
        $search = trim((string)($_GET['q'] ?? ''));

        $result = MetricsService::getAllCompaniesMetrics(
            $status ?: null,
            $search ?: null,
            $page,
            $per_page
        );

        $flash = $_SESSION['superadmin_flash'] ?? null;
        unset($_SESSION['superadmin_flash']);

        $this->view('super-admin/stores/index', [
            'title' => 'Gestão de Lojas',
            'companies' => $result['companies'],
            'pagination' => $result['pagination'],
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'flash' => $flash,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** GET /superadmin/stores/:id - Detalhe de uma loja */
    public function storesShow(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/StoreManagementService.php';

        $company_id = (int)($params['id'] ?? 0);
        if ($company_id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin/stores');
        }

        $store = StoreManagementService::getStoreDetails($company_id);
        if (!$store) {
            $this->redirectWithFlash('error', 'Loja não encontrada.', 'superadmin/stores');
        }

        $flash = $_SESSION['superadmin_flash'] ?? null;
        unset($_SESSION['superadmin_flash']);

        $this->view('super-admin/stores/show', [
            'title' => 'Detalhes da Loja: ' . $store['name'],
            'store' => $store,
            'flash' => $flash,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** GET /superadmin/stores/:id/edit - Formulário de edição */
    public function storesEditForm(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/StoreManagementService.php';

        $company_id = (int)($params['id'] ?? 0);
        if ($company_id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin/stores');
        }

        $store = StoreManagementService::getStoreDetails($company_id);
        if (!$store) {
            $this->redirectWithFlash('error', 'Loja não encontrada.', 'superadmin/stores');
        }

        $old = $_SESSION['superadmin_old_input'] ?? [];
        $errors = $_SESSION['superadmin_errors'] ?? [];
        unset($_SESSION['superadmin_old_input'], $_SESSION['superadmin_errors']);

        $flash = $_SESSION['superadmin_flash'] ?? null;
        unset($_SESSION['superadmin_flash']);

        $this->view('super-admin/stores/edit', [
            'title' => 'Editar Loja: ' . $store['name'],
            'store' => $store,
            'old' => $old,
            'errors' => $errors,
            'flash' => $flash,
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
        ]);
    }

    /** POST /superadmin/stores/:id/update - Atualiza informações da loja */
    public function storesUpdate(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/StoreManagementService.php';

        $company_id = (int)($params['id'] ?? 0);
        if ($company_id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin/stores');
        }

        $admin_id = (int)($_SESSION['super_admin_id'] ?? 0);

        // Validações básicas
        $errors = [];
        $name = trim((string)($_POST['name'] ?? ''));
        $whatsapp = trim((string)($_POST['whatsapp'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $min_order = trim((string)($_POST['min_order'] ?? ''));

        if (empty($name)) {
            $errors['name'] = 'Nome é obrigatório';
        }

        if (!empty($min_order) && !is_numeric($min_order)) {
            $errors['min_order'] = 'Valor mínimo deve ser numérico';
        }

        if (!empty($errors)) {
            $_SESSION['superadmin_errors'] = $errors;
            $_SESSION['superadmin_old_input'] = $_POST;
            header('Location: ' . base_url("superadmin/stores/{$company_id}/edit"));
            exit;
        }

        $data = [];
        if ($name) $data['name'] = $name;
        if ($whatsapp) $data['whatsapp'] = $whatsapp;
        if ($address) $data['address'] = $address;
        if ($min_order) $data['min_order'] = (float)$min_order;

        $result = StoreManagementService::updateStore($company_id, $data, $admin_id);

        $this->redirectWithFlash(
            $result['success'] ? 'success' : 'error',
            $result['message'],
            "superadmin/stores/{$company_id}"
        );
    }

    /** POST /superadmin/stores/:id/suspend - Suspende loja */
    public function storesSuspend(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/StoreManagementService.php';

        $company_id = (int)($params['id'] ?? 0);
        $admin_id = (int)($_SESSION['super_admin_id'] ?? 0);

        if ($company_id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin/stores');
        }

        $reason = trim((string)($_POST['reason'] ?? 'Suspensão administrativa'));

        $result = StoreManagementService::suspendStore($company_id, $reason, $admin_id);

        $this->redirectWithFlash(
            $result['success'] ? 'success' : 'error',
            $result['message'],
            "superadmin/stores/{$company_id}"
        );
    }

    /** POST /superadmin/stores/:id/activate - Ativa loja */
    public function storesActivate(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/StoreManagementService.php';

        $company_id = (int)($params['id'] ?? 0);
        $admin_id = (int)($_SESSION['super_admin_id'] ?? 0);

        if ($company_id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin/stores');
        }

        $result = StoreManagementService::activateStore($company_id, $admin_id);

        $this->redirectWithFlash(
            $result['success'] ? 'success' : 'error',
            $result['message'],
            "superadmin/stores/{$company_id}"
        );
    }

    /** POST /superadmin/stores/:id/maintenance - Coloca loja em manutenção */
    public function storesMaintenance(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/StoreManagementService.php';

        $company_id = (int)($params['id'] ?? 0);
        $admin_id = (int)($_SESSION['super_admin_id'] ?? 0);

        if ($company_id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin/stores');
        }

        $reason = trim((string)($_POST['reason'] ?? 'Manutenção programada'));

        $result = StoreManagementService::maintenanceStore($company_id, $reason, $admin_id);

        $this->redirectWithFlash(
            $result['success'] ? 'success' : 'error',
            $result['message'],
            "superadmin/stores/{$company_id}"
        );
    }

    /* ========= FASE 2: Auditoria + Impersonate ========= */

    /** GET /superadmin/audit-logs - Lista logs de auditoria */
    public function auditLogs(array $params = []): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../models/AuditLog.php';
        require_once __DIR__ . '/../services/AuditService.php';

        $page = max(1, (int)($_GET['page'] ?? 1));
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        // Filtros
        $filters = [
            'super_admin_id' => (int)($_GET['admin_id'] ?? 0) ?: null,
            'company_id' => (int)($_GET['company_id'] ?? 0) ?: null,
            'action' => $_GET['action'] ?? null,
            'module' => $_GET['module'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];

        $filters = array_filter($filters, fn($v) => $v !== null);

        $logs = AuditLog::search($filters, $per_page, $offset);
        $total = AuditLog::count($filters);
        $total_pages = ceil($total / $per_page);

        $pagination = [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => $total_pages,
            'has_prev' => $page > 1,
            'has_next' => $page < $total_pages
        ];

        $this->view('super-admin/audit-logs', [
            'logs' => $logs,
            'pagination' => $pagination,
            'filters' => $filters
        ]);
    }

    /** GET /superadmin/impersonate/:id - Iniciar impersonação (formulário) */
    public function impersonateForm(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../models/Company.php';

        $company_id = (int)($params['id'] ?? 0);
        if ($company_id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin/stores');
        }

        $company = Company::find($company_id);
        if (!$company) {
            $this->redirectWithFlash('error', 'Loja não encontrada.', 'superadmin/stores');
        }

        $this->view('super-admin/impersonate-form', ['company' => $company]);
    }

    /** POST /superadmin/impersonate/:id/start - Iniciar impersonação */
    public function impersonateStart(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/ImpersonationService.php';

        $company_id = (int)($params['id'] ?? 0);
        $admin_id = (int)($_SESSION['super_admin_id'] ?? 0);

        if ($company_id < 1) {
            $this->redirectWithFlash('error', 'Loja inválida.', 'superadmin/stores');
        }

        $reason = trim((string)($_POST['reason'] ?? 'Support/Debugging'));
        $role = in_array($_POST['role'] ?? 'owner', ['owner', 'staff']) ? $_POST['role'] : 'owner';

        $result = ImpersonationService::start($admin_id, $company_id, $reason, $role);

        if (!$result['success']) {
            $this->redirectWithFlash('error', $result['message'], 'superadmin/stores');
        }

        // Iniciar sessão isolada de impersonação
        $_SESSION['impersonation_token'] = $result['session_token'];
        $_SESSION['impersonation_id'] = $result['impersonation_id'];
        $_SESSION['impersonated_company_id'] = $company_id;
        $_SESSION['impersonated_role'] = $role;
        $_SESSION['impersonated_by_admin_id'] = $admin_id;

        $this->redirectWithFlash('success', $result['message'], 'dashboard');
    }

    /** POST /superadmin/impersonate/end - Encerrar impersonação */
    public function impersonateEnd(): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/ImpersonationService.php';

        $session_token = $_SESSION['impersonation_token'] ?? null;
        if (!$session_token) {
            $this->redirectWithFlash('error', 'Nenhuma impersonação ativa.', 'superadmin');
        }

        $result = ImpersonationService::end($session_token);

        // Limpar sessão de impersonação
        unset(
            $_SESSION['impersonation_token'],
            $_SESSION['impersonation_id'],
            $_SESSION['impersonated_company_id'],
            $_SESSION['impersonated_role'],
            $_SESSION['impersonated_by_admin_id']
        );

        $this->redirectWithFlash(
            $result['success'] ? 'success' : 'error',
            $result['message'],
            'superadmin'
        );
    }

    /** GET /superadmin/impersonations - Histórico de impersonações */
    public function impersonationHistory(): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/ImpersonationService.php';

        $page = max(1, (int)($_GET['page'] ?? 1));
        $admin_id = (int)($_SESSION['super_admin_id'] ?? 0);

        $history = ImpersonationService::getHistory($admin_id, $page, 50);

        $this->view('super-admin/impersonation-history', [
            'history' => $history,
            'admin_id' => $admin_id
        ]);
    }

    /* ========= FASE 3: Pedidos + Logs Centralizados ========= */

    /** GET /superadmin/orders-monitor */
    public function ordersMonitor(array $params = []): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/OrderMonitoringService.php';

        $companyId = (int)($_GET['company_id'] ?? 0);
        $status = trim((string)($_GET['status'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));

        $summary = OrderMonitoringService::getGlobalSummary($companyId > 0 ? $companyId : null);
        $listing = OrderMonitoringService::listOrders(
            $companyId > 0 ? $companyId : null,
            $status !== '' ? $status : null,
            $page,
            40
        );

        $companies = $this->db()->query('SELECT id, name, slug FROM companies ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

        $this->view('super-admin/orders-monitor', [
            'summary' => $summary,
            'listing' => $listing,
            'companies' => $companies,
            'selectedCompanyId' => $companyId,
            'selectedStatus' => $status,
        ]);
    }

    /** GET /superadmin/orders/{id}/timeline */
    public function orderTimeline(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/OrderMonitoringService.php';

        $orderId = (int)($params['id'] ?? 0);
        if ($orderId < 1) {
            $this->redirectWithFlash('error', 'Pedido inválido.', 'superadmin/orders-monitor');
        }

        $timeline = OrderMonitoringService::getOrderTimeline($orderId);
        if (!$timeline['order']) {
            $this->redirectWithFlash('error', 'Pedido não encontrado.', 'superadmin/orders-monitor');
        }

        $this->view('super-admin/order-timeline', [
            'order' => $timeline['order'],
            'events' => $timeline['events'],
        ]);
    }

    /** GET /superadmin/system-logs */
    public function systemLogs(array $params = []): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/LogAggregatorService.php';

        $filters = [
            'level' => trim((string)($_GET['level'] ?? '')),
            'module' => trim((string)($_GET['module'] ?? '')),
            'company_id' => (int)($_GET['company_id'] ?? 0),
            'search' => trim((string)($_GET['search'] ?? '')),
        ];
        if ($filters['company_id'] < 1) {
            unset($filters['company_id']);
        }
        if ($filters['level'] === '') {
            unset($filters['level']);
        }
        if ($filters['module'] === '') {
            unset($filters['module']);
        }
        if ($filters['search'] === '') {
            unset($filters['search']);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $logs = LogAggregatorService::getLogs($filters, $page, 60);

        $this->view('super-admin/system-logs', [
            'filters' => $filters,
            'logs' => $logs,
        ]);
    }

    /** POST /superadmin/system-logs/ingest */
    public function systemLogsIngest(): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/LogAggregatorService.php';

        $filePath = __DIR__ . '/../../storage/logs/exceptions.log';
        $inserted = LogAggregatorService::ingestExceptionsLog($filePath, 300);

        $this->redirectWithFlash('success', "Ingestão concluída: {$inserted} linhas importadas.", 'superadmin/system-logs');
    }

    /** GET /superadmin/system-logs/export */
    public function systemLogsExport(): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/LogAggregatorService.php';

        $filters = [
            'level' => trim((string)($_GET['level'] ?? '')),
            'module' => trim((string)($_GET['module'] ?? '')),
            'company_id' => (int)($_GET['company_id'] ?? 0),
            'search' => trim((string)($_GET['search'] ?? '')),
        ];
        if (($filters['company_id'] ?? 0) < 1) {
            unset($filters['company_id']);
        }
        if (($filters['level'] ?? '') === '') {
            unset($filters['level']);
        }
        if (($filters['module'] ?? '') === '') {
            unset($filters['module']);
        }
        if (($filters['search'] ?? '') === '') {
            unset($filters['search']);
        }

        $csv = LogAggregatorService::exportCsv($filters, 2000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="system-logs-export.csv"');
        echo $csv;
        exit;
    }

    /* ========= FASE 4: Webhooks + Filas ========= */

    /** GET /superadmin/webhooks */
    public function webhooks(array $params = []): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/WebhookService.php';

        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'source' => trim((string)($_GET['source'] ?? '')),
            'company_id' => (int)($_GET['company_id'] ?? 0),
        ];

        if (($filters['status'] ?? '') === '') {
            unset($filters['status']);
        }
        if (($filters['source'] ?? '') === '') {
            unset($filters['source']);
        }
        if (($filters['company_id'] ?? 0) < 1) {
            unset($filters['company_id']);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = WebhookService::getDashboard($filters, $page, 40);

        $this->view('super-admin/webhooks', [
            'filters' => $filters,
            'data' => $result,
        ]);
    }

    /** POST /superadmin/webhooks/{id}/retry */
    public function webhooksRetry(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/WebhookService.php';

        $id = (int)($params['id'] ?? 0);
        if ($id < 1) {
            $this->redirectWithFlash('error', 'Webhook inválido.', 'superadmin/webhooks');
        }

        $result = WebhookService::retry($id);
        $this->redirectWithFlash($result['success'] ? 'success' : 'error', $result['message'], 'superadmin/webhooks');
    }

    /** GET /superadmin/queues */
    public function queues(array $params = []): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/QueueService.php';

        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'job_type' => trim((string)($_GET['job_type'] ?? '')),
            'company_id' => (int)($_GET['company_id'] ?? 0),
        ];

        if (($filters['status'] ?? '') === '') {
            unset($filters['status']);
        }
        if (($filters['job_type'] ?? '') === '') {
            unset($filters['job_type']);
        }
        if (($filters['company_id'] ?? 0) < 1) {
            unset($filters['company_id']);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = QueueService::getDashboard($filters, $page, 50);

        $this->view('super-admin/queues', [
            'filters' => $filters,
            'data' => $result,
        ]);
    }

    /** POST /superadmin/queues/{id}/retry */
    public function queuesRetry(array $params): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/QueueService.php';

        $id = (int)($params['id'] ?? 0);
        if ($id < 1) {
            $this->redirectWithFlash('error', 'Job inválido.', 'superadmin/queues');
        }

        $result = QueueService::retry($id);
        $this->redirectWithFlash($result['success'] ? 'success' : 'error', $result['message'], 'superadmin/queues');
    }

    /* ========= FASE 5: WhatsApp Monitoring + Feature Flags + RBAC ========= */

    /** GET /superadmin/whatsapp-monitor */
    public function whatsappMonitor(array $params = []): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/WhatsAppMonitoringService.php';

        $companyId = (int)($_GET['company_id'] ?? 0);
        $data = WhatsAppMonitoringService::getOverview($companyId > 0 ? $companyId : null);
        $companies = $this->db()->query('SELECT id, name, slug FROM companies ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

        $this->view('super-admin/whatsapp-monitor', [
            'data' => $data,
            'companies' => $companies,
            'selectedCompanyId' => $companyId,
        ]);
    }

    /** GET /superadmin/feature-flags */
    public function featureFlags(array $params = []): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/FeatureFlagService.php';

        $companyId = max(1, (int)($_GET['company_id'] ?? 1));
        $data = FeatureFlagService::getOverview($companyId);
        $companies = $this->db()->query('SELECT id, name, slug FROM companies ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

        $this->view('super-admin/feature-flags', [
            'data' => $data,
            'companies' => $companies,
            'selectedCompanyId' => $companyId,
        ]);
    }

    /** POST /superadmin/feature-flags/toggle */
    public function featureFlagsToggle(): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/FeatureFlagService.php';
        require_once __DIR__ . '/../services/EventDispatcher.php';
        require_once __DIR__ . '/../events/FeatureFlagChangedEvent.php';

        $companyId = (int)($_POST['company_id'] ?? 0);
        $featureFlagId = (int)($_POST['feature_flag_id'] ?? 0);
        $enabled = (int)($_POST['enabled'] ?? 0) === 1;
        $reason = trim((string)($_POST['reason'] ?? ''));
        $changedBy = (int)($_SESSION['super_admin_id'] ?? 0);

        if ($companyId < 1 || $featureFlagId < 1 || $changedBy < 1) {
            $this->redirectWithFlash('error', 'Parametros invalidos para toggle de feature.', 'superadmin/feature-flags');
        }

        $result = FeatureFlagService::toggleTenantFeature($companyId, $featureFlagId, $enabled, $changedBy, $reason !== '' ? $reason : null);

        if (($result['success'] ?? false) === true) {
            EventDispatcher::dispatch(new FeatureFlagChangedEvent(
                $featureFlagId,
                $companyId,
                [
                    'enabled' => $enabled,
                    'reason' => $reason,
                ],
                $changedBy
            ));
        }

        $this->redirectWithFlash($result['success'] ? 'success' : 'error', $result['message'], 'superadmin/feature-flags?company_id=' . $companyId);
    }

    /** GET /superadmin/rbac */
    public function rbacIndex(array $params = []): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/RBACService.php';

        $matrix = RBACService::getMatrix();
        $admins = $this->db()->query("SELECT id, name, email, role FROM users WHERE role = 'root' OR role = 'owner' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('super-admin/rbac', [
            'matrix' => $matrix,
            'admins' => $admins,
        ]);
    }

    /** POST /superadmin/rbac/assign-role */
    public function rbacAssignRole(): void
    {
        SuperAdminMiddleware::enforce();

        require_once __DIR__ . '/../services/RBACService.php';

        $userId = (int)($_POST['user_id'] ?? 0);
        $roleId = (int)($_POST['role_id'] ?? 0);
        $assignedBy = (int)($_SESSION['super_admin_id'] ?? 0);

        if ($userId < 1 || $roleId < 1 || $assignedBy < 1) {
            $this->redirectWithFlash('error', 'Parametros invalidos para atribuicao de role.', 'superadmin/rbac');
        }

        $result = RBACService::assignRole($userId, $roleId, $assignedBy);
        $this->redirectWithFlash($result['success'] ? 'success' : 'error', $result['message'], 'superadmin/rbac');
    }

    /* ========= FASE 6: Observabilidade + Seguranca Multi-tenant + Eventos ========= */

    /** GET /superadmin/observability */
    public function observabilityDashboard(array $params = []): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('observability.read');

        require_once __DIR__ . '/../services/ObservabilityService.php';

        $data = ObservabilityService::dashboard();
        $this->view('super-admin/observability', ['data' => $data]);
    }

    /** POST /superadmin/observability/run-checks */
    public function observabilityRunChecks(): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('observability.run_checks');

        require_once __DIR__ . '/../services/ObservabilityService.php';

        $results = ObservabilityService::runHealthChecks();
        $critical = 0;
        foreach ($results as $item) {
            if (($item['status'] ?? '') === 'critical') {
                $critical++;
            }
        }

        $msg = $critical > 0
            ? "Health checks concluido com {$critical} componente(s) critico(s)."
            : 'Health checks concluido sem componentes criticos.';

        $this->redirectWithFlash($critical > 0 ? 'error' : 'success', $msg, 'superadmin/observability');
    }

    /** GET /superadmin/events */
    public function eventsIndex(array $params = []): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('events.read');

        require_once __DIR__ . '/../services/EventLogService.php';

        $filters = [
            'event_name' => trim((string)($_GET['event_name'] ?? '')),
            'source' => trim((string)($_GET['source'] ?? '')),
            'company_id' => (int)($_GET['company_id'] ?? 0),
        ];
        if (($filters['event_name'] ?? '') === '') {
            unset($filters['event_name']);
        }
        if (($filters['source'] ?? '') === '') {
            unset($filters['source']);
        }
        if (($filters['company_id'] ?? 0) < 1) {
            unset($filters['company_id']);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $data = EventLogService::listing($filters, $page, 60);

        $this->view('super-admin/events', [
            'filters' => $filters,
            'data' => $data,
        ]);
    }

    /** POST /superadmin/events/dispatch-test */
    public function eventsDispatchTest(): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('events.dispatch');

        require_once __DIR__ . '/../services/EventDispatcher.php';
        require_once __DIR__ . '/../events/WhatsAppDisconnectedEvent.php';

        $companyId = (int)($_POST['company_id'] ?? 1);
        $instanceId = (int)($_POST['instance_id'] ?? 1);
        $adminId = (int)($_SESSION['super_admin_id'] ?? 0);

        if ($companyId < 1 || $instanceId < 1) {
            $this->redirectWithFlash('error', 'Parâmetros inválidos para disparo de evento.', 'superadmin/events');
        }

        $company = Company::find($companyId);
        if (!$company) {
            $this->redirectWithFlash('error', 'Empresa informada não foi encontrada.', 'superadmin/events');
        }

        EventDispatcher::dispatch(new WhatsAppDisconnectedEvent(
            $instanceId,
            $companyId,
            ['reason' => 'manual_test_dispatch'],
            $adminId > 0 ? $adminId : null
        ));

        $this->redirectWithFlash('success', 'Evento de teste despachado com sucesso.', 'superadmin/events');
    }

    /* ========= FASE 7: Billing SaaS + Assinaturas + Faturas ========= */

    /** GET /superadmin/billing */
    public function billingIndex(array $params = []): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('billing.read');

        require_once __DIR__ . '/../models/Plan.php';
        require_once __DIR__ . '/../models/Subscription.php';
        require_once __DIR__ . '/../models/Invoice.php';
        require_once __DIR__ . '/../models/UsageLimit.php';

        $pdo = $this->db();
        $companies = $pdo->query('SELECT id, name, slug, active FROM companies ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
        [$companyId, $selectedCompany] = $this->parseCompanyScope($companies);

        $statusFilter = trim((string)($_GET['subscription_status'] ?? ''));
        $selectedPlanCode = trim((string)($_GET['plan_code'] ?? ''));
        $allowedStatuses = ['trialing', 'active', 'past_due', 'canceled', 'incomplete', 'paused'];
        if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = '';
        }

        $plans = Plan::all(true);
        $selectedPlan = $selectedPlanCode !== '' ? Plan::findByCode($selectedPlanCode) : null;

        $subsSql = 'SELECT s.id, s.company_id, s.plan_id, s.status, s.current_period_end, s.created_at,
                           c.name AS company_name, c.slug AS company_slug,
                           p.name AS plan_name, p.code AS plan_code, p.price_monthly, p.currency
                    FROM subscriptions s
                    INNER JOIN companies c ON c.id = s.company_id
                    INNER JOIN plans p ON p.id = s.plan_id
                    WHERE 1=1';
        $subsBind = [];

        if ($companyId > 0) {
            $subsSql .= ' AND s.company_id = ?';
            $subsBind[] = $companyId;
        }
        if ($statusFilter !== '') {
            $subsSql .= ' AND s.status = ?';
            $subsBind[] = $statusFilter;
        }

        $subsSql .= ' ORDER BY s.id DESC LIMIT 80';
        $subsStmt = $pdo->prepare($subsSql);
        $subsStmt->execute($subsBind);
        $subscriptions = $subsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $currentSubscription = null;
        $invoices = [];
        $usageLimits = [];
        if ($companyId > 0) {
            $currentSubscription = Subscription::currentByCompany($companyId);
            $invoices = Invoice::listByCompany($companyId, 40);
            $usageLimits = UsageLimit::listByCompany($companyId);
        }

        $statusRows = $pdo->query('SELECT status, COUNT(*) AS total FROM subscriptions GROUP BY status')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $statusSummary = [
            'trialing' => 0,
            'active' => 0,
            'past_due' => 0,
            'canceled' => 0,
            'incomplete' => 0,
            'paused' => 0,
        ];
        foreach ($statusRows as $row) {
            $key = (string)($row['status'] ?? '');
            if (array_key_exists($key, $statusSummary)) {
                $statusSummary[$key] = (int)($row['total'] ?? 0);
            }
        }

        $this->view('super-admin/billing', [
            'title' => 'Billing SaaS',
            'superAdminName' => $_SESSION['super_admin_name'] ?? '',
            'companies' => $companies,
            'selectedCompany' => $selectedCompany,
            'selectedCompanyId' => $companyId,
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
            'selectedPlanCode' => $selectedPlanCode,
            'subscriptions' => $subscriptions,
            'currentSubscription' => $currentSubscription,
            'invoices' => $invoices,
            'usageLimits' => $usageLimits,
            'subscriptionStatusFilter' => $statusFilter,
            'statusSummary' => $statusSummary,
            'allowedStatuses' => $allowedStatuses,
        ]);
    }

    /** POST /superadmin/billing/plans/save */
    public function billingSavePlan(): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('billing.manage');

        require_once __DIR__ . '/../models/Plan.php';

        $code = trim((string)($_POST['code'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $priceMonthly = (float)($_POST['price_monthly'] ?? 0);
        $priceYearly = (float)($_POST['price_yearly'] ?? 0);
        $currency = strtoupper(trim((string)($_POST['currency'] ?? 'BRL')));
        $isActive = !empty($_POST['is_active']) ? 1 : 0;
        $limitsJsonRaw = trim((string)($_POST['limits_json'] ?? ''));

        if ($code === '' || $name === '') {
            $this->redirectWithFlash('error', 'Codigo e nome do plano sao obrigatorios.', 'superadmin/billing?plan_code=' . urlencode($code));
        }

        if (!preg_match('/^[A-Za-z0-9._-]{2,60}$/', $code)) {
            $this->redirectWithFlash('error', 'Codigo do plano invalido.', 'superadmin/billing?plan_code=' . urlencode($code));
        }

        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            $this->redirectWithFlash('error', 'Moeda invalida.', 'superadmin/billing?plan_code=' . urlencode($code));
        }

        $limitsJson = null;
        if ($limitsJsonRaw !== '') {
            $decoded = json_decode($limitsJsonRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->redirectWithFlash('error', 'JSON de limites invalido.', 'superadmin/billing?plan_code=' . urlencode($code));
            }
            $limitsJson = $decoded;
        }

        Plan::upsertByCode([
            'code' => $code,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'price_monthly' => $priceMonthly,
            'price_yearly' => $priceYearly,
            'currency' => $currency,
            'limits_json' => $limitsJson,
            'is_active' => $isActive,
        ]);

        $this->redirectWithFlash('success', 'Plano salvo com sucesso.', 'superadmin/billing?plan_code=' . urlencode($code));
    }

    /** POST /superadmin/billing/plans/{code}/toggle */
    public function billingTogglePlanStatus(array $params = []): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('billing.manage');

        require_once __DIR__ . '/../models/Plan.php';

        $code = trim((string)($params['code'] ?? ''));
        $plan = $code !== '' ? Plan::findByCode($code) : null;

        if (!$plan) {
            $this->redirectWithFlash('error', 'Plano nao encontrado.', 'superadmin/billing');
        }

        Plan::upsertByCode([
            'code' => (string)$plan['code'],
            'name' => (string)$plan['name'],
            'description' => $plan['description'] ?? null,
            'price_monthly' => (float)($plan['price_monthly'] ?? 0),
            'price_yearly' => (float)($plan['price_yearly'] ?? 0),
            'currency' => (string)($plan['currency'] ?? 'BRL'),
            'limits_json' => $plan['limits_json'] ?? null,
            'is_active' => empty($plan['is_active']) ? 1 : 0,
        ]);

        $this->redirectWithFlash('success', 'Status do plano atualizado.', 'superadmin/billing?plan_code=' . urlencode((string)$plan['code']));
    }

    /** POST /superadmin/billing/subscriptions/create */
    public function billingCreateSubscription(): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('billing.manage');

        require_once __DIR__ . '/../models/Plan.php';
        require_once __DIR__ . '/../models/Subscription.php';
        require_once __DIR__ . '/../models/UsageLimit.php';

        $companyId = (int)($_POST['company_id'] ?? 0);
        $planId = (int)($_POST['plan_id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'active'));
        $trialDays = max(0, (int)($_POST['trial_days'] ?? 0));
        $billingMonths = max(1, min(24, (int)($_POST['billing_months'] ?? 1)));
        $adminId = (int)($_SESSION['super_admin_id'] ?? 0);

        $allowedStatuses = ['trialing', 'active', 'past_due', 'canceled', 'incomplete', 'paused'];
        if (!in_array($status, $allowedStatuses, true)) {
            $this->redirectWithFlash('error', 'Status de assinatura invalido.', 'superadmin/billing?company_id=' . $companyId);
        }

        $company = Company::find($companyId);
        if (!$company) {
            $this->redirectWithFlash('error', 'Empresa invalida para assinatura.', 'superadmin/billing');
        }

        $plan = Plan::find($planId);
        if (!$plan) {
            $this->redirectWithFlash('error', 'Plano invalido.', 'superadmin/billing?company_id=' . $companyId);
        }

        $now = new DateTimeImmutable('now');
        $periodStart = $now->format('Y-m-d H:i:s');
        $periodEnd = $now->modify('+' . $billingMonths . ' month')->format('Y-m-d H:i:s');
        $trialEndsAt = null;
        if ($trialDays > 0) {
            $trialEndsAt = $now->modify('+' . $trialDays . ' day')->format('Y-m-d H:i:s');
            if ($status === 'active') {
                $status = 'trialing';
            }
        }

        $subscriptionId = Subscription::create([
            'company_id' => $companyId,
            'plan_id' => $planId,
            'status' => $status,
            'starts_at' => $periodStart,
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'metadata_json' => [
                'source' => 'superadmin_manual',
                'billing_months' => $billingMonths,
                'trial_days' => $trialDays,
            ],
            'created_by_super_admin_id' => $adminId > 0 ? $adminId : null,
        ]);

        $limitsRaw = (string)($plan['limits_json'] ?? '');
        if ($limitsRaw !== '') {
            $limits = json_decode($limitsRaw, true);
            if (is_array($limits)) {
                foreach ($limits as $resourceKey => $resourceConfig) {
                    $resourceKey = trim((string)$resourceKey);
                    if ($resourceKey === '') {
                        continue;
                    }

                    $hardLimit = 0;
                    $softLimit = 0;
                    $resetPeriod = 'monthly';
                    $isBlocking = 1;

                    if (is_array($resourceConfig)) {
                        $hardLimit = (int)($resourceConfig['hard_limit'] ?? 0);
                        $softLimit = (int)($resourceConfig['soft_limit'] ?? 0);
                        $resetPeriod = (string)($resourceConfig['reset_period'] ?? 'monthly');
                        $isBlocking = (int)($resourceConfig['is_blocking'] ?? 1) === 1 ? 1 : 0;
                    } elseif (is_numeric($resourceConfig)) {
                        $hardLimit = (int)$resourceConfig;
                    }

                    UsageLimit::upsert([
                        'company_id' => $companyId,
                        'subscription_id' => $subscriptionId,
                        'resource_key' => $resourceKey,
                        'hard_limit' => max(0, $hardLimit),
                        'soft_limit' => max(0, $softLimit),
                        'current_usage' => 0,
                        'reset_period' => in_array($resetPeriod, ['daily', 'weekly', 'monthly', 'never'], true) ? $resetPeriod : 'monthly',
                        'resets_at' => $periodEnd,
                        'is_blocking' => $isBlocking,
                    ]);
                }
            }
        }

        $this->redirectWithFlash('success', 'Assinatura criada com sucesso (ID ' . $subscriptionId . ').', 'superadmin/billing?company_id=' . $companyId);
    }

    /** POST /superadmin/billing/invoices/create-draft */
    public function billingCreateInvoiceDraft(): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('billing.manage');

        require_once __DIR__ . '/../models/Invoice.php';
        require_once __DIR__ . '/../models/Subscription.php';

        $companyId = (int)($_POST['company_id'] ?? 0);
        $amountTotal = (float)($_POST['amount_total'] ?? 0);
        $currency = strtoupper(trim((string)($_POST['currency'] ?? 'BRL')));
        $dueDateInput = trim((string)($_POST['due_date'] ?? ''));

        $company = Company::find($companyId);
        if (!$company) {
            $this->redirectWithFlash('error', 'Empresa invalida para faturamento.', 'superadmin/billing');
        }

        if ($amountTotal <= 0) {
            $this->redirectWithFlash('error', 'Valor total da fatura deve ser maior que zero.', 'superadmin/billing?company_id=' . $companyId);
        }

        $dueDate = null;
        if ($dueDateInput !== '') {
            $ts = strtotime($dueDateInput);
            if ($ts !== false) {
                $dueDate = date('Y-m-d H:i:s', $ts);
            }
        }
        if ($dueDate === null) {
            $dueDate = date('Y-m-d H:i:s', strtotime('+7 days'));
        }

        $currentSubscription = Subscription::currentByCompany($companyId);

        $invoiceId = Invoice::createDraft([
            'company_id' => $companyId,
            'subscription_id' => $currentSubscription['id'] ?? null,
            'status' => 'open',
            'currency' => $currency !== '' ? $currency : 'BRL',
            'amount_subtotal' => $amountTotal,
            'amount_tax' => 0,
            'amount_discount' => 0,
            'amount_total' => $amountTotal,
            'due_date' => $dueDate,
            'payload_json' => [
                'source' => 'superadmin_manual',
                'created_at' => date('c'),
            ],
        ]);

        $this->redirectWithFlash('success', 'Fatura criada com sucesso (ID ' . $invoiceId . ').', 'superadmin/billing?company_id=' . $companyId);
    }

    /** POST /superadmin/billing/subscriptions/{id}/status */
    public function billingUpdateSubscriptionStatus(array $params = []): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('billing.manage');

        require_once __DIR__ . '/../models/Subscription.php';

        $subscriptionId = (int)($params['id'] ?? 0);
        $newStatus = trim((string)($_POST['status'] ?? ''));
        $allowedStatuses = ['trialing', 'active', 'past_due', 'canceled', 'incomplete', 'paused'];

        if ($subscriptionId < 1 || !in_array($newStatus, $allowedStatuses, true)) {
            $this->redirectWithFlash('error', 'Dados invalidos para atualizar assinatura.', 'superadmin/billing');
        }

        $stmt = $this->db()->prepare('SELECT id, company_id, status FROM subscriptions WHERE id = ? LIMIT 1');
        $stmt->execute([$subscriptionId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subscription) {
            $this->redirectWithFlash('error', 'Assinatura nao encontrada.', 'superadmin/billing');
        }

        if ((string)($subscription['status'] ?? '') === $newStatus) {
            $this->redirectWithFlash('success', 'Status ja estava atualizado.', 'superadmin/billing?company_id=' . (int)$subscription['company_id']);
        }

        $canceledAt = $newStatus === 'canceled' ? date('Y-m-d H:i:s') : null;
        Subscription::updateStatus($subscriptionId, $newStatus, $canceledAt);

        $this->redirectWithFlash('success', 'Status da assinatura atualizado para ' . $newStatus . '.', 'superadmin/billing?company_id=' . (int)$subscription['company_id']);
    }

    /** POST /superadmin/billing/invoices/{id}/mark-paid */
    public function billingMarkInvoicePaid(array $params = []): void
    {
        SuperAdminMiddleware::enforce();
        PermissionMiddleware::enforce('billing.manage');

        require_once __DIR__ . '/../models/Invoice.php';

        $invoiceId = (int)($params['id'] ?? 0);
        if ($invoiceId < 1) {
            $this->redirectWithFlash('error', 'Fatura invalida para confirmacao.', 'superadmin/billing');
        }

        $stmt = $this->db()->prepare('SELECT id, company_id, status FROM invoices WHERE id = ? LIMIT 1');
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            $this->redirectWithFlash('error', 'Fatura nao encontrada.', 'superadmin/billing');
        }

        if ((string)($invoice['status'] ?? '') === 'paid') {
            $this->redirectWithFlash('success', 'Fatura ja estava marcada como paga.', 'superadmin/billing?company_id=' . (int)$invoice['company_id']);
        }

        Invoice::markPaid($invoiceId);

        $this->redirectWithFlash('success', 'Fatura marcada como paga com sucesso.', 'superadmin/billing?company_id=' . (int)$invoice['company_id']);
    }
}

