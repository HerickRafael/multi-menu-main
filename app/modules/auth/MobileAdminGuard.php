<?php

declare(strict_types=1);

class MobileAdminGuard
{
    /**
     * Valida acesso admin no contexto mobile.
     *
     * @return array{0: array, 1: array}
     */
    public static function requireCompanyAccess(?string $permissionKey = null): array
    {
        Auth::start();

        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

        if (!Auth::checkAdmin()) {
            header('Location: /login');
            exit;
        }

        $company = Company::findBySlug($slug);
        if (!$company || empty($company['id'])) {
            http_response_code(404);
            echo 'Empresa invalida';
            exit;
        }

        $user = Auth::user();
        if (!Auth::hasCompanyAccess((int)$company['id'], $user)) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        Auth::setActiveCompany((int)$company['id'], (string)($company['slug'] ?? $slug));

        if ($permissionKey !== null) {
            Auth::requirePermission($permissionKey, $user, (int)$company['id']);
        }

        return [$user, $company];
    }
}
