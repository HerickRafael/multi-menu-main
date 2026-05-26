<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

class PermissionMiddleware
{
    public static function enforce(string $permissionKey, bool $json = false): void
    {
        Auth::start();
        Auth::requireSuperAdmin($json);

        if (Auth::superAdminHasPermission($permissionKey)) {
            return;
        }

        if ($json) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado.',
                'permission' => $permissionKey,
            ]);
            exit;
        }

        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }
}
