<?php

declare(strict_types=1);

/**
 * AddressAutocompleteService - Intelligent Address Search with Confidence Control
 * 
 * Architecture:
 *   Layer 1: Redis cache (hot queries, <5ms)
 *   Layer 2: MySQL FULLTEXT search (prefix match, <50ms)
 *   Layer 3: MySQL LIKE fallback (partial match, <100ms)
 *   Layer 4: Fuzzy search with Levenshtein distance (PHP-side, <200ms)
 *   NO real-time external API calls during search
 * 
 * Trust model:
 *   - OpenStreetMap = source of truth for confidence validation
 *   - OSM-imported data = highest trust (confidence 0.90)
 *   - Customer/learned streets validated against OSM in background
 *   - If confirmed in OSM → confidence boosted to 0.85, source upgraded
 *   - If NOT in OSM → stays at original lower confidence
 * 
 * Quality control:
 *   - Background OSM validation via Nominatim (cron, not real-time)
 *   - Neighborhood-street consistency validation
 *   - Suspect flagging for inconsistent data
 *   - Threshold-based promotion (pending → active)
 *   - Automatic cleanup of low-confidence data via cron
 */
class AddressAutocompleteService
{
    private \PDO $db;
    private int $companyId;
    
    private const MAX_RESULTS = 10;
    private const REDIS_TTL = 900;       // 15 min
    private const REDIS_PREFIX = 'ac:';
    private const MIN_QUERY_LENGTH = 2;
    private const LEVENSHTEIN_THRESHOLD = 3;
    
    // Minimum distinct customers to promote pending → active
    private const PROMOTION_THRESHOLD = 4;
    
    // Confidence thresholds
    private const CONFIDENCE_OSM = 0.90;
    private const CONFIDENCE_MANUAL = 0.85;
    private const CONFIDENCE_CUSTOMER_NEW = 0.60;
    private const CONFIDENCE_CUSTOMER_BOOST = 0.03; // per consistent observation
    private const CONFIDENCE_LEARNED = 0.20;
    private const CONFIDENCE_NOMINATIM = 0.50;
    private const CONFIDENCE_OSM_VALIDATED = 0.85;   // non-OSM street confirmed in OSM
    private const CONFIDENCE_SUSPECT_PENALTY = 0.30;
    private const CONFIDENCE_MIN_VISIBLE = 0.10;     // below this → never show
    
    // Ranking weights
    private const SOURCE_BOOST = [
        'customer'  => 15,
        'manual'    => 10,
        'learned'   => 0,
        'osm'       => 5,
        'nominatim' => 0,
    ];
    
    public function __construct(\PDO $db, int $companyId)
    {
        $this->db = $db;
        $this->companyId = $companyId;
    }
    
    // ========================================================================
    // Main Search (4-layer, NO external API in real-time)
    // ========================================================================
    
