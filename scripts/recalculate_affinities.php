#!/usr/bin/env php
<?php

/**
 * ============================================================================
 * Cron Job: Recalcular Afinidades de Produtos
 * ============================================================================
 * 
 * Este script deve ser executado periodicamente (ex: 1x por dia) para
 * recalcular os scores de afinidade entre produtos baseado em compras reais.
 * 
 * Uso:
 *   php scripts/recalculate_affinities.php
 * 
 * Crontab (diariamente às 3h da madrugada):
 *   0 3 * * * cd /path/to/multi_menu && php scripts/recalculate_affinities.php >> storage/logs/cron.log 2>&1
 */

declare(strict_types=1);

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Carregar autoloader e configurações
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/services/RecommendationEngine.php';
require_once __DIR__ . '/../app/models/Company.php';

echo "\n";
echo "========================================\n";
echo "Recalculando Afinidades de Produtos\n";
echo "========================================\n";
echo "Início: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $engine = new RecommendationEngine($db);
    
    // Buscar todas as empresas ativas
    $stmt = $db->prepare("SELECT id, name, slug FROM companies WHERE active = 1");
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($companies)) {
        echo "Nenhuma empresa ativa encontrada.\n";
        exit(0);
    }
    
    echo "Empresas encontradas: " . count($companies) . "\n\n";
    
    $totalUpdated = 0;
    
    foreach ($companies as $company) {
        $companyId = (int)$company['id'];
        $companyName = $company['name'];
        
        echo "Processando: {$companyName} (ID: {$companyId})\n";
        
        $startTime = microtime(true);
        $updated = $engine->recalculateAffinityScores($companyId);
        $endTime = microtime(true);
        
        $duration = round($endTime - $startTime, 2);
        
        echo "  → {$updated} afinidades atualizadas em {$duration}s\n";
        
        $totalUpdated += $updated;
    }
    
    echo "\n========================================\n";
    echo "Processamento Concluído!\n";
    echo "========================================\n";
    echo "Total de afinidades atualizadas: {$totalUpdated}\n";
    echo "Fim: " . date('Y-m-d H:i:s') . "\n\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}
