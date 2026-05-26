<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class QueueJob
{
    public static function enqueue(
        string $jobType,
        ?array $payload,
        ?int $companyId = null,
        int $priority = 5,
        int $maxAttempts = 5,
        ?string $availableAt = null,
        ?string $dedupKey = null
    ): bool {
        $sql = 'INSERT INTO queue_jobs
                (company_id, job_type, dedup_key, payload_json, status, priority, max_attempts, available_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $st = db()->prepare($sql);
        return $st->execute([
            $companyId,
            $jobType,
            $dedupKey,
            $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'pending',
            $priority,
            $maxAttempts,
            $availableAt ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Enfileira coalescendo por dedup_key: se já existir job pending/retrying
     * com o mesmo dedup_key, retorna false sem inserir (o job existente vai
     * pegar o estado atualizado quando rodar).
     */
    public static function enqueueCoalesced(
        string $jobType,
        string $dedupKey,
        ?array $payload,
        ?int $companyId = null,
        int $priority = 5,
        int $maxAttempts = 5,
        ?string $availableAt = null
    ): bool {
        $existing = db()->prepare(
            'SELECT id FROM queue_jobs
              WHERE dedup_key = ? AND status IN (\'pending\', \'retrying\')
              LIMIT 1'
        );
        $existing->execute([$dedupKey]);
        if ($existing->fetchColumn() !== false) {
            return false; // coalesced
        }

        return self::enqueue($jobType, $payload, $companyId, $priority, $maxAttempts, $availableAt, $dedupKey);
    }

    public static function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM queue_jobs WHERE 1=1';
        $bind = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $bind[] = $filters['status'];
        }
        if (!empty($filters['job_type'])) {
            $sql .= ' AND job_type = ?';
            $bind[] = $filters['job_type'];
        }
        if (!empty($filters['company_id'])) {
            $sql .= ' AND company_id = ?';
            $bind[] = (int)$filters['company_id'];
        }

        $sql .= ' ORDER BY priority ASC, created_at DESC, id DESC LIMIT ? OFFSET ?';
        $bind[] = $limit;
        $bind[] = $offset;

        $st = db()->prepare($sql);
        $st->execute($bind);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function count(array $filters): int
    {
        $sql = 'SELECT COUNT(*) FROM queue_jobs WHERE 1=1';
        $bind = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $bind[] = $filters['status'];
        }
        if (!empty($filters['job_type'])) {
            $sql .= ' AND job_type = ?';
            $bind[] = $filters['job_type'];
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
        $st = db()->prepare('SELECT * FROM queue_jobs WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function retry(int $id): bool
    {
        $sql = 'UPDATE queue_jobs
                SET status = ?,
                    attempts = attempts + 1,
                    last_error = NULL,
                    reserved_at = NULL,
                    available_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?';
        $st = db()->prepare($sql);
        return $st->execute(['retrying', $id]);
    }
}