    /**
     * Search for streets. Only returns active, trusted entries.
     * Flow: neighborhood-scoped first → city-wide fallback → limit 10.
     * NEVER calls external APIs during search.
     */
    public function search(string $query, string $city, string $neighborhood = ''): array
    {
        $start = microtime(true);
        $query = trim($query);
        $city = trim($city);
        
        if (mb_strlen($query) < self::MIN_QUERY_LENGTH || $city === '') {
            return ['results' => [], 'source' => 'empty', 'timing_ms' => 0];
        }
        
        $cityNorm = self::normalize($city);
        $queryNorm = self::normalize($query);
        $nbNorm = self::normalize($neighborhood);
        
        // Layer 1: Redis cache
        $cacheKey = $this->buildCacheKey($cityNorm, $nbNorm, $queryNorm);
        $cached = $this->getFromRedis($cacheKey);
        if ($cached !== null) {
            return [
                'results' => $cached,
                'source' => 'redis',
                'timing_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        }
        
        $results = [];
        $source = 'mysql';
        
        // Layer 2: MySQL FULLTEXT — neighborhood-scoped first
        if ($nbNorm !== '') {
            $results = $this->searchFulltext($cityNorm, $queryNorm, $nbNorm);
        }
        
        // Expand to city-wide if insufficient
        if (count($results) < 3) {
            $cityResults = $this->searchFulltext($cityNorm, $queryNorm);
            $results = $this->mergeResults($results, $cityResults);
        }
        
        // Layer 3: LIKE fallback
        if (count($results) < 3) {
            if ($nbNorm !== '') {
                $likeNb = $this->searchLike($cityNorm, $queryNorm, $nbNorm);
                $results = $this->mergeResults($results, $likeNb);
            }
            if (count($results) < 3) {
                $likeCity = $this->searchLike($cityNorm, $queryNorm);
                $results = $this->mergeResults($results, $likeCity);
            }
        }
        
        // Layer 4: Fuzzy matching
        if (count($results) < 3 && mb_strlen($queryNorm) >= 4) {
            $fuzzyResults = $this->searchFuzzy($cityNorm, $queryNorm);
            $results = $this->mergeResults($results, $fuzzyResults);
        }
        
        // Final dedup
        $results = $this->deduplicateResults($results);
        
        // Rank by confidence + popularity + relevance + neighborhood match
        $results = $this->rankResults($results, $queryNorm, $nbNorm);
        
        // Limit
        $results = array_slice($results, 0, self::MAX_RESULTS);
        
        // Cache
        if (!empty($results)) {
            $this->setInRedis($cacheKey, $results);
        }
        
        return [
            'results' => $results,
            'source' => $source,
            'timing_ms' => round((microtime(true) - $start) * 1000, 2),
        ];
    }
    
    // ========================================================================
    // Search Layers (all filter by status='active' only)
    // ========================================================================
    
    /**
     * Layer 2: FULLTEXT search. Only returns active entries with confidence >= threshold.
     */
    private function searchFulltext(string $cityNorm, string $queryNorm, string $nbNorm = ''): array
    {
        if (mb_strlen($queryNorm) < 3) return [];
        
        $ftQuery = $this->escapeFulltext($queryNorm);
        $words = preg_split('/\s+/', $ftQuery, -1, PREG_SPLIT_NO_EMPTY);
        $booleanQuery = '';
        foreach ($words as $word) {
            if (mb_strlen($word) >= 3) {
                $booleanQuery .= '+' . $word . '* ';
            }
        }
        $booleanQuery = trim($booleanQuery);
        if ($booleanQuery === '') return [];
        
        $nbClause = '';
        if ($nbNorm !== '') {
            $nbClause = 'AND neighborhood_normalized = :nb';
        }
        
        $sql = "SELECT id, street, neighborhood, lat, lon, popularity_score, 
                       confidence_score, source, status,
                       MATCH(street_normalized) AGAINST(:ft IN BOOLEAN MODE) AS relevance
                FROM address_streets
                WHERE company_id = :cid
                  AND city_normalized = :city
                  AND status = 'active'
                  AND confidence_score >= :minConf
                  AND MATCH(street_normalized) AGAINST(:ft2 IN BOOLEAN MODE)
                  {$nbClause}
                ORDER BY relevance DESC, confidence_score DESC, popularity_score DESC
                LIMIT :lim";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':ft', $booleanQuery, \PDO::PARAM_STR);
        $stmt->bindValue(':ft2', $booleanQuery, \PDO::PARAM_STR);
        $stmt->bindValue(':cid', $this->companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':city', $cityNorm, \PDO::PARAM_STR);
        $stmt->bindValue(':minConf', self::CONFIDENCE_MIN_VISIBLE, \PDO::PARAM_STR);
        $stmt->bindValue(':lim', self::MAX_RESULTS * 2, \PDO::PARAM_INT);
        if ($nbNorm !== '') {
            $stmt->bindValue(':nb', $nbNorm, \PDO::PARAM_STR);
        }
        $stmt->execute();
        
        return $this->formatRows($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Layer 3: LIKE fallback. Only returns active entries.
     */
    private function searchLike(string $cityNorm, string $queryNorm, string $nbNorm = ''): array
    {
        $nbClause = '';
        if ($nbNorm !== '') {
            $nbClause = 'AND neighborhood_normalized = :nb';
        }
        
        $sql = "SELECT id, street, neighborhood, lat, lon, popularity_score, 
                       confidence_score, source, status
                FROM address_streets
                WHERE company_id = :cid
                  AND city_normalized = :city
                  AND status = 'active'
                  AND confidence_score >= :minConf
                  AND street_normalized LIKE :q
                  {$nbClause}
                ORDER BY confidence_score DESC, popularity_score DESC, street ASC
                LIMIT :lim";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $this->companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':city', $cityNorm, \PDO::PARAM_STR);
        $stmt->bindValue(':minConf', self::CONFIDENCE_MIN_VISIBLE, \PDO::PARAM_STR);
        $stmt->bindValue(':q', '%' . $this->escapeLike($queryNorm) . '%', \PDO::PARAM_STR);
        $stmt->bindValue(':lim', self::MAX_RESULTS * 2, \PDO::PARAM_INT);
        if ($nbNorm !== '') {
            $stmt->bindValue(':nb', $nbNorm, \PDO::PARAM_STR);
        }
        $stmt->execute();
        
        return $this->formatRows($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
    
    /**
     * Layer 4: Fuzzy matching. Only returns active entries.
     */
    private function searchFuzzy(string $cityNorm, string $queryNorm): array
    {
        $stripped = preg_replace('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s+/u', '', $queryNorm);
        $prefix = mb_substr($stripped, 0, 2);
        if (mb_strlen($prefix) < 2) return [];
        
        $sql = "SELECT id, street, street_normalized, neighborhood, lat, lon, 
                       popularity_score, confidence_score, source, status
                FROM address_streets
                WHERE company_id = :cid
                  AND city_normalized = :city
                  AND status = 'active'
                  AND confidence_score >= :minConf
                  AND street_normalized LIKE :prefix
                LIMIT 500";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $this->companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':city', $cityNorm, \PDO::PARAM_STR);
        $stmt->bindValue(':minConf', self::CONFIDENCE_MIN_VISIBLE, \PDO::PARAM_STR);
        $stmt->bindValue(':prefix', '%' . $this->escapeLike($prefix) . '%', \PDO::PARAM_STR);
        $stmt->execute();
        
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $results = [];
        
        $queryStripped = preg_replace('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s+/u', '', $queryNorm);
        $queryWords = preg_split('/\s+/', $queryStripped);
        
        foreach ($candidates as $row) {
            $streetNorm = $row['street_normalized'];
            $streetStripped = preg_replace('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s+/u', '', $streetNorm);
            $streetWords = preg_split('/\s+/', $streetStripped);
            
            $matched = false;
            foreach ($queryWords as $qWord) {
                if (mb_strlen($qWord) < 3) continue;
                foreach ($streetWords as $sWord) {
                    if (mb_strlen($sWord) < 3) continue;
                    $compareLen = mb_strlen($qWord);
                    $sWordTrunc = mb_substr($sWord, 0, $compareLen);
                    $threshold = $compareLen <= 4 ? 1 : self::LEVENSHTEIN_THRESHOLD;
                    if (levenshtein($qWord, $sWordTrunc) <= $threshold) {
                        $matched = true;
                        break 2;
                    }
                }
            }
            
            if ($matched) {
                $results[] = [
                    'id' => (int)$row['id'],
                    'street' => $row['street'],
                    'neighborhood' => $row['neighborhood'] ?? '',
                    'lat' => $row['lat'] !== null ? (float)$row['lat'] : null,
                    'lon' => $row['lon'] !== null ? (float)$row['lon'] : null,
                    'popularity' => (int)$row['popularity_score'],
                    'confidence' => (float)$row['confidence_score'],
                    'source' => $row['source'] ?? 'osm',
                ];
            }
            
            if (count($results) >= self::MAX_RESULTS) break;
        }
        
        return $results;
    }
    
    // ========================================================================
    // Customer Learning (observations, not truth)
    // ========================================================================
    
    /**
     * Record a customer order's address as an observation.
     * Does NOT blindly overwrite neighborhood — validates consistency first.
     * Tracks distinct customers for promotion threshold.
     *
     * @param string $customerPhone Raw phone for hashing (never stored raw)
     */
    public function syncFromOrder(string $city, string $neighborhood, string $street, string $customerPhone = ''): void
    {
        $street = trim($street);
        $city = trim($city);
        $neighborhood = trim($neighborhood);
        
        if ($street === '' || $city === '') return;
        if (mb_strlen($street) < 3) return;
        
        $street = self::canonicalStreetName($street);
        if (mb_strlen($street) < 5) return;
        
        $streetNorm = self::normalize($street);
        $cityNorm = self::normalize($city);
        $nbNorm = self::normalize($neighborhood);
        
        // Check if street already exists
        $existing = $this->findStreet($cityNorm, $streetNorm);
        
        if ($existing) {
            $this->processExistingStreetObservation($existing, $neighborhood, $nbNorm, $customerPhone);
        } else {
            $this->insertCustomerStreet($city, $cityNorm, $neighborhood, $nbNorm, $street, $streetNorm, $customerPhone);
        }
        
        $this->invalidateCityCacheByName($cityNorm);
    }
    
    /**
     * Process an observation for an existing street.
     * Validates neighborhood consistency before any updates.
     */
    private function processExistingStreetObservation(array $existing, string $neighborhood, string $nbNorm, string $customerPhone): void
    {
        $streetId = (int)$existing['id'];
        $existingNbNorm = self::normalize($existing['neighborhood'] ?? '');
        $existingSource = $existing['source'];
        
        // Track distinct customer
        $this->trackCustomer($streetId, $customerPhone);
        
        // Always boost popularity (observation = usage evidence)
        $this->db->prepare(
            "UPDATE address_streets SET popularity_score = popularity_score + 2, updated_at = NOW() WHERE id = :id"
        )->execute([':id' => $streetId]);
        
        // Neighborhood consistency check
        if ($nbNorm !== '' && $existingNbNorm !== '' && $existingNbNorm !== $nbNorm) {
            // Conflict: customer says different neighborhood than what we have
            if (in_array($existingSource, ['osm', 'manual'], true)) {
                // OSM/manual data is trusted — don't overwrite
                $this->adjustConfidence($streetId, -0.01);
                error_log("[AddressAutocomplete] Neighborhood conflict for #{$streetId}: existing={$existingNbNorm}, customer={$nbNorm} — trusted source, ignoring");
            } else {
                // Non-trusted source with conflict
                $conf = (float)$existing['confidence_score'];
                if ($conf < 0.50) {
                    $this->db->prepare(
                        "UPDATE address_streets SET status = 'suspect' WHERE id = :id AND status != 'active'"
                    )->execute([':id' => $streetId]);
                }
            }
        } elseif ($nbNorm !== '' && $existingNbNorm === '') {
            // Street has no neighborhood — adopt customer's (safe fill)
            $this->db->prepare(
                "UPDATE address_streets SET neighborhood = :nb, neighborhood_normalized = :nbN WHERE id = :id"
            )->execute([':nb' => $neighborhood, ':nbN' => $nbNorm, ':id' => $streetId]);
        }
        
        // Boost confidence on consistent observations
        if ($nbNorm === '' || $existingNbNorm === '' || $existingNbNorm === $nbNorm) {
            $this->adjustConfidence($streetId, self::CONFIDENCE_CUSTOMER_BOOST);
        }
        
        // Upgrade source from learned/nominatim to customer
        if (in_array($existingSource, ['learned', 'nominatim'], true)) {
            $this->db->prepare(
                "UPDATE address_streets SET source = 'customer' WHERE id = :id"
            )->execute([':id' => $streetId]);
        }
        
        // Check for promotion (pending → active)
        $this->checkPromotion($streetId);
    }
    
    /**
     * Insert a new customer-sourced street.
     * Starts with moderate confidence; active immediately since it came from a real order.
     */
    private function insertCustomerStreet(string $city, string $cityNorm, string $neighborhood, string $nbNorm, string $street, string $streetNorm, string $customerPhone): void
    {
        $sql = "INSERT IGNORE INTO address_streets 
                (company_id, city, city_normalized, neighborhood, neighborhood_normalized, 
                 street, street_normalized, lat, lon, source, popularity_score,
                 confidence_score, status, distinct_customers)
                VALUES (:cid, :city, :cityN, :nb, :nbN, :street, :streetN, 
                        NULL, NULL, 'customer', 10, :conf, 'active', 1)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cid' => $this->companyId,
                ':city' => $city,
                ':cityN' => $cityNorm,
                ':nb' => $neighborhood,
                ':nbN' => $nbNorm,
                ':street' => $street,
                ':streetN' => $streetNorm,
                ':conf' => self::CONFIDENCE_CUSTOMER_NEW,
            ]);
            
            $streetId = (int)$this->db->lastInsertId();
            if ($streetId > 0) {
                $this->trackCustomer($streetId, $customerPhone);
            }
        } catch (\PDOException $e) {
            error_log('[AddressAutocomplete] insertCustomerStreet failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Record a user-typed street as pending (learned).
     * Does NOT appear in autocomplete until validated by 4+ distinct customers.
     */
    public function learnStreet(string $city, string $neighborhood, string $street): void
    {
        $street = trim($street);
        
        if (mb_strlen($street) < 10) return;
        
        $streetNorm = self::normalize($street);
        if (!preg_match('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia|av)\s+\S{3,}/ui', $streetNorm)) return;
        
        $cityNorm = self::normalize($city);
        
        // Check if already exists
        $stmt = $this->db->prepare(
            "SELECT id FROM address_streets 
             WHERE company_id = :cid AND city_normalized = :city AND street_normalized = :street LIMIT 1"
        );
        $stmt->execute([':cid' => $this->companyId, ':city' => $cityNorm, ':street' => $streetNorm]);
        if ($stmt->fetchColumn() !== false) return;
        
        $street = self::canonicalStreetName($street);
        $neighborhood = trim($neighborhood);
        
        $sql = "INSERT IGNORE INTO address_streets 
                (company_id, city, city_normalized, neighborhood, neighborhood_normalized, 
                 street, street_normalized, lat, lon, source, popularity_score,
                 confidence_score, status, distinct_customers)
                VALUES (:cid, :city, :cityN, :nb, :nbN, :street, :streetN, 
                        NULL, NULL, 'learned', 1, :conf, 'pending', 0)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cid' => $this->companyId,
                ':city' => $city,
                ':cityN' => $cityNorm,
                ':nb' => $neighborhood,
                ':nbN' => self::normalize($neighborhood),
                ':street' => $street,
                ':streetN' => self::normalize($street),
                ':conf' => self::CONFIDENCE_LEARNED,
            ]);
        } catch (\PDOException $e) {
            error_log('[AddressAutocomplete] learnStreet failed: ' . $e->getMessage());
        }
    }
    
    // ========================================================================
    // Distinct Customer Tracking & Promotion
    // ========================================================================
    
    /**
     * Track a distinct customer for a street entry.
     */
    private function trackCustomer(int $streetId, string $customerPhone): void
    {
        if ($streetId <= 0 || $customerPhone === '') return;
        
        $hash = hash('sha256', $customerPhone);
        
        try {
            $this->db->prepare(
                "INSERT IGNORE INTO address_street_customers (street_id, customer_hash) VALUES (:sid, :hash)"
            )->execute([':sid' => $streetId, ':hash' => $hash]);
            
            // Update distinct_customers count
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM address_street_customers WHERE street_id = :sid"
            );
            $stmt->execute([':sid' => $streetId]);
            $count = (int)$stmt->fetchColumn();
            
            $this->db->prepare(
                "UPDATE address_streets SET distinct_customers = :cnt WHERE id = :id"
            )->execute([':cnt' => $count, ':id' => $streetId]);
        } catch (\PDOException $e) {
            error_log('[AddressAutocomplete] trackCustomer failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if a pending/suspect street should be promoted to active.
     * Requires PROMOTION_THRESHOLD distinct customers AND confidence >= 0.40.
     */
    private function checkPromotion(int $streetId): void
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT status, distinct_customers, confidence_score 
                 FROM address_streets WHERE id = :id"
            );
            $stmt->execute([':id' => $streetId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$row || $row['status'] === 'active') return;
            
            $customers = (int)$row['distinct_customers'];
            $confidence = (float)$row['confidence_score'];
            
            if ($customers >= self::PROMOTION_THRESHOLD && $confidence >= 0.40) {
                $this->db->prepare(
                    "UPDATE address_streets SET status = 'active' WHERE id = :id"
                )->execute([':id' => $streetId]);
                
                error_log("[AddressAutocomplete] Promoted #{$streetId} to active ({$customers} customers, confidence {$confidence})");
            }
        } catch (\PDOException $e) {
            error_log('[AddressAutocomplete] checkPromotion failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Adjust confidence score (clamped to 0.00-1.00).
     */
    private function adjustConfidence(int $streetId, float $delta): void
    {
        try {
            $this->db->prepare(
                "UPDATE address_streets 
                 SET confidence_score = LEAST(1.00, GREATEST(0.00, confidence_score + :delta))
                 WHERE id = :id"
            )->execute([':delta' => $delta, ':id' => $streetId]);
        } catch (\PDOException $e) {
            // Non-critical
        }
    }
    
    // ========================================================================
    // Popularity Tracking
    // ========================================================================
    
    /**
     * Increment popularity when user selects a street from autocomplete.
     * Also gives a small confidence boost (validated by user selection).
     */
    public function incrementPopularity(int $streetId): void
    {
        try {
            $this->db->prepare(
                "UPDATE address_streets 
                 SET popularity_score = popularity_score + 1,
                     confidence_score = LEAST(1.00, confidence_score + 0.005)
                 WHERE id = :id AND company_id = :cid"
            )->execute([':id' => $streetId, ':cid' => $this->companyId]);
            
            $this->invalidateCityCache($streetId);
        } catch (\PDOException $e) {
            error_log('[AddressAutocomplete] incrementPopularity failed: ' . $e->getMessage());
        }
    }
    
    // ========================================================================
    // Persistence
    // ========================================================================
    
    /**
     * Find an existing street by normalized city + street.
     */
    private function findStreet(string $cityNorm, string $streetNorm): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, street, neighborhood, neighborhood_normalized, source, 
                    confidence_score, status, distinct_customers, popularity_score
             FROM address_streets 
             WHERE company_id = :cid AND city_normalized = :city AND street_normalized = :street 
             LIMIT 1"
        );
        $stmt->execute([
            ':cid' => $this->companyId,
            ':city' => $cityNorm,
            ':street' => $streetNorm,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
    
    /**
     * Persist a street to local DB (for OSM/Nominatim background imports).
     */
    public function persistStreet(string $city, string $neighborhood, string $street, ?float $lat, ?float $lon, string $source = 'osm', string $status = 'active'): void
    {
        $street = self::canonicalStreetName($street);
        
        $confidence = match ($source) {
            'osm' => self::CONFIDENCE_OSM,
            'nominatim' => self::CONFIDENCE_NOMINATIM,
            'manual' => self::CONFIDENCE_MANUAL,
            default => 0.50,
        };
        
        $sql = "INSERT IGNORE INTO address_streets 
                (company_id, city, city_normalized, neighborhood, neighborhood_normalized, 
                 street, street_normalized, lat, lon, source, confidence_score, status)
                VALUES (:cid, :city, :cityN, :nb, :nbN, :street, :streetN, :lat, :lon, :src, :conf, :status)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cid' => $this->companyId,
                ':city' => $city,
                ':cityN' => self::normalize($city),
                ':nb' => $neighborhood,
                ':nbN' => self::normalize($neighborhood),
                ':street' => $street,
                ':streetN' => self::normalize($street),
                ':lat' => $lat,
                ':lon' => $lon,
                ':src' => $source,
                ':conf' => $confidence,
                ':status' => $status,
            ]);
        } catch (\PDOException $e) {
            error_log('[AddressAutocomplete] persistStreet failed: ' . $e->getMessage());
        }
    }
    
    // ========================================================================
    // Nominatim (background enrichment ONLY, NOT called during search)
    // ========================================================================
    
    /**
     * Search Nominatim for streets. For background/cron use ONLY.
     * Persists results to local DB for future searches.
     * NEVER called during real-time search flow.
     */
    public function searchNominatimBackground(string $city, string $query): array
    {
        $searchQuery = $query . ', ' . $city . ', RS, Brazil';
        
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $searchQuery,
            'format' => 'json',
            'limit' => 8,
            'countrycodes' => 'br',
            'addressdetails' => 1,
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT      => 'MultiMenu-Delivery/1.0 (contact@multimenu.com.br)',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || $response === false) return [];
        
        $data = json_decode($response, true);
        if (!is_array($data)) return [];
        
        $results = [];
        $cityNorm = self::normalize($city);
        
        foreach ($data as $item) {
            $type = $item['type'] ?? '';
            if (!in_array($type, ['residential', 'tertiary', 'secondary', 'primary', 'trunk', 'road', 'living_street', 'pedestrian', 'service', 'unclassified', 'track'])) {
                $class = $item['class'] ?? '';
                if ($class !== 'highway') continue;
            }
            
            $name = $item['address']['road'] ?? ($item['name'] ?? '');
            if ($name === '') continue;
            
            $lat = isset($item['lat']) ? (float)$item['lat'] : null;
            $lon = isset($item['lon']) ? (float)$item['lon'] : null;
            
            $itemCity = $item['address']['city'] ?? ($item['address']['town'] ?? ($item['address']['municipality'] ?? ''));
            if ($itemCity !== '' && self::normalize($itemCity) !== $cityNorm) continue;
            
            $nb = $item['address']['suburb'] ?? ($item['address']['neighbourhood'] ?? '');
            
            $results[] = [
                'id' => 0,
                'street' => $name,
                'neighborhood' => $nb,
                'lat' => $lat,
                'lon' => $lon,
                'popularity' => 0,
                'source' => 'nominatim',
            ];
            
            if ($lat !== null) {
                $this->persistStreet($city, $nb, $name, $lat, $lon, 'nominatim', 'active');
            }
        }
        
        return $results;
    }
    
    // ========================================================================
    // Neighborhood Consistency Validation (for cron/background use)
    // ========================================================================
    
    /**
     * Validate street-neighborhood consistency for a city.
     * Streets associated with many conflicting neighborhoods get penalized.
     * Called from maintenance cron, NOT during search.
     */
    public function validateConsistency(): array
    {
        $sql = "SELECT street_normalized, 
                       GROUP_CONCAT(DISTINCT neighborhood_normalized) as neighborhoods,
                       COUNT(DISTINCT neighborhood_normalized) as nb_count
                FROM address_streets
                WHERE company_id = :cid 
                  AND neighborhood_normalized != ''
                GROUP BY company_id, city_normalized, street_normalized
                HAVING nb_count > 3";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $this->companyId]);
        $conflicts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $marked = 0;
        foreach ($conflicts as $row) {
            $this->db->prepare(
                "UPDATE address_streets 
                 SET confidence_score = GREATEST(0.10, confidence_score - :penalty)
                 WHERE company_id = :cid AND street_normalized = :street
                   AND source NOT IN ('osm', 'manual')"
            )->execute([
                ':penalty' => self::CONFIDENCE_SUSPECT_PENALTY,
                ':cid' => $this->companyId,
                ':street' => $row['street_normalized'],
            ]);
            $marked++;
        }
        
        return ['conflicts_found' => count($conflicts), 'penalized' => $marked];
    }
    
    /**
     * Promote pending streets that meet the threshold (for cron use).
     */
    public function promoteQualifiedPending(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE address_streets 
             SET status = 'active' 
             WHERE company_id = :cid 
               AND status = 'pending'
               AND distinct_customers >= :threshold
               AND confidence_score >= 0.40"
        );
        $stmt->execute([
            ':cid' => $this->companyId,
            ':threshold' => self::PROMOTION_THRESHOLD,
        ]);
        return $stmt->rowCount();
    }
    
    /**
     * Decay confidence for stale entries (for cron use).
     * Entries not updated in 90+ days get a small confidence reduction.
     */
    public function decayStaleConfidence(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE address_streets 
             SET confidence_score = GREATEST(0.05, confidence_score - 0.05)
             WHERE company_id = :cid 
               AND source NOT IN ('osm', 'manual')
               AND confidence_score > 0.10
               AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        $stmt->execute([':cid' => $this->companyId]);
        return $stmt->rowCount();
    }
    
    /**
     * Cleanup suspect entries older than 90 days with very low confidence (for cron use).
     */
    public function cleanupSuspect(): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM address_streets 
             WHERE company_id = :cid 
               AND status = 'suspect'
               AND confidence_score < 0.15
               AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        $stmt->execute([':cid' => $this->companyId]);
        return $stmt->rowCount();
    }

    // ========================================================================
    // OSM Validation (background — Nominatim as confidence authority)
    // ========================================================================

    /**
     * Validate non-OSM streets against OpenStreetMap via Nominatim.
     * Streets confirmed in OSM get confidence boosted to 0.85 and lat/lon enriched.
     * Streets NOT found stay at their current confidence.
     * 
     * Called from cron ONLY — respects Nominatim rate limit (1 req/s).
     * Processes up to $limit streets per run to avoid overloading.
     *
     * @return array{validated: int, not_found: int, errors: int}
     */
    public function validateAgainstOSM(int $limit = 50): array
    {
        // Select non-OSM streets that haven't been validated yet
        // (osm_id IS NULL means not yet checked against OSM)
        $stmt = $this->db->prepare(
            "SELECT id, city, street, street_normalized, neighborhood, lat, lon, source, confidence_score
             FROM address_streets
             WHERE company_id = :cid
               AND source NOT IN ('osm')
               AND osm_id IS NULL
               AND status IN ('active', 'pending')
             ORDER BY 
               CASE WHEN source = 'customer' THEN 0 ELSE 1 END,
               popularity_score DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':cid', $this->companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $streets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stats = ['validated' => 0, 'not_found' => 0, 'errors' => 0];

        foreach ($streets as $row) {
            // Rate limit: 1 request per second (Nominatim policy)
            usleep(1100000);

            $result = $this->checkStreetInOSM($row['city'], $row['street']);

            if ($result === null) {
                // API error — mark with osm_id = 0 so we don't retry immediately
                $this->db->prepare(
                    "UPDATE address_streets SET osm_id = 0 WHERE id = :id"
                )->execute([':id' => $row['id']]);
                $stats['errors']++;
                continue;
            }

            if ($result !== false) {
                // Confirmed in OSM — boost confidence
                $newConf = max((float)$row['confidence_score'], self::CONFIDENCE_OSM_VALIDATED);
                $updates = [
                    'confidence_score' => $newConf,
                    'osm_id' => (int)$result['osm_id'],
                ];

                // Enrich lat/lon if missing
                if ($row['lat'] === null && $result['lat'] !== null) {
                    $updates['lat'] = $result['lat'];
                    $updates['lon'] = $result['lon'];
                }

                // Enrich neighborhood if missing
                if (empty($row['neighborhood']) && !empty($result['neighborhood'])) {
                    $updates['neighborhood'] = $result['neighborhood'];
                    $updates['neighborhood_normalized'] = self::normalize($result['neighborhood']);
                }

                // Promote pending → active if OSM confirms existence
                $statusClause = '';
                $stmt2 = $this->db->prepare(
                    "SELECT status FROM address_streets WHERE id = :id"
                );
                $stmt2->execute([':id' => $row['id']]);
                $currentStatus = $stmt2->fetchColumn();
                if ($currentStatus === 'pending') {
                    $updates['status'] = 'active';
                }

                $setClauses = [];
                $params = [':id' => $row['id']];
                foreach ($updates as $col => $val) {
                    $setClauses[] = "{$col} = :{$col}";
                    $params[":{$col}"] = $val;
                }

                $this->db->prepare(
                    "UPDATE address_streets SET " . implode(', ', $setClauses) . ", updated_at = NOW() WHERE id = :id"
                )->execute($params);

                $stats['validated']++;
                error_log("[AddressAutocomplete] OSM validated #{$row['id']} '{$row['street']}' → confidence={$newConf}, osm_id={$result['osm_id']}");
            } else {
                // Not found in OSM — mark osm_id = -1 so we don't retry
                $this->db->prepare(
                    "UPDATE address_streets SET osm_id = -1, updated_at = NOW() WHERE id = :id"
                )->execute([':id' => $row['id']]);
                $stats['not_found']++;
            }
        }

        return $stats;
    }

    /**
     * Check if a street exists in OSM via Nominatim structured search.
     * Returns match data on success, false if not found, null on API error.
     *
     * @return array{osm_id: int, lat: float, lon: float, neighborhood: string}|false|null
     */
    private function checkStreetInOSM(string $city, string $street): array|false|null
    {
        $streetClean = self::stripStreetPrefix(self::normalize($street));
        if (mb_strlen($streetClean) < 3) return false;

        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'street' => $street,
            'city' => $city,
            'state' => 'Rio Grande do Sul',
            'country' => 'Brazil',
            'format' => 'json',
            'limit' => 3,
            'addressdetails' => 1,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT      => 'MultiMenu-Delivery/1.0 (contact@multimenu.com.br)',
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) return null;

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data)) return false;

