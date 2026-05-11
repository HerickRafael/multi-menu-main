<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * MobileAdminKdsController
 * 
 * Kitchen Display System otimizado para mobile.
 * Reutiliza Order model (snapshot/delta/status) do desktop.
 * View mobile com tabs swipeable em vez de 3 colunas.
 */
class MobileAdminKdsController extends Controller
{
    private function guard(): array
    {
        Auth::start();

        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

        if (!Auth::checkAdmin()) {
            header('Location: /login');
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company || empty($company['id'])) {
            http_response_code(404);
            echo 'Empresa inválida';
            exit;
        }

        $u = Auth::user();
        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        $this->ensureCompanyContext((int)$company['id'], $slug);

        return [$u, $company];
    }

    /**
     * GET /kds — Tela KDS mobile
     */
    public function index(array $params = [])
    {
        [$u, $company] = $this->guard();

        $db = db();
        $companyId = (int)$company['id'];
        $snapshot = Order::snapshot($db, $companyId);

        $bellPath = (string)(config('kds_bell_url') ?? '');
        $bellUrl = '';
        if ($bellPath !== '') {
            if (preg_match('/^(https?:)?\/\//i', $bellPath)) {
                $bellUrl = $bellPath;
            } else {
                $bellUrl = base_url(ltrim($bellPath, '/'));
            }
        }

        $config = [
            'dataUrl'         => '/kds/data',
            'statusUrl'       => '/kds/status',
            'orderDetailBase' => '/orders/show?id=',
            'bellUrl'         => $bellUrl ?: $bellPath,
            'columns' => [
                ['id' => 'pending',   'label' => 'Recebidos'],
                ['id' => 'paid',      'label' => 'Preparando'],
                ['id' => 'completed', 'label' => 'Prontos'],
            ],
            'slaMinutes' => (int)(config('kds_sla_minutes') ?? 20),
            'refreshMs'  => (int)(config('kds_refresh_ms') ?? 1500),
        ];

        $pageTitle = 'KDS';
        $activeNav = 'kds';

        return $this->viewMobile('kds/index', [
            'company'         => $company,
            'u'               => $u,
            'initialSnapshot' => $snapshot,
            'kdsConfig'       => $config,
            'pageTitle'       => $pageTitle,
            'activeNav'       => $activeNav,
        ]);
    }

    /**
     * GET /kds/data — JSON polling endpoint
     */
    public function data(array $params = [])
    {
        [$u, $company] = $this->guard();

        $db = db();
        $companyId = (int)$company['id'];

        $sinceParam = isset($_GET['since']) ? trim((string)$_GET['since']) : '';

        if ($sinceParam !== '') {
            $delta = Order::snapshotDelta($db, $companyId, $sinceParam);
        } else {
            $delta = [
                'orders'       => Order::snapshot($db, $companyId),
                'removed_ids'  => [],
                'full_refresh' => true,
            ];
        }

        $payload = [
            'orders'       => $delta['orders'],
            'removed_ids'  => $delta['removed_ids'] ?? [],
            'full_refresh' => !empty($delta['full_refresh']),
            'server_time'  => gmdate('c'),
        ];

        $syncToken = Order::latestChangeToken($db, $companyId);
        $payload['sync_token'] = $syncToken ?: $payload['server_time'];

        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * POST /kds/status — Atualiza status do pedido
     */
    public function status(array $params = [])
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'method_not_allowed']);
            exit;
        }

        [$u, $company] = $this->guard();

        $db = db();
        $companyId = (int)$company['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $orderId = (int)($input['order_id'] ?? 0);
        $status = strtolower(trim((string)($input['status'] ?? '')));

        if ($orderId <= 0 || $status === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'invalid_payload']);
            exit;
        }

        $ok = Order::updateStatus($db, $orderId, $companyId, $status);

        if (!$ok) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'invalid_status']);
            exit;
        }

        $order = Order::findForKds($db, $orderId, $companyId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'order'   => $order,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function viewMobile(string $path, array $data = [])
    {
        $file = __DIR__ . '/../views/admin/mobile/' . $path . '.php';

        if (!file_exists($file)) {
            http_response_code(500);
            echo "View mobile não encontrada: $path";
            return;
        }

        extract($data);
        include $file;
    }
}
