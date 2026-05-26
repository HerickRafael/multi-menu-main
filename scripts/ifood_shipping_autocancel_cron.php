#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * iFood Shipping Orders — Auto-Cancel Cron
 *
 * Cancela shipping orders travados que nunca progrediram para entrega.
 * Cobre cenários onde o pedido fica em SUBMITTED/ACCEPTED/CONFIRMED/PICKED_UP
 * por tempo demais sem chegar a DELIVERED — provavelmente entregador
 * abandonou, sistema do iFood teve problema, ou pedido foi esquecido.
 *
 * Threshold por estado (env-overridable):
 *   SUBMITTED_TIMEOUT_HOURS    (default 1)  — aceitou mas não confirmou
 *   ACCEPTED_TIMEOUT_HOURS     (default 2)  — confirmou mas sem entregador
 *   CONFIRMED_TIMEOUT_HOURS    (default 2)  — entregador atribuído sem retirar
 *   PICKED_UP_TIMEOUT_HOURS    (default 3)  — pegou mas não entregou
 *
 * Não cancela rows com retries >= MAX_RETRIES (default 5) — provavelmente
 * tem causa estrutural; um operador precisa olhar.
 *
 * Cron sugerido (a cada 30 min):
 *   *\/30 * * * * php /path/to/scripts/ifood_shipping_autocancel_cron.php >> /var/log/ifood_shipping_autocancel.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/ShippingDispatcher.php';

use App\Services\IFood\ShippingDispatcher;

$thresholds = [
    'SUBMITTED' => max(1, (int) (getenv('SUBMITTED_TIMEOUT_HOURS') ?: '1')),
    'ACCEPTED'  => max(1, (int) (getenv('ACCEPTED_TIMEOUT_HOURS')  ?: '2')),
    'CONFIRMED' => max(1, (int) (getenv('CONFIRMED_TIMEOUT_HOURS') ?: '2')),
    'PICKED_UP' => max(1, (int) (getenv('PICKED_UP_TIMEOUT_HOURS') ?: '3')),
];
$maxRetries = max(1, (int) (getenv('MAX_RETRIES') ?: '5'));
$capPerCompany = max(1, (int) (getenv('CAP_PER_COMPANY') ?: '50'));

$onlyCompanyId = isset($argv[1]) ? (int) $argv[1] : 0;

$db = db();
$ts = static fn(): string => '[' . date('Y-m-d H:i:s') . ']';

echo $ts() . sprintf(
    " auto-cancel: SUBMITTED>%dh ACCEPTED>%dh CONFIRMED>%dh PICKED_UP>%dh max_retries=%d cap=%d\n",
    $thresholds['SUBMITTED'],
    $thresholds['ACCEPTED'],
    $thresholds['CONFIRMED'],
    $thresholds['PICKED_UP'],
    $maxRetries,
    $capPerCompany
);

// Companies com integração ativa
$sql = 'SELECT i.company_id, c.name AS company_name
          FROM ifood_integrations i
          INNER JOIN companies c ON c.id = i.company_id
         WHERE i.is_active = 1';
$bind = [];
if ($onlyCompanyId > 0) {
    $sql .= ' AND i.company_id = ?';
    $bind[] = $onlyCompanyId;
}
$stmt = $db->prepare($sql);
$stmt->execute($bind);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($companies)) {
    echo $ts() . " Nenhuma integração ativa.\n";
    exit(0);
}

$totalDetected = 0;
$totalEnqueued = 0;

foreach ($companies as $comp) {
    $companyId = (int) $comp['company_id'];

    // Cláusula CASE para mapear status -> threshold em horas.
    // Usamos updated_at como referência (último sinal de atividade).
    $whenExprs = [];
    foreach ($thresholds as $status => $hours) {
        $whenExprs[] = "WHEN status = '{$status}' AND updated_at < DATE_SUB(NOW(), INTERVAL {$hours} HOUR) THEN 1";
    }
    $whenClause = implode(' ', $whenExprs);

    $driftSql = "
        SELECT external_reference, status, retries, updated_at
          FROM ifood_shipping_orders
         WHERE company_id = :cid
           AND retries < :max_retries
           AND status IN ('SUBMITTED','ACCEPTED','CONFIRMED','PICKED_UP')
           AND CASE {$whenClause} ELSE 0 END = 1
         ORDER BY updated_at ASC
         LIMIT :cap";

    $stmt = $db->prepare($driftSql);
    $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
    $stmt->bindValue(':max_retries', $maxRetries, PDO::PARAM_INT);
    $stmt->bindValue(':cap', $capPerCompany, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo $ts() . " Company {$companyId} ({$comp['company_name']}): sem candidatos\n";
        continue;
    }

    echo $ts() . " Company {$companyId}: " . count($rows) . " candidato(s) a cancel\n";
    $totalDetected += count($rows);

    foreach ($rows as $row) {
        $ref = (string) $row['external_reference'];
        $cancelResult = ShippingDispatcher::cancelShippingOrder($companyId, $ref, 'auto_cancel_timeout');
        if (!empty($cancelResult['enqueued'])) {
            $totalEnqueued++;
            echo $ts() . "   ↳ ref={$ref} status={$row['status']} updated={$row['updated_at']} → cancel enqueued\n";
        } else {
            echo $ts() . "   ↳ ref={$ref} status={$row['status']} → skip ({$cancelResult['message']})\n";
        }
    }
}

echo $ts() . " Concluído. detectados={$totalDetected} enfileirados={$totalEnqueued}\n";
