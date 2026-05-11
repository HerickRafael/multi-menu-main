<?php

declare(strict_types=1);

class WhatsAppMonitoringService
{
    public static function getOverview(?int $companyId = null): array
    {
        $bind = [];
        $where = '';
        if ($companyId !== null && $companyId > 0) {
            $where = ' WHERE ei.company_id = ?';
            $bind[] = $companyId;
        }

        $sql = 'SELECT ei.id, ei.company_id, ei.label, ei.number, ei.instance_identifier, ei.status, ei.connected_at,
                       c.name AS company_name, c.slug AS company_slug
                FROM evolution_instances ei
                INNER JOIN companies c ON c.id = ei.company_id' . $where . '
                ORDER BY ei.updated_at DESC, ei.id DESC';

        $st = db()->prepare($sql);
        $st->execute($bind);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $summarySql = 'SELECT
                         COUNT(*) AS total_instances,
                         SUM(CASE WHEN status = "open" OR status = "connected" THEN 1 ELSE 0 END) AS online_count,
                         SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_count,
                         SUM(CASE WHEN status = "close" OR status = "disconnected" THEN 1 ELSE 0 END) AS offline_count
                       FROM evolution_instances' . ($companyId !== null && $companyId > 0 ? ' WHERE company_id = ?' : '');
        $summarySt = db()->prepare($summarySql);
        $summarySt->execute($companyId !== null && $companyId > 0 ? [$companyId] : []);
        $summary = $summarySt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'rows' => $rows,
            'summary' => [
                'total' => (int)($summary['total_instances'] ?? 0),
                'online' => (int)($summary['online_count'] ?? 0),
                'pending' => (int)($summary['pending_count'] ?? 0),
                'offline' => (int)($summary['offline_count'] ?? 0),
            ],
        ];
    }
}
