<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class OrderTimeline
{
    public static function addEvent(
        int $orderId,
        int $companyId,
        ?string $statusFrom,
        string $statusTo,
        string $changedByType = 'system',
        ?int $changedById = null,
        string $source = 'system',
        ?string $notes = null
    ): bool {
        $sql = 'INSERT INTO order_timeline
                (order_id, company_id, status_from, status_to, changed_by_type, changed_by_id, source, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $st = db()->prepare($sql);
        return $st->execute([$orderId, $companyId, $statusFrom, $statusTo, $changedByType, $changedById, $source, $notes]);
    }

    public static function listByOrder(int $orderId, int $companyId): array
    {
        $sql = 'SELECT * FROM order_timeline WHERE order_id = ? AND company_id = ? ORDER BY created_at ASC, id ASC';
        $st = db()->prepare($sql);
        $st->execute([$orderId, $companyId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function countByOrder(int $orderId, int $companyId): int
    {
        $sql = 'SELECT COUNT(*) FROM order_timeline WHERE order_id = ? AND company_id = ?';
        $st = db()->prepare($sql);
        $st->execute([$orderId, $companyId]);
        return (int)$st->fetchColumn();
    }
}
