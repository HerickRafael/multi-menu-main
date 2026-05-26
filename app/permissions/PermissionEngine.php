<?php

declare(strict_types=1);

class PermissionEngine
{
    private const ROLE_PERMISSIONS = [
        'root' => ['*'],
        'owner' => [
            'dashboard.view',
            'orders.*',
            'products.*',
            'categories.*',
            'customers.*',
            'coupons.*',
            'analytics.view',
            'financial.view',
            'settings.*',
        ],
        'manager' => [
            'dashboard.view',
            'orders.view',
            'orders.update',
            'orders.create',
            'products.view',
            'products.manage',
            'categories.view',
            'categories.manage',
            'customers.view',
            'customers.manage',
            'coupons.view',
            'analytics.view',
        ],
        'staff' => [
            'dashboard.view',
            'orders.view',
            'orders.update',
            'products.view',
            'categories.view',
            'customers.view',
            'coupons.view',
        ],
        'customer' => [
            'profile.view',
            'orders.view',
        ],
    ];

    public static function allows(array $user, string $permissionKey, ?int $companyId = null): bool
    {
        $role = (string)($user['role'] ?? '');
        if ($role === '') {
            return false;
        }

        if ($role !== 'root' && $companyId !== null && (int)($user['company_id'] ?? 0) !== $companyId) {
            return false;
        }

        $allowed = self::ROLE_PERMISSIONS[$role] ?? [];

        if (in_array('*', $allowed, true)) {
            return true;
        }

        foreach ($allowed as $pattern) {
            if (self::matches($pattern, $permissionKey)) {
                return true;
            }
        }

        return false;
    }

    private static function matches(string $pattern, string $permissionKey): bool
    {
        if ($pattern === $permissionKey) {
            return true;
        }

        if (str_ends_with($pattern, '.*')) {
            $prefix = substr($pattern, 0, -2);
            return str_starts_with($permissionKey, $prefix . '.');
        }

        if (str_ends_with($pattern, '.manage')) {
            $prefix = substr($pattern, 0, -7);
            return $permissionKey === $pattern
                || $permissionKey === $prefix . '.view'
                || $permissionKey === $prefix . '.create'
                || $permissionKey === $prefix . '.update'
                || $permissionKey === $prefix . '.delete';
        }

        return false;
    }
}
