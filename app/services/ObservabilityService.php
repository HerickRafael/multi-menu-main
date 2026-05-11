<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/HealthCheck.php';

class ObservabilityService
{
    public static function runHealthChecks(): array
    {
        $results = [];

        // Check DB
        try {
            db()->query('SELECT 1');
            $results[] = ['component' => 'database', 'status' => 'ok', 'message' => 'Conexao com banco OK', 'metadata' => ['latency_ms' => 0]];
        } catch (Throwable $e) {
            $results[] = ['component' => 'database', 'status' => 'critical', 'message' => 'Falha na conexao com banco', 'metadata' => ['error' => $e->getMessage()]];
        }

        // Check storage/logs writable
        $logsPath = __DIR__ . '/../../storage/logs';
        if (is_dir($logsPath) && is_writable($logsPath)) {
            $results[] = ['component' => 'storage.logs', 'status' => 'ok', 'message' => 'Diretorio de logs gravavel', 'metadata' => ['path' => $logsPath]];
        } else {
            $results[] = ['component' => 'storage.logs', 'status' => 'warning', 'message' => 'Diretorio de logs nao gravavel', 'metadata' => ['path' => $logsPath]];
        }

        // Check queue backlog
        try {
            $queuePending = (int)db()->query("SELECT COUNT(*) FROM queue_jobs WHERE status IN ('pending','retrying','failed')")->fetchColumn();
            $status = $queuePending > 100 ? 'warning' : 'ok';
            $results[] = ['component' => 'queue.backlog', 'status' => $status, 'message' => 'Backlog da fila monitorado', 'metadata' => ['pending_count' => $queuePending]];
        } catch (Throwable $e) {
            $results[] = ['component' => 'queue.backlog', 'status' => 'warning', 'message' => 'Nao foi possivel medir backlog da fila', 'metadata' => ['error' => $e->getMessage()]];
        }

        // Check webhook failures last 24h
        try {
            $failedWebhook = (int)db()->query("SELECT COUNT(*) FROM webhook_logs WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
            $status = $failedWebhook > 20 ? 'warning' : 'ok';
            $results[] = ['component' => 'webhooks.failed_24h', 'status' => $status, 'message' => 'Falhas de webhook nas ultimas 24h', 'metadata' => ['failed_count' => $failedWebhook]];
        } catch (Throwable $e) {
            $results[] = ['component' => 'webhooks.failed_24h', 'status' => 'warning', 'message' => 'Nao foi possivel medir falhas de webhook', 'metadata' => ['error' => $e->getMessage()]];
        }

        foreach ($results as $item) {
            HealthCheck::add($item['component'], $item['status'], $item['message'], $item['metadata']);
        }

        return $results;
    }

    public static function dashboard(): array
    {
        return [
            'summary' => HealthCheck::summaryLastHour(),
            'latest' => HealthCheck::latest(120),
        ];
    }
}
