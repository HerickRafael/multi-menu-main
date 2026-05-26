<?php

declare(strict_types=1);
class Auth
{
    public static function start(): void
    {
        $cfg = config();
        date_default_timezone_set($cfg['timezone'] ?? 'America/Sao_Paulo');
        
        // Configurar o nome da sessão ANTES de iniciar (se ainda não foi iniciada)
        if (session_status() === PHP_SESSION_NONE) {
            session_name($cfg['session_name'] ?? 'mm_session');
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = [
          'id'         => $user['id'],
          'role'       => $user['role'],
          'company_id' => $user['company_id'] ?? null,
          'name'       => $user['name'] ?? '',
          'email'      => $user['email'] ?? '',
        ];
    }

    public static function loginCustomer(array $customer): void
    {
        $_SESSION['customer'] = [
            'id' => (int)($customer['id'] ?? 0),
            'name' => (string)($customer['name'] ?? ''),
            'whatsapp' => (string)($customer['whatsapp'] ?? ''),
            'e164' => (string)($customer['e164'] ?? $customer['whatsapp_e164'] ?? ''),
            'company_id' => isset($customer['company_id']) ? (int)$customer['company_id'] : null,
            'company_slug' => (string)($customer['company_slug'] ?? ''),
            'login_at' => $customer['login_at'] ?? date('Y-m-d H:i:s'),
        ];

        $_SESSION['customer_id'] = (int)($customer['id'] ?? 0);
        $_SESSION['customer_phone'] = (string)($customer['whatsapp'] ?? '');
        $_SESSION['customer_name'] = (string)($customer['name'] ?? '');

        if (isset($customer['company_id'])) {
            $_SESSION['company_id'] = (int)$customer['company_id'];
        }
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function currentRole(): ?string
    {
        $user = self::user();

        return $user ? (string)($user['role'] ?? '') : null;
    }

    public static function isRoot(): bool
    {
        return self::currentRole() === 'root';
    }

    public static function hasCompanyAccess(int $companyId, ?array $user = null): bool
    {
        $currentUser = $user ?? self::user();

        if (!$currentUser) {
            return false;
        }

        if (($currentUser['role'] ?? '') === 'root') {
            return true;
        }

        return (int)($currentUser['company_id'] ?? 0) === $companyId;
    }

    public static function loginSuperAdmin(array $admin): void
    {
        $adminId = (int)($admin['id'] ?? 0);
        $role = (string)($admin['role'] ?? '');

        if ($role === '' && $adminId > 0) {
            $role = self::resolveSuperAdminRole($adminId);
        }

        if ($role === '') {
            $role = 'root';
        }

        $_SESSION['super_admin_id'] = $adminId;
        $_SESSION['super_admin_name'] = (string)($admin['name'] ?? '');
        $_SESSION['super_admin_role'] = $role;
    }

    public static function customer(?string $slug = null): ?array
    {
        $customer = $_SESSION['customer'] ?? null;

        if (!$customer) {
            return null;
        }

        if ($slug !== null && !empty($customer['company_slug']) && $customer['company_slug'] !== $slug) {
            return null;
        }

        return $customer;
    }

    public static function checkCustomer(?string $slug = null): bool
    {
        return self::customer($slug) !== null;
    }

    public static function superAdmin(): ?array
    {
        $id = (int)($_SESSION['super_admin_id'] ?? 0);
        if ($id < 1) {
            return null;
        }

        return [
            'id' => $id,
            'name' => (string)($_SESSION['super_admin_name'] ?? ''),
            'role' => (string)($_SESSION['super_admin_role'] ?? 'root'),
        ];
    }

    public static function superAdminId(): ?int
    {
        $admin = self::superAdmin();
        return $admin ? (int)$admin['id'] : null;
    }

    public static function superAdminRole(): string
    {
        $admin = self::superAdmin();
        return (string)($admin['role'] ?? 'root');
    }

    public static function checkSuperAdmin(): bool
    {
        return self::superAdmin() !== null;
    }

    public static function requireSuperAdmin(bool $json = false): void
    {
        if (self::checkSuperAdmin()) {
            return;
        }

        if ($json) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Nao autenticado.']);
            exit;
        }

        $_SESSION['superadmin_flash'] = [
            'type' => 'error',
            'message' => 'Faça login para continuar.',
        ];
        header('Location: ' . base_url('superadmin/login'));
        exit;
    }

    public static function superAdminHasPermission(string $permissionKey): bool
    {
        $admin = self::superAdmin();
        if (!$admin) {
            return false;
        }

        $role = (string)($admin['role'] ?? 'root');
        if ($role === 'root') {
            return true;
        }

        if (!class_exists('Permission')) {
            require_once __DIR__ . '/../models/Permission.php';
        }

        return Permission::userHasPermission((int)$admin['id'], $permissionKey);
    }

    public static function hasPermission(string $permissionKey, ?array $user = null, ?int $companyId = null): bool
    {
        if ($user === null && self::checkSuperAdmin()) {
            return self::superAdminHasPermission($permissionKey);
        }

        $currentUser = $user ?? self::user();
        if (!$currentUser) {
            return false;
        }

        if (!class_exists('PermissionEngine')) {
            require_once __DIR__ . '/../permissions/PermissionEngine.php';
        }

        return PermissionEngine::allows($currentUser, $permissionKey, $companyId ?? self::activeCompanyId());
    }

    public static function requirePermission(string $permissionKey, ?array $user = null, ?int $companyId = null, bool $json = false): void
    {
        if (self::hasPermission($permissionKey, $user, $companyId)) {
            return;
        }

        if ($json) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Acesso negado.', 'permission' => $permissionKey]);
            exit;
        }

        http_response_code(403);
        echo 'Acesso negado';
        exit;
    }

