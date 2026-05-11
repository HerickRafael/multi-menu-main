#!/usr/bin/env php
<?php

/**
 * Seed address_streets from real customer order data (customer_addresses table).
 * 
 * This script mines REAL addresses used by customers in previous orders,
 * grouped by street + neighborhood + city, and upserts them into address_streets
 * with source='customer' and a popularity boost.
 * 
 * Customer data takes priority: if a street already exists from OSM,
 * the neighborhood is overwritten with the customer-provided value (which is
 * based on the delivery_zone selected at checkout — more accurate than OSM/Haversine).
 * 
 * Filters applied:
 *   - Street name must be >= 5 chars
 *   - Street must start with a valid prefix (Rua, Avenida, Travessa, etc.) OR be >= 8 chars
 *   - Strips house numbers accidentally appended to street names
 * 
 * Usage: php seed_streets_from_customers.php [--company=ID] [--dry-run] [--verbose]
 *   Without --company: processes ALL companies
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/services/AddressAutocompleteService.php';

// Parse CLI args
$options = getopt('', ['company:', 'dry-run', 'verbose']);
$companyFilter = isset($options['company']) ? (int)$options['company'] : 0;
$dryRun = isset($options['dry-run']);
$verbose = isset($options['verbose']);

$db = db();

echo "=== Seed Streets from Customer Orders ===\n";
echo "Mode: " . ($dryRun ? 'DRY RUN' : 'LIVE') . "\n\n";

// Get companies to process
if ($companyFilter > 0) {
    $companies = [['id' => $companyFilter]];
} else {
    $companies = $db->query("SELECT id FROM companies WHERE active = 1")->fetchAll(PDO::FETCH_ASSOC);
}

$totalSeeded = 0;
$totalSkipped = 0;
$totalUpdated = 0;

foreach ($companies as $company) {
    $companyId = (int)$company['id'];
    echo "Company #{$companyId}:\n";

    // Fetch unique streets from customer_addresses, grouped by street+neighborhood+city
    // COUNT(*) gives us a natural popularity indicator
    $stmt = $db->prepare("
        SELECT 
            ca.street,
            ca.neighborhood,
            ca.city,
            COUNT(*) as order_count
        FROM customer_addresses ca
        WHERE ca.company_id = :cid
          AND ca.street IS NOT NULL 
          AND TRIM(ca.street) != ''
          AND ca.city IS NOT NULL
          AND TRIM(ca.city) != ''
        GROUP BY ca.street, ca.neighborhood, ca.city
        ORDER BY order_count DESC
    ");
    $stmt->execute([':cid' => $companyId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo "  No customer addresses found.\n\n";
        continue;
    }

    echo "  Found " . count($rows) . " unique street entries from customers.\n";

    foreach ($rows as $row) {
        $street = trim($row['street']);
        $neighborhood = trim($row['neighborhood'] ?? '');
        $city = trim($row['city']);
        $orderCount = (int)$row['order_count'];

        // === Filtering ===

        // Strip trailing house numbers (e.g., "Valdir Azevedo Fonseca 457 Última Casa")
        // Keep the street name part only — numbers at end after the name
        $cleanStreet = preg_replace('/[,\s]+\d{1,5}\s*.*$/u', '', $street);
        if ($cleanStreet !== $street && mb_strlen($cleanStreet) >= 5) {
            if ($verbose) echo "    Cleaned: '{$street}' → '{$cleanStreet}'\n";
            $street = $cleanStreet;
        }
        // Strip trailing punctuation
        $street = rtrim($street, ' ,;.');

        // Min 5 chars
        if (mb_strlen($street) < 5) {
            if ($verbose) echo "    SKIP (too short): '{$street}'\n";
            $totalSkipped++;
            continue;
        }

        // Must look like a street name: starts with prefix OR is >= 8 chars (numbered streets like "Rua 17")
        $hasPrefix = (bool)preg_match('/^(rua|avenida|av\.?|travessa|tv\.?|beco|alameda|praca|praça|largo|estrada|rodovia|servidao|passagem|viela|r\.)\s+/ui', $street);
        if (!$hasPrefix && mb_strlen($street) < 8) {
            if ($verbose) echo "    SKIP (no prefix, short): '{$street}'\n";
            $totalSkipped++;
            continue;
        }

        // Skip if neighborhood is empty (unreliable data)
        if ($neighborhood === '') {
            if ($verbose) echo "    SKIP (no neighborhood): '{$street}'\n";
            $totalSkipped++;
            continue;
        }

        // === Upsert ===
        $streetNorm = AddressAutocompleteService::normalize($street);
        $cityNorm = AddressAutocompleteService::normalize($city);
        $nbNorm = AddressAutocompleteService::normalize($neighborhood);

        // Popularity boost: base 10 + (order_count * 5), capped at 50
        $popularityBoost = min(50, 10 + ($orderCount * 5));

        if ($verbose) {
            echo "    UPSERT: '{$street}' | {$neighborhood} | {$city} (orders: {$orderCount}, boost: +{$popularityBoost})\n";
        }

        if (!$dryRun) {
            $sql = "INSERT INTO address_streets 
                    (company_id, city, city_normalized, neighborhood, neighborhood_normalized, 
                     street, street_normalized, lat, lon, source, popularity_score)
                    VALUES (:cid, :city, :cityN, :nb, :nbN, :street, :streetN, NULL, NULL, 'customer', :pop)
                    ON DUPLICATE KEY UPDATE 
                        neighborhood = VALUES(neighborhood),
                        neighborhood_normalized = VALUES(neighborhood_normalized),
                        source = 'customer',
                        popularity_score = GREATEST(popularity_score, VALUES(popularity_score)),
                        updated_at = NOW()";

            try {
                $stmt2 = $db->prepare($sql);
                $stmt2->execute([
                    ':cid' => $companyId,
                    ':city' => $city,
                    ':cityN' => $cityNorm,
                    ':nb' => $neighborhood,
                    ':nbN' => $nbNorm,
                    ':street' => $street,
                    ':streetN' => $streetNorm,
                    ':pop' => $popularityBoost,
                ]);

                if ($stmt2->rowCount() === 1) {
                    $totalSeeded++;
                } else {
                    $totalUpdated++;
                }
            } catch (PDOException $e) {
                echo "    ERROR: " . $e->getMessage() . "\n";
            }
        } else {
            $totalSeeded++;
        }
    }

    echo "\n";
}

echo "=== Summary ===\n";
echo "Seeded (new):  {$totalSeeded}\n";
echo "Updated (existing): {$totalUpdated}\n";
echo "Skipped (filtered): {$totalSkipped}\n";
echo ($dryRun ? "(DRY RUN - no changes made)\n" : "Done.\n");
