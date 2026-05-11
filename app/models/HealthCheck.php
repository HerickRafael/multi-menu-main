<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class HealthCheck
{
    public static function add(string $component, string $status, ?string $message = null, ?array $metadata = null): bool
    {
        $sql = 'INSERT INTO health_checks (component, status, message, metadata_json, checked_at)
                VALUES (?, ?, ?, ?, NOW())';
        $st = db()->prepare($sql);
        return $st->execute([
            $component,
            $status,
            $message,
            $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public static function latest(int $limit = 100): array
    {
        $st = db()->prepare('SELECT * FROM health_checks ORDER BY checked_at DESC, id DESC LIMIT ?');
        $st->execute([$limit]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function summaryLastHour(): array
    {
        $sql = 'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = "ok" THEN 1 ELSE 0 END) AS ok_count,
                    SUM(CASE WHEN status = "warning" THEN 1 ELSE 0 END) AS warning_count,
                    SUM(CASE WHEN status = "critical" THEN 1 ELSE 0 END) AS critical_count
                FROM health_checks
                WHERE checked_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)';
        $row = db()->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'ok' => (int)($row['ok_count'] ?? 0),
            'warning' => (int)($row['warning_count'] ?? 0),
            'critical' => (int)($row['critical_count'] ?? 0),
        ];
    }
}
