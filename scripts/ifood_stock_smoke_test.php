#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smoke test da Fase 3 (stock sync).
 *
 * Não bate na API real do iFood; valida:
 *   1) StockSyncDispatcher::syncProduct() insere job em queue_jobs com dedup_key correto.
 *   2) Chamadas repetidas dentro da janela são coalescidas (não dupliam linhas pending).
 *   3) Após reservar+nullar dedup_key, uma nova chamada cria novo job.
 *   4) StockSyncHandler skipa produtos sem mapping (sem erros).
 *
 * Pré-requisitos:
 *   - banco com migrations atualizadas (incluindo 20260525_ifood_phase3_stock_sync.sql)
 *   - uma company com id válido (default: 1) e ifood_integrations.is_active = 1
 *   - opcionalmente, um produto local com entry em ifood_product_mapping para
 *     testar o caminho feliz (steps 1–3).
 *
 * Uso:
 *   php scripts/ifood_stock_smoke_test.php [company_id] [product_id]
 *
 * Se product_id não for fornecido, o script tenta achar um produto mapeado da company.
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/IFoodEndpoints.php';
require_once __DIR__ . '/../app/services/IFood/StockSyncDispatcher.php';
require_once __DIR__ . '/../app/services/IFood/Handlers/StockSyncHandler.php';
require_once __DIR__ . '/../app/services/IFood/IFoodApiLogger.php';
require_once __DIR__ . '/../app/models/QueueJob.php';

use App\Services\IFood\IFoodApiLogger;
use App\Services\IFood\IFoodEndpoints;
use App\Services\IFood\StockSyncDispatcher;
use App\Services\IFood\Handlers\StockSyncHandler;

$db = db();
$companyId = (int)($argv[1] ?? 1);
$productIdArg = isset($argv[2]) ? (int)$argv[2] : 0;

echo "→ Smoke test stock sync — company={$companyId}\n";

// 0) pré-requisito: integração ativa
$stmt = $db->prepare('SELECT environment, is_active FROM ifood_integrations WHERE company_id = ?');
$stmt->execute([$companyId]);
$integration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$integration || (int)$integration['is_active'] !== 1) {
    fwrite(STDERR, "× company {$companyId} não tem integração iFood ativa. Abortando.\n");
    exit(1);
}
$env = IFoodEndpoints::normalizeEnvironment((string)$integration['environment']);
echo "  ↳ ambiente: {$env}\n";

// 1) acha um produto mapeado (ou usa o argumento)
$productId = $productIdArg;
if ($productId <= 0) {
    $stmt = $db->prepare(
        'SELECT product_id FROM ifood_product_mapping
          WHERE company_id = ? AND product_id IS NOT NULL AND is_active = 1
          LIMIT 1'
    );
    $stmt->execute([$companyId]);
    $productId = (int)($stmt->fetchColumn() ?: 0);
}

if ($productId <= 0) {
    echo "ℹ Sem produto mapeado para testar caminho feliz; testando apenas skip path.\n";
    // Cria um product_id fictício alto que não existe em mapping → deve coalescer NADA (skip).
    $fakePid = 99999999;
    $r = StockSyncDispatcher::syncProduct($companyId, $fakePid);
    echo "  syncProduct(no-mapping): " . ($r ? 'enqueued (inesperado)' : 'skipped (ok)') . "\n";
    exit(0);
}

echo "  ↳ usando product_id = {$productId}\n";

$dedup = sprintf('ifood.stock:%d:%s:%d', $companyId, $env, $productId);

// limpa jobs anteriores para o teste ficar determinístico (apenas pending/retrying do mesmo dedup_key)
$db->prepare(
    "UPDATE queue_jobs SET dedup_key = NULL
      WHERE dedup_key = ? AND status IN ('pending','retrying')"
)->execute([$dedup]);

// 2) primeira chamada → deve inserir
$a = StockSyncDispatcher::syncProduct($companyId, $productId, 60);
echo "  1ª syncProduct: " . ($a ? 'enqueued ✓' : 'skipped ×') . "\n";

// 3) segunda chamada imediata → deve coalescer
$b = StockSyncDispatcher::syncProduct($companyId, $productId, 60);
echo "  2ª syncProduct: " . ($b ? 'enqueued (DUPLICADO! BUG)' : 'coalesced ✓') . "\n";

// 4) conta quantos pending com o dedup_key existem (esperado: 1)
$stmt = $db->prepare(
    "SELECT COUNT(*) FROM queue_jobs
      WHERE dedup_key = ? AND status IN ('pending','retrying')"
);
$stmt->execute([$dedup]);
$pending = (int)$stmt->fetchColumn();
echo "  jobs pending com dedup_key: {$pending} " . ($pending === 1 ? '✓' : '× (esperado 1)') . "\n";

// 5) simula a "reserva" do job (nulla o dedup_key como o worker faria)
$db->prepare(
    "UPDATE queue_jobs SET dedup_key = NULL, status = 'processing', reserved_at = NOW()
      WHERE dedup_key = ? AND status = 'pending' LIMIT 1"
)->execute([$dedup]);

// 6) nova chamada agora deve criar novo job (o anterior está em processing sem dedup)
$c = StockSyncDispatcher::syncProduct($companyId, $productId, 60);
echo "  3ª syncProduct (após reserva): " . ($c ? 'enqueued ✓' : 'skipped ×') . "\n";

// 7) testa o handler diretamente com um product_id que não tem mapping → deve não lançar
$fakePid = 99999998;
$logger = new IFoodApiLogger($db);
$handler = new StockSyncHandler($db, $logger);
try {
    $handler->handle(
        ['id' => 0, 'company_id' => $companyId, 'job_type' => 'ifood.stock.sync', 'attempts' => 1, 'max_attempts' => 5],
        ['product_id' => $fakePid, 'environment' => $env]
    );
    echo "  handler.handle(no-mapping): silent skip ✓\n";
} catch (\Throwable $e) {
    echo "  handler.handle(no-mapping): jogou exceção × — " . $e->getMessage() . "\n";
}

// 8) cleanup: deixa os jobs de teste como 'done' para não vazarem
$db->prepare(
    "UPDATE queue_jobs SET status = 'done', completed_at = NOW(), last_error = 'smoke-test cleanup'
      WHERE job_type = 'ifood.stock.sync'
        AND company_id = ?
        AND status IN ('pending','retrying','processing')
        AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
)->execute([$companyId]);

echo "→ Smoke test concluído.\n";
