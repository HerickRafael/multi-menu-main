<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../services/IFoodService.php';

/**
 * MobileAdminIFoodController
 * 
 * Integração iFood otimizada para mobile.
 * Reutiliza IFoodService do desktop.
 */
class MobileAdminIFoodController extends Controller
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
     * GET /ifood/config — Configuração iFood
     */
    public function config(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $db = db();

        $ifoodService = new IFoodService($db, (int)$company['id']);
        $config = $ifoodService->getConfig();
        $merchants = [];
        $merchantStatus = null;

        if ($config && !empty($config['access_token'])) {
            try {
                $merchants = $ifoodService->getMerchants();
                $activeMerchantId = $ifoodService->getActiveMerchantId();
                if ($activeMerchantId !== '') {
                    $merchantStatus = $ifoodService->getMerchantStatus($activeMerchantId);
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }

        $this->view('admin/mobile/ifood/config', [
            'pageTitle' => 'iFood',
            'activeNav' => 'settings',
            'config' => $config,
            'merchants' => $merchants,
            'merchantStatus' => $merchantStatus,
            'company' => $company,
            'user' => $u,
        ]);
    }

    /**
     * POST /ifood/config/save — Salvar configuração
     */
    public function saveConfig(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $db = db();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ifood/config');
            exit;
        }

        $ifoodService = new IFoodService($db, (int)$company['id']);

        $data = [
            'client_id' => trim($_POST['client_id'] ?? ''),
            'client_secret' => trim($_POST['client_secret'] ?? ''),
            'merchant_id' => trim($_POST['merchant_id'] ?? ''),
            'is_active' => isset($_POST['is_active']),
            'auto_confirm' => isset($_POST['auto_confirm']),
        ];

        if ($ifoodService->saveConfig($data)) {
            $_SESSION['flash_success'] = 'Configuração salva com sucesso!';
        } else {
            $_SESSION['flash_error'] = 'Erro ao salvar configuração.';
        }

        header('Location: /ifood/config');
        exit;
    }

    /**
     * GET /ifood/orders — Listar pedidos iFood
     */
    public function orders(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $db = db();

        $ifoodService = new IFoodService($db, (int)$company['id']);
        $status = $_GET['status'] ?? null;
        $orders = $ifoodService->getOrders($status);

        $this->view('admin/mobile/ifood/orders', [
            'pageTitle' => 'Pedidos iFood',
            'activeNav' => 'orders',
            'orders' => $orders,
            'currentStatus' => $status,
            'company' => $company,
            'user' => $u,
        ]);
    }

    /**
     * GET /ifood/orders/{id} — Detalhe do pedido
     */
    public function viewOrder(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $db = db();

        $ifoodService = new IFoodService($db, (int)$company['id']);
        $order = $ifoodService->getOrder($id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pedido não encontrado.';
            header('Location: /ifood/orders');
            exit;
        }

        $order['items'] = json_decode($order['items'], true) ?? [];
        $order['payments'] = json_decode($order['payments'], true) ?? [];
        $order['delivery_address'] = json_decode($order['delivery_address'], true) ?? [];
        $order['benefits'] = json_decode($order['benefits'], true) ?? [];
        $order['raw_data'] = json_decode($order['raw_data'], true) ?? [];

        $this->view('admin/mobile/ifood/order-detail', [
            'pageTitle' => 'Pedido #' . ($order['ifood_display_id'] ?? substr($order['ifood_order_id'], 0, 8)),
            'activeNav' => 'orders',
            'order' => $order,
            'company' => $company,
            'user' => $u,
        ]);
    }

    /**
     * POST /ifood/orders/{id}/confirm
     */
    public function confirmOrder(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $db = db();

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
     * POST /ifood/orders/{id}/ready
     */
    public function readyOrder(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $db = db();

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
     * POST /ifood/orders/{id}/dispatch
     */
    public function dispatchOrder(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $db = db();

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
     * POST /ifood/orders/{id}/cancel
     */
    public function cancelOrder(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $db = db();

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
     * GET /ifood/orders/{id}/cancel-reasons
     */
    public function getCancellationReasons(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $db = db();

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
     * POST /ifood/poll — Buscar novos pedidos
     */
    public function poll(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $db = db();

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
     * POST /ifood/test-connection
     */
    public function testConnection(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $db = db();

        $ifoodService = new IFoodService($db, (int)$company['id']);

        $config = $ifoodService->getConfig();
        if (!$config || empty($config['client_id']) || empty($config['client_secret'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Configure o Client ID e Client Secret antes de testar.'
            ]);
            return;
        }

        $result = $ifoodService->testAuthentication();

        if (!$result['success']) {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error'] ?? 'Credenciais inválidas.'
            ]);
            return;
        }

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
     * POST /ifood/clear-error
     */
    public function clearError(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $db = db();
        
        $stmt = $db->prepare("UPDATE ifood_integrations SET last_error = NULL WHERE company_id = :cid");
        $stmt->execute(['cid' => (int)$company['id']]);
        
        $this->jsonResponse(['success' => true]);
    }

    /**
     * GET /ifood/status
     */
    public function status(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $db = db();

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
            $isOpen = (bool)($raw['available'] ?? $raw['is_open'] ?? false);
            $statusTitle = (string)($raw['message']['title'] ?? $raw['status'] ?? '');
        }

        return ['status' => $statusTitle, 'is_open' => $isOpen];
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
