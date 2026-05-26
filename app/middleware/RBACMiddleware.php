<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Permission.php';

class RBACMiddleware
{
    public static function enforce(string $permissionKey): void
    {
        Auth::start();
        Auth::requireSuperAdmin(true);

        if (!Auth::superAdminHasPermission($permissionKey)) {
            http_response_code(403);
            exit('Acesso negado.');
        }
    }
}
