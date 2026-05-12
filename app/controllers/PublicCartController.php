<?php

declare(strict_types=1);
// app/controllers/PublicCartController.php

// Iniciar buffer de output para evitar problemas com headers
ob_start();

require_once __DIR__ . '/../../vendor/autoload.php';

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Serviços e modelos específicos
require_once __DIR__ . '/../services/CartStorage.php';
require_once __DIR__ . '/../models/ProductCustomization.php';
require_once __DIR__ . '/../models/DeliveryCity.php';
require_once __DIR__ . '/../models/DeliveryZone.php';
require_once __DIR__ . '/../services/OrderNotificationService.php';
require_once __DIR__ . '/../modules/checkout/CheckoutTotalsService.php';
require_once __DIR__ . '/../helpers/OrderItemData.php';
require_once __DIR__ . '/../helpers/CheckoutSuccessOrder.php';
require_once __DIR__ . '/../helpers/CheckoutSuccessMessageBuilder.php';

class PublicCartController extends Controller
{
    /** @var CartStorage */
    private $storage;

    public function __construct()
    {
        $this->storage = CartStorage::instance();
    }

    /** Sanitiza e copia dados de personalização salvos na sessão */
    private function snapshotCustomization(int $productId, ?int $parentId = null, ?int $unitIndex = null): ?array
    {
        $raw = $this->storage->getCustomization($productId, null, $parentId, $unitIndex);

        if (!$raw || !is_array($raw)) {
            return null;
        }
        $result = [
            'single'    => [],
            'singleQty' => [],
            'choice'    => [],
            'qty'       => [],
        ];

        if (isset($raw['single']) && is_array($raw['single'])) {
            foreach ($raw['single'] as $g => $data) {
                // Suportar tanto formato antigo (int) quanto novo (array de índices)
                if (is_array($data)) {
                    $result['single'][(int)$g] = array_map('intval', $data);
                } else {
                    $result['single'][(int)$g] = [(int)$data];
                }
            }
        }

        // Copiar quantidades dos itens single (singleQty)
        // Formato: singleQty[$gi][$itemIndex] = qty
        if (isset($raw['singleQty']) && is_array($raw['singleQty'])) {
            foreach ($raw['singleQty'] as $g => $qtyData) {
                if (is_array($qtyData)) {
                    // Novo formato: array associativo [$itemIndex => $qty]
                    $result['singleQty'][(int)$g] = [];
                    foreach ($qtyData as $idx => $qty) {
                        $result['singleQty'][(int)$g][(int)$idx] = max(1, (int)$qty);
                    }
                } else {
                    // Formato antigo: valor único (manter compatibilidade)
                    $result['singleQty'][(int)$g] = max(1, (int)$qtyData);
                }
            }
        }

        if (isset($raw['choice']) && is_array($raw['choice'])) {
            foreach ($raw['choice'] as $g => $vals) {
                if (!is_array($vals)) {
                    continue;
                }
                $clean = [];

                foreach ($vals as $val) {
                    $clean[] = (int)$val;
                }
                $result['choice'][(int)$g] = array_values(array_unique($clean));
            }
        }

        if (isset($raw['qty']) && is_array($raw['qty'])) {
            foreach ($raw['qty'] as $g => $items) {
                if (!is_array($items)) {
                    continue;
                }

                foreach ($items as $i => $qty) {
                    $qtyInt = (int)$qty;

                    if ($qtyInt < 0) {
                        continue;
                    }
                    // Incluir qty=0 para preservar remoções de ingredientes (ex: "sem cebola")
                    $result['qty'][(int)$g][(int)$i] = $qtyInt;
                }
            }
        }

        if (isset($raw['quantity'])) {
            $quantity = max(1, (int)$raw['quantity']);
            $result['quantity'] = $quantity;
        }

        // Remove se nada foi preenchido
        if (!$result['single'] && !$result['choice'] && !$result['qty'] && empty($result['quantity'])) {
            return null;
        }

        return $result;
    }

    /** Monta snapshot com os itens padrões configurados no produto */
    private function defaultCustomizationSnapshot(int $productId): ?array
    {
        $mods = ProductCustomization::loadForPublic($productId);

        if (!$mods) {
            return null;
        }

        $snapshot = [
            'single'    => [],
            'singleQty' => [],
            'choice'    => [],
            'qty'       => [],
        ];

        $hasData = false;

        foreach ($mods as $gi => $group) {
            $type = DataValidator::getString($group, 'type', 'extra');
            $items = DataValidator::getArray($group, 'items');

            if (!$items) {
                continue;
            }

            if ($type === 'single') {
                $selectedIndex = null;
                $selectedQty = 1;

                foreach ($items as $ii => $item) {
                    if (!empty($item['default'])) {
                        $selectedIndex = $ii;
                        // Pegar quantidade default se existir
                        if (!empty($item['default_qty'])) {
                            $selectedQty = max(1, (int)$item['default_qty']);
                        }
                        break;
                    }
                }

                // Só adicionar ao snapshot se houver item marcado como default
                if ($selectedIndex !== null) {
                    // Usar formato de array para consistência com o novo formato
                    $snapshot['single'][(int)$gi] = [(int)$selectedIndex];
                    $snapshot['singleQty'][(int)$gi] = [(int)$selectedIndex => $selectedQty];
                    $hasData = true;
                }
            } elseif ($type === 'addon') {
                $selected = [];

                foreach ($items as $ii => $item) {
                    if (!empty($item['default']) || !empty($item['selected'])) {
                        $selected[] = (int)$ii;
                    }
                }

                if ($selected) {
                    $snapshot['choice'][(int)$gi] = array_values(array_unique($selected));
                    $hasData = true;
                }
            } else {
                $qtyItems = [];

                foreach ($items as $ii => $item) {
                    $qty = isset($item['qty']) ? (int)$item['qty'] : 0;

                    if ($qty <= 0 && !empty($item['default_qty'])) {
                        $qty = (int)$item['default_qty'];
                    }

                    if ($qty <= 0) {
                        continue;
                    }
                    $qtyItems[(int)$ii] = $qty;
                }

                if ($qtyItems) {
                    $snapshot['qty'][(int)$gi] = $qtyItems;
                    $hasData = true;
                }
            }
        }

        return $hasData ? $snapshot : null;
    }

    /**
     * Resolve seleção postada dos grupos do combo (ids dos produtos simples)
     * Retorna mapa group_id => simple_id|array
     */
    private function resolveComboSelection(array $product, array $postData): array
    {
        $selection = [];

        if (($product['type'] ?? 'simple') !== 'combo') {
            return $selection;
        }

        $groups = Product::getComboGroupsWithItems((int)$product['id']);

        foreach ($groups as $index => $group) {
            $groupId = DataValidator::getInt($group, 'id');

            if ($groupId <= 0) {
                continue;
            }

            $items = DataValidator::getArray($group, 'items');

            if (!$items) {
                continue;
            }

            $minQty = DataValidator::getInt($group, 'min', 'min_qty');
            $rawValue = $postData[$index] ?? null;
            $selectedIds = [];

            if (is_array($rawValue)) {
                foreach ($rawValue as $val) {
                    $selectedIds[] = (int)$val;
                }
            } elseif ($rawValue !== null && $rawValue !== '') {
                $selectedIds[] = (int)$rawValue;
            }

            if (!$selectedIds) {
                if ($minQty > 0) {
                    foreach ($items as $item) {
                        if (!empty($item['default'])) {
                            $selectedIds[] = (int)$item['id'];
                        }
                    }

                    if (!$selectedIds && $items) {
                        $selectedIds[] = (int)$items[0]['id'];
                    }
                } else {
                    continue;
                }
            }

            $simpleChosen = [];

            foreach ($items as $item) {
                $comboItemId = (int)$item['id'];

                if (!in_array($comboItemId, $selectedIds, true)) {
                    continue;
                }
                $simpleId = DataValidator::getInt($item, 'simple_id', 'simple_product_id');

                if ($simpleId <= 0) {
                    continue;
                }
                $simpleChosen[] = $simpleId;
            }

            if (!$simpleChosen) {
                continue;
            }

            $max = DataValidator::getInt($group, 'max', 'max_qty');
            if ($max === 0) {
                $max = 1; // Default para 1 se ambos forem 0
            }

            if ($max > 0 && count($simpleChosen) > $max) {
                $simpleChosen = array_slice($simpleChosen, 0, $max);
            }

            $selection[$groupId] = count($simpleChosen) === 1 ? $simpleChosen[0] : array_values($simpleChosen);
        }

        return $selection;
    }

    /** Gera identificador curto para o item da sacola */
    private function generateUid(): string
    {
        return bin2hex(random_bytes(6));
    }

    /** Formata endereço completo em linhas para exibir no pedido */
    private function formatOrderAddress(array $address): string
    {
        $parts = [];

        $line1 = trim(DataValidator::getString($address, 'street'));
        $number = trim(DataValidator::getString($address, 'number'));

        if ($number !== '') {
            $line1 = $line1 !== '' ? $line1 . ', ' . $number : $number;
        }
        $complement = trim(DataValidator::getString($address, 'complement'));

        if ($complement !== '') {
            $line1 = $line1 !== '' ? $line1 . ' - ' . $complement : $complement;
        }

        if ($line1 !== '') {
            $parts[] = $line1;
        }

        $line2Segments = [];

        $neighborhood = DataValidator::getString($address, 'neighborhood');
        if ($neighborhood !== '') {
            $line2Segments[] = trim($neighborhood);
        }
        $city = trim(DataValidator::getString($address, 'city'));

        if ($city !== '') {
            $line2Segments[] = $city;
        }

        if ($line2Segments) {
            $parts[] = implode(' - ', $line2Segments);
        }

        $reference = trim(DataValidator::getString($address, 'reference'));

        if ($reference !== '') {
            $parts[] = 'Referência: ' . $reference;
        }

        return implode("\n", array_filter($parts, static fn ($line) => $line !== ''));
    }

    /** Persiste endereço no pedido, ignorando caso a coluna não exista */
    private function persistOrderAddress(PDO $db, int $orderId, ?string $address): void
    {
        $address = $address !== null ? trim($address) : '';

        if ($address === '') {
            return;
        }

        try {
            $stmt = $db->prepare('UPDATE orders SET customer_address = :addr WHERE id = :id');
            $stmt->execute([
                ':addr' => $address,
                ':id'   => $orderId,
            ]);
        } catch (Throwable $e) {
            // Coluna opcional não existe; segue sem interromper fluxo
        }
    }

