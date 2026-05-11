<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Subscription
{
    public static function currentByCompany(int $companyId): ?array
    {
        $sql = 'SELECT s.*, p.code AS plan_code, p.name AS plan_name, p.price_monthly, p.price_yearly, p.currency
                FROM subscriptions s
                INNER JOIN plans p ON p.id = s.plan_id
                WHERE s.company_id = ?
                ORDER BY s.id DESC
                LIMIT 1';
        $st = db()->prepare($sql);
        $st->execute([$companyId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function listByStatus(string $status, int $limit = 100): array
    {
        $valid = ['trialing', 'active', 'past_due', 'canceled', 'incomplete', 'paused'];
        if (!in_array($status, $valid, true)) {
            throw new InvalidArgumentException('status invalido');
        }

        $st = db()->prepare('SELECT * FROM subscriptions WHERE status = ? ORDER BY id DESC LIMIT ?');
        $st->bindValue(1, $status);
        $st->bindValue(2, max(1, min(500, $limit)), PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO subscriptions
                (company_id, plan_id, status, starts_at, trial_ends_at, current_period_start, current_period_end, canceled_at,
                 external_provider, external_subscription_id, metadata_json, created_by_super_admin_id)
                VALUES
                (:company_id, :plan_id, :status, :starts_at, :trial_ends_at, :current_period_start, :current_period_end, :canceled_at,
                 :external_provider, :external_subscription_id, :metadata_json, :created_by_super_admin_id)';

        $metadata = $data['metadata_json'] ?? null;
        $metadataPayload = null;
        if ($metadata !== null) {
            $metadataPayload = is_string($metadata) ? $metadata : json_encode($metadata, JSON_UNESCAPED_UNICODE);
        }

        $st = db()->prepare($sql);
        $st->execute([
            'company_id' => (int)$data['company_id'],
            'plan_id' => (int)$data['plan_id'],
            'status' => (string)($data['status'] ?? 'incomplete'),
            'starts_at' => $data['starts_at'] ?? date('Y-m-d H:i:s'),
            'trial_ends_at' => $data['trial_ends_at'] ?? null,
            'current_period_start' => $data['current_period_start'] ?? null,
            'current_period_end' => $data['current_period_end'] ?? null,
            'canceled_at' => $data['canceled_at'] ?? null,
            'external_provider' => $data['external_provider'] ?? null,
            'external_subscription_id' => $data['external_subscription_id'] ?? null,
            'metadata_json' => $metadataPayload,
            'created_by_super_admin_id' => $data['created_by_super_admin_id'] ?? null,
        ]);

        return (int)db()->lastInsertId();
    }

    public static function updateStatus(int $subscriptionId, string $status, ?string $canceledAt = null): bool
    {
        $sql = 'UPDATE subscriptions
                SET status = :status,
                    canceled_at = :canceled_at,
                    updated_at = NOW()
                WHERE id = :id';
        $st = db()->prepare($sql);
        return $st->execute([
            'status' => $status,
            'canceled_at' => $canceledAt,
            'id' => $subscriptionId,
        ]);
    }
}
