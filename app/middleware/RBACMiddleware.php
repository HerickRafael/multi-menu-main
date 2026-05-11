<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Permission.php';

class RBACMiddleware
{
    public static function enforce(string $permissionKey): void
    {
        $userId = (int)($_SESSION['super_admin_id'] ?? 0);
        if ($userId < 1) {
            http_response_code(401);
            exit('Nao autenticado.');
        }

        // Root sempre tem acesso total por compatibilidade operacional.
        $role = (string)($_SESSION['super_admin_role'] ?? 'root');
        if ($role === 'root') {
            return;
        }

        if (!Permission::userHasPermission($userId, $permissionKey)) {
            http_response_code(403);
            exit('Acesso negado.');
        }
    }
}
