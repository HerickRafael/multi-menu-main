<?php

declare(strict_types=1);

class CustomerListService
{
    /**
     * Retorna lista paginada de clientes e estatisticas.
     *
     * @return array{customers: array, stats: array, pagination: array}
     */
    public static function listWithStats(PDO $db, int $companyId, string $search, int $page, int $perPage): array
    {
        $search = trim($search);
        $offset = ($page - 1) * $perPage;

        $whereClause = 'WHERE c.company_id = ?';
        $queryParams = [$companyId];

        if ($search !== '') {
            $whereClause .= ' AND (c.name LIKE ? OR c.whatsapp LIKE ? OR c.whatsapp_e164 LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $queryParams[] = $searchTerm;
            $queryParams[] = $searchTerm;
            $queryParams[] = $searchTerm;
        }

        $countSql = "SELECT COUNT(*) FROM customers c {$whereClause}";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($queryParams);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalItems / $perPage));

        $sql = "
            SELECT 
                c.*,
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(CASE WHEN o.status NOT IN ('canceled') THEN o.total ELSE 0 END), 0) as total_spent,
                MAX(o.created_at) as last_order_at
            FROM customers c
            LEFT JOIN orders o ON (
                o.customer_phone = c.whatsapp 
                OR o.customer_phone = c.whatsapp_e164 
                OR o.customer_phone = REGEXP_REPLACE(c.whatsapp, '[^0-9]', '')
                OR CONCAT('55', o.customer_phone) = c.whatsapp_e164
                OR o.customer_phone = SUBSTRING(c.whatsapp_e164, 3)
            ) AND o.company_id = c.company_id
            {$whereClause}
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $queryParams[] = $perPage;
        $queryParams[] = $offset;
        $stmt = $db->prepare($sql);
        $stmt->execute($queryParams);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $statsStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers_30d,
                COUNT(CASE WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_7d
            FROM customers 
            WHERE company_id = ?
        ");
        $statsStmt->execute([$companyId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        return [
            'customers' => $customers,
            'stats' => $stats ?: [],
            'pagination' => [
                'page' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
            ],
        ];
    }
}