    public static function requireCustomer(?string $slug = null): void
    {
        if (!self::checkCustomer($slug)) {
            header('Location: /login');
            exit;
        }
    }

    public static function logoutCustomer(): void
    {
        unset(
            $_SESSION['customer'],
            $_SESSION['customer_id'],
            $_SESSION['customer_phone'],
            $_SESSION['customer_name'],
            $_SESSION['company_id'],
            $_SESSION['couponCode'],
            $_SESSION['couponDiscount']
        );
    }

    public static function logoutSuperAdmin(): void
    {
        unset($_SESSION['super_admin_id'], $_SESSION['super_admin_name'], $_SESSION['super_admin_role']);
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /** Admin logado? */
    public static function checkAdmin(): bool
    {
        $u = self::user();

        return $u && in_array($u['role'], ['root','owner','staff'], true);
    }

    /** Exige admin logado (redireciona pro login) */
    public static function requireAdmin(): void
    {
        if (!self::checkAdmin()) {
            header('Location: ' . base_url('admin/login'));
            exit;
        }
    }

    /** company_id padrão do usuário (pode ser null para root) */
    public static function companyId(): ?int
    {
        $u = self::user();

        return $u['company_id'] ?? null;
    }

    /* ========= Contexto de Empresa Ativa (para root trocar de empresa) ========= */

    /** Define o contexto de empresa ativa (também útil para owner/staff) */
    public static function setActiveCompany(int $companyId, ?string $slug = null): void
    {
        $_SESSION['active_company_id'] = $companyId;

        if ($slug !== null) {
            $_SESSION['active_company_slug'] = $slug;
        }
    }

    /** company_id efetivo usado pelo painel admin (prioriza contexto ativo) */
    public static function activeCompanyId(): ?int
    {
        if (isset($_SESSION['active_company_id'])) {
            return (int)$_SESSION['active_company_id'];
        }

        return self::companyId(); // fallback: company do usuário
    }

    /** slug efetivo do contexto ativo (se disponível) */
    public static function activeCompanySlug(): ?string
    {
        if (!empty($_SESSION['active_company_slug'])) {
            return (string)$_SESSION['active_company_slug'];
        }

        return null; // opcional: você pode buscar pelo Company::findById(self::activeCompanyId())
    }

    /** Limpa o contexto ativo (ex.: no logout ou troca de empresa) */
    public static function clearActiveCompany(): void
    {
        unset($_SESSION['active_company_id'], $_SESSION['active_company_slug']);
    }

    private static function resolveSuperAdminRole(int $adminId): string
    {
        if (!class_exists('Role')) {
            require_once __DIR__ . '/../models/Role.php';
        }

        $roles = Role::userRoles($adminId);

        if (empty($roles)) {
            return 'root';
        }

        $priority = ['root', 'owner', 'manager', 'staff', 'customer'];
        $slugs = array_map(static function (array $role): string {
            return (string)($role['slug'] ?? '');
        }, $roles);

        foreach ($priority as $slug) {
            if (in_array($slug, $slugs, true)) {
                return $slug;
            }
        }

        return $slugs[0] !== '' ? $slugs[0] : 'root';
    }
}
