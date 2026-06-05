<?php

declare(strict_types=1);

/**
 * Tokens de dispositivo para push nativo (FCM / APNs via FCM).
 * Tabela: device_tokens (migration 20260531_create_device_tokens.sql).
 */
class DeviceToken
{
    private const PLATFORMS = ['android', 'ios'];
    private const MAX_FAILURES = 5;

    /**
     * Registra/atualiza um device (UPSERT por fcm_token).
     * Reativa e zera falhas se o token já existir.
     *
     * @return array{id:int}
     */
    public static function register(
        int $companyId,
        ?int $userId,
        string $fcmToken,
        string $platform,
        array $meta = []
    ): array {
        $platform = in_array($platform, self::PLATFORMS, true) ? $platform : 'android';

        $pdo = db();
        $sql = 'INSERT INTO device_tokens
                    (company_id, user_id, fcm_token, platform, app_version, device_name, device_id, is_active, failed_count)
                VALUES (:company_id, :user_id, :fcm_token, :platform, :app_version, :device_name, :device_id, 1, 0)
                ON DUPLICATE KEY UPDATE
                    company_id  = VALUES(company_id),
                    user_id     = VALUES(user_id),
                    platform    = VALUES(platform),
                    app_version = VALUES(app_version),
                    device_name = VALUES(device_name),
                    device_id   = VALUES(device_id),
                    is_active   = 1,
                    failed_count = 0';

        $st = $pdo->prepare($sql);
        $st->execute([
            ':company_id'  => $companyId,
            ':user_id'     => $userId,
            ':fcm_token'   => $fcmToken,
            ':platform'    => $platform,
            ':app_version' => isset($meta['app_version']) ? substr((string) $meta['app_version'], 0, 20) : null,
            ':device_name' => isset($meta['device_name']) ? substr((string) $meta['device_name'], 0, 100) : null,
            ':device_id'   => isset($meta['device_id']) ? substr((string) $meta['device_id'], 0, 128) : null,
        ]);

        // lastInsertId pode ser 0 em UPDATE; resolve o id pelo token.
        $id = (int) $pdo->lastInsertId();
        if ($id === 0) {
            $sel = $pdo->prepare('SELECT id FROM device_tokens WHERE fcm_token = ? LIMIT 1');
            $sel->execute([$fcmToken]);
            $id = (int) ($sel->fetchColumn() ?: 0);
        }

        return ['id' => $id];
    }

    /** Desativa (logout/desregistro) um device pelo token. */
    public static function deactivate(string $fcmToken): bool
    {
        $st = db()->prepare('UPDATE device_tokens SET is_active = 0 WHERE fcm_token = :t AND is_active = 1');
        $st->execute([':t' => $fcmToken]);

        return $st->rowCount() > 0;
    }

    /** Lista os tokens ativos de uma empresa. */
    public static function listActiveByCompany(int $companyId): array
    {
        $st = db()->prepare('SELECT id, fcm_token, platform FROM device_tokens WHERE company_id = :c AND is_active = 1');
        $st->execute([':c' => $companyId]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marca falha de envio; ao passar do limite, desativa o token
     * (token inválido/expirado reportado pelo FCM).
     */
    public static function markFailure(string $fcmToken): void
    {
        $st = db()->prepare('UPDATE device_tokens
                             SET failed_count = failed_count + 1,
                                 is_active = IF(failed_count + 1 >= :max, 0, is_active)
                             WHERE fcm_token = :t');
        $st->execute([':max' => self::MAX_FAILURES, ':t' => $fcmToken]);
    }

    /** Marca uso bem-sucedido (zera falhas). */
    public static function markSuccess(string $fcmToken): void
    {
        $st = db()->prepare('UPDATE device_tokens SET last_used_at = NOW(), failed_count = 0 WHERE fcm_token = :t');
        $st->execute([':t' => $fcmToken]);
    }
}
