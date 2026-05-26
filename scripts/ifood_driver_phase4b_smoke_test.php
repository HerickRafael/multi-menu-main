#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smoke test da Fase 4B (webhook driver events + cancel + auto-resubmit cron).
 *
 * Cobre:
 *   1) IFoodService::processWebhookEvent('DISPATCHED' com driver no metadata)
 *      → ifood_order_drivers populado com driver_id/name/phone, status=ASSIGNED.
 *   2) Evento PICKED_UP → atualiza picked_up_at (mantém ASSIGNED).
 *   3) Evento CONCLUDED → status=COMPLETED, delivered_at populado.
 *   4) Evento sem driver e order não-existente → no-op (sem exception).
 *   5) DriverRequestDispatcher::cancelForOrder skipa quando estado terminal.
 *   6) cancelForOrder enfileira corretamente quando estado é REQUESTED.
 *   7) DriverCancelHandler com estado terminal → skip silencioso.
 *   8) Cron de resubmit: ignora pedidos NO_DRIVER recém-atualizados (dentro
 *      do threshold), enfileira os mais velhos.
 *
 * Uso: php scripts/ifood_driver_phase4b_smoke_test.php [company_id]
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFoodService.php';
require_once __DIR__ . '/../app/services/IFood/IFoodEndpoints.php';
require_once __DIR__ . '/../app/services/IFood/DriverRequestDispatcher.php';
require_once __DIR__ . '/../app/services/IFood/Handlers/DriverCancelHandler.php';
require_once __DIR__ . '/../app/services/IFood/IFoodApiLogger.php';
require_once __DIR__ . '/../app/models/QueueJob.php';

use App\Services\IFood\IFoodEndpoints;
use App\Services\IFood\DriverRequestDispatcher;
use App\Services\IFood\Handlers\DriverCancelHandler;
use App\Services\IFood\IFoodApiLogger;

$db = db();
$companyId = (int) ($argv[1] ?? 1);

echo "→ Smoke test Fase 4B — company={$companyId}\n";

$stmt = $db->prepare('SELECT environment, is_active FROM ifood_integrations WHERE company_id = ?');
$stmt->execute([$companyId]);
$integration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$integration || (int) $integration['is_active'] !== 1) {
    fwrite(STDERR, "× company {$companyId} sem integração iFood ativa. Abortando.\n");
    exit(1);
}
$env = IFoodEndpoints::normalizeEnvironment((string) $integration['environment']);
echo "  ↳ ambiente: {$env}\n";

$orderIdA = 'SMOKE-4B-A-' . bin2hex(random_bytes(4));
$orderIdB = 'SMOKE-4B-B-' . bin2hex(random_bytes(4));
$orderIdC = 'SMOKE-4B-C-' . bin2hex(random_bytes(4));
$orderIdD = 'SMOKE-4B-D-' . bin2hex(random_bytes(4)); // p/ cancel
$orderIdE = 'SMOKE-4B-E-' . bin2hex(random_bytes(4)); // resubmit antigo (deve aparecer)
$orderIdF = 'SMOKE-4B-F-' . bin2hex(random_bytes(4)); // resubmit recente (não deve aparecer)
$merchantId = 'SMOKE-MERCHANT';
$jsonEmpty = json_encode([]);

$insertOrder = $db->prepare(
    'INSERT INTO ifood_orders
        (company_id, ifood_order_id, ifood_merchant_id, status, items, payments, delivered_by, order_type)
     VALUES (:cid, :oid, :mid, :st, :it, :pa, :dby, :ot)'
);
foreach ([$orderIdA, $orderIdB, $orderIdC, $orderIdD, $orderIdE, $orderIdF] as $oid) {
    $insertOrder->execute([
        ':cid' => $companyId, ':oid' => $oid, ':mid' => $merchantId,
        ':st' => 'CONFIRMED', ':it' => $jsonEmpty, ':pa' => $jsonEmpty,
        ':dby' => 'MERCHANT', ':ot' => 'DELIVERY',
    ]);
}

