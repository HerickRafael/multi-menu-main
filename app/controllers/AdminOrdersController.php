<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Serviços específicos do controller
require_once __DIR__ . '/../modules/auth/AdminGuard.php';
require_once __DIR__ . '/../modules/orders/OrderListService.php';
require_once __DIR__ . '/../modules/orders/OrderDetailsService.php';
require_once __DIR__ . '/../modules/orders/OrderStatusService.php';
require_once __DIR__ . '/../services/OrderNotificationService.php';
require_once __DIR__ . '/../services/ThermalReceipt.php';
require_once __DIR__ . '/../services/IFoodService.php';

class AdminOrdersController extends Controller
{
    /** Valida sessão, empresa e retorna [$u, $company] */
    private function guard(string $slug): array
    {
        return AdminGuard::requireCompanyAccess($slug);
    }

    private function buildOrdersList(int $companyId, ?string $status, ?string $source, ?string $search): array
    {
        $db = $this->db();

        // Mapeamento de status UI → status iFood
        $ifoodStatusMap = [
            'pending'    => 'PLACED',
            'confirmed'  => 'CONFIRMED',
            'ready'      => 'READY_TO_PICKUP',
            'dispatched' => 'DISPATCHED',
            'completed'  => 'CONCLUDED',
            'canceled'   => 'CANCELLED',
        ];

        // Mapeamento de status iFood → status UI normalizado
        $ifoodNormalizeMap = [
            'PLACED'          => 'pending',
            'CONFIRMED'       => 'confirmed',
            'READY_TO_PICKUP' => 'ready',
            'DISPATCHED'      => 'dispatched',
            'CONCLUDED'       => 'completed',
            'CANCELLED'       => 'canceled',
        ];

        // Mapeamento de status UI → DB status para pedidos regulares
        $regularStatusMap = [
            'pending'    => 'pending',
            'confirmed'  => 'paid',
            'ready'      => 'paid',
            'dispatched' => 'paid',
            'completed'  => 'completed',
            'canceled'   => 'canceled',
        ];

        $includeRegular = $source !== 'ifood';
        $includeIfood   = $source === null || $source === 'ifood';

        $allOrders = [];

        // ── Pedidos regulares ────────────────────────────────────────────────
        if ($includeRegular) {
            $dbStatus = $status !== null ? ($regularStatusMap[$status] ?? $status) : null;
            $result = OrderListService::listForCompany($db, $companyId, [
                'status'         => $dbStatus,
                'source'         => $source,
                'exclude_source' => ($includeIfood && $source === null) ? 'ifood' : null,
                'search'         => $search,
                'page'           => 1,
                'per_page'       => 200,
            ]);
            foreach ($result['orders'] as $o) {
                $rawStatus = (string)($o['status'] ?? 'pending');
                $allOrders[] = [
                    'id'             => (int)($o['id'] ?? 0),
                    'display_id'     => '#' . ($o['order_number'] ?? $o['id'] ?? 0),
                    'source'         => (string)($o['source'] ?? 'manual'),
                    'is_ifood'       => false,
                    'customer_name'  => (string)($o['customer_name'] ?? '-'),
                    'customer_phone' => (string)($o['customer_phone'] ?? ''),
                    'status'         => $rawStatus,
                    'status_raw'     => $rawStatus,
                    'total'          => (float)($o['total'] ?? 0),
                    'created_at'     => (string)($o['created_at'] ?? ''),
                    'items_count'    => (int)($o['items_qty'] ?? $o['items_count'] ?? 0),
                    'order_type'     => null,
                    'delivered_by'   => null,
                    'ifood_row_id'   => null,
                    'local_order_id' => null,
                ];
            }
        }

        // ── Pedidos iFood ────────────────────────────────────────────────────
        if ($includeIfood) {
            $ifoodStatus = $status !== null ? ($ifoodStatusMap[$status] ?? null) : null;
            $ifoodService = new IFoodService($db, $companyId);
            $ifoodRaw = $ifoodService->getOrders($ifoodStatus, 200);
            foreach ($ifoodRaw as $o) {
                $rawIfoodStatus = (string)($o['status'] ?? 'PLACED');
                $normalizedStatus = $ifoodNormalizeMap[$rawIfoodStatus] ?? 'pending';

                if ($search !== null) {
                    $haystack = strtolower(
                        ($o['ifood_display_id'] ?? '') . ' ' .
                        ($o['customer_name'] ?? '') . ' ' .
                        ($o['customer_phone'] ?? '')
                    );
                    if (!str_contains($haystack, strtolower($search))) {
                        continue;
                    }
                }

                $allOrders[] = [
                    'id'             => (int)($o['id'] ?? 0),
                    'display_id'     => (string)($o['ifood_display_id'] ?? ('IF-' . ($o['id'] ?? 0))),
                    'source'         => 'ifood',
                    'is_ifood'       => true,
                    'customer_name'  => (string)($o['customer_name'] ?? '-'),
                    'customer_phone' => (string)($o['customer_phone'] ?? ''),
                    'status'         => $normalizedStatus,
                    'status_raw'     => $rawIfoodStatus,
                    'total'          => (float)($o['total_amount'] ?? 0),
                    'created_at'     => (string)($o['created_at'] ?? ''),
                    'items_count'    => 0,
                    'order_type'     => (string)($o['order_type'] ?? ''),
                    'delivered_by'   => (string)($o['delivered_by'] ?? ''),
                    'ifood_row_id'   => (int)($o['id'] ?? 0),
                    'local_order_id' => isset($o['local_order_id']) ? (int)$o['local_order_id'] : null,
                ];
            }
        }

        usort($allOrders, static function (array $a, array $b): int {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $allOrders;
    }

    public function poll($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$u, $company] = $this->guard($slug);

        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (string)$_GET['status'] : null;
        $source = isset($_GET['source']) && $_GET['source'] !== '' ? (string)$_GET['source'] : null;
        $search = isset($_GET['q'])      && $_GET['q']      !== '' ? (string)$_GET['q']      : null;

        $orders = $this->buildOrdersList((int)$company['id'], $status, $source, $search);

        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode([
            'orders' => $orders,
            'server_time' => gmdate('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function index($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();
        $forceLegacy = (string)($_GET['legacy'] ?? '') === '1';

        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (string)$_GET['status'] : null;
        $source = isset($_GET['source']) && $_GET['source'] !== '' ? (string)$_GET['source'] : null;
        $search = isset($_GET['q'])      && $_GET['q']      !== '' ? (string)$_GET['q']      : null;

        $allOrders = $this->buildOrdersList((int)$company['id'], $status, $source, $search);

        if (!$forceLegacy) {
            $payload = [
                'orders'         => $allOrders,
                'current_status' => $status,
                'current_source' => $source,
                'status_labels'  => [
                    'pending'    => 'Novo',
                    'confirmed'  => 'Confirmado',
                    'ready'      => 'Pronto',
                    'dispatched' => 'Em Entrega',
                    'completed'  => 'Concluído',
                    'canceled'   => 'Cancelado',
                ],
                'urls' => [
                    'list'              => '/admin/' . rawurlencode($slug) . '/orders',
                    'create'            => '/admin/' . rawurlencode($slug) . '/orders/create',
                    'show_base'         => '/admin/' . rawurlencode($slug) . '/orders/show?id=',
                    'edit_base'         => '/admin/' . rawurlencode($slug) . '/orders/',
                    'ifood_action_base' => '/admin/' . rawurlencode($slug) . '/ifood/orders/',
                    'ifood_poll'        => '/admin/' . rawurlencode($slug) . '/ifood/poll',
                    'ifood_config'      => '/admin/' . rawurlencode($slug) . '/ifood/config',
                    'local_order_base'  => '/admin/' . rawurlencode($slug) . '/orders/show?id=',
                    'poll'              => '/admin/' . rawurlencode($slug) . '/orders/poll',
                    'notification_sound' => '/audio/notification.mp3',
                ],
            ];

            \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_ORDERS__', $payload);
            return;
        }

        // Legacy view (fallback)
        $orders   = $allOrders;
        $pagination = ['page' => 1, 'perPage' => count($orders), 'total' => count($orders), 'totalPages' => 1];

        return $this->view('admin/orders/index', [
            'orders'      => $orders,
            'status'      => $status,
            'source'      => $source,
            'search'      => $search,
            'company'     => $company,
            'activeSlug'  => $company['slug'],
            'pagination'  => $pagination,
        ]);
    }

    public function show($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($_GET['id'] ?? 0);
        $details = OrderDetailsService::loadAdminDetails($db, (int)$company['id'], $orderId);

        if (!$details) {
            http_response_code(404);
            echo 'Pedido não encontrado';

            return;
        }

        $order = $details['order'];
        $ifoodData = $details['ifoodData'];
        $paymentMethodName = $details['paymentMethodName'];
        $paymentMethodType = $details['paymentMethodType'];
        $paymentMethodMeta = $details['paymentMethodMeta'];
        $paymentMethodInstructions = $details['paymentMethodInstructions'];
        $orderEvents = $details['orderEvents'];
        $forceLegacy = (string)($_GET['legacy'] ?? '') === '1';

        if ($forceLegacy) {
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

        // ── Build SPA payload ─────────────────────────────────────────────────
        $decodeJson = static function ($value) {
            if (is_array($value)) return $value;
            if (!is_string($value) || $value === '') return null;
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : null;
        };

        $items = is_array($order['items'] ?? null) ? $order['items'] : [];
        $itemsPayload = [];
        foreach ($items as $it) {
            $itemsPayload[] = [
                'id'                  => isset($it['id']) ? (int)$it['id'] : null,
                'product_name'        => (string)($it['product_name'] ?? ''),
                'quantity'            => (int)($it['quantity'] ?? 0),
                'unit_price'          => (float)($it['unit_price'] ?? 0),
                'line_total'          => (float)($it['line_total'] ?? 0),
                'notes'               => (string)($it['notes'] ?? ''),
                'combo_data'          => $decodeJson($it['combo_data'] ?? null),
                'customization_data'  => $decodeJson($it['customization_data'] ?? null),
            ];
        }

        $ifoodPayload = null;
        if (is_array($ifoodData) && !empty($ifoodData)) {
            $ifoodPayload = [
                'ifood_order_id'      => (string)($ifoodData['ifood_order_id'] ?? ($order['ifood_order_id'] ?? '')),
                'ifood_display_id'    => (string)($ifoodData['ifood_display_id'] ?? ''),
                'status'              => (string)($ifoodData['status'] ?? ''),
                'order_type'          => (string)($ifoodData['order_type'] ?? ''),
                'order_timing'        => (string)($ifoodData['order_timing'] ?? ''),
                'scheduled_datetime'  => $ifoodData['scheduled_datetime'] ?? null,
                'customer_document'   => $ifoodData['customer_document'] ?? null,
                'pickup_code'         => $ifoodData['pickup_code'] ?? null,
                'delivered_by'        => $ifoodData['delivered_by'] ?? null,
                'delivery_address'    => $decodeJson($ifoodData['delivery_address'] ?? null),
                'payments'            => $decodeJson($ifoodData['payments'] ?? null),
                'benefits'            => $decodeJson($ifoodData['benefits'] ?? null),
                'raw_data'            => $decodeJson($ifoodData['raw_data'] ?? null),
                'cancellation_reason' => $ifoodData['cancellation_reason'] ?? null,
                'created_at'          => $ifoodData['created_at'] ?? null,
            ];
        }

        // WhatsApp link
        $wa = null;
        if (!empty($order['customer_phone'])) {
            $digits = preg_replace('/\D+/', '', (string)$order['customer_phone']);
            if ($digits) {
                if (!str_starts_with($digits, '55')) {
                    $digits = '55' . $digits;
                }
                $orderNumber = (int)($order['order_number'] ?? $order['id'] ?? 0);
                $waText = rawurlencode('Olá! Sobre o pedido #' . $orderNumber . '.');
                $wa = "https://wa.me/{$digits}?text={$waText}";
            }
        }

        $orderId = (int)($order['id'] ?? 0);
        $payload = [
            'order' => [
                'id'                  => $orderId,
                'order_number'        => (int)($order['order_number'] ?? $order['id'] ?? 0),
                'status'              => (string)($order['status'] ?? 'pending'),
                'source'              => (string)($order['source'] ?? 'manual'),
                'customer_name'       => (string)($order['customer_name'] ?? ''),
                'customer_phone'      => (string)($order['customer_phone'] ?? ''),
                'customer_address'    => (string)($order['customer_address'] ?? ''),
                'notes'               => (string)($order['notes'] ?? ''),
                'subtotal'            => (float)($order['subtotal'] ?? 0),
                'delivery_fee'        => (float)($order['delivery_fee'] ?? 0),
                'discount'            => (float)($order['discount'] ?? 0),
                'loyalty_discount'    => (float)($order['loyalty_discount'] ?? 0),
                'total'               => (float)($order['total'] ?? 0),
                'payment_method_id'   => isset($order['payment_method_id']) ? (int)$order['payment_method_id'] : null,
                'created_at'          => (string)($order['created_at'] ?? ''),
                'ifood_order_id'      => $order['ifood_order_id'] ?? null,
                'items'               => $itemsPayload,
            ],
            'payment' => [
                'name'         => $paymentMethodName,
                'type'         => $paymentMethodType,
                'meta'         => is_string($paymentMethodMeta) ? $decodeJson($paymentMethodMeta) : $paymentMethodMeta,
                'instructions' => $paymentMethodInstructions,
            ],
            'ifood'          => $ifoodPayload,
            'events'         => $orderEvents,
            'status_labels'  => [
                'pending'   => 'Aguardando',
                'paid'      => 'Confirmado',
                'completed' => 'Concluído',
                'canceled'  => 'Cancelado',
            ],
            'whatsapp_url'   => $wa,
            'urls' => [
                'list'       => '/admin/' . rawurlencode($slug) . '/orders',
                'set_status' => '/admin/' . rawurlencode($slug) . '/orders/setStatus',
                'edit'       => '/admin/' . rawurlencode($slug) . '/orders/' . $orderId . '/edit',
                'delete'     => '/admin/' . rawurlencode($slug) . '/orders/' . $orderId . '/del',
                'print'      => '/admin/' . rawurlencode($slug) . '/orders/print?id=' . $orderId,
                'legacy'     => '/admin/' . rawurlencode($slug) . '/orders/show?id=' . $orderId . '&legacy=1',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_ORDER__', $payload);
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
                'product_name' => (string)($item['product_name'] ?? ''),
                'customization_data' => is_array($customData) ? $customData : null,
            ];
        }

        $forceLegacy = (string)($_GET['legacy'] ?? '') === '1';
        if ($forceLegacy) {
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

        $slugEnc = rawurlencode((string)$company['slug']);
        $payload = [
            'is_edit' => true,
            'order_id' => (int)$order['id'],
            'order_number' => (int)($order['order_number'] ?? $order['id']),
            'products' => array_map(static function (array $p): array {
                return [
                    'id' => (int)$p['id'],
                    'name' => (string)($p['name'] ?? ''),
                    'description' => (string)($p['description'] ?? ''),
                    'sku' => (string)($p['sku'] ?? ''),
                    'price' => isset($p['price']) ? (float)$p['price'] : 0.0,
                    'promo_price' => isset($p['promo_price']) && $p['promo_price'] !== null && $p['promo_price'] !== ''
                        ? (float)$p['promo_price']
                        : null,
                    'category_id' => isset($p['category_id']) && $p['category_id'] !== null
                        ? (int)$p['category_id']
                        : null,
                    'category_name' => (string)($p['category_name'] ?? ''),
                    'image' => (string)($p['image'] ?? ''),
                    'type' => (string)($p['type'] ?? 'simple'),
                    'active' => (int)($p['active'] ?? 0) === 1,
                ];
            }, $products),
            'categories' => array_map(static function (array $c): array {
                return ['id' => (int)$c['id'], 'name' => (string)($c['name'] ?? '')];
            }, $categories),
            'customers' => array_map(static function (array $c): array {
                return [
                    'id' => (int)$c['id'],
                    'name' => (string)($c['name'] ?? ''),
                    'whatsapp' => (string)($c['whatsapp'] ?? ''),
                ];
            }, $customers),
            'cities' => array_map(static function (array $c): array {
                return ['id' => (int)$c['id'], 'name' => (string)($c['name'] ?? '')];
            }, $cities),
            'zones_by_city' => array_map(static function (array $list): array {
                return array_map(static function (array $z): array {
                    return [
                        'id' => (int)$z['id'],
                        'city_id' => (int)$z['city_id'],
                        'neighborhood' => (string)($z['neighborhood'] ?? ''),
                        'fee' => (float)($z['fee'] ?? 0),
                    ];
                }, $list);
            }, $zonesByCity),
            'payment_methods' => array_map(static function (array $pm): array {
                return [
                    'id' => (int)$pm['id'],
                    'name' => (string)($pm['name'] ?? ''),
                    'type' => (string)($pm['type'] ?? ''),
                ];
            }, $paymentMethods),
            'defaults' => $defaults,
            'prefill' => [
                'customer_name' => (string)$prefill['customer_name'],
                'customer_phone' => (string)$prefill['customer_phone'],
                'notes' => (string)$prefill['notes'],
                'delivery_fee' => (float)$prefill['delivery_fee'],
                'discount' => (float)$prefill['discount'],
                'delivery_type' => (string)$prefill['delivery_type'],
                'street' => (string)$prefill['street'],
                'number' => (string)$prefill['number'],
                'complement' => (string)$prefill['complement'],
                'city_id' => $prefill['city_id'],
                'zone_id' => $prefill['zone_id'],
                'payment_method_id' => $prefill['payment_method_id'] !== null ? (int)$prefill['payment_method_id'] : null,
                'cash_amount' => $prefill['cash_amount'],
                'items' => $initialItems,
            ],
            'urls' => [
                'list' => '/admin/' . $slugEnc . '/orders',
                'submit' => '/admin/' . $slugEnc . '/orders/' . (int)$order['id'],
                'show' => '/admin/' . $slugEnc . '/orders/show?id=' . (int)$order['id'],
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render((string)$company['slug'], $company, '__ADMIN_STORE_ORDER_FORM__', $payload);
    }

    public function setStatus($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $orderId = (int)($_POST['id'] ?? 0);
        $status  = $_POST['status'] ?? '';

        $result = OrderStatusService::updateForCompany($db, (int)$company['id'], $orderId, $status);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if (!empty($result['ok'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso',
                    'status'  => $result['internal_status'] ?? $status,
                ]);
            } else {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => $result['error'] ?? 'Não foi possível atualizar o status',
                ]);
            }
            exit;
        }

        // Fallback para formulários PHP legados
        if (!empty($result['ok'])) {
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/orders/show?id=' . $orderId));
            exit;
        }

        http_response_code(400);
        echo $result['error'] ?? 'Não foi possível atualizar o status';
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

        $payload = [
            'products' => array_map(static function (array $p): array {
                return [
                    'id' => (int)$p['id'],
                    'name' => (string)($p['name'] ?? ''),
                    'description' => (string)($p['description'] ?? ''),
                    'sku' => (string)($p['sku'] ?? ''),
                    'price' => isset($p['price']) ? (float)$p['price'] : 0.0,
                    'promo_price' => isset($p['promo_price']) && $p['promo_price'] !== null && $p['promo_price'] !== ''
                        ? (float)$p['promo_price']
                        : null,
                    'category_id' => isset($p['category_id']) && $p['category_id'] !== null
                        ? (int)$p['category_id']
                        : null,
                    'category_name' => (string)($p['category_name'] ?? ''),
                    'image' => (string)($p['image'] ?? ''),
                    'type' => (string)($p['type'] ?? 'simple'),
                    'active' => (int)($p['active'] ?? 0) === 1,
                ];
            }, $products),
            'categories' => array_map(static function (array $c): array {
                return ['id' => (int)$c['id'], 'name' => (string)($c['name'] ?? '')];
            }, $categories),
            'customers' => array_map(static function (array $c): array {
                return [
                    'id' => (int)$c['id'],
                    'name' => (string)($c['name'] ?? ''),
                    'whatsapp' => (string)($c['whatsapp'] ?? ''),
                ];
            }, $customers),
            'cities' => array_map(static function (array $c): array {
                return ['id' => (int)$c['id'], 'name' => (string)($c['name'] ?? '')];
            }, $cities),
            'zones_by_city' => array_map(static function (array $list): array {
                return array_map(static function (array $z): array {
                    return [
                        'id' => (int)$z['id'],
                        'city_id' => (int)$z['city_id'],
                        'neighborhood' => (string)($z['neighborhood'] ?? ''),
                        'fee' => (float)($z['fee'] ?? 0),
                    ];
                }, $list);
            }, $zonesByCity),
            'payment_methods' => array_map(static function (array $pm): array {
                return [
                    'id' => (int)$pm['id'],
                    'name' => (string)($pm['name'] ?? ''),
                    'type' => (string)($pm['type'] ?? ''),
                ];
            }, $paymentMethods),
            'defaults' => $defaults,
            'urls' => [
                'list' => '/admin/' . rawurlencode((string)$company['slug']) . '/orders',
                'submit' => '/admin/' . rawurlencode((string)$company['slug']) . '/orders',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render((string)$company['slug'], $company, '__ADMIN_STORE_ORDER_FORM__', $payload);
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
