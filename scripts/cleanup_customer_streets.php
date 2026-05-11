#!/usr/bin/env php
<?php

/**
 * One-time cleanup: fix duplicate/junk customer street entries.
 * 
 * Actions:
 *   1. Delete obvious junk entries (non-addresses)
 *   2. Expand abbreviated prefixes (R. → Rua, Av → Avenida, etc.)
 *   3. Merge customer entries without prefix into their OSM counterparts
 *   4. Remove remaining duplicates keeping best entry per canonical name+city
 * 
 * Usage:
 *   php scripts/cleanup_customer_streets.php --dry-run    (preview changes)
 *   php scripts/cleanup_customer_streets.php              (execute)
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/services/AddressAutocompleteService.php';

$dryRun = in_array('--dry-run', $argv);
$pdo = db();

echo "[" . date('Y-m-d H:i:s') . "] Cleanup de ruas duplicadas" . ($dryRun ? ' (DRY-RUN)' : '') . "\n\n";

$deleted = 0;
$updated = 0;
$merged = 0;

// =========================================================================
// Step 1: Delete junk entries (non-addresses)
// =========================================================================
echo "=== Step 1: Remover lixo ===\n";

$junkPatterns = [
    "tu sabe onde",
    "Rodovia RS30 km90",
];

// Also: entries < 5 chars, customer entries with abbreviated names (single-letter words like "p")
$stmt = $pdo->query("
    SELECT id, street, street_normalized, source, neighborhood 
    FROM address_streets
    WHERE (
        street_normalized IN ('tu sabe onde', 'rodovia rs30 km90')
        OR (source = 'customer' AND CHAR_LENGTH(street) < 5 AND street NOT REGEXP '^(Rua|Avenida|Travessa)')
    )
    ORDER BY street
");
$junkRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($junkRows as $row) {
    echo "  DELETE #{$row['id']}: \"{$row['street']}\" ({$row['source']}, {$row['neighborhood']})\n";
    if (!$dryRun) {
        $pdo->prepare("DELETE FROM address_streets WHERE id = :id")->execute([':id' => $row['id']]);
    }
    $deleted++;
}
echo "  → {$deleted} entries removidas\n\n";

// =========================================================================
// Step 2: Expand abbreviated prefixes in existing entries
// =========================================================================
echo "=== Step 2: Expandir prefixos abreviados ===\n";

$expansions = [
    ['/^R\\.\\s+/u',    'Rua ',      'r. '],
    ['/^Av\\.\\s+/ui',  'Avenida ',  'av. '],
    ['/^Av\\s+/ui',    'Avenida ',  'av '],
    ['/^Trav\\.\\s+/ui','Travessa ', 'trav. '],
    ['/^Trav\\s+/ui',  'Travessa ', 'trav '],
    ['/^Rod\\.\\s+/ui', 'Rodovia ',  'rod. '],
    ['/^Estr\\.\\s+/ui','Estrada ',  'estr. '],
    ['/^Rus\\s+/ui',   'Rua ',      'rus '],
];

$stmt = $pdo->query("
    SELECT id, street, street_normalized, source, neighborhood_normalized, city_normalized, company_id
    FROM address_streets
    WHERE street_normalized REGEXP '^(r\\\\.|av\\\\.|av |trav\\\\.|trav |rod\\\\.|estr\\\\.|rus )'
    ORDER BY street
");
$abbrRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($abbrRows as $row) {
    $newStreet = $row['street'];
    $matched = false;
    
    foreach ($expansions as [$pattern, $replacement, $normPrefix]) {
        $result = preg_replace($pattern, $replacement, $newStreet);
        if ($result !== $newStreet) {
            $newStreet = $result;
            $matched = true;
            break;
        }
    }
    
    if (!$matched) continue;
    
    // Apply title case via canonicalStreetName
    $newStreet = AddressAutocompleteService::canonicalStreetName($newStreet);
    $newNorm = AddressAutocompleteService::normalize($newStreet);
    
    // Check if expanded name already exists (would create dup on UNIQUE KEY)
    $checkStmt = $pdo->prepare("
        SELECT id, street, source, popularity_score FROM address_streets
        WHERE company_id = :cid
          AND city_normalized = :city
          AND neighborhood_normalized = :nb
          AND street_normalized = :street
          AND id != :id
        LIMIT 1
    ");
    $checkStmt->execute([
        ':cid' => $row['company_id'],
        ':city' => $row['city_normalized'],
        ':nb' => $row['neighborhood_normalized'],
        ':street' => $newNorm,
        ':id' => $row['id'],
    ]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Merge into existing: transfer popularity, upgrade source if needed
        echo "  MERGE #{$row['id']}: \"{$row['street']}\" → existing #{$existing['id']}: \"{$existing['street']}\"\n";
        if (!$dryRun) {
            $sourceRank = ['customer' => 4, 'manual' => 3, 'learned' => 2, 'osm' => 1, 'nominatim' => 0];
            $bestSource = ($sourceRank[$row['source']] ?? 0) >= ($sourceRank[$existing['source']] ?? 0) ? $row['source'] : $existing['source'];
            
            $pdo->prepare("
                UPDATE address_streets 
                SET source = :src, popularity_score = popularity_score + :pop
                WHERE id = :id
            ")->execute([
                ':src' => $bestSource,
                ':pop' => max(0, (int)($row['popularity_score'] ?? 0)),
                ':id' => $existing['id'],
            ]);
            $pdo->prepare("DELETE FROM address_streets WHERE id = :id")->execute([':id' => $row['id']]);
        }
        $merged++;
    } else {
        // Safe to rename
        echo "  UPDATE #{$row['id']}: \"{$row['street']}\" → \"{$newStreet}\"\n";
        if (!$dryRun) {
            $pdo->prepare("
                UPDATE address_streets 
                SET street = :street, street_normalized = :streetN 
                WHERE id = :id
            ")->execute([
                ':street' => $newStreet,
                ':streetN' => $newNorm,
                ':id' => $row['id'],
            ]);
        }
        $updated++;
    }
}
echo "  → {$updated} renomeados, {$merged} mesclados\n\n";

// =========================================================================
// Step 3: Merge customer entries without prefix into OSM counterparts
// =========================================================================
echo "=== Step 3: Mesclar entries sem prefixo com contraparte OSM ===\n";

$stmt = $pdo->query("
    SELECT id, street, street_normalized, source, neighborhood, neighborhood_normalized, 
           city_normalized, company_id, popularity_score
    FROM address_streets
    WHERE source = 'customer'
      AND street_normalized NOT REGEXP '^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia) '
    ORDER BY street
");
$noPrefixRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mergedStep3 = 0;
foreach ($noPrefixRows as $row) {
    $nameNorm = $row['street_normalized'];
    $match = null;
    
    // Try 1: Exact suffix match — "castro alves" matches "rua castro alves"
    $matchStmt = $pdo->prepare("
        SELECT id, street, source, neighborhood, neighborhood_normalized, popularity_score
        FROM address_streets
        WHERE company_id = :cid
          AND city_normalized = :city
          AND street_normalized LIKE :pattern
          AND id != :id
        ORDER BY 
            FIELD(source, 'customer', 'manual', 'learned', 'osm', 'nominatim'),
            popularity_score DESC
        LIMIT 1
    ");
    $matchStmt->execute([
        ':cid' => $row['company_id'],
        ':city' => $row['city_normalized'],
        ':pattern' => '% ' . $nameNorm,
        ':id' => $row['id'],
    ]);
    $match = $matchStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($match) {
        echo "  MERGE #{$row['id']}: \"{$row['street']}\" → #{$match['id']}: \"{$match['street']}\"\n";
    }
    
    // Try 2: Fuzzy match by first significant word (>=5 chars)
    // Catches abbreviated names like "Hildebrando p Veloso" → "Rua Hildebrando Pinheiro Veloso"
    if (!$match) {
        $words = preg_split('/\s+/', $nameNorm);
        $firstLongWord = '';
        foreach ($words as $w) {
            if (mb_strlen($w) >= 5) { $firstLongWord = $w; break; }
        }
        
        if ($firstLongWord !== '') {
            $fuzzyStmt = $pdo->prepare("
                SELECT id, street, source, neighborhood, neighborhood_normalized, popularity_score
                FROM address_streets
                WHERE company_id = :cid
                  AND city_normalized = :city
                  AND street_normalized LIKE :pattern
                  AND street_normalized REGEXP '^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia) '
                  AND id != :id
                ORDER BY 
                    FIELD(source, 'customer', 'manual', 'learned', 'osm', 'nominatim'),
                    popularity_score DESC
                LIMIT 1
            ");
            $fuzzyStmt->execute([
                ':cid' => $row['company_id'],
                ':city' => $row['city_normalized'],
                ':pattern' => '%' . $firstLongWord . '%',
                ':id' => $row['id'],
            ]);
            $match = $fuzzyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($match) {
                echo "  MERGE (fuzzy) #{$row['id']}: \"{$row['street']}\" → #{$match['id']}: \"{$match['street']}\"\n";
            }
        }
    }
    
    if ($match) {
        if (!$dryRun) {
            $updates = ["popularity_score = popularity_score + " . (int)$row['popularity_score']];
            $params = [':id' => $match['id']];
            $updates[] = "source = 'customer'";
            
            if (!empty($row['neighborhood']) && empty($match['neighborhood'])) {
                $updates[] = "neighborhood = :nb";
                $updates[] = "neighborhood_normalized = :nbN";
                $params[':nb'] = $row['neighborhood'];
                $params[':nbN'] = $row['neighborhood_normalized'];
            }
            
            $pdo->prepare("UPDATE address_streets SET " . implode(', ', $updates) . " WHERE id = :id")->execute($params);
            $pdo->prepare("DELETE FROM address_streets WHERE id = :id")->execute([':id' => $row['id']]);
        }
        $mergedStep3++;
    } else {
        echo "  SKIP #{$row['id']}: \"{$row['street']}\" (no counterpart found)\n";
    }
}
echo "  → {$mergedStep3} mesclados\n\n";

// =========================================================================
// Step 4: Deduplicate remaining entries by canonical name (same city)
// =========================================================================
echo "=== Step 4: Deduplicar restantes por nome canônico ===\n";

$stmt = $pdo->query("
    SELECT id, street, street_normalized, source, neighborhood, neighborhood_normalized,
           city_normalized, company_id, popularity_score
    FROM address_streets
    ORDER BY company_id, city_normalized, street
");
$allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by (company_id, city_normalized, canonical_name_with_expanded_prefix)
// This keeps "Rua Brasil" and "Avenida Brasil" separate (different street types)
// but merges "R. Bruno" + "Rua Bruno" (same type, different abbreviation)
$groups = [];
$bareEntries = []; // entries without standard prefix
foreach ($allRows as $row) {
    $canonical = AddressAutocompleteService::normalize(AddressAutocompleteService::canonicalStreetName($row['street']));
    $hasPrefix = preg_match('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s/', $canonical);
    $baseKey = $row['company_id'] . '|' . $row['city_normalized'] . '|';
    
    if ($hasPrefix) {
        $groups[$baseKey . $canonical][] = $row;
    } else {
        // Try to merge bare names into prefixed groups
        $stripped = $canonical;
        $merged = false;
        foreach ($groups as $gKey => &$gEntries) {
            if (strpos($gKey, $baseKey) !== 0) continue;
            $gCanonical = substr($gKey, strlen($baseKey));
            $gStripped = AddressAutocompleteService::stripStreetPrefix($gCanonical);
            if ($gStripped === $stripped) {
                $gEntries[] = $row;
                $merged = true;
                break;
            }
        }
        unset($gEntries);
        if (!$merged) {
            $groups[$baseKey . '_bare:' . $canonical][] = $row;
        }
    }
}

$dedupDeleted = 0;
foreach ($groups as $key => $entries) {
    if (count($entries) <= 1) continue;
    
    // Sort: customer first, then popularity DESC, then has-prefix, then has-neighborhood
    usort($entries, function($a, $b) {
        $sourceRank = ['customer' => 4, 'manual' => 3, 'learned' => 2, 'osm' => 1, 'nominatim' => 0];
        $aRank = $sourceRank[$a['source']] ?? 0;
        $bRank = $sourceRank[$b['source']] ?? 0;
        if ($aRank !== $bRank) return $bRank - $aRank;
        
        if ($a['popularity_score'] !== $b['popularity_score']) return $b['popularity_score'] - $a['popularity_score'];
        
        $aPrefix = preg_match('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s/i', $a['street']);
        $bPrefix = preg_match('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s/i', $b['street']);
        if ($aPrefix !== $bPrefix) return $bPrefix - $aPrefix;
        
        $aNb = !empty($a['neighborhood']);
        $bNb = !empty($b['neighborhood']);
        return (int)$bNb - (int)$aNb;
    });
    
    $keeper = $entries[0];
    $losers = array_slice($entries, 1);
    
    // Only merge if losers and keeper share same canonical name
    foreach ($losers as $loser) {
        // Only auto-merge if neighborhoods overlap or one is empty
        $keeperNb = $keeper['neighborhood_normalized'];
        $loserNb = $loser['neighborhood_normalized'];
        $nbMatch = ($keeperNb === $loserNb || $keeperNb === '' || $loserNb === '');
        
        if (!$nbMatch) continue; // Different neighborhoods = different locations, keep both
        
        echo "  DEDUP: keep #{$keeper['id']} \"{$keeper['street']}\" ({$keeper['source']}, {$keeper['neighborhood']}), "
           . "delete #{$loser['id']} \"{$loser['street']}\" ({$loser['source']}, {$loser['neighborhood']})\n";
        
        if (!$dryRun) {
            // Transfer popularity
            $pdo->prepare("
                UPDATE address_streets SET popularity_score = popularity_score + :pop WHERE id = :id
            ")->execute([':pop' => (int)$loser['popularity_score'], ':id' => $keeper['id']]);
            
            // Inherit neighborhood if keeper lacks one
            if (empty($keeper['neighborhood']) && !empty($loser['neighborhood'])) {
                $pdo->prepare("
                    UPDATE address_streets SET neighborhood = :nb, neighborhood_normalized = :nbN WHERE id = :id
                ")->execute([
                    ':nb' => $loser['neighborhood'],
                    ':nbN' => $loser['neighborhood_normalized'],
                    ':id' => $keeper['id'],
                ]);
            }
            
            $pdo->prepare("DELETE FROM address_streets WHERE id = :id")->execute([':id' => $loser['id']]);
        }
        $dedupDeleted++;
    }
}
echo "  → {$dedupDeleted} duplicatas removidas\n\n";

// =========================================================================
// Summary
// =========================================================================
$totalRemoved = $deleted + $merged + $mergedStep3 + $dedupDeleted;
echo "=== Resumo ===\n";
echo "  Lixo removido: {$deleted}\n";
echo "  Prefixos expandidos: {$updated}\n";
echo "  Mesclados (Step 2): {$merged}\n";
echo "  Mesclados sem prefixo (Step 3): {$mergedStep3}\n";
echo "  Duplicatas removidas (Step 4): {$dedupDeleted}\n";
echo "  Total removido: {$totalRemoved}\n";

// Count remaining
$count = $pdo->query("SELECT COUNT(*) FROM address_streets WHERE company_id = 1")->fetchColumn();
echo "  Entries restantes: {$count}\n";

echo "\n[" . date('Y-m-d H:i:s') . "] " . ($dryRun ? "DRY-RUN concluído (nenhuma alteração feita)" : "Cleanup concluído") . "\n";
