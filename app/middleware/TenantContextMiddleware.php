<?php

declare(strict_types=1);

/**
 * Resolve contexto de tenant com base na URL/subdominio e aplica no contexto atual.
 *
 * Regras:
 * - /admin/{slug}/...  => slug obrigatorio e valido
 * - /api/{slug}/...    => slug obrigatorio e valido
 * - /{slug}/...        => slug publico quando o primeiro segmento nao e reservado
 * - subdominio mobile  => usa MOBILE_SLUG
 */
class TenantContextMiddleware
{
    /** @var string[] */
    private const RESERVED_PUBLIC_SEGMENTS = [
        'admin',
        'superadmin',
        'api',
        'webhook',
        'vendas',
        'robots',
        'sitemap',
        'img',
        'push',
        'cross-sell',
        'login',
        'health-check.php',
    ];

    /**
     * @return array{company_id:?int,slug:?string,required:bool,source:string}
     */
    public static function applyFromRequest(string $requestUri): array
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static function ($p): bool {
            return $p !== '';
        }));

        $slug = null;
        $required = false;
        $source = 'none';

        if (defined('IS_MOBILE_SUBDOMAIN') && IS_MOBILE_SUBDOMAIN) {
            $mobileSlug = trim((string)($_SERVER['MOBILE_SLUG'] ?? ''));
            if ($mobileSlug !== '') {
                $slug = $mobileSlug;
                $required = true;
                $source = 'mobile_subdomain';
            }
        }

        if ($slug === null && isset($segments[0]) && strtolower($segments[0]) === 'admin') {
            $slug = isset($segments[1]) ? trim((string)$segments[1]) : '';
            $required = true;
            $source = 'admin_path';
        }

        if ($slug === null && isset($segments[0]) && strtolower($segments[0]) === 'api') {
            $slug = isset($segments[1]) ? trim((string)$segments[1]) : '';
            $required = true;
            $source = 'api_path';
        }

        if ($slug === null && isset($segments[0])) {
            $first = strtolower((string)$segments[0]);
            if (!in_array($first, self::RESERVED_PUBLIC_SEGMENTS, true) && preg_match('/^[A-Za-z0-9_-]+$/', (string)$segments[0])) {
                $slug = (string)$segments[0];
                $required = true;
                $source = 'public_path';
            }
        }

        if ($slug === null) {
            return [
                'company_id' => null,
                'slug' => null,
                'required' => false,
                'source' => $source,
            ];
        }

        $slug = trim(rawurldecode($slug));
        if ($slug === '') {
            if ($required) {
                self::abortNotFound();
            }

            return [
                'company_id' => null,
                'slug' => null,
                'required' => $required,
                'source' => $source,
            ];
        }

        $company = Company::findBySlug($slug);
        if (!$company || empty($company['id'])) {
            if ($required) {
                self::abortNotFound();
            }

            return [
                'company_id' => null,
                'slug' => $slug,
                'required' => $required,
                'source' => $source,
            ];
        }

        $companyId = (int)$company['id'];
        $resolvedSlug = (string)($company['slug'] ?? $slug);
        Auth::setActiveCompany($companyId, $resolvedSlug);

        return [
            'company_id' => $companyId,
            'slug' => $resolvedSlug,
            'required' => $required,
            'source' => $source,
        ];
    }

    private static function abortNotFound(): void
    {
        http_response_code(404);
        echo '404';
        exit;
    }
}
