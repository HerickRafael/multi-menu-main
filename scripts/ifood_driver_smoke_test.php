#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smoke test da Fase 4 (driver request).
 *
 * Valida (sem bater na API real do iFood):
 *   1) DriverRequestDispatcher pula quando pedido não existe.
 *   2) Pula quando delivered_by != MERCHANT.
 *   3) Pula quando status terminal (CONCLUDED/CANCELLED).
 *   4) Enfileira corretamente quando pedido válido (CONFIRMED + MERCHANT).
 *   5) Chamadas repetidas são coalescidas via dedup_key.
 *   6) Após reservar o job (dedup_key=NULL), nova chamada cria novo job.
 *   7) Handler skipa pedido sem integração (sem exceção).
 *   8) Handler skipa quando delivered_by=IFOOD (registra estado CANCELLED).
 *
 * Pré-requisitos:
 *   - migration phase4 aplicada
 *   - company 1 com ifood_integrations.is_active = 1
 *
 * Uso:
 *   php scripts/ifood_driver_smoke_test.php [company_id]
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/IFoodEndpoints.php';
require_once __DIR__ . '/../app/services/IFood/DriverRequestDispatcher.php';
require_once __DIR__ . '/../app/services/IFood/Handlers/DriverRequestHandler.php';
require_once __DIR__ . '/../app/services/IFood/IFoodApiLogger.php';
require_once __DIR__ . '/../app/models/QueueJob.php';

use App\Services\IFood\DriverRequestDispatcher;
use App\Services\IFood\Handlers\DriverRequestHandler;
use App\Services\IFood\IFoodApiLogger;
use App\Services\IFood\IFoodEndpoints;

$db = db();
$companyId = (int) ($argv[1] ?? 1);

echo "→ Smoke test driver request — company={$companyId}\n";

$stmt = $db->prepare('SELECT environment, is_active FROM ifood_integrations WHERE company_id = ?');
$stmt->execute([$companyId]);
$integration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$integration || (int) $integration['is_active'] !== 1) {
    fwrite(STDERR, "× company {$companyId} sem integração iFood ativa. Abortando.\n");
    exit(1);
}
$env = IFoodEndpoints::normalizeEnvironment((string) $integration['environment']);
echo "  ↳ ambiente: {$env}\n";

// IDs fake usados nos cenários — todos com prefixo SMOKE-DRIVER- para fácil cleanup.
$orderIdValid  = 'SMOKE-DRIVER-OK-' . bin2hex(random_bytes(4));
$orderIdIFood  = 'SMOKE-DRIVER-IFOOD-' . bin2hex(random_bytes(4));
$orderIdDone   = 'SMOKE-DRIVER-DONE-' . bin2hex(random_bytes(4));
$orderIdAbsent = 'SMOKE-DRIVER-MISSING-' . bin2hex(random_bytes(4));

// merchant_id necessário pelo NOT NULL
$merchantId = 'SMOKE-MERCHANT';

// Inserir pedidos fake com diferentes características
$insertOrder = $db->prepare(
    'INSERT INTO ifood_orders
        (company_id, ifood_order_id, ifood_merchant_id, status, items, payments, delivered_by, order_type)
     VALUES (:cid, :oid, :mid, :st, :items, :pays, :dby, :ot)'
);
$jsonEmpty = json_encode([]);

$insertOrder->execute([
    ':cid'   => $companyId,
    ':oid'   => $orderIdValid,
    ':mid'   => $merchantId,
    ':st'    => 'CONFIRMED',
    ':items' => $jsonEmpty,
    ':pays'  => $jsonEmpty,
    ':dby'   => 'MERCHANT',
    ':ot'    => 'DELIVERY',
]);
$insertOrder->execute([
    ':cid'   => $companyId,
    ':oid'   => $orderIdIFood,
    ':mid'   => $merchantId,
    ':st'    => 'CONFIRMED',
    ':items' => $jsonEmpty,
    ':pays'  => $jsonEmpty,
    ':dby'   => 'IFOOD',
    ':ot'    => 'DELIVERY',
]);
$insertOrder->execute([
    ':cid'   => $companyId,
    ':oid'   => $orderIdDone,
    ':mid'   => $merchantId,
    ':st'    => 'CONCLUDED',
    ':items' => $jsonEmpty,
    ':pays'  => $jsonEmpty,
    ':dby'   => 'MERCHANT',
    ':ot'    => 'DELIVERY',
]);

echo "  ↳ 3 pedidos fake inseridos (valido, ifood-entregue, concluído)\n";

