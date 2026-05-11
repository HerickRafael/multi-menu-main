<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class EventLog
{
    public static function create(
        string $eventName,
        ?string $aggregateType,
        ?int $aggregateId,
        ?int $companyId,
        ?array $payload,
        ?int $dispatchedBy,
        string $source = 'system'
    ): bool {
        $sql = 'INSERT INTO events_log
                (event_name, aggregate_type, aggregate_id, company_id, payload_json, dispatched_by, source)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $st = db()->prepare($sql);
        return $st->execute([
            $eventName,
            $aggregateType,
            $aggregateId,
            $companyId,
            $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            $dispatchedBy,
            $source,
        ]);
    }

    public static function search(array $filters, int $limit = 80, int $offset = 0): array
    {
        $sql = 'SELECT * FROM events_log WHERE 1=1';
        $bind = [];
        if (!empty($filters['event_name'])) {
            $sql .= ' AND event_name = ?';
            $bind[] = $filters['event_name'];
        }
        if (!empty($filters['company_id'])) {
            $sql .= ' AND company_id = ?';
            $bind[] = (int)$filters['company_id'];
        }
        if (!empty($filters['source'])) {
            $sql .= ' AND source = ?';
            $bind[] = $filters['source'];
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
        $sql = 'SELECT COUNT(*) FROM events_log WHERE 1=1';
        $bind = [];
        if (!empty($filters['event_name'])) {
            $sql .= ' AND event_name = ?';
            $bind[] = $filters['event_name'];
        }
        if (!empty($filters['company_id'])) {
            $sql .= ' AND company_id = ?';
            $bind[] = (int)$filters['company_id'];
        }
        if (!empty($filters['source'])) {
            $sql .= ' AND source = ?';
            $bind[] = $filters['source'];
        }

        $st = db()->prepare($sql);
        $st->execute($bind);
        return (int)$st->fetchColumn();
    }
}
