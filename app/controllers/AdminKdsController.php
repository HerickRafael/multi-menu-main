<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class AdminKdsController extends Controller
{
    private function guard(string $slug): array
    {
        Auth::start();
        $user = Auth::user();

        if (!$user) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company) {
            echo 'Empresa inválida';
            exit;
        }

        if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
            echo 'Acesso negado';
            exit;
        }

        return [$user, $company];
    }

    public function index($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $db = $this->db();
        $companyId = (int)$company['id'];
        $snapshot = Order::snapshot($db, $companyId);
        $hasCanceled = false;

        foreach ($snapshot as $order) {
            if (($order['status'] ?? '') === 'canceled') {
                $hasCanceled = true;
                break;
            }
        }

        // Resolve bell URL (aceita http(s), //, caminho absoluto ou relativo ao app)
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
            'dataUrl'         => base_url('admin/' . rawurlencode($company['slug']) . '/kds/data'),
            'statusUrl'       => base_url('admin/' . rawurlencode($company['slug']) . '/kds/status'),
            'orderDetailBase' => base_url('admin/' . rawurlencode($company['slug']) . '/orders/show?id='),
            'bellUrl'         => $bellUrl ?: $bellPath,
            'columns' => [
                ['id' => 'pending',   'label' => 'Recebidos'],
                ['id' => 'paid',      'label' => 'Preparando'],
                ['id' => 'completed', 'label' => 'Prontos'],
            ],
            'slaMinutes'     => (int)(config('kds_sla_minutes') ?? 20),
            'refreshMs'      => (int)(config('kds_refresh_ms') ?? 1500),
        ];

        return $this->view('admin/kds/index', [
            'company'         => $company,
            'activeSlug'      => $company['slug'],
            'initialSnapshot' => $snapshot,
            'kdsConfig'       => $config,
            'hasCanceled'     => $hasCanceled,
        ]);
    }

    public function data($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $db = $this->db();
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

    public function status($params)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'method_not_allowed']);
            exit;
        }

        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $db = $this->db();
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
}
