#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smoke test da Fase 5B (quote stub + webhook shipping + auto-cancel).
 *
 * Cobre:
 *   1) Quote rejeita payload sem pickup/delivery.
 *   2) Quote retorna http_status do iFood (sandbox provavelmente 4xx — sem
 *      credenciais reais quote falha, mas tem que retornar estruturado).
 *   3) Webhook: evento DISPATCHED com orderId = ifood_shipping_id atualiza
 *      ifood_shipping_orders.status para CONFIRMED.
 *   4) Webhook: PICKED_UP → PICKED_UP + picked_up_at populado.
 *   5) Webhook: CONCLUDED → DELIVERED + delivered_at + next_poll_at=NULL.
 *   6) Webhook: evento para shipping_id inexistente → no-op (sem exception).
 *   7) Webhook: evento atrasado (CONFIRMED) chega após DELIVERED → ignorado
 *      (não faz downgrade).
 *   8) Auto-cancel: row SUBMITTED há 2h → enfileira cancel.
 *   9) Auto-cancel: row CONFIRMED há 30min → não enfileira (dentro threshold).
 *  10) Auto-cancel: row PICKED_UP com retries >= MAX_RETRIES → skipa.
 *
 * Uso: php scripts/ifood_shipping_phase5b_smoke_test.php [company_id]
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFoodService.php';
require_once __DIR__ . '/../app/services/IFood/IFoodEndpoints.php';
require_once __DIR__ . '/../app/services/IFood/ShippingDispatcher.php';
require_once __DIR__ . '/../app/models/QueueJob.php';

use App\Services\IFood\IFoodEndpoints;
use App\Services\IFood\ShippingDispatcher;

$db = db();
$companyId = (int) ($argv[1] ?? 1);

echo "→ Smoke test 5B — company={$companyId}\n";

$stmt = $db->prepare('SELECT environment, is_active FROM ifood_integrations WHERE company_id = ?');
$stmt->execute([$companyId]);
$integration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$integration || (int) $integration['is_active'] !== 1) {
    fwrite(STDERR, "× company {$companyId} sem integração iFood ativa. Abortando.\n");
    exit(1);
}
$env = IFoodEndpoints::normalizeEnvironment((string) $integration['environment']);
echo "  ↳ ambiente: {$env}\n";

$passed = 0;
$failed = 0;
$check = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    echo '  ' . ($ok ? '✓ ' : '× ') . $label . "\n";
    $ok ? $passed++ : $failed++;
};

$cleanup = static function () use ($db, $companyId) {
    $db->prepare("DELETE FROM queue_jobs WHERE job_type LIKE 'ifood.shipping.%' AND company_id=?")
        ->execute([$companyId]);
    $db->prepare("DELETE FROM ifood_shipping_orders WHERE company_id=? AND external_reference LIKE 'SMOKE-5B-%'")
        ->execute([$companyId]);
    $db->prepare("DELETE FROM ifood_events_log WHERE event_id LIKE 'SMOKE-5B-EVT-%'")
        ->execute();
};

// 1) Quote sem pickup/delivery → fail
$r = ShippingDispatcher::quoteShippingOrder($companyId, ['items' => []]);
$check('quote sem pickup/delivery → fail', $r['ok'] === false && str_contains((string) $r['message'], 'pickup'));

// 2) Quote com pickup+delivery mas sem credenciais sandbox válidas → estruturado mesmo falhando
$r = ShippingDispatcher::quoteShippingOrder($companyId, [
    'pickup' => ['addressLine' => 'A'],
    'delivery' => ['addressLine' => 'B'],
    'items' => [['name' => 'x', 'quantity' => 1, 'unitPrice' => 10]],
]);
// O resultado depende das credenciais — mas a estrutura tem que estar correta.
$check('quote retorna estrutura {ok, quote, http_status, message}',
    array_keys($r) === ['ok', 'quote', 'http_status', 'message']);

