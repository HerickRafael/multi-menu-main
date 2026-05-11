<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Middleware específico
require_once __DIR__ . '/../middleware/ApiSecurity.php';

use App\Middleware\ApiSecurity;

class ApiController extends Controller
{
    private ApiSecurity $apiSecurity;
    private array $authData;
    private ?array $company = null;

    public function __construct()
    {
        // Configura o middleware de segurança da API
        if (!class_exists('SecurityRequirements', false)) {
            require_once __DIR__ . '/../config/SecurityRequirements.php';
        }

        $this->apiSecurity = new ApiSecurity([
            'auth_methods' => ['bearer', 'api_key'],
            'require_auth' => true,
            'jwt_secret' => SecurityRequirements::resolveJwtSecret(),
            'cors_enabled' => true,
            'rate_limit_enabled' => true,
            'rate_limit_requests' => 1000, // 1000 requests per minute for API
            'rate_limit_window' => 60,
            'log_requests' => true,
        ], db());

        try {
            // Autentica a requisição
            $result = $this->apiSecurity->handle();
            $this->authData = $result['auth_data'];
            
            // Responde headers CORS se for preflight
            if ($result['preflight'] ?? false) {
                $this->sendResponse(['message' => 'CORS preflight OK'], 200);
                exit;
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            $statusCode = is_int($code) && $code > 0 ? $code : 401;
            $this->sendError($e->getMessage(), $statusCode);
            exit;
        }
    }

    /**
     * Valida e carrega a empresa pelo slug
     */
    private function loadCompany(string $slug): void
    {
        $this->company = Company::findBySlug($slug);
        
        if (!$this->company || !$this->company['active']) {
            $this->sendError('Empresa não encontrada ou inativa', 404);
            exit;
        }
    }

    /**
     * Retorna informações da empresa
     */
    public function getCompany($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        // Remove dados sensíveis
        $companyData = $this->company;
        unset($companyData['created_at'], $companyData['updated_at']);

        $this->sendResponse([
            'company' => $companyData,
            'authenticated_user' => $this->authData['user_id'] ?? null
        ]);
    }

    /**
     * Lista todas as categorias da empresa
     */
    public function getCategories($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        $categories = Category::listByCompany($this->company['id']);

        $this->sendResponse([
            'categories' => $categories,
            'total' => count($categories)
        ]);
    }

    /**
     * Lista todos os produtos da empresa ou de uma categoria específica
     */
    public function getProducts($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        $categoryId = $_GET['category_id'] ?? null;
        $search = $_GET['search'] ?? null;
        $active = $_GET['active'] ?? '1';

        if ($categoryId) {
            $products = Product::listByCategory($this->company['id'], (int)$categoryId, $search);
        } else {
            $products = Product::listByCompany($this->company['id'], $search, $active === '1');
        }

        $this->sendResponse([
            'products' => $products,
            'total' => count($products),
            'filters' => [
                'category_id' => $categoryId,
                'search' => $search,
                'active' => $active === '1'
            ]
        ]);
    }

    /**
     * Lista produtos simples para busca autocomplete
     */
    public function getSimpleProducts($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        $search = $_GET['search'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 20), 50); // Max 50 por request

        // Busca produtos simples ativos
        $db = db();
        $query = "
            SELECT p.id, p.name, p.sku, p.price, p.allow_customize,
                   COUNT(pi.ingredient_id) as ingredient_count
            FROM products p
            LEFT JOIN product_ingredients pi ON p.id = pi.product_id
            JOIN categories c ON p.category_id = c.id
            WHERE c.company_id = ? 
            AND p.type = 'simple' 
            AND p.active = 1
        ";
        
        $params = [$this->company['id']];
        
        // Filtro de busca por nome, SKU ou ID
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $query .= " AND (
                p.name LIKE ? OR 
                p.sku LIKE ? OR 
                CAST(p.id AS CHAR) LIKE ?
            )";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " 
            GROUP BY p.id, p.name, p.sku, p.price, p.allow_customize
            ORDER BY p.name ASC 
            LIMIT ?
        ";
        $params[] = $limit;

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatar preços para exibição
        foreach ($products as &$product) {
            $product['price_formatted'] = 'R$ ' . number_format((float)$product['price'], 2, ',', '.');
            $product['ingredient_count'] = (int)$product['ingredient_count'];
            $product['allow_customize'] = (bool)$product['allow_customize'];
        }

