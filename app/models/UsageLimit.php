<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class UsageLimit
{
    public static function listByCompany(int $companyId): array
    {
        $st = db()->prepare('SELECT * FROM usage_limits WHERE company_id = ? ORDER BY resource_key ASC');
        $st->execute([$companyId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getByCompanyAndResource(int $companyId, string $resourceKey): ?array
    {
        $st = db()->prepare('SELECT * FROM usage_limits WHERE company_id = ? AND resource_key = ? LIMIT 1');
        $st->execute([$companyId, trim($resourceKey)]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsert(array $data): bool
    {
        $sql = 'INSERT INTO usage_limits
                (company_id, subscription_id, resource_key, hard_limit, soft_limit, current_usage, reset_period, resets_at, is_blocking)
                VALUES
                (:company_id, :subscription_id, :resource_key, :hard_limit, :soft_limit, :current_usage, :reset_period, :resets_at, :is_blocking)
                ON DUPLICATE KEY UPDATE
                    subscription_id = VALUES(subscription_id),
                    hard_limit = VALUES(hard_limit),
                    soft_limit = VALUES(soft_limit),
                    current_usage = VALUES(current_usage),
                    reset_period = VALUES(reset_period),
                    resets_at = VALUES(resets_at),
                    is_blocking = VALUES(is_blocking),
                    updated_at = NOW()';

        $st = db()->prepare($sql);
        return $st->execute([
            'company_id' => (int)$data['company_id'],
            'subscription_id' => $data['subscription_id'] ?? null,
            'resource_key' => trim((string)$data['resource_key']),
            'hard_limit' => (int)($data['hard_limit'] ?? 0),
            'soft_limit' => (int)($data['soft_limit'] ?? 0),
            'current_usage' => (int)($data['current_usage'] ?? 0),
            'reset_period' => (string)($data['reset_period'] ?? 'monthly'),
            'resets_at' => $data['resets_at'] ?? null,
            'is_blocking' => (int)($data['is_blocking'] ?? 1) === 1 ? 1 : 0,
        ]);
    }

    public static function incrementUsage(int $companyId, string $resourceKey, int $increment = 1): bool
    {
        $sql = 'UPDATE usage_limits
                SET current_usage = current_usage + :inc,
                    updated_at = NOW()
                WHERE company_id = :company_id AND resource_key = :resource_key';
        $st = db()->prepare($sql);
        return $st->execute([
            'inc' => max(0, $increment),
            'company_id' => $companyId,
            'resource_key' => trim($resourceKey),
        ]);
    }

    public static function resetUsage(int $companyId, string $resourceKey, ?string $nextResetsAt = null): bool
    {
        $sql = 'UPDATE usage_limits
                SET current_usage = 0,
                    resets_at = :resets_at,
                    updated_at = NOW()
                WHERE company_id = :company_id AND resource_key = :resource_key';
        $st = db()->prepare($sql);
        return $st->execute([
            'resets_at' => $nextResetsAt,
            'company_id' => $companyId,
            'resource_key' => trim($resourceKey),
        ]);
    }
}
