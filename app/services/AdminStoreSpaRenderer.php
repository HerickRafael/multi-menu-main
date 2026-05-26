<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Renders the React SPA shell with an injected window payload.
 * Used by admin store controllers migrated to React (Dashboard, Orders,
 * Categories, Ingredients, etc.).
 */
final class AdminStoreSpaRenderer
{
    /**
     * @param array<string, mixed> $company
     * @param array<string, mixed> $payload
     */
    public static function render(string $slug, array $company, string $payloadKey, array $payload): void
    {
        $indexPath = dirname(__DIR__, 2) . '/public/superadmin/index.html';
        if (!is_file($indexPath)) {
            http_response_code(503);
            echo 'Painel indisponivel. Build frontend nao encontrado.';
            return;
        }

        $html = (string)file_get_contents($indexPath);

        $companyId = (int)($company['id'] ?? 0);

        $context = [
            'slug' => $slug,
            'company_id' => $companyId,
            'company_name' => (string)($company['name'] ?? 'Loja'),
            'company_logo' => (string)($company['logo'] ?? ''),
            'company_banner' => (string)($company['banner'] ?? ''),
            'min_order' => isset($company['min_order']) ? (float)$company['min_order'] : null,
            'theme' => [
                'primaryColor' => function_exists('admin_theme_primary_color') ? admin_theme_primary_color($company) : '#4F46E5',
                'primaryGradient' => function_exists('admin_theme_gradient') ? admin_theme_gradient($company) : null,
            ],
            'system_logo'     => '/assets/icons/admin/logo-multimenu.png',
            'store_is_open'   => self::storeIsOpen($companyId),
            'ifood_is_active' => self::ifoodIsActive($companyId),
            'store_hours'     => self::getStoreHours($companyId),
            'settings_url'    => '/admin/' . rawurlencode($slug) . '/settings',
        ];

        $csrfToken = '';
        if (class_exists('\\App\\Middleware\\CsrfProtection')) {
            try {
                $csrfToken = \App\Middleware\CsrfProtection::generateToken(false);
            } catch (\Throwable $e) {
                $csrfToken = '';
            }
        }

        $scripts =
            '<meta name="csrf-token" content="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">' .
            '<script>window.__ADMIN_STORE_CONTEXT__ = ' .
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) .
            ';</script>' .
            '<script>window.' . $payloadKey . ' = ' .
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) .
            ';</script>';

        if (str_contains($html, '</head>')) {
            $html = str_replace('</head>', $scripts . "\n</head>", $html);
        } else {
            $html = $scripts . "\n" . $html;
        }

        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $html;
    }

    private static function getStoreHours(int $companyId): array
    {
        try {
            $stmt = db()->prepare('SELECT * FROM company_hours WHERE company_id = ? ORDER BY weekday');
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $result = [];
            foreach ($rows as $row) {
                $result[(int)$row['weekday']] = [
                    'is_open' => (bool)$row['is_open'],
                    'open1'   => !empty($row['open1'])  ? substr($row['open1'],  0, 5) : null,
                    'close1'  => !empty($row['close1']) ? substr($row['close1'], 0, 5) : null,
                    'open2'   => !empty($row['open2'])  ? substr($row['open2'],  0, 5) : null,
                    'close2'  => !empty($row['close2']) ? substr($row['close2'], 0, 5) : null,
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function storeIsOpen(int $companyId): bool
    {
        try {
            $tz = function_exists('config') ? (config('timezone') ?? 'America/Sao_Paulo') : 'America/Sao_Paulo';
            date_default_timezone_set($tz);
            $weekday = (int)date('w'); // 0=Dom, 1=Seg, ..., 6=Sab
            $db = db();
            $stmt = $db->prepare('SELECT * FROM company_hours WHERE company_id = ? AND weekday = ?');
            $stmt->execute([$companyId, $weekday]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row || empty($row['is_open'])) {
                return false;
            }
            $now   = new \DateTime();
            $today = $now->format('Y-m-d');
            foreach ([['open1', 'close1'], ['open2', 'close2']] as [$ok, $ck]) {
                if (empty($row[$ok]) || empty($row[$ck])) {
                    continue;
                }
                $open  = \DateTime::createFromFormat('Y-m-d H:i:s', $today . ' ' . $row[$ok]);
                $close = \DateTime::createFromFormat('Y-m-d H:i:s', $today . ' ' . $row[$ck]);
                if (!$open || !$close) {
                    continue;
                }
                if ($close < $open) {
                    $close = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d', strtotime('+1 day')) . ' ' . $row[$ck]);
                }
                if ($now >= $open && $now <= $close) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function ifoodIsActive(int $companyId): bool
    {
        try {
            $stmt = db()->prepare('SELECT is_active FROM ifood_integrations WHERE company_id = ? LIMIT 1');
            $stmt->execute([$companyId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row && (int)$row['is_active'] === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
