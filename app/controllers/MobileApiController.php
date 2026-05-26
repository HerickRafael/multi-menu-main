<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/MobileAdminGuard.php';
require_once __DIR__ . '/../modules/orders/OrderStatusService.php';

/**
 * MobileApiController
 * 
 * API endpoints para o app mobile (AJAX).
 */
class MobileApiController extends Controller
{
    private function guard(): array
    {
        return MobileAdminGuard::requireCompanyAccess();
    }

    /**
     * GET /api/stats
     * Retorna estatísticas para atualização em tempo real
     */
    public function getStats(array $params = [])
    {
        [$u, $company] = $this->guard();

        $companyId = (int)$company['id'];
        $db = db();

        $stats = [
            'today_orders' => Order::countTodayByCompany($db, $companyId),
            'pending_orders' => Order::countPendingByCompany($db, $companyId),
            'today_revenue' => Order::sumTodayByCompany($db, $companyId),
            'active_products' => Product::countActiveByCompany($companyId),
        ];

        $this->jsonSuccess($stats);
    }

    /**
     * GET /api/orders
     * Lista pedidos para o app
     */
    public function getOrders(array $params = [])
    {
        [$u, $company] = $this->guard();

        $companyId = (int)$company['id'];
        $status = $_GET['status'] ?? null;
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $db = db();
        
        if ($status && $status !== 'all') {
            $orders = Order::listByCompanyAndStatus($companyId, $status, $limit, $offset);
        } else {
            $orders = Order::listByCompany($db, $companyId, null, $limit, $offset);
        }

        // Formata para API
        $formatted = array_map(function($order) {
            return [
                'id' => (int)$order['id'],
                'customer_name' => $order['customer_name'] ?? 'Cliente',
                'customer_phone' => $order['customer_phone'] ?? null,
                'status' => $order['status'],
                'total' => (float)$order['total'],
                'item_count' => (int)($order['items_count'] ?? $order['item_count'] ?? 0),
                'delivery_type' => $order['delivery_type'] ?? 'delivery',
                'created_at' => $order['created_at'],
            ];
        }, $orders);

        $this->jsonSuccess([
            'orders' => $formatted,
            'count' => count($formatted),
        ]);
    }

    /**
     * GET /api/orders/{id}
     * Detalhes de um pedido
     */
    public function getOrder(array $params = [])
    {
        [$u, $company] = $this->guard();

        $orderId = (int)($params['id'] ?? 0);
        
        if (!$orderId) {
            $this->jsonError('ID do pedido inválido', 400);
        }

        $db = db();
        $order = Order::findWithItems($db, $orderId, (int)$company['id']);

        if (!$order) {
            $this->jsonError('Pedido não encontrado', 404);
        }

        $this->jsonSuccess(['order' => $order]);
    }

