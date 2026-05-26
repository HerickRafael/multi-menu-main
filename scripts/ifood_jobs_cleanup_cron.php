#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * iFood Jobs & Logs Cleanup Cron
 *
 * Purga registros antigos para manter as tabelas saudáveis:
 *
 *   queue_jobs status='done'  > IFOOD_JOBS_RETENTION_DONE_DAYS  (default 7)
 *   queue_jobs status='dead'  > IFOOD_JOBS_RETENTION_DEAD_DAYS  (default 30)
 *   ifood_api_logs            > IFOOD_LOGS_RETENTION_DAYS       (default 30)
 *
 * Cron sugerido (diário às 3h):
 *   0 3 * * * php /path/to/scripts/ifood_jobs_cleanup_cron.php >> /var/log/ifood_jobs_cleanup.log 2>&1
 *
 * Flags:
 *   --dry-run    apenas reporta o que seria deletado
 */

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/IFoodApiLogger.php';

use App\Services\IFood\IFoodApiLogger;

$dryRun = in_array('--dry-run', $argv ?? [], true);

$doneDays = max(1, (int) (getenv('IFOOD_JOBS_RETENTION_DONE_DAYS') ?: '7'));
$deadDays = max(1, (int) (getenv('IFOOD_JOBS_RETENTION_DEAD_DAYS') ?: '30'));
$logsDays = max(1, (int) (getenv('IFOOD_LOGS_RETENTION_DAYS')      ?: '30'));

$db = db();
$ts = static fn(): string => '[' . date('Y-m-d H:i:s') . ']';

echo $ts() . sprintf(
    " cleanup (dry_run=%s): done>%dd, dead>%dd, api_logs>%dd\n",
    $dryRun ? 'yes' : 'no',
    $doneDays,
    $deadDays,
    $logsDays
);

// Conta antes (sempre, para reportar)
$counts = [];
$stmt = $db->prepare(
    "SELECT COUNT(*) FROM queue_jobs
      WHERE job_type LIKE 'ifood.%' AND status = 'done'
        AND completed_at < DATE_SUB(NOW(), INTERVAL :d DAY)"
);
$stmt->execute([':d' => $doneDays]);
$counts['done'] = (int) $stmt->fetchColumn();

$stmt = $db->prepare(
    "SELECT COUNT(*) FROM queue_jobs
      WHERE job_type LIKE 'ifood.%' AND status = 'dead'
        AND updated_at < DATE_SUB(NOW(), INTERVAL :d DAY)"
);
$stmt->execute([':d' => $deadDays]);
$counts['dead'] = (int) $stmt->fetchColumn();

$stmt = $db->prepare(
    "SELECT COUNT(*) FROM ifood_api_logs
      WHERE created_at < DATE_SUB(NOW(), INTERVAL :d DAY)"
);
$stmt->execute([':d' => $logsDays]);
$counts['api_logs'] = (int) $stmt->fetchColumn();

echo $ts() . " candidatos: done={$counts['done']} dead={$counts['dead']} api_logs={$counts['api_logs']}\n";

if ($dryRun) {
    echo $ts() . " dry-run — nada deletado.\n";
    exit(0);
}

// Done: deleta em batches para não travar
$deleted = ['done' => 0, 'dead' => 0, 'api_logs' => 0];
try {
    $stmt = $db->prepare(
        "DELETE FROM queue_jobs
          WHERE job_type LIKE 'ifood.%' AND status = 'done'
            AND completed_at < DATE_SUB(NOW(), INTERVAL :d DAY)
          LIMIT 10000"
    );
    do {
        $stmt->execute([':d' => $doneDays]);
        $deleted['done'] += $stmt->rowCount();
    } while ($stmt->rowCount() === 10000);

    $stmt = $db->prepare(
        "DELETE FROM queue_jobs
          WHERE job_type LIKE 'ifood.%' AND status = 'dead'
            AND updated_at < DATE_SUB(NOW(), INTERVAL :d DAY)
          LIMIT 10000"
    );
    do {
        $stmt->execute([':d' => $deadDays]);
        $deleted['dead'] += $stmt->rowCount();
    } while ($stmt->rowCount() === 10000);

    // Reusa o purgeOlderThan que já existe para logs
    $logger = new IFoodApiLogger($db);
    $deleted['api_logs'] = $logger->purgeOlderThan($logsDays);
} catch (\Throwable $e) {
    echo $ts() . " ERRO durante cleanup: {$e->getMessage()}\n";
    exit(1);
}

echo $ts() . sprintf(
    " deletados: done=%d dead=%d api_logs=%d\n",
    $deleted['done'],
    $deleted['dead'],
    $deleted['api_logs']
);
