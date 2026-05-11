<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/QueueJob.php';

class QueueService
{
    public static function getDashboard(array $filters, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $rows = QueueJob::search($filters, $perPage, $offset);
        $total = QueueJob::count($filters);

        $summary = self::summary($filters);

        return [
            'rows' => $rows,
            'summary' => $summary,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function retry(int $jobId): array
    {
        $job = QueueJob::getById($jobId);
        if (!$job) {
            return ['success' => false, 'message' => 'Job não encontrado.'];
        }

        $ok = QueueJob::retry($jobId);
        if (!$ok) {
            return ['success' => false, 'message' => 'Falha ao reagendar job.'];
        }

        return ['success' => true, 'message' => 'Job reagendado para processamento.'];
    }

    public static function summary(?array $filters = null): array
    {
        $sql = 'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) AS processing,
                    SUM(CASE WHEN status = "done" THEN 1 ELSE 0 END) AS done_count,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN status = "dead" THEN 1 ELSE 0 END) AS dead_count
                FROM queue_jobs
                WHERE 1 = 1';

        $bind = [];

        if (is_array($filters) && !empty($filters['company_id'])) {
            $sql .= ' AND company_id = ?';
            $bind[] = (int)$filters['company_id'];
        }
        if (is_array($filters) && !empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $bind[] = (string)$filters['status'];
        }
        if (is_array($filters) && !empty($filters['job_type'])) {
            $sql .= ' AND job_type = ?';
            $bind[] = (string)$filters['job_type'];
        }

        $st = db()->prepare($sql);
        $st->execute($bind);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'processing' => (int)($row['processing'] ?? 0),
            'done' => (int)($row['done_count'] ?? 0),
            'failed' => (int)($row['failed'] ?? 0),
            'dead' => (int)($row['dead_count'] ?? 0),
        ];
    }
}
