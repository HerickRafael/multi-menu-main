#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smoke test da Central Logística.
 *
 * Cria dados sintéticos cobrindo cenários ativos + SLA breach + alertas,
 * roda o LogisticsDashboardService e valida shape + valores.
 *
 * Cenários montados:
 *   - 1 pedido CONFIRMED há 5 min (dentro SLA cozinha=25) → não alerta
 *   - 1 pedido CONFIRMED há 60 min (kitchen_sla_breach)
 *   - 1 pedido DISPATCHED há 90 min (delivery_sla_breach)
 *   - 1 driver ASSIGNED + picked_up_at → in_route
 *   - 1 driver ASSIGNED sem picked_up_at → waiting
 *   - 1 driver NO_DRIVER há 20 min → no_driver_prolonged
 *   - 1 shipping SUBMITTED há 90 min → shipping_stuck_submitted
 *   - 1 shipping FAILED nas últimas 24h → shipping_failures_24h
 *   - 1 stock drift → stock_drift alert
 *   - Métricas 24h: 3 pedidos recebidos, 1 entregue → calcula taxas
 *
 * Uso: php scripts/ifood_logistics_smoke_test.php [company_id]
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/LogisticsDashboardService.php';

use App\Services\IFood\LogisticsDashboardService;

$db = db();
$companyId = (int) ($argv[1] ?? 1);

echo "→ Smoke test logística — company={$companyId}\n";

$stmt = $db->prepare('SELECT environment, is_active FROM ifood_integrations WHERE company_id = ?');
$stmt->execute([$companyId]);
$integration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$integration || (int) $integration['is_active'] !== 1) {
    fwrite(STDERR, "× company {$companyId} sem integração. Abortando.\n");
    exit(1);
}
$env = $integration['environment'] === 'sandbox' ? 'sandbox' : 'production';

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

// IDs sintéticos com prefixo SMOKE-LOG
$idOk        = 'SMOKE-LOG-OK-' . bin2hex(random_bytes(4));
$idKitchen   = 'SMOKE-LOG-KIT-' . bin2hex(random_bytes(4));
$idDelivery  = 'SMOKE-LOG-DEL-' . bin2hex(random_bytes(4));
$idConcluded = 'SMOKE-LOG-DONE-' . bin2hex(random_bytes(4));
$idDriverIR  = 'SMOKE-LOG-DRV-IR-' . bin2hex(random_bytes(4));
$idDriverWT  = 'SMOKE-LOG-DRV-WT-' . bin2hex(random_bytes(4));
$idDriverND  = 'SMOKE-LOG-DRV-ND-' . bin2hex(random_bytes(4));
$refShipStuck = 'SMOKE-LOG-SHP-STUCK-' . bin2hex(random_bytes(4));
$refShipFail  = 'SMOKE-LOG-SHP-FAIL-' . bin2hex(random_bytes(4));

$cleanup = static function () use ($db, $companyId) {
    $db->prepare("DELETE FROM ifood_orders WHERE ifood_order_id LIKE 'SMOKE-LOG-%'")->execute();
    $db->prepare("DELETE FROM ifood_order_drivers WHERE ifood_order_id LIKE 'SMOKE-LOG-%'")->execute();
    $db->prepare("DELETE FROM ifood_shipping_orders WHERE external_reference LIKE 'SMOKE-LOG-%'")->execute();
    $db->prepare("DELETE FROM ifood_stock_sync_state WHERE company_id=? AND product_id=99999990")->execute([$companyId]);
};
$cleanup(); // garante estado limpo no início

// === Setup pedidos ===
$insOrder = $db->prepare(
    "INSERT INTO ifood_orders
        (company_id, ifood_order_id, ifood_merchant_id, status, items, payments,
         delivered_by, order_type, confirmed_at, dispatched_at, concluded_at, created_at)
     VALUES (?, ?, ?, ?, '[]', '[]', 'MERCHANT', 'DELIVERY', ?, ?, ?, ?)"
);
// CONFIRMED há 5 min — dentro SLA
$insOrder->execute([$companyId, $idOk, 'M', 'CONFIRMED', date('Y-m-d H:i:s', time() - 300), null, null, date('Y-m-d H:i:s', time() - 600)]);
// CONFIRMED há 60 min — kitchen breach
$insOrder->execute([$companyId, $idKitchen, 'M', 'CONFIRMED', date('Y-m-d H:i:s', time() - 3600), null, null, date('Y-m-d H:i:s', time() - 3700)]);
// DISPATCHED há 90 min — delivery breach
$insOrder->execute([$companyId, $idDelivery, 'M', 'DISPATCHED', date('Y-m-d H:i:s', time() - 5500), date('Y-m-d H:i:s', time() - 5400), null, date('Y-m-d H:i:s', time() - 5700)]);
// CONCLUDED dentro 24h — conta como completed
$insOrder->execute([$companyId, $idConcluded, 'M', 'CONCLUDED', date('Y-m-d H:i:s', time() - 7200), date('Y-m-d H:i:s', time() - 6900), date('Y-m-d H:i:s', time() - 6300), date('Y-m-d H:i:s', time() - 7500)]);

