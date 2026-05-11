<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/WebhookLog.php';
require_once __DIR__ . '/../models/QueueJob.php';

class WebhookService
{
    public static function getDashboard(array $filters, int $page = 1, int $perPage = 40): array
    {
        $offset = ($page - 1) * $perPage;
        $rows = WebhookLog::search($filters, $perPage, $offset);
        $total = WebhookLog::count($filters);

        $summary = self::summary();

        return [
            'rows' => $rows,
            'summary' => $summary,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function retry(int $webhookId): array
    {
        $log = WebhookLog::getById($webhookId);
        if (!$log) {
            return ['success' => false, 'message' => 'Webhook não encontrado.'];
        }

        $payload = null;
        if (!empty($log['payload_json'])) {
            $decoded = json_decode((string)$log['payload_json'], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $jobPayload = [
            'webhook_log_id' => (int)$log['id'],
            'source' => $log['source'] ?? 'unknown',
            'event_type' => $log['event_type'] ?? 'unknown',
            'payload' => $payload,
        ];

        $queued = QueueJob::enqueue('webhook_retry', $jobPayload, isset($log['company_id']) ? (int)$log['company_id'] : null, 3, 5);
        if (!$queued) {
            return ['success' => false, 'message' => 'Falha ao enfileirar retry do webhook.'];
        }

        WebhookLog::markRetried($webhookId, null);

        return ['success' => true, 'message' => 'Retry enfileirado com sucesso.'];
    }

    public static function summary(): array
    {
        $pdo = db();
        $row = $pdo->query('SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) AS processed,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN status = "retrying" THEN 1 ELSE 0 END) AS retrying,
                    SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) AS received
                FROM webhook_logs')->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'processed' => (int)($row['processed'] ?? 0),
            'failed' => (int)($row['failed'] ?? 0),
            'retrying' => (int)($row['retrying'] ?? 0),
            'received' => (int)($row['received'] ?? 0),
        ];
    }
}
