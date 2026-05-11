<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Serviços específicos do controller
require_once __DIR__ . '/../services/OrderNotificationService.php';
require_once __DIR__ . '/../services/ThermalReceipt.php';

class AdminOrdersController extends Controller
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

    public function index($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $status = $_GET['status'] ?? null;
        $status = $status === '' ? null : $status;
        
        // Filtro por origem (manual, website, ifood)
        $source = $_GET['source'] ?? null;
        $source = $source === '' ? null : $source;
        
        // Busca por texto (nome, telefone, ID)
        $search = $_GET['q'] ?? null;
        $search = $search === '' ? null : $search;
        
        // Paginação
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }
        
        $offset = ($page - 1) * $perPage;
        $totalOrders = Order::countByCompany($db, (int)$company['id'], $status, $search, $source);
        $totalPages = max(1, (int)ceil($totalOrders / $perPage));
        
        $orders = Order::listByCompany($db, (int)$company['id'], $status, $perPage, $offset, $search, $source);

        return $this->view('admin/orders/index', [
            'orders'      => $orders,
            'status'      => $status,
            'source'      => $source,
            'search'      => $search,
            'company'     => $company,
            'activeSlug'  => $company['slug'],
            'pagination'  => [
                'page'       => $page,
                'perPage'    => $perPage,
                'total'      => $totalOrders,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    public function show($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($_GET['id'] ?? 0);
        $order = Order::findWithItems($db, $orderId, (int)$company['id']);

        if (!$order) {
            http_response_code(404);
            echo 'Pedido não encontrado';

            return;
        }

        // Carregar dados adicionais do iFood se for pedido de origem iFood
        $ifoodData = null;
        if (($order['source'] ?? '') === 'ifood' && !empty($order['ifood_order_id'])) {
            $stIfood = $db->prepare('SELECT * FROM ifood_orders WHERE ifood_order_id = ? AND company_id = ?');
            $stIfood->execute([$order['ifood_order_id'], (int)$company['id']]);
            $ifoodData = $stIfood->fetch(\PDO::FETCH_ASSOC);
        }

        // Buscar dados do método de pagamento para pedidos manuais/website
        $paymentMethodName         = null;
        $paymentMethodType         = null;
        $paymentMethodMeta         = null;
        $paymentMethodInstructions = null;
        if (!empty($order['payment_method_id'])) {
            $stPm = $db->prepare('SELECT name, type, meta, instructions FROM payment_methods WHERE id = ? AND company_id = ?');
            $stPm->execute([(int)$order['payment_method_id'], (int)$company['id']]);
            $pmRow = $stPm->fetch(\PDO::FETCH_ASSOC);
            if ($pmRow) {
                $paymentMethodName         = $pmRow['name'] ?? null;
                $paymentMethodType         = $pmRow['type'] ?? null;
                $paymentMethodInstructions = $pmRow['instructions'] ?? null;
                $rawMeta = $pmRow['meta'] ?? null;
                if ($rawMeta) {
                    $paymentMethodMeta = is_string($rawMeta) ? json_decode($rawMeta, true) : $rawMeta;
                }
            }
        }

        $orderEvents = Order::eventsForOrder($db, (int)$company['id'], $orderId, 50);

        return $this->view('admin/orders/show', [
            'order'                     => $order,
            'ifoodData'                 => $ifoodData,
            'paymentMethodName'         => $paymentMethodName,
            'paymentMethodType'         => $paymentMethodType,
            'paymentMethodMeta'         => $paymentMethodMeta,
            'paymentMethodInstructions' => $paymentMethodInstructions,
            'orderEvents'               => $orderEvents,
            'company'                   => $company,
            'activeSlug'                => $company['slug'],
        ]);
    }

    public function edit($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($params['id'] ?? 0);
        $order = Order::findWithItems($db, $orderId, (int)$company['id']);

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

        // Buscar produtos com categorias
        $products = Product::listByCompany((int)$company['id'], null, false);

        // Carregar grupos de personalização para cada produto
        require_once __DIR__ . '/../models/ProductCustomization.php';
        $customizationMap = [];
        foreach ($products as $prod) {
            $groups = ProductCustomization::loadForPublic((int)$prod['id']);
            if (!empty($groups)) {
                $customizationMap[(int)$prod['id']] = $groups;
            }
        }

        // Buscar categorias
        $stmt = $db->prepare("SELECT id, name FROM categories WHERE company_id = ? ORDER BY name");
        $stmt->execute([(int)$company['id']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar clientes
        $stmt = $db->prepare("SELECT id, name, whatsapp FROM customers WHERE company_id = ? ORDER BY name LIMIT 100");
        $stmt->execute([(int)$company['id']]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar cidades e bairros
        $stmt = $db->prepare("SELECT id, name FROM delivery_cities WHERE company_id = ? ORDER BY name");
        $stmt->execute([(int)$company['id']]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT id, city_id, neighborhood, fee FROM delivery_zones WHERE company_id = ? ORDER BY neighborhood");
        $stmt->execute([(int)$company['id']]);
        $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar zonas por cidade
        $zonesByCity = [];
        foreach ($zones as $zone) {
            $cityId = (int)$zone['city_id'];
            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = $zone;
        }

        // Buscar métodos de pagamento ativos
        $stmt = $db->prepare("SELECT id, name, type, active FROM payment_methods WHERE company_id = ? AND active = 1 ORDER BY name");
        $stmt->execute([(int)$company['id']]);
        $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $addressInfo = [
            'street' => '',
            'number' => '',
            'complement' => '',
            'neighborhood' => '',
            'city' => '',
        ];

        $rawAddress = trim((string)($order['customer_address'] ?? ''));
        if ($rawAddress !== '') {
            $lines = preg_split('/\r?\n/', $rawAddress);
            if (count($lines) > 1) {
                $line1 = trim((string)($lines[0] ?? ''));
                $line2 = trim((string)($lines[1] ?? ''));
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
                if ($normalize($zone['neighborhood'] ?? '') === $zoneNeedle) {
                    $selectedZoneId = (int)$zone['id'];
                    break;
                }
            }
        }

        $cashAmount = null;
        $notesRaw = (string)($order['notes'] ?? '');
        $notesClean = $notesRaw;
        if ($notesRaw !== '') {
            $lines = preg_split('/\r?\n/', $notesRaw);
            $cleanLines = [];
            foreach ($lines as $line) {
                $line = trim((string)$line);
                if ($line === '') {
                    continue;
                }
                if (preg_match('/^Troco para:\s*R\$\s*([\d.,]+)/i', $line, $matches)) {
                    $rawValue = str_replace(['R$', ' '], '', $matches[1]);
                    $rawValue = str_replace('.', '', $rawValue);
                    $rawValue = str_replace(',', '.', $rawValue);
                    $cashAmount = (float)$rawValue;
                    continue;
                }
                $cleanLines[] = $line;
            }
            $notesClean = trim(implode("\n", $cleanLines));
        }

        $deliveryType = empty($order['customer_address']) ? 'pickup' : 'delivery';

        $defaults = [
            'customer_name' => '',
            'customer_phone' => '',
            'notes' => '',
            'delivery_fee' => 0,
            'discount' => 0,
        ];

        $prefill = [
            'customer_name' => $order['customer_name'] ?? '',
            'customer_phone' => $order['customer_phone'] ?? '',
            'notes' => $notesClean,
            'delivery_fee' => (float)($order['delivery_fee'] ?? 0),
            'discount' => (float)($order['discount'] ?? 0),
            'delivery_type' => $deliveryType,
            'street' => $addressInfo['street'],
            'number' => $addressInfo['number'],
            'complement' => $addressInfo['complement'],
            'city_id' => $selectedCityId,
            'zone_id' => $selectedZoneId,
            'payment_method_id' => $order['payment_method_id'] ?? null,
            'cash_amount' => $cashAmount,
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

        return $this->view('admin/orders/form', [
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'cities' => $cities,
            'zonesByCity' => $zonesByCity,
            'paymentMethods' => $paymentMethods,
            'defaults' => $defaults,
            'prefill' => $prefill,
            'company' => $company,
            'activeSlug' => $company['slug'],
            'customizationMap' => $customizationMap,
            'isEdit' => true,
            'order' => $order,
            'initialItems' => $initialItems,
        ]);
    }

    public function setStatus($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($_POST['id'] ?? 0);
        $status  = $_POST['status'] ?? '';

        if (Order::updateStatus($db, $orderId, (int)$company['id'], $status)) {
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/orders/show?id=' . $orderId));
            exit;
        }
        http_response_code(400);
        echo 'Não foi possível atualizar o status';
    }

    public function create($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        // Buscar produtos com categorias
        $products = Product::listByCompany((int)$company['id'], null, false);

        // Carregar grupos de personalização para cada produto
        require_once __DIR__ . '/../models/ProductCustomization.php';
        $customizationMap = [];
        foreach ($products as $prod) {
            $groups = ProductCustomization::loadForPublic((int)$prod['id']);
            if (!empty($groups)) {
                $customizationMap[(int)$prod['id']] = $groups;
            }
        }
        
        // Buscar categorias
        $stmt = $db->prepare("SELECT id, name FROM categories WHERE company_id = ? ORDER BY name");
        $stmt->execute([(int)$company['id']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar clientes
        $stmt = $db->prepare("SELECT id, name, whatsapp FROM customers WHERE company_id = ? ORDER BY name LIMIT 100");
        $stmt->execute([(int)$company['id']]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar cidades e bairros
        $stmt = $db->prepare("SELECT id, name FROM delivery_cities WHERE company_id = ? ORDER BY name");
        $stmt->execute([(int)$company['id']]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("SELECT id, city_id, neighborhood, fee FROM delivery_zones WHERE company_id = ? ORDER BY neighborhood");
        $stmt->execute([(int)$company['id']]);
        $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agrupar zonas por cidade
        $zonesByCity = [];
        foreach ($zones as $zone) {
            $cityId = (int)$zone['city_id'];
            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = $zone;
        }
        
        // Buscar métodos de pagamento ativos
        $stmt = $db->prepare("SELECT id, name, type, active FROM payment_methods WHERE company_id = ? AND active = 1 ORDER BY name");
        $stmt->execute([(int)$company['id']]);
        $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $defaults = [
            'customer_name'  => '',
            'customer_phone' => '',
            'notes'          => '',
            'delivery_fee'   => 0,
            'discount'       => 0,
        ];

        return $this->view('admin/orders/form', [
            'products'         => $products,
            'categories'       => $categories,
            'customers'        => $customers,
            'cities'           => $cities,
            'zonesByCity'      => $zonesByCity,
            'paymentMethods'   => $paymentMethods,
            'defaults'         => $defaults,
            'company'          => $company,
            'activeSlug'       => $company['slug'],
            'customizationMap' => $customizationMap,
        ]);
    }

    public function store($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $customer_name  = trim($_POST['customer_name']  ?? '');
        $customer_phone = normalizePhone(trim($_POST['customer_phone'] ?? ''));
        $notes          = trim($_POST['notes']          ?? '');
        $delivery_fee   = (float)str_replace(',', '.', $_POST['delivery_fee'] ?? 0);
        $discount       = (float)str_replace(',', '.', $_POST['discount']     ?? 0);
        $delivery_type  = $_POST['delivery_type'] ?? 'delivery';

        // Pagamento
        $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;

        // Verificar tipo do pagamento para troco (dinheiro)
        $cashAmount = 0.0;
        $cashChange = 0.0;
        if ($payment_method_id) {
            $stPmType = $db->prepare('SELECT type FROM payment_methods WHERE id = ? LIMIT 1');
            $stPmType->execute([$payment_method_id]);
            $pmType = $stPmType->fetchColumn();
            if ($pmType === 'cash') {
                $cashAmount = (float)str_replace(',', '.', $_POST['cash_amount'] ?? '0');
            }
        }

        // Endereço
        $zone_id    = (int)($_POST['zone_id']    ?? 0);
        $street     = trim($_POST['street']      ?? '');
        $number     = trim($_POST['number']      ?? '');
        $complement = trim($_POST['complement']  ?? '');

        // Derivar taxa de entrega e bairro da zona selecionada
        $neighborhood = '';
        $city_name    = '';
        if ($zone_id) {
            $companyId = (int)$company['id'];
            require_once __DIR__ . '/../models/DeliveryZone.php';
            $zone = DeliveryZone::findForCompany($zone_id, $companyId);
            if ($zone) {
                $delivery_fee = (float)($zone['fee'] ?? $delivery_fee);
                $neighborhood = (string)($zone['neighborhood'] ?? '');
                $city_name    = (string)($zone['city_name'] ?? '');
            }
        }

        // Montar endereço formatado
        $addressParts = array_filter([$street, $number, $complement, $neighborhood, $city_name]);
        $customer_address = implode(', ', $addressParts) ?: null;

        // Se for retirada, zerar taxa de entrega e endereço
        if ($delivery_type === 'pickup') {
            $delivery_fee = 0;
            $customer_address = null;
        }

        $product_ids = $_POST['product_id'] ?? [];
        $quantities  = $_POST['quantity']   ?? [];
        $customization_jsons = $_POST['customization_data_json'] ?? [];

        if (!$customer_name) {
            http_response_code(400);
            echo 'Informe o nome do cliente.';
            return;
        }

        if ($customer_phone === '' || strlen(preg_replace('/[^0-9]/', '', $customer_phone)) < 10) {
            http_response_code(400);
            echo 'Informe o WhatsApp do cliente (com DDD). É necessário para contato sobre entrega.';
            return;
        }

        // OTIMIZAÇÃO: Buscar todos os produtos em uma única query (evita N+1)
        $validProductIds = array_filter(array_map('intval', $product_ids), fn($id) => $id > 0);
        $productsMap = Product::findByIds($validProductIds, (int)$company['id']);

        $items = [];
        $subtotal = 0.0;

        foreach ($product_ids as $i => $pid) {
            $pid = (int)$pid;
            $qty = (int)($quantities[$i] ?? 0);

            if ($pid <= 0 || $qty <= 0) {
                continue;
            }

            $prod = $productsMap[$pid] ?? null;

            if (!$prod) {
                continue;
            }

            $unit = (float)($prod['promo_price'] ?: $prod['price']);

            // Processar personalização se presente
            $customData = null;
            $extraDelta = 0.0;
            $customJson = $customization_jsons[$i] ?? null;
            if ($customJson) {
                $decoded = json_decode($customJson, true);
                if (is_array($decoded)) {
                    $customData = $decoded;
                    $extraDelta = (float)($decoded['total_delta'] ?? 0);
                }
            }
            $unit += $extraDelta;

            $line = $unit * $qty;

            $items[] = [
                'product_id'         => $pid,
                'quantity'           => $qty,
                'unit_price'         => $unit,
                'line_total'         => $line,
                'customization_data' => $customData,
            ];
            $subtotal += $line;
        }

        if (empty($items)) {
            http_response_code(400);
            echo 'Adicione ao menos um item.';

            return;
        }

        $total = max(0, $subtotal + $delivery_fee - $discount);

        // Adicionar informações de troco às observações (se pagamento em dinheiro)
        if ($cashAmount > 0.009) {
            $cashChange = max(0, $cashAmount - $total);
            $trocoLine = "Troco para: R$ " . number_format($cashAmount, 2, ',', '.');
            if ($cashChange > 0.009) {
                $trocoLine .= " (Troco: R$ " . number_format($cashChange, 2, ',', '.') . ")";
            } else {
                $trocoLine .= " (pagamento exato)";
            }
            $notes = $notes ? $notes . "\n" . $trocoLine : $trocoLine;
        }

        $companyId = (int)$company['id'];
        $orderId = Order::create($db, [
            'company_id'        => $companyId,
            'customer_name'     => $customer_name,
            'customer_phone'    => $customer_phone,
            'customer_address'  => $customer_address,
            'payment_method_id' => $payment_method_id,
            'subtotal'          => $subtotal,
            'delivery_fee'      => $delivery_fee,
            'discount'          => $discount,
            'total'             => $total,
            'status'            => 'pending',
            'notes'             => $notes,
            'source'            => 'manual',
        ]);

        foreach ($items as $it) {
            Order::addItem($db, $orderId, $it);
        }

        Order::emitOrderEvent($db, $orderId, $companyId, 'order.created');

        // Sync street to autocomplete system
        if ($delivery_type === 'delivery' && $street !== '' && $city_name !== '') {
            try {
                require_once __DIR__ . '/../services/AddressAutocompleteService.php';
                $acService = new \AddressAutocompleteService($db, $companyId);
                $acService->syncFromOrder($city_name, $neighborhood, $street, $customer_phone);
            } catch (\Throwable $e) {
                error_log("Admin syncFromOrder error: " . $e->getMessage());
            }
        }

        // Enviar notificação de novo pedido para grupos configurados
        try {
            // Resolver nome do método de pagamento para a notificação
            $paymentMethodNameForNotif = null;
            if ($payment_method_id) {
                $stPmNotif = $db->prepare('SELECT name FROM payment_methods WHERE id = ? LIMIT 1');
                $stPmNotif->execute([$payment_method_id]);
                $pmRowNotif = $stPmNotif->fetch(\PDO::FETCH_ASSOC);
                $paymentMethodNameForNotif = $pmRowNotif['name'] ?? null;
            }

            $orderData = [
                'id' => $orderId,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_address' => $customer_address,
                'delivery_type' => $delivery_type,
                'payment_method' => $paymentMethodNameForNotif,
                'cash_amount' => $cashAmount,
                'cash_change' => $cashChange,
                'total' => $total,
                'subtotal' => $subtotal,
                'delivery_fee' => $delivery_fee,
                'discount' => $discount,
                'items' => array_map(function($item) use ($productsMap) {
                    // Usar o mapa de produtos já carregado (evita N+1)
                    $product = $productsMap[$item['product_id']] ?? null;
                    return [
                        'name'             => $product['name'] ?? 'Produto',
                        'quantity'         => $item['quantity'],
                        'price'            => $item['unit_price'],
                        'customization_data' => $item['customization_data'] ?? null,
                    ];
                }, $items),
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            OrderNotificationService::sendOrderNotification($companyId, $orderData);
            
            // Enviar Web Push Notification para os dispositivos cadastrados
            try {
                require_once __DIR__ . '/../services/WebPushService.php';
                $webPushService = new \App\Services\WebPushService();
                $webPushService->notifyNewOrder($companyId, $orderData);
            } catch (\Throwable $e) {
                error_log("Erro ao enviar Web Push: " . $e->getMessage());
            }
        } catch (Exception $e) {
            // Log do erro mas não interrompe o fluxo do pedido
            error_log("Erro ao enviar notificação de pedido: " . $e->getMessage());
        }

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/orders/show?id=' . $orderId));
        exit;
    }

    public function update($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($params['id'] ?? 0);
        $order = Order::findBasic($db, $orderId, (int)$company['id']);

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

        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_phone = normalizePhone(trim($_POST['customer_phone'] ?? ''));
        $notes = trim($_POST['notes'] ?? '');
        $delivery_fee = (float)str_replace(',', '.', $_POST['delivery_fee'] ?? 0);
        $discount = (float)str_replace(',', '.', $_POST['discount'] ?? 0);
        $delivery_type = $_POST['delivery_type'] ?? 'delivery';

        $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;

        $cashAmount = 0.0;
        $cashChange = 0.0;
        if ($payment_method_id) {
            $stPmType = $db->prepare('SELECT type FROM payment_methods WHERE id = ? LIMIT 1');
            $stPmType->execute([$payment_method_id]);
            $pmType = $stPmType->fetchColumn();
            if ($pmType === 'cash') {
                $cashAmount = (float)str_replace(',', '.', $_POST['cash_amount'] ?? '0');
            }
        }

        $zone_id = (int)($_POST['zone_id'] ?? 0);
        $street = trim($_POST['street'] ?? '');
        $number = trim($_POST['number'] ?? '');
        $complement = trim($_POST['complement'] ?? '');

        $neighborhood = '';
        $city_name = '';
        if ($zone_id) {
            $companyId = (int)$company['id'];
            require_once __DIR__ . '/../models/DeliveryZone.php';
            $zone = DeliveryZone::findForCompany($zone_id, $companyId);
            if ($zone) {
                $delivery_fee = (float)($zone['fee'] ?? $delivery_fee);
                $neighborhood = (string)($zone['neighborhood'] ?? '');
                $city_name = (string)($zone['city_name'] ?? '');
            }
        }

        $addressParts = array_filter([$street, $number, $complement, $neighborhood, $city_name]);
        $customer_address = implode(', ', $addressParts) ?: null;

        if ($delivery_type === 'pickup') {
            $delivery_fee = 0;
            $customer_address = null;
        }

        $product_ids = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $customization_jsons = $_POST['customization_data_json'] ?? [];

        if (!$customer_name) {
            http_response_code(400);
            echo 'Informe o nome do cliente.';
            return;
        }

        if ($customer_phone === '' || strlen(preg_replace('/[^0-9]/', '', $customer_phone)) < 10) {
            http_response_code(400);
            echo 'Informe o WhatsApp do cliente (com DDD). É necessário para contato sobre entrega.';
            return;
        }

        $validProductIds = array_filter(array_map('intval', $product_ids), fn($id) => $id > 0);
        $productsMap = Product::findByIds($validProductIds, (int)$company['id']);

        $items = [];
        $subtotal = 0.0;

        foreach ($product_ids as $i => $pid) {
            $pid = (int)$pid;
            $qty = (int)($quantities[$i] ?? 0);

            if ($pid <= 0 || $qty <= 0) {
                continue;
            }

            $prod = $productsMap[$pid] ?? null;
            if (!$prod) {
                continue;
            }

            $unit = (float)($prod['promo_price'] ?: $prod['price']);

            $customData = null;
            $extraDelta = 0.0;
            $customJson = $customization_jsons[$i] ?? null;
            if ($customJson) {
                $decoded = json_decode($customJson, true);
                if (is_array($decoded)) {
                    $customData = $decoded;
                    $extraDelta = (float)($decoded['total_delta'] ?? 0);
                }
            }
            $unit += $extraDelta;

            $line = $unit * $qty;
            $items[] = [
                'product_id' => $pid,
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $line,
                'customization_data' => $customData,
            ];
            $subtotal += $line;
        }

        if (empty($items)) {
            http_response_code(400);
            echo 'Adicione ao menos um item.';
            return;
        }

        $loyaltyDiscount = (float)($order['loyalty_discount'] ?? 0);
        $total = max(0, $subtotal + $delivery_fee - $discount - $loyaltyDiscount);

        $notes = preg_replace('/^Troco para:.*$/mi', '', $notes);
        $notes = trim($notes);
        if ($cashAmount > 0.009) {
            $cashChange = max(0, $cashAmount - $total);
            $trocoLine = "Troco para: R$ " . number_format($cashAmount, 2, ',', '.');
            if ($cashChange > 0.009) {
                $trocoLine .= " (Troco: R$ " . number_format($cashChange, 2, ',', '.') . ")";
            } else {
                $trocoLine .= " (pagamento exato)";
            }
            $notes = $notes ? $notes . "\n" . $trocoLine : $trocoLine;
        }

        $orderData = [
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_address' => $customer_address,
            'payment_method_id' => $payment_method_id,
            'delivery_fee' => $delivery_fee,
            'discount' => $discount,
            'notes' => $notes,
        ];

        try {
            $updated = Order::updatePendingOrder($db, $orderId, (int)$company['id'], $orderData, $items);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'Erro ao atualizar pedido: ' . $e->getMessage();
            return;
        }

        if ($updated) {
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/orders/show?id=' . $orderId));
            exit;
        }

        http_response_code(400);
        echo 'Não foi possível atualizar o pedido.';
    }

    public function destroy($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($params['id'] ?? 0);

        if ($orderId > 0 && Order::delete($db, $orderId, (int)$company['id'])) {
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/orders'));
            exit;
        }

        http_response_code(400);
        echo 'Não foi possível excluir o pedido.';
    }

    public function printPdf($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($_GET['id'] ?? 0);
        $order = Order::findWithItems($db, $orderId, (int)$company['id']);

        if (!$order) {
            http_response_code(404);
            echo 'Pedido não encontrado';
            return;
        }

        try {
            // Gera o PDF usando o serviço ThermalReceipt
            $pdfPath = ThermalReceipt::generatePdf(
                $company,
                $order,
                $order['items'] ?? []
            );

            // Envia o PDF para o navegador
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="pedido_' . $orderId . '.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            
            readfile($pdfPath);
            
            // Remove o arquivo temporário
            @unlink($pdfPath);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo 'Erro ao gerar PDF: ' . $e->getMessage();
        }
    }
}
