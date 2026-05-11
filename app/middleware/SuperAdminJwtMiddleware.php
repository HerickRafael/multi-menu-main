<?php

declare(strict_types=1);

/**
 * JWT Middleware for Super Admin API
 *
 * Validates Bearer tokens on /api/superadmin/* routes.
 * Uses HMAC-SHA256 (HS256) — symmetric, no dependency on external library.
 */
class SuperAdminJwtMiddleware
{
    private const ALGORITHM = 'sha256';
    private const TOKEN_TTL  = 86400; // 24h
    private const BLACKLIST_DIR = __DIR__ . '/../../storage/security/jwt_blacklist';

    // ─── Token Generation ────────────────────────────────────────────────────

    public static function generateToken(array $payload): string
    {
        $secret  = self::getSecret();
        $header  = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['jti'] = self::generateJti();
        $payload['iat'] = time();
        $payload['exp'] = time() + self::TOKEN_TTL;
        $body = self::base64UrlEncode(json_encode($payload));
        $sig  = self::base64UrlEncode(hash_hmac(self::ALGORITHM, "$header.$body", $secret, true));
        return "$header.$body.$sig";
    }

    // ─── Token Validation ────────────────────────────────────────────────────

    public static function validateToken(string $token): ?array
    {
        if (self::isTokenRevoked($token)) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $signature] = $parts;
        $secret   = self::getSecret();
        $expected = self::base64UrlEncode(hash_hmac(self::ALGORITHM, "$header.$body", $secret, true));

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($body), true);

        if (!is_array($payload)) {
            return null;
        }

        // Check expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Revoke a token until its expiration.
     */
    public static function revokeToken(string $token): void
    {
        $payload = self::decodePayload($token);

        if (!is_array($payload) || !isset($payload['exp'])) {
            return;
        }

        $expiresAt = (int)$payload['exp'];
        if ($expiresAt <= time()) {
            return;
        }

        self::ensureBlacklistStorage();

        $record = [
            'expires_at' => $expiresAt,
            'revoked_at' => time(),
        ];

        $path = self::blacklistFilePath($token);
        @file_put_contents($path, json_encode($record, JSON_UNESCAPED_UNICODE));

        self::cleanupBlacklistFiles();
    }

    // ─── Middleware Entry Point ───────────────────────────────────────────────

    /**
     * Validate the incoming request's JWT.
     * Returns the decoded payload or sends a 401 JSON response.
     */
    public static function authenticate(): array
    {
        $token = self::extractToken();

        if ($token === null) {
            self::respondUnauthorized('Token não fornecido');
        }

        $payload = self::validateToken($token);

        if ($payload === null) {
            self::respondUnauthorized('Token inválido ou expirado');
        }

        return $payload;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private static function extractToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        // Fallback: Apache rewrites may use REDIRECT_HTTP_AUTHORIZATION
        $redirect = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($redirect, 'Bearer ')) {
            return substr($redirect, 7);
        }

        return null;
    }

    private static function getSecret(): string
    {
        $secret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? '');

        if (empty($secret)) {
            $appEnv = strtolower((string)(getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'local')));
            if ($appEnv === 'production') {
                throw new \RuntimeException('JWT_SECRET é obrigatório em produção');
            }

            // Fallback to a derived key from session name — deterministic per environment (non-production)
            $config = require __DIR__ . '/../config/app.php';
            $secret = hash('sha256', ($config['session_name'] ?? 'mm_session') . '_super_admin_jwt_2025');
        }

        return $secret;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function decodePayload(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        return is_array($payload) ? $payload : null;
    }

    private static function isTokenRevoked(string $token): bool
    {
        self::cleanupBlacklistFiles();

        $path = self::blacklistFilePath($token);
        if (!is_file($path)) {
            return false;
        }

        $raw = @file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return true;
        }

        $expiresAt = (int)($data['expires_at'] ?? 0);
        if ($expiresAt <= time()) {
            @unlink($path);
            return false;
        }

        return true;
    }

    private static function blacklistFilePath(string $token): string
    {
        $hash = hash('sha256', $token);
        return rtrim(self::BLACKLIST_DIR, '/') . '/' . $hash . '.json';
    }

    private static function ensureBlacklistStorage(): void
    {
        if (!is_dir(self::BLACKLIST_DIR)) {
            @mkdir(self::BLACKLIST_DIR, 0775, true);
        }
    }

    private static function cleanupBlacklistFiles(): void
    {
        self::ensureBlacklistStorage();

        $files = glob(rtrim(self::BLACKLIST_DIR, '/') . '/*.json') ?: [];
        $now = time();

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $raw = @file_get_contents($file);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            $expiresAt = (int)($data['expires_at'] ?? 0);

            if ($expiresAt > 0 && $expiresAt <= $now) {
                @unlink($file);
            }
        }
    }

    private static function generateJti(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @return never
     */
    private static function respondUnauthorized(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}
