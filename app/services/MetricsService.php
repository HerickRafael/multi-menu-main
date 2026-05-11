<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/CompanyMetrics.php';
require_once __DIR__ . '/../models/CompanyStatus.php';
require_once __DIR__ . '/../models/Company.php';

/**
 * MetricsService
 * 
 * Orquestra coleta de métricas globais e por loja
 * Centraliza lógica de cálculo e cache
 */
class MetricsService
{
    /**
     * Retorna métricas globais do sistema
     */
    public static function getGlobalMetrics(): array
    {
        return CompanyMetrics::getGlobalMetrics();
    }

    /**
     * Retorna métricas de uma loja específica
     */
    public static function getCompanyMetrics(int $company_id): array
    {
        $metrics = CompanyMetrics::getMetrics($company_id);
        $status = CompanyStatus::getCurrentStatus($company_id);
        $company = Company::find($company_id);

        return [
            'company' => $company,
            'status' => $status['status'],
            'metrics' => $metrics,
            'is_online' => $status['status'] === 'active',
            'summary' => [
                'total_today' => $metrics['total_orders_today'] ?? 0,
                'revenue_today' => (float)($metrics['total_revenue_today'] ?? 0),
                'active_orders' => $metrics['active_orders_now'] ?? 0,
                'pending_orders' => $metrics['pending_orders'] ?? 0,
                'avg_prep_time' => $metrics['preparation_time_avg'] ?? 0
            ]
        ];
    }

    /**
     * Retorna todas as lojas com suas métricas (para dashboard)
     */
    public static function getAllCompaniesMetrics(
        ?string $status_filter = null,
        ?string $search = null,
        int $page = 1,
        int $per_page = 20
    ): array {
        $offset = ($page - 1) * $per_page;

        // Build query base
        $query = 'SELECT c.*, s.status FROM companies c 
                  LEFT JOIN (
                    SELECT company_id, status FROM company_operational_status 
                    WHERE id IN (
                        SELECT MAX(id) FROM company_operational_status 
                        GROUP BY company_id
                    )
                  ) s ON c.id = s.company_id 
                  WHERE 1=1';

        $params = [];

        // Filtro de status
        if ($status_filter && in_array($status_filter, ['active', 'suspended', 'maintenance', 'blocked'])) {
            $query .= ' AND COALESCE(s.status, "active") = ?';
            $params[] = $status_filter;
        }

        // Filtro de busca
        if ($search) {
            $query .= ' AND (c.name LIKE ? OR c.slug LIKE ?)';
            $search_term = "%{$search}%";
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Ordenação
        $query .= ' ORDER BY c.name ASC LIMIT ? OFFSET ?';
        $params[] = $per_page;
        $params[] = $offset;

        // Execute query
        $stmt = db()->prepare($query);
        $stmt->execute($params);
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer com métricas e recursos
        $enriched = [];
        foreach ($companies as $company) {
            $metrics = CompanyMetrics::getMetrics($company['id']);
            $company['metrics'] = $metrics;
            $company['status'] = $company['status'] ?? 'active';
            $company['is_online'] = $company['status'] === 'active';
            $enriched[] = $company;
        }

        // Total de lojas
        $count_query = 'SELECT COUNT(*) FROM companies c 
                        LEFT JOIN (
                          SELECT company_id, status FROM company_operational_status 
                          WHERE id IN (
                              SELECT MAX(id) FROM company_operational_status 
                              GROUP BY company_id
                          )
                        ) s ON c.id = s.company_id 
                        WHERE 1=1';
        
        $count_params = [];
        if ($status_filter && in_array($status_filter, ['active', 'suspended', 'maintenance', 'blocked'])) {
            $count_query .= ' AND COALESCE(s.status, "active") = ?';
            $count_params[] = $status_filter;
        }
        if ($search) {
            $count_query .= ' AND (c.name LIKE ? OR c.slug LIKE ?)';
            $count_params[] = "%{$search}%";
            $count_params[] = "%{$search}%";
        }

        $count_stmt = db()->prepare($count_query);
        $count_stmt->execute($count_params);
        $total = (int)$count_stmt->fetchColumn();

        return [
            'companies' => $enriched,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'pages' => ceil($total / $per_page)
            ]
        ];
    }

    /**
     * Calcula resumo de métricas global (para dashboard cards)
     */
    public static function getDashboardSummary(): array
    {
        $global = self::getGlobalMetrics();

        // Métricas de status
        $stmt = db()->query('
            SELECT 
                COUNT(CASE WHEN c.active = 1 THEN 1 END) as total_stores,
                COUNT(CASE WHEN COALESCE(s.status, "active") = "active" THEN 1 END) as online_stores,
                COUNT(CASE WHEN COALESCE(s.status, "active") = "suspended" THEN 1 END) as suspended_stores,
                COUNT(CASE WHEN COALESCE(s.status, "active") = "maintenance" THEN 1 END) as maintenance_stores,
                COUNT(CASE WHEN COALESCE(s.status, "active") = "blocked" THEN 1 END) as blocked_stores
            FROM companies c
            LEFT JOIN (
                SELECT company_id, status FROM company_operational_status 
                WHERE id IN (
                    SELECT MAX(id) FROM company_operational_status 
                    GROUP BY company_id
                )
            ) s ON c.id = s.company_id
        ');
        
        $status_summary = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_stores' => (int)$status_summary['total_stores'],
            'online_stores' => (int)$status_summary['online_stores'],
            'suspended_stores' => (int)$status_summary['suspended_stores'],
            'maintenance_stores' => (int)$status_summary['maintenance_stores'],
            'blocked_stores' => (int)$status_summary['blocked_stores'],
            'global_active_orders' => $global['global_active_orders'],
            'global_revenue_today' => $global['global_revenue_today'],
            'offline_stores' => $global['offline_stores']
        ];
    }

    /**
     * Força recalcular métricas de uma loja
     */
    public static function refreshMetrics(int $company_id): void
    {
        CompanyMetrics::clearCache($company_id);
        // Trigger recalc na próxima requisição
        CompanyMetrics::getMetrics($company_id);
    }
}