$cleanup = static function () use ($db, $companyId, $orderIdA, $orderIdB, $orderIdC, $orderIdD, $orderIdE, $orderIdF) {
    $db->prepare("DELETE FROM queue_jobs WHERE job_type LIKE 'ifood.driver.%' AND company_id=?")
        ->execute([$companyId]);
    $db->prepare("DELETE FROM ifood_order_drivers WHERE company_id=? AND ifood_order_id LIKE 'SMOKE-4B-%'")
        ->execute([$companyId]);
    $ids = [$orderIdA, $orderIdB, $orderIdC, $orderIdD, $orderIdE, $orderIdF];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $db->prepare("DELETE FROM ifood_orders WHERE ifood_order_id IN ({$placeholders})")
        ->execute($ids);
    // Limpa logs de eventos do smoke test (event_id começa com SMOKE-EVT)
    $db->prepare("DELETE FROM ifood_events_log WHERE event_id LIKE 'SMOKE-EVT-%' OR ifood_order_id LIKE 'SMOKE-4B-%'")
        ->execute();
};

$passed = 0;
$failed = 0;
$check = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    echo '  ' . ($ok ? '✓ ' : '× ') . $label . "\n";
    $ok ? $passed++ : $failed++;
};

$service = new IFoodService($db, $companyId);

// 1) Evento DISPATCHED com driver no metadata
$service->processWebhookEvent([
    'id' => 'SMOKE-EVT-' . bin2hex(random_bytes(4)),
    'orderId' => $orderIdA,
    'merchantId' => $merchantId,
    'fullCode' => 'DISPATCHED',
    'code' => 'DSP',
    'metadata' => [
        'driver' => [
            'id' => 'DRV-001',
            'name' => 'João Entregador',
            'phone' => '+5511999999999',
            'vehicle' => ['type' => 'MOTORCYCLE'],
        ],
    ],
]);
$stmt = $db->prepare(
    'SELECT request_status, driver_id, driver_name, driver_phone, vehicle_type, assigned_at
       FROM ifood_order_drivers WHERE company_id=? AND ifood_order_id=?'
);
$stmt->execute([$companyId, $orderIdA]);
$state = $stmt->fetch(PDO::FETCH_ASSOC);
$check('DISPATCHED com driver → ASSIGNED + dados do entregador', (bool) $state
    && $state['request_status'] === 'ASSIGNED'
    && $state['driver_id'] === 'DRV-001'
    && $state['driver_name'] === 'João Entregador'
    && $state['vehicle_type'] === 'MOTORCYCLE'
    && $state['assigned_at'] !== null
);

// 2) PICKED_UP → mantém ASSIGNED, popula picked_up_at
$service->processWebhookEvent([
    'id' => 'SMOKE-EVT-' . bin2hex(random_bytes(4)),
    'orderId' => $orderIdA,
    'merchantId' => $merchantId,
    'fullCode' => 'PICKED_UP',
    'code' => 'PKU',
    'metadata' => [],
]);
$stmt->execute([$companyId, $orderIdA]);
$state = $stmt->fetch(PDO::FETCH_ASSOC);
$check('PICKED_UP → mantém ASSIGNED + picked_up_at populado', (bool) $state
    && $state['request_status'] === 'ASSIGNED'
);
$stmt2 = $db->prepare('SELECT picked_up_at FROM ifood_order_drivers WHERE ifood_order_id=?');
$stmt2->execute([$orderIdA]);
$pickedUpAt = $stmt2->fetchColumn();
$check('picked_up_at não-nulo', $pickedUpAt !== null && $pickedUpAt !== false);

// 3) CONCLUDED → COMPLETED + delivered_at
$service->processWebhookEvent([
    'id' => 'SMOKE-EVT-' . bin2hex(random_bytes(4)),
    'orderId' => $orderIdA,
    'merchantId' => $merchantId,
    'fullCode' => 'CONCLUDED',
    'code' => 'CON',
    'metadata' => [],
]);
$stmt->execute([$companyId, $orderIdA]);
$state = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt3 = $db->prepare('SELECT delivered_at FROM ifood_order_drivers WHERE ifood_order_id=?');
$stmt3->execute([$orderIdA]);
$delivered = $stmt3->fetchColumn();
$check('CONCLUDED → COMPLETED + delivered_at', (bool) $state
    && $state['request_status'] === 'COMPLETED'
    && $delivered !== null && $delivered !== false
);

// 4) Evento para pedido inexistente → no-op (sem exception)
$threw = false;
try {
    $service->processWebhookEvent([
        'id' => 'SMOKE-EVT-' . bin2hex(random_bytes(4)),
        'orderId' => 'NONEXISTENT-' . bin2hex(random_bytes(4)),
        'merchantId' => $merchantId,
        'fullCode' => 'DISPATCHED',
        'code' => 'DSP',
        'metadata' => ['driver' => ['id' => 'x', 'name' => 'y']],
    ]);
} catch (\Throwable $e) {
    $threw = true;
}
$check('evento driver para pedido inexistente → no-op silencioso', !$threw);

