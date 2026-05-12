<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/WebPushService.php';
require_once __DIR__ . '/OrderNotificationService.php';

/**
 * iFood Integration Service
 * 
 * Handles all communication with iFood API
 * - Authentication (OAuth 2.0)
 * - Order polling and webhooks
 * - Order actions (confirm, dispatch, etc.)
 * - Catalog sync
 * 
 * @package App\Services
 * @version 1.0.0
 * @see https://developer.ifood.com.br/pt-BR/docs/references
 */
class IFoodService
{
    private const API_BASE_URL = 'https://merchant-api.ifood.com.br';
    private const AUTH_URL = 'https://merchant-api.ifood.com.br/authentication/v1.0';
    
    private PDO $db;
    private int $companyId;
    private ?array $config = null;
    
    /**
     * Constructor
     */
    public function __construct(PDO $db, int $companyId)
    {
        $this->db = $db;
        $this->companyId = $companyId;
    }
    
    /**
     * Get or refresh access token
     */
    public function getAccessToken(): ?string
    {
        $config = $this->getConfig();
        
        if (!$config || empty($config['client_id']) || empty($config['client_secret'])) {
            return null;
        }
        
        // Check if token is still valid (with 5 min buffer)
        if (!empty($config['access_token']) && !empty($config['token_expires_at'])) {
            $expiresAt = strtotime($config['token_expires_at']);
            if ($expiresAt > time() + 300) {
                return $this->decrypt($config['access_token']);
            }
        }
        
        // Refresh token
        return $this->refreshToken();
    }
    
