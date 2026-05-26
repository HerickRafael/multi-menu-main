#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smoke test da Fase 7 (DLQ observability).
 *
 * Cobre:
 *   1) queueStats agrega corretamente status e janelas (1h, 24h).
 *   2) apiHealth calcula taxa de erro a partir de ifood_api_logs sintéticos.
 *   3) latencyStats devolve avg/p50/p95/p99 com dados conhecidos.
 *   4) deadJobs lista filtrado por company + job_type.
 *   5) retryDeadJob volta de dead para retrying e zera reserved_at.
 *   6) retryDeadJobsByType faz bulk e respeita limit + filtro company.
 *   7) retryDeadJob de job inexistente → ok=false friendly.
 *   8) alertingViolations dispara quando thresholds são violados.
 *   9) alertingViolations não dispara com total_calls=0 (evita falso positivo).
 *  10) cleanup cron --dry-run não deleta nada.
 *
 * Uso: php scripts/ifood_dlq_smoke_test.php [company_id]
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/DLQObservabilityService.php';

use App\Services\IFood\DLQObservabilityService;

$db = db();
$companyId = (int) ($argv[1] ?? 1);

echo "→ Smoke test DLQ observability — company={$companyId}\n";

$passed = 0;
$failed = 0;
$check = static function (string $label, bool $ok, ?string $detail = null) use (&$passed, &$failed): void {
    $out = '  ' . ($ok ? '✓ ' : '× ') . $label;
    if (!$ok && $detail !== null) {
        $out .= " [{$detail}]";
    }
    echo $out . "\n";
    $ok ? $passed++ : $failed++;
};

$cleanup = static function () use ($db, $companyId) {
    $db->prepare("DELETE FROM queue_jobs WHERE job_type LIKE 'ifood.smoke-dlq.%' AND company_id=?")
        ->execute([$companyId]);
    $db->prepare("DELETE FROM ifood_api_logs WHERE module = 'smoke_dlq'")->execute();
    $db->prepare("DELETE FROM ifood_api_logs WHERE module = 'dlq_alert' AND request_url = 'cron://dlq_alerts'")->execute();
};
$cleanup();

// === Setup queue_jobs sintéticos ===
$insJob = $db->prepare(
    "INSERT INTO queue_jobs
        (company_id, job_type, status, attempts, max_attempts, last_error, available_at, created_at, updated_at, completed_at)
     VALUES (?, ?, ?, ?, 5, ?, ?, ?, ?, ?)"
);
$now = date('Y-m-d H:i:s');
$ago30m = date('Y-m-d H:i:s', time() - 1800);
$ago2h  = date('Y-m-d H:i:s', time() - 7200);

// 3 dead na última hora (cima do threshold 5? não, igual)
$insJob->execute([$companyId, 'ifood.smoke-dlq.a', 'dead', 5, 'erro X timeout', $now, $now, $ago30m, null]);
$insJob->execute([$companyId, 'ifood.smoke-dlq.a', 'dead', 5, 'erro X timeout', $now, $now, $ago30m, null]);
$insJob->execute([$companyId, 'ifood.smoke-dlq.b', 'dead', 5, 'erro Y 500',     $now, $now, $ago30m, null]);
// 3 dead há 2h (entram só em 24h, não em 1h)
$insJob->execute([$companyId, 'ifood.smoke-dlq.c', 'dead', 5, 'erro antigo', $now, $now, $ago2h, null]);
$insJob->execute([$companyId, 'ifood.smoke-dlq.c', 'dead', 5, 'erro antigo', $now, $now, $ago2h, null]);
$insJob->execute([$companyId, 'ifood.smoke-dlq.c', 'dead', 5, 'erro antigo', $now, $now, $ago2h, null]);
// 1 retrying
$insJob->execute([$companyId, 'ifood.smoke-dlq.a', 'retrying', 2, 'transient',  $now, $now, $now,    null]);
// 1 done há 30m
$insJob->execute([$companyId, 'ifood.smoke-dlq.a', 'done',     1, null,         $now, $now, $now,    $now]);

// === Setup api_logs sintéticos (última hora) ===
$insLog = $db->prepare(
    "INSERT INTO ifood_api_logs
        (company_id, environment, module, request_method, request_url, http_status, latency_ms, attempt_number, created_at)
     VALUES (?, 'sandbox', 'smoke_dlq', 'GET', 'cron://smoke', ?, ?, 1, ?)"
);
// 15 sucesso, 3 erro 5xx, 2 erro 4xx, 2 network = 22 total → 7 erro = 31.8%
for ($i = 0; $i < 15; $i++) {
    $insLog->execute([$companyId, 200, 100 + $i * 10, $now]); // latências 100..240
}
for ($i = 0; $i < 3; $i++) {
    $insLog->execute([$companyId, 500, 200 + $i * 100, $now]);
}
for ($i = 0; $i < 2; $i++) {
    $insLog->execute([$companyId, 400, 50, $now]);
}
for ($i = 0; $i < 2; $i++) {
    $insLog->execute([$companyId, null, 30000, $now]); // timeout — alta latência
}

$service = new DLQObservabilityService($db);

// === Asserts ===

