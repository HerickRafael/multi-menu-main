<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

require_once __DIR__ . '/../modules/auth/MobileAdminGuard.php';
require_once __DIR__ . '/../modules/orders/OrderListService.php';
require_once __DIR__ . '/../modules/orders/OrderStatusService.php';

/**
 * MobileAdminOrdersController
 * 
 * Gestão de pedidos mobile - foco em ações rápidas.
 */
class MobileAdminOrdersController extends Controller
{
    private function guard(): array
    {
        return MobileAdminGuard::requireCompanyAccess();
    }

    /**
     * GET /orders
     * Lista de pedidos com filtros por status
     */
    public function index(array $params = [])
    {
        [$u, $company] = $this->guard();

        $companyId = (int)$company['id'];
        $status = $_GET['status'] ?? 'all';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;

        $db = db();
        $listResult = OrderListService::listForMobile($db, $companyId, $status, $page, $limit);
        $orders = $listResult['orders'];
        $totalOrders = $listResult['totalOrders'];
        $statusCounts = $listResult['statusCounts'];
        $totalPages = $listResult['totalPages'];

        $pageTitle = 'Pedidos';
        $activeNav = 'orders';

        return $this->viewMobile('orders/index', compact(
            'company',
            'u',
            'orders',
            'status',
            'statusCounts',
            'page',
            'totalPages',
            'pageTitle',
            'activeNav'
        ));
    }

    /**
     * GET /orders/show?id=X
     * Detalhes do pedido
     */
    public function show(array $params = [])
    {
        [$u, $company] = $this->guard();

        $orderId = (int)($_GET['id'] ?? 0);
        
        if (!$orderId) {
            header('Location: /orders');
            exit;
        }

        $order = Order::find($orderId, (int)$company['id']);

        if (!$order || (int)$order['company_id'] !== (int)$company['id']) {
            http_response_code(404);
            echo 'Pedido não encontrado';
            exit;
        }

        // Busca itens do pedido
        $items = Order::getItems($orderId, (int)$company['id']);

        // Buscar histórico de eventos
        $db = db();
        $orderEvents = Order::eventsForOrder($db, (int)$company['id'], $orderId, 50);

        // Busca dados do cliente
        $customer = null;
        if (!empty($order['customer_id'])) {
            $customer = Customer::findByCompanyAndId((int)$company['id'], (int)$order['customer_id']);
        }

        $orderNumber = $order['order_number'] ?? $order['id'] ?? $orderId;
        $pageTitle = 'Pedido #' . $orderNumber;
        $activeNav = 'orders';
        $showBackButton = true;

        return $this->viewMobile('orders/show', compact(
            'company',
            'u',
            'order',
            'items',
            'customer',
            'orderEvents',
            'pageTitle',
            'activeNav',
            'showBackButton'
        ));
    }

