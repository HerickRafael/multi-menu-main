<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * Protege rotas /superadmin (exceto login).
 */
class SuperAdminMiddleware
{
    private static bool $sessionRegeneratedThisRequest = false;

    public static function enforce(): void
    {
        SessionManager::start();

        if (!self::$sessionRegeneratedThisRequest) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            self::$sessionRegeneratedThisRequest = true;
        }

        $id = isset($_SESSION['super_admin_id']) ? (int) $_SESSION['super_admin_id'] : 0;

        if ($id < 1) {
            $_SESSION['superadmin_flash'] = [
                'type' => 'error',
                'message' => 'Faça login para continuar.',
            ];
            header('Location: ' . base_url('superadmin/login'));
            exit;
        }

        require_once __DIR__ . '/../models/SuperAdmin.php';

        $admin = SuperAdmin::findActiveById($id);

        if (!$admin) {
            unset($_SESSION['super_admin_id'], $_SESSION['super_admin_name']);
            $_SESSION['superadmin_flash'] = [
                'type' => 'error',
                'message' => 'Sessão inválida ou conta desativada.',
            ];
            header('Location: ' . base_url('superadmin/login'));
            exit;
        }

        $_SESSION['super_admin_name'] = $admin['name'];
    }

    public static function csrfField(): string
    {
        return function_exists('csrf_field') ? csrf_field() : '';
    }
}