        $this->sendResponse([
            'products' => $products,
            'total' => count($products),
            'search' => $search
        ]);
    }

    /**
     * Retorna detalhes de um produto específico
     */
    public function getProduct($params): void
    {
        $slug = $params['slug'] ?? null;
        $productId = $params['id'] ?? null;

        if (!$slug || !$productId) {
            $this->sendError('Slug da empresa e ID do produto são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);

        $product = Product::find((int)$productId);
        
        if (!$product) {
            $this->sendError('Produto não encontrado', 404);
            return;
        }

        // Verifica se o produto pertence à empresa
        $category = Category::find($product['category_id']);
        if (!$category || $category['company_id'] != $this->company['id']) {
            $this->sendError('Produto não pertence a esta empresa', 403);
            return;
        }

        $this->sendResponse([
            'product' => $product,
            'category' => $category
        ]);
    }

    /**
     * Lista pedidos da empresa
     */
    public function getOrders($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        // Parâmetros de filtro
        $status = $_GET['status'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100 por request
        $offset = max((int)($_GET['offset'] ?? 0), 0);

        $orders = Order::listByCompany(
            db(),
            $this->company['id'],
            $status,
            $limit,
            $offset
        );

        $total = Order::countByCompany($this->company['id']);

        $this->sendResponse([
            'orders' => $orders,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'filters' => [
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ]);
    }

    /**
     * Retorna detalhes de um pedido específico
     */
    public function getOrder($params): void
    {
        $slug = $params['slug'] ?? null;
        $orderId = $params['id'] ?? null;

        if (!$slug || !$orderId) {
            $this->sendError('Slug da empresa e ID do pedido são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);

        $order = Order::findBasic(db(), (int)$orderId, $this->company['id']);
        
        if (!$order) {
            $this->sendError('Pedido não encontrado', 404);
            return;
        }

        // Carrega pedido com itens
        $orderWithItems = Order::findWithItems(db(), (int)$orderId, $this->company['id']);

        $this->sendResponse([
            'order' => $orderWithItems['order'] ?? $order,
            'items' => $orderWithItems['items'] ?? []
        ]);
    }

    /**
     * Cria um novo pedido via API
     */
    public function createOrder($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        // Recebe dados JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->sendError('Dados JSON inválidos', 400);
            return;
        }

        // Validações básicas
        $required = ['customer_name', 'customer_phone', 'items'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Campo obrigatório: {$field}", 400);
                return;
            }
        }

        if (empty($input['items']) || !is_array($input['items'])) {
            $this->sendError('Items do pedido são obrigatórios', 400);
            return;
        }

        $rawAddress = trim((string)($input['delivery_address'] ?? $input['customer_address'] ?? $input['address'] ?? ''));
        if ($rawAddress === '') {
            $this->sendError('Endereço de entrega é obrigatório', 400);
            return;
        }

        try {
            // Calcula totais dos itens
            $subtotal = 0;
            foreach ($input['items'] as $item) {
                $qty = (int)($item['quantity'] ?? 1);
                $price = (float)($item['unit_price'] ?? 0);
                $subtotal += $qty * $price;
            }

            // Cria o pedido
            $orderData = [
                'company_id' => $this->company['id'],
                'customer_name' => $input['customer_name'],
                'customer_phone' => normalizePhone($input['customer_phone']),
                'subtotal' => $subtotal,
                'delivery_fee' => (float)($input['delivery_fee'] ?? 0),
                'discount' => (float)($input['discount'] ?? 0),
                'total' => $subtotal + (float)($input['delivery_fee'] ?? 0) - (float)($input['discount'] ?? 0),
                'status' => 'pending',
                'notes' => $input['notes'] ?? null,
                'customer_address' => $rawAddress
            ];

            $orderId = Order::create(db(), $orderData);

            // Adiciona os itens
            foreach ($input['items'] as $item) {
                Order::addItem(db(), $orderId, [
                    'product_id' => (int)$item['product_id'],
                    'quantity' => (int)($item['quantity'] ?? 1),
                    'unit_price' => (float)($item['unit_price'] ?? 0),
                    'line_total' => (int)($item['quantity'] ?? 1) * (float)($item['unit_price'] ?? 0),
                    'combo_data' => $item['combo_data'] ?? null,
                    'customization_data' => $item['customization_data'] ?? null,
                    'notes' => $item['notes'] ?? null
                ]);
            }

            $orderWithItems = Order::findWithItems(db(), $orderId, $this->company['id']);

            // Enviar Web Push Notification para os dispositivos cadastrados
            try {
                require_once __DIR__ . '/../services/WebPushService.php';
                $webPushService = new \App\Services\WebPushService();
                $webPushService->notifyNewOrder($this->company['id'], [
                    'id' => $orderId,
                    'customer_name' => $orderData['customer_name'],
                    'customer_phone' => $orderData['customer_phone'],
                    'total' => $orderData['total'],
                ]);
            } catch (\Throwable $e) {
                error_log("Erro ao enviar Web Push: " . $e->getMessage());
            }

            $this->sendResponse([
                'message' => 'Pedido criado com sucesso',
                'order' => $orderWithItems
            ], 201);

        } catch (Exception $e) {
            $this->sendError('Erro ao criar pedido: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualiza status de um pedido
     */
    public function updateOrderStatus($params): void
    {
        $slug = $params['slug'] ?? null;
        $orderId = $params['id'] ?? null;

        if (!$slug || !$orderId) {
            $this->sendError('Slug da empresa e ID do pedido são obrigatórios', 400);
            return;
        }

        $this->loadCompany($slug);

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['status'])) {
            $this->sendError('Status é obrigatório', 400);
            return;
        }

        $order = Order::findBasic(db(), (int)$orderId, $this->company['id']);
        
        if (!$order) {
            $this->sendError('Pedido não encontrado', 404);
            return;
        }

        try {
            $success = Order::updateStatus(db(), (int)$orderId, $this->company['id'], $input['status']);

            if (!$success) {
                $this->sendError('Status inválido ou erro ao atualizar', 400);
                return;
            }

            $updatedOrder = Order::findBasic(db(), (int)$orderId, $this->company['id']);

            $this->sendResponse([
                'message' => 'Status do pedido atualizado com sucesso',
                'order' => $updatedOrder
            ]);

        } catch (Exception $e) {
            $this->sendError('Erro ao atualizar status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Gera um novo token JWT para o usuário autenticado
     */
    public function generateToken($params): void
    {
        if (!isset($this->authData['user_id'])) {
            $this->sendError('ID do usuário não encontrado nos dados de autenticação', 400);
            return;
        }

        $payload = [
            'sub' => $this->authData['user_id'],
            'scopes' => $this->authData['scopes'] ?? ['read', 'write'],
            'company_access' => $params['slug'] ?? null
        ];

        try {
            $token = $this->apiSecurity->generateJwt($payload);

            $this->sendResponse([
                'token' => $token,
                'expires_in' => 3600, // 1 hora
                'token_type' => 'Bearer'
            ]);

        } catch (Exception $e) {
            $this->sendError('Erro ao gerar token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Retorna estatísticas da empresa
     */
    public function getStats($params): void
    {
        $slug = $params['slug'] ?? null;
        if (!$slug) {
            $this->sendError('Slug da empresa é obrigatório', 400);
            return;
        }

        $this->loadCompany($slug);

        try {
            // Contagem básica de pedidos
            $totalOrders = Order::countByCompany($this->company['id']);
            
            // Contagem de produtos e categorias via SQL direto
            $db = db();
            
            $stmt = $db->prepare('SELECT COUNT(*) FROM products WHERE company_id = ? AND active = 1');
            $stmt->execute([$this->company['id']]);
            $totalProducts = (int)$stmt->fetchColumn();
            
            $stmt = $db->prepare('SELECT COUNT(*) FROM categories WHERE company_id = ? AND active = 1');
            $stmt->execute([$this->company['id']]);
            $totalCategories = (int)$stmt->fetchColumn();
            
            // Pedidos recentes
            $recentOrders = Order::listRecentByCompany($this->company['id'], 5);
            
            $stats = [
                'total_orders' => $totalOrders,
                'total_products' => $totalProducts,
                'total_categories' => $totalCategories,
                'recent_orders' => count($recentOrders),
                'company_info' => [
                    'name' => $this->company['name'],
                    'slug' => $this->company['slug'],
                    'active' => (bool)$this->company['active']
                ]
            ];

            $this->sendResponse([
                'stats' => $stats,
                'generated_at' => date('c')
            ]);

        } catch (Exception $e) {
            $this->sendError('Erro ao gerar estatísticas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/{slug}/track-interaction
     * Registra interação do cliente com produto (machine learning)
     */
    public function trackInteraction($params): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendError('Método não permitido. Use POST.', 405);
                return;
            }

            $this->loadCompany($params['slug'] ?? '');

            $data = json_decode(file_get_contents('php://input'), true);
            
            $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
            $eventType = $data['event_type'] ?? '';
            $customerId = isset($data['customer_id']) ? (int)$data['customer_id'] : null;
            $sessionId = $data['session_id'] ?? null;

            if ($productId <= 0) {
                $this->sendError('product_id é obrigatório', 400);
                return;
            }

            if (!in_array($eventType, ['view', 'add_to_cart', 'purchase'], true)) {
                $this->sendError('event_type inválido. Use: view, add_to_cart ou purchase', 400);
                return;
            }

            require_once __DIR__ . '/../services/RecommendationEngine.php';
            $engine = new RecommendationEngine($this->db());

            $success = $engine->trackInteraction(
                (int)$this->company['id'],
                $productId,
                $eventType,
                $customerId,
                $sessionId
            );

            if ($success) {
                $this->sendResponse([
                    'message' => 'Interação registrada com sucesso',
                    'product_id' => $productId,
                    'event_type' => $eventType
                ]);
            } else {
                $this->sendError('Erro ao registrar interação', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Erro ao registrar interação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Envia resposta JSON de sucesso
     */
    private function sendResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Envia resposta JSON de erro
     */
    private function sendError(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status
            ],
            'timestamp' => date('c')
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}