// 5) cancelForOrder skipa estado terminal (COMPLETED)
$ok = DriverRequestDispatcher::cancelForOrder($companyId, $orderIdA, 'test');
$check('cancelForOrder skipa COMPLETED', $ok === false);

// 6) cancelForOrder enfileira quando REQUESTED
$db->prepare(
    "INSERT INTO ifood_order_drivers (company_id, environment, ifood_order_id, request_status, requested_at)
     VALUES (?, ?, ?, 'REQUESTED', NOW())"
)->execute([$companyId, $env, $orderIdD]);
$ok = DriverRequestDispatcher::cancelForOrder($companyId, $orderIdD, 'test_cancel');
$check('cancelForOrder enfileira para REQUESTED', $ok === true);

$dedupCancel = sprintf('ifood.driver-cancel:%d:%s:%s', $companyId, $env, $orderIdD);
$stmtJ = $db->prepare("SELECT COUNT(*) FROM queue_jobs WHERE dedup_key=? AND status IN ('pending','retrying')");
$stmtJ->execute([$dedupCancel]);
$check('1 job pending de cancel', (int) $stmtJ->fetchColumn() === 1);

// 7) DriverCancelHandler com estado terminal → skip
$db->prepare("UPDATE ifood_order_drivers SET request_status='CANCELLED' WHERE ifood_order_id=?")
    ->execute([$orderIdD]);
$handler = new DriverCancelHandler($db, new IFoodApiLogger($db));
$threw = false;
try {
    $handler->handle(
        ['id' => 0, 'company_id' => $companyId, 'job_type' => 'ifood.driver.cancel', 'attempts' => 1, 'max_attempts' => 5],
        ['ifood_order_id' => $orderIdD, 'environment' => $env, 'reason' => 'test']
    );
} catch (\Throwable $e) {
    $threw = true;
}
$check('cancel handler skipa estado terminal (sem exception)', !$threw);

// 8) Cron de resubmit — setup
//   E = REQUESTED há 1h sem assigned_at → DEVE aparecer
//   F = NO_DRIVER atualizado AGORA → NÃO deve aparecer (dentro do threshold)
$db->prepare(
    "INSERT INTO ifood_order_drivers (company_id, environment, ifood_order_id, request_status, requested_at, updated_at)
     VALUES (?, ?, ?, 'REQUESTED', DATE_SUB(NOW(), INTERVAL 60 MINUTE), DATE_SUB(NOW(), INTERVAL 60 MINUTE))"
)->execute([$companyId, $env, $orderIdE]);
$db->prepare(
    "INSERT INTO ifood_order_drivers (company_id, environment, ifood_order_id, request_status, updated_at)
     VALUES (?, ?, ?, 'NO_DRIVER', NOW())"
)->execute([$companyId, $env, $orderIdF]);

// Limpa jobs antes para deixar a contagem clara
$db->prepare("DELETE FROM queue_jobs WHERE job_type='ifood.driver.request' AND company_id=?")
    ->execute([$companyId]);

// Roda o cron (carrega o script — ele usa argv para company filter)
$_SERVER['argv'] = ['ifood_driver_resubmit_cron.php', (string) $companyId];
$_SERVER['argc'] = 2;
$GLOBALS['argv'] = $_SERVER['argv'];
$GLOBALS['argc'] = $_SERVER['argc'];

ob_start();
require __DIR__ . '/ifood_driver_resubmit_cron.php';
$cronOutput = ob_get_clean();

// Conta quantos jobs novos foram criados
$stmtJ = $db->prepare(
    "SELECT COUNT(*) FROM queue_jobs WHERE job_type='ifood.driver.request' AND company_id=?
      AND payload_json LIKE ?"
);
$stmtJ->execute([$companyId, '%' . $orderIdE . '%']);
$check('cron enfileira REQUESTED travado há 1h', (int) $stmtJ->fetchColumn() === 1);

$stmtJ->execute([$companyId, '%' . $orderIdF . '%']);
$check('cron ignora NO_DRIVER recém atualizado', (int) $stmtJ->fetchColumn() === 0);

echo "\n  Total: {$passed} ok, {$failed} falha(s)\n";

$cleanup();
echo "→ Smoke test concluído. Estado limpo.\n";
exit($failed === 0 ? 0 : 1);