    /**
     * GET /orders/{id}/edit
     * Editar pedido pendente
     */
    public function edit(array $params = [])
    {
        [$u, $company] = $this->guard();

        $companyId = (int)$company['id'];
        $orderId = (int)($params['id'] ?? 0);

        if (!$orderId) {
            header('Location: /orders');
            exit;
        }

        $db = db();
        $order = Order::findWithItems($db, $orderId, $companyId);

        if (!$order) {
            http_response_code(404);
            echo 'Pedido não encontrado';
            exit;
        }

        if (($order['status'] ?? '') !== 'pending') {
            http_response_code(403);
            echo 'Somente pedidos pendentes podem ser editados.';
            exit;
        }

        if (($order['source'] ?? '') === 'ifood') {
            http_response_code(403);
            echo 'Pedidos do iFood não podem ser editados.';
            exit;
        }

        $categories = Category::listByCompany($companyId);
        $products = Product::listByCompany($companyId);

        $stmt = $db->prepare("SELECT id, name, type, active FROM payment_methods WHERE company_id = ? AND active = 1 ORDER BY sort_order, name");
        $stmt->execute([$companyId]);
        $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../models/DeliveryCity.php';
        require_once __DIR__ . '/../models/DeliveryZone.php';

        $cities = DeliveryCity::allByCompany($companyId);
        $zones = DeliveryZone::allByCompany($companyId);

        $zonesByCity = [];
        foreach ($zones as $zone) {
            $cityId = (int)($zone['city_id'] ?? 0);
            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = [
                'id' => (int)$zone['id'],
                'name' => $zone['neighborhood'] ?? '',
                'fee' => (float)($zone['fee'] ?? 0),
                'city_name' => $zone['city_name'] ?? ''
            ];
        }

        $zonesPresent = !empty($zones);

        require_once __DIR__ . '/../models/ProductCustomization.php';
        $customizationMap = [];
        foreach ($products as $prod) {
            $groups = ProductCustomization::loadForPublic((int)$prod['id']);
            if (!empty($groups)) {
                $customizationMap[(int)$prod['id']] = $groups;
            }
        }

        $addressInfo = [
            'street' => '',
            'number' => '',
            'complement' => '',
            'neighborhood' => '',
            'city' => '',
            'reference' => '',
        ];

        $rawAddress = trim((string)($order['customer_address'] ?? ''));
        if ($rawAddress !== '') {
            $lines = preg_split('/\r?\n/', $rawAddress);
            if (count($lines) > 1) {
                $line1 = trim((string)($lines[0] ?? ''));
                $line2 = trim((string)($lines[1] ?? ''));
                $line3 = trim((string)($lines[2] ?? ''));

                if ($line1 !== '') {
                    $line1Parts = array_map('trim', explode(',', $line1, 2));
                    $addressInfo['street'] = $line1Parts[0] ?? '';
                    $rest = $line1Parts[1] ?? '';
                    if ($rest !== '') {
                        $restParts = array_map('trim', explode('-', $rest, 2));
                        $addressInfo['number'] = $restParts[0] ?? '';
                        $addressInfo['complement'] = $restParts[1] ?? '';
                    }
                }

                if ($line2 !== '') {
                    $line2Parts = array_map('trim', explode('-', $line2, 2));
                    $addressInfo['neighborhood'] = $line2Parts[0] ?? '';
                    $addressInfo['city'] = $line2Parts[1] ?? '';
                }

                if ($line3 !== '' && preg_match('/^Ref:\s*(.+)$/i', $line3, $match)) {
                    $addressInfo['reference'] = trim($match[1]);
                }
            } else {
                $parts = array_map('trim', explode(',', $rawAddress));
                $addressInfo['city'] = array_pop($parts) ?? '';
                $addressInfo['neighborhood'] = array_pop($parts) ?? '';
                if (count($parts) > 2) {
                    $addressInfo['complement'] = trim(implode(', ', array_slice($parts, 2)));
                }
                $addressInfo['street'] = $parts[0] ?? '';
                $addressInfo['number'] = $parts[1] ?? '';
            }
        }

        $normalize = function ($value) {
            $value = trim((string)$value);
            if ($value === '') {
                return '';
            }
            return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        };

        $selectedCityId = null;
        if ($addressInfo['city'] !== '') {
            $cityNeedle = $normalize($addressInfo['city']);
            foreach ($cities as $city) {
                if ($normalize($city['name'] ?? '') === $cityNeedle) {
                    $selectedCityId = (int)$city['id'];
                    break;
                }
            }
        }

        $selectedZoneId = null;
        if ($selectedCityId && $addressInfo['neighborhood'] !== '') {
            $zoneNeedle = $normalize($addressInfo['neighborhood']);
            foreach ($zonesByCity[$selectedCityId] ?? [] as $zone) {
                if ($normalize($zone['name'] ?? '') === $zoneNeedle) {
                    $selectedZoneId = (int)$zone['id'];
                    break;
                }
            }
        }

        $deliveryType = empty($order['customer_address']) ? 'pickup' : 'delivery';

        $prefill = [
            'customer_name' => $order['customer_name'] ?? '',
            'customer_phone' => $order['customer_phone'] ?? '',
            'notes' => $order['notes'] ?? '',
            'delivery_fee' => (float)($order['delivery_fee'] ?? 0),
            'discount' => (float)($order['discount'] ?? 0),
            'delivery_type' => $deliveryType,
            'street' => $addressInfo['street'],
            'number' => $addressInfo['number'],
            'complement' => $addressInfo['complement'],
            'reference' => $addressInfo['reference'],
            'neighborhood' => $addressInfo['neighborhood'],
            'city_id' => $selectedCityId,
            'zone_id' => $selectedZoneId,
            'payment_method_id' => $order['payment_method_id'] ?? null,
        ];

        $initialItems = [];
        foreach (($order['items'] ?? []) as $item) {
            $customData = $item['customization_data'] ?? null;
            if (is_string($customData)) {
                $decoded = json_decode($customData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $customData = $decoded;
                }
            }
            $initialItems[] = [
                'product_id' => (int)($item['product_id'] ?? 0),
                'quantity' => (int)($item['quantity'] ?? 0),
                'unit_price' => (float)($item['unit_price'] ?? 0),
                'product_name' => $item['product_name'] ?? '',
                'customization_data' => $customData,
            ];
        }

        $orderNumber = $order['order_number'] ?? $order['id'] ?? $orderId;
        $pageTitle = 'Editar Pedido #' . $orderNumber;
        $activeNav = 'orders';
        $showBackButton = true;

        return $this->viewMobile('orders/create', compact(
            'company',
            'u',
            'categories',
            'products',
            'paymentMethods',
            'cities',
            'zones',
            'zonesByCity',
            'zonesPresent',
            'customizationMap',
            'pageTitle',
            'activeNav',
            'showBackButton',
            'order',
            'prefill',
            'initialItems',
            'orderId'
        ));
    }

