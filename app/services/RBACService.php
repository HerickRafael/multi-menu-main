<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';

class RBACService
{
    public static function getMatrix(): array
    {
        return [
            'roles' => Role::allWithPermissions(),
            'permissions' => Permission::all(),
        ];
    }

    public static function assignRole(int $userId, int $roleId, int $assignedBy): array
    {
        $ok = Role::assignToUser($userId, $roleId, $assignedBy);
        if (!$ok) {
            return ['success' => false, 'message' => 'Falha ao atribuir role.'];
        }

        return ['success' => true, 'message' => 'Role atribuida com sucesso.'];
    }

    public static function userPermissions(int $userId): array
    {
        $roles = Role::userRoles($userId);
        $perms = [];

        foreach (Permission::all() as $permission) {
            $key = (string)$permission['permission_key'];
            if (Permission::userHasPermission($userId, $key)) {
                $perms[] = $key;
            }
        }

        return [
            'roles' => $roles,
            'permissions' => $perms,
        ];
    }
}
