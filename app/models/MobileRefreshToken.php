<?php

declare(strict_types=1);

/**
 * Refresh tokens do app mobile (Flutter).
 *
 * Suporta o fluxo de auth da Fase 1:
 *  - issue()              → emite refresh token (guarda apenas o SHA-256)
 *  - findValidByToken()   → valida (não revogado e não expirado)
 *  - rotate()             → revoga o atual e emite um novo (rotação segura)
 *  - revokeByToken()      → logout do device
 *  - revokeAllForUser()   → logout global
 *
 * Tabela: mobile_refresh_tokens (migration 20260531_create_mobile_refresh_tokens.sql)
 */
class MobileRefreshToken
{
    /** Validade padrão do refresh token. */
    private const TTL_DAYS = 30;

    /** Gera o hash que é efetivamente armazenado (nunca guardamos o token em texto). */
    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * Emite um novo refresh token e persiste o hash.
     *
     * @return array{id:int, token:string, expires_at:string}
     */
    public static function issue(int $userId, int $companyId, array $meta = []): array
    {
        $plain = bin2hex(random_bytes(32)); // 64 chars
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TTL_DAYS . ' days'));

        // Capturar a conexão em variável local: lastInsertId() precisa ser lido
        // no MESMO handle que executou o INSERT (chamar db() de novo pode não
        // refletir o último insert).
        $pdo = db();

        $sql = 'INSERT INTO mobile_refresh_tokens
                    (user_id, company_id, token_hash, device_token_id, user_agent, ip_address, expires_at)
                VALUES (:user_id, :company_id, :token_hash, :device_token_id, :user_agent, :ip_address, :expires_at)';

        $st = $pdo->prepare($sql);
        $st->execute([
            ':user_id'         => $userId,
            ':company_id'      => $companyId,
            ':token_hash'      => self::hash($plain),
            ':device_token_id' => $meta['device_token_id'] ?? null,
            ':user_agent'      => isset($meta['user_agent']) ? substr((string) $meta['user_agent'], 0, 255) : null,
            ':ip_address'      => $meta['ip_address'] ?? null,
            ':expires_at'      => $expiresAt,
        ]);

        return [
            'id'         => (int) $pdo->lastInsertId(),
            'token'      => $plain,
            'expires_at' => $expiresAt,
        ];
    }

    /** Retorna a linha do token se for válido (não revogado e não expirado), ou null. */
    public static function findValidByToken(string $plainToken): ?array
    {
        $sql = 'SELECT * FROM mobile_refresh_tokens
                WHERE token_hash = :h AND revoked_at IS NULL AND expires_at > NOW()
                LIMIT 1';
        $st = db()->prepare($sql);
        $st->execute([':h' => self::hash($plainToken)]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Rotação: revoga o token atual e emite um novo para o mesmo usuário/empresa.
     *
     * @return array{id:int, token:string, expires_at:string}|null
     */
    public static function rotate(string $plainToken, array $meta = []): ?array
    {
        $current = self::findValidByToken($plainToken);
        if (!$current) {
            return null;
        }

        $new = self::issue((int) $current['user_id'], (int) $current['company_id'], $meta);

        $st = db()->prepare('UPDATE mobile_refresh_tokens
                             SET revoked_at = NOW(), rotated_to = :new_id, last_used_at = NOW()
                             WHERE id = :id');
        $st->execute([':new_id' => $new['id'], ':id' => (int) $current['id']]);

        return $new;
    }

    /** Logout do device: invalida um refresh token específico. */
    public static function revokeByToken(string $plainToken): bool
    {
        $st = db()->prepare('UPDATE mobile_refresh_tokens
                             SET revoked_at = NOW()
                             WHERE token_hash = :h AND revoked_at IS NULL');
        $st->execute([':h' => self::hash($plainToken)]);

        return $st->rowCount() > 0;
    }

    /** Logout global: invalida todos os refresh tokens ativos do usuário. */
    public static function revokeAllForUser(int $userId): int
    {
        $st = db()->prepare('UPDATE mobile_refresh_tokens
                             SET revoked_at = NOW()
                             WHERE user_id = :u AND revoked_at IS NULL');
        $st->execute([':u' => $userId]);

        return $st->rowCount();
    }
}