    /**
     * POST /orders/setStatus
     * Atualiza status do pedido
     */
    public function setStatus(array $params = [])
    {
        [$u, $company] = $this->guard();

        $orderId = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!$orderId || !$status) {
            $this->jsonResponse(['success' => false, 'error' => 'Dados inválidos']);
            return;
        }

        $db = db();
        $result = OrderStatusService::updateForCompany($db, (int)$company['id'], $orderId, $status);

        if (empty($result['ok'])) {
            $this->jsonResponse(['success' => false, 'error' => $result['error'] ?? 'Falha ao atualizar status']);
            return;
        }

        // Se for request AJAX, retorna JSON
        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => true,
                'status' => $result['requested_status'] ?? $status,
                'internal_status' => $result['internal_status'] ?? $status,
            ]);
            return;
        }

        // Senão, redireciona
        header('Location: /orders');
        exit;
    }

    /**
     * GET /orders/create
     * Formulário de novo pedido manual
     */
    public function create(array $params = [])
    {
        [$u, $company] = $this->guard();

        $companyId = (int)$company['id'];
        $categories = Category::listByCompany($companyId);
        $products = Product::listByCompany($companyId);
        
        // Buscar métodos de pagamento ativos
        $db = db();
        $stmt = $db->prepare("SELECT id, name, type, active FROM payment_methods WHERE company_id = ? AND active = 1 ORDER BY sort_order, name");
        $stmt->execute([$companyId]);
        $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar cidades e zonas de entrega cadastradas
        require_once __DIR__ . '/../models/DeliveryCity.php';
        require_once __DIR__ . '/../models/DeliveryZone.php';
        
        $cities = DeliveryCity::allByCompany($companyId);
        $zones = DeliveryZone::allByCompany($companyId);
        
        // Organizar zonas por cidade
        $zonesByCity = [];
        foreach ($zones as $zone) {
            $cityId = (int)($zone['city_id'] ?? 0);
            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = [
                'id' => (int)$zone['id'],
                'name' => $zone['neighborhood'] ?? '',
                'fee' => (float)($zone['fee'] ?? 0),
                'city_name' => $zone['city_name'] ?? ''
            ];
        }
        
        // Verificar se há zonas cadastradas
        $zonesPresent = !empty($zones);

        // Carregar mapa de customizações por produto
        require_once __DIR__ . '/../models/ProductCustomization.php';
        $customizationMap = [];
        foreach ($products as $prod) {
            $groups = ProductCustomization::loadForPublic((int)$prod['id']);
            if (!empty($groups)) {
                $customizationMap[(int)$prod['id']] = $groups;
            }
        }

        $pageTitle = 'Novo Pedido';
        $activeNav = 'orders';
        $showBackButton = true;

        return $this->viewMobile('orders/create', compact(
            'company',
            'u',
            'categories',
            'products',
            'paymentMethods',
            'cities',
            'zones',
            'zonesByCity',
            'zonesPresent',
            'customizationMap',
            'pageTitle',
            'activeNav',
            'showBackButton'
        ));
    }

    /**
     * POST /orders
     * Salva novo pedido
     */
    public function store(array $params = [])
    {
        [$u, $company] = $this->guard();

        $companyId = (int)$company['id'];
        $db = db();

        // Validação básica
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerPhone = normalizePhone(trim($_POST['customer_phone'] ?? ''));
        $deliveryType = $_POST['delivery_type'] ?? 'delivery';
        $customerAddress = trim($_POST['customer_address'] ?? '');
        $deliveryFee = 0;
        $notes = trim($_POST['notes'] ?? '');
        $discount = (float)str_replace(',', '.', $_POST['discount'] ?? 0);
        $items = $_POST['items'] ?? [];
        $paymentMethodType = trim($_POST['payment_method'] ?? '');
        $paymentMethodId = (int)($_POST['payment_method_id'] ?? 0);
        $zoneId = (int)($_POST['zone_id'] ?? 0);

        // Se não tem ID mas tem tipo, buscar um método ativo desse tipo
        if (!$paymentMethodId && $paymentMethodType) {
            $stmt = $db->prepare("SELECT id FROM payment_methods WHERE company_id = ? AND type = ? AND active = 1 ORDER BY sort_order LIMIT 1");
            $stmt->execute([$companyId, $paymentMethodType]);
            $paymentMethodId = (int)$stmt->fetchColumn();
        }

        if (empty($customerName) || empty($customerPhone)) {
            $_SESSION['flash_error'] = 'Nome e telefone do cliente são obrigatórios.';
            header('Location: /orders/create');
            exit;
        }

        if (empty($items)) {
            $_SESSION['flash_error'] = 'Adicione pelo menos um produto ao pedido.';
            header('Location: /orders/create');
            exit;
        }

        // Parse delivery fee - primeiro tenta pegar da zona selecionada
        if ($zoneId && $deliveryType === 'delivery') {
            require_once __DIR__ . '/../models/DeliveryZone.php';
            $zone = DeliveryZone::findForCompany($zoneId, $companyId);
            if ($zone) {
                $deliveryFee = (float)($zone['fee'] ?? 0);
            }
        } elseif (!empty($_POST['delivery_fee'])) {
            // Se não tem zona, usa o valor manual
            $deliveryFee = (float)str_replace(',', '.', $_POST['delivery_fee']);
        }

        // Se for retirada, não cobra entrega
        if ($deliveryType === 'pickup') {
            $deliveryFee = 0;
            $customerAddress = '';
        }

        // Calcula subtotal
        $subtotal = 0;
        $processedItems = [];

        $customizationJsons = $_POST['customization_data_json'] ?? [];

        foreach ($items as $idx => $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            $unitPrice = (float)($item['unit_price'] ?? 0);

            if ($productId && $quantity > 0) {
                $customizationData = null;
                $rawJson = $customizationJsons[$idx] ?? null;
                if ($rawJson) {
                    $decoded = json_decode($rawJson, true);
                    if (is_array($decoded)) {
                        $delta = (float)($decoded['total_delta'] ?? 0);
                        $unitPrice += $delta;
                        $customizationData = $decoded;
                    }
                }

                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                $processedItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'combo_data' => null,
                    'customization_data' => $customizationData,
                    'notes' => null,
                ];
            }
        }

        if (empty($processedItems)) {
            $_SESSION['flash_error'] = 'Nenhum item válido no pedido.';
            header('Location: /orders/create');
            exit;
        }

        $total = max(0, $subtotal + $deliveryFee - $discount);

        // Cria o pedido
        try {
            $db->beginTransaction();

            $orderId = Order::create($db, [
                'company_id' => $companyId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'notes' => $notes,
                'customer_address' => $customerAddress,
                'payment_method_id' => $paymentMethodId ?: null,
                'source' => 'manual',
            ]);

            // Adiciona os itens
            foreach ($processedItems as $item) {
                Order::addItem($db, $orderId, $item);
            }

            $db->commit();

            // Sync street to autocomplete system
            $street = trim($_POST['street'] ?? '');
            if ($deliveryType === 'delivery' && $street !== '') {
                try {
                    $cityName = '';
                    $neighborhoodName = trim($_POST['neighborhood'] ?? '');
                    if ($cityId) {
                        require_once __DIR__ . '/../models/DeliveryCity.php';
                        $cityRow = DeliveryCity::findForCompany($cityId, $companyId);
                        $cityName = $cityRow['name'] ?? '';
                    }
                    if (!$neighborhoodName && $zoneId) {
                        require_once __DIR__ . '/../models/DeliveryZone.php';
                        $zoneRow = DeliveryZone::findForCompany($zoneId, $companyId);
                        $neighborhoodName = $zoneRow['neighborhood'] ?? '';
                    }
                    if ($cityName !== '') {
                        require_once __DIR__ . '/../services/AddressAutocompleteService.php';
                        $acService = new \AddressAutocompleteService($db, $companyId);
                        $acService->syncFromOrder($cityName, $neighborhoodName, $street, $customerPhone);
                    }
                } catch (\Throwable $e) {
                    error_log("Mobile admin syncFromOrder error: " . $e->getMessage());
                }
            }

            $_SESSION['flash_success'] = 'Pedido #' . $orderId . ' criado com sucesso!';
            header('Location: /orders');
            exit;

        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Erro ao criar pedido: ' . $e->getMessage();
            header('Location: /orders/create');
            exit;
        }
    }

    /**
     * POST /orders/{id}
     * Atualiza pedido pendente
     */
    public function update(array $params = [])
    {
        [$u, $company] = $this->guard();

        $companyId = (int)$company['id'];
        $db = db();

        $orderId = (int)($params['id'] ?? 0);
        $order = Order::findBasic($db, $orderId, $companyId);

        if (!$order) {
            http_response_code(404);
            echo 'Pedido não encontrado';
            return;
        }

        if (($order['status'] ?? '') !== 'pending') {
            http_response_code(403);
            echo 'Somente pedidos pendentes podem ser editados.';
            return;
        }

        if (($order['source'] ?? '') === 'ifood') {
            http_response_code(403);
            echo 'Pedidos do iFood não podem ser editados.';
            return;
        }

        $customerName = trim($_POST['customer_name'] ?? '');
        $customerPhone = normalizePhone(trim($_POST['customer_phone'] ?? ''));
        $deliveryType = $_POST['delivery_type'] ?? 'delivery';
        $customerAddress = trim($_POST['customer_address'] ?? '');
        $deliveryFee = 0;
        $notes = trim($_POST['notes'] ?? '');
        $discount = (float)str_replace(',', '.', $_POST['discount'] ?? 0);
        $items = $_POST['items'] ?? [];
        $paymentMethodType = trim($_POST['payment_method'] ?? '');
        $paymentMethodId = (int)($_POST['payment_method_id'] ?? 0);
        $cityId = (int)($_POST['city_id'] ?? 0);
        $zoneId = (int)($_POST['zone_id'] ?? 0);

        if (!$paymentMethodId && $paymentMethodType) {
            $stmt = $db->prepare("SELECT id FROM payment_methods WHERE company_id = ? AND type = ? AND active = 1 ORDER BY sort_order LIMIT 1");
            $stmt->execute([$companyId, $paymentMethodType]);
            $paymentMethodId = (int)$stmt->fetchColumn();
        }

        if (empty($customerName) || empty($customerPhone)) {
            $_SESSION['flash_error'] = 'Nome e telefone do cliente são obrigatórios.';
            header('Location: /orders/' . $orderId . '/edit');
            exit;
        }

        if (empty($items)) {
            $_SESSION['flash_error'] = 'Adicione pelo menos um produto ao pedido.';
            header('Location: /orders/' . $orderId . '/edit');
            exit;
        }

        if ($zoneId && $deliveryType === 'delivery') {
            require_once __DIR__ . '/../models/DeliveryZone.php';
            $zone = DeliveryZone::findForCompany($zoneId, $companyId);
            if ($zone) {
                $deliveryFee = (float)($zone['fee'] ?? 0);
            }
        } elseif (!empty($_POST['delivery_fee'])) {
            $deliveryFee = (float)str_replace(',', '.', $_POST['delivery_fee']);
        }

        if ($deliveryType === 'pickup') {
            $deliveryFee = 0;
            $customerAddress = '';
        }

        $subtotal = 0;
        $processedItems = [];
        $customizationJsons = $_POST['customization_data_json'] ?? [];

        foreach ($items as $idx => $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            $unitPrice = (float)($item['unit_price'] ?? 0);

            if ($productId && $quantity > 0) {
                $customizationData = null;
                $rawJson = $customizationJsons[$idx] ?? null;
                if ($rawJson) {
                    $decoded = json_decode($rawJson, true);
                    if (is_array($decoded)) {
                        $delta = (float)($decoded['total_delta'] ?? 0);
                        $unitPrice += $delta;
                        $customizationData = $decoded;
                    }
                }

                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                $processedItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'combo_data' => null,
                    'customization_data' => $customizationData,
                    'notes' => null,
                ];
            }
        }

        if (empty($processedItems)) {
            $_SESSION['flash_error'] = 'Nenhum item válido no pedido.';
            header('Location: /orders/' . $orderId . '/edit');
            exit;
        }

        $loyaltyDiscount = (float)($order['loyalty_discount'] ?? 0);
        $total = max(0, $subtotal + $deliveryFee - $discount - $loyaltyDiscount);

        $orderData = [
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_address' => $customerAddress,
            'payment_method_id' => $paymentMethodId ?: null,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'notes' => $notes,
        ];

        try {
            $updated = Order::updatePendingOrder($db, $orderId, $companyId, $orderData, $processedItems);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao atualizar pedido: ' . $e->getMessage();
            header('Location: /orders/' . $orderId . '/edit');
            exit;
        }

        if ($updated) {
            $_SESSION['flash_success'] = 'Pedido #' . $orderId . ' atualizado com sucesso!';
            header('Location: /orders/show?id=' . $orderId);
            exit;
        }

        $_SESSION['flash_error'] = 'Não foi possível atualizar o pedido.';
        header('Location: /orders/' . $orderId . '/edit');
        exit;
    }

    /**
     * POST /orders/{id}/del
     * Remove pedido
     */
    public function destroy(array $params = [])
    {
        [$u, $company] = $this->guard();

        $orderId = (int)($params['id'] ?? 0);
        $companyId = (int)$company['id'];
        $db = db();

        if ($orderId) {
            $order = Order::find($orderId, (int)$company['id']);
            if ($order && (int)$order['company_id'] === $companyId) {
                Order::delete($db, $orderId, $companyId);
                $_SESSION['flash_success'] = 'Pedido excluído com sucesso!';
            }
        }

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true]);
            return;
        }

        header('Location: /orders');
        exit;
    }

    /**
     * GET /orders/print?id=X
     * Gera PDF para impressão térmica
     */
    public function printPdf(array $params = [])
    {
        [$u, $company] = $this->guard();

        $db = db();
        $orderId = (int)($_GET['id'] ?? 0);
        $order = Order::findWithItems($db, $orderId, (int)$company['id']);

        if (!$order) {
            http_response_code(404);
            echo 'Pedido não encontrado';
            return;
        }

        try {
            $pdfPath = ThermalReceipt::generatePdf(
                $company,
                $order,
                $order['items'] ?? []
            );

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="pedido_' . $orderId . '.pdf"');
            header('Content-Length: ' . filesize($pdfPath));

            readfile($pdfPath);
            @unlink($pdfPath);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo 'Erro ao gerar PDF: ' . $e->getMessage();
        }
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

    protected function jsonResponse(array $data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