// Setup para webhook tests: 1 shipping order PENDING marcado como SUBMITTED com ifood_shipping_id
$ref1 = 'SMOKE-5B-1-' . bin2hex(random_bytes(4));
$ifoodId1 = 'SHIP-' . bin2hex(random_bytes(6));
$db->prepare(
    "INSERT INTO ifood_shipping_orders
        (company_id, environment, external_reference, ifood_shipping_id, status, request_payload, submitted_at, next_poll_at)
     VALUES (?, ?, ?, ?, 'SUBMITTED', '{}', NOW(), DATE_ADD(NOW(), INTERVAL 30 SECOND))"
)->execute([$companyId, $env, $ref1, $ifoodId1]);

$service = new IFoodService($db, $companyId);

// 3) DISPATCHED → CONFIRMED
$service->processWebhookEvent([
    'id' => 'SMOKE-5B-EVT-' . bin2hex(random_bytes(4)),
    'orderId' => $ifoodId1,
    'merchantId' => 'SMOKE-MERCHANT',
    'fullCode' => 'DISPATCHED',
    'code' => 'DSP',
    'metadata' => [],
]);
$stmt = $db->prepare('SELECT status, accepted_at FROM ifood_shipping_orders WHERE external_reference=?');
$stmt->execute([$ref1]);
$state = $stmt->fetch(PDO::FETCH_ASSOC);
$check('DISPATCHED → CONFIRMED + accepted_at', (bool) $state && $state['status'] === 'CONFIRMED' && $state['accepted_at'] !== null);

// 4) PICKED_UP
$service->processWebhookEvent([
    'id' => 'SMOKE-5B-EVT-' . bin2hex(random_bytes(4)),
    'orderId' => $ifoodId1,
    'merchantId' => 'SMOKE-MERCHANT',
    'fullCode' => 'PICKED_UP',
    'code' => 'PKU',
    'metadata' => [],
]);
$stmt->execute([$ref1]);
$state = $stmt->fetch(PDO::FETCH_ASSOC);
$stmtP = $db->prepare('SELECT picked_up_at FROM ifood_shipping_orders WHERE external_reference=?');
$stmtP->execute([$ref1]);
$pickedAt = $stmtP->fetchColumn();
$check('PICKED_UP → status + picked_up_at', $state['status'] === 'PICKED_UP' && $pickedAt !== null && $pickedAt !== false);

// 5) CONCLUDED → DELIVERED + delivered_at + next_poll_at=NULL
$service->processWebhookEvent([
    'id' => 'SMOKE-5B-EVT-' . bin2hex(random_bytes(4)),
    'orderId' => $ifoodId1,
    'merchantId' => 'SMOKE-MERCHANT',
    'fullCode' => 'CONCLUDED',
    'code' => 'CON',
    'metadata' => [],
]);
$stmt2 = $db->prepare('SELECT status, delivered_at, next_poll_at FROM ifood_shipping_orders WHERE external_reference=?');
$stmt2->execute([$ref1]);
$state = $stmt2->fetch(PDO::FETCH_ASSOC);
$check('CONCLUDED → DELIVERED + delivered_at + next_poll_at=NULL',
    $state['status'] === 'DELIVERED' && $state['delivered_at'] !== null && $state['next_poll_at'] === null);

// 6) Evento para shipping_id inexistente → no-op
$threw = false;
try {
    $service->processWebhookEvent([
        'id' => 'SMOKE-5B-EVT-' . bin2hex(random_bytes(4)),
        'orderId' => 'NONEXISTENT-' . bin2hex(random_bytes(4)),
        'merchantId' => 'SMOKE-MERCHANT',
        'fullCode' => 'PICKED_UP',
        'code' => 'PKU',
        'metadata' => [],
    ]);
} catch (\Throwable $e) {
    $threw = true;
}
$check('evento para shipping_id inexistente → no-op', !$threw);