    /** Monta estrutura pronta para renderização e cálculo */
    private function hydrateCartItems(array $rawItems, array $company): array
    {
        $hydrated = [];

        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            if ((int)($item['company_id'] ?? 0) !== (int)($company['id'] ?? 0)) {
                continue;
            }

            $productId = (int)($item['product_id'] ?? 0);

            if ($productId <= 0) {
                continue;
            }

            $product = Product::find($productId, true, (int)$company['id']);

            if (!$product || (int)($product['company_id'] ?? 0) !== (int)($company['id'] ?? 0)) {
                continue;
            }

            if (!empty($product['active']) && (int)$product['active'] !== 1) {
                continue;
            }

            $qty = max(1, min(99, (int)($item['qty'] ?? 1)));

            $comboMap = isset($item['combo']) && is_array($item['combo']) ? $item['combo'] : [];
            $comboData = $this->expandComboData($product, $comboMap);

            $customData = isset($item['customization']) && is_array($item['customization'])
                ? $item['customization']
                : $this->defaultCustomizationSnapshot($productId);
            $baseCustomization = $this->expandCustomization($productId, $customData);

            $componentCustomizations = [];
            $componentExtra = 0.0;

            if ($comboData['selected_items']) {
                foreach ($comboData['selected_items'] as $selected) {
                    $simpleId = (int)($selected['simple_id'] ?? 0);

                    if ($simpleId <= 0) {
                        continue;
                    }
                    
                    $rawCustom = null;
                    $unitCustomizations = null;
                    $itemQty = (int)($selected['qty'] ?? $selected['default_qty'] ?? 1);

                    // Buscar em component_customizations (novo formato - prioridade)
                    if (isset($item['component_customizations'][$simpleId]) && is_array($item['component_customizations'][$simpleId])) {
                        $compData = $item['component_customizations'][$simpleId];
                        
                        // Verificar se tem personalizações por unidade
                        if (isset($compData['unit_customizations']) && is_array($compData['unit_customizations'])) {
                            $unitCustomizations = [];
                            foreach ($compData['unit_customizations'] as $unitNum => $unitSnap) {
                                $expandedUnit = $this->expandCustomization($simpleId, $unitSnap);
                                $unitCustomizations[$unitNum] = $expandedUnit;
                                // Somar o extra de cada unidade personalizada
                                $componentExtra += $expandedUnit['total_delta'];
                            }
                        } elseif (isset($compData['customization']) && is_array($compData['customization'])) {
                            $rawCustom = $compData['customization'];
                        } else {
                            // O próprio $compData é a personalização
                            $rawCustom = $compData;
                        }
                    }
                    // Fallback: buscar em combo_customizations (formato legado ou novo)
                    elseif (isset($item['combo_customizations']) && is_array($item['combo_customizations']) && array_key_exists($simpleId, $item['combo_customizations'])) {
                        $comboCompData = $item['combo_customizations'][$simpleId];
                        
                        // Verificar se tem unit_customizations dentro de combo_customizations
                        if (isset($comboCompData['unit_customizations']) && is_array($comboCompData['unit_customizations'])) {
                            $unitCustomizations = [];
                            foreach ($comboCompData['unit_customizations'] as $unitNum => $unitSnap) {
                                $expandedUnit = $this->expandCustomization($simpleId, $unitSnap);
                                $unitCustomizations[$unitNum] = $expandedUnit;
                                $componentExtra += $expandedUnit['total_delta'];
                            }
                        } else {
                            // Formato legado: o próprio valor é a personalização
                            $rawCustom = $comboCompData;
                        }
                    }

                    // Se não tem unit_customizations, processar normalmente
                    if ($unitCustomizations === null) {
                        if ($rawCustom === null) {
                            $rawCustom = $this->defaultCustomizationSnapshot($simpleId);
                        }
                        $expanded = $this->expandCustomization($simpleId, $rawCustom);

                        if ($expanded['has_customization']) {
                            $componentCustomizations[$simpleId] = [
                                'component' => $selected,
                                'customization' => $expanded,
                            ];
                            // Se o item tem múltiplas unidades e uma personalização única, aplicar a todas
                            $componentExtra += $expanded['total_delta'] * $itemQty;
                        }
                    } else {
                        // Tem personalizações por unidade
                        $componentCustomizations[$simpleId] = [
                            'component' => $selected,
                            'customization' => reset($unitCustomizations), // Primeira como base
                            'unit_customizations' => $unitCustomizations,
                        ];
                    }
                }
            }

            $pricing = $comboData['pricing'];
            // Incluir extra de troca de componentes (calculado em expandComboData)
            $componentSwapExtra = (float)($comboData['component_swap_extra'] ?? 0.0);
            $unitPrice = DataValidator::getFloat($pricing, 'total', 'base') + $baseCustomization['total_delta'] + $componentExtra + $componentSwapExtra;

            if ($unitPrice < 0) {
                $unitPrice = 0.0;
            }

            $hydrated[] = [
                'uid' => DataValidator::getString($item, 'uid'),
                'product' => [
                    'id' => $productId,
                    'name' => DataValidator::getString($product, 'name', 'Produto'),
                    'image' => DataValidator::getString($product, 'image'),
                    'type' => DataValidator::getString($product, 'type', 'simple'),
                ],
                'qty' => $qty,
                'combo' => $comboData,
                'customization' => $baseCustomization,
                'component_customizations' => $componentCustomizations,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $qty,
            ];
        }