// === Setup drivers ===
$insDrv = $db->prepare(
    "INSERT INTO ifood_order_drivers
        (company_id, environment, ifood_order_id, request_status, picked_up_at, requested_at, updated_at, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$now = date('Y-m-d H:i:s');
$h2ago = date('Y-m-d H:i:s', time() - 7200);
// in_route: ASSIGNED + picked_up_at
$insDrv->execute([$companyId, $env, $idDriverIR, 'ASSIGNED', $now, $now, $now, $h2ago]);
// waiting: ASSIGNED sem picked_up_at
$insDrv->execute([$companyId, $env, $idDriverWT, 'ASSIGNED', null, $now, $now, $h2ago]);
// NO_DRIVER há 20 min
$insDrv->execute([$companyId, $env, $idDriverND, 'NO_DRIVER', null, null, date('Y-m-d H:i:s', time() - 1200), $h2ago]);

// === Setup shipping ===
$insShp = $db->prepare(
    "INSERT INTO ifood_shipping_orders
        (company_id, environment, external_reference, ifood_shipping_id, status, request_payload, submitted_at, updated_at, created_at)
     VALUES (?, ?, ?, ?, ?, '{}', ?, ?, ?)"
);
// SUBMITTED há 90 min → stuck (threshold default 60 min)
$insShp->execute([$companyId, $env, $refShipStuck, 'SHP-' . bin2hex(random_bytes(4)), 'SUBMITTED', date('Y-m-d H:i:s', time() - 5400), date('Y-m-d H:i:s', time() - 5400), date('Y-m-d H:i:s', time() - 5500)]);
// FAILED nas últimas 24h
$insShp->execute([$companyId, $env, $refShipFail, 'SHP-' . bin2hex(random_bytes(4)), 'FAILED', null, $h2ago, $h2ago]);

// === Setup stock drift ===
$db->prepare(
    "INSERT INTO ifood_stock_sync_state
        (company_id, environment, product_id, ifood_product_id, desired_status, last_synced_status, last_synced_at)
     VALUES (?, ?, 99999990, 'STK-DRIFT', 'AVAILABLE', 'UNAVAILABLE', NOW())"
)->execute([$companyId, $env]);

// === Roda o service ===
$service = new LogisticsDashboardService($db);
$dash = $service->dashboard($companyId);

// === Asserts ===
$check('dashboard tem keys principais',
    isset($dash['summary'], $dash['active'], $dash['metrics_24h'], $dash['alerts'], $dash['queue_health'], $dash['sla']));

// Summary
$sum = $dash['summary'];
$check(
    'summary.ifood_orders_active >= 3',
    (int) ($sum['ifood_orders_active'] ?? 0) >= 3,
    'got=' . (int) ($sum['ifood_orders_active'] ?? 0)
);
$check(
    'summary.drivers_in_route == 1',
    (int) ($sum['drivers_in_route'] ?? -1) === 1,
    'got=' . (int) ($sum['drivers_in_route'] ?? -1)
);
$check(
    'summary.drivers_waiting == 1',
    (int) ($sum['drivers_waiting'] ?? -1) === 1,
    'got=' . (int) ($sum['drivers_waiting'] ?? -1)
);
$check(
    'summary.shipping_active >= 1',
    (int) ($sum['shipping_active'] ?? 0) >= 1
);
$check(
    'summary.stock_drift >= 1',
    (int) ($sum['stock_drift'] ?? 0) >= 1
);

// Active
$active = $dash['active'];
$activeIds = array_column($active['ifood'] ?? [], 'ifood_order_id');
$check('active.ifood contém kitchen breach', in_array($idKitchen, $activeIds, true));
$check('active.ifood contém delivery breach', in_array($idDelivery, $activeIds, true));
$check('active.ifood NÃO contém CONCLUDED', !in_array($idConcluded, $activeIds, true));

$kitchenRow = null;
foreach ($active['ifood'] ?? [] as $r) {
    if ($r['ifood_order_id'] === $idKitchen) { $kitchenRow = $r; break; }
}
$check('kitchen breach row tem sla_breach=true', $kitchenRow !== null && $kitchenRow['sla_breach'] === true);

$okRow = null;
foreach ($active['ifood'] ?? [] as $r) {
    if ($r['ifood_order_id'] === $idOk) { $okRow = $r; break; }
}
$check('CONFIRMED 5min row tem sla_breach=false', $okRow !== null && $okRow['sla_breach'] === false);

// Metrics
$metrics = $dash['metrics_24h'];
$check('metrics tem window_hours=24', ($metrics['window_hours'] ?? 0) === 24);
$check(
    'metrics.orders_received >= 4',
    (int) ($metrics['orders_received'] ?? 0) >= 4,
    'got=' . (int) ($metrics['orders_received'] ?? 0)
);
$check(
    'metrics.orders_completed >= 1',
    (int) ($metrics['orders_completed'] ?? 0) >= 1
);
$check(
    'metrics.no_driver_events >= 1',
    (int) ($metrics['no_driver_events'] ?? 0) >= 1
);

// Alerts
$alertTypes = array_column($dash['alerts'] ?? [], 'type');
$check('alerts contém kitchen_sla_breach', in_array('kitchen_sla_breach', $alertTypes, true));
$check('alerts contém delivery_sla_breach', in_array('delivery_sla_breach', $alertTypes, true));
$check('alerts contém no_driver_prolonged', in_array('no_driver_prolonged', $alertTypes, true));
$check('alerts contém shipping_stuck_submitted', in_array('shipping_stuck_submitted', $alertTypes, true));
$check('alerts contém shipping_failures_24h', in_array('shipping_failures_24h', $alertTypes, true));
$check('alerts contém stock_drift', in_array('stock_drift', $alertTypes, true));

// Queue health (não verifica valores específicos — cross-company; só tem que ter as keys)
$qh = $dash['queue_health'];
$check('queue_health tem keys', isset($qh['pending'], $qh['processing'], $qh['retrying'], $qh['dead'], $qh['dead_24h']));

// SLA thresholds presentes
$check('sla thresholds presentes', isset($dash['sla']['kitchen_minutes'], $dash['sla']['delivery_minutes']));

echo "\n  Total: {$passed} ok, {$failed} falha(s)\n";

$cleanup();
echo "→ Smoke test concluído. Estado limpo.\n";
exit($failed === 0 ? 0 : 1);
