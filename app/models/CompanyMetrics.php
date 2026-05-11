<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/SmartCache.php';

/**
 * CompanyMetrics Model
 * 
 * Cache de métricas operacionais (com TTL de 5 minutos)
 * Métricas: total_orders_today, total_revenue_today, active_orders_now, etc
 */
class CompanyMetrics
{
    private const CACHE_TTL = 300; // 5 minutos

    /**
     * Obtém todas as métricas de uma loja
     */
    public static function getMetrics(int $company_id): array
    {
        $key = "company:metrics:{$company_id}";
        
        return SmartCache::remember($key, function() use ($company_id) {
            $stmt = db()->prepare('
                SELECT * FROM company_metrics_cache 
                WHERE company_id = ? AND expires_at > NOW()
                LIMIT 1
            ');
            $stmt->execute([$company_id]);
            $cached = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cached) {
                // Parse JSON metadata
                if ($cached['metadata']) {
                    $cached['metadata'] = json_decode($cached['metadata'], true);
                }
                return $cached;
            }
            
            // Se não há cache válido, calcular e armazenar
            return self::calculateAndStore($company_id);
        }, self::CACHE_TTL);
    }

    /**
     * Calcula e armazena métricas de uma loja
     */
    private static function calculateAndStore(int $company_id): array
    {
        $metrics = self::calculateMetrics($company_id);
        
        // Armazenar no banco
        $stmt = db()->prepare('
            INSERT INTO company_metrics_cache 
            (company_id, total_orders_today, total_revenue_today, active_orders_now, 
             pending_orders, preparation_time_avg, cached_at, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 5 MINUTE))
            ON DUPLICATE KEY UPDATE
                total_orders_today = VALUES(total_orders_today),
                total_revenue_today = VALUES(total_revenue_today),
                active_orders_now = VALUES(active_orders_now),
                pending_orders = VALUES(pending_orders),
                preparation_time_avg = VALUES(preparation_time_avg),
                cached_at = NOW(),
                expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE)
        ');
        
        $stmt->execute([
            $company_id,
            (int)$metrics['total_orders_today'],
            (float)$metrics['total_revenue_today'],
            (int)$metrics['active_orders_now'],
            (int)$metrics['pending_orders'],
            (int)$metrics['preparation_time_avg']
        ]);
        
        return $metrics;
    }

    /**
     * Calcula métricas de uma loja (queries em tempo real)
     */
    private static function calculateMetrics(int $company_id): array
    {
        // Total de pedidos hoje
        $stmt = db()->prepare('
            SELECT COUNT(*) as total FROM orders 
            WHERE company_id = ? AND DATE(created_at) = DATE(NOW())
        ');
        $stmt->execute([$company_id]);
        $total_orders_today = (int)$stmt->fetchColumn();

        // Receita total hoje
        $stmt = db()->prepare('
            SELECT COALESCE(SUM(total), 0) as revenue FROM orders 
            WHERE company_id = ? AND DATE(created_at) = DATE(NOW()) 
            AND status IN ("paid", "confirmed", "preparing", "ready", "out_for_delivery", "delivered")
        ');
        $stmt->execute([$company_id]);
        $total_revenue_today = (float)$stmt->fetchColumn();

        // Pedidos ativos agora (não finalizados nem cancelados)
        $stmt = db()->prepare('
            SELECT COUNT(*) as active FROM orders 
            WHERE company_id = ? 
            AND status IN ("pending", "paid", "confirmed", "preparing", "ready", "out_for_delivery")
        ');
        $stmt->execute([$company_id]);
        $active_orders_now = (int)$stmt->fetchColumn();

        // Pedidos pendentes
        $stmt = db()->prepare('
            SELECT COUNT(*) as pending FROM orders 
            WHERE company_id = ? AND status = "pending"
        ');
        $stmt->execute([$company_id]);
        $pending_orders = (int)$stmt->fetchColumn();

        // Tempo médio de preparação (em minutos)
        $stmt = db()->prepare('
            SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)), 0) as avg_prep
            FROM orders 
            WHERE company_id = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND status IN ("delivered", "cancelled")
        ');
        $stmt->execute([$company_id]);
        $preparation_time_avg = (int)$stmt->fetchColumn();

        return [
            'total_orders_today' => $total_orders_today,
            'total_revenue_today' => $total_revenue_today,
            'active_orders_now' => $active_orders_now,
            'pending_orders' => $pending_orders,
            'preparation_time_avg' => $preparation_time_avg
        ];
    }

    /**
     * Limpa cache de métricas de uma loja
     */
    public static function clearCache(int $company_id): void
    {
        SmartCache::forget("company:metrics:{$company_id}");
    }

    /**
     * Retorna métricas globais (todas as lojas)
     */
    public static function getGlobalMetrics(): array
    {
        $key = "system:metrics:global";
        
        return SmartCache::remember($key, function() {
            // Lojas ativas
            $stmt = db()->query('SELECT COUNT(*) FROM companies WHERE active = 1');
            $total_active_stores = (int)$stmt->fetchColumn();

            // Pedidos ativos globais
            $stmt = db()->query('
                SELECT COUNT(*) FROM orders 
                WHERE status IN ("pending", "paid", "confirmed", "preparing", "ready", "out_for_delivery")
            ');
            $global_active_orders = (int)$stmt->fetchColumn();

            // Receita global hoje
            $stmt = db()->query('
                SELECT COALESCE(SUM(total), 0) FROM orders 
                WHERE DATE(created_at) = DATE(NOW())
                AND status IN ("paid", "confirmed", "preparing", "ready", "out_for_delivery", "delivered")
            ');
            $global_revenue_today = (float)$stmt->fetchColumn();

            // Stores offline (sem pedidos nas últimas 2 horas)
            $stmt = db()->query('
                SELECT COUNT(DISTINCT c.id) FROM companies c
                WHERE c.active = 1
                AND c.id NOT IN (
                    SELECT DISTINCT company_id FROM orders 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                )
            ');
            $offline_stores = (int)$stmt->fetchColumn();

            return [
                'total_active_stores' => $total_active_stores,
                'offline_stores' => $offline_stores,
                'global_active_orders' => $global_active_orders,
                'global_revenue_today' => $global_revenue_today
            ];
        }, self::CACHE_TTL);
    }
}
