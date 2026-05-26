<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Serviços específicos do controller
require_once __DIR__ . '/../services/IFoodService.php';

/**
 * Admin iFood Controller
 * 
 * Handles iFood integration management
 * - Configuration
 * - Order listing
 * - Order actions (confirm, ready, dispatch)
 * - Manual polling
 * 
 * @package App\Controllers
 */
class AdminIFoodController extends Controller
{
    /** Valida sessão, empresa e retorna [$u, $company] */
    /**
     * Aceita tanto o id local de ifood_orders (int) quanto o UUID/display_id
     * e retorna o ifood_order_id (UUID) correspondente, ou null.
     */
    private function resolveIFoodOrderIdParam(int $companyId, string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        $stmt = $this->db()->prepare(
            'SELECT ifood_order_id FROM ifood_orders
              WHERE company_id = :cid
                AND (id = :as_int OR ifood_order_id = :as_uuid OR ifood_display_id = :as_disp)
              LIMIT 1'
        );
        $stmt->execute([
            ':cid'      => $companyId,
            ':as_int'   => ctype_digit($raw) ? (int) $raw : 0,
            ':as_uuid'  => $raw,
            ':as_disp'  => $raw,
        ]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (string) $id;
    }

    private function guard(string $slug): array
    {
        Auth::start();
        $u = Auth::user();

        if (!$u) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company) {
            echo 'Empresa inválida';
            exit;
        }

        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            echo 'Acesso negado';
            exit;
        }

