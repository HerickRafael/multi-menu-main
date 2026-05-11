<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Validação de segredo compartilhado em webhooks e endpoints internos de fila.
 */
final class WebhookGate
{
    private static function isProduction(): bool
    {
        return (string)($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '') === 'production';
    }

    /**
     * Webhook externo: aceita X-Webhook-Secret ou Authorization: Bearer.
     *
     * @param non-empty-string $envVar
     */
    public static function requireInboundWebhookSecret(string $envVar, bool $jsonResponse = true): void
    {
        $secret = trim((string)($_ENV[$envVar] ?? getenv($envVar) ?: ''));

        if ($secret === '') {
            if (self::isProduction()) {
                self::deny(503, 'Webhook secret not configured: ' . $envVar, $jsonResponse);
            }

            return;
        }

        $provided = trim((string)($_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? ''));
        $authHeader = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($provided === '' && $authHeader !== '') {
            if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
                $provided = $m[1];
            }
        }

        if ($provided === '' || !hash_equals($secret, $provided)) {
            self::deny(401, 'Unauthorized', $jsonResponse);
        }
    }

    /**
     * Endpoint interno (cron/worker): apenas header X-Webhook-Internal.
     *
     * @param non-empty-string $envVar
     * @param non-empty-string $serverHeaderKey e.g. HTTP_X_WEBHOOK_INTERNAL
     */
    public static function requireHeaderSecret(string $envVar, string $serverHeaderKey, bool $jsonResponse = true): void
    {
        $secret = trim((string)($_ENV[$envVar] ?? getenv($envVar) ?: ''));

        if ($secret === '') {
            if (self::isProduction()) {
                self::deny(503, 'Webhook secret not configured: ' . $envVar, $jsonResponse);
            }

            return;
        }

        $sent = trim((string)($_SERVER[$serverHeaderKey] ?? ''));
        if ($sent === '' || !hash_equals($secret, $sent)) {
            self::deny(401, 'Unauthorized', $jsonResponse);
        }
    }

    private static function deny(int $code, string $message, bool $jsonResponse): void
    {
        http_response_code($code);
        if ($jsonResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
        } else {
            header('Content-Type: text/plain; charset=utf-8');
            echo $message;
        }
        exit;
    }
}
