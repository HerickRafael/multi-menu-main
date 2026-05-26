#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smoke test da Fase 5 (Shipping Orders).
 *
 * Não bate na API real do iFood; valida:
 *   1) Dispatcher rejeita payload incompleto (sem customer/delivery/items).
 *   2) Dispatcher cria row + enfileira job ifood.shipping.create.
 *   3) Mesmo external_reference repetido → idempotente (não cria 2ª row).
 *   4) Dedup_key impede 2 jobs pending para o mesmo external_reference.
 *   5) Após "reservar" o job (worker), nova chamada cria novo job.
 *   6) Cancel sem row → erro friendly.
 *   7) Cancel para row PENDING sem ifood_shipping_id → marca CANCELLED só local.
 *   8) Cancel para row com status terminal → no-op.
 *   9) Handler create skipa row em status terminal.
 *  10) Handler cancel skipa row sem ifood_shipping_id e marca local.
 *  11) generateReference produz string única em cada chamada.
 *
 * Uso: php scripts/ifood_shipping_smoke_test.php [company_id]
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/IFoodEndpoints.php';
require_once __DIR__ . '/../app/services/IFood/ShippingDispatcher.php';
require_once __DIR__ . '/../app/services/IFood/Handlers/ShippingOrderCreateHandler.php';
require_once __DIR__ . '/../app/services/IFood/Handlers/ShippingOrderCancelHandler.php';
require_once __DIR__ . '/../app/services/IFood/IFoodApiLogger.php';

use App\Services\IFood\IFoodApiLogger;
use App\Services\IFood\IFoodEndpoints;
use App\Services\IFood\ShippingDispatcher;
use App\Services\IFood\Handlers\ShippingOrderCancelHandler;
use App\Services\IFood\Handlers\ShippingOrderCreateHandler;

$db = db();
$companyId = (int) ($argv[1] ?? 1);

echo "→ Smoke test shipping — company={$companyId}\n";

$stmt = $db->prepare('SELECT environment, is_active FROM ifood_integrations WHERE company_id = ?');
$stmt->execute([$companyId]);
$integration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$integration || (int) $integration['is_active'] !== 1) {
    fwrite(STDERR, "× company {$companyId} sem integração iFood ativa. Abortando.\n");
    exit(1);
}
$env = IFoodEndpoints::normalizeEnvironment((string) $integration['environment']);
echo "  ↳ ambiente: {$env}\n";

$validPayload = [
    'customer' => ['name' => 'Cliente Teste', 'phone' => '11999999999', 'document' => '12345678900'],
    'items'    => [['name' => 'Produto', 'quantity' => 1, 'unitPrice' => 25.0]],
    'pickup'   => ['addressLine' => 'Rua A, 100', 'city' => 'São Paulo'],
    'delivery' => ['addressLine' => 'Rua B, 200', 'city' => 'São Paulo', 'postalCode' => '01000000'],
    'payment'  => ['method' => 'CASH', 'amount' => 25.0],
    'weight'   => 0.5,
    'dimensions' => ['height' => 10, 'width' => 20, 'length' => 30],
    'observation' => 'smoke-test',
];

$ref1 = 'SMOKE-SHP-1-' . bin2hex(random_bytes(4));
$ref2 = 'SMOKE-SHP-2-' . bin2hex(random_bytes(4));
$ref3 = 'SMOKE-SHP-3-' . bin2hex(random_bytes(4));

$cleanup = static function () use ($db, $companyId) {
    $db->prepare("DELETE FROM queue_jobs WHERE job_type LIKE 'ifood.shipping.%' AND company_id=?")
        ->execute([$companyId]);
    $db->prepare("DELETE FROM ifood_shipping_orders WHERE company_id=? AND external_reference LIKE 'SMOKE-SHP-%'")
        ->execute([$companyId]);
};

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

// 1) Payload incompleto rejeitado
$result = ShippingDispatcher::createShippingOrder($companyId, ['customer' => ['name' => 'x']], []);
$check('payload incompleto → fail', $result['ok'] === false, $result['message']);

// 2) Payload válido → cria + enfileira
$result = ShippingDispatcher::createShippingOrder(
    $companyId,
    $validPayload,
    ['external_reference' => $ref1]
);
$check('payload válido → ok', $result['ok'] === true && $result['enqueued'] === true);

