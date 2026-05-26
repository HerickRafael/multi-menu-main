#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * iFood Driver Auto-Resubmit Cron
 *
 * Detecta solicitações de entregador travadas e re-enfileira para o worker
 * tentar novamente. Cobre dois cenários:
 *
 *   1) request_status='NO_DRIVER' e updated_at < NOW - NO_DRIVER_RETRY_MIN min
 *      → re-enfileira (a fila de entregadores oscila; tentar de novo costuma
 *         resolver). Cap em consecutive failures via retries < MAX_RETRIES.
 *
 *   2) request_status='REQUESTED' há mais de REQUESTED_TIMEOUT_MIN min sem
 *      assigned_at → re-enfileira (assignment não chegou). Pode indicar que
 *      o evento DRIVER_ASSIGNED se perdeu ou nunca veio.
 *
 * Ignora COMPLETED/CANCELLED/FAILED (estados terminais) e PENDING (job ainda
 * está na fila, não precisa de re-push).
 *
 * Coalescing: usa DriverRequestDispatcher (dedup_key garante 1 job por pedido).
 *
 * Cron sugerido (a cada 5 min):
 *   *\/5 * * * * php /path/to/scripts/ifood_driver_resubmit_cron.php >> /var/log/ifood_driver_resubmit.log 2>&1
 *
 * Tuning via env:
 *   NO_DRIVER_RETRY_MIN     (default: 5)  — quanto esperar antes de retry NO_DRIVER
 *   REQUESTED_TIMEOUT_MIN   (default: 15) — quanto esperar antes de retry REQUESTED sem assignment
 *   MAX_RETRIES             (default: 10) — não re-enfileirar acima disso
 *   CAP_PER_COMPANY         (default: 100)
 */

if (php_sapi_name() !== 'cli') {
    die("Este script só pode ser executado via linha de comando.\n");
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/IFoodEndpoints.php';
require_once __DIR__ . '/../app/services/IFood/DriverRequestDispatcher.php';

use App\Services\IFood\IFoodEndpoints;
use App\Services\IFood\DriverRequestDispatcher;

$noDriverRetryMin   = max(1, (int) (getenv('NO_DRIVER_RETRY_MIN')   ?: '5'));
$requestedTimeout   = max(1, (int) (getenv('REQUESTED_TIMEOUT_MIN') ?: '15'));
$maxRetries         = max(1, (int) (getenv('MAX_RETRIES')           ?: '10'));
$capPerCompany      = max(1, (int) (getenv('CAP_PER_COMPANY')       ?: '100'));

$onlyCompanyId = isset($argv[1]) ? (int) $argv[1] : 0;

$db = db();
$ts = static fn(): string => '[' . date('Y-m-d H:i:s') . ']';

echo $ts() . sprintf(
    " Iniciando resubmit driver (no_driver>=%dm, requested>=%dm, max_retries=%d, cap=%d)\n",
    $noDriverRetryMin,
    $requestedTimeout,
    $maxRetries,
    $capPerCompany
);

$sql = 'SELECT i.company_id, i.environment, c.name AS company_name
          FROM ifood_integrations i
          INNER JOIN companies c ON c.id = i.company_id
         WHERE i.is_active = 1';
$bind = [];
if ($onlyCompanyId > 0) {
    $sql .= ' AND i.company_id = ?';
    $bind[] = $onlyCompanyId;
}
$sql .= ' ORDER BY i.company_id ASC';

$stmt = $db->prepare($sql);
$stmt->execute($bind);
$integrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($integrations)) {
    echo $ts() . " Nenhuma integração ativa.\n";
    exit(0);
}

$totalEnqueued = 0;
$totalCoalesced = 0;
$totalChecked = 0;

foreach ($integrations as $integration) {
    $companyId = (int) $integration['company_id'];
    $env = IFoodEndpoints::normalizeEnvironment((string) $integration['environment']);

    echo $ts() . " Company {$companyId} ({$integration['company_name']}) env={$env}\n";

    // Casos de drift de driver request.
    $driftSql = "
        SELECT d.ifood_order_id, d.request_status, d.retries, d.requested_at, d.updated_at
          FROM ifood_order_drivers d
          INNER JOIN ifood_orders o
                  ON o.company_id     = d.company_id
                 AND o.ifood_order_id = d.ifood_order_id
         WHERE d.company_id  = :cid
           AND d.environment = :env
           AND d.retries     < :max_retries
           AND o.status NOT IN ('CONCLUDED', 'CANCELLED')
           AND o.delivered_by = 'MERCHANT'
           AND (
                (d.request_status = 'NO_DRIVER' AND d.updated_at < DATE_SUB(NOW(), INTERVAL :no_driver_min MINUTE))
             OR (d.request_status = 'REQUESTED'
                 AND d.assigned_at IS NULL
                 AND d.requested_at IS NOT NULL
                 AND d.requested_at < DATE_SUB(NOW(), INTERVAL :req_timeout_min MINUTE))
           )
         ORDER BY d.updated_at ASC
         LIMIT :cap";

    $stmt = $db->prepare($driftSql);
    $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
    $stmt->bindValue(':env', $env, PDO::PARAM_STR);
    $stmt->bindValue(':max_retries', $maxRetries, PDO::PARAM_INT);
    $stmt->bindValue(':no_driver_min', $noDriverRetryMin, PDO::PARAM_INT);
    $stmt->bindValue(':req_timeout_min', $requestedTimeout, PDO::PARAM_INT);
    $stmt->bindValue(':cap', $capPerCompany, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalChecked += count($rows);
    if (empty($rows)) {
        echo $ts() . "   ↳ sem casos de resubmit\n";
        continue;
    }

    echo $ts() . "   ↳ " . count($rows) . " pedido(s) para resubmit\n";

    $perCompanyEnqueued = 0;
    $perCompanyCoalesced = 0;
    $i = 0;

    foreach ($rows as $row) {
        $orderId = (string) ($row['ifood_order_id'] ?? '');
        if ($orderId === '') {
            continue;
        }
        $delay = $i++; // escalonar para não estourar rate limit
        $ok = DriverRequestDispatcher::requestForOrder($companyId, $orderId, $delay);
        if ($ok) {
            $perCompanyEnqueued++;
        } else {
            $perCompanyCoalesced++;
        }
    }

    echo $ts() . sprintf(
        "   ↳ enfileirados=%d coalescidos=%d\n",
        $perCompanyEnqueued,
        $perCompanyCoalesced
    );

    $totalEnqueued += $perCompanyEnqueued;
    $totalCoalesced += $perCompanyCoalesced;
}

echo $ts() . sprintf(
    " Concluído. companies=%d, candidatos=%d, enfileirados=%d, coalescidos=%d\n",
    count($integrations),
    $totalChecked,
    $totalEnqueued,
    $totalCoalesced
);