        return $hydrated;
    }

    /** Expande dados de combo para visualização */
    private function expandComboData(array $product, array $comboMap): array
    {
        $priceMode = DataValidator::getString($product, 'price_mode', 'fixed');
        
        $result = [
            'groups' => [],
            'selected_items' => [],
            'pricing_map' => [],
            'pricing' => ['base' => $this->baseProductPrice($product), 'sum_delta' => 0, 'total' => $this->baseProductPrice($product)],
            'price_mode' => $priceMode,
        ];

        if (DataValidator::getString($product, 'type', 'simple') !== 'combo') {
            return $result;
        }

        $groups = Product::getComboGroupsWithItems((int)$product['id']);

        foreach ($groups as $group) {
            $gid = DataValidator::getInt($group, 'id');

            if ($gid <= 0) {
                continue;
            }
            $items = DataValidator::getArray($group, 'items');

            if (!$items) {
                continue;
            }

            $minQty = DataValidator::getInt($group, 'min', 'min_qty');
            $wanted = $comboMap[$gid] ?? null;
            $selectedSimpleIds = [];

            if (is_array($wanted)) {
                foreach ($wanted as $sid) {
                    $selectedSimpleIds[] = (int)$sid;
                }
            } elseif ($wanted !== null) {
                $selectedSimpleIds[] = (int)$wanted;
            } else {
                if ($minQty > 0) {
                    foreach ($items as $opt) {
                        if (!empty($opt['default'])) {
                            $selectedSimpleIds[] = (int)$opt['simple_id'];
                        }
                    }

                    if (!$selectedSimpleIds && $items) {
                        $selectedSimpleIds[] = (int)$items[0]['simple_id'];
                    }
                }
            }

            if (!$selectedSimpleIds) {
                continue;
            }

            $groupDetails = [];

            foreach ($items as $opt) {
                $simpleId = DataValidator::getInt($opt, 'simple_id', 'simple_product_id');

                if ($simpleId <= 0) {
                    continue;
                }

                if (!in_array($simpleId, $selectedSimpleIds, true)) {
                    continue;
                }
                $delta = DataValidator::getFloat($opt, 'delta', 'delta_price');
                $basePrice = null;
                $priceOverride = array_key_exists('price_override', $opt) && $opt['price_override'] !== null 
                    ? (float)$opt['price_override'] 
                    : null;

                // Prioridade: price_override > base_price > price
                if ($priceOverride !== null) {
                    $basePrice = $priceOverride;
                } elseif (array_key_exists('base_price', $opt) && $opt['base_price'] !== null) {
                    $basePrice = (float)$opt['base_price'];
                } elseif (array_key_exists('price', $opt) && $opt['price'] !== null) {
                    $basePrice = (float)$opt['price'];
                }
                $isDefault = !empty($opt['default']) || !empty($opt['is_default']);
                $comboItemId = DataValidator::getInt($opt, 'id');
                if ($comboItemId === 0) {
                    $comboItemId = $simpleId;
                }
                $defaultQty = DataValidator::getInt($opt, 'default_qty');
                if ($defaultQty <= 0 && $isDefault) {
                    $defaultQty = 1;
                }
                $itemData = [
                    'simple_id' => $simpleId,
                    'combo_item_id' => $comboItemId,
                    'name' => DataValidator::getString($opt, 'name'),
                    'delta' => $delta,
                    'image' => DataValidator::getString($opt, 'image'),
                    'customizable' => !empty($opt['customizable']) || !empty($opt['allow_customize']),
                    'base_price' => $basePrice,
                    'price_override' => $priceOverride,
                    'is_default' => $isDefault,
                    'default' => $isDefault,
                    'qty' => $defaultQty,
                    'default_qty' => $defaultQty,
                ];
                $groupDetails[] = $itemData;
                $result['selected_items'][] = $itemData;
            }

            if (!$groupDetails) {
                continue;
            }

            // Preparar todos os itens do grupo (não apenas selecionados) para comparação de preços
            $allItems = [];
            foreach ($items as $opt) {
                $simpleId = DataValidator::getInt($opt, 'simple_id', 'simple_product_id');
                if ($simpleId <= 0) continue;
                
                $priceOverride = array_key_exists('price_override', $opt) && $opt['price_override'] !== null 
                    ? (float)$opt['price_override'] 
                    : null;
                    
                $itemBasePrice = null;
                if ($priceOverride !== null) {
                    $itemBasePrice = $priceOverride;
                } elseif (array_key_exists('base_price', $opt) && $opt['base_price'] !== null) {
                    $itemBasePrice = (float)$opt['base_price'];
                } elseif (array_key_exists('price', $opt) && $opt['price'] !== null) {
                    $itemBasePrice = (float)$opt['price'];
                }
                
                $allItems[] = [
                    'simple_id' => $simpleId,
                    'name' => DataValidator::getString($opt, 'name'),
                    'base_price' => $itemBasePrice,
                    'price_override' => $priceOverride,
                    'delta' => DataValidator::getFloat($opt, 'delta', 'delta_price'),
                    'is_default' => !empty($opt['default']) || !empty($opt['is_default']),
                    'default' => !empty($opt['default']) || !empty($opt['is_default']),
                ];
            }

            $result['groups'][] = [
                'id' => $gid,
                'name' => DataValidator::getString($group, 'name'),
                'items' => $groupDetails,
                'all_items' => $allItems, // Todos os itens disponíveis para comparação de preço
            ];

            $result['pricing_map'][$gid] = count($groupDetails) === 1
                ? $groupDetails[0]['simple_id']
                : array_map(static function ($row) {
                    return $row['simple_id'];
                }, $groupDetails);
        }

        $result['pricing'] = Product::calculateComboTotal($product, $result['pricing_map']);

        // Calcular extra de troca de componentes (quando delta_price é zero, usar diferença de preços)
        $componentSwapExtra = 0.0;
        foreach ($result['groups'] as $group) {
            // Encontrar o item padrão do grupo
            $defaultPrice = null;
            $defaultDelta = 0.0;
            $allItems = $group['all_items'] ?? [];
            foreach ($allItems as $item) {
                if (!empty($item['is_default']) || !empty($item['default'])) {
                    $defaultPrice = $item['base_price'] ?? $item['price_override'] ?? null;
                    $defaultDelta = $item['delta'] ?? 0.0;
                    break;
                }
            }
            
            // Calcular diferença para cada item selecionado
            foreach (($group['items'] ?? []) as $selected) {
                $isDefault = !empty($selected['is_default']) || !empty($selected['default']);
                if ($isDefault) {
                    continue;
                }
                
                $selectedDelta = $selected['delta'] ?? 0.0;
                $selectedPrice = $selected['base_price'] ?? $selected['price_override'] ?? null;
                
                // Verificar se delta está configurado
                $deltaDiff = $selectedDelta - $defaultDelta;
                if (abs($deltaDiff) > 0.009) {
                    // Usar delta configurado
                    $componentSwapExtra += $deltaDiff;
                } elseif ($defaultPrice !== null && $selectedPrice !== null) {
                    // Se delta é zero, calcular diferença de preços
                    $componentSwapExtra += ($selectedPrice - $defaultPrice);
                }
            }
        }
        $result['component_swap_extra'] = $componentSwapExtra;

        return $result;
    }

    /** Retorna preço base do produto (considerando promocional) */
    private function baseProductPrice(array $product): float
    {
        $price = DataValidator::getFloat($product, 'price');
        $promo = DataValidator::getFloat($product, 'promo_price');

        if ($promo > 0 && $promo < $price) {
            return $promo;
        }

        return $price;
    }

    /** Expande personalização para renderização e totalização */
    private function expandCustomization(int $productId, ?array $customData): array
    {
        $result = [
            'groups' => [],
            'total_delta' => 0.0,
            'has_customization' => false,
        ];
        $mods = ProductCustomization::loadForPublic($productId);

        if (!$mods) {
            return $result;
        }

        if (!$customData) {
            return $result;
        }

        foreach ($mods as $gi => $group) {
            $type = DataValidator::getString($group, 'type', 'extra');
            $items = DataValidator::getArray($group, 'items');

            if (!$items) {
                continue;
            }

            if ($type === 'single') {
                if (!isset($customData['single'][$gi])) {
                    continue;
                }
                
                // Suportar tanto formato antigo (int) quanto novo (array de índices)
                $selectedIndices = $customData['single'][$gi];
                if (!is_array($selectedIndices)) {
                    $selectedIndices = [(int)$selectedIndices];
                }
                
                $selectedItems = [];
                $groupDelta = 0.0;
                
                foreach ($selectedIndices as $index) {
                    $index = (int)$index;
                    
                    if (!isset($items[$index])) {
                        continue;
                    }
                    
                    $item = $items[$index];
                    $priceUnit = DataValidator::getFloat($item, 'sale_price', 'delta');
                    
                    // Pegar quantidade padrão configurada no produto
                    // Em grupos single/choice, 1 unidade é SEMPRE incluída no preço base
                    // (trocar entre itens do grupo não deve cobrar extra)
                    $defaultQty = DataValidator::getInt($item, 'default_qty');
                    if ($defaultQty <= 0) {
                        $defaultQty = 1;
                    }
                    
                    // Pegar quantidade selecionada pelo cliente
                    $qty = 1;
                    if (isset($customData['singleQty'][$gi])) {
                        $singleQtyData = $customData['singleQty'][$gi];
                        if (is_array($singleQtyData) && isset($singleQtyData[$index])) {
                            $qty = max(1, (int)$singleQtyData[$index]);
                        } elseif (!is_array($singleQtyData)) {
                            $qty = max(1, (int)$singleQtyData);
                        }
                    }
                    
                    // Calcular delta: só cobra a diferença acima do padrão
                    // Se qty = 4 e default_qty = 4, delta = 0 (incluso)
                    // Se qty = 6 e default_qty = 4, delta = 2 * priceUnit
                    $deltaQty = $qty - $defaultQty;
                    if ($deltaQty < 0) {
                        $deltaQty = 0; // Não dar desconto se abaixo do padrão
                    }
                    
                    $linePrice = $priceUnit * $deltaQty;
                    $groupDelta += $linePrice;
                    
                    $selectedItems[] = [
                        'name' => DataValidator::getString($item, 'name'),
                        'qty' => $qty,
                        'unit_price' => $priceUnit,
                        'price' => $linePrice,
                        'default_qty' => $defaultQty,
                        'delta_qty' => $deltaQty,
                    ];
                }
                
                if ($selectedItems) {
                    $result['groups'][] = [
                        'name' => DataValidator::getString($group, 'name'),
                        'type' => 'single',
                        'items' => $selectedItems,
                    ];

                    if ($groupDelta > 0) {
                        $result['total_delta'] += $groupDelta;
                    }
                    $result['has_customization'] = true;
                }
            } elseif ($type === 'addon') {
                if (empty($customData['choice'][$gi]) || !is_array($customData['choice'][$gi])) {
                    continue;
                }
                $selected = [];

                foreach ($customData['choice'][$gi] as $idx) {
                    $idx = (int)$idx;

                    if (!isset($items[$idx])) {
                        continue;
                    }
                    $item = $items[$idx];
                    $price = DataValidator::getFloat($item, 'sale_price', 'delta');
                    $selected[] = [
                        'name' => DataValidator::getString($item, 'name'),
                        'price' => $price,
                    ];

                    if ($price > 0) {
                        $result['total_delta'] += $price;
                    }
                }

                if ($selected) {
                    $result['groups'][] = [
                        'name' => DataValidator::getString($group, 'name'),
                        'type' => 'addon',
                        'items' => $selected,
                    ];
                    $result['has_customization'] = true;
                }
            } elseif ($type === 'pool') {
                // Pool mode: primeiros pool_free itens grátis, extras cobram sale_price
                if (empty($customData['qty'][$gi]) || !is_array($customData['qty'][$gi])) {
                    continue;
                }
                $poolFree = DataValidator::getInt($group, 'pool_free');
                if ($poolFree <= 0) {
                    $poolFree = DataValidator::getInt($group, 'max');
                }
                $selected = [];
                $totalUnits = 0;

                foreach ($customData['qty'][$gi] as $idx => $qty) {
                    $idx = (int)$idx;
                    $qty = (int)$qty;
                    if ($qty <= 0 || !isset($items[$idx])) {
                        continue;
                    }
                    $item = $items[$idx];
                    $priceUnit = DataValidator::getFloat($item, 'sale_price', 'delta');

                    // Calcular quantas unidades deste item são grátis vs pagas
                    $freeSlots = max(0, $poolFree - $totalUnits);
                    $freeQty   = min($qty, $freeSlots);
                    $paidQty   = $qty - $freeQty;
                    $totalUnits += $qty;

                    $linePrice = $paidQty > 0 ? $priceUnit * $paidQty : 0.0;

                    $selected[] = [
                        'name'       => DataValidator::getString($item, 'name'),
                        'qty'        => $qty,
                        'unit_price' => $priceUnit,
                        'price'      => $linePrice,
                        'free_qty'   => $freeQty,
                        'paid_qty'   => $paidQty,
                    ];

                    if ($linePrice > 0.0) {
                        $result['total_delta'] += $linePrice;
                    }
                }

                if ($selected) {
                    $result['groups'][] = [
                        'name'  => DataValidator::getString($group, 'name'),
                        'type'  => 'pool',
                        'items' => $selected,
                    ];
                    $result['has_customization'] = true;
                }
            } else {
                if (empty($customData['qty'][$gi]) || !is_array($customData['qty'][$gi])) {
                    continue;
                }
                $selected = [];

                foreach ($customData['qty'][$gi] as $idx => $qty) {
                    $idx = (int)$idx;
                    $qty = (int)$qty;

                    if (!isset($items[$idx])) {
                        continue;
                    }
                    $item = $items[$idx];
                    $priceUnit = DataValidator::getFloat($item, 'sale_price', 'delta');
                    $defaultQty = DataValidator::getInt($item, 'default_qty', 'qty');
                    if ($defaultQty === 0) {
                        $defaultQty = null;
                    }
                    
                    // Se qty=0 e não tinha default, ignorar (nunca foi selecionado)
                    // Se qty=0 mas tinha default > 0, é remoção - registrar!
                    if ($qty <= 0 && ($defaultQty === null || $defaultQty <= 0)) {
                        continue;
                    }
                    
                    $deltaQty = $qty;

                    if ($defaultQty !== null) {
                        $deltaQty = $qty - $defaultQty;
                    }
                    
                    // Se qty=0 é remoção - não cobrar/descontar, apenas marcar como removido
                    // Só cobrar quando é adição (deltaQty > 0)
                    $isRemoval = ($qty === 0 && $defaultQty !== null && $defaultQty > 0);
                    $linePrice = $isRemoval ? 0.0 : ($deltaQty > 0 ? $priceUnit * $deltaQty : 0.0);
                    
                    $line = [
                        'name' => DataValidator::getString($item, 'name'),
                        'qty' => $qty,
                        'unit_price' => $priceUnit,
                        'price' => $linePrice,
                        'default_qty' => $defaultQty,
                        'delta_qty' => $deltaQty,
                        'removed' => $isRemoval,
                    ];

                    if ($linePrice > 0.0) {
                        $result['total_delta'] += $linePrice;
                    }
                    $selected[] = $line;
                }

                if ($selected) {
                    $result['groups'][] = [
                        'name' => DataValidator::getString($group, 'name'),
                        'type' => 'qty',
                        'items' => $selected,
                    ];
                    $result['has_customization'] = true;
                }
            }
        }

        return $result;
    }

    /** GET /{slug}/cart */
    public function index($params)
    {
        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $requireLogin = (bool)(config('login_required') ?? false);
        $customer = AuthCustomer::current($slug);

        if ($requireLogin && !$customer) {
            $cartPath = '/' . ltrim(parse_url(base_url($slug . '/cart'), PHP_URL_PATH) ?: '', '/');
            $redirect = base_url($slug) . '?login=1&redirect_to=' . urlencode($cartPath);
            header('Location: ' . $redirect);
            exit;
        }

        $cartRef = $this->storage->getCart();
        $items = $this->hydrateCartItems($cartRef, $company);

        if (!is_array($items)) {
            $items = [];
        }

        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += (float)$item['line_total'];
        }

        // Buscar prefixo do cupom
        $db = db();
        $stmt = $db->prepare("SELECT coupon_prefix FROM companies WHERE id = :id");
        $stmt->execute(['id' => $company['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $couponPrefix = $result['coupon_prefix'] ?? 'WOLL';

        return $this->view('public/cart', [
            'company' => $company,
            'items'   => $items,
            'totals'  => [
                'subtotal' => $subtotal,
                'total'    => $subtotal,
            ],
            'slug' => $slug,
            'updateUrl' => base_url($slug . '/cart/update'),
            'customer' => $customer,
            'requireLogin' => $requireLogin,
            'coupon_prefix' => $couponPrefix,
        ]);
    }

    /** GET /{slug}/checkout */
    public function checkout($params)
    {
        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $requireLogin = (bool)(config('login_required') ?? false);
        $customer = AuthCustomer::current($slug);

        if ($requireLogin && !$customer) {
            $checkoutPath = '/' . ltrim(parse_url(base_url($slug . '/checkout'), PHP_URL_PATH) ?: '', '/');
            $redirect = base_url($slug) . '?login=1&redirect_to=' . urlencode($checkoutPath);
            header('Location: ' . $redirect);
            exit;
        }

        if ($customer && function_exists('validate_session_ownership') && !validate_session_ownership()) {
            $slugClean = trim((string)$slug, '/');
            header('Location: ' . base_url($slugClean . '?session_expired=1'));
            exit;
        }
        if ($customer && function_exists('validate_session_activity')) {
            $maxIdle = (int)(config('session_lifetime_seconds') ?? 604800);
            if (!validate_session_activity($maxIdle)) {
                $slugClean = trim((string)$slug, '/');
                header('Location: ' . base_url($slugClean . '?expired=1'));
                exit;
            }
        }

        $db = db();

        // Recarregar dados atualizados do cliente do banco de dados para evitar cache
        if ($customer && !empty($customer['id'])) {
            $stmt = $db->prepare('SELECT id, name, whatsapp, whatsapp_e164 FROM customers WHERE id = ? LIMIT 1');
            $stmt->execute([$customer['id']]);
            $freshCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($freshCustomer) {
                // Atualizar a sessão com dados frescos do banco
                $_SESSION['customer'] = array_merge($customer, [
                    'name'     => $freshCustomer['name'],
                    'whatsapp' => $freshCustomer['whatsapp'],
                    'e164'     => $freshCustomer['whatsapp_e164'],
                ]);
                $customer = $_SESSION['customer'];
            }
        }

        $cartRef = $this->storage->getCart();
        $items = $this->hydrateCartItems($cartRef, $company);

        if (!$items) {
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        $subtotal = 0.0;
        $loyaltyDiscount = 0.0; // Desconto de fidelidade total
        
        // Pegar taxa embutida da empresa (não do produto)
        $embeddedFee = (float)($company['embedded_delivery_fee'] ?? 0);

        // Buscar IDs dos produtos com taxa embutida habilitada
        $embeddedEnabledIds = [];
        if ($embeddedFee > 0) {
            $stmtEmbedded = $db->prepare('SELECT id FROM products WHERE company_id = ? AND embedded_fee_enabled = 1');
            $stmtEmbedded->execute([$company['id']]);
            $embeddedEnabledIds = array_column($stmtEmbedded->fetchAll(\PDO::FETCH_ASSOC), 'id');
            $embeddedEnabledIds = array_map('intval', $embeddedEnabledIds);
        }

        foreach ($items as $item) {
            $subtotal += (float)$item['line_total'];
            
            // Calcula o desconto de fidelidade apenas para produtos selecionados
            $qty = (int)($item['qty'] ?? 1);
            $itemProductId = (int)($item['product']['id'] ?? 0);
            
            if ($embeddedFee > 0 && $qty > 0 && in_array($itemProductId, $embeddedEnabledIds, true)) {
                $loyaltyDiscount += ($embeddedFee * $qty);
            }
        }

        // Validar pedido mínimo
        $minOrder = !empty($company['min_order']) ? (float)$company['min_order'] : 0;
        if ($minOrder > 0 && $subtotal < $minOrder) {
            $_SESSION['error_message'] = sprintf(
                'O valor mínimo do pedido é R$ %.2f. Seu carrinho possui R$ %.2f. Adicione mais R$ %.2f em produtos.',
                $minOrder,
                $subtotal,
                $minOrder - $subtotal
            );
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        // Verificar se o usuário mudou - limpar endereço da sessão se for outro usuário
        $sessionUserPhone = $_SESSION['checkout_user_phone'] ?? null;
        $currentUserPhone = $customer['whatsapp'] ?? null;
        
        if ($sessionUserPhone && $currentUserPhone && $sessionUserPhone !== $currentUserPhone) {
            // Usuário mudou - limpar TUDO da sessão do checkout
            unset($_SESSION['checkout_address']);
            unset($_SESSION['checkout_user_phone']);
            $sessionUserPhone = null;
        }
        
        // Salvar o telefone do usuário atual na sessão
        if ($currentUserPhone) {
            $_SESSION['checkout_user_phone'] = $currentUserPhone;
        }

        // Sempre começar com campos vazios - NÃO carregar da sessão
        $deliveryAddress = [
            'name'              => $customer['name'] ?? '',
            'phone'             => $customer['whatsapp'] ?? '',
            'street'            => '',
            'number'            => '',
            'neighborhood'      => '',
            'city'              => '',
            'complement'        => '',
            'reference'         => '',
            'notes'             => '',
            'city_id'           => 0,
            'zone_id'           => 0,
            'payment_method_id' => 0,
            'address_id'        => 0,
        ];

        $companyId = (int)($company['id'] ?? 0);
        
        // Buscar endereços salvos do cliente
        $savedAddresses = [];
        if ($customer && isset($customer['id']) && $customer['id'] > 0) {
            $savedAddresses = CustomerAddress::getByCustomer((int)$customer['id'], $companyId);
            
            // NÃO carregar endereço padrão automaticamente
            // O usuário deve selecionar a cidade e bairro manualmente
        }
        
        $cities = DeliveryCity::allByCompany($companyId);
        $zonesRaw = DeliveryZone::allByCompany($companyId);

        // SEMPRE começar sem cidade/zona selecionada
        // Usuário deve selecionar manualmente no formulário
        $selectedCityId = 0;
        $selectedZoneId = 0;

        $zonesByCity = [];
        $selectedZone = null;

        foreach ($zonesRaw as $zone) {
            $cityId = (int)($zone['city_id'] ?? 0);
            $mapped = [
                'id'         => (int)($zone['id'] ?? 0),
                'city_id'    => $cityId,
                'name'       => (string)($zone['neighborhood'] ?? ''),
                'fee'        => (float)($zone['fee'] ?? 0),
                'city_name'  => (string)($zone['city_name'] ?? ''),
            ];

            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = $mapped;

            if ($mapped['id'] === $selectedZoneId) {
                $selectedZone = $mapped;
            }
        }

        // NÃO selecionar cidade automaticamente mesmo se houver apenas uma
        // O usuário deve fazer a seleção manualmente
        
        // NÃO alterar selectedCityId ou selectedZoneId - devem permanecer 0
        // para que o formulário apareça vazio

        $deliveryFee = 0.0; // Sem seleção de zona, sem taxa de entrega
        
        // Verificar se o subtotal atingiu o valor mínimo para frete grátis
        $freeShippingMin = !empty($company['delivery_free_min_value']) ? (float)$company['delivery_free_min_value'] : 0;
        if ($freeShippingMin > 0 && $subtotal >= $freeShippingMin) {
            $deliveryFee = 0.0; // Frete grátis aplicado
        }
        
        $total = $subtotal + $deliveryFee - $loyaltyDiscount;

        $paymentMethods = PaymentMethod::activeByCompany($companyId);
        // construir icon_url absoluto para cada método, para evitar problemas com caminhos relativos
        $baseUrlFull = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
        foreach ($paymentMethods as &$pm) {
            $pm = is_array($pm) ? $pm : [];
            $metaRaw = $pm['meta'] ?? null;
            $meta = JsonHelper::decode($metaRaw);
            $pm['meta'] = $meta;
            $icon = '';
            // Preferir coluna `icon` quando disponível (migração incremental)
            if (!empty($pm['icon']) && is_string($pm['icon'])) {
                $icon = trim((string)$pm['icon']);
            } elseif (!empty($meta['icon']) && is_string($meta['icon'])) {
                $icon = trim((string)$meta['icon']);
            }
            $iconUrl = '';
            if ($icon !== '') {
                if (preg_match('#^https?://#i', $icon)) {
                    $iconUrl = $icon;
                } else {
                    if (str_starts_with($icon, '/')) {
                        $iconUrl = $baseUrlFull !== '' ? ($baseUrlFull . $icon) : $icon;
                    } else {
                        $iconUrl = $baseUrlFull !== '' ? base_url($icon) : ('/' . ltrim($icon, '/'));
                    }
                }
            } elseif (($pm['type'] ?? '') === 'pix') {
                // ícone padrão do pix quando não definido
                $pixPath = '/assets/card-brands/pix.svg';
                $iconUrl = $baseUrlFull !== '' ? ($baseUrlFull . $pixPath) : $pixPath;
            } elseif (($pm['type'] ?? '') === 'cash') {
                // ícone padrão do dinheiro quando não definido
                $cashPath = '/assets/card-brands/cash.svg';
                $iconUrl = $baseUrlFull !== '' ? ($baseUrlFull . $cashPath) : $cashPath;
            }
            $pm['icon_url'] = $iconUrl;
        }
        unset($pm);
        $selectedPaymentId = (int)($deliveryAddress['payment_method_id'] ?? 0);

        if (!$selectedPaymentId && $paymentMethods) {
            $selectedPaymentId = (int)$paymentMethods[0]['id'];
        }
        
        // FORÇAR city_id e zone_id para 0 - usuário deve selecionar manualmente
        $deliveryAddress['city_id'] = 0;
        $deliveryAddress['zone_id'] = 0;
        $deliveryAddress['payment_method_id'] = $selectedPaymentId;

        // Buscar endereços salvos do cliente
        $savedAddresses = [];
        if ($customer && isset($customer['id']) && $customer['id'] > 0) {
            $savedAddresses = CustomerAddress::getByCustomer((int)$customer['id'], $companyId);
        }

        $flash = $_SESSION['checkout_flash'] ?? null;
        unset($_SESSION['checkout_flash']);

        $zonesPresent = false;
        foreach ($zonesByCity as $cityZones) {
            if (!empty($cityZones)) {
                $zonesPresent = true;
                break;
            }
        }

        $couponCode = !empty($_SESSION['couponCode']) ? (string)$_SESSION['couponCode'] : '';
        $couponPercentage = !empty($_SESSION['couponCode']) ? (float)($_SESSION['couponDiscount'] ?? 0) : 0.0;
        $calc = CheckoutTotalsService::compute([
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'loyalty_discount' => $loyaltyDiscount,
            'coupon_percentage' => $couponPercentage,
            'selected_zone_id' => $selectedZoneId,
            'zones_present' => $zonesPresent,
        ]);
        $checkoutTotals = [
            'coupon_discount' => $calc['couponDiscount'],
            'delivery_discount_applied' => $calc['deliveryDiscountApplied'],
            'remaining_loyalty_discount' => $calc['remainingLoyaltyDiscount'],
            'final_delivery_fee' => $calc['finalDeliveryFee'],
            'total' => $calc['total'],
            'delivery_label' => $calc['deliveryLabel'],
        ];

        return $this->view('public/checkout', [
            'company'           => $company,
            'items'             => $items,
            'totals'            => [
                'subtotal'         => $subtotal,
                'delivery'         => $deliveryFee,
                'total'            => $total,
                'loyalty_discount' => $loyaltyDiscount,
            ],
            'slug'              => $slug,
            'customer'          => $customer,
            'deliveryAddress'   => $deliveryAddress,
            'savedAddresses'    => $savedAddresses,
            'cities'            => $cities,
            'zonesByCity'       => $zonesByCity,
            'selectedCityId'    => 0, // Sempre 0 - usuário deve selecionar
            'selectedZoneId'    => 0, // Sempre 0 - usuário deve selecionar
            'paymentMethods'    => $paymentMethods,
            'selectedPaymentId' => $selectedPaymentId,
            'couponCode'        => $couponCode,
            'couponPercentage'  => $couponPercentage,
            'zonesPresent'      => $zonesPresent,
            'checkoutTotals'    => $checkoutTotals,
            'flash'             => $flash,
        ]);
    }

    /** GET /{slug}/checkout/success */
    public function checkoutSuccess($params)
    {
        $slug    = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $success = $_SESSION['checkout_success'] ?? null;

        if (!$success || !is_array($success)) {
            header('Location: ' . base_url($slug));
            exit;
        }

        // Limpar sessões de processamento e sucesso
        unset($_SESSION['checkout_success']);
        unset($_SESSION['order_processing']);

        // Montar DTO e gerar URL do WhatsApp fora da view
        $successOrder = CheckoutSuccessOrder::fromArrays(
            $company,
            $success,
            (string) $slug,
        );

        $builder      = new CheckoutSuccessMessageBuilder($successOrder);
        $whatsappUrl  = $successOrder->hasValidWhatsApp() ? $builder->buildUrl() : '';

        return $this->view('public/checkout_success', [
            'successOrder' => $successOrder,
            'whatsappUrl'  => $whatsappUrl,
        ]);
    }

    /** POST /{slug}/checkout/processing — confirmação JSON usada pela página de processamento */
    public function confirmProcessing($params)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Empresa não encontrada']);
            return;
        }

        $processingOrder = $_SESSION['checkout_success'] ?? $_SESSION['order_processing'] ?? null;

        if (!$processingOrder || empty($processingOrder['order_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nenhum pedido em processamento']);
            return;
        }

        echo json_encode(['success' => true, 'order_id' => (int)$processingOrder['order_id']]);
    }

    /** GET /{slug}/checkout/processing */
    public function processing($params)
    {
        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        // Verificar se há um pedido em processamento na sessão
        $processingOrder = $_SESSION['order_processing'] ?? null;

        if (!$processingOrder || !is_array($processingOrder)) {
            // Se não há pedido em processamento, redireciona para o carrinho
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        // Transferir dados para checkout_success antes de limpar order_processing
        $_SESSION['checkout_success'] = $processingOrder;
        
        // Capturar notificação pendente antes de renderizar
        $pendingNotification = $_SESSION['pending_notification'] ?? null;
        if ($pendingNotification) {
            unset($_SESSION['pending_notification']);
        }
        
        // PRIMEIRO: Renderizar a view e enviar para o usuário imediatamente
        ignore_user_abort(true);
        
        // Renderizar view em buffer
        ob_start();
        $this->view('public/order-processing', [
            'company' => $company,
            'slug'    => $slug,
            'order'   => $processingOrder,
        ]);
        $content = ob_get_clean();
        
        // Enviar headers e conteúdo IMEDIATAMENTE
        header('Content-Length: ' . strlen($content));
        header('Connection: close');
        echo $content;
        
        // Flush todos os buffers
        if (function_exists('ob_end_flush')) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
        }
        flush();
        
        // Fechar sessão para não bloquear outras requests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // DEPOIS: Enviar notificação em background (usuário já viu a página)
        if ($pendingNotification) {
            try {
                OrderNotificationService::sendOrderNotification(
                    $pendingNotification['companyId'],
                    $pendingNotification['orderData']
                );
            } catch (Exception $e) {
                error_log("Erro ao enviar notificação: " . $e->getMessage());
            }
            
            // Enviar Web Push Notification para os dispositivos cadastrados
            try {
                require_once __DIR__ . '/../services/WebPushService.php';
                $webPushService = new \App\Services\WebPushService();
                $webPushService->notifyNewOrder(
                    $pendingNotification['companyId'],
                    $pendingNotification['orderData']
                );
            } catch (\Throwable $e) {
                error_log("Erro ao enviar Web Push: " . $e->getMessage());
            }
        }
        
        return;
    }

    /** POST /{slug}/checkout */
    public function submitCheckout($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';

            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $requireLogin = (bool)(config('login_required') ?? false);
        $customer = AuthCustomer::current($slug);

        if ($requireLogin && !$customer) {
            $checkoutPath = '/' . ltrim(parse_url(base_url($slug . '/checkout'), PHP_URL_PATH) ?: '', '/');
            $redirect = base_url($slug) . '?login=1&redirect_to=' . urlencode($checkoutPath);
            header('Location: ' . $redirect);
            exit;
        }

        $cartRef = $this->storage->getCart();

        if (!$cartRef) {
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        $items = $this->hydrateCartItems($cartRef, $company);

        if (!$items) {
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        $companyId = (int)$company['id'];
        $cities = DeliveryCity::allByCompany($companyId);
        $zonesRaw = DeliveryZone::allByCompany($companyId);

        $cityMap = [];

        foreach ($cities as $cityRow) {
            $cityMap[(int)($cityRow['id'] ?? 0)] = (string)($cityRow['name'] ?? '');
        }

        $zoneMap = [];
        $zonesByCity = [];

        foreach ($zonesRaw as $zoneRow) {
            $zoneId = (int)($zoneRow['id'] ?? 0);
            $zoneMap[$zoneId] = $zoneRow;
            $cityId = (int)($zoneRow['city_id'] ?? 0);

            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = $zoneRow;
        }

        $activePaymentMethods = PaymentMethod::activeByCompany($companyId);

        // Verificar se está usando endereço salvo
        $useSavedAddress = isset($_POST['use_saved_address']) && $_POST['use_saved_address'] === '1';
        $selectedAddressId = (int)($_POST['selected_address_id'] ?? 0);
        
        $addressInput = isset($_POST['address']) && is_array($_POST['address']) ? $_POST['address'] : [];
        
        // Se estiver usando endereço salvo, carregar dados do banco
        if ($useSavedAddress && $selectedAddressId > 0 && $customer) {
            require_once __DIR__ . '/../models/CustomerAddress.php';
            $savedAddress = CustomerAddress::getAddress($selectedAddressId, (int)$customer['id']);
            
            if ($savedAddress) {
                // Usar dados do endereço salvo, incluindo city e neighborhood já salvos
                $addressInput = [
                    'name'         => $savedAddress['name'] ?? '',
                    'phone'        => $savedAddress['phone'] ?? '',
                    'street'       => $savedAddress['street'] ?? '',
                    'number'       => $savedAddress['number'] ?? '',
                    'complement'   => $savedAddress['complement'] ?? '',
                    'reference'    => $savedAddress['reference'] ?? '',
                    'city_id'      => (int)($savedAddress['city_id'] ?? 0),
                    'zone_id'      => (int)($savedAddress['zone_id'] ?? 0),
                    'city'         => $savedAddress['city'] ?? '',
                    'neighborhood' => $savedAddress['neighborhood'] ?? '',
                ];
            }
        }

        $clean = [
            'name'        => trim($addressInput['name'] ?? ''),
            'phone'       => normalizePhone(trim($addressInput['phone'] ?? '')),
            'street'      => trim($addressInput['street'] ?? ''),
            'number'      => trim($addressInput['number'] ?? ''),
            'complement'  => trim($addressInput['complement'] ?? ''),
            'reference'   => trim($addressInput['reference'] ?? ''),
            'city_id'     => (int)($addressInput['city_id'] ?? 0),
            'zone_id'     => (int)($addressInput['zone_id'] ?? 0),
            'city'        => trim($addressInput['city'] ?? ''),
            'neighborhood' => trim($addressInput['neighborhood'] ?? ''),
            'notes'       => trim($_POST['order']['notes'] ?? ''),
        ];

        // Fallback: usar whatsapp do cliente logado se phone não veio no form
        if ($clean['phone'] === '' && !empty($customer['whatsapp'])) {
            $clean['phone'] = normalizePhone($customer['whatsapp']);
        }

        $deliveryFee = 0.0;

        // Preencher city e neighborhood se ainda não estiverem definidos
        if (empty($clean['city']) && !isset($cityMap[$clean['city_id']])) {
            $clean['city_id'] = 0;
        } elseif (empty($clean['city']) && isset($cityMap[$clean['city_id']])) {
            $clean['city'] = $cityMap[$clean['city_id']];
        }

        if ($clean['zone_id'] && isset($zoneMap[$clean['zone_id']])) {
            $zone = $zoneMap[$clean['zone_id']];
            $zoneCityId = (int)($zone['city_id'] ?? 0);

            if (!$clean['city_id'] || $clean['city_id'] !== $zoneCityId) {
                $clean['city_id'] = $zoneCityId;
                if (empty($clean['city'])) {
                    $clean['city'] = $cityMap[$zoneCityId] ?? '';
                }
            }
            
            // Só preencher neighborhood se ainda não estiver definido
            if (empty($clean['neighborhood'])) {
                $clean['neighborhood'] = (string)($zone['neighborhood'] ?? '');
            }
            
            $deliveryFee = (float)($zone['fee'] ?? 0.0);
        } else {
            $clean['zone_id'] = 0;
            if (empty($clean['neighborhood'])) {
                $clean['neighborhood'] = '';
            }
            $deliveryFee = 0.0;
        }

        $paymentInput = isset($_POST['payment']) && is_array($_POST['payment']) ? $_POST['payment'] : [];
        $paymentMethodId = (int)($paymentInput['method_id'] ?? 0);
        $paymentMethod = $paymentMethodId ? PaymentMethod::findForCompany($paymentMethodId, $companyId) : null;

        if (!$paymentMethod || (int)($paymentMethod['active'] ?? 0) !== 1) {
            $paymentMethodId = 0;
            $paymentMethod = null;
        }
        
        $clean['payment_method_id'] = $paymentMethodId;

        // Processar valor em dinheiro se for método cash
        $cashAmount = 0.0;
        if ($paymentMethod && ($paymentMethod['type'] ?? '') === 'cash') {
            $cashAmount = (float)($_POST['cash_amount'] ?? 0);
        }

        $errors = [];
        
        // Verificar métodos de pagamento ativos para validação
        $activePaymentMethods = PaymentMethod::activeByCompany($companyId);

        if ($clean['name'] === '') {
            $errors[] = 'Informe o nome do destinatário.';
        }

        if ($clean['phone'] === '') {
            $errors[] = 'Informe o telefone/WhatsApp para contato.';
        } elseif (strlen(preg_replace('/[^0-9]/', '', $clean['phone'])) < 10) {
            $errors[] = 'Telefone/WhatsApp inválido. Informe o número completo com DDD.';
        }
        $zonesForSelectedCity = $clean['city_id'] > 0 && isset($zonesByCity[$clean['city_id']])
            ? $zonesByCity[$clean['city_id']] : [];

        if ($clean['city_id'] <= 0 && !empty($cityMap)) {
            $errors[] = 'Selecione uma cidade atendida.';
        }

        if ($clean['zone_id'] <= 0 && !empty($zonesForSelectedCity)) {
            $errors[] = 'Selecione um bairro atendido.';
        }

        if ($clean['street'] === '') {
            $errors[] = 'Informe a rua/avenida.';
        }

        if ($clean['number'] === '') {
            $errors[] = 'Informe o número do endereço.';
        }

        // Validar se o número contém apenas dígitos
        if ($clean['number'] !== '' && !preg_match('/^\d+$/', $clean['number'])) {
            $errors[] = 'O número do endereço deve conter apenas números.';
        }

        if ($activePaymentMethods && $paymentMethodId <= 0) {
            $errors[] = 'Escolha um método de pagamento disponível.';
        }

        if ($errors) {
            $_SESSION['checkout_flash'] = [
                'type' => 'error',
                'message' => implode(' ', $errors),
            ];
            $_SESSION['checkout_address'] = $clean;
            header('Location: ' . base_url($slug . '/checkout'));
            exit;
        }

        $orderItemsPayload = [];
        $subtotal = 0.0;
        $loyaltyDiscount = 0.0; // Desconto de fidelidade
        $itemsSummary = [];
        
        // Pegar taxa embutida da empresa (não do produto)
        $embeddedFee = (float)($company['embedded_delivery_fee'] ?? 0);

        // Buscar IDs dos produtos com taxa embutida habilitada
        $dbEmb = db();
        $embeddedEnabledIds = [];
        if ($embeddedFee > 0) {
            $stmtEmbedded = $dbEmb->prepare('SELECT id FROM products WHERE company_id = ? AND embedded_fee_enabled = 1');
            $stmtEmbedded->execute([$company['id']]);
            $embeddedEnabledIds = array_column($stmtEmbedded->fetchAll(\PDO::FETCH_ASSOC), 'id');
            $embeddedEnabledIds = array_map('intval', $embeddedEnabledIds);
        }

        foreach ($items as $item) {
            $productId = (int)($item['product']['id'] ?? 0);

            if ($productId <= 0) {
                continue;
            }
            $quantity = max(1, (int)($item['qty'] ?? 1));
            $unitPrice = (float)($item['unit_price'] ?? 0.0);
            $lineTotal = (float)($item['line_total'] ?? ($unitPrice * $quantity));
            
            // Calcular desconto de fidelidade apenas para produtos selecionados
            if ($embeddedFee > 0 && $quantity > 0 && in_array($productId, $embeddedEnabledIds, true)) {
                $loyaltyDiscount += ($embeddedFee * $quantity);
            }

            // Preparar combo_data incluindo component_customizations para persistir as personalizações por unidade
            $comboDataToSave = $item['combo'] ?? null;
            if ($comboDataToSave && !empty($item['component_customizations'])) {
                $comboDataToSave['component_customizations'] = $item['component_customizations'];
            }

            $orderItemsPayload[] = [
                'product_id'          => $productId,
                'quantity'            => $quantity,
                'unit_price'          => $unitPrice,
                'line_total'          => $lineTotal,
                'combo_data'          => $comboDataToSave,
                'customization_data'  => $item['customization'] ?? null,
                'notes'               => $item['notes'] ?? null,
            ];
            $subtotal += $lineTotal;

            $itemsSummary[] = [
                'name'       => (string)($item['product']['name'] ?? 'Produto'),
                'quantity'   => $quantity,
                'line_total' => $lineTotal,
                'unit_price' => $unitPrice,
                'customization' => $item['customization'] ?? null,
                'combo'      => $comboDataToSave ?? null,
                'component_customizations' => $item['component_customizations'] ?? [],
            ];
        }

        if (!$orderItemsPayload) {
            $_SESSION['checkout_flash'] = [
                'type' => 'error',
                'message' => 'Seu carrinho está vazio.',
            ];
            $_SESSION['checkout_address'] = $clean;
            header('Location: ' . base_url($slug . '/checkout'));
            exit;
        }

        // Validar pedido mínimo
        $minOrder = !empty($company['min_order']) ? (float)$company['min_order'] : 0;
        if ($minOrder > 0 && $subtotal < $minOrder) {
            $_SESSION['checkout_flash'] = [
                'type' => 'error',
                'message' => sprintf(
                    'O valor mínimo do pedido é R$ %.2f. Seu carrinho possui R$ %.2f. Adicione mais R$ %.2f em produtos.',
                    $minOrder,
                    $subtotal,
                    $minOrder - $subtotal
                ),
            ];
            $_SESSION['checkout_address'] = $clean;
            header('Location: ' . base_url($slug . '/checkout'));
            exit;
        }

        // Verificar se o subtotal atingiu o valor mínimo para frete grátis
        $freeShippingMin = !empty($company['delivery_free_min_value']) ? (float)$company['delivery_free_min_value'] : 0;
        if ($freeShippingMin > 0 && $subtotal >= $freeShippingMin) {
            $deliveryFee = 0.0; // Frete grátis aplicado
        }

        $discount = 0.0;
        $total = max(0.0, $subtotal + $deliveryFee - $discount - $loyaltyDiscount);

        // Validação específica para pagamento em dinheiro
        if ($paymentMethod && ($paymentMethod['type'] ?? '') === 'cash') {
            // Se cashAmount for 0, significa que não precisa de troco (pagamento exato)
            if ($cashAmount > 0 && $cashAmount < $total) {
                $deficit = $total - $cashAmount;
                $errors[] = 'Valor insuficiente. Falta ' . MoneyFormatter::format($deficit) . ' para completar o pagamento.';
            }
            // Se cashAmount for 0, assumimos pagamento exato sem troco
        }

        // Verificar erros antes de prosseguir
        if ($errors) {
            $_SESSION['checkout_flash'] = [
                'type' => 'error',
                'message' => implode(' ', $errors),
            ];
            $_SESSION['checkout_address'] = $clean;
            header('Location: ' . base_url($slug . '/checkout'));
            exit;
        }

        $paymentMethodName = $paymentMethod ? trim((string)($paymentMethod['name'] ?? '')) : '';
        $paymentInstructions = $paymentMethod ? trim((string)($paymentMethod['instructions'] ?? '')) : '';

        $orderNotesParts = [];

        if ($clean['notes'] !== '') {
            $orderNotesParts[] = 'Observações: ' . $clean['notes'];
        }

        if ($paymentMethodName !== '') {
            $paymentLine = 'Pagamento: ' . $paymentMethodName;

            if ($paymentInstructions !== '') {
                $paymentLine .= ' — ' . $paymentInstructions;
            }

            // Adicionar informações de troco para pagamento em dinheiro
            if (($paymentMethod['type'] ?? '') === 'cash') {
                if ($cashAmount > 0) {
                    $change = $cashAmount - $total;
                    $paymentLine .= ' — Valor informado: ' . MoneyFormatter::format($cashAmount);
                    if ($change > 0) {
                        $paymentLine .= ' (Troco: ' . MoneyFormatter::format($change) . ')';
                    }
                } else {
                    $paymentLine .= ' — Pagamento exato (sem troco)';
                }
            }

            $orderNotesParts[] = $paymentLine;
        }
        $orderNotes = $orderNotesParts ? implode("\n\n", $orderNotesParts) : null;

        $formattedAddress = $this->formatOrderAddress($clean);

        $db = $this->db();
        try {
            $db->beginTransaction();
            
            // Pegar código do cupom da sessão, se houver
            $couponCode = !empty($_SESSION['couponCode']) ? $_SESSION['couponCode'] : null;
            
            $orderId = Order::create($db, [
                'company_id'       => $companyId,
                'customer_name'    => $clean['name'],
                'customer_phone'   => $clean['phone'],
                'subtotal'         => $subtotal,
                'delivery_fee'     => $deliveryFee,
                'discount'         => $discount,
                'loyalty_discount' => $loyaltyDiscount,
                'coupon_code'      => $couponCode,
                'total'            => $total,
                'status'           => 'pending',
                'notes'            => $orderNotes,
                'customer_address' => $formattedAddress,
                'payment_method_id' => $paymentMethodId,
            ]);

            foreach ($orderItemsPayload as $payload) {
                Order::addItem($db, $orderId, $payload);
            }

            $this->persistOrderAddress($db, $orderId, $formattedAddress);

            // ===== TRACKING DE PURCHASE PARA MACHINE LEARNING =====
            try {
                require_once __DIR__ . '/../services/RecommendationEngine.php';
                $engine = new RecommendationEngine($db);
                
                // Registrar purchase para cada produto no pedido
                foreach ($items as $item) {
                    $productId = (int)($item['product']['id'] ?? 0);
                    if ($productId > 0) {
                        $engine->trackInteraction(
                            $companyId,
                            $productId,
                            'purchase',
                            $customerId,
                            null
                        );
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao registrar purchase para ML: " . $e->getMessage());
            }

            // Marcar cupom de desconto de fidelidade como usado
            if (!empty($_SESSION['couponCode'])) {
                try {
                    // Buscar cupom para verificar se é genérico ou individual
                    $stmt = $db->prepare('
                        SELECT customer_phone, usage_limit, times_used
                        FROM customer_loyalty_coupons 
                        WHERE coupon_code = ? AND company_id = ?
                    ');
                    $stmt->execute([$_SESSION['couponCode'], $companyId]);
                    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($coupon) {
                        if (!empty($coupon['customer_phone'])) {
                            // Cupom individual - marcar como usado (is_used = 1)
                            $stmt = $db->prepare('
                                UPDATE customer_loyalty_coupons 
                                SET is_used = 1, used_at = NOW(), order_id = ?, times_used = times_used + 1
                                WHERE coupon_code = ? AND company_id = ?
                            ');
                            $stmt->execute([$orderId, $_SESSION['couponCode'], $companyId]);
                        } else {
                            // Cupom genérico - incrementar contador
                            $stmt = $db->prepare('
                                UPDATE customer_loyalty_coupons 
                                SET times_used = times_used + 1, used_at = NOW()
                                WHERE coupon_code = ? AND company_id = ?
                            ');
                            $stmt->execute([$_SESSION['couponCode'], $companyId]);
                            
                            // Registrar uso na tabela de rastreamento
                            $stmt = $db->prepare('
                                INSERT IGNORE INTO coupon_usage 
                                (company_id, coupon_code, customer_phone, order_id)
                                VALUES (?, ?, ?, ?)
                            ');
                            $stmt->execute([$companyId, $_SESSION['couponCode'], $clean['phone'], $orderId]);
                        }
                    }
                    
                    // Limpar cupom da sessão após marcar como usado
                    unset($_SESSION['couponCode'], $_SESSION['couponDiscount']);
                } catch (Exception $e) {
                    error_log("Erro ao marcar cupom como usado: " . $e->getMessage());
                }
            }

            // Emitir evento do pedido (protegido contra erros)
            try {
                Order::emitOrderEvent($db, $orderId, $companyId, 'order.created');
            } catch (Exception $e) {
                // Log do erro mas não interrompe o fluxo do pedido
                error_log("Erro ao emitir evento do pedido: " . $e->getMessage());
            }

            // Enviar notificação de novo pedido para grupos configurados
            try {
                $orderData = [
                    'id' => $orderId,
                    'customer_name' => $clean['name'],
                    'customer_phone' => $clean['phone'],
                    'total' => $total,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'discount' => $discount,
                    'loyalty_discount' => $loyaltyDiscount,
                    'payment_method' => $paymentMethod ? $paymentMethod['name'] : 'Não informado',
                    'payment_type' => $paymentMethod ? ($paymentMethod['type'] ?? '') : '',
                    'items' => array_map(function($item) use ($db, $companyId) {
                        $product = Product::find($item['product']['id'] ?? 0, true, (int)$companyId);
                        $itemData = [
                            'name' => $product['name'] ?? 'Produto',
                            'quantity' => $item['qty'] ?? 1,
                            'price' => $item['unit_price'] ?? 0,
                            'customization_delta' => (float)($item['customization']['total_delta'] ?? 0),
                            'combo' => '',
                            'customization' => '',
                            'component_customizations' => []
                        ];
                        
                        // Processar dados de combo - enviar o JSON completo se disponível
                        if (isset($item['combo'])) {
                            if (is_array($item['combo'])) {
                                // Se for array, converter para JSON para enviar
                                $itemData['combo'] = json_encode($item['combo']);
                            } else {
                                // Se já for string, usar diretamente
                                $itemData['combo'] = $item['combo'];
                            }
                        }
                        
                        // Adicionar component_customizations para processar extras dos itens do combo
                        if (isset($item['component_customizations']) && is_array($item['component_customizations'])) {
                            $itemData['component_customizations'] = $item['component_customizations'];
                        }
                        
                        // Processar dados de personalização (mostrar apenas adições/remoções, NÃO inclusos)
                        if (isset($item['customization']) && is_array($item['customization'])) {
                            $customParts = [];
                            if (!empty($item['customization']['groups'])) {
                                foreach ($item['customization']['groups'] as $group) {
                                    $groupName = $group['name'] ?? '';
                                    $groupType = $group['type'] ?? 'qty';
                                    
                                    if (!empty($group['items'])) {
                                        foreach ($group['items'] as $customItem) {
                                            $itemName = $customItem['name'] ?? '';
                                            $qty = $customItem['qty'] ?? 1;
                                            $deltaQty = $customItem['delta_qty'] ?? null;
                                            $price = $customItem['price'] ?? 0;
                                            $status = $customItem['status'] ?? ''; // 'Incluso', 'Extra', etc
                                            $isRemoved = !empty($customItem['removed']);
                                            
                                            // NÃO mostrar ingredientes inclusos (sem custo e delta 0)
                                            // Mostrar apenas:
                                            // 1. Items removidos (flag removed)
                                            // 2. Items com delta_qty != 0 (adicionados ou removidos)
                                            // 3. Items com preço > 0 (extras pagos)
                                            // 4. Items tipo addon/single (sempre customização ativa)
                                            
                                            if ($itemName) {
                                                // Grupos pool (açaí): mostrar TODOS os itens com qty > 0
                                                if ($groupType === 'pool') {
                                                    if ($qty > 0) {
                                                        $paidQty = (int)($customItem['paid_qty'] ?? 0);
                                                        $unitPrice = (float)($customItem['unit_price'] ?? $price);
                                                        $priceText = ($paidQty > 0 && $unitPrice > 0)
                                                            ? ' (+' . MoneyFormatter::format($paidQty * $unitPrice) . ')'
                                                            : '';
                                                        $customParts[] = $qty > 1
                                                            ? "{$qty}x {$itemName}{$priceText}"
                                                            : "{$itemName}{$priceText}";
                                                    }
                                                    continue;
                                                }

                                                // Verificar se é "Incluso" - NÃO mostrar
                                                // Incluso = sem preço E sem modificação de quantidade E não removido
                                                $isIncluso = (
                                                    !$isRemoved &&
                                                    ($status === 'Incluso' || 
                                                    ($price == 0 && ($deltaQty === null || $deltaQty == 0)))
                                                );
                                                
                                                // Decidir se mostra baseado no tipo e se não é incluso
                                                $shouldShow = false;
                                                
                                                // Remoções sempre devem aparecer
                                                if ($isRemoved) {
                                                    $shouldShow = true;
                                                } elseif (!$isIncluso) {
                                                    // Para tipos addon/single/choice: sempre mostrar
                                                    if (in_array($groupType, ['addon', 'single', 'choice'])) {
                                                        $shouldShow = true;
                                                    }
                                                    // Para tipo qty: mostrar apenas se delta_qty != 0 ou tem preço
                                                    elseif ($groupType === 'qty') {
                                                        if ($deltaQty !== null && $deltaQty != 0) {
                                                            $shouldShow = true;
                                                        } elseif ($price > 0) {
                                                            $shouldShow = true;
                                                        }
                                                    }
                                                }
                                                
                                                if ($shouldShow && !empty($itemName)) {
                                                    // Formatar com preço quando aplicável
                                                    $priceText = '';
                                                    if ($price > 0) {
                                                        $priceText = ' (+' . MoneyFormatter::format($price) . ')';
                                                    }
                                                    
                                                    // Formatar quantidade
                                                    if ($isRemoved) {
                                                        // Item removido (flag removed)
                                                        $customParts[] = "Sem {$itemName}";
                                                    } elseif ($deltaQty !== null && $deltaQty > 0) {
                                                        // Item adicionado
                                                        $customParts[] = "+{$deltaQty}x {$itemName}{$priceText}";
                                                    } elseif ($deltaQty !== null && $deltaQty < 0) {
                                                        // Item removido (por delta negativo)
                                                        $customParts[] = "Sem {$itemName}";
                                                    } elseif ($qty > 1) {
                                                        $customParts[] = "{$qty}x {$itemName}{$priceText}";
                                                    } else {
                                                        $customParts[] = "{$itemName}{$priceText}";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if ($customParts) {
                                $itemData['customization'] = implode("\n", $customParts);
                            }
                        }
                        
                        return $itemData;
                    }, $items),
                    'notes' => $orderNotes,
                    'customer_address' => $formattedAddress,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                // Notificação será enviada APÓS o commit para não bloquear o checkout
                $pendingNotification = ['companyId' => $companyId, 'orderData' => $orderData];
            } catch (Exception $e) {
                // Log do erro mas não interrompe o fluxo do pedido
                error_log("Erro ao preparar notificação de pedido: " . $e->getMessage());
                $pendingNotification = null;
            }

            // Programa de Fidelidade — progresso será incrementado ao marcar pedido como completed
            // (via Order::updateStatus) para evitar contagem de pedidos cancelados
            $loyaltyReward = null;

            $db->commit();
            
            // Salvar notificação pendente em sessão para envio na página processing
            if (isset($pendingNotification) && $pendingNotification) {
                $_SESSION['pending_notification'] = $pendingNotification;
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Checkout error: ' . $e->getMessage());
            error_log('Checkout error trace: ' . $e->getTraceAsString());

            $_SESSION['checkout_flash'] = [
                'type' => 'error',
                'message' => 'Não foi possível finalizar o pedido. ' . $e->getMessage(),
            ];
            $_SESSION['checkout_address'] = $clean;
            
            // Limpar qualquer output anterior
            if (ob_get_level()) {
                ob_clean();
            }
            
            header('Location: ' . base_url($slug . '/checkout'));
            exit;
        }

        // Salvar endereço automaticamente no perfil do cliente (primeiro pedido)
        if ($customer && isset($customer['id']) && !$useSavedAddress && !empty($clean['street'])) {
            require_once __DIR__ . '/../models/CustomerAddress.php';
            
            try {
                $addressData = [
                    'customer_id'  => (int)$customer['id'],
                    'company_id'   => $companyId,
                    'label'        => '', // Cliente pode editar depois
                    'name'         => $clean['name'],
                    'phone'        => $clean['phone'],
                    'city_id'      => $clean['city_id'],
                    'zone_id'      => $clean['zone_id'],
                    'city'         => $clean['city'],
                    'neighborhood' => $clean['neighborhood'],
                    'street'       => $clean['street'],
                    'number'       => $clean['number'],
                    'complement'   => $clean['complement'],
                    'reference'    => $clean['reference'],
                ];
                
                CustomerAddress::createAddress($addressData);
                error_log("Endereço salvo automaticamente para cliente ID: " . $customer['id']);
            } catch (Exception $e) {
                // Não interrompe o fluxo se falhar ao salvar o endereço
                error_log("Erro ao salvar endereço automaticamente: " . $e->getMessage());
            }
            
            // Sync street to autocomplete database (continuous learning)
            try {
                require_once __DIR__ . '/../services/AddressAutocompleteService.php';
                $autocomplete = new \AddressAutocompleteService(db(), $companyId);
                $autocomplete->syncFromOrder($clean['city'], $clean['neighborhood'], $clean['street'], $clean['phone'] ?? '');
            } catch (\Exception $e) {
                // Non-critical — don't block checkout
            }
        }

        $_SESSION['checkout_address'] = $clean;
        $_SESSION['checkout_address']['delivery_fee'] = $deliveryFee;

        $this->storage->clearCart();

        unset($_SESSION['checkout_flash']);

        $_SESSION['checkout_success'] = [
            'order_id'           => $orderId,
            'customer_name'      => $clean['name'],
            'customer_phone'     => $clean['phone'],
            'total'              => $total,
            'subtotal'           => $subtotal,
            'delivery_fee'       => $deliveryFee,
            'payment_method'     => $paymentMethodName,
            'payment_type'       => $paymentMethod ? ($paymentMethod['type'] ?? '') : '',
            'payment_instructions' => $paymentInstructions,
            'address'            => $formattedAddress,
            'notes'              => $clean['notes'],
            'items'              => $itemsSummary,
            'cash_amount'        => $cashAmount > 0 ? $cashAmount : 0,
            'cash_change'        => ($cashAmount > 0 && $cashAmount > $total) ? $cashAmount - $total : 0,
            'loyalty_reward'     => $loyaltyReward,
        ];

        // Armazenar dados do pedido em processamento
        $_SESSION['order_processing'] = [
            'order_number' => '#' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT),
            'order_id'     => $orderId,
        ];

        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Location: ' . base_url($slug . '/checkout/processing'));
        exit;
    }

    /** POST /{slug}/cart/add */
    public function add($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';

            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $requireLogin = (bool)(config('login_required') ?? false);

        if ($requireLogin && !AuthCustomer::current()) {
            $redirect = $productId > 0
                ? base_url($slug . '/produto/' . $productId . '')
                : base_url($slug . '');
            header('Location: ' . $redirect);
            exit;
        }

        $product = $productId > 0 ? Product::find($productId, true, (int)$company['id']) : null;

        if (!$product || (int)($product['company_id'] ?? 0) !== (int)$company['id'] || (int)($product['active'] ?? 0) !== 1) {
            http_response_code(404);
            return $this->view('public/product-not-found', [
                'company' => $company,
            ]);
        }

        $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
        $qty = max(1, min(99, $qty));

        $postCombo = isset($_POST['combo']) && is_array($_POST['combo']) ? $_POST['combo'] : [];
        $comboSelection = $this->resolveComboSelection($product, $postCombo);

        $baseCustomization = $this->snapshotCustomization($productId);

        if ($baseCustomization === null) {
            $baseCustomization = $this->defaultCustomizationSnapshot($productId);
        }
        // A personalização em sessão é temporária: após adicionar, não deve contaminar novas adições.
        $this->storage->removeCustomization($productId, null, null);
        $componentCustomizations = [];

        if ($comboSelection) {
            // Buscar dados completos dos grupos do combo para obter default_qty
            $comboGroups = Product::getComboGroupsWithItems($productId);
            $itemDefaultQtys = [];
            
            // Mapear simple_id => default_qty para cada item dos grupos
            foreach ($comboGroups as $group) {
                foreach (($group['items'] ?? []) as $item) {
                    $simpleId = DataValidator::getInt($item, 'simple_id', 'simple_product_id');
                    $isDefault = !empty($item['default']) || !empty($item['is_default']);
                    $defaultQty = DataValidator::getInt($item, 'default_qty');
                    if ($defaultQty <= 0 && $isDefault) {
                        $defaultQty = 1;
                    }
                    if ($simpleId > 0) {
                        $itemDefaultQtys[$simpleId] = max($itemDefaultQtys[$simpleId] ?? 0, $defaultQty);
                    }
                }
            }
            
            foreach ($comboSelection as $value) {
                $ids = is_array($value) ? $value : [$value];

                foreach ($ids as $sid) {
                    $sid = (int)$sid;

                    if ($sid <= 0) {
                        continue;
                    }
                    
                    $defaultQty = $itemDefaultQtys[$sid] ?? 1;
                    
                    // Se o item tem múltiplas unidades, buscar personalização de cada unidade
                    if ($defaultQty > 1) {
                        $unitCustomizations = [];
                        
                        for ($unit = 1; $unit <= $defaultQty; $unit++) {
                            $unitSnap = $this->snapshotCustomization($sid, $productId, $unit);
                            
                            if ($unitSnap !== null) {
                                $unitCustomizations[$unit] = $unitSnap;
                            } else {
                                // Se não tem personalização para esta unidade, usar a padrão
                                $unitCustomizations[$unit] = $this->defaultCustomizationSnapshot($sid);
                            }
                            // Limpar personalização da unidade
                            $this->storage->removeCustomization($sid, null, $productId, $unit);
                        }
                        
                        // Armazenar a estrutura com unit_customizations (sempre terá valores)
                        $componentCustomizations[$sid] = [
                            'unit_customizations' => $unitCustomizations
                        ];
                    } else {
                        // Item com quantidade 1: buscar personalização sem unitIndex
                        $snap = $this->snapshotCustomization($sid, $productId);

                        if ($snap === null) {
                            $snap = $this->defaultCustomizationSnapshot($sid);
                        }

                        if ($snap) {
                            $componentCustomizations[$sid] = $snap;
                        }
                    }
                    
                    // Limpar personalização contextualizada base (sem unitIndex)
                    $this->storage->removeCustomization($sid, null, $productId);
                }
            }
        }

        // iOS FIX: Aceitar cross_sell tanto do POST quanto do GET (action modificado)
        $crossSellFromPost = isset($_POST['cross_sell']) && is_array($_POST['cross_sell']) ? $_POST['cross_sell'] : [];
        $crossSellFromGet = isset($_GET['cross_sell']) && is_array($_GET['cross_sell']) ? $_GET['cross_sell'] : [];
        
        // Merge: priorizar POST, mas usar GET se POST estiver vazio
        $crossSellData = !empty($crossSellFromPost) ? $crossSellFromPost : $crossSellFromGet;
        
        if (!empty($crossSellData)) {
            // Atualizar $_POST para que o restante do código funcione normalmente
            $_POST['cross_sell'] = $crossSellData;
        }

        // Fallback: se o cliente não enviou cross_sell no POST, tentar recuperar
        // cross-sells personalizados que já foram salvos como customizations
        // no formato parentId:productId (por exemplo "10:2"). Isso cobre casos
        // em que o JS do cliente falhou em adicionar inputs hidden antes do submit.
        if ((empty($_POST['cross_sell']) || !is_array($_POST['cross_sell']))) {
            try {
                $customs = $this->storage->getCustomizations();
                $recovered = [];

                foreach ($customs as $key => $val) {
                    if (!is_string($key)) {
                        continue;
                    }

                    // Chaves no formato parentId:productId
                    if (strpos($key, $productId . ':') === 0) {
                        $parts = explode(':', $key, 2);
                        $csId = isset($parts[1]) ? (int)$parts[1] : 0;

                        if ($csId > 0) {
                            $recovered[] = $csId;
                        }
                    }
                }

                if ($recovered) {
                    // Inserir no _POST para que o restante do fluxo processe normalmente
                    $_POST['cross_sell'] = $recovered;
                }
            } catch (Throwable $e) {
            }
        }
        
        // 🛒 RECUPERAR CARRINHO EXISTENTE ANTES DE ADICIONAR NOVOS ITENS
        $cartRef = $this->storage->getCart();
        
        if (!is_array($cartRef)) {
            $cartRef = [];
        }
        
        // Adicionar produto principal
        $cartRef[] = [
            'uid' => $this->generateUid(),
            'company_id' => (int)$company['id'],
            'product_id' => $productId,
            'qty' => $qty,
            'combo' => $comboSelection,
            'customization' => $baseCustomization,
            'combo_customizations' => $componentCustomizations,
            'added_at' => time(),
        ];

        // Processar produtos de cross-sell
        if (isset($_POST['cross_sell']) && is_array($_POST['cross_sell'])) {
            
            foreach ($_POST['cross_sell'] as $crossSellId) {
                $crossSellId = (int)$crossSellId;
                
                if ($crossSellId <= 0) {
                    continue;
                }
                
                $crossSellProduct = Product::find($crossSellId, true, (int)$company['id']);
                
                if (!$crossSellProduct) {
                    continue;
                }
                
                if ((int)($crossSellProduct['company_id'] ?? 0) !== (int)$company['id']) {
                    continue;
                }
                
                if ((int)($crossSellProduct['active'] ?? 0) !== 1) {
                    continue;
                }
                
                // Adicionar produto de cross-sell ao carrinho (quantidade 1)
                // Buscar personalização usando contexto (parentId = productId principal)
                $crossSellCustomization = $this->snapshotCustomization($crossSellId, $productId);
                
                if ($crossSellCustomization === null) {
                    $crossSellCustomization = $this->defaultCustomizationSnapshot($crossSellId);
                }
                
                $cartItem = [
                    'uid' => $this->generateUid(),
                    'company_id' => (int)$company['id'],
                    'product_id' => $crossSellId,
                    'qty' => 1,
                    'combo' => null,
                    'customization' => $crossSellCustomization,
                    'combo_customizations' => [],
                    'added_at' => time(),
                ];
                
                $cartRef[] = $cartItem;
                
                // Limpar personalização contextualizada após adicionar ao carrinho
                $this->storage->removeCustomization($crossSellId, null, $productId);
            }
        }
        
        $this->storage->setCart($cartRef);

        header('Location: ' . base_url($slug . '/cart'));
        exit;
    }

    /** POST /{slug}/cart/update */
    public function update($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';

            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $requireLogin = (bool)(config('login_required') ?? false);

        if ($requireLogin && !AuthCustomer::current()) {
            $redirect = base_url($slug . '');
            header('Location: ' . $redirect);
            exit;
        }

        $uid = isset($_POST['uid']) ? (string)$_POST['uid'] : '';

        if ($uid === '') {
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        $action = $_POST['action'] ?? null;
        $qtyParam = isset($_POST['qty']) ? (int)$_POST['qty'] : null;

        $cartRef = $this->storage->getCart();

        foreach ($cartRef as $index => &$item) {
            if (!is_array($item)) {
                continue;
            }

            if ((string)($item['uid'] ?? '') !== $uid) {
                continue;
            }

            if ((int)($item['company_id'] ?? 0) !== (int)$company['id']) {
                continue;
            }

            if ($action === 'inc') {
                $item['qty'] = min(99, max(1, (int)($item['qty'] ?? 1) + 1));
                break;
            }

            if ($action === 'dec') {
                $current = (int)($item['qty'] ?? 1);
                $newQty = max(0, $current - 1);

                if ($newQty <= 0) {
                    unset($cartRef[$index]);
                } else {
                    $item['qty'] = $newQty;
                }
                break;
            }

            if ($qtyParam !== null) {
                if ($qtyParam <= 0) {
                    unset($cartRef[$index]);
                } else {
                    $item['qty'] = min(99, max(1, $qtyParam));
                }
                break;
            }
        }
        unset($item);

        // Reindexa e persiste
        $cartRef = array_values($cartRef);
        $this->storage->setCart($cartRef);

        if (!$cartRef) {
            unset($_SESSION['checkout_address'], $_SESSION['checkout_flash']);
        }

        header('Location: ' . base_url($slug . '/cart'));
        exit;
    }

    /**
     * Valida cupom de desconto (fidelidade)
     */
    public function validateCoupon($params)
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company) {
            echo json_encode(['success' => false, 'message' => 'Empresa não encontrada']);
            exit;
        }

        // Verificar se cliente está logado
        $customer = AuthCustomer::current();
        if (!$customer) {
            echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para usar cupons']);
            exit;
        }

        // Ler JSON do body
        $input = json_decode(file_get_contents('php://input'), true);
        $couponCode = strtoupper(trim($input['coupon_code'] ?? ''));

        if (empty($couponCode)) {
            echo json_encode(['success' => false, 'message' => 'Código de cupom inválido']);
            exit;
        }

        try {
            $db = db();
            
            // Buscar cupom na tabela
            $stmt = $db->prepare('
                SELECT id, discount_percentage, is_used, customer_phone, usage_limit, times_used, allow_multiple_uses_per_customer
                FROM customer_loyalty_coupons 
                WHERE company_id = ? 
                AND coupon_code = ?
                LIMIT 1
            ');
            $stmt->execute([$company['id'], $couponCode]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$coupon) {
                echo json_encode(['success' => false, 'message' => 'Cupom não encontrado']);
                exit;
            }

            // Verificar se é cupom individual (tem customer_phone)
            if (!empty($coupon['customer_phone'])) {
                // Cupom individual - verificar se pertence ao cliente
                if ($coupon['customer_phone'] !== $customer['whatsapp']) {
                    echo json_encode(['success' => false, 'message' => 'Este cupom não pertence a você']);
                    exit;
                }
                
                // Verificar limite de uso (cupons individuais também têm usage_limit)
                $usageLimit = (int)($coupon['usage_limit'] ?? 1);
                $timesUsed = (int)($coupon['times_used'] ?? 0);
                
                // Cupom usado = is_used=1 OU times_used >= usage_limit
                if ((int)$coupon['is_used'] === 1 && $timesUsed > 0) {
                    echo json_encode(['success' => false, 'message' => 'Este cupom já foi utilizado']);
                    exit;
                }
                
                if ($usageLimit > 0 && $timesUsed >= $usageLimit) {
                    echo json_encode(['success' => false, 'message' => 'Este cupom atingiu o limite de uso']);
                    exit;
                }
            } else {
                // Cupom genérico - verificar limite de uso
                $usageLimit = (int)($coupon['usage_limit'] ?? 1);
                $timesUsed = (int)($coupon['times_used'] ?? 0);
                $allowMultipleUses = (int)($coupon['allow_multiple_uses_per_customer'] ?? 0);
                
                if ($usageLimit > 0 && $timesUsed >= $usageLimit) {
                    echo json_encode(['success' => false, 'message' => 'Este cupom atingiu o limite de uso']);
                    exit;
                }
                
                // Se NÃO permite múltiplos usos por cliente, verificar se cliente já usou
                if ($allowMultipleUses === 0) {
                    $stmt = $db->prepare('
                        SELECT id
                        FROM coupon_usage 
                        WHERE company_id = ? 
                        AND coupon_code = ?
                        AND customer_phone = ?
                        LIMIT 1
                    ');
                    $stmt->execute([$company['id'], $couponCode, $customer['whatsapp']]);
                    
                    if ($stmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'Você já usou este cupom anteriormente']);
                        exit;
                    }
                }
                // Se permite múltiplos usos, qualquer cliente pode usar até atingir o limite global
            }

            // Cupom válido!
            echo json_encode([
                'success' => true,
                'discount' => (float)$coupon['discount_percentage'],
                'message' => 'Cupom aplicado com sucesso!'
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao validar cupom']);
            exit;
        }
    }

    /**
     * Sincroniza cupom do sessionStorage para a sessão PHP
     * 🔐 Atualizado com validação de segurança
     */
    public function syncCoupon($params)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        try {
            // Ler JSON do body
            $input = json_decode(file_get_contents('php://input'), true);
            $couponCode = trim($input['coupon_code'] ?? '');
            $couponDiscount = (float)($input['coupon_discount'] ?? 0);
            $customerId = (int)($input['customer_id'] ?? 0);
            
            // 🔐 Validar token de sessão do header
            $sessionToken = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? '';
            if (!empty($sessionToken) && function_exists('validate_session_token')) {
                if (!validate_session_token($sessionToken)) {
                    if (function_exists('log_security_event')) {
                        log_security_event('sync_coupon_invalid_token', [
                            'provided_token' => substr($sessionToken, 0, 16) . '...',
                            'customer_id_param' => $customerId
                        ]);
                    }
                    echo json_encode(['success' => false, 'message' => 'Token de sessão inválido']);
                    exit;
                }
            }
            
            // 🔐 Validar que o customer_id corresponde à sessão
            $sessionCustomerId = (int)($_SESSION['customer_id'] ?? $_SESSION['customer']['id'] ?? 0);
            if ($customerId > 0 && $sessionCustomerId > 0 && $customerId !== $sessionCustomerId) {
                if (function_exists('log_security_event')) {
                    log_security_event('sync_coupon_customer_mismatch', [
                        'session_customer_id' => $sessionCustomerId,
                        'request_customer_id' => $customerId
                    ]);
                }
                echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
                exit;
            }

            if (!empty($couponCode) && $couponDiscount > 0) {
                $_SESSION['couponCode'] = $couponCode;
                $_SESSION['couponDiscount'] = $couponDiscount;
                
                if (function_exists('log_security_event')) {
                    log_security_event('coupon_synced', [
                        'coupon_code' => $couponCode,
                        'discount' => $couponDiscount,
                        'customer_id' => $sessionCustomerId
                    ]);
                }
            }

            echo json_encode(['success' => true]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao sincronizar cupom']);
            exit;
        }
    }

    /**
     * POST /{slug}/checkout/calculate — totais do checkout (servidor = fonte de verdade).
     */
    public function calculateCheckout(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $slug = $params['slug'] ?? '';
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Empresa não encontrada']);

            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $zoneId = (int)($input['zone_id'] ?? 0);
        $cityId = (int)($input['city_id'] ?? 0);
        $companyId = (int)$company['id'];

        $cartRef = $this->storage->getCart();
        $items = $this->hydrateCartItems($cartRef, $company);

        if (!$items) {
            echo json_encode(['success' => false, 'message' => 'Carrinho vazio']);

            return;
        }

        $db = db();
        $subtotal = 0.0;
        $loyaltyDiscount = 0.0;
        $embeddedFee = (float)($company['embedded_delivery_fee'] ?? 0);
        $embeddedEnabledIds = [];

        if ($embeddedFee > 0) {
            $stmtEmbedded = $db->prepare('SELECT id FROM products WHERE company_id = ? AND embedded_fee_enabled = 1');
            $stmtEmbedded->execute([$companyId]);
            $embeddedEnabledIds = array_map('intval', array_column($stmtEmbedded->fetchAll(PDO::FETCH_ASSOC), 'id'));
        }

        foreach ($items as $item) {
            $subtotal += (float)$item['line_total'];
            $qty = (int)($item['qty'] ?? 1);
            $itemProductId = (int)($item['product']['id'] ?? 0);

            if ($embeddedFee > 0 && $qty > 0 && in_array($itemProductId, $embeddedEnabledIds, true)) {
                $loyaltyDiscount += ($embeddedFee * $qty);
            }
        }

        $deliveryFee = 0.0;

        if ($zoneId > 0) {
            $zone = DeliveryZone::findForCompany($zoneId, $companyId);

            if (!$zone || ($cityId > 0 && (int)($zone['city_id'] ?? 0) !== $cityId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Zona inválida']);

                return;
            }
            $deliveryFee = (float)($zone['fee'] ?? 0);
        }

        $freeShippingMin = !empty($company['delivery_free_min_value']) ? (float)$company['delivery_free_min_value'] : 0.0;

        if ($freeShippingMin > 0 && $subtotal >= $freeShippingMin) {
            $deliveryFee = 0.0;
        }

        $couponPct = !empty($_SESSION['couponCode']) ? (float)($_SESSION['couponDiscount'] ?? 0) : 0.0;
        $zonesRaw = DeliveryZone::allByCompany($companyId);
        $zonesPresent = count($zonesRaw) > 0;

        $calc = CheckoutTotalsService::compute([
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'loyalty_discount' => $loyaltyDiscount,
            'coupon_percentage' => $couponPct,
            'selected_zone_id' => $zoneId,
            'zones_present' => $zonesPresent,
        ]);

        echo json_encode([
            'success' => true,
            'data' => [
                'subtotal' => $subtotal,
                'loyalty_discount' => $loyaltyDiscount,
                'coupon_discount' => $calc['couponDiscount'],
                'delivery_fee' => $deliveryFee,
                'final_delivery_fee' => $calc['finalDeliveryFee'],
                'delivery_discount_applied' => $calc['deliveryDiscountApplied'],
                'remaining_loyalty_discount' => $calc['remainingLoyaltyDiscount'],
                'total' => $calc['total'],
                'delivery_label' => $calc['deliveryLabel'],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST /{slug}/reorder/{orderId}
     * Repopula o carrinho com os itens de um pedido anterior.
     */
    public function reorder(array $params): void
    {
        $slug = $params['slug'] ?? '';
        $orderId = (int)($params['orderId'] ?? 0);

        $company = Company::findBySlug($slug);
        if (!$company || !$company['active']) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        // Verificar se o cliente está logado
        $customer = $_SESSION['customer'] ?? null;
        if (!$customer) {
            header('Location: ' . base_url(rawurlencode($slug) . '?login=1'));
            exit;
        }

        // Verificar que o pedido pertence ao cliente logado
        $db = $this->db();
        $order = Order::findWithItems($db, $orderId, (int)$company['id']);
        if (!$order) {
            header('Location: ' . base_url(rawurlencode($slug) . '/profile?error=reorder_not_found'));
            exit;
        }

        $customerPhone = preg_replace('/[^0-9]/', '', $customer['whatsapp'] ?? $customer['e164'] ?? '');
        $orderPhone = preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? '');
        // Normaliza DDI 55: remove prefixo se o número tiver 12+ dígitos (ex: 5511999999999 → 11999999999)
        $stripDdi = static function (string $p): string {
            return (strlen($p) >= 12 && str_starts_with($p, '55')) ? substr($p, 2) : $p;
        };
        if ($stripDdi($customerPhone) !== $stripDdi($orderPhone)) {
            header('Location: ' . base_url(rawurlencode($slug) . '/profile?error=reorder_not_yours'));
            exit;
        }

        // Buscar itens reordenáveis (apenas produtos ainda ativos)
        $items = Order::getReorderableItems($orderId, (int)$company['id']);
        if (empty($items)) {
            header('Location: ' . base_url(rawurlencode($slug) . '/profile?error=reorder_unavailable'));
            exit;
        }

        // Reconstruir carrinho
        $cart = $this->storage->getCart();
        $added = 0;

        foreach ($items as $item) {
            $cart[] = [
                'uid'                   => $this->generateUid(),
                'company_id'            => (int)$company['id'],
                'product_id'            => (int)$item['product_id'],
                'qty'                   => (int)$item['quantity'],
                'combo'                 => $item['combo_data'] ? json_decode($item['combo_data'], true) : null,
                'customization'         => $item['customization_data'] ? json_decode($item['customization_data'], true) : null,
                'combo_customizations'  => [],
                'added_at'              => time(),
            ];
            $added++;
        }

        $this->storage->setCart($cart);
        $_SESSION['cart'] = $cart;

        $skipped = count(Order::getItems($orderId, (int)$company['id'])) - $added;
        $qs = $skipped > 0 ? '?reorder=partial&added=' . $added . '&skipped=' . $skipped : '?reorder=ok';

        header('Location: ' . base_url(rawurlencode($slug) . '/cart' . $qs));
        exit;
    }
}
