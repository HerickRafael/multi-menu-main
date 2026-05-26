#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * iFood worker — consome jobs em queue_jobs onde job_type LIKE 'ifood.%'.
 *
 * Uso típico via cron (a cada minuto):
 *   * * * * * php /var/www/html/scripts/ifood_worker.php --max=100
 *
 * Uso daemon (systemd / supervisor):
 *   php scripts/ifood_worker.php --loop=55
 *
 * Flags:
 *   --max=N      processa até N jobs e sai (default 50)
 *   --loop=SEC   roda em loop por SEC segundos (default off)
 *   --sleep=MS   pausa entre jobs / quando ocioso (default 100/500)
 *   --verbose    imprime stats por iteração
 */

require_once __DIR__ . '/../app/bootstrap.php';

// Carrega classes manualmente (não há autoloader PSR-4 configurado para App\Services)
$base = __DIR__ . '/../app/services/IFood';
require_once $base . '/IFoodEndpoints.php';
require_once $base . '/IFoodApiLogger.php';
require_once $base . '/IFoodResponse.php';
require_once $base . '/IFoodClient.php';
require_once $base . '/IFoodJobHandler.php';
require_once $base . '/IFoodRetryableException.php';
require_once $base . '/IFoodJobDispatcher.php';
require_once $base . '/IFoodJobWorker.php';

use App\Services\IFood\IFoodJobDispatcher;
use App\Services\IFood\IFoodJobWorker;

// ── Parse flags ─────────────────────────────────────────────────────────────

$args = [];
foreach ($argv as $arg) {
    if (preg_match('/^--([a-z]+)(?:=(.*))?$/', $arg, $m)) {
        $args[$m[1]] = $m[2] ?? true;
    }
}
$maxJobs  = isset($args['max']) && is_string($args['max']) ? (int) $args['max'] : 50;
$loopSec  = isset($args['loop']) && is_string($args['loop']) ? (int) $args['loop'] : 0;
$sleepMs  = isset($args['sleep']) && is_string($args['sleep']) ? (int) $args['sleep'] : ($loopSec > 0 ? 500 : 100);
$verbose  = !empty($args['verbose']);

// ── Dispatcher: registra handlers ───────────────────────────────────────────
//
// Handlers serão registrados nas próximas fases. Por enquanto, o dispatcher
// fica vazio e qualquer job ifood.* falhará como "No handler registered" —
// permitindo testar a infra de reserva/retry sem features acopladas.

$dispatcher = new IFoodJobDispatcher();

// Permite que módulos externos registrem seus handlers ao carregar
// app/services/IFood/handlers/bootstrap.php se existir.
$handlersBootstrap = __DIR__ . '/../app/services/IFood/Handlers/bootstrap.php';
if (file_exists($handlersBootstrap)) {
    /** @var IFoodJobDispatcher $dispatcher */
    require $handlersBootstrap;
}

// ── Run ─────────────────────────────────────────────────────────────────────

$db = db();
$worker = new IFoodJobWorker($db, $dispatcher);

$startedAt = date('Y-m-d H:i:s');
if ($verbose) {
    fwrite(STDOUT, "[ifood_worker] started at {$startedAt}\n");
    fwrite(STDOUT, sprintf("[ifood_worker] mode=%s max=%d loop=%ds sleep=%dms\n",
        $loopSec > 0 ? 'loop' : 'batch', $maxJobs, $loopSec, $sleepMs));
}

$stats = $loopSec > 0
    ? $worker->loop($loopSec, $sleepMs)
    : $worker->run($maxJobs, $sleepMs);

$finishedAt = date('Y-m-d H:i:s');
fwrite(STDOUT, sprintf(
    "[ifood_worker] %s → %s: processed=%d succeeded=%d retried=%d dead=%d\n",
    $startedAt,
    $finishedAt,
    $stats['processed'],
    $stats['succeeded'],
    $stats['retried'],
    $stats['dead']
));

exit(0);