    /**
     * Refresh access token using client credentials
     */
    private function refreshToken(): ?string
    {
        $config = $this->getConfig();
        
        $response = $this->httpRequest('POST', self::AUTH_URL . '/oauth/token', [
            'grantType' => 'client_credentials',
            'clientId' => $config['client_id'],
            'clientSecret' => $this->decrypt($config['client_secret']),
        ], false, true);
        
        if (!$response || !isset($response['accessToken'])) {
            $this->logError('Failed to refresh token: ' . json_encode($response));
            return null;
        }
        
        // Save new token
        $expiresAt = date('Y-m-d H:i:s', time() + ($response['expiresIn'] ?? 3600));
        
        $stmt = $this->db->prepare("
            UPDATE ifood_integrations 
            SET access_token = :token, 
                token_expires_at = :expires,
                last_error = NULL,
                updated_at = NOW()
            WHERE company_id = :company_id
        ");
        
        $stmt->execute([
            'token' => $this->encrypt($response['accessToken']),
            'expires' => $expiresAt,
            'company_id' => $this->companyId,
        ]);
        
        // Clear cached config
        $this->config = null;
        
        return $response['accessToken'];
    }
    
    /**
     * Poll for new events
     */
    public function pollEvents(): array
    {
        $config = $this->getConfig();
        
        if (!$config || !$config['is_active']) {
            return ['success' => false, 'error' => 'Integration not active'];
        }
        
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Could not get access token'];
        }
        
        // Get events with x-polling-merchants header
        $merchantId = $config['merchant_id'] ?? '';
        $extraHeaders = $merchantId ? ['x-polling-merchants: ' . $merchantId] : [];
        $response = $this->httpRequest('GET', self::API_BASE_URL . '/order/v1.0/events:polling', [], true, false, $extraHeaders);
        
        if ($response === null) {
            return ['success' => false, 'error' => 'Failed to poll events'];
        }
        
        $events = $response ?? [];
        $processed = 0;
        $errors = [];
        $eventIds = [];
        
        foreach ($events as $event) {
            try {
                $this->processEvent($event);
                $eventIds[] = $event['id'];
                $processed++;
            } catch (\Exception $e) {
                $errors[] = [
                    'event_id' => $event['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Acknowledge processed events
        if (!empty($eventIds)) {
            $this->acknowledgeEvents($eventIds);
        }
        
        // Update last poll time
        $stmt = $this->db->prepare("
            UPDATE ifood_integrations 
            SET last_poll_at = NOW() 
            WHERE company_id = :company_id
        ");
        $stmt->execute(['company_id' => $this->companyId]);
        
        return [
            'success' => true,
            'total_events' => count($events),
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    /**
     * Process a webhook event (public entry point for WebhookIFoodController).
     * Delegates to processEvent() which handles all event types.
     */
    public function processWebhookEvent(array $event): void
    {
        $this->processEvent($event);
    }
    
    /**
     * Process a single event
     */
    private function processEvent(array $event): void
    {
        $eventId = $event['id'] ?? '';
        $orderId = $event['orderId'] ?? '';
        $code = $event['code'] ?? '';
        $fullCode = $event['fullCode'] ?? '';
        $merchantId = $event['merchantId'] ?? '';
        $createdAt = $event['createdAt'] ?? null;
        $metadata = $event['metadata'] ?? [];
        
        // Log event (ON DUPLICATE KEY UPDATE prevents duplicates in log)
        $this->logEvent($event);
        
        // Deduplicate: skip if this exact event_id was already processed
        if ($eventId) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM ifood_events_log 
                WHERE event_id = :event_id AND acknowledged = 1
            ");
            $stmt->execute(['event_id' => $eventId]);
            if ((int) $stmt->fetchColumn() > 0) {
                return; // Already processed and acknowledged
            }
        }
        
        // Process based on event type
        switch ($fullCode) {
            case 'PLACED':
                $this->handleNewOrder($orderId);
                break;
                
            case 'CONFIRMED':
                $this->updateOrderStatus($orderId, 'CONFIRMED', 'confirmed_at');
                break;
                
            case 'READY_TO_PICKUP':
                $this->updateOrderStatus($orderId, 'READY_TO_PICKUP', 'ready_at');
                break;
                
            case 'DISPATCHED':
                $this->updateOrderStatus($orderId, 'DISPATCHED', 'dispatched_at');
                break;
                
            case 'CONCLUDED':
                $this->updateOrderStatus($orderId, 'CONCLUDED', 'concluded_at');
                break;
                
            case 'CANCELLED':
                $this->handleCancellation($orderId, $metadata);
                break;
                
            case 'CANCELLATION_REQUESTED':
                // Could auto-accept or notify admin
                $this->handleCancellationRequest($orderId, $metadata);
                break;
                
            default:
                // Log other events for reference
                break;
        }
    }
    
    /**
     * Handle new order (PLACED event)
     */
    private function handleNewOrder(string $ifoodOrderId): void
    {
        // Get order details from iFood
        $orderDetails = $this->getOrderDetails($ifoodOrderId);
        
        if (!$orderDetails) {
            throw new \Exception("Could not fetch order details for: $ifoodOrderId");
        }
        
        // Check if order already exists
        $stmt = $this->db->prepare("
            SELECT id FROM ifood_orders WHERE ifood_order_id = :ifood_order_id
        ");
        $stmt->execute(['ifood_order_id' => $ifoodOrderId]);
        
        if ($stmt->fetch()) {
            return; // Already processed
        }
        
        // Parse order data
        $customer = $orderDetails['customer'] ?? [];
        $delivery = $orderDetails['delivery'] ?? [];
        $total = $orderDetails['total'] ?? [];
        $payments = $orderDetails['payments'] ?? [];
        $items = $orderDetails['items'] ?? [];
        $benefits = $orderDetails['benefits'] ?? [];
        
        // Insert iFood order
        $stmt = $this->db->prepare("
            INSERT INTO ifood_orders (
                company_id, ifood_order_id, ifood_display_id, ifood_merchant_id,
                order_type, order_timing, sales_channel, status,
                customer_name, customer_phone, customer_document,
                delivery_address, items, benefits, payments,
                total_amount, subtotal, delivery_fee, benefits_total, additional_fees,
                pickup_code, delivered_by, scheduled_datetime,
                ifood_created_at, raw_data
            ) VALUES (
                :company_id, :ifood_order_id, :display_id, :merchant_id,
                :order_type, :order_timing, :sales_channel, 'PLACED',
                :customer_name, :customer_phone, :customer_document,
                :delivery_address, :items, :benefits, :payments,
                :total_amount, :subtotal, :delivery_fee, :benefits_total, :additional_fees,
                :pickup_code, :delivered_by, :scheduled_datetime,
                :ifood_created_at, :raw_data
            )
        ");
        
        $scheduledDatetime = null;
        if (!empty($orderDetails['schedule'])) {
            $scheduledDatetime = $orderDetails['schedule']['deliveryDateTimeStart'] ?? null;
        }
        
        $stmt->execute([
            'company_id' => $this->companyId,
            'ifood_order_id' => $ifoodOrderId,
            'display_id' => $orderDetails['displayId'] ?? null,
            'merchant_id' => $orderDetails['merchant']['id'] ?? '',
            'order_type' => $orderDetails['orderType'] ?? 'DELIVERY',
            'order_timing' => $orderDetails['orderTiming'] ?? 'IMMEDIATE',
            'sales_channel' => $orderDetails['salesChannel'] ?? 'IFOOD',
            'customer_name' => $customer['name'] ?? null,
            'customer_phone' => $customer['phone']['number'] ?? null,
            'customer_document' => $customer['documentNumber'] ?? null,
            'delivery_address' => json_encode($delivery['deliveryAddress'] ?? null),
            'items' => json_encode($items),
            'benefits' => json_encode($benefits),
            'payments' => json_encode($payments),
            'total_amount' => $total['orderAmount'] ?? 0,
            'subtotal' => $total['subTotal'] ?? 0,
            'delivery_fee' => $total['deliveryFee'] ?? 0,
            'benefits_total' => $total['benefits'] ?? 0,
            'additional_fees' => $total['additionalFees'] ?? 0,
            'pickup_code' => $delivery['pickupCode'] ?? null,
            'delivered_by' => $delivery['deliveredBy'] ?? 'IFOOD',
            'scheduled_datetime' => $scheduledDatetime,
            'ifood_created_at' => $orderDetails['createdAt'] ?? null,
            'raw_data' => json_encode($orderDetails),
        ]);
        
        $ifoodOrderDbId = (int) $this->db->lastInsertId();
        
        // Create local order
        $localOrderId = $this->createLocalOrder($ifoodOrderDbId, $orderDetails);
        
        // Update with local order ID
        if ($localOrderId) {
            $stmt = $this->db->prepare("
                UPDATE ifood_orders SET order_id = :order_id WHERE id = :id
            ");
            $stmt->execute(['order_id' => $localOrderId, 'id' => $ifoodOrderDbId]);
        }
        
        // Auto-confirm if configured
        $config = $this->getConfig();
        if ($config['auto_confirm']) {
            $this->confirmOrder($ifoodOrderId);
        }
        
        // Send push notification
        $this->notifyNewOrder($ifoodOrderDbId, $orderDetails);
    }
    
    /**
     * Create local order from iFood order
     */
    private function createLocalOrder(int $ifoodOrderDbId, array $orderDetails): ?int
    {
        $customer = $orderDetails['customer'] ?? [];
        $delivery = $orderDetails['delivery'] ?? [];
        $address = $delivery['deliveryAddress'] ?? [];
        $total = $orderDetails['total'] ?? [];
        $payments = $orderDetails['payments'] ?? [];
        
        $phone = $customer['phone']['number'] ?? null;
        
        // Find payment_method_id
        $paymentMethodId = null;
        if (!empty($payments['methods'])) {
            $firstMethod = $payments['methods'][0] ?? [];
            $method = strtoupper($firstMethod['method'] ?? '');
            $brand = $firstMethod['brand'] ?? $firstMethod['card']['brand'] ?? null;
            
            if ($brand) {
                $stmt = $this->db->prepare("SELECT id FROM payment_methods WHERE company_id = :cid AND name LIKE :brand LIMIT 1");
                $stmt->execute(['cid' => $this->companyId, 'brand' => '%' . $brand . '%']);
                $pm = $stmt->fetch();
                if ($pm) $paymentMethodId = (int) $pm['id'];
            }
            
            // Fallback: Pix
            if (!$paymentMethodId && $method === 'PIX') {
                $stmt = $this->db->prepare("SELECT id FROM payment_methods WHERE company_id = :cid AND LOWER(name) LIKE '%pix%' LIMIT 1");
                $stmt->execute(['cid' => $this->companyId]);
                $pm = $stmt->fetch();
                if ($pm) $paymentMethodId = (int) $pm['id'];
            }
        }
        
        // Build delivery address string
        $addressParts = array_filter([
            $address['streetName'] ?? null,
            !empty($address['streetNumber']) ? $address['streetNumber'] : null,
            !empty($address['complement']) ? $address['complement'] : null,
            !empty($address['neighborhood']) ? $address['neighborhood'] : null,
            !empty($address['city']) ? $address['city'] : null,
        ]);
        $addressStr = implode(', ', $addressParts);
        if (!empty($address['reference'])) {
            $addressStr .= ' (Ref: ' . $address['reference'] . ')';
        }
        
        // Get next order_number
        $nextOrderNumber = \Order::getNextOrderNumber($this->db, $this->companyId);
        
        $ifoodOrderId = $orderDetails['id'] ?? null;
        
        $stmt = $this->db->prepare("
            INSERT INTO orders (
                order_number, company_id, status,
                subtotal, delivery_fee, discount, total,
                payment_method_id,
                customer_name, customer_phone,
                customer_address,
                notes, source, ifood_order_id, created_at
            ) VALUES (
                :order_number, :company_id, 'pending',
                :subtotal, :delivery_fee, :discount, :total,
                :payment_method_id,
                :customer_name, :customer_phone,
                :customer_address,
                :notes, 'ifood', :ifood_order_id, NOW()
            )
        ");
        
        $stmt->execute([
            'order_number' => $nextOrderNumber,
            'company_id' => $this->companyId,
            'subtotal' => $total['subTotal'] ?? 0,
            'delivery_fee' => $total['deliveryFee'] ?? 0,
            'discount' => $total['benefits'] ?? 0,
            'total' => $total['orderAmount'] ?? 0,
            'payment_method_id' => $paymentMethodId,
            'customer_name' => $customer['name'] ?? 'Cliente iFood',
            'customer_phone' => normalizePhone((string) ($phone ?? '')),
            'customer_address' => $addressStr ?: null,
            'notes' => implode(' | ', array_filter([
                sprintf("iFood #%s", $orderDetails['displayId'] ?? $orderDetails['id']),
                $orderDetails['extraInfo'] ?? '',
                !empty($delivery['observations']) ? 'Obs entrega: ' . $delivery['observations'] : '',
            ])),
            'ifood_order_id' => $ifoodOrderId
        ]);
        
        $orderId = (int) $this->db->lastInsertId();
        
        // Create order items
        $this->createOrderItems($orderId, $orderDetails['items'] ?? []);
        
        return $orderId;
    }
    
    /**
     * Create order items from iFood items
     */
    private function createOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            // Try to find local product by external code or name
            $productId = $this->findLocalProduct($item);
            
            $stmt = $this->db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, quantity, unit_price, line_total,
                    notes
                ) VALUES (
                    :order_id, :product_id, :quantity, :unit_price, :line_total,
                    :notes
                )
            ");
            
            $itemName = $item['name'] ?? '';
            $observations = $item['observations'] ?? '';
            
            // Build notes: always include iFood item name, plus observations if any
            $notesParts = array_filter([$itemName, $observations]);
            $notes = implode(' - ', $notesParts) ?: null;
            
            $stmt->execute([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unitPrice'] ?? 0,
                'line_total' => $item['totalPrice'] ?? 0,
                'notes' => $notes
            ]);
            
            // Store options as customization_data
            if (!empty($item['options'])) {
                $orderItemId = (int) $this->db->lastInsertId();
                $customizationData = [];
                foreach ($item['options'] as $opt) {
                    $customizationData[] = [
                        'group' => $opt['groupName'] ?? '',
                        'name' => $opt['name'] ?? '',
                        'quantity' => $opt['quantity'] ?? 1,
                        'price' => $opt['price'] ?? 0,
                    ];
                }
                $stmt2 = $this->db->prepare("UPDATE order_items SET customization_data = :cd WHERE id = :id");
                $stmt2->execute(['cd' => json_encode($customizationData), 'id' => $orderItemId]);
            }
        }
    }
    
    /**
     * Create order item options from iFood options
     */
    /**
     * Find local product by iFood item
     */
    private function findLocalProduct(array $item): ?int
    {
        $externalCode = $item['externalCode'] ?? null;
        $ifoodProductId = $item['id'] ?? null;
        
        // Try by mapping
        if ($ifoodProductId) {
            $stmt = $this->db->prepare("
                SELECT product_id FROM ifood_product_mapping 
                WHERE company_id = :company_id AND ifood_product_id = :ifood_id AND product_id IS NOT NULL
            ");
            $stmt->execute([
                'company_id' => $this->companyId,
                'ifood_id' => $ifoodProductId
            ]);
            $mapping = $stmt->fetch();
            if ($mapping) {
                return (int) $mapping['product_id'];
            }
        }
        
        // Try by external code (SKU)
        if ($externalCode) {
            $stmt = $this->db->prepare("
                SELECT id FROM products 
                WHERE company_id = :company_id AND sku = :sku AND deleted_at IS NULL
            ");
            $stmt->execute([
                'company_id' => $this->companyId,
                'sku' => $externalCode
            ]);
            $product = $stmt->fetch();
            if ($product) {
                return (int) $product['id'];
            }
        }
        
        // Try by name (fuzzy match)
        $name = $item['name'] ?? '';
        if ($name) {
            $stmt = $this->db->prepare("
                SELECT id FROM products 
                WHERE company_id = :company_id AND name LIKE :name AND deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([
                'company_id' => $this->companyId,
                'name' => '%' . $name . '%'
            ]);
            $product = $stmt->fetch();
            if ($product) {
                return (int) $product['id'];
            }
        }
        
        return null;
    }
    
    /**
     * Notify admin about new iFood order
     */
    private function notifyNewOrder(int $ifoodOrderDbId, array $orderDetails): void
    {
        try {
            // Get local order data
            $stmt = $this->db->prepare("
                SELECT o.* FROM orders o
                INNER JOIN ifood_orders io ON io.order_id = o.id
                WHERE io.id = :id
            ");
            $stmt->execute(['id' => $ifoodOrderDbId]);
            $order = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("iFood notifyNewOrder: order not found for ifood_orders.id={$ifoodOrderDbId}");
                return;
            }
            
            $orderId = (int) $order['id'];
            
            // Fetch order items for WhatsApp message
            $stmtItems = $this->db->prepare("
                SELECT oi.*, p.name AS product_name 
                FROM order_items oi 
                LEFT JOIN products p ON p.id = oi.product_id 
                WHERE oi.order_id = :oid
            ");
            $stmtItems->execute(['oid' => $orderId]);
            $items = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);
            
            // Fetch payment method name
            $paymentMethod = 'iFood';
            if (!empty($order['payment_method_id'])) {
                $stmtPm = $this->db->prepare("SELECT name FROM payment_methods WHERE id = :id LIMIT 1");
                $stmtPm->execute(['id' => $order['payment_method_id']]);
                $pm = $stmtPm->fetch(\PDO::FETCH_ASSOC);
                if ($pm) $paymentMethod = $pm['name'];
            }
            
            // Build order data for notifications
            $orderData = [
                'id' => $orderId,
                'order_number' => $order['order_number'],
                'customer_name' => $order['customer_name'],
                'customer_phone' => $order['customer_phone'],
                'customer_address' => $order['customer_address'],
                'total' => $order['total'],
                'subtotal' => $order['subtotal'],
                'delivery_fee' => $order['delivery_fee'],
                'discount' => $order['discount'],
                'payment_method' => $paymentMethod,
                'notes' => $order['notes'],
                'source' => 'ifood',
                'created_at' => $order['created_at'],
                'items' => array_map(function ($it) {
                    return [
                        'name' => $it['product_name'] ?: ($it['notes'] ?: 'Item iFood'),
                        'quantity' => (int) ($it['quantity'] ?? 1),
                        'price' => (float) ($it['unit_price'] ?? 0),
                    ];
                }, $items),
            ];
            
            // Send WhatsApp notification
            try {
                \OrderNotificationService::sendOrderNotification($this->companyId, $orderData);
            } catch (\Throwable $e) {
                error_log("iFood WhatsApp notification error: " . $e->getMessage());
            }
            
            // Send Web Push notification
            try {
                $webPushService = new \App\Services\WebPushService($this->db);
                $webPushService->notifyNewOrder($this->companyId, $orderData);
            } catch (\Throwable $e) {
                error_log("iFood Web Push notification error: " . $e->getMessage());
            }
        } catch (\Throwable $e) {
            // Don't fail order creation if notification fails
            error_log("Failed to send iFood notification: " . $e->getMessage());
        }
    }
    
    /**
     * Get order details from iFood API
     */
    public function getOrderDetails(string $orderId): ?array
    {
        return $this->httpRequest(
            'GET',
            self::API_BASE_URL . "/order/v1.0/orders/{$orderId}",
            [],
            true
        );
    }
    
    /**
     * Confirm an order
     */
    public function confirmOrder(string $orderId): bool
    {
        $response = $this->httpRequest(
            'POST',
            self::API_BASE_URL . "/order/v1.0/orders/{$orderId}/confirm",
            [],
            true
        );
        
        if ($response !== null) {
            $this->updateOrderStatus($orderId, 'CONFIRMED', 'confirmed_at');
            return true;
        }
        
        return false;
    }
    
    /**
     * Mark order as ready to pickup
     */
    public function readyToPickup(string $orderId): bool
    {
        $response = $this->httpRequest(
            'POST',
            self::API_BASE_URL . "/order/v1.0/orders/{$orderId}/readyToPickup",
            [],
            true
        );
        
        if ($response !== null) {
            $this->updateOrderStatus($orderId, 'READY_TO_PICKUP', 'ready_at');
            return true;
        }
        
        return false;
    }
    
    /**
     * Dispatch order (for own delivery)
     */
    public function dispatchOrder(string $orderId): bool
    {
        $response = $this->httpRequest(
            'POST',
            self::API_BASE_URL . "/order/v1.0/orders/{$orderId}/dispatch",
            [],
            true
        );
        
        if ($response !== null) {
            $this->updateOrderStatus($orderId, 'DISPATCHED', 'dispatched_at');
            return true;
        }
        
        return false;
    }
    
    /**
     * Cancel order (public wrapper)
     */
    public function cancelOrder(string $orderId, ?string $reasonCode = null, string $reason = ''): array
    {
        if (!$reasonCode) {
            return ['success' => false, 'error' => 'Motivo de cancelamento é obrigatório'];
        }
        
        $result = $this->requestCancellation($orderId, $reasonCode, $reason);
        
        if ($result) {
            $this->handleCancellation($orderId, ['reason' => $reason ?: $reasonCode]);
            return ['success' => true, 'message' => 'Pedido cancelado com sucesso'];
        }
        
        return ['success' => false, 'error' => 'Falha ao cancelar pedido no iFood'];
    }
    
    /**
     * Request order cancellation
     */
    public function requestCancellation(string $orderId, string $reasonCode, string $reason = ''): bool
    {
        $response = $this->httpRequest(
            'POST',
            self::API_BASE_URL . "/order/v1.0/orders/{$orderId}/requestCancellation",
            [
                'cancellationCode' => $reasonCode,
                'reason' => $reason
            ],
            true
        );
        
        return $response !== null;
    }
    
    /**
     * Get available cancellation reasons for an order
     */
    public function getCancellationReasons(string $orderId): array
    {
        $response = $this->httpRequest(
            'GET',
            self::API_BASE_URL . "/order/v1.0/orders/{$orderId}/cancellationReasons",
            [],
            true
        );
        
        return $response ?? [];
    }
    
    /**
     * Acknowledge events
     */
    private function acknowledgeEvents(array $eventIds): void
    {
        $this->httpRequest(
            'POST',
            self::API_BASE_URL . '/order/v1.0/events/acknowledgment',
            array_map(fn($id) => ['id' => $id], $eventIds),
            true
        );
        
        // Mark as acknowledged in database
        if (!empty($eventIds)) {
            $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
            $stmt = $this->db->prepare("
                UPDATE ifood_events_log 
                SET acknowledged = 1, acknowledged_at = NOW() 
                WHERE event_id IN ($placeholders)
            ");
            $stmt->execute($eventIds);
        }
    }
    
    /**
     * Update order status
     */
    private function updateOrderStatus(string $ifoodOrderId, string $status, string $timestampField): void
    {
        $stmt = $this->db->prepare("
            UPDATE ifood_orders 
            SET status = :status, {$timestampField} = NOW(), updated_at = NOW()
            WHERE ifood_order_id = :ifood_order_id
        ");
        $stmt->execute([
            'status' => $status,
            'ifood_order_id' => $ifoodOrderId
        ]);
        
        // Also update local order status (must match enum: pending, completed, canceled)
        switch ($status) {
            case 'CONFIRMED':
            case 'READY_TO_PICKUP':
            case 'DISPATCHED':
                $localStatus = 'pending'; // Still in progress
                break;
            case 'CONCLUDED':
                $localStatus = 'completed';
                break;
            case 'CANCELLED':
                $localStatus = 'canceled';
                break;
            default:
                $localStatus = null;
        }
        
        if ($localStatus) {
            $stmt = $this->db->prepare("
                UPDATE orders o
                INNER JOIN ifood_orders io ON io.order_id = o.id
                SET o.status = :status, o.updated_at = NOW()
                WHERE io.ifood_order_id = :ifood_order_id
            ");
            $stmt->execute([
                'status' => $localStatus,
                'ifood_order_id' => $ifoodOrderId
            ]);
        }
    }
    
    /**
     * Handle order cancellation
     */
    private function handleCancellation(string $ifoodOrderId, array $metadata): void
    {
        $reason = $metadata['details'] ?? $metadata['reason'] ?? 'Cancelado pelo iFood';
        
        $stmt = $this->db->prepare("
            UPDATE ifood_orders 
            SET status = 'CANCELLED', cancelled_at = NOW(), cancellation_reason = :reason
            WHERE ifood_order_id = :ifood_order_id
        ");
        $stmt->execute([
            'reason' => $reason,
            'ifood_order_id' => $ifoodOrderId
        ]);
        
        // Update local order
        $stmt = $this->db->prepare("
            UPDATE orders o
            INNER JOIN ifood_orders io ON io.order_id = o.id
            SET o.status = 'canceled', o.updated_at = NOW()
            WHERE io.ifood_order_id = :ifood_order_id
        ");
        $stmt->execute([
            'ifood_order_id' => $ifoodOrderId
        ]);
    }
    
    /**
     * Handle cancellation request from customer/iFood
     * Updates iFood order status and notifies admin via push + WhatsApp
     */
    private function handleCancellationRequest(string $ifoodOrderId, array $metadata): void
    {
        $reason = $metadata['details'] ?? $metadata['reason'] ?? 'Solicitação de cancelamento';
        
        // Update iFood order status
        $stmt = $this->db->prepare("
            UPDATE ifood_orders 
            SET status = 'CANCELLATION_REQUESTED', cancellation_reason = :reason, updated_at = NOW()
            WHERE ifood_order_id = :ifood_order_id
        ");
        $stmt->execute([
            'reason' => $reason,
            'ifood_order_id' => $ifoodOrderId
        ]);
        
        // Get order info for notification
        $stmt = $this->db->prepare("
            SELECT io.ifood_display_id, o.order_number, o.id as order_id
            FROM ifood_orders io
            LEFT JOIN orders o ON o.id = io.order_id
            WHERE io.ifood_order_id = :ifood_order_id AND io.company_id = :company_id
        ");
        $stmt->execute([
            'ifood_order_id' => $ifoodOrderId,
            'company_id' => $this->companyId
        ]);
        $orderInfo = $stmt->fetch();
        
        if ($orderInfo) {
            $displayId = $orderInfo['ifood_display_id'] ?: $ifoodOrderId;
            
            // Send push notification about cancellation request
            try {
                $pushService = new WebPushService($this->db, $this->companyId);
                $pushService->sendToAll(
                    '⚠️ Cancelamento Solicitado',
                    "Pedido iFood #{$displayId}: {$reason}. Acesse o painel para aceitar ou recusar.",
                    base_url("admin/ifood/orders/{$orderInfo['order_id']}")
                );
            } catch (\Throwable $e) {
                error_log("Push notification error for cancellation request: " . $e->getMessage());
            }
        }
        
        error_log("Cancellation requested for iFood order: $ifoodOrderId - " . json_encode($metadata));
    }
    
    /**
     * Log event to database
     */
    private function logEvent(array $event): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO ifood_events_log (
                company_id, ifood_order_id, event_id, event_code, event_full_code,
                metadata, ifood_created_at
            ) VALUES (
                :company_id, :order_id, :event_id, :code, :full_code,
                :metadata, :created_at
            ) ON DUPLICATE KEY UPDATE
                metadata = VALUES(metadata)
        ");
        
        $stmt->execute([
            'company_id' => $this->companyId,
            'order_id' => $event['orderId'] ?? '',
            'event_id' => $event['id'] ?? '',
            'code' => $event['code'] ?? '',
            'full_code' => $event['fullCode'] ?? '',
            'metadata' => json_encode($event['metadata'] ?? []),
            'created_at' => $event['createdAt'] ?? null
        ]);
    }
    
    /**
     * Get integration config
     */
    public function getConfig(): ?array
    {
        if ($this->config !== null) {
            return $this->config;
        }
        
        $stmt = $this->db->prepare("
            SELECT * FROM ifood_integrations WHERE company_id = :company_id
        ");
        $stmt->execute(['company_id' => $this->companyId]);
        
        $this->config = $stmt->fetch() ?: null;
        return $this->config;
    }
    
    /**
     * Save integration config
     */
    public function saveConfig(array $data): bool
    {
        $existing = $this->getConfig();
        
        if ($existing) {
            $fields = [];
            $params = ['company_id' => $this->companyId];
            
            if (isset($data['client_id'])) {
                $fields[] = 'client_id = :client_id';
                $params['client_id'] = $data['client_id'];
            }
            
            if (isset($data['client_secret']) && !empty($data['client_secret'])) {
                $fields[] = 'client_secret = :client_secret';
                $params['client_secret'] = $this->encrypt($data['client_secret']);
            }
            
            if (isset($data['merchant_id'])) {
                $fields[] = 'merchant_id = :merchant_id';
                $params['merchant_id'] = $data['merchant_id'];
            }
            
            if (isset($data['is_active'])) {
                $fields[] = 'is_active = :is_active';
                $params['is_active'] = $data['is_active'] ? 1 : 0;
            }
            
            if (isset($data['auto_confirm'])) {
                $fields[] = 'auto_confirm = :auto_confirm';
                $params['auto_confirm'] = $data['auto_confirm'] ? 1 : 0;
            }
            
            if (empty($fields)) {
                return true;
            }
            
            $sql = "UPDATE ifood_integrations SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE company_id = :company_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO ifood_integrations (
                    company_id, client_id, client_secret, merchant_id,
                    is_active, auto_confirm
                ) VALUES (
                    :company_id, :client_id, :client_secret, :merchant_id,
                    :is_active, :auto_confirm
                )
            ");
            
            return $stmt->execute([
                'company_id' => $this->companyId,
                'client_id' => $data['client_id'] ?? null,
                'client_secret' => isset($data['client_secret']) ? $this->encrypt($data['client_secret']) : null,
                'merchant_id' => $data['merchant_id'] ?? null,
                'is_active' => ($data['is_active'] ?? false) ? 1 : 0,
                'auto_confirm' => ($data['auto_confirm'] ?? false) ? 1 : 0,
            ]);
        }
    }
    
    /**
     * Log error
     */
    private function logError(string $error): void
    {
        error_log("iFood Integration Error (Company {$this->companyId}): $error");
        
        $stmt = $this->db->prepare("
            UPDATE ifood_integrations 
            SET last_error = :error, updated_at = NOW()
            WHERE company_id = :company_id
        ");
        $stmt->execute([
            'error' => $error,
            'company_id' => $this->companyId
        ]);
    }
    
    /**
     * Make HTTP request
     */
    private function httpRequest(string $method, string $url, array $data = [], bool $authenticated = true, bool $formEncoded = false, array $extraHeaders = []): ?array
    {
        $contentType = $formEncoded ? 'Content-Type: application/x-www-form-urlencoded' : 'Content-Type: application/json';
        $headers = [
            $contentType,
            'Accept: application/json',
        ];
        
        if ($authenticated) {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }
            $headers[] = "Authorization: Bearer $token";
        }
        
        $headers = array_merge($headers, $extraHeaders);
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        $body = $formEncoded ? http_build_query($data) : json_encode($data);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->logError("cURL error: $error");
            return null;
        }
        
        if ($httpCode >= 400) {
            $this->logError("HTTP $httpCode: $response");
            // Para auth, retornar corpo com erro em vez de null
            if (!$authenticated) {
                $decoded = json_decode($response, true);
                return is_array($decoded) ? $decoded : null;
            }
            return null;
        }
        
        // Empty response is valid for some endpoints
        if (empty($response)) {
            return [];
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError("JSON decode error: " . json_last_error_msg());
            return null;
        }
        
        return $decoded;
    }
    
    /**
     * Encrypt sensitive data
     */
    private function encrypt(string $data): string
    {
        $key = $_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: 'default-key-change-me';
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    private function decrypt(string $data): string
    {
        $key = $_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: 'default-key-change-me';
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv) ?: '';
    }
    
    /**
     * Test authentication with iFood API
     * @return array ['success' => bool, 'error' => string|null, 'expires_at' => string|null]
     */
    public function testAuthentication(): array
    {
        $config = $this->getConfig();
        
        if (!$config || empty($config['client_id']) || empty($config['client_secret'])) {
            return ['success' => false, 'error' => 'Credenciais não configuradas'];
        }
        
        // Faz requisição de autenticação direta
        $response = $this->httpRequest('POST', self::AUTH_URL . '/oauth/token', [
            'grantType' => 'client_credentials',
            'clientId' => $config['client_id'],
            'clientSecret' => $this->decrypt($config['client_secret']),
        ], false, true);
        
        if (!$response) {
            return ['success' => false, 'error' => 'Falha na comunicação com o servidor do iFood'];
        }
        
        if (isset($response['error']) || isset($response['error_description'])) {
            $errorMsg = $response['error_description'] ?? $response['error'] ?? 'Erro desconhecido';
            return ['success' => false, 'error' => $errorMsg];
        }
        
        if (!isset($response['accessToken'])) {
            return ['success' => false, 'error' => 'Resposta inválida do servidor. Verifique as credenciais.'];
        }
        
        // Salva o token obtido
        $expiresAt = date('Y-m-d H:i:s', time() + ($response['expiresIn'] ?? 3600));
        
        $stmt = $this->db->prepare("
            UPDATE ifood_integrations 
            SET access_token = :token, 
                token_expires_at = :expires,
                last_error = NULL,
                updated_at = NOW()
            WHERE company_id = :company_id
        ");
        
        $stmt->execute([
            'token' => $this->encrypt($response['accessToken']),
            'expires' => $expiresAt,
            'company_id' => $this->companyId,
        ]);
        
        $this->config = null;
        
        return [
            'success' => true, 
            'expires_at' => $expiresAt,
            'token_type' => $response['tokenType'] ?? 'Bearer'
        ];
    }
    
    /**
     * Get merchants list
     */
    public function getMerchants(): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            throw new \Exception('Não foi possível obter token de acesso. Verifique as credenciais.');
        }
        
        $response = $this->httpRequest(
            'GET',
            self::API_BASE_URL . '/merchant/v1.0/merchants',
            [],
            true
        );
        
        if ($response === null) {
            throw new \Exception('Falha ao buscar lojas no iFood.');
        }
        
        return $response;
    }
    
    /**
     * Get merchant status
     */
    public function getMerchantStatus(string $merchantId): ?array
    {
        return $this->httpRequest(
            'GET',
            self::API_BASE_URL . "/merchant/v1.0/merchants/{$merchantId}/status",
            [],
            true
        );
    }
    
    /**
     * Get iFood orders by status
     */
    public function getOrders(?string $status = null, int $limit = 50): array
    {
        $sql = "
            SELECT io.*, o.id as local_order_id, o.status as local_status
            FROM ifood_orders io
            LEFT JOIN orders o ON o.id = io.order_id
            WHERE io.company_id = :company_id
        ";
        
        $params = ['company_id' => $this->companyId];
        
        if ($status) {
            $sql .= " AND io.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY io.created_at DESC LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':company_id', $this->companyId, PDO::PARAM_INT);
        if ($status) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get single iFood order
     */
    public function getOrder(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT io.*, o.id as local_order_id, o.status as local_status
            FROM ifood_orders io
            LEFT JOIN orders o ON o.id = io.order_id
            WHERE io.id = :id AND io.company_id = :company_id
        ");
        $stmt->execute([
            'id' => $id,
            'company_id' => $this->companyId
        ]);
        
        return $stmt->fetch() ?: null;
    }
}
