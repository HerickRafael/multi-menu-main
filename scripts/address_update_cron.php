#!/usr/bin/env php
<?php

/**
 * Cron: Manutenção periódica do autocomplete de endereços
 * 
 * Responsabilidades:
 *   1. Sync ruas de customer_addresses → address_streets (enriquecimento)
 *   2. Promoção: pending → active quando distinct_customers >= 4
 *   3. Validação de consistência bairro-rua
 *   4. Decay de confiança para dados inativos (>90 dias)
 *   5. Cleanup de ruas "suspect" com confiança muito baixa
 *   6. Validação OSM: confirma ruas não-OSM contra OpenStreetMap (Nominatim)
 *   7. Cleanup de ruas "learned" sem uso, >60 dias
 *   8. Decay de popularidade (evita acúmulo infinito)
 * 
 * Executar via crontab:
 *   0 3 * * 0 php /var/www/html/scripts/address_update_cron.php
 *   (Domingos às 3h da manhã)
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/services/AddressAutocompleteService.php';

$pdo = db();

echo "[" . date('Y-m-d H:i:s') . "] Iniciando manutenção de autocomplete...\n";

// 1) Sync ruas de customer_addresses → address_streets
// Busca endereços de clientes que ainda não foram sincronizados (últimos 7 dias)
$addresses = $pdo->query(
    "SELECT ca.company_id, ca.city, ca.neighborhood, ca.street, COUNT(*) as cnt
     FROM customer_addresses ca
     WHERE ca.street IS NOT NULL AND ca.street != ''
       AND ca.city IS NOT NULL AND ca.city != ''
       AND ca.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY ca.company_id, ca.city, ca.neighborhood, ca.street
     ORDER BY cnt DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$synced = 0;
$lastCompanyId = null;
$service = null;

foreach ($addresses as $row) {
    $companyId = (int)$row['company_id'];
    if ($companyId !== $lastCompanyId) {
        $service = new AddressAutocompleteService($pdo, $companyId);
        $lastCompanyId = $companyId;
    }
    
    $service->syncFromOrder($row['city'], $row['neighborhood'], $row['street']);
    $synced++;
}

if ($synced > 0) {
    echo "  Sincronizadas {$synced} ruas de pedidos recentes\n";
}

// 2) Promoção: pending → active (threshold de 4 clientes distintos)
$companies = $pdo->query("SELECT DISTINCT company_id FROM address_streets WHERE status = 'pending'")->fetchAll(PDO::FETCH_COLUMN);
$totalPromoted = 0;
foreach ($companies as $cid) {
    $svc = new AddressAutocompleteService($pdo, (int)$cid);
    $totalPromoted += $svc->promoteQualifiedPending();
}
if ($totalPromoted > 0) {
    echo "  Promovidas {$totalPromoted} ruas pending → active\n";
}

// 3) Validação de consistência bairro-rua
$allCompanies = $pdo->query("SELECT DISTINCT company_id FROM address_streets")->fetchAll(PDO::FETCH_COLUMN);
$totalConflicts = 0;
foreach ($allCompanies as $cid) {
    $svc = new AddressAutocompleteService($pdo, (int)$cid);
    $result = $svc->validateConsistency();
    $totalConflicts += $result['conflicts_found'];
}
if ($totalConflicts > 0) {
    echo "  Encontrados {$totalConflicts} conflitos de bairro-rua\n";
}

// 4) Decay de confiança para dados inativos >90 dias
$totalDecayedConf = 0;
foreach ($allCompanies as $cid) {
    $svc = new AddressAutocompleteService($pdo, (int)$cid);
    $totalDecayedConf += $svc->decayStaleConfidence();
}
if ($totalDecayedConf > 0) {
    echo "  Decay de confiança em {$totalDecayedConf} ruas inativas\n";
}

// 5) Cleanup de ruas "suspect" com confiança muito baixa (>90 dias)
$totalCleaned = 0;
foreach ($allCompanies as $cid) {
    $svc = new AddressAutocompleteService($pdo, (int)$cid);
    $totalCleaned += $svc->cleanupSuspect();
}
if ($totalCleaned > 0) {
    echo "  Removidas {$totalCleaned} ruas suspect com baixa confiança\n";
}

// 6) Validação OSM: confirma ruas não-OSM contra OpenStreetMap via Nominatim
// Ruas confirmadas no OSM recebem confidence boost para 0.85
$totalValidated = 0;
$totalNotFound = 0;
foreach ($allCompanies as $cid) {
    $svc = new AddressAutocompleteService($pdo, (int)$cid);
    $osmResult = $svc->validateAgainstOSM(50);
    $totalValidated += $osmResult['validated'];
    $totalNotFound += $osmResult['not_found'];
}
if ($totalValidated > 0 || $totalNotFound > 0) {
    echo "  OSM validation: {$totalValidated} confirmadas, {$totalNotFound} não encontradas\n";
}

// 7) Limpar ruas "learned" sem popularidade, com >60 dias 
$cleaned = $pdo->exec(
    "DELETE FROM address_streets 
     WHERE source = 'learned' 
       AND popularity_score <= 1 
       AND status = 'pending'
       AND created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)"
);
if ($cleaned > 0) {
    echo "  Removidas {$cleaned} ruas 'learned' sem uso\n";
}

// 8) Decay de popularidade (reduz 10% para scores > 50)
$decayed = $pdo->exec(
    "UPDATE address_streets 
     SET popularity_score = GREATEST(1, FLOOR(popularity_score * 0.9))
     WHERE popularity_score > 50"
);
if ($decayed > 0) {
    echo "  Decay aplicado em {$decayed} ruas populares\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Concluído.\n";
