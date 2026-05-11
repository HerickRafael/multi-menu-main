<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class WebhookLog
{
    public static function create(
        ?int $companyId,
        string $source,
        string $eventType,
        ?array $payload,
        string $status = 'received',
        ?int $responseStatus = null,
        ?string $responseBody = null,
        ?string $lastError = null
    ): bool {
        $sql = 'INSERT INTO webhook_logs
                (company_id, source, event_type, payload_json, response_status, response_body, status, last_error)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $st = db()->prepare($sql);
        return $st->execute([
            $companyId,
            $source,
            $eventType,
            $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            $responseStatus,
            $responseBody,
            $status,
            $lastError,
        ]);
    }

    public static function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM webhook_logs WHERE 1=1';
        $bind = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $bind[] = $filters['status'];
        }
        if (!empty($filters['source'])) {
            $sql .= ' AND source = ?';
            $bind[] = $filters['source'];
        }
        if (!empty($filters['company_id'])) {
            $sql .= ' AND company_id = ?';
            $bind[] = (int)$filters['company_id'];
        }

        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?';
        $bind[] = $limit;
        $bind[] = $offset;

        $st = db()->prepare($sql);
        $st->execute($bind);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function count(array $filters): int
    {
        $sql = 'SELECT COUNT(*) FROM webhook_logs WHERE 1=1';
        $bind = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $bind[] = $filters['status'];
        }
        if (!empty($filters['source'])) {
            $sql .= ' AND source = ?';
            $bind[] = $filters['source'];
        }
        if (!empty($filters['company_id'])) {
            $sql .= ' AND company_id = ?';
            $bind[] = (int)$filters['company_id'];
        }

        $st = db()->prepare($sql);
        $st->execute($bind);
        return (int)$st->fetchColumn();
    }

    public static function getById(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM webhook_logs WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function markRetried(int $id, ?string $error = null): bool
    {
        $sql = 'UPDATE webhook_logs
                SET status = ?,
                    retry_count = retry_count + 1,
                    last_error = ?,
                    next_retry_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE),
                    updated_at = NOW()
                WHERE id = ?';
        $st = db()->prepare($sql);
        return $st->execute([$error ? 'failed' : 'retrying', $error, $id]);
    }

    public static function markProcessed(int $id, ?int $responseStatus = null, ?string $responseBody = null): bool
    {
        $sql = 'UPDATE webhook_logs
                SET status = ?, response_status = ?, response_body = ?, processed_at = NOW(), updated_at = NOW()
                WHERE id = ?';
        $st = db()->prepare($sql);
        return $st->execute(['processed', $responseStatus, $responseBody, $id]);
    }
}