        return [$u, $company];
    }
    
    /**
     * Configuration page
     */
    public function config($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $ifoodService = new IFoodService($db, (int)$company['id']);
        $config = $ifoodService->getConfig();
        $merchants = [];
        $merchantStatus = null;

        if ($config && !empty($config['access_token'])) {
            try {
                $merchants = $ifoodService->getMerchants();
                if (!empty($config['merchant_id'])) {
                    $merchantStatus = $ifoodService->getMerchantStatus($ifoodService->getActiveMerchantId());
                }
            } catch (\Exception $e) {
                // Ignore - will show as disconnected
            }
        }

        $isConnected = is_array($config) && !empty($config['access_token']);

        $payload = [
            'config' => [
                'client_id' => (string)($config['client_id'] ?? ''),
                'client_secret_masked' => !empty($config['client_secret'])
                    ? substr((string)$config['client_secret'], 0, 4) . str_repeat('•', 28)
                    : '',
                'has_client_secret' => !empty($config['client_secret']),
                'merchant_id' => (string)($config['merchant_id'] ?? ''),
                'sandbox_merchant_id' => (string)($config['sandbox_merchant_id'] ?? ''),
                'environment' => (string)($config['environment'] ?? 'production'),
                'is_active' => (int)($config['is_active'] ?? 0) === 1,
                'auto_confirm' => (int)($config['auto_confirm'] ?? 0) === 1,
                'last_sync_at' => $config['last_sync_at'] ?? null,
                'last_error' => $config['last_error'] ?? null,
            ],
            'is_connected' => $isConnected,
            'merchants' => array_map(static function (array $m): array {
                return [
                    'id' => (string)($m['id'] ?? ''),
                    'name' => (string)($m['name'] ?? ''),
                    'corporate_name' => (string)($m['corporateName'] ?? $m['corporate_name'] ?? ''),
                ];
            }, $merchants ?: []),
            'merchant_status' => $merchantStatus !== null ? self::parseMerchantStatus($merchantStatus) : null,
            'flash' => [
                'error' => $_SESSION['flash_error'] ?? null,
                'success' => $_SESSION['flash_success'] ?? null,
            ],
            'webhook_url' => base_url('webhook/ifood'),
            'urls' => [
                'submit' => '/admin/' . rawurlencode($slug) . '/ifood/config/save',
                'orders' => '/admin/' . rawurlencode($slug) . '/orders?source=ifood',
                'status' => '/admin/' . rawurlencode($slug) . '/ifood/status',
                'test_connection' => '/admin/' . rawurlencode($slug) . '/ifood/test-connection',
                'clear_error' => '/admin/' . rawurlencode($slug) . '/ifood/clear-error',
                'poll' => '/admin/' . rawurlencode($slug) . '/ifood/poll',
                'logs' => '/admin/' . rawurlencode($slug) . '/ifood/logs',
                'reviews' => '/admin/' . rawurlencode($slug) . '/ifood/reviews',
            ],
        ];

        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_CONFIG__', $payload);
    }
    
    /**
     * Save configuration
     */
    public function saveConfig($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . base_url("admin/{$slug}/ifood/config"));
            exit;
        }
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        
        $data = [
            'client_id' => trim($_POST['client_id'] ?? ''),
            'client_secret' => trim($_POST['client_secret'] ?? ''),
            'merchant_id' => trim($_POST['merchant_id'] ?? ''),
            'sandbox_merchant_id' => trim($_POST['sandbox_merchant_id'] ?? ''),
            'environment' => $_POST['environment'] ?? 'production',
            'is_active' => isset($_POST['is_active']),
            'auto_confirm' => isset($_POST['auto_confirm']),
        ];
        
        if ($ifoodService->saveConfig($data)) {
            $_SESSION['flash_success'] = 'Configuração salva com sucesso!';
        } else {
            $_SESSION['flash_error'] = 'Erro ao salvar configuração.';
        }
        
        header('Location: ' . base_url("admin/{$slug}/ifood/config"));
        exit;
    }
    
    /**
     * List iFood orders — redirecionado para a página unificada de pedidos
     */
    public function orders($params): void
    {
        $slug = $params['slug'];
        $qs = http_build_query(array_filter([
            'source' => 'ifood',
            'status' => $_GET['status'] ?? null,
        ]));
        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/orders') . ($qs ? '?' . $qs : ''));
        exit;
    }

    /**
     * View single order
     */
    public function viewOrder($params): void
    {
        $slug = $params['slug'];
        $id = (int)$params['id'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $ifoodService = new IFoodService($db, (int)$company['id']);
        $order = $ifoodService->getOrder($id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pedido não encontrado.';
            header('Location: ' . base_url("admin/{$slug}/ifood/orders"));
            exit;
        }

        $decode = static function ($raw): array {
            if (is_array($raw)) return $raw;
            if (!is_string($raw) || $raw === '') return [];
            $d = json_decode($raw, true);
            return is_array($d) ? $d : [];
        };

        $items = $decode($order['items'] ?? null);
        $payments = $decode($order['payments'] ?? null);
        $deliveryAddress = $decode($order['delivery_address'] ?? null);
        $benefits = $decode($order['benefits'] ?? null);

        $toFloat = static fn($v) => $v === null || $v === '' ? 0.0 : (float)$v;

        $payload = [
            'order' => [
                'id' => (int)($order['id'] ?? 0),
                'ifood_order_id' => (string)($order['ifood_order_id'] ?? ''),
                'ifood_display_id' => (string)($order['ifood_display_id'] ?? ''),
                'local_order_id' => isset($order['local_order_id']) && $order['local_order_id'] !== null
                    ? (int)$order['local_order_id'] : null,
                'status' => (string)($order['status'] ?? ''),
                'order_type' => (string)($order['order_type'] ?? ''),
                'order_timing' => (string)($order['order_timing'] ?? ''),
                'delivered_by' => (string)($order['delivered_by'] ?? ''),
                'pickup_code' => (string)($order['pickup_code'] ?? ''),
                'customer_name' => (string)($order['customer_name'] ?? ''),
                'customer_phone' => (string)($order['customer_phone'] ?? ''),
                'subtotal' => $toFloat($order['subtotal'] ?? 0),
                'delivery_fee' => $toFloat($order['delivery_fee'] ?? 0),
                'additional_fees' => $toFloat($order['additional_fees'] ?? 0),
                'benefits_total' => $toFloat($order['benefits_total'] ?? 0),
                'total_amount' => $toFloat($order['total_amount'] ?? 0),
                'cancellation_reason' => (string)($order['cancellation_reason'] ?? ''),
                'ifood_created_at' => isset($order['ifood_created_at']) ? (string)$order['ifood_created_at'] : null,
                'created_at' => (string)($order['created_at'] ?? ''),
                'confirmed_at' => isset($order['confirmed_at']) ? (string)$order['confirmed_at'] : null,
                'ready_at' => isset($order['ready_at']) ? (string)$order['ready_at'] : null,
                'dispatched_at' => isset($order['dispatched_at']) ? (string)$order['dispatched_at'] : null,
                'concluded_at' => isset($order['concluded_at']) ? (string)$order['concluded_at'] : null,
                'cancelled_at' => isset($order['cancelled_at']) ? (string)$order['cancelled_at'] : null,
                'items' => $items,
                'payments' => $payments,
                'delivery_address' => $deliveryAddress,
                'benefits' => $benefits,
            ],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/ifood/orders',
                'confirm' => '/admin/' . rawurlencode($slug) . '/ifood/orders/' . (int)$order['id'] . '/confirm',
                'ready' => '/admin/' . rawurlencode($slug) . '/ifood/orders/' . (int)$order['id'] . '/ready',
                'dispatch' => '/admin/' . rawurlencode($slug) . '/ifood/orders/' . (int)$order['id'] . '/dispatch',
                'cancel' => '/admin/' . rawurlencode($slug) . '/ifood/orders/' . (int)$order['id'] . '/cancel',
                'cancel_reasons' => '/admin/' . rawurlencode($slug) . '/ifood/orders/' . (int)$order['id'] . '/cancel-reasons',
                'local_order' => $order['local_order_id'] !== null
                    ? '/admin/' . rawurlencode($slug) . '/orders/show?id=' . (int)$order['local_order_id']
                    : null,
                'driver_state'    => '/admin/' . rawurlencode($slug) . '/ifood/orders/' . (int)$order['id'] . '/driver-state',
                'driver_request' => '/admin/' . rawurlencode($slug) . '/ifood/orders/' . (int)$order['id'] . '/request-driver',
                'driver_cancel'  => '/admin/' . rawurlencode($slug) . '/ifood/orders/' . (int)$order['id'] . '/cancel-driver',
            ],
            'driver_state' => $this->loadDriverStateForPayload((int) $company['id'], (string) ($order['ifood_order_id'] ?? '')),
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_ORDER__', $payload);
    }

    /**
     * Lookup ifood_order_drivers para um pedido específico. Retorna null se não houver.
     * @return array<string,mixed>|null
     */
    private function loadDriverStateForPayload(int $companyId, string $ifoodOrderId): ?array
    {
        if ($ifoodOrderId === '') return null;
        $stmt = $this->db()->prepare(
            'SELECT environment, ifood_order_id, order_display_id, request_status,
                    driver_id, driver_name, driver_phone, vehicle_type,
                    requested_at, assigned_at, picked_up_at, delivered_at,
                    cancelled_at, cancel_reason, retries, last_error,
                    last_response_status, updated_at
               FROM ifood_order_drivers
              WHERE company_id = :cid AND ifood_order_id = :oid
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':oid' => $ifoodOrderId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
    
    /**
     * Confirm order
     */
    public function confirmOrder($params): void
    {
        $slug = $params['slug'];
        $id = (int)$params['id'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        $order = $ifoodService->getOrder($id);
        
        if (!$order) {
            $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado']);
            return;
        }
        
        $ok = $ifoodService->confirmOrder($order['ifood_order_id']);
        $this->jsonResponse(['success' => $ok, 'message' => $ok ? 'Pedido confirmado.' : 'Falha ao confirmar pedido no iFood.']);
    }
    
    /**
     * Mark order as ready
     */
    public function readyOrder($params): void
    {
        $slug = $params['slug'];
        $id = (int)$params['id'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        $order = $ifoodService->getOrder($id);
        
        if (!$order) {
            $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado']);
            return;
        }
        
        $ok = $ifoodService->readyToPickup($order['ifood_order_id']);
        $this->jsonResponse(['success' => $ok, 'message' => $ok ? 'Pedido marcado como pronto.' : 'Falha ao marcar pedido como pronto.']);
    }
    
    /**
     * Dispatch order
     */
    public function dispatchOrder($params): void
    {
        $slug = $params['slug'];
        $id = (int)$params['id'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        $order = $ifoodService->getOrder($id);
        
        if (!$order) {
            $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado']);
            return;
        }
        
        $ok = $ifoodService->dispatchOrder($order['ifood_order_id']);
        $this->jsonResponse(['success' => $ok, 'message' => $ok ? 'Pedido despachado.' : 'Falha ao despachar pedido no iFood.']);
    }
    
    /**
     * Cancel order
     */
    public function cancelOrder($params): void
    {
        $slug = $params['slug'];
        $id = (int)$params['id'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        $order = $ifoodService->getOrder($id);
        
        if (!$order) {
            $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $reasonCode = $input['reason_code'] ?? null;
        
        $result = $ifoodService->cancelOrder($order['ifood_order_id'], $reasonCode);
        $this->jsonResponse($result);
    }
    
    /**
     * Get cancellation reasons
     */
    public function getCancellationReasons($params): void
    {
        $slug = $params['slug'];
        $id = (int)$params['id'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        $order = $ifoodService->getOrder($id);
        
        if (!$order) {
            $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado']);
            return;
        }
        
        $reasons = $ifoodService->getCancellationReasons($order['ifood_order_id']);
        $this->jsonResponse(['success' => true, 'reasons' => $reasons]);
    }
    
    /**
     * Manual poll for new orders
     */
    public function poll($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        $result = $ifoodService->pollEvents();
        $this->jsonResponse([
            'success' => (bool)($result['success'] ?? false),
            'count'   => (int)($result['total_events'] ?? 0),
            'message' => ($result['success'] ?? false)
                ? 'Sincronização concluída: ' . ($result['processed'] ?? 0) . ' evento(s) processado(s).'
                : ($result['error'] ?? 'Falha ao buscar pedidos.'),
        ]);
    }
    
    /**
     * Test connection with iFood API
     */
    public function testConnection($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        
        // Primeiro verifica se as credenciais estão configuradas
        $config = $ifoodService->getConfig();
        if (!$config || empty($config['client_id']) || empty($config['client_secret'])) {
            $this->jsonResponse([
                'success' => false, 
                'error' => 'Configure o Client ID e Client Secret antes de testar.'
            ]);
            return;
        }
        
        // Tenta obter um token de acesso (isso valida as credenciais)
        $result = $ifoodService->testAuthentication();
        
        if (!$result['success']) {
            $this->jsonResponse([
                'success' => false, 
                'error' => $result['error'] ?? 'Credenciais inválidas. Verifique o Client ID e Client Secret.'
            ]);
            return;
        }
        
        // Se autenticação OK, tenta listar merchants
        try {
            $merchants = $ifoodService->getMerchants();
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Conexão estabelecida com sucesso!',
                'merchants' => $merchants,
                'expires_at' => $result['expires_at'] ?? null
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false, 
                'error' => 'Erro ao buscar lojas: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Clear last error
     */
    public function clearError($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $stmt = $db->prepare("UPDATE ifood_integrations SET last_error = NULL WHERE company_id = :cid");
        $stmt->execute(['cid' => (int)$company['id']]);
        
        $this->jsonResponse(['success' => true]);
    }
    
    /**
     * Get integration status
     */
    public function status($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        $config = $ifoodService->getConfig();
        
        $status = [
            'configured' => !empty($config['client_id']),
            'active' => !empty($config['is_active']),
            'has_token' => !empty($config['access_token']),
            'merchant_id' => $config['merchant_id'] ?? null,
        ];
        
        if ($status['has_token'] && $status['merchant_id']) {
            try {
                $merchantStatus = $ifoodService->getMerchantStatus($ifoodService->getActiveMerchantId());
                $status['merchant_status'] = $merchantStatus !== null ? self::parseMerchantStatus($merchantStatus) : null;
            } catch (\Exception $e) {
                $status['merchant_status'] = null;
                $status['error'] = $e->getMessage();
            }
        }
        
        $this->jsonResponse($status);
    }
    
    /**
     * GET /admin/{slug}/ifood/logs
     * Página de auditoria de chamadas à API iFood (Fase 0).
     * Renderiza SPA com filtros: módulo, status, método, env, data, pagina.
     */
    public function logs($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        $companyId = (int)$company['id'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(200, (int)($_GET['per_page'] ?? 50)));
        $module = trim((string)($_GET['module'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $method = trim((string)($_GET['method'] ?? ''));
        $env = trim((string)($_GET['env'] ?? ''));

        $where = ['company_id = :cid'];
        $params2 = [':cid' => $companyId];

        if ($module !== '') {
            $where[] = 'module = :module';
            $params2[':module'] = $module;
        }
        if ($status !== '') {
            // status pode ser: '2xx', '4xx', '5xx', 'error' (null), ou exato '200'
            if ($status === '2xx') {
                $where[] = 'http_status BETWEEN 200 AND 299';
            } elseif ($status === '4xx') {
                $where[] = 'http_status BETWEEN 400 AND 499';
            } elseif ($status === '5xx') {
                $where[] = 'http_status BETWEEN 500 AND 599';
            } elseif ($status === 'error') {
                $where[] = 'http_status IS NULL';
            } elseif (ctype_digit($status)) {
                $where[] = 'http_status = :status';
                $params2[':status'] = (int)$status;
            }
        }
        if ($method !== '') {
            $where[] = 'request_method = :method';
            $params2[':method'] = strtoupper($method);
        }
        if ($env === 'sandbox' || $env === 'production') {
            $where[] = 'environment = :env';
            $params2[':env'] = $env;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM ifood_api_logs WHERE {$whereSql}");
        $countStmt->execute($params2);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $listStmt = $db->prepare(
            "SELECT id, environment, module, request_method, request_url,
                    http_status, latency_ms, attempt_number, error_message, job_id, created_at,
                    LEFT(request_body, 800) AS request_preview,
                    LEFT(response_body, 800) AS response_preview
               FROM ifood_api_logs
              WHERE {$whereSql}
              ORDER BY id DESC
              LIMIT {$perPage} OFFSET {$offset}"
        );
        $listStmt->execute($params2);
        $rows = $listStmt->fetchAll(\PDO::FETCH_ASSOC);

        $payload = [
            'logs' => array_map(static function (array $r): array {
                return [
                    'id'              => (int)$r['id'],
                    'environment'     => (string)$r['environment'],
                    'module'          => (string)$r['module'],
                    'method'          => (string)$r['request_method'],
                    'url'             => (string)$r['request_url'],
                    'status'          => $r['http_status'] !== null ? (int)$r['http_status'] : null,
                    'latency_ms'      => $r['latency_ms'] !== null ? (int)$r['latency_ms'] : null,
                    'attempt'         => (int)$r['attempt_number'],
                    'error'           => $r['error_message'],
                    'job_id'          => $r['job_id'] !== null ? (int)$r['job_id'] : null,
                    'created_at'      => (string)$r['created_at'],
                    'request_preview' => $r['request_preview'],
                    'response_preview'=> $r['response_preview'],
                ];
            }, $rows),
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int)ceil($total / $perPage),
            ],
            'filters' => [
                'module' => $module,
                'status' => $status,
                'method' => $method,
                'env'    => $env,
            ],
            'urls' => [
                'self'   => '/admin/' . rawurlencode($slug) . '/ifood/logs',
                'config' => '/admin/' . rawurlencode($slug) . '/ifood/config',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_LOGS__', $payload);
    }

    /**
     * GET /admin/{slug}/ifood/reviews
     * Lista de avaliações sincronizadas do iFood, paginada e filtrável.
     */
    public function reviews($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        $companyId = (int)$company['id'];

        $config = (new IFoodService($db, $companyId))->getConfig();
        $currentEnv = (string)($config['environment'] ?? 'production');
        $merchantForEnv = $currentEnv === 'sandbox'
            ? (string)($config['sandbox_merchant_id'] ?? '')
            : (string)($config['merchant_id'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 20)));
        $rating = trim((string)($_GET['rating'] ?? ''));
        $hasComment = trim((string)($_GET['has_comment'] ?? '')); // '', 'yes', 'no'
        $env = trim((string)($_GET['env'] ?? ''));

        $where = ['company_id = :cid'];
        $bind = [':cid' => $companyId];

        if (ctype_digit($rating) && (int)$rating >= 1 && (int)$rating <= 5) {
            $where[] = 'rating = :rating';
            $bind[':rating'] = (int)$rating;
        }
        if ($hasComment === 'yes') {
            $where[] = "comment IS NOT NULL AND comment <> ''";
        } elseif ($hasComment === 'no') {
            $where[] = "(comment IS NULL OR comment = '')";
        }
        if ($env === 'sandbox' || $env === 'production') {
            $where[] = 'environment = :env';
            $bind[':env'] = $env;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM ifood_reviews WHERE {$whereSql}");
        $countStmt->execute($bind);
        $total = (int)$countStmt->fetchColumn();

        // Agregados (rating médio + breakdown), filtrando apenas por env quando informado
        $statsWhere = ['company_id = :cid'];
        $statsBind = [':cid' => $companyId];
        if ($env === 'sandbox' || $env === 'production') {
            $statsWhere[] = 'environment = :env';
            $statsBind[':env'] = $env;
        }
        $statsWhereSql = implode(' AND ', $statsWhere);
        $statsStmt = $db->prepare("SELECT
            COUNT(*) AS total,
            AVG(rating) AS avg_rating,
            SUM(rating = 1) AS s1,
            SUM(rating = 2) AS s2,
            SUM(rating = 3) AS s3,
            SUM(rating = 4) AS s4,
            SUM(rating = 5) AS s5,
            MAX(fetched_at) AS last_fetched
            FROM ifood_reviews WHERE {$statsWhereSql}");
        $statsStmt->execute($statsBind);
        $stats = $statsStmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        $offset = ($page - 1) * $perPage;
        $listStmt = $db->prepare(
            "SELECT id, environment, ifood_review_id, ifood_order_id, order_display_id,
                    rating, comment, customer_name, moderation_status, review_date, fetched_at
               FROM ifood_reviews
              WHERE {$whereSql}
              ORDER BY COALESCE(review_date, fetched_at) DESC, id DESC
              LIMIT {$perPage} OFFSET {$offset}"
        );
        $listStmt->execute($bind);
        $rows = $listStmt->fetchAll(\PDO::FETCH_ASSOC);

        $payload = [
            'reviews' => array_map(static function (array $r): array {
                return [
                    'id'                => (int)$r['id'],
                    'environment'       => (string)$r['environment'],
                    'ifood_review_id'   => (string)$r['ifood_review_id'],
                    'ifood_order_id'    => $r['ifood_order_id'],
                    'order_display_id'  => $r['order_display_id'],
                    'rating'            => (int)$r['rating'],
                    'comment'           => $r['comment'],
                    'customer_name'     => $r['customer_name'],
                    'moderation_status' => $r['moderation_status'],
                    'review_date'       => $r['review_date'],
                    'fetched_at'        => $r['fetched_at'],
                ];
            }, $rows),
            'stats' => [
                'total'         => (int)($stats['total'] ?? 0),
                'avg_rating'    => $stats['avg_rating'] !== null ? round((float)$stats['avg_rating'], 2) : null,
                'breakdown'     => [
                    1 => (int)($stats['s1'] ?? 0),
                    2 => (int)($stats['s2'] ?? 0),
                    3 => (int)($stats['s3'] ?? 0),
                    4 => (int)($stats['s4'] ?? 0),
                    5 => (int)($stats['s5'] ?? 0),
                ],
                'last_fetched'  => $stats['last_fetched'] ?? null,
            ],
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int)ceil($total / max(1, $perPage)),
            ],
            'filters' => [
                'rating'      => $rating,
                'has_comment' => $hasComment,
                'env'         => $env,
            ],
            'config' => [
                'current_env'         => $currentEnv,
                'merchant_for_env'    => $merchantForEnv,
                'can_sync'            => $merchantForEnv !== '' && !empty($config['client_id']),
            ],
            'urls' => [
                'self'   => '/admin/' . rawurlencode($slug) . '/ifood/reviews',
                'config' => '/admin/' . rawurlencode($slug) . '/ifood/config',
                'fetch'  => '/admin/' . rawurlencode($slug) . '/ifood/reviews/fetch',
                'order'  => '/admin/' . rawurlencode($slug) . '/ifood/orders/',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_REVIEWS__', $payload);
    }

    /**
     * POST /admin/{slug}/ifood/reviews/fetch
     * Enfileira um job ifood.reviews.fetch para o merchant do ambiente atual.
     */
    public function fetchReviews($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        $companyId = (int)$company['id'];

        $config = (new IFoodService($db, $companyId))->getConfig();
        if (!$config) {
            $this->jsonResponse(['success' => false, 'message' => 'Configuração iFood não encontrada.']);
        }

        $env = (string)($config['environment'] ?? 'production');
        $merchantId = $env === 'sandbox'
            ? (string)($config['sandbox_merchant_id'] ?? '')
            : (string)($config['merchant_id'] ?? '');

        if ($merchantId === '') {
            $this->jsonResponse([
                'success' => false,
                'message' => "Merchant ID de {$env} não configurado.",
            ]);
        }

        require_once __DIR__ . '/../models/QueueJob.php';
        $ok = \QueueJob::enqueue(
            jobType: 'ifood.reviews.fetch',
            payload: [
                'merchant_id' => $merchantId,
                'environment' => $env,
                'page_size'   => 50,
                'max_pages'   => 100,
            ],
            companyId: $companyId,
            priority: 5,
            maxAttempts: 5
        );

        $this->jsonResponse([
            'success' => (bool)$ok,
            'message' => $ok
                ? 'Sincronização agendada. O worker vai processar nos próximos segundos.'
                : 'Falha ao enfileirar o job.',
        ]);
    }

    /**
     * POST /admin/{slug}/ifood/stock/sync
     * Body opcional: { "product_id": <int> } → sincroniza apenas esse produto.
     * Sem product_id → sincroniza todos os produtos mapeados (in bulk, escalonado).
     */
    public function syncStock($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $config = (new IFoodService($this->db(), $companyId))->getConfig();
        if (!$config || (int) ($config['is_active'] ?? 0) !== 1) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Integração iFood não está ativa para esta loja.',
            ]);
            return;
        }

        require_once __DIR__ . '/../services/IFood/StockSyncDispatcher.php';

        $productIdRaw = $_POST['product_id'] ?? ($_GET['product_id'] ?? null);
        $productId = $productIdRaw !== null && $productIdRaw !== '' ? (int) $productIdRaw : 0;

        if ($productId > 0) {
            $ok = \App\Services\IFood\StockSyncDispatcher::syncProduct($companyId, $productId, 0);
            $this->jsonResponse([
                'success' => true,
                'enqueued' => $ok ? 1 : 0,
                'coalesced' => $ok ? 0 : 1,
                'message' => $ok
                    ? 'Sincronização agendada para o produto.'
                    : 'Já existe sincronização pendente para esse produto (ou sem mapeamento iFood).',
            ]);
            return;
        }

        $count = \App\Services\IFood\StockSyncDispatcher::syncAllForCompany($companyId);
        $this->jsonResponse([
            'success' => true,
            'enqueued' => $count,
            'message' => sprintf('%d produto(s) enfileirado(s) para sincronização.', $count),
        ]);
    }

    /**
     * POST /admin/{slug}/ifood/orders/{id}/request-driver
     * Enfileira solicitação de entregador para um pedido iFood.
     */
    public function requestDriver($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];
        $ifoodOrderId = $this->resolveIFoodOrderIdParam($companyId, (string) ($params['id'] ?? ''));

        if ($ifoodOrderId === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Pedido iFood não encontrado.']);
            return;
        }

        $config = (new IFoodService($this->db(), $companyId))->getConfig();
        if (!$config || (int) ($config['is_active'] ?? 0) !== 1) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Integração iFood não está ativa para esta loja.',
            ]);
            return;
        }

        require_once __DIR__ . '/../services/IFood/DriverRequestDispatcher.php';
        $ok = \App\Services\IFood\DriverRequestDispatcher::requestForOrder($companyId, $ifoodOrderId, 0);

        $this->jsonResponse([
            'success'   => true,
            'enqueued'  => $ok ? 1 : 0,
            'coalesced' => $ok ? 0 : 1,
            'message'   => $ok
                ? 'Solicitação de entregador agendada. O worker vai processar nos próximos segundos.'
                : 'Já existe solicitação pendente, pedido não permite request driver, ou integração inativa.',
        ]);
    }

    /**
     * POST /admin/{slug}/ifood/orders/{id}/cancel-driver
     * Cancela a solicitação de entregador (job assíncrono).
     */
    public function cancelDriver($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];
        $ifoodOrderId = $this->resolveIFoodOrderIdParam($companyId, (string) ($params['id'] ?? ''));

        if ($ifoodOrderId === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Pedido iFood não encontrado.']);
            return;
        }

        $config = (new IFoodService($this->db(), $companyId))->getConfig();
        if (!$config || (int) ($config['is_active'] ?? 0) !== 1) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Integração iFood não está ativa para esta loja.',
            ]);
            return;
        }

        $reason = trim((string) ($_POST['reason'] ?? 'admin_cancel'));
        require_once __DIR__ . '/../services/IFood/DriverRequestDispatcher.php';
        $ok = \App\Services\IFood\DriverRequestDispatcher::cancelForOrder($companyId, $ifoodOrderId, $reason, 0);

        $this->jsonResponse([
            'success'   => true,
            'enqueued'  => $ok ? 1 : 0,
            'coalesced' => $ok ? 0 : 1,
            'message'   => $ok
                ? 'Cancelamento de entregador agendado.'
                : 'Já existe cancelamento pendente, ou pedido sem entry de driver, ou estado terminal.',
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/orders/{id}/driver-state
     * Retorna o estado da solicitação de entregador de um pedido.
     */
    public function driverState($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];
        $ifoodOrderId = $this->resolveIFoodOrderIdParam($companyId, (string) ($params['id'] ?? ''));

        if ($ifoodOrderId === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Pedido iFood não encontrado.']);
            return;
        }

        $stmt = $this->db()->prepare(
            'SELECT environment, ifood_order_id, order_display_id, request_status,
                    driver_id, driver_name, driver_phone, vehicle_type,
                    requested_at, assigned_at, picked_up_at, delivered_at,
                    cancelled_at, cancel_reason, retries, last_error,
                    last_response_status, updated_at
               FROM ifood_order_drivers
              WHERE company_id = :cid AND ifood_order_id = :oid
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':oid' => $ifoodOrderId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->jsonResponse([
            'success' => true,
            'state'   => $row ?: null,
        ]);
    }

    /**
     * POST /admin/{slug}/ifood/shipping/quote
     * Cota custo/ETA de um shipping order. Body JSON: { payload: {...} }
     * Síncrono — retorna o response do iFood ou erro.
     */
    public function quoteShipping($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $raw = file_get_contents('php://input');
        $body = json_decode((string) $raw, true);
        if (!is_array($body)) {
            $this->jsonResponse(['success' => false, 'message' => 'JSON inválido']);
            return;
        }

        $iFoodPayload = $body['payload'] ?? [];
        if (!is_array($iFoodPayload) || empty($iFoodPayload)) {
            $this->jsonResponse(['success' => false, 'message' => 'payload ausente']);
            return;
        }

        require_once __DIR__ . '/../services/IFood/ShippingDispatcher.php';
        $result = \App\Services\IFood\ShippingDispatcher::quoteShippingOrder($companyId, $iFoodPayload);

        $this->jsonResponse([
            'success'     => (bool) $result['ok'],
            'quote'       => $result['quote'],
            'http_status' => $result['http_status'],
            'message'     => $result['message'],
        ]);
    }

    /**
     * POST /admin/{slug}/ifood/shipping/orders
     * Cria um pedido de logística iFood. Body JSON com:
     *   external_reference (opcional)
     *   order_id           (opcional)
     *   payload            (obrigatório) — o body que será POSTado em /shipping/v1.0/orders
     */
    public function createShipping($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $raw = file_get_contents('php://input');
        $body = json_decode((string) $raw, true);
        if (!is_array($body)) {
            $this->jsonResponse(['success' => false, 'message' => 'JSON inválido']);
            return;
        }

        $iFoodPayload = $body['payload'] ?? [];
        if (!is_array($iFoodPayload) || empty($iFoodPayload)) {
            $this->jsonResponse(['success' => false, 'message' => 'payload ausente']);
            return;
        }

        require_once __DIR__ . '/../services/IFood/ShippingDispatcher.php';
        $result = \App\Services\IFood\ShippingDispatcher::createShippingOrder(
            $companyId,
            $iFoodPayload,
            [
                'order_id'           => isset($body['order_id']) ? (int) $body['order_id'] : null,
                'external_reference' => isset($body['external_reference']) ? (string) $body['external_reference'] : null,
            ]
        );

        $this->jsonResponse([
            'success'             => (bool) $result['ok'],
            'external_reference'  => $result['external_reference'],
            'status'              => $result['status'],
            'enqueued'            => $result['enqueued'],
            'already_existed'     => $result['already_existed'],
            'message'             => $result['message'],
        ]);
    }

    /**
     * POST /admin/{slug}/ifood/shipping/orders/{external_reference}/cancel
     */
    public function cancelShipping($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];
        $externalRef = trim((string) ($params['ext'] ?? ''));

        if ($externalRef === '') {
            $this->jsonResponse(['success' => false, 'message' => 'external_reference ausente']);
            return;
        }

        $reason = trim((string) ($_POST['reason'] ?? 'admin_cancel'));
        require_once __DIR__ . '/../services/IFood/ShippingDispatcher.php';
        $result = \App\Services\IFood\ShippingDispatcher::cancelShippingOrder($companyId, $externalRef, $reason);

        $this->jsonResponse([
            'success'  => (bool) $result['ok'],
            'enqueued' => $result['enqueued'],
            'status'   => $result['status'],
            'message'  => $result['message'],
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/shipping/orders/{external_reference}
     */
    public function shippingState($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];
        $externalRef = trim((string) ($params['ext'] ?? ''));

        if ($externalRef === '') {
            $this->jsonResponse(['success' => false, 'message' => 'external_reference ausente']);
            return;
        }

        $stmt = $this->db()->prepare(
            'SELECT id, environment, order_id, external_reference, ifood_shipping_id, status,
                    submitted_at, accepted_at, picked_up_at, delivered_at, cancelled_at, cancel_reason,
                    retries, last_error, last_response_status, next_poll_at, updated_at
               FROM ifood_shipping_orders
              WHERE company_id = :cid AND external_reference = :ref
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':ref' => $externalRef]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->jsonResponse([
            'success' => true,
            'state'   => $row ?: null,
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/shipping/orders
     * Lista os shipping orders da company com filtros opcionais (status, env).
     */
    public function listShipping($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $status = trim((string) ($_GET['status'] ?? ''));
        $env = trim((string) ($_GET['env'] ?? ''));

        $sql = 'SELECT external_reference, environment, ifood_shipping_id, status,
                       submitted_at, delivered_at, cancelled_at, updated_at
                  FROM ifood_shipping_orders
                 WHERE company_id = :cid';
        $bind = [':cid' => $companyId];
        if ($status !== '') {
            $sql .= ' AND status = :status';
            $bind[':status'] = $status;
        }
        if ($env !== '') {
            $sql .= ' AND environment = :env';
            $bind[':env'] = $env === 'sandbox' ? 'sandbox' : 'production';
        }
        $sql .= ' ORDER BY updated_at DESC LIMIT 200';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($bind);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $this->jsonResponse([
            'success' => true,
            'items'   => $rows,
            'count'   => count($rows),
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/stock
     * Página React de Stock Sync — lista de produtos mapeados + estado de sync.
     */
    public function stockSyncPage($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        // Lista joining mapping + sync_state + products
        $stmt = $this->db()->prepare(
            "SELECT m.product_id, m.ifood_product_id, m.is_active AS mapping_active,
                    p.name AS product_name, p.active AS product_active,
                    s.environment, s.desired_status, s.last_synced_status,
                    s.last_synced_at, s.last_error, s.consecutive_failures
               FROM ifood_product_mapping m
          LEFT JOIN products p ON p.id = m.product_id
          LEFT JOIN ifood_stock_sync_state s
                    ON s.company_id = m.company_id
                    AND s.product_id = m.product_id
              WHERE m.company_id = :cid
                AND m.product_id IS NOT NULL
              ORDER BY (s.last_synced_status IS NOT NULL AND s.last_synced_status <> s.desired_status) DESC,
                       s.consecutive_failures DESC,
                       p.name ASC
              LIMIT 500"
        );
        $stmt->execute([':cid' => $companyId]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Counters
        $stats = ['total' => count($items), 'synced' => 0, 'drift' => 0, 'never_synced' => 0, 'with_errors' => 0];
        foreach ($items as $row) {
            $desired = (string) ($row['desired_status'] ?? '');
            $last = (string) ($row['last_synced_status'] ?? '');
            $failures = (int) ($row['consecutive_failures'] ?? 0);
            if ($last === '') {
                $stats['never_synced']++;
            } elseif ($desired !== '' && $desired !== $last) {
                $stats['drift']++;
            } else {
                $stats['synced']++;
            }
            if ($failures > 0) {
                $stats['with_errors']++;
            }
        }

        $payload = [
            'items' => $items,
            'stats' => $stats,
            'urls' => [
                'self'     => '/admin/' . rawurlencode($slug) . '/ifood/stock',
                'sync'     => '/admin/' . rawurlencode($slug) . '/ifood/stock/sync',
                'state'    => '/admin/' . rawurlencode($slug) . '/ifood/stock/state',
                'logistics'=> '/admin/' . rawurlencode($slug) . '/ifood/logistics',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_STOCK__', $payload);
    }

    /**
     * GET /admin/{slug}/ifood/shipping
     * Lista de shipping orders (React shell).
     */
    public function shippingListPage($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        $envFilter = trim((string) ($_GET['env'] ?? ''));

        $sql = 'SELECT external_reference, environment, ifood_shipping_id, status,
                       submitted_at, delivered_at, cancelled_at, updated_at,
                       order_id, retries
                  FROM ifood_shipping_orders
                 WHERE company_id = :cid';
        $bind = [':cid' => $companyId];
        if ($statusFilter !== '') { $sql .= ' AND status = :status'; $bind[':status'] = $statusFilter; }
        if ($envFilter !== '')    { $sql .= ' AND environment = :env'; $bind[':env'] = $envFilter === 'sandbox' ? 'sandbox' : 'production'; }
        $sql .= ' ORDER BY updated_at DESC LIMIT 200';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($bind);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Counters por status pra exibir filtros
        $stmt = $this->db()->prepare(
            "SELECT status, COUNT(*) AS c FROM ifood_shipping_orders WHERE company_id = :cid GROUP BY status"
        );
        $stmt->execute([':cid' => $companyId]);
        $counts = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['status']] = (int) $row['c'];
        }

        $payload = [
            'items'   => $items,
            'counts'  => $counts,
            'filters' => ['status' => $statusFilter, 'env' => $envFilter],
            'urls' => [
                'self'   => '/admin/' . rawurlencode($slug) . '/ifood/shipping',
                'list'   => '/admin/' . rawurlencode($slug) . '/ifood/shipping/orders',
                'create' => '/admin/' . rawurlencode($slug) . '/ifood/shipping/new',
                'detail_base' => '/admin/' . rawurlencode($slug) . '/ifood/shipping/r/',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_SHIPPING_LIST__', $payload);
    }

    /**
     * GET /admin/{slug}/ifood/shipping/new
     * Página de criação de shipping order.
     */
    public function shippingNewPage($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);

        $payload = [
            'company' => ['id' => (int) $company['id'], 'name' => (string) ($company['name'] ?? '')],
            'urls' => [
                'self'   => '/admin/' . rawurlencode($slug) . '/ifood/shipping/new',
                'list'   => '/admin/' . rawurlencode($slug) . '/ifood/shipping',
                'quote'  => '/admin/' . rawurlencode($slug) . '/ifood/shipping/quote',
                'create' => '/admin/' . rawurlencode($slug) . '/ifood/shipping/orders',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_SHIPPING_NEW__', $payload);
    }

    /**
     * GET /admin/{slug}/ifood/shipping/r/{ref}
     * Detalhe de um shipping order.
     */
    public function shippingDetailPage($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];
        $ref = (string) ($params['ref'] ?? '');

        $stmt = $this->db()->prepare(
            'SELECT * FROM ifood_shipping_orders
              WHERE company_id = :cid AND external_reference = :ref
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':ref' => $ref]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        $payload = [
            'shipping' => $row,
            'urls' => [
                'self'   => '/admin/' . rawurlencode($slug) . '/ifood/shipping/r/' . rawurlencode($ref),
                'list'   => '/admin/' . rawurlencode($slug) . '/ifood/shipping',
                'state'  => '/admin/' . rawurlencode($slug) . '/ifood/shipping/orders/' . rawurlencode($ref),
                'cancel' => '/admin/' . rawurlencode($slug) . '/ifood/shipping/orders/' . rawurlencode($ref) . '/cancel',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_SHIPPING_DETAIL__', $payload);
    }

    /**
     * GET /admin/{slug}/ifood/logistics
     * Renderiza a página React de Central Logística com payload inicial.
     */
    public function logisticsPage($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        require_once __DIR__ . '/../services/IFood/LogisticsDashboardService.php';
        $service = new \App\Services\IFood\LogisticsDashboardService($this->db());

        $payload = [
            'dashboard' => $service->dashboard($companyId),
            'urls' => [
                'self'         => '/admin/' . rawurlencode($slug) . '/ifood/logistics',
                'dashboard'    => '/admin/' . rawurlencode($slug) . '/ifood/logistics/dashboard',
                'summary'      => '/admin/' . rawurlencode($slug) . '/ifood/logistics/summary',
                'active'       => '/admin/' . rawurlencode($slug) . '/ifood/logistics/active',
                'metrics'      => '/admin/' . rawurlencode($slug) . '/ifood/logistics/metrics',
                'alerts'       => '/admin/' . rawurlencode($slug) . '/ifood/logistics/alerts',
                'config'       => '/admin/' . rawurlencode($slug) . '/ifood/config',
                'observability'=> '/admin/' . rawurlencode($slug) . '/ifood/observability',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_LOGISTICS__', $payload);
    }

    /**
     * GET /admin/{slug}/ifood/observability
     * Renderiza a página React de DLQ Observability.
     */
    public function observabilityPage($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);

        require_once __DIR__ . '/../services/IFood/DLQObservabilityService.php';
        $service = new \App\Services\IFood\DLQObservabilityService($this->db());

        $payload = [
            'health' => $service->health(),
            'dead_jobs' => $service->deadJobs((int) $company['id'], null, 50),
            'urls' => [
                'self'           => '/admin/' . rawurlencode($slug) . '/ifood/observability',
                'health'         => '/admin/' . rawurlencode($slug) . '/ifood/observability/health',
                'api_health'     => '/admin/' . rawurlencode($slug) . '/ifood/observability/api-health',
                'dead_jobs'      => '/admin/' . rawurlencode($slug) . '/ifood/observability/dead-jobs',
                'retry_one'      => '/admin/' . rawurlencode($slug) . '/ifood/observability/dead-jobs',
                'retry_all'      => '/admin/' . rawurlencode($slug) . '/ifood/observability/dead-jobs/retry-all',
                'logistics'      => '/admin/' . rawurlencode($slug) . '/ifood/logistics',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_OBSERVABILITY__', $payload);
    }

    /**
     * GET /admin/{slug}/ifood/widget
     * Central iFood — embute o widget iFood (chat com cliente, rastreio,
     * pausar loja, status de pedidos em tempo real) dentro do nosso admin.
     *
     * O widget é uma iframe servida pelo iFood. O admin configura a URL
     * uma vez (obtida no portal do desenvolvedor iFood) e ela é renderizada
     * em tela cheia dentro da nossa página.
     */
    public function widgetPage($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        require_once __DIR__ . '/../services/IFood/IFoodWidgetService.php';
        $service = new \App\Services\IFood\IFoodWidgetService($this->db());

        $config = $service->getConfig($companyId);

        // Status da integração — usa pra mostrar se está OK pra usar o widget.
        $ifoodConfig = (new IFoodService($this->db(), $companyId))->getConfig();
        $integrationOk = $ifoodConfig && (int) ($ifoodConfig['is_active'] ?? 0) === 1;

        $payload = [
            'widget_url'      => (string) ($config['merchant_url'] ?? ''),
            'enabled'         => (bool) $config['enabled'],
            'integration_ok'  => $integrationOk,
            'merchant_id'     => $integrationOk ? (string) (
                ($ifoodConfig['environment'] ?? 'production') === 'sandbox'
                    ? ($ifoodConfig['sandbox_merchant_id'] ?? '')
                    : ($ifoodConfig['merchant_id'] ?? '')
            ) : '',
            'environment'     => $integrationOk ? (string) ($ifoodConfig['environment'] ?? 'production') : 'production',
            'urls' => [
                'self'       => '/admin/' . rawurlencode($slug) . '/ifood/widget',
                'save'       => '/admin/' . rawurlencode($slug) . '/ifood/widget/config',
                'config'     => '/admin/' . rawurlencode($slug) . '/ifood/config',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_IFOOD_WIDGET__', $payload);
    }

    /**
     * GET /admin/{slug}/ifood/widget/config
     */
    public function widgetConfig($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        require_once __DIR__ . '/../services/IFood/IFoodWidgetService.php';
        $service = new \App\Services\IFood\IFoodWidgetService($this->db());

        $this->jsonResponse([
            'success' => true,
            'data'    => $service->getConfig($companyId),
        ]);
    }

    /**
     * POST /admin/{slug}/ifood/widget/config
     * Body JSON: { enabled, widget_type, merchant_slug, merchant_url, theme, position, ... }
     */
    public function widgetSaveConfig($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $raw = file_get_contents('php://input');
        $body = json_decode((string) $raw, true) ?: $_POST;
        if (!is_array($body)) {
            $this->jsonResponse(['success' => false, 'message' => 'body inválido']);
            return;
        }

        require_once __DIR__ . '/../services/IFood/IFoodWidgetService.php';
        $service = new \App\Services\IFood\IFoodWidgetService($this->db(), $this->widgetCacheDir());
        $result = $service->saveConfig($companyId, $body);

        if (!$result['ok']) {
            $this->jsonResponse(['success' => false, 'message' => $result['message']]);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'data'    => $service->getConfig($companyId),
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/widget/preview
     * Devolve HTML mínimo com o widget injetado — útil para o admin
     * visualizar antes de embarcar no site público.
     */
    public function widgetPreview($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $scriptUrl = '/api/' . rawurlencode($slug) . '/ifood-widget/ifood.js';
        $html = <<<HTML
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>iFood Widget Preview</title></head>
<body data-store-slug="{$slug}">
  <h1>Preview do widget iFood — {$company['name']}</h1>
  <p>Este HTML inclui o snippet exatamente como o site público faria.</p>
  <div data-ifood-widget-slot style="margin:20px 0;border:1px dashed #ccc;padding:20px">
    Slot para widget_type=embedded
  </div>
  <p>Para tracking: <code>iFoodWidget.track('SEU-ORDER-ID')</code></p>
  <script src="{$scriptUrl}"></script>
</body></html>
HTML;
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    /**
     * Pasta de cache do JS do widget. Cria sob storage/cache.
     */
    private function widgetCacheDir(): string
    {
        $dir = __DIR__ . '/../../storage/cache/ifood_widget';
        return $dir;
    }

    /**
     * GET /admin/{slug}/ifood/observability/health
     * Saúde consolidada: queue stats + api health + latência + top errors.
     */
    public function observabilityHealth($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);

        require_once __DIR__ . '/../services/IFood/DLQObservabilityService.php';
        $service = new \App\Services\IFood\DLQObservabilityService($this->db());

        $this->jsonResponse([
            'success' => true,
            'data'    => $service->health(),
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/observability/dead-jobs?job_type=...&limit=50&offset=0
     */
    public function observabilityDeadJobs($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $jobType = trim((string) ($_GET['job_type'] ?? ''));
        $limit = max(1, min(200, (int) ($_GET['limit'] ?? 50)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $allCompanies = !empty($_GET['all']) && $_GET['all'] === '1';

        require_once __DIR__ . '/../services/IFood/DLQObservabilityService.php';
        $service = new \App\Services\IFood\DLQObservabilityService($this->db());

        $items = $service->deadJobs(
            $allCompanies ? null : $companyId,
            $jobType !== '' ? $jobType : null,
            $limit,
            $offset
        );

        $this->jsonResponse([
            'success' => true,
            'items'   => $items,
            'count'   => count($items),
        ]);
    }

    /**
     * POST /admin/{slug}/ifood/observability/dead-jobs/{id}/retry
     * Body opcional: { "reset_attempts": true }
     */
    public function observabilityRetryDeadJob($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $jobId = (int) ($params['id'] ?? 0);

        $reset = !empty($_POST['reset_attempts']);

        require_once __DIR__ . '/../services/IFood/DLQObservabilityService.php';
        $service = new \App\Services\IFood\DLQObservabilityService($this->db());
        $result = $service->retryDeadJob($jobId, $reset);

        $this->jsonResponse([
            'success' => $result['ok'],
            'id'      => $result['id'],
            'message' => $result['message'],
        ]);
    }

    /**
     * POST /admin/{slug}/ifood/observability/dead-jobs/retry-all
     * Body: { "job_type": "ifood.driver.request", "all_companies": false, "limit": 100 }
     */
    public function observabilityRetryAllDeadJobs($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $raw = file_get_contents('php://input');
        $body = json_decode((string) $raw, true) ?: $_POST;

        $jobType = trim((string) ($body['job_type'] ?? ''));
        if ($jobType === '') {
            $this->jsonResponse(['success' => false, 'message' => 'job_type obrigatório']);
            return;
        }
        $allCompanies = !empty($body['all_companies']);
        $limit = max(1, min(1000, (int) ($body['limit'] ?? 100)));

        require_once __DIR__ . '/../services/IFood/DLQObservabilityService.php';
        $service = new \App\Services\IFood\DLQObservabilityService($this->db());
        $result = $service->retryDeadJobsByType(
            $jobType,
            $allCompanies ? null : $companyId,
            $limit
        );

        $this->jsonResponse([
            'success' => $result['ok'],
            'retried' => $result['retried'],
            'message' => $result['message'],
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/observability/api-health
     */
    public function observabilityApiHealth($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);

        require_once __DIR__ . '/../services/IFood/DLQObservabilityService.php';
        $service = new \App\Services\IFood\DLQObservabilityService($this->db());

        $this->jsonResponse([
            'success' => true,
            'data'    => [
                'api'     => $service->apiHealth(),
                'latency' => $service->latencyStats(),
            ],
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/logistics/dashboard
     * Resposta consolidada: summary + active + metrics_24h + alerts + queue_health.
     */
    public function logisticsDashboard($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        require_once __DIR__ . '/../services/IFood/LogisticsDashboardService.php';
        $service = new \App\Services\IFood\LogisticsDashboardService($this->db());

        $this->jsonResponse([
            'success' => true,
            'data'    => $service->dashboard($companyId),
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/logistics/summary
     */
    public function logisticsSummary($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        require_once __DIR__ . '/../services/IFood/LogisticsDashboardService.php';
        $service = new \App\Services\IFood\LogisticsDashboardService($this->db());

        $this->jsonResponse([
            'success' => true,
            'data'    => $service->summary($companyId),
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/logistics/active?limit=30
     */
    public function logisticsActive($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];
        $limit = max(1, min(200, (int) ($_GET['limit'] ?? 30)));

        require_once __DIR__ . '/../services/IFood/LogisticsDashboardService.php';
        $service = new \App\Services\IFood\LogisticsDashboardService($this->db());

        $this->jsonResponse([
            'success' => true,
            'data'    => $service->activeOrders($companyId, $limit),
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/logistics/metrics
     */
    public function logisticsMetrics($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        require_once __DIR__ . '/../services/IFood/LogisticsDashboardService.php';
        $service = new \App\Services\IFood\LogisticsDashboardService($this->db());

        $this->jsonResponse([
            'success' => true,
            'data'    => $service->metrics24h($companyId),
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/logistics/alerts
     */
    public function logisticsAlerts($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        require_once __DIR__ . '/../services/IFood/LogisticsDashboardService.php';
        $service = new \App\Services\IFood\LogisticsDashboardService($this->db());

        $this->jsonResponse([
            'success' => true,
            'data'    => $service->alerts($companyId),
        ]);
    }

    /**
     * GET /admin/{slug}/ifood/stock/state
     * Retorna o snapshot de sync de cada produto (para dashboard / debug).
     */
    public function stockState($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $companyId = (int) $company['id'];

        $env = $_GET['env'] ?? null;
        $sql = 'SELECT s.product_id, s.environment, s.ifood_product_id,
                       s.desired_status, s.last_synced_status, s.last_synced_at,
                       s.last_error, s.consecutive_failures,
                       p.name AS product_name, p.active AS product_active
                  FROM ifood_stock_sync_state s
             LEFT JOIN products p ON p.id = s.product_id
                 WHERE s.company_id = :cid';
        $bind = [':cid' => $companyId];
        if ($env !== null && $env !== '') {
            $sql .= ' AND s.environment = :env';
            $bind[':env'] = $env === 'sandbox' ? 'sandbox' : 'production';
        }
        $sql .= ' ORDER BY s.last_synced_at DESC, s.product_id ASC LIMIT 500';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($bind);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $this->jsonResponse([
            'success' => true,
            'items'   => $rows,
            'count'   => count($rows),
        ]);
    }

    /**
     * Normaliza a resposta do endpoint /merchant/v1.0/merchants/{id}/status.
     * O iFood retorna um array de objetos de canal, cada um com `available` e
     * `message.title`. Suporta também o formato de objeto plano como fallback.
     *
     * @param array<mixed> $raw
     * @return array{status: string, is_open: bool}
     */
    private static function parseMerchantStatus(array $raw): array
    {
        $isOpen = false;
        $statusTitle = '';

        // Formato real da API: array de canais [{ available: bool, message: { title: string } }]
        if (isset($raw[0]) && is_array($raw[0])) {
            foreach ($raw as $channel) {
                if (!empty($channel['available'])) {
                    $isOpen = true;
                    $statusTitle = (string)($channel['message']['title'] ?? 'Online');
                    break;
                }
            }
            if (!$isOpen && !empty($raw[0]['message']['title'])) {
                $statusTitle = (string)$raw[0]['message']['title'];
            }
        } else {
            // Fallback: objeto plano legado
            $isOpen = (bool)($raw['available'] ?? $raw['is_open'] ?? false);
            $statusTitle = (string)($raw['message']['title'] ?? $raw['status'] ?? '');
        }

        return ['status' => $statusTitle, 'is_open' => $isOpen];
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
