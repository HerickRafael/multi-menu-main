<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/EventLog.php';

class EventLogService
{
    public static function listing(array $filters, int $page = 1, int $perPage = 60): array
    {
        $offset = ($page - 1) * $perPage;
        $rows = EventLog::search($filters, $perPage, $offset);
        $total = EventLog::count($filters);

        return [
            'rows' => $rows,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }
}
