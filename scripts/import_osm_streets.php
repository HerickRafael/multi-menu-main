#!/usr/bin/env php
<?php

/**
 * Import OSM Streets - Importação em massa de ruas do OpenStreetMap
 * 
 * Uso:
 *   php scripts/import_osm_streets.php --company=1 --city="Osório"
 *   php scripts/import_osm_streets.php --company=1 --all-cities
 *   php scripts/import_osm_streets.php --company=1 --city="Porto Alegre" --force
 * 
 * Opções:
 *   --company=ID   ID da empresa (obrigatório)
 *   --city=NOME    Nome da cidade para importar
 *   --all-cities   Importar todas as cidades cadastradas em delivery_cities
 *   --force        Re-importar mesmo se já existir
 *   --dry-run      Simular sem persistir
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/services/AddressAutocompleteService.php';

// Parse CLI arguments
$opts = getopt('', ['company:', 'city:', 'all-cities', 'force', 'dry-run']);

$companyId = isset($opts['company']) ? (int)$opts['company'] : 0;
$cityName = $opts['city'] ?? '';
$allCities = isset($opts['all-cities']);
$force = isset($opts['force']);
$dryRun = isset($opts['dry-run']);

if ($companyId <= 0) {
    fwrite(STDERR, "Erro: --company=ID é obrigatório\n");
    fwrite(STDERR, "Uso: php scripts/import_osm_streets.php --company=1 --city=\"Osório\"\n");
    exit(1);
}

if ($cityName === '' && !$allCities) {
    fwrite(STDERR, "Erro: Informe --city=\"Nome\" ou --all-cities\n");
    exit(1);
}

$pdo = db();
$service = new AddressAutocompleteService($pdo, $companyId);

// Determinar lista de cidades
$cities = [];
if ($allCities) {
    $stmt = $pdo->prepare(
        "SELECT DISTINCT dc.name 
         FROM delivery_cities dc 
         WHERE dc.company_id = :cid 
         ORDER BY dc.name"
    );
    $stmt->execute([':cid' => $companyId]);
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($cities)) {
        echo "Nenhuma cidade encontrada para empresa #{$companyId}\n";
        exit(0);
    }
    echo "Encontradas " . count($cities) . " cidades para importar\n\n";
} else {
    $cities = [$cityName];
}

$totalStreets = 0;
$totalNeighborhoods = 0;
$totalErrors = 0;

foreach ($cities as $city) {
    echo "=== Importando: {$city} ===\n";
    
    // Check if already imported (unless --force)
    if (!$force) {
        $stmt = $pdo->prepare(
            "SELECT status, total_streets FROM address_import_log 
             WHERE company_id = :cid AND city_normalized = :city"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':city' => AddressAutocompleteService::normalize($city),
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing && $existing['status'] === 'completed') {
            echo "  ✓ Já importada ({$existing['total_streets']} ruas). Use --force para re-importar.\n\n";
            continue;
        }
    }
    
    if ($dryRun) {
        echo "  [dry-run] Simulação - não vai persistir dados\n\n";
        continue;
    }
    
    // If forcing, clear old data first
    if ($force) {
        $cityNorm = AddressAutocompleteService::normalize($city);
        $stmt = $pdo->prepare(
            "DELETE FROM address_streets WHERE company_id = :cid AND city_normalized = :city"
        );
        $stmt->execute([':cid' => $companyId, ':city' => $cityNorm]);
        $deleted = $stmt->rowCount();
        
        $pdo->prepare(
            "DELETE FROM address_import_log WHERE company_id = :cid AND city_normalized = :city"
        )->execute([':cid' => $companyId, ':city' => $cityNorm]);
        
        if ($deleted > 0) {
            echo "  Removidas {$deleted} ruas existentes\n";
        }
    }
    
    $start = microtime(true);
    $result = $service->importCityFromOverpass($city);
    $elapsed = round(microtime(true) - $start, 2);
    
    if ($result['streets'] > 0) {
        echo "  ✓ {$result['streets']} ruas, {$result['neighborhoods']} bairros ({$elapsed}s)\n";
        $totalStreets += $result['streets'];
        $totalNeighborhoods += $result['neighborhoods'];
    } else {
        echo "  ✗ Falha na importação\n";
        $totalErrors++;
    }
    
    echo "\n";
    
    // Rate-limit between cities (Overpass API courtesy)
    if (count($cities) > 1) {
        sleep(2);
    }
}

echo "=== Resumo ===\n";
echo "Ruas importadas: {$totalStreets}\n";
echo "Bairros mapeados: {$totalNeighborhoods}\n";
if ($totalErrors > 0) {
    echo "Erros: {$totalErrors}\n";
}
echo "Concluído.\n";