    /**
     * POST /api/orders/{id}/status
     * Atualiza status de um pedido
     */
    public function updateOrderStatus(array $params = [])
    {
        [$u, $company] = $this->guard();

        $orderId = (int)($params['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $status = $input['status'] ?? '';

        if (!$orderId) {
            $this->jsonError('ID do pedido inválido', 400);
        }

        $db = db();
        $result = OrderStatusService::updateForCompany($db, (int)$company['id'], $orderId, $status);

        if (!empty($result['ok'])) {
            $this->jsonSuccess([
                'status' => $result['requested_status'] ?? $status,
                'internal_status' => $result['internal_status'] ?? $status,
            ]);
        }

        $error = $result['error'] ?? 'Falha ao atualizar status';
        $statusCode = 500;
        if ($error === 'ID do pedido inválido' || $error === 'Status inválido') {
            $statusCode = 400;
        } elseif ($error === 'Pedido não encontrado') {
            $statusCode = 404;
        }

        $this->jsonError($error, $statusCode);
    }

    /**
     * GET /api/customers/search
     * Busca cliente por telefone
     */
    public function searchCustomerByPhone(array $params = [])
    {
        [$u, $company] = $this->guard();

        $phone = trim($_GET['phone'] ?? '');
        
        if (empty($phone)) {
            $this->jsonSuccess(['found' => false, 'customer' => null]);
            return;
        }

        // Limpar telefone - apenas números
        $phoneClean = normalizePhone($phone);
        
        if (strlen($phoneClean) < 10) {
            $this->jsonSuccess(['found' => false, 'customer' => null]);
            return;
        }

        $companyId = (int)$company['id'];
        $db = db();

        // Pegar os últimos 8 dígitos para busca mais flexível
        // Isso ignora diferenças de código de país (55) e variações de DDD
        $last8 = substr($phoneClean, -8);
        $phoneLike = '%' . $last8;
        
        // Buscar por whatsapp (formato original ou E164)
        $sql = "SELECT id, name, whatsapp, whatsapp_e164, email 
                FROM customers 
                WHERE company_id = :cid 
                  AND (
                      whatsapp_e164 LIKE :phone1
                      OR REPLACE(REPLACE(REPLACE(REPLACE(whatsapp, '(', ''), ')', ''), '-', ''), ' ', '') LIKE :phone2
                  )
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':cid' => $companyId,
            ':phone1' => $phoneLike,
            ':phone2' => $phoneLike,
        ]);
        
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // Buscar endereço padrão do cliente na tabela customer_addresses
            $sqlAddr = "SELECT ca.street, ca.number, ca.neighborhood, ca.complement, ca.reference, 
                               ca.city, ca.city_id, ca.zone_id,
                               COALESCE(dz.fee, 0) as delivery_fee
                        FROM customer_addresses ca
                        LEFT JOIN delivery_zones dz ON dz.id = ca.zone_id
                        WHERE ca.customer_id = :cust_id 
                          AND ca.company_id = :cid
                        ORDER BY ca.is_default DESC, ca.updated_at DESC 
                        LIMIT 1";
            $stmtAddr = $db->prepare($sqlAddr);
            $stmtAddr->execute([
                ':cust_id' => $customer['id'],
                ':cid' => $companyId,
            ]);
            $address = $stmtAddr->fetch(PDO::FETCH_ASSOC);
            
            $this->jsonSuccess([
                'found' => true,
                'customer' => [
                    'id' => (int)$customer['id'],
                    'name' => $customer['name'],
                    'phone' => $customer['whatsapp'] ?? $customer['whatsapp_e164'],
                    'email' => $customer['email'] ?? null,
                    'address' => $address ? [
                        'street' => $address['street'] ?? '',
                        'number' => $address['number'] ?? '',
                        'neighborhood' => $address['neighborhood'] ?? '',
                        'complement' => $address['complement'] ?? '',
                        'reference' => $address['reference'] ?? '',
                        'city' => $address['city'] ?? '',
                        'city_id' => (int)($address['city_id'] ?? 0),
                        'zone_id' => (int)($address['zone_id'] ?? 0),
                        'delivery_fee' => (float)($address['delivery_fee'] ?? 0),
                    ] : null,
                ]
            ]);
        } else {
            $this->jsonSuccess(['found' => false, 'customer' => null]);
        }
    }

    /**
     * Resposta JSON de sucesso
     */
    private function jsonSuccess($data)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Resposta JSON de erro
     */
    private function jsonError(string $message, int $code = 400)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========== STREET AUTOCOMPLETE ==========

    /**
     * GET /api/street-autocomplete?q=...&city=...&neighborhood=...
     */
    public function streetSearch(array $params = []): void
    {
        [$u, $company] = $this->guard();
        header('Content-Type: application/json; charset=utf-8');

        $query = trim($_GET['q'] ?? '');
        $city = trim($_GET['city'] ?? '');
        $neighborhood = trim($_GET['neighborhood'] ?? '');

        if ($query === '' || mb_strlen($query) < 2 || $city === '') {
            echo json_encode(['results' => []]);
            return;
        }

        require_once __DIR__ . '/../services/AddressAutocompleteService.php';
        $companyId = (int)$company['id'];
        $service = new \AddressAutocompleteService(db(), $companyId);
        $result = $service->search($query, $city, $neighborhood);

        $output = [];
        foreach ($result['results'] as $item) {
            $entry = [
                'street' => $item['street'],
                'display' => $item['street'],
                'validated' => true,
            ];
            if (isset($item['id']) && $item['id'] > 0) {
                $entry['id'] = $item['id'];
            }
            if (!empty($item['neighborhood'])) {
                $entry['neighborhood'] = $item['neighborhood'];
            }
            $output[] = $entry;
        }

        echo json_encode([
            'results' => $output,
            'source' => $result['source'] ?? 'unknown',
            'timing_ms' => $result['timing_ms'] ?? 0,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST /api/street-autocomplete/popularity
     */
    public function streetPopularity(array $params = []): void
    {
        [$u, $company] = $this->guard();
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true);
        $streetId = (int)($input['street_id'] ?? 0);

        if ($streetId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_street_id']);
            return;
        }

        require_once __DIR__ . '/../services/AddressAutocompleteService.php';
        $service = new \AddressAutocompleteService(db(), (int)$company['id']);
        $service->incrementPopularity($streetId);

        echo json_encode(['ok' => true]);
    }

    /**
     * POST /api/street-autocomplete/learn
     */
    public function streetLearn(array $params = []): void
    {
        [$u, $company] = $this->guard();
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true);
        $city = trim($input['city'] ?? '');
        $neighborhood = trim($input['neighborhood'] ?? '');
        $street = trim($input['street'] ?? '');

        if ($city === '' || $street === '') {
            http_response_code(400);
            echo json_encode(['error' => 'missing_fields']);
            return;
        }

        $city = mb_substr($city, 0, 120);
        $neighborhood = mb_substr($neighborhood, 0, 120);
        $street = mb_substr($street, 0, 255);

        require_once __DIR__ . '/../services/AddressAutocompleteService.php';
        $service = new \AddressAutocompleteService(db(), (int)$company['id']);
        $service->learnStreet($city, $neighborhood, $street);

        echo json_encode(['ok' => true]);
    }
}
