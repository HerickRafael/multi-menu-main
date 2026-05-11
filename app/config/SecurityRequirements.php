<?php

declare(strict_types=1);

/**
 * Centraliza checagens de ambiente e segredos para produção.
 */
final class SecurityRequirements
{
    private const WEAK_JWT_SECRETS = [
        '',
        'multi-menu-api-secret-change-in-production',
    ];

    private const WEAK_APP_KEYS = [
        '',
        'default_secret_key',
    ];

    public static function isProduction(): bool
    {
        return (string)($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '') === 'production';
    }

    /**
     * APP_DEBUG explícito; se ausente, assume debug fora de produção.
     */
    public static function appDebugEnabled(): bool
    {
        $raw = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
        if ($raw === false || $raw === null || $raw === '') {
            return !self::isProduction();
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    public static function configurePhpErrorReporting(): void
    {
        if (self::appDebugEnabled()) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        }
    }

    /**
     * Falha rápida em produção se segredos críticos forem fracos ou ausentes.
     */
    public static function assertProductionSecrets(): void
    {
        if (!self::isProduction()) {
            return;
        }

        $jwt = trim((string)($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: ''));
        $appKey = trim((string)($_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: ''));

        $errors = [];
        if (in_array($jwt, self::WEAK_JWT_SECRETS, true) || strlen($jwt) < 32) {
            $errors[] = 'JWT_SECRET must be set to a strong value (at least 32 characters) in production.';
        }
        if (in_array($appKey, self::WEAK_APP_KEYS, true) || strlen($appKey) < 32) {
            $errors[] = 'APP_KEY must be set to a strong value (at least 32 characters) in production.';
        }

        if ($errors === []) {
            return;
        }

        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo implode("\n", $errors);
        exit;
    }

    public static function resolveJwtSecret(): string
    {
        $jwt = trim((string)($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: ''));
        if (self::isProduction()) {
            return $jwt;
        }
        if ($jwt === '') {
            return 'multi-menu-api-secret-change-in-production';
        }

        return $jwt;
    }

    public static function resolveAppKey(): string
    {
        $key = trim((string)($_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: ''));
        if ($key !== '') {
            return $key;
        }

        return 'default_secret_key';
    }

    public static function contentSecurityPolicy(): string
    {
        $custom = trim((string)($_ENV['CONTENT_SECURITY_POLICY'] ?? getenv('CONTENT_SECURITY_POLICY') ?: ''));
        if ($custom !== '') {
            return $custom;
        }

        return implode(' ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            // Super Admin SPA: Tailwind CDN + módulos ESM (esm.sh)
            "script-src 'self' 'unsafe-inline' https://esm.sh https://cdn.tailwindcss.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https:",
        ]);
    }
}
