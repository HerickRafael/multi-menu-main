<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class FeatureFlag
{
    public static function all(): array
    {
        $st = db()->query('SELECT * FROM feature_flags ORDER BY name ASC');
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function tenantFlags(int $companyId): array
    {
        $sql = 'SELECT tf.id, tf.company_id, tf.feature_flag_id, tf.enabled, tf.updated_at,
                       ff.flag_key, ff.name, ff.description, ff.default_enabled, ff.is_active
                FROM tenant_features tf
                INNER JOIN feature_flags ff ON ff.id = tf.feature_flag_id
                WHERE tf.company_id = ?
                ORDER BY ff.name ASC';
        $st = db()->prepare($sql);
        $st->execute([$companyId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function setTenantFlag(int $companyId, int $featureFlagId, bool $enabled, ?int $updatedBy = null): bool
    {
        $sql = 'INSERT INTO tenant_features (company_id, feature_flag_id, enabled, updated_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), updated_by = VALUES(updated_by), updated_at = NOW()';
        $st = db()->prepare($sql);
        return $st->execute([$companyId, $featureFlagId, $enabled ? 1 : 0, $updatedBy]);
    }

    public static function getTenantFlag(int $companyId, int $featureFlagId): ?array
    {
        $st = db()->prepare('SELECT * FROM tenant_features WHERE company_id = ? AND feature_flag_id = ? LIMIT 1');
        $st->execute([$companyId, $featureFlagId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function addHistory(
        int $companyId,
        int $featureFlagId,
        bool $oldEnabled,
        bool $newEnabled,
        ?int $changedBy,
        ?string $reason
    ): bool {
        $sql = 'INSERT INTO feature_flag_history
                (company_id, feature_flag_id, old_enabled, new_enabled, changed_by, reason)
                VALUES (?, ?, ?, ?, ?, ?)';
        $st = db()->prepare($sql);
        return $st->execute([$companyId, $featureFlagId, $oldEnabled ? 1 : 0, $newEnabled ? 1 : 0, $changedBy, $reason]);
    }
}
