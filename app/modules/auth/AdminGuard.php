<?php

declare(strict_types=1);

class AdminGuard
{
    /**
     * Valida acesso admin para um slug e retorna [user, company].
     * Em caso de falha, faz redirect ou responde erro e encerra.
     *
     * @return array{0: array, 1: array}
     */
    public static function requireCompanyAccess(string $slug, bool $ensureContext = true): array
    {
        Auth::start();

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $company = Company::findBySlug($slug);
        if (!$company || empty($company['id'])) {
            http_response_code(404);
            echo 'Empresa invalida';
            exit;
        }

        $isRoot = ($user['role'] === 'root');
        if (!$isRoot && (int)$user['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        if ($ensureContext) {
            Auth::setActiveCompany((int)$company['id'], (string)($company['slug'] ?? $slug));
        }

        return [$user, $company];
    }
}
