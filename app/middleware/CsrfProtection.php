<?php

namespace App\Middleware;

/**
 * CSRF Protection Middleware
 *
 * Token único por sessão (padrão Laravel/Symfony).
 * O token é gerado uma vez por sessão e reutilizado em todas as requisições.
 * Não valida IP/UA — o cookie de sessão (HttpOnly + SameSite=Lax) é a barreira principal.
 *
 * @link https://owasp.org/www-community/attacks/csrf
 */
class CsrfProtection
{
    private const TOKEN_LENGTH = 32;
    private const TOKEN_FIELD  = 'csrf_token';
    private const SESSION_KEY  = '_csrf_token';
    private const META_TAG_NAME = 'csrf-token';

    /**
     * Garante que a sessão está ativa.
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (class_exists('SessionManager', false)) {
            \SessionManager::start();

            return;
        }
        @session_start();
    }

    /**
     * Retorna o token CSRF da sessão, criando-o se necessário.
     * Sempre retorna o MESMO token para a sessão atual.
     *
     * @param bool $singleUse ignorado — mantido para compatibilidade de assinatura
     */
    public static function generateToken(bool $singleUse = true): string
    {
        self::init();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Valida um token CSRF contra o token da sessão.
     *
     * @param string|null $token       Token recebido (POST/header)
     * @param bool        $checkIp     ignorado — mantido para compatibilidade
     * @param bool        $checkUserAgent ignorado — mantido para compatibilidade
     */
    public static function validateToken(
        ?string $token,
        bool $checkIp = false,
        bool $checkUserAgent = false
    ): bool {
        self::init();

        if (empty($token)) {
            return false;
        }

        $stored = $_SESSION[self::SESSION_KEY] ?? null;

        if (empty($stored)) {
            return false;
        }

        return hash_equals($stored, $token);
    }

    /**
     * Valida o token do request atual (POST/PUT/DELETE/PATCH).
     */
    public static function validate(bool $dieOnFailure = true): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return true;
        }

        $token = $_POST[self::TOKEN_FIELD] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        $isValid = self::validateToken($token);

        if (!$isValid && $dieOnFailure) {
            if (self::expectsJson()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'CSRF token validation failed',
                    'code'  => 'CSRF_TOKEN_INVALID',
                ]);
                exit;
            }

            // Requisição de browser: destruir sessão e redirecionar silenciosamente para login
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }

            // Detecta slug para redirecionar ao login correto
            // Ex.: /admin/wollburger/products -> /admin/wollburger/login
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            if (preg_match('#^/admin/([^/?]+)#', $uri, $m)) {
                $loginUrl = '/admin/' . $m[1] . '/login?msg=sessao_expirada';
            } else {
                $loginUrl = '/login?msg=sessao_expirada';
            }

            header('Location: ' . $loginUrl, true, 302);
            exit;
        }

        return $isValid;
    }

    /**
     * Gera meta tag CSRF para uso em JS/AJAX.
     */
    public static function metaTag(): string
    {
        $token = self::generateToken();
        return sprintf(
            '<meta name="%s" content="%s">',
            htmlspecialchars(self::META_TAG_NAME),
            htmlspecialchars($token)
        );
    }

    /**
     * Gera campo hidden CSRF para formulários.
     */
    public static function field(): string
    {
        $token = self::generateToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars(self::TOKEN_FIELD),
            htmlspecialchars($token)
        );
    }

    /**
     * Alias de generateToken() — mantido para compatibilidade.
     */
    public static function getToken(bool $singleUse = false): string
    {
        return self::generateToken();
    }

    /**
     * Regenera o token da sessão (usar após login/logout).
     */
    public static function regenerateToken(): void
    {
        self::init();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    /**
     * Limpa o token da sessão.
     */
    public static function clearTokens(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Compatibilidade: getStats() vazio.
     */
    public static function getStats(): array
    {
        return ['total' => 1, 'valid' => 1, 'expired' => 0];
    }

    private static function expectsJson(): bool
    {
        $accept      = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }
}

