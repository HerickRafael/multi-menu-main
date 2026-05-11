<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/OrderTimeline.php';

class OrderMonitoringService
{
    public static function getGlobalSummary(?int $companyId = null): array
    {
        $where = '';
        $bind = [];
        if ($companyId !== null && $companyId > 0) {
            $where = ' WHERE o.company_id = ?';
            $bind[] = $companyId;
        }

        $sql = 'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN o.status = "pending" THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN o.status = "paid" THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN o.status = "completed" THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN o.status = "canceled" THEN 1 ELSE 0 END) AS canceled_count,
                    COALESCE(SUM(o.total), 0) AS total_value
                FROM orders o' . $where;
        $st = db()->prepare($sql);
        $st->execute($bind);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'pending' => (int)($row['pending_count'] ?? 0),
            'paid' => (int)($row['paid_count'] ?? 0),
            'completed' => (int)($row['completed_count'] ?? 0),
            'canceled' => (int)($row['canceled_count'] ?? 0),
            'total_value' => (float)($row['total_value'] ?? 0),
        ];
    }

    public static function listOrders(?int $companyId, ?string $status, int $page = 1, int $perPage = 40): array
    {
        $offset = ($page - 1) * $perPage;
        $whereParts = [];
        $bind = [];

        if ($companyId !== null && $companyId > 0) {
            $whereParts[] = 'o.company_id = ?';
            $bind[] = $companyId;
        }
        if ($status !== null && $status !== '') {
            $whereParts[] = 'o.status = ?';
            $bind[] = $status;
        }
        $where = $whereParts ? (' WHERE ' . implode(' AND ', $whereParts)) : '';

        $sql = 'SELECT o.id, o.company_id, o.customer_name, o.customer_phone, o.total, o.status, o.created_at,
                       c.name AS company_name, c.slug AS company_slug
                FROM orders o
                INNER JOIN companies c ON c.id = o.company_id' . $where . '
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?';

        $bindRows = $bind;
        $bindRows[] = $perPage;
        $bindRows[] = $offset;

        $stRows = db()->prepare($sql);
        $stRows->execute($bindRows);
        $rows = $stRows->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sqlCount = 'SELECT COUNT(*) FROM orders o' . $where;
        $stCount = db()->prepare($sqlCount);
        $stCount->execute($bind);
        $total = (int)$stCount->fetchColumn();

        return [
            'rows' => $rows,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function getOrderTimeline(int $orderId): array
    {
        $stOrder = db()->prepare('SELECT id, company_id, status, created_at, updated_at FROM orders WHERE id = ? LIMIT 1');
        $stOrder->execute([$orderId]);
        $order = $stOrder->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return ['order' => null, 'events' => []];
        }

        $companyId = (int)$order['company_id'];
        $events = OrderTimeline::listByOrder($orderId, $companyId);

        // Fallback útil para pedidos antigos sem trilha registrada.
        if (empty($events)) {
            $events[] = [
                'status_from' => null,
                'status_to' => 'pending',
                'changed_by_type' => 'system',
                'source' => 'fallback',
                'notes' => 'Evento inicial inferido a partir do pedido',
                'created_at' => $order['created_at'],
            ];
            if (($order['status'] ?? 'pending') !== 'pending') {
                $events[] = [
                    'status_from' => 'pending',
                    'status_to' => $order['status'],
                    'changed_by_type' => 'system',
                    'source' => 'fallback',
                    'notes' => 'Status atual inferido',
                    'created_at' => $order['updated_at'] ?? $order['created_at'],
                ];
            }
        }

        return ['order' => $order, 'events' => $events];
    }
}