        $cityNorm = self::normalize($city);
        $streetNorm = self::normalize($street);
        $streetStripped = self::stripStreetPrefix($streetNorm);

        foreach ($data as $item) {
            $road = $item['address']['road'] ?? ($item['name'] ?? '');
            if ($road === '') continue;

            $roadNorm = self::normalize($road);
            $roadStripped = self::stripStreetPrefix($roadNorm);

            // Verify street name matches (fuzzy: ≤2 edit distance on stripped name)
            if (levenshtein($streetStripped, $roadStripped) > 2 &&
                strpos($roadStripped, $streetStripped) === false &&
                strpos($streetStripped, $roadStripped) === false) {
                continue;
            }

            // Verify city matches
            $itemCity = $item['address']['city'] ?? ($item['address']['town'] ?? ($item['address']['municipality'] ?? ''));
            if ($itemCity !== '' && self::normalize($itemCity) !== $cityNorm) continue;

            return [
                'osm_id' => (int)($item['osm_id'] ?? 0),
                'lat' => isset($item['lat']) ? (float)$item['lat'] : null,
                'lon' => isset($item['lon']) ? (float)$item['lon'] : null,
                'neighborhood' => $item['address']['suburb'] ?? ($item['address']['neighbourhood'] ?? ''),
            ];
        }

