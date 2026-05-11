#!/usr/bin/env php
<?php

/**
 * iFood Polling Cron Job
 * 
 * Este script deve ser executado via cron a cada minuto para buscar
 * novos pedidos do iFood.
 * 
 * Exemplo de crontab:
 * * * * * * php /path/to/scripts/ifood_polling_cron.php >> /var/log/ifood_polling.log 2>&1
 * 
 * @package App\Scripts
 */

declare(strict_types=1);

// Verificar se está sendo executado via CLI
if (php_sapi_name() !== 'cli') {
    die("Este script só pode ser executado via linha de comando.\n");
}

// Carregar bootstrap
require_once __DIR__ . '/../app/bootstrap.php';

// Carregar serviço
require_once __DIR__ . '/../app/services/IFoodService.php';

echo "[" . date('Y-m-d H:i:s') . "] Iniciando polling iFood...\n";

$db = db();

// Buscar todas as empresas com integração iFood ativa
$stmt = $db->prepare("
    SELECT i.*, c.name as company_name, c.slug
    FROM ifood_integrations i
    INNER JOIN companies c ON c.id = i.company_id
    WHERE i.is_active = 1
");
$stmt->execute();
$integrations = $stmt->fetchAll();

if (empty($integrations)) {
    echo "[" . date('Y-m-d H:i:s') . "] Nenhuma integração iFood ativa encontrada.\n";
    exit(0);
}

$totalProcessed = 0;
$totalErrors = 0;

// iFood requires polling every 30s. Cron runs every 1 min,
// so we poll twice per execution: once immediately, once after 30s.
for ($round = 1; $round <= 2; $round++) {
    if ($round > 1) {
        sleep(30);
    }
    
    foreach ($integrations as $integration) {
        echo "[" . date('Y-m-d H:i:s') . "] [Round $round] Processando: {$integration['company_name']} (ID: {$integration['company_id']})\n";
        
        try {
            $service = new IFoodService($db, (int) $integration['company_id']);
            $result = $service->pollEvents();
            
            if ($result['success']) {
                $totalProcessed += $result['processed'] ?? 0;
                echo "[" . date('Y-m-d H:i:s') . "]   - Eventos: {$result['total_events']}, Processados: {$result['processed']}\n";
                
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        echo "[" . date('Y-m-d H:i:s') . "]   - ERRO: {$error['error']}\n";
                        $totalErrors++;
                    }
                }
            } else {
                echo "[" . date('Y-m-d H:i:s') . "]   - FALHA: {$result['error']}\n";
                $totalErrors++;
            }
        } catch (\Throwable $e) {
            echo "[" . date('Y-m-d H:i:s') . "]   - EXCEÇÃO: {$e->getMessage()}\n";
            $totalErrors++;
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Polling concluído. Total processado: {$totalProcessed}, Erros: {$totalErrors}\n";
