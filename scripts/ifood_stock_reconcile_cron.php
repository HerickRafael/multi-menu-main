#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * iFood Stock Reconciliation Cron
 *
 * Detecta drift entre o estado local (`products.active`) e o último estado
 * sincronizado com o iFood (`ifood_stock_sync_state.last_synced_status`),
 * re-enfileirando `ifood.stock.sync` para produtos que precisam convergir.
 *
 * Cobre 3 cenários de drift:
 *   1) Produto mapeado nunca sincronizado (sem row em ifood_stock_sync_state).
 *   2) Produto mapeado com snapshot != estado atual local
 *      (ex: webhook perdido, worker crashou, job morto antes de retry).
 *   3) Falhas consecutivas: re-tenta produtos com consecutive_failures > 0
 *      (já com sucesso parcial em retry exponencial do worker, mas o cron
 *      garante que mesmo após esgotar retries o item volta pra fila).
 *
 * Coalescing: usa StockSyncDispatcher::syncProduct() que respeita dedup_key,
 * então rodar múltiplas vezes em curta janela não duplica jobs.
 *
 * Rate limiting: cap por company (default 200/run) evita encher a fila
 * quando há muito drift acumulado. Próxima execução pega o resto.
 *
 * Exemplo de crontab (a cada 15 min):
 *   *\/15 * * * * php /path/to/scripts/ifood_stock_reconcile_cron.php >> /var/log/ifood_stock_reconcile.log 2>&1
 *
 * Uso manual:
 *   php scripts/ifood_stock_reconcile_cron.php             # todas as companies
 *   php scripts/ifood_stock_reconcile_cron.php <company>   # apenas uma company
 *   CAP_PER_COMPANY=500 php scripts/ifood_stock_reconcile_cron.php  # ajustar cap
 */

if (php_sapi_name() !== 'cli') {
    die("Este script só pode ser executado via linha de comando.\n");
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/IFoodEndpoints.php';
require_once __DIR__ . '/../app/services/IFood/StockSyncDispatcher.php';

use App\Services\IFood\IFoodEndpoints;
use App\Services\IFood\StockSyncDispatcher;

$capPerCompany = (int) (getenv('CAP_PER_COMPANY') ?: '200');
$capPerCompany = max(1, $capPerCompany);

$onlyCompanyId = isset($argv[1]) ? (int) $argv[1] : 0;

$db = db();

$ts = static fn(): string => '[' . date('Y-m-d H:i:s') . ']';
echo $ts() . " Iniciando reconciliação de stock sync (cap={$capPerCompany}/company)\n";

// 1) Companies com integração ativa
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
    echo $ts() . " Nenhuma integração iFood ativa.\n";
    exit(0);
}

$totalEnqueued = 0;
$totalCoalesced = 0;
$totalChecked = 0;

foreach ($integrations as $integration) {
    $companyId = (int) $integration['company_id'];
    $env = IFoodEndpoints::normalizeEnvironment((string) $integration['environment']);

    echo $ts() . " Company {$companyId} ({$integration['company_name']}) env={$env}\n";

    /*
     * Detecta drift via LEFT JOIN: pega cada mapeamento ativo + produto local,
     * mesmo quando ainda não há linha em ifood_stock_sync_state. Filtra por:
     *  - sem snapshot (s.product_id IS NULL)               → primeira sync
     *  - snapshot divergente do active atual               → drift real
     *  - tem snapshot bom mas falhas consecutivas > 0      → re-tenta
     *
     * Ordenação: prioriza nunca-sincronizados, depois mais antigos.
     */
    $driftSql = "
        SELECT m.product_id,
               p.active,
               s.last_synced_status,
               s.consecutive_failures,
               s.last_synced_at
          FROM ifood_product_mapping m
          INNER JOIN products p
                  ON p.id = m.product_id
                 AND p.company_id = m.company_id
          LEFT JOIN ifood_stock_sync_state s
                 ON s.company_id  = m.company_id
                AND s.environment = :env
                AND s.product_id  = m.product_id
         WHERE m.company_id = :cid
           AND m.is_active  = 1
           AND m.product_id IS NOT NULL
           AND (
                 s.product_id IS NULL
              OR (s.last_synced_status IS NOT NULL
                  AND ((p.active = 1 AND s.last_synced_status <> 'AVAILABLE')
                    OR (p.active = 0 AND s.last_synced_status <> 'UNAVAILABLE')))
              OR (s.consecutive_failures IS NOT NULL AND s.consecutive_failures > 0)
           )
         ORDER BY (s.product_id IS NULL) DESC,
                  s.consecutive_failures DESC,
                  s.last_synced_at ASC
         LIMIT :cap";

    $stmt = $db->prepare($driftSql);
    $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
    $stmt->bindValue(':env', $env, PDO::PARAM_STR);
    $stmt->bindValue(':cap', $capPerCompany, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalChecked += count($rows);
    if (empty($rows)) {
        echo $ts() . "   ↳ sem drift detectado\n";
        continue;
    }

    echo $ts() . "   ↳ " . count($rows) . " produto(s) precisam sync\n";

    $perCompanyEnqueued = 0;
    $perCompanyCoalesced = 0;
    $i = 0;

    foreach ($rows as $row) {
        $productId = (int) $row['product_id'];
        if ($productId <= 0) {
            continue;
        }
        // Delay escalonado pra amaciar a fila: 0s, 1s, 2s, ...
        $delay = $i++;
        $ok = StockSyncDispatcher::syncProduct($companyId, $productId, $delay);
        if ($ok) {
            $perCompanyEnqueued++;
        } else {
            $perCompanyCoalesced++;
        }
    }

    echo $ts() . sprintf(
        "   ↳ enfileirados=%d coalescidos=%d (cap=%d)\n",
        $perCompanyEnqueued,
        $perCompanyCoalesced,
        $capPerCompany
    );

    $totalEnqueued += $perCompanyEnqueued;
    $totalCoalesced += $perCompanyCoalesced;
}

echo $ts() . sprintf(
    " Concluído. companies=%d, drift_detectado=%d, enfileirados=%d, coalescidos=%d\n",
    count($integrations),
    $totalChecked,
    $totalEnqueued,
    $totalCoalesced
);
