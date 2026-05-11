<?php

declare(strict_types=1);

/**
 * ============================================================================
 * RecommendationEngine - Sistema de Recomendação Inteligente
 * ============================================================================
 * 
 * Combina 3 tipos de inteligência para gerar recomendações personalizadas:
 * 
 * 1. Preferências Pessoais - Aprende o gosto individual do cliente
 * 2. Filtro Colaborativo - Descobre padrões: "quem comprou X também comprou Y"
 * 3. Popularidade - Fallback inteligente para clientes novos
 * 
 * Pesos dos eventos:
 * - View (visualizar): +1.0
 * - Add to Cart: +3.0
 * - Purchase (comprar): +5.0
 */

class RecommendationEngine
{
    private PDO $db;
    
    // Pesos configuráveis dos eventos
    const WEIGHT_VIEW = 1.0;
    const WEIGHT_CART = 3.0;
    const WEIGHT_PURCHASE = 5.0;
    
    // Pesos da fórmula final
    const PERSONAL_WEIGHT = 0.35;      // 35% preferências pessoais
    const COLLABORATIVE_WEIGHT = 0.35;  // 35% filtro colaborativo
    const POPULARITY_WEIGHT = 0.15;     // 15% popularidade
    const TIME_WEIGHT = 0.15;           // 15% fator horário
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    /**
     * Método principal: Gera recomendações para um produto específico
     * 
     * @param int $companyId ID da empresa
     * @param int $productId Produto que o cliente está visualizando
     * @param int|null $customerId ID do cliente (null = anônimo)
     * @param string|null $sessionId Session ID para clientes anônimos
     * @param int $limit Quantidade de recomendações
     * @return array Lista de produtos recomendados com scores
     */
    public function getRecommendations(
        int $companyId,
        int $productId,
        ?int $customerId = null,
        ?string $sessionId = null,
        int $limit = 4
    ): array {
        $scores = [];
        
        // 1. Obter scores de preferências pessoais
        if ($customerId !== null) {
            $personalScores = $this->getPersonalScores($companyId, $customerId, $productId);
            foreach ($personalScores as $pid => $score) {
                $scores[$pid] = ($scores[$pid] ?? 0) + ($score * self::PERSONAL_WEIGHT);
            }
        }
        
        // 2. Obter scores de filtro colaborativo (produtos comprados juntos)
        $collaborativeScores = $this->getCollaborativeScores($companyId, $productId);
        foreach ($collaborativeScores as $pid => $score) {
            $scores[$pid] = ($scores[$pid] ?? 0) + ($score * self::COLLABORATIVE_WEIGHT);
        }
        
        // 3. Obter scores de popularidade (fallback)
        $popularityScores = $this->getPopularityScores($companyId, $productId);
        foreach ($popularityScores as $pid => $score) {
            $scores[$pid] = ($scores[$pid] ?? 0) + ($score * self::POPULARITY_WEIGHT);
        }
        
        // 4. Obter scores por horário (contextual)
        $timeScores = $this->getTimeBasedScores($companyId, $productId);
        foreach ($timeScores as $pid => $score) {
            $scores[$pid] = ($scores[$pid] ?? 0) + ($score * self::TIME_WEIGHT);
        }
        
        // 5. Aplicar ajustes de negócio
        $scores = $this->applyBusinessRules($companyId, $scores);
        
        // 6. Ordenar por score e limitar
        arsort($scores);
        $topProductIds = array_slice(array_keys($scores), 0, $limit, true);
        
        // 7. Buscar detalhes dos produtos
        if (empty($topProductIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($topProductIds), '?'));
        $stmt = $this->db->prepare("
            SELECT p.id, p.name, p.image, p.price, p.promo_price,
                   COUNT(DISTINCT pcg.id) as has_ingredients
            FROM products p
            LEFT JOIN product_custom_groups pcg ON p.id = pcg.product_id
            WHERE p.company_id = ?
              AND p.id IN ($placeholders)
              AND p.active = 1
              AND p.type = 'simple'
            GROUP BY p.id, p.name, p.image, p.price, p.promo_price
        ");
        
        $params = array_merge([$companyId], $topProductIds);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ordenar produtos pela ordem dos scores
        $productsOrdered = [];
        foreach ($topProductIds as $pid) {
            foreach ($products as $product) {
                if ((int)$product['id'] === $pid) {
                    $product['recommendation_score'] = round($scores[$pid], 2);
                    $productsOrdered[] = $product;
                    break;
                }
            }
        }
        
        return $productsOrdered;
    }
    
    /**
     * 1. PREFERÊNCIAS PESSOAIS
     * Retorna produtos que o cliente demonstrou interesse
     */
    private function getPersonalScores(int $companyId, int $customerId, int $excludeProductId): array
    {
        $stmt = $this->db->prepare("
            SELECT product_id, preference_score
            FROM customer_product_preferences
            WHERE company_id = ?
              AND customer_id = ?
              AND product_id != ?
            ORDER BY preference_score DESC
            LIMIT 20
        ");
        
        $stmt->execute([$companyId, $customerId, $excludeProductId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $scores = [];
        $maxScore = 0;
        
        foreach ($results as $row) {
            $score = (float)$row['preference_score'];
            if ($score > $maxScore) {
                $maxScore = $score;
            }
            $scores[(int)$row['product_id']] = $score;
        }
        
        // Normalizar entre 0 e 1
        if ($maxScore > 0) {
            foreach ($scores as $pid => $score) {
                $scores[$pid] = $score / $maxScore;
            }
        }
        
        return $scores;
    }
    
    /**
     * 2. FILTRO COLABORATIVO (Item-Based)
     * Descobre: "quem comprou X também comprou Y"
     */
    private function getCollaborativeScores(int $companyId, int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT product_b_id, affinity_score
            FROM product_affinity_scores
            WHERE company_id = ?
              AND product_a_id = ?
            ORDER BY affinity_score DESC
            LIMIT 20
        ");
        
        $stmt->execute([$companyId, $productId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $scores = [];
        $maxScore = 0;
        
        foreach ($results as $row) {
            $score = (float)$row['affinity_score'];
            if ($score > $maxScore) {
                $maxScore = $score;
            }
            $scores[(int)$row['product_b_id']] = $score;
        }
        
        // Normalizar entre 0 e 1
        if ($maxScore > 0) {
            foreach ($scores as $pid => $score) {
                $scores[$pid] = $score / $maxScore;
            }
        }
        
        return $scores;
    }
    
    /**
     * 3. POPULARIDADE GLOBAL
     * Fallback para clientes novos ou sem histórico
     */
    private function getPopularityScores(int $companyId, int $excludeProductId): array
    {
        $stmt = $this->db->prepare("
            SELECT product_id, popularity_score
            FROM product_popularity_scores
            WHERE company_id = ?
              AND product_id != ?
            ORDER BY popularity_score DESC
            LIMIT 20
        ");
        
        $stmt->execute([$companyId, $excludeProductId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $scores = [];
        $maxScore = 0;
        
        foreach ($results as $row) {
            $score = (float)$row['popularity_score'];
            if ($score > $maxScore) {
                $maxScore = $score;
            }
            $scores[(int)$row['product_id']] = $score;
        }
        
        // Normalizar entre 0 e 1
        if ($maxScore > 0) {
            foreach ($scores as $pid => $score) {
                $scores[$pid] = $score / $maxScore;
            }
        }
        
        return $scores;
    }
    
    /**
     * 4. RECOMENDAÇÕES POR HORÁRIO
     * Descobre quais produtos vendem mais em cada faixa horária
     * Faixas: manhã (6-11), almoço (11-14), tarde (14-18), noite (18-23), madrugada (23-6)
     */
    private function getTimeBasedScores(int $companyId, int $excludeProductId): array
    {
        $currentHour = (int)date('G'); // 0-23, timezone do servidor (America/Sao_Paulo)
        $period = match (true) {
            $currentHour >= 6 && $currentHour < 11  => 'morning',
            $currentHour >= 11 && $currentHour < 14 => 'lunch',
            $currentHour >= 14 && $currentHour < 18 => 'afternoon',
            $currentHour >= 18 && $currentHour < 23 => 'evening',
            default                                  => 'late',
        };
        
        $hourRanges = [
            'morning'   => [6, 11],
            'lunch'     => [11, 14],
            'afternoon' => [14, 18],
            'evening'   => [18, 23],
            'late'      => [23, 6],
        ];
        
        // Tentar usar SmartCache se disponível
        $cacheKey = "time_scores:{$companyId}:{$period}";
        if (class_exists('SmartCache')) {
            $cached = SmartCache::get($cacheKey);
            if ($cached !== null && is_array($cached)) {
                unset($cached[$excludeProductId]);
                return $cached;
            }
        }
        
        [$hourStart, $hourEnd] = $hourRanges[$period];
        
        // Query: produtos mais comprados nesta faixa horária nos últimos 60 dias
        if ($hourStart < $hourEnd) {
            // Faixa normal (ex: 6-11)
            $stmt = $this->db->prepare("
                SELECT oi.product_id, SUM(oi.quantity) AS time_score
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE o.company_id = ?
                  AND o.status = 'completed'
                  AND o.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                  AND HOUR(o.created_at) >= ? AND HOUR(o.created_at) < ?
                GROUP BY oi.product_id
                HAVING time_score >= 2
                ORDER BY time_score DESC
                LIMIT 20
            ");
            $stmt->execute([$companyId, $hourStart, $hourEnd]);
        } else {
            // Faixa cruzada meia-noite (23-6)
            $stmt = $this->db->prepare("
                SELECT oi.product_id, SUM(oi.quantity) AS time_score
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE o.company_id = ?
                  AND o.status = 'completed'
                  AND o.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                  AND (HOUR(o.created_at) >= ? OR HOUR(o.created_at) < ?)
                GROUP BY oi.product_id
                HAVING time_score >= 2
                ORDER BY time_score DESC
                LIMIT 20
            ");
            $stmt->execute([$companyId, $hourStart, $hourEnd]);
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fallback: se poucos dados (<3 produtos), retorna vazio
        if (count($results) < 3) {
            return [];
        }
        
        $scores = [];
        $maxScore = 0;
        
        foreach ($results as $row) {
            $score = (float)$row['time_score'];
            if ($score > $maxScore) {
                $maxScore = $score;
            }
            $scores[(int)$row['product_id']] = $score;
        }
        
        // Normalizar entre 0 e 1
        if ($maxScore > 0) {
            foreach ($scores as $pid => $score) {
                $scores[$pid] = $score / $maxScore;
            }
        }
        
        // Cachear por 2 horas
        if (class_exists('SmartCache')) {
            SmartCache::set($cacheKey, $scores, 7200);
        }
        
        unset($scores[$excludeProductId]);
        return $scores;
    }
    
    /**
     * 5. AJUSTES DE NEGÓCIO
     * Considera: estoque, margem, promoções, horário
     */
    private function applyBusinessRules(int $companyId, array $scores): array
    {
        if (empty($scores)) {
            return $scores;
        }
        
        $productIds = array_keys($scores);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $stmt = $this->db->prepare("
            SELECT id, promo_price, price
            FROM products
            WHERE company_id = ?
              AND id IN ($placeholders)
              AND active = 1
        ");
        
        $params = array_merge([$companyId], $productIds);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $pid = (int)$product['id'];
            
            if (!isset($scores[$pid])) {
                continue;
            }
            
            // Boost para produtos em promoção (+15%)
            $promoPrice = $product['promo_price'];
            $price = (float)$product['price'];
            
            if ($promoPrice !== null && (float)$promoPrice > 0 && (float)$promoPrice < $price) {
                $scores[$pid] *= 1.15;
            }
            
            // TODO: Adicionar outros ajustes:
            // - Boost por margem alta
            // - Penalidade por estoque baixo
        }
        
        return $scores;
    }
    
    /**
     * Registra uma interação cliente-produto
     * Chamado pelo frontend via API
     */
    public function trackInteraction(
        int $companyId,
        int $productId,
        string $eventType,
        ?int $customerId = null,
        ?string $sessionId = null
    ): bool {
        // Validar event_type
        $validEvents = ['view', 'add_to_cart', 'purchase'];
        if (!in_array($eventType, $validEvents, true)) {
            return false;
        }
        
        // Definir peso do evento
        $weight = match($eventType) {
            'view' => self::WEIGHT_VIEW,
            'add_to_cart' => self::WEIGHT_CART,
            'purchase' => self::WEIGHT_PURCHASE,
            default => 1.0
        };
        
        $stmt = $this->db->prepare("
            INSERT INTO customer_product_interactions 
            (company_id, customer_id, session_id, product_id, event_type, event_weight)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $companyId,
            $customerId,
            $sessionId,
            $productId,
            $eventType,
            $weight
        ]);
    }
    
    /**
     * Recalcula scores de afinidade entre produtos
     * Deve ser executado periodicamente (cron job)
     */
    public function recalculateAffinityScores(int $companyId): int
    {
        // Encontrar pares de produtos comprados juntos nas últimas compras
        $stmt = $this->db->prepare("
            SELECT 
                i1.product_id AS product_a,
                i2.product_id AS product_b,
                COUNT(*) AS co_occurrence
            FROM customer_product_interactions i1
            INNER JOIN customer_product_interactions i2
                ON i1.company_id = i2.company_id
                AND i1.customer_id = i2.customer_id
                AND i1.product_id < i2.product_id
                AND i1.event_type = 'purchase'
                AND i2.event_type = 'purchase'
            WHERE i1.company_id = ?
              AND i1.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY i1.product_id, i2.product_id
            HAVING co_occurrence >= 2
        ");
        
        $stmt->execute([$companyId]);
        $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        
        foreach ($pairs as $pair) {
            $productA = (int)$pair['product_a'];
            $productB = (int)$pair['product_b'];
            $coOccurrence = (int)$pair['co_occurrence'];
            
            // Calcular score de afinidade (pode ser melhorado com Jaccard ou Cosine)
            $affinityScore = log($coOccurrence + 1) * 10;
            
            // Inserir/atualizar score bidirecional
            $this->upsertAffinityScore($companyId, $productA, $productB, $affinityScore, $coOccurrence);
            $this->upsertAffinityScore($companyId, $productB, $productA, $affinityScore, $coOccurrence);
            
            $updated += 2;
        }
        
        return $updated;
    }
    
    private function upsertAffinityScore(
        int $companyId,
        int $productA,
        int $productB,
        float $score,
        int $coOccurrence
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO product_affinity_scores 
            (company_id, product_a_id, product_b_id, affinity_score, co_occurrence_count)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                affinity_score = VALUES(affinity_score),
                co_occurrence_count = VALUES(co_occurrence_count)
        ");
        
        $stmt->execute([$companyId, $productA, $productB, $score, $coOccurrence]);
    }
}