$cleanup = static function () use ($db, $companyId, $orderIdValid, $orderIdIFood, $orderIdDone) {
    $db->prepare("DELETE FROM queue_jobs WHERE job_type='ifood.driver.request' AND company_id=?")
        ->execute([$companyId]);
    // Limpa por prefixo para pegar também o $orderIdAbsent (o handler grava
    // estado FAILED antes de lançar a exceção do teste 7).
    $db->prepare("DELETE FROM ifood_order_drivers WHERE company_id=? AND ifood_order_id LIKE 'SMOKE-DRIVER-%'")
        ->execute([$companyId]);
    $db->prepare("DELETE FROM ifood_orders WHERE ifood_order_id IN (?,?,?)")
        ->execute([$orderIdValid, $orderIdIFood, $orderIdDone]);
};

$passed = 0;
$failed = 0;
$check = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    echo '  ' . ($ok ? '✓ ' : '× ') . $label . "\n";
    $ok ? $passed++ : $failed++;
};

// 1) Pedido inexistente → skip
$r = DriverRequestDispatcher::requestForOrder($companyId, $orderIdAbsent);
$check('dispatcher skipa pedido inexistente', $r === false);

// 2) Pedido com delivered_by=IFOOD → skip
$r = DriverRequestDispatcher::requestForOrder($companyId, $orderIdIFood);
$check('dispatcher skipa delivered_by=IFOOD', $r === false);

// 3) Pedido CONCLUDED → skip
$r = DriverRequestDispatcher::requestForOrder($companyId, $orderIdDone);
$check('dispatcher skipa status terminal', $r === false);

// 4) Pedido válido → enfileira
$r = DriverRequestDispatcher::requestForOrder($companyId, $orderIdValid, 60);
$check('dispatcher enfileira pedido válido', $r === true);

$dedup = sprintf('ifood.driver:%d:%s:%s', $companyId, $env, $orderIdValid);

// 5) Chamada imediata → coalescing
$r = DriverRequestDispatcher::requestForOrder($companyId, $orderIdValid, 60);
$check('2ª chamada coalesce', $r === false);

$stmt = $db->prepare("SELECT COUNT(*) FROM queue_jobs WHERE dedup_key = ? AND status IN ('pending','retrying')");
$stmt->execute([$dedup]);
$check('1 job pending com dedup_key', (int) $stmt->fetchColumn() === 1);

// 6) Simula reserva (worker nulla dedup_key)
$db->prepare(
    "UPDATE queue_jobs SET dedup_key=NULL, status='processing', reserved_at=NOW()
      WHERE dedup_key=? AND status='pending' LIMIT 1"
)->execute([$dedup]);
$r = DriverRequestDispatcher::requestForOrder($companyId, $orderIdValid, 60);
$check('após reserva, nova chamada enfileira', $r === true);

// 7) Handler com pedido inexistente → joga (ordem não existe localmente)
$handler = new DriverRequestHandler($db, new IFoodApiLogger($db));
$threw = false;
try {
    $handler->handle(
        ['id' => 0, 'company_id' => $companyId, 'job_type' => 'ifood.driver.request', 'attempts' => 1, 'max_attempts' => 5],
        ['ifood_order_id' => $orderIdAbsent, 'environment' => $env]
    );
} catch (\RuntimeException $e) {
    $threw = strpos($e->getMessage(), 'não encontrado') !== false;
}
$check('handler joga RuntimeException para pedido sem registro local', $threw);

// 8) Handler com delivered_by=IFOOD → skip silencioso + estado CANCELLED
try {
    $handler->handle(
        ['id' => 0, 'company_id' => $companyId, 'job_type' => 'ifood.driver.request', 'attempts' => 1, 'max_attempts' => 5],
        ['ifood_order_id' => $orderIdIFood, 'environment' => $env]
    );
    $stmt = $db->prepare(
        'SELECT request_status FROM ifood_order_drivers WHERE company_id=? AND ifood_order_id=?'
    );
    $stmt->execute([$companyId, $orderIdIFood]);
    $state = (string) ($stmt->fetchColumn() ?: '');
    $check('handler marca pedido IFOOD como CANCELLED (silent)', $state === 'CANCELLED');
} catch (\Throwable $e) {
    $check('handler marca pedido IFOOD como CANCELLED (silent) — joga: ' . $e->getMessage(), false);
}

// 9) Handler com status CONCLUDED → joga RuntimeException
$threw = false;
try {
    $handler->handle(
        ['id' => 0, 'company_id' => $companyId, 'job_type' => 'ifood.driver.request', 'attempts' => 1, 'max_attempts' => 5],
        ['ifood_order_id' => $orderIdDone, 'environment' => $env]
    );
} catch (\RuntimeException $e) {
    $threw = str_contains($e->getMessage(), 'não permite request driver');
}
$check('handler joga para status CONCLUDED', $threw);

echo "\n  Total: {$passed} ok, {$failed} falha(s)\n";

$cleanup();
echo "→ Smoke test concluído. Estado limpo.\n";
exit($failed === 0 ? 0 : 1);
