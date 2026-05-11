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
        
        // Try to get merchants if configured
        if ($config && !empty($config['access_token'])) {
            try {
                $merchants = $ifoodService->getMerchants();
                
                if (!empty($config['merchant_id'])) {
                    $merchantStatus = $ifoodService->getMerchantStatus($config['merchant_id']);
                }
            } catch (\Exception $e) {
                // Ignore - will show as disconnected
            }
        }
        
        $this->view('admin/ifood/config', [
            'pageTitle' => 'Integração iFood',
            'config' => $config,
            'merchants' => $merchants,
            'merchantStatus' => $merchantStatus,
            'company' => $company,
            'activeSlug' => $company['slug'],
        ]);
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
     * List iFood orders
     */
    public function orders($params): void
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        
        $ifoodService = new IFoodService($db, (int)$company['id']);
        $status = $_GET['status'] ?? null;
        $orders = $ifoodService->getOrders($status);
        
        $this->view('admin/ifood/orders', [
            'pageTitle' => 'Pedidos iFood',
            'orders' => $orders,
            'currentStatus' => $status,
            'company' => $company,
            'activeSlug' => $company['slug'],
        ]);
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
        
        // Parse JSON fields
        $order['items'] = json_decode($order['items'], true) ?? [];
        $order['payments'] = json_decode($order['payments'], true) ?? [];
        $order['delivery_address'] = json_decode($order['delivery_address'], true) ?? [];
        $order['benefits'] = json_decode($order['benefits'], true) ?? [];
        $order['raw_data'] = json_decode($order['raw_data'], true) ?? [];
        
        $this->view('admin/ifood/order-detail', [
            'pageTitle' => 'Pedido iFood #' . $order['ifood_display_id'],
            'order' => $order,
            'company' => $company,
            'activeSlug' => $company['slug'],
        ]);
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
        
        $result = $ifoodService->confirmOrder($order['ifood_order_id']);
        $this->jsonResponse($result);
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
        
        $result = $ifoodService->markAsReady($order['ifood_order_id']);
        $this->jsonResponse($result);
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
        
        $result = $ifoodService->dispatchOrder($order['ifood_order_id']);
        $this->jsonResponse($result);
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
        $events = $ifoodService->pollEvents();
        $this->jsonResponse(['success' => true, 'events' => count($events), 'data' => $events]);
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
                $merchantStatus = $ifoodService->getMerchantStatus($config['merchant_id']);
                $status['merchant_status'] = $merchantStatus;
            } catch (\Exception $e) {
                $status['merchant_status'] = null;
                $status['error'] = $e->getMessage();
            }
        }
        
        $this->jsonResponse($status);
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