// 7) Evento atrasado CONFIRMED após DELIVERED → não faz downgrade
$service->processWebhookEvent([
    'id' => 'SMOKE-5B-EVT-' . bin2hex(random_bytes(4)),
    'orderId' => $ifoodId1,
    'merchantId' => 'SMOKE-MERCHANT',
    'fullCode' => 'DISPATCHED',
    'code' => 'DSP',
    'metadata' => [],
]);
$stmt2->execute([$ref1]);
$state = $stmt2->fetch(PDO::FETCH_ASSOC);
$check('evento atrasado não rebaixa DELIVERED', $state['status'] === 'DELIVERED');

// Auto-cancel setup: 3 rows com idades e estados diferentes
$ref2 = 'SMOKE-5B-2-' . bin2hex(random_bytes(4));  // SUBMITTED há 2h → cancela
$ref3 = 'SMOKE-5B-3-' . bin2hex(random_bytes(4));  // CONFIRMED há 30min → ignora
$ref4 = 'SMOKE-5B-4-' . bin2hex(random_bytes(4));  // PICKED_UP há 5h com max retries → skipa
$ifoodId2 = 'SHIP-' . bin2hex(random_bytes(6));
$ifoodId3 = 'SHIP-' . bin2hex(random_bytes(6));
$ifoodId4 = 'SHIP-' . bin2hex(random_bytes(6));

$ins = $db->prepare(
    "INSERT INTO ifood_shipping_orders
        (company_id, environment, external_reference, ifood_shipping_id, status, request_payload, submitted_at, updated_at, retries)
     VALUES (?, ?, ?, ?, ?, '{}', NOW(), ?, ?)"
);
$ins->execute([$companyId, $env, $ref2, $ifoodId2, 'SUBMITTED', date('Y-m-d H:i:s', time() - 7200), 0]);
$ins->execute([$companyId, $env, $ref3, $ifoodId3, 'CONFIRMED', date('Y-m-d H:i:s', time() - 1800), 0]);
$ins->execute([$companyId, $env, $ref4, $ifoodId4, 'PICKED_UP', date('Y-m-d H:i:s', time() - 18000), 10]);

// Clean jobs antes
$db->prepare("DELETE FROM queue_jobs WHERE job_type='ifood.shipping.cancel' AND company_id=?")
    ->execute([$companyId]);

// Roda o auto-cancel cron via require (consome argv[1] = company_id)
$_SERVER['argv'] = ['ifood_shipping_autocancel_cron.php', (string) $companyId];
$_SERVER['argc'] = 2;
$GLOBALS['argv'] = $_SERVER['argv'];
$GLOBALS['argc'] = $_SERVER['argc'];

ob_start();
require __DIR__ . '/ifood_shipping_autocancel_cron.php';
$cronOut = ob_get_clean();

$stmtJ = $db->prepare(
    "SELECT COUNT(*) FROM queue_jobs WHERE job_type='ifood.shipping.cancel' AND company_id=?
      AND payload_json LIKE ?"
);
// 8) SUBMITTED 2h → cancela
$stmtJ->execute([$companyId, '%' . $ref2 . '%']);
$check('auto-cancel: SUBMITTED >1h enfileira', (int) $stmtJ->fetchColumn() === 1);
// 9) CONFIRMED 30min → não
$stmtJ->execute([$companyId, '%' . $ref3 . '%']);
$check('auto-cancel: CONFIRMED 30min não enfileira', (int) $stmtJ->fetchColumn() === 0);
// 10) PICKED_UP retries=10 (>= MAX_RETRIES=5) → skipa
$stmtJ->execute([$companyId, '%' . $ref4 . '%']);
$check('auto-cancel: retries >= MAX_RETRIES skipa', (int) $stmtJ->fetchColumn() === 0);

echo "\n  Total: {$passed} ok, {$failed} falha(s)\n";

$cleanup();
echo "→ Smoke test concluído. Estado limpo.\n";
exit($failed === 0 ? 0 : 1);
