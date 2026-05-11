<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Permission
{
    public static function all(): array
    {
        $st = db()->query('SELECT * FROM permissions ORDER BY module ASC, permission_key ASC');
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function userHasPermission(int $userId, string $permissionKey): bool
    {
        $sql = 'SELECT 1
                FROM super_admin_roles sar
                INNER JOIN role_permissions rp ON rp.role_id = sar.role_id AND rp.allowed = 1
                INNER JOIN permissions p ON p.id = rp.permission_id
                WHERE sar.user_id = ? AND p.permission_key = ?
                LIMIT 1';
        $st = db()->prepare($sql);
        $st->execute([$userId, $permissionKey]);
        return (bool)$st->fetchColumn();
    }
}