        return false;
    }

    /**
     * Re-validate streets previously not found (osm_id = -1) that are older than 30 days.
     * OSM data gets updated, so streets may appear later.
     */
    public function revalidateNotFound(int $limit = 20): array
    {
        $this->db->prepare(
            "UPDATE address_streets SET osm_id = NULL
             WHERE company_id = :cid
               AND osm_id = -1
               AND updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
             LIMIT :lim"
        )->execute([':cid' => $this->companyId, ':lim' => $limit]);

        return $this->validateAgainstOSM($limit);
    }
    
    // ========================================================================
    // Ranking (confidence-aware)
    // ========================================================================
    
    /**
     * Rank results using multi-factor scoring:
     *   1. Text relevance (prefix match, contains match)
     *   2. Neighborhood match (strong boost)
     *   3. Confidence score (trusted data ranks higher)
     *   4. Popularity (usage frequency, logarithmic cap)
     *   5. Source priority
     *
     * Non-validated data NEVER outranks trusted data.
     */
    private function rankResults(array $results, string $queryNorm, string $nbNorm): array
    {
        foreach ($results as &$r) {
            $streetNorm = self::normalize($r['street']);
            $confidence = $r['confidence'] ?? 0.50;
            $popularity = $r['popularity'] ?? 0;
            $src = $r['source'] ?? 'osm';
            
            // Base score from popularity (capped to prevent runaway)
            $score = min($popularity, 100);
            
            // Source boost
            $score += self::SOURCE_BOOST[$src] ?? 0;
            
            // Confidence factor (0.0 – 1.0 → 0 – 50 points)
            $score += (int)($confidence * 50);
            
            // Text relevance
            if (strpos($streetNorm, $queryNorm) === 0) {
                $score += 100; // Exact prefix match
            } elseif (strpos($streetNorm, $queryNorm) !== false) {
                $score += 50;  // Contains
            }
            
            // Stripped prefix comparison
            $strippedStreet = preg_replace('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s+/u', '', $streetNorm);
            $strippedQuery = preg_replace('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s+/u', '', $queryNorm);
            if ($strippedQuery !== '' && strpos($strippedStreet, $strippedQuery) === 0) {
                $score += 80;
            }
            
            // Neighborhood match (strong boost)
            if ($nbNorm !== '' && self::normalize($r['neighborhood'] ?? '') === $nbNorm) {
                $score += 200;
            }
            
            $r['_score'] = $score;
        }
        unset($r);
        
        usort($results, function ($a, $b) {
            if ($b['_score'] !== $a['_score']) {
                return $b['_score'] - $a['_score'];
            }
            return strcmp($a['street'], $b['street']);
        });
        
        // Clean internal fields from output
        foreach ($results as &$r) {
            unset($r['_score'], $r['source'], $r['confidence']);
        }
        
        return $results;
    }
    
    /**
     * Format DB rows into result array.
     */
    private function formatRows(array $rows): array
    {
        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'id' => (int)$row['id'],
                'street' => $row['street'],
                'neighborhood' => $row['neighborhood'] ?? '',
                'lat' => $row['lat'] !== null ? (float)$row['lat'] : null,
                'lon' => $row['lon'] !== null ? (float)$row['lon'] : null,
                'popularity' => (int)($row['popularity_score'] ?? 0),
                'confidence' => (float)($row['confidence_score'] ?? 0.50),
                'source' => $row['source'] ?? 'osm',
            ];
        }
        return $results;
    }
    
    // ========================================================================
    // Deduplication
    // ========================================================================
    
    private function mergeResults(array $primary, array $secondary): array
    {
        return $this->deduplicateResults(array_merge($primary, $secondary));
    }
    
    /**
     * Deduplicate by canonical street name.
     * Expands abbreviations so R./Rua merge, but Rua ≠ Avenida stays separate.
     */
    private function deduplicateResults(array $results): array
    {
        $groups = [];
        $bareNames = [];
        
        foreach ($results as $r) {
            $canonical = self::normalize(self::canonicalStreetName($r['street']));
            $hasPrefix = preg_match('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s/u', $canonical);
            
            if ($hasPrefix) {
                $groups[$canonical][] = $r;
            } else {
                $bareNames[] = $r;
            }
        }
        
        foreach ($bareNames as $bare) {
            $bareName = self::normalize($bare['street']);
            $merged = false;
            foreach ($groups as $key => &$entries) {
                if (self::stripStreetPrefix($key) === $bareName) {
                    $entries[] = $bare;
                    $merged = true;
                    break;
                }
            }
            unset($entries);
            if (!$merged) {
                $groups['_bare:' . $bareName][] = $bare;
            }
        }
        
        $deduped = [];
        foreach ($groups as $entries) {
            if (count($entries) === 1) {
                $deduped[] = $entries[0];
                continue;
            }
            
            usort($entries, function ($a, $b) {
                // Prefer higher confidence
                $aConf = $a['confidence'] ?? 0.50;
                $bConf = $b['confidence'] ?? 0.50;
                if (abs($aConf - $bConf) > 0.05) return $bConf <=> $aConf;
                
                // Prefer entries with proper prefix
                $aHasPrefix = preg_match('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s/ui', $a['street']);
                $bHasPrefix = preg_match('/^(rua|avenida|travessa|beco|alameda|praca|largo|estrada|rodovia)\s/ui', $b['street']);
                if ($aHasPrefix !== $bHasPrefix) return $bHasPrefix - $aHasPrefix;
                
                // Prefer higher source boost
                $aBoost = self::SOURCE_BOOST[$a['source'] ?? 'osm'] ?? 0;
                $bBoost = self::SOURCE_BOOST[$b['source'] ?? 'osm'] ?? 0;
                if ($aBoost !== $bBoost) return $bBoost - $aBoost;
                
                // Prefer higher popularity
                $aPop = $a['popularity'] ?? 0;
                $bPop = $b['popularity'] ?? 0;
                if ($aPop !== $bPop) return $bPop - $aPop;
                
                return (!empty($b['neighborhood']) ? 1 : 0) - (!empty($a['neighborhood']) ? 1 : 0);
            });
            
            $best = $entries[0];
            if (empty($best['neighborhood'])) {
                foreach ($entries as $e) {
                    if (!empty($e['neighborhood'])) {
                        $best['neighborhood'] = $e['neighborhood'];
                        break;
                    }
                }
            }
            $deduped[] = $best;
        }
        
        return $deduped;
    }
    
    // ========================================================================
    // Redis Cache Layer
    // ========================================================================
    
    private function buildCacheKey(string $cityNorm, string $nbNorm, string $queryNorm): string
    {
        return self::REDIS_PREFIX . $this->companyId . ':' . $cityNorm . ':' . $nbNorm . ':' . $queryNorm;
    }
    
    private function getFromRedis(string $key): ?array
    {
        try {
            SmartCache::init();
            $redis = self::getRedisInstance();
            if ($redis === null) return null;
            
            $data = $redis->get($key);
            if ($data === false) return null;
            
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function setInRedis(string $key, array $data): void
    {
        try {
            $redis = self::getRedisInstance();
            if ($redis === null) return;
            
            $redis->setex($key, self::REDIS_TTL, json_encode($data, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            // Redis failure is non-critical
        }
    }
    
    private function invalidateCityCache(int $streetId): void
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT city_normalized FROM address_streets WHERE id = :id"
            );
            $stmt->execute([':id' => $streetId]);
            $cityNorm = $stmt->fetchColumn();
            if ($cityNorm === false) return;
            
            $this->invalidateCityCacheByName($cityNorm);
        } catch (\Exception $e) {
            // Non-critical
        }
    }
    
    private function invalidateCityCacheByName(string $cityNorm): void
    {
        try {
            $redis = self::getRedisInstance();
            if ($redis === null) return;
            
            $pattern = self::REDIS_PREFIX . $this->companyId . ':' . $cityNorm . ':*';
            $cursor = null;
            do {
                $keys = $redis->scan($cursor, $pattern, 100);
                if ($keys !== false && !empty($keys)) {
                    $redis->del(...$keys);
                }
            } while ($cursor > 0);
        } catch (\Exception $e) {
            // Non-critical
        }
    }
    
    private static function getRedisInstance(): ?\Redis
    {
        static $redis = null;
        if ($redis !== null) return $redis;
        if (!extension_loaded('redis')) return null;
        
        try {
            $redis = new \Redis();
            $host = getenv('REDIS_HOST') ?: ($_ENV['REDIS_HOST'] ?? 'redis_redis');
            $port = getenv('REDIS_PORT') ?: ($_ENV['REDIS_PORT'] ?? 6379);
            $password = getenv('REDIS_PASSWORD') ?: ($_ENV['REDIS_PASSWORD'] ?? '');
            
            $redis->connect($host, (int)$port, 2.0);
            if (!empty($password)) $redis->auth($password);
            $redis->select(3);
            return $redis;
        } catch (\Exception $e) {
            error_log('[AddressAutocomplete] Redis connection failed: ' . $e->getMessage());
            $redis = null;
            return null;
        }
    }
    
    // ========================================================================
    // String Utilities
    // ========================================================================
    
    public static function normalize(string $str): string
    {
        $str = mb_strtolower(trim($str), 'UTF-8');
        $str = preg_replace('/[áàãâä]/u', 'a', $str);
        $str = preg_replace('/[éèêë]/u', 'e', $str);
        $str = preg_replace('/[íìîï]/u', 'i', $str);
        $str = preg_replace('/[óòõôö]/u', 'o', $str);
        $str = preg_replace('/[úùûü]/u', 'u', $str);
        $str = preg_replace('/[ç]/u', 'c', $str);
        $str = preg_replace('/[ñ]/u', 'n', $str);
        return $str;
    }
    
    public static function canonicalStreetName(string $street): string
    {
        $street = trim($street);
        if ($street === '') return '';
        
        $expansions = [
            '/^r\.\s+/ui'     => 'Rua ',
            '/^av\.\s+/ui'    => 'Avenida ',
            '/^av\s+/ui'      => 'Avenida ',
            '/^trav\.\s+/ui'  => 'Travessa ',
            '/^trav\s+/ui'    => 'Travessa ',
            '/^rod\.\s+/ui'   => 'Rodovia ',
            '/^estr\.\s+/ui'  => 'Estrada ',
            '/^al\.\s+/ui'    => 'Alameda ',
            '/^pca\.\s+/ui'   => 'Praça ',
            '/^pç\.\s+/ui'    => 'Praça ',
        ];
        
        foreach ($expansions as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $street);
            if ($result !== $street) {
                $street = $result;
                break;
            }
        }
        
        $street = preg_replace('/^Rus\s+/u', 'Rua ', $street);
        $street = preg_replace('/^rus\s+/ui', 'Rua ', $street);
        
        $street = mb_convert_case($street, MB_CASE_TITLE, 'UTF-8');
        
        $street = preg_replace_callback('/\b(Da|Das|De|Do|Dos|E|Em|No|Na|Nos|Nas)\b/u', function($m) {
            return mb_strtolower($m[1], 'UTF-8');
        }, $street);
        
        $street = mb_strtoupper(mb_substr($street, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($street, 1, null, 'UTF-8');
        
        return $street;
    }
    
    public static function stripStreetPrefix(string $normalized): string
    {
        return preg_replace(
            '/^(rua|r\.|avenida|av\.?|travessa|trav\.?|beco|alameda|al\.|praca|pca\.|largo|estrada|estr\.|rodovia|rod\.)\s+/ui',
            '',
            $normalized
        );
    }
    
    private function escapeFulltext(string $str): string
    {
        return preg_replace('/[+\-><()~*"@]+/', ' ', $str);
    }
    
    private function escapeLike(string $str): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $str);
    }
}