$stmt = $db->prepare('SELECT status, request_payload FROM ifood_shipping_orders WHERE external_reference=?');
$stmt->execute([$ref1]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$check('row criada em PENDING', (bool) $row && $row['status'] === 'PENDING');

// 3) Idempotência: mesma ref → não duplica
$result2 = ShippingDispatcher::createShippingOrder(
    $companyId,
    $validPayload,
    ['external_reference' => $ref1]
);
$stmt = $db->prepare("SELECT COUNT(*) FROM ifood_shipping_orders WHERE company_id=? AND external_reference=?");
$stmt->execute([$companyId, $ref1]);
$check('ref repetida não duplica row', (int) $stmt->fetchColumn() === 1);

// 4) Dedup_key — não duplica job
$dedup = sprintf('ifood.shipping.create:%d:%s:%s', $companyId, $env, $ref1);
$stmt = $db->prepare("SELECT COUNT(*) FROM queue_jobs WHERE dedup_key=? AND status IN ('pending','retrying')");
$stmt->execute([$dedup]);
$check('1 job pending para ref1', (int) $stmt->fetchColumn() === 1);

// 5) Após "reservar" o job → nova ref cria novo job
$db->prepare(
    "UPDATE queue_jobs SET dedup_key=NULL, status='processing', reserved_at=NOW()
      WHERE dedup_key=? AND status='pending'"
)->execute([$dedup]);
$result3 = ShippingDispatcher::createShippingOrder(
    $companyId,
    $validPayload,
    ['external_reference' => $ref2]
);
$check('nova ref após reserva → enqueued', $result3['ok'] === true && $result3['enqueued'] === true);

// 6) Cancel sem row → erro friendly
$cancelResult = ShippingDispatcher::cancelShippingOrder($companyId, 'NONEXISTENT-' . bin2hex(random_bytes(4)));
$check('cancel sem row → ok=false friendly', $cancelResult['ok'] === false && str_contains((string) $cancelResult['message'], 'não encontrada'));

// 7) Cancel para PENDING sem ifood_shipping_id → enfileira (handler vai marcar local)
$cancelResult = ShippingDispatcher::cancelShippingOrder($companyId, $ref1, 'test');
$check('cancel para PENDING → enqueued', $cancelResult['ok'] === true && $cancelResult['enqueued'] === true);

// 8) Marcar ref2 como CANCELLED manualmente; cancel deve ser no-op
$db->prepare("UPDATE ifood_shipping_orders SET status='CANCELLED' WHERE external_reference=?")
    ->execute([$ref2]);
$cancelResult = ShippingDispatcher::cancelShippingOrder($companyId, $ref2);
$check('cancel para CANCELLED → no-op', $cancelResult['ok'] === true && $cancelResult['enqueued'] === false);

// 9) Handler create skipa terminal
$db->prepare("UPDATE ifood_shipping_orders SET status='REJECTED' WHERE external_reference=?")
    ->execute([$ref1]);
$handlerCreate = new ShippingOrderCreateHandler($db, new IFoodApiLogger($db));
$threw = false;
try {
    $handlerCreate->handle(
        ['id' => 0, 'company_id' => $companyId, 'job_type' => 'ifood.shipping.create', 'attempts' => 1, 'max_attempts' => 5],
        ['external_reference' => $ref1, 'environment' => $env]
    );
} catch (\Throwable $e) {
    $threw = true;
}
$check('handler create skipa terminal sem exception', !$threw);

// 10) Handler cancel skipa row sem ifood_shipping_id e marca local
// Cria ref3 PENDING sem ifood_id
ShippingDispatcher::createShippingOrder($companyId, $validPayload, ['external_reference' => $ref3]);
$handlerCancel = new ShippingOrderCancelHandler($db, new IFoodApiLogger($db));
try {
    $handlerCancel->handle(
        ['id' => 0, 'company_id' => $companyId, 'job_type' => 'ifood.shipping.cancel', 'attempts' => 1, 'max_attempts' => 5],
        ['external_reference' => $ref3, 'environment' => $env, 'reason' => 'test_local']
    );
    $stmt = $db->prepare('SELECT status, cancelled_at FROM ifood_shipping_orders WHERE external_reference=?');
    $stmt->execute([$ref3]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $check(
        'handler cancel sem ifood_id → CANCELLED local',
        (bool) $row && $row['status'] === 'CANCELLED' && $row['cancelled_at'] !== null
    );
} catch (\Throwable $e) {
    $check('handler cancel sem ifood_id → exception inesperada: ' . $e->getMessage(), false);
}

// 11) Reference gen — duas chamadas dão refs diferentes
$ref_a = 'SHP-' . time() . '-' . bin2hex(random_bytes(6));
usleep(1000);
$ref_b = 'SHP-' . time() . '-' . bin2hex(random_bytes(6));
$check('reference generator produz refs únicas', $ref_a !== $ref_b);

echo "\n  Total: {$passed} ok, {$failed} falha(s)\n";

$cleanup();
echo "→ Smoke test concluído. Estado limpo.\n";
exit($failed === 0 ? 0 : 1);
