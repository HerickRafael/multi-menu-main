<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class SystemLog
{
    public static function create(
        string $level,
        string $module,
        string $message,
        ?array $context = null,
        ?int $companyId = null,
        ?int $orderId = null,
        string $source = 'app',
        ?string $loggedAt = null
    ): bool {
        $sql = 'INSERT INTO system_logs
                (level, module, message, context_json, company_id, order_id, source, logged_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $st = db()->prepare($sql);
        return $st->execute([
            $level,
            $module,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            $companyId,
            $orderId,
            $source,
            $loggedAt ?? date('Y-m-d H:i:s')
        ]);
    }

    public static function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM system_logs WHERE 1=1';
        $bind = [];

        if (!empty($filters['level'])) {
            $sql .= ' AND level = ?';
            $bind[] = $filters['level'];
        }
        if (!empty($filters['module'])) {
            $sql .= ' AND module = ?';
            $bind[] = $filters['module'];
        }
        if (!empty($filters['company_id'])) {
            $sql .= ' AND company_id = ?';
            $bind[] = (int)$filters['company_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND message LIKE ?';
            $bind[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY logged_at DESC, id DESC LIMIT ? OFFSET ?';
        $bind[] = $limit;
        $bind[] = $offset;

        $st = db()->prepare($sql);
        $st->execute($bind);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function count(array $filters): int
    {
        $sql = 'SELECT COUNT(*) FROM system_logs WHERE 1=1';
        $bind = [];

        if (!empty($filters['level'])) {
            $sql .= ' AND level = ?';
            $bind[] = $filters['level'];
        }
        if (!empty($filters['module'])) {
            $sql .= ' AND module = ?';
            $bind[] = $filters['module'];
        }
        if (!empty($filters['company_id'])) {
            $sql .= ' AND company_id = ?';
            $bind[] = (int)$filters['company_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND message LIKE ?';
            $bind[] = '%' . $filters['search'] . '%';
        }

        $st = db()->prepare($sql);
        $st->execute($bind);
        return (int)$st->fetchColumn();
    }
}