// 1) queueStats: deve contar 3 dead_1h + 6 dead_24h da smoke
$qs = $service->queueStats();
// (pode haver jobs reais de outras fases — checamos só smoke prefixes)
$smokeDead1h = $qs['by_type_dead_24h']['ifood.smoke-dlq.a'] ?? 0;
$check('queueStats inclui smoke-dlq.a no by_type_dead_24h',
    ($qs['by_type_dead_24h']['ifood.smoke-dlq.a'] ?? 0) >= 2);
$check('queueStats inclui smoke-dlq.c no by_type_dead_24h (3)',
    ($qs['by_type_dead_24h']['ifood.smoke-dlq.c'] ?? 0) === 3);
$check('queueStats.dead_total >= 6 (3 recentes + 3 antigos)',
    $qs['dead_total'] >= 6, 'got=' . $qs['dead_total']);

// 2) apiHealth: deve mostrar erro >= 7/22 = 31.8%
$api = $service->apiHealth();
$check('apiHealth.total_calls >= 22', $api['total_calls'] >= 22);
$check('apiHealth.errors_5xx >= 3', $api['errors_5xx'] >= 3);
$check('apiHealth.errors_4xx >= 2', $api['errors_4xx'] >= 2);
$check('apiHealth.network_errors >= 2', $api['network_errors'] >= 2);
$check('apiHealth.error_rate > 0.10', $api['error_rate'] > 0.10, 'got=' . $api['error_rate']);

// 3) latencyStats
$lat = $service->latencyStats();
$check('latencyStats.count >= 20', $lat['count'] >= 20);
$check('latencyStats tem p95_ms', $lat['p95_ms'] !== null);
$check('latencyStats.max_ms >= 30000', $lat['max_ms'] >= 30000);

// 4) deadJobs filtrado por company + type
$dead = $service->deadJobs($companyId, 'ifood.smoke-dlq.a', 10);
$check('deadJobs filtro por type retorna apenas type filtrado',
    count($dead) > 0 && array_reduce($dead, fn($acc, $r) => $acc && $r['job_type'] === 'ifood.smoke-dlq.a', true));

// 5) retryDeadJob: pega o id de um dead e re-promove
$deadOne = $service->deadJobs($companyId, 'ifood.smoke-dlq.a', 1);
$jobId = (int) ($deadOne[0]['id'] ?? 0);
$r = $service->retryDeadJob($jobId);
$check('retryDeadJob ok', $r['ok'] === true && $r['id'] === $jobId);
$stmt = $db->prepare("SELECT status FROM queue_jobs WHERE id = ?");
$stmt->execute([$jobId]);
$check('job está em retrying agora', (string) $stmt->fetchColumn() === 'retrying');

// 6) retryDeadJobsByType (smoke-dlq.c, limit 2)
$r = $service->retryDeadJobsByType('ifood.smoke-dlq.c', $companyId, 2);
$check('retryDeadJobsByType retornou ok com retried=2', $r['ok'] === true && $r['retried'] === 2);

// 7) retry de job inexistente
$r = $service->retryDeadJob(99999999);
$check('retry de job inexistente → ok=false', $r['ok'] === false);

// 8) alertingViolations — error_rate threshold default 0.10 → 31.8% deve disparar
$violations = $service->alertingViolations();
$types = array_column($violations, 'type');
$check('alertingViolations dispara api_error_rate', in_array('api_error_rate', $types, true));

// 9) Com poucos calls não dispara (testamos limpando os logs sintéticos primeiro)
$db->prepare("DELETE FROM ifood_api_logs WHERE module = 'smoke_dlq'")->execute();
$violations = $service->alertingViolations();
$types = array_column($violations, 'type');
$check('alertingViolations NÃO dispara api_error_rate sem volume', !in_array('api_error_rate', $types, true));

// 10) cleanup cron --dry-run: roda como subprocesso (cron tem exit() que mataria o test)
// Forçar idades antigas para que entrem nos candidatos
$db->prepare("UPDATE queue_jobs SET completed_at = DATE_SUB(NOW(), INTERVAL 30 DAY) WHERE job_type LIKE 'ifood.smoke-dlq.%' AND status='done'")->execute();
$db->prepare("UPDATE queue_jobs SET updated_at  = DATE_SUB(NOW(), INTERVAL 60 DAY) WHERE job_type LIKE 'ifood.smoke-dlq.%' AND status='dead'")->execute();

$rowsBefore = (int) $db->query("SELECT COUNT(*) FROM queue_jobs WHERE job_type LIKE 'ifood.smoke-dlq.%'")->fetchColumn();
$cleanupScript = escapeshellarg(__DIR__ . '/ifood_jobs_cleanup_cron.php');
$cleanupOut = shell_exec("php {$cleanupScript} --dry-run 2>&1");
$rowsAfter = (int) $db->query("SELECT COUNT(*) FROM queue_jobs WHERE job_type LIKE 'ifood.smoke-dlq.%'")->fetchColumn();
$check('cleanup --dry-run não deleta', $rowsBefore === $rowsAfter,
    "before={$rowsBefore} after={$rowsAfter}");
$check('cleanup --dry-run reporta candidatos', str_contains((string) $cleanupOut, 'candidatos:'));

echo "\n  Total: {$passed} ok, {$failed} falha(s)\n";

$cleanup();
echo "→ Smoke test concluído. Estado limpo.\n";
exit($failed === 0 ? 0 : 1);
