<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/IFood/IFoodWidgetService.php';

use App\Services\IFood\IFoodWidgetService;

/**
 * Endpoints públicos do widget iFood — chamados pelo site público do merchant.
 * Sem auth. Resolve company pelo slug e responde apenas o que é seguro expor.
 *
 *   GET /api/{slug}/ifood-widget/config.json   → config sanitizada + URLs
 *   GET /api/{slug}/ifood-widget/ifood.js      → snippet JS auto-contido
 *   GET /api/{slug}/ifood-widget/track/{ref}   → URL de tracking de pedido iFood
 */
class PublicIFoodWidgetController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function configJson($params): void
    {
        $companyId = $this->resolveCompany((string) ($params['slug'] ?? ''));
        if ($companyId === null) {
            $this->jsonResponse(['success' => false, 'message' => 'company não encontrada'], 404);
            return;
        }

        $service = new IFoodWidgetService($this->db);
        $config = $service->getPublicConfig($companyId);

        // CORS-style allow-list opcional (se admin configurou allowed_origins)
        $this->applyCorsHeaders($companyId);

        $this->jsonResponse(['success' => true, 'data' => $config]);
    }

    public function jsSnippet($params): void
    {
        $companyId = $this->resolveCompany((string) ($params['slug'] ?? ''));
        if ($companyId === null) {
            http_response_code(404);
            header('Content-Type: application/javascript; charset=utf-8');
            echo "/* iFood Widget: company não encontrada */\n";
            return;
        }

        $cacheDir = __DIR__ . '/../../storage/cache/ifood_widget';
        $service = new IFoodWidgetService($this->db, $cacheDir);
        $js = $service->renderJsSnippet($companyId);

        $this->applyCorsHeaders($companyId);
        header('Content-Type: application/javascript; charset=utf-8');
        // Cache 1h no browser/edge — o cache_version no JS resolve invalidação após save
        header('Cache-Control: public, max-age=3600');
        echo $js;
    }

    public function trackOrder($params): void
    {
        $companyId = $this->resolveCompany((string) ($params['slug'] ?? ''));
        if ($companyId === null) {
            $this->jsonResponse(['success' => false, 'message' => 'company não encontrada'], 404);
            return;
        }

        $orderRef = (string) ($params['ref'] ?? '');
        $service = new IFoodWidgetService($this->db);
        $result = $service->trackingUrlForOrder($companyId, $orderRef);

        $this->applyCorsHeaders($companyId);

        $this->jsonResponse([
            'success' => $result['ok'],
            'url'     => $result['url'],
            'message' => $result['message'],
        ], $result['ok'] ? 200 : 404);
    }

    private function resolveCompany(string $slug): ?int
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }
        try {
            $stmt = $this->db->prepare('SELECT id FROM companies WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $id = $stmt->fetchColumn();
            return $id === false ? null : (int) $id;
        } catch (\Throwable $e) {
            error_log('[PublicWidget] resolveCompany falhou: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Se a config tem allowed_origins, valida o Origin e seta Access-Control-Allow-Origin.
     * Se não tem (null), allow-all (*) — apropriado para widget público.
     */
    private function applyCorsHeaders(int $companyId): void
    {
        $stmt = $this->db->prepare('SELECT allowed_origins FROM ifood_widget_config WHERE company_id = ?');
        $stmt->execute([$companyId]);
        $allowed = (string) ($stmt->fetchColumn() ?: '');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($allowed === '') {
            header('Access-Control-Allow-Origin: *');
            return;
        }

        $list = array_filter(array_map('trim', explode(',', $allowed)));
        if ($origin !== '' && in_array($origin, $list, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }
        // Se origin não está na lista, omitimos o header (browser bloqueia request)
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
