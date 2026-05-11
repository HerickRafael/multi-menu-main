<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Role
{
    public static function allWithPermissions(): array
    {
        $sql = 'SELECT r.id AS role_id, r.slug, r.name, r.description,
                       p.id AS permission_id, p.permission_key, p.module, p.action,
                       rp.allowed
                FROM roles r
                LEFT JOIN role_permissions rp ON rp.role_id = r.id
                LEFT JOIN permissions p ON p.id = rp.permission_id
                ORDER BY r.name ASC, p.permission_key ASC';
        $rows = db()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $rid = (int)$row['role_id'];
            if (!isset($map[$rid])) {
                $map[$rid] = [
                    'id' => $rid,
                    'slug' => $row['slug'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'permissions' => [],
                ];
            }
            if (!empty($row['permission_id'])) {
                $map[$rid]['permissions'][] = [
                    'id' => (int)$row['permission_id'],
                    'key' => $row['permission_key'],
                    'module' => $row['module'],
                    'action' => $row['action'],
                    'allowed' => (int)$row['allowed'] === 1,
                ];
            }
        }

        return array_values($map);
    }

    public static function assignToUser(int $userId, int $roleId, ?int $assignedBy = null): bool
    {
        $sql = 'INSERT INTO super_admin_roles (user_id, role_id, assigned_by)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE assigned_by = VALUES(assigned_by)';
        $st = db()->prepare($sql);
        return $st->execute([$userId, $roleId, $assignedBy]);
    }

    public static function userRoles(int $userId): array
    {
        $sql = 'SELECT r.*
                FROM super_admin_roles sar
                INNER JOIN roles r ON r.id = sar.role_id
                WHERE sar.user_id = ?
                ORDER BY r.name ASC';
        $st = db()->prepare($sql);
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
