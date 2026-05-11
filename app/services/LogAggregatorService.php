<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/SystemLog.php';

class LogAggregatorService
{
    public static function ingestExceptionsLog(string $filePath, int $maxLines = 300): int
    {
        if (!is_file($filePath)) {
            return 0;
        }

        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return 0;
        }

        $slice = array_slice($lines, -1 * max(1, $maxLines));
        $inserted = 0;

        foreach ($slice as $line) {
            $level = self::guessLevel($line);
            $module = 'app';
            $message = mb_substr(trim($line), 0, 2000);

            if (SystemLog::create($level, $module, $message, ['origin' => 'exceptions.log'], null, null, 'file')) {
                $inserted++;
            }
        }

        return $inserted;
    }

    public static function getLogs(array $filters, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $rows = SystemLog::search($filters, $perPage, $offset);
        $total = SystemLog::count($filters);

        return [
            'rows' => $rows,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function exportCsv(array $filters, int $limit = 2000): string
    {
        $rows = SystemLog::search($filters, $limit, 0);
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, ['id', 'level', 'module', 'message', 'company_id', 'order_id', 'source', 'logged_at']);

        foreach ($rows as $row) {
            fputcsv($fp, [
                $row['id'] ?? '',
                $row['level'] ?? '',
                $row['module'] ?? '',
                $row['message'] ?? '',
                $row['company_id'] ?? '',
                $row['order_id'] ?? '',
                $row['source'] ?? '',
                $row['logged_at'] ?? '',
            ]);
        }

        rewind($fp);
        return (string)stream_get_contents($fp);
    }

    private static function guessLevel(string $line): string
    {
        $l = mb_strtolower($line);
        if (str_contains($l, 'fatal') || str_contains($l, 'critical')) {
            return 'critical';
        }
        if (str_contains($l, 'error') || str_contains($l, 'exception')) {
            return 'error';
        }
        if (str_contains($l, 'warn')) {
            return 'warning';
        }
        if (str_contains($l, 'debug')) {
            return 'debug';
        }
        return 'info';
    }
}
