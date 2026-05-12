<?php
// Sessão: SessionManager no front controller; validação em PublicCartController::checkout.

// Variáveis seguras
$company         = is_array($company ?? null) ? $company : [];
$items           = is_array($items ?? null) ? $items : [];
$totals          = is_array($totals ?? null) ? $totals : [];
$address         = is_array($deliveryAddress ?? null) ? $deliveryAddress : [];
$cities          = is_array($cities ?? null) ? $cities : [];
$zonesByCity     = is_array($zonesByCity ?? null) ? $zonesByCity : [];
$paymentMethods  = is_array($paymentMethods ?? null) ? $paymentMethods : [];
$customerData    = is_array($customer ?? null) ? $customer : [];
$checkoutTotals  = is_array($checkoutTotals ?? null) ? $checkoutTotals : [];

$slug      = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$slugClean = trim($slug, '/');
$cartUrl   = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'cart') : '#';
$submitUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'checkout') : '#';

$subtotal        = (float)($totals['subtotal'] ?? 0.0);
$deliveryFee     = (float)($totals['delivery'] ?? 0.0);
$loyaltyDiscount = (float)($totals['loyalty_discount'] ?? 0.0);

$couponCode = isset($couponCode) ? (string)$couponCode : '';
$couponPercentage = isset($couponPercentage) ? (float)$couponPercentage : 0.0;

$selectedCityId    = isset($selectedCityId) ? (int)$selectedCityId : (int)($address['city_id'] ?? 0);
$selectedZoneId    = isset($selectedZoneId) ? (int)$selectedZoneId : (int)($address['zone_id'] ?? 0);
$selectedPaymentId = isset($selectedPaymentId) ? (int)$selectedPaymentId : (int)($address['payment_method_id'] ?? 0);
$zonesPresent = isset($zonesPresent) ? (bool)$zonesPresent : false;

$couponDiscount = (float)($checkoutTotals['coupon_discount'] ?? 0.0);
$deliveryDiscountApplied = (float)($checkoutTotals['delivery_discount_applied'] ?? 0.0);
$remainingLoyaltyDiscount = (float)($checkoutTotals['remaining_loyalty_discount'] ?? 0.0);
$finalDeliveryFee = (float)($checkoutTotals['final_delivery_fee'] ?? $deliveryFee);
$total = (float)($checkoutTotals['total'] ?? ($subtotal + $deliveryFee - $loyaltyDiscount));
$deliveryLabel = (string)($checkoutTotals['delivery_label'] ?? '');

$cardBrandMapping = function_exists('payment_card_brand_filenames')
    ? payment_card_brand_filenames()
    : [
        'visa' => 'visa.svg',
        'mastercard' => 'mastercard.svg',
        'master' => 'mastercard.svg',
        'elo' => 'elo.svg',
        'hipercard' => 'hipercard.svg',
        'hiper' => 'hipercard.svg',
        'diners' => 'diners.svg',
        'american express' => 'others.svg',
        'amex' => 'others.svg',
    ];

$selectedPayment = null;

foreach ($paymentMethods as $method) {
    if ((int)($method['id'] ?? 0) === $selectedPaymentId) {
        $selectedPayment = $method;
        break;
    }
}

// Auto-select PIX if no method is pre-selected or if PIX is available
if (!$selectedPayment && $paymentMethods) {
    // First try to find PIX method
    $pixMethod = null;
    foreach ($paymentMethods as $method) {
        if (($method['type'] ?? '') === 'pix') {
            $pixMethod = $method;
            break;
        }
    }
    
    if ($pixMethod) {
        $selectedPayment = $pixMethod;
        $selectedPaymentId = (int)($selectedPayment['id'] ?? 0);
    } else {
        // Fallback to first available method
        $selectedPayment = $paymentMethods[0];
        $selectedPaymentId = (int)($selectedPayment['id'] ?? 0);
    }
}
$paymentInstructions = (string)($selectedPayment['instructions'] ?? '');
$flash = is_array($flash ?? null) ? $flash : null;

$addressCityName = (string)($address['city'] ?? '');
$addressNeighborhoodName = (string)($address['neighborhood'] ?? '');

$citiesForJs = array_map(static function ($city) {
    return [
      'id'   => (int)($city['id'] ?? 0),
      'name' => (string)($city['name'] ?? ''),
    ];
}, $cities);

$zonesForJs = [];

foreach ($zonesByCity as $cityId => $zoneList) {
    $cityKey = (string)$cityId;
    $zonesForJs[$cityKey] = [];

    foreach ($zoneList as $zone) {
        $zonesForJs[$cityKey][] = [
          'id'        => (int)($zone['id'] ?? 0),
          'city_id'   => (int)($zone['city_id'] ?? 0),
          'name'      => (string)($zone['name'] ?? ''),
          'fee'       => (float)($zone['fee'] ?? 0),
          'city_name' => (string)($zone['city_name'] ?? ''),
        ];
    }
}

$savedAddresses = is_array($savedAddresses ?? null) ? $savedAddresses : [];
$hasAddresses = count($savedAddresses) > 0;
$selectedAddressId = (int)($address['address_id'] ?? 0);

// Mapear taxas de entrega por zone_id
$zoneFeesMap = [];
foreach ($zonesByCity as $cityZones) {
    foreach ($cityZones as $zone) {
        $zoneFeesMap[(int)($zone['id'] ?? 0)] = (float)($zone['fee'] ?? 0);
    }
}

$paymentMethodsForJs = [];
foreach ($paymentMethods as $method) {
    $methodId = (int)($method['id'] ?? 0);
    if ($methodId <= 0) {
        continue;
    }

    $metaArr = [];
    if (!empty($method['meta'])) {
        $metaArr = is_string($method['meta']) ? json_decode($method['meta'], true) : (is_array($method['meta']) ? $method['meta'] : []);
    }
    if (!is_array($metaArr)) {
        $metaArr = [];
    }

    $pxKey = (string)($method['pix_key'] ?? ($metaArr['px_key'] ?? ''));
    $paymentMethodsForJs[(string)$methodId] = [
      'id' => $methodId,
      'name' => (string)($method['name'] ?? 'Pagamento'),
      'type' => (string)($method['type'] ?? 'others'),
      'instructions' => (string)($method['instructions'] ?? ''),
      'pix_key' => $pxKey,
      'meta' => $metaArr,
    ];
}

$syncCouponUrl = function_exists('base_url')
    ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'sync-coupon')
    : '/' . ($slugClean !== '' ? $slugClean . '/' : '') . 'sync-coupon';

$calculateTotalsUrl = function_exists('base_url')
    ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'checkout/calculate')
    : '/' . ($slugClean !== '' ? $slugClean . '/' : '') . 'checkout/calculate';

$sessionCustomerId = (int)($_SESSION['customer_id'] ?? $_SESSION['customer']['id'] ?? 0);

$checkoutConfig = [
  'session' => [
    'customerId' => (int)$sessionCustomerId,
    'companySlug' => (string)$slugClean,
  ],
  'flags' => [
    'hasSessionCoupon' => !empty($_SESSION['couponCode']),
    'hasMultipleAddresses' => count($savedAddresses) > 1,
  ],
  'urls' => [
    'syncCouponUrl' => (string)$syncCouponUrl,
    'calculateTotalsUrl' => (string)$calculateTotalsUrl,
  ],
  'data' => [
    'subtotal' => (float)$subtotal,
    'loyaltyDiscount' => (float)$loyaltyDiscount,
    'couponDiscount' => (float)$couponDiscount,
    'freeShippingMin' => !empty($company['delivery_free_min_value']) ? (float)$company['delivery_free_min_value'] : 0.0,
    'cities' => $citiesForJs,
    'zonesByCity' => $zonesForJs,
    'selectedCityId' => (int)$selectedCityId,
    'selectedZoneId' => (int)$selectedZoneId,
    'zonesPresent' => (bool)$zonesPresent,
  ],
  'paymentMethods' => $paymentMethodsForJs,
  'selection' => [
    'selectedPaymentId' => (int)$selectedPaymentId,
  ],
];

$checkoutCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/checkout.css';
$checkoutJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/checkout.js';
$checkoutCssVersion = is_file($checkoutCssPath) ? (string) filemtime($checkoutCssPath) : (string) time();
$checkoutJsVersion = is_file($checkoutJsPath) ? (string) filemtime($checkoutJsPath) : (string) time();

?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title>Checkout — <?= e($company['name'] ?? 'Cardápio') ?></title>
<?php if (!empty($company['logo'])): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
<link rel="apple-touch-icon" href="<?= e(base_url($company['logo'])) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/checkout.css')) ?>?v=<?= e($checkoutCssVersion) ?>">
</head>
<body>

<div class="app">
  <div class="topbar">
    <div class="topwrap">
  <a class="back" href="<?= e($cartUrl) ?>" data-action="navigate" aria-label="Voltar para a sacola">
        <svg viewBox="0 0 24 24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"/></svg>
      </a>
      <div class="title">Checkout</div>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="mx-4 mt-4 rounded-xl border <?= ($flash['type'] ?? '') === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?> px-4 py-3 text-sm">
      <?= e($flash['message'] ?? '') ?>
    </div>
  <?php endif; ?>

  <form id="checkout-form" class="content" method="post" action="<?= e($submitUrl) ?>">
    <?php if (function_exists('csrf_field')): ?>
      <?= csrf_field() ?>
    <?php elseif (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php endif; ?>

    <?php if ($hasAddresses): ?>
    <!-- Endereços Salvos -->
    <section class="card">
      <h2>Endereço de entrega</h2>

      <div class="space-y-3" id="saved-addresses-list">
        <?php foreach ($savedAddresses as $savedAddr): 
          $addrId = (int)($savedAddr['id'] ?? 0);
          $isDefault = (int)($savedAddr['is_default'] ?? 0) === 1;
          $isSelected = $selectedAddressId === $addrId || ($selectedAddressId === 0 && $isDefault);
          $addrZoneId = (int)($savedAddr['zone_id'] ?? 0);
          $addrCityId = (int)($savedAddr['city_id'] ?? 0);
          $addrFee = $addrZoneId > 0 ? ($zoneFeesMap[$addrZoneId] ?? 0) : 0;
        ?>
        <label class="address-option <?= $isSelected ? 'selected' : '' ?>">
          <input 
            type="radio" 
            name="selected_address_id" 
            value="<?= $addrId ?>" 
            <?= $isSelected ? 'checked' : '' ?> 
            class="address-radio"
            data-zone-id="<?= $addrZoneId ?>"
            data-city-id="<?= $addrCityId ?>"
            data-fee="<?= number_format($addrFee, 2, '.', '') ?>"
            data-address-name="<?= e($savedAddr['name'] ?? '') ?>"
            data-address-phone="<?= e($savedAddr['phone'] ?? '') ?>"
            data-address-street="<?= e($savedAddr['street'] ?? '') ?>"
            data-address-number="<?= e($savedAddr['number'] ?? '') ?>"
            data-address-complement="<?= e($savedAddr['complement'] ?? '') ?>"
            data-address-reference="<?= e($savedAddr['reference'] ?? '') ?>"
            data-address-city="<?= e($savedAddr['city'] ?? '') ?>"
            data-address-neighborhood="<?= e($savedAddr['neighborhood'] ?? '') ?>"
          >
          <div class="address-content">
            <div class="address-header">
              <span class="address-name"><?= e($savedAddr['name'] ?? '') ?></span>
              <?php if ($isDefault): ?>
                <span class="badge-small">Padrão</span>
              <?php endif; ?>
              <?php if (!empty($savedAddr['label'])): ?>
                <span class="badge-small badge-label"><?= e($savedAddr['label']) ?></span>
              <?php endif; ?>
            </div>
            <div class="address-details">
              <?= e($savedAddr['street'] ?? '') ?>, <?= e($savedAddr['number'] ?? '') ?>
              <?php if (!empty($savedAddr['complement'])): ?>
                - <?= e($savedAddr['complement']) ?>
              <?php endif; ?>
              <br>
              <?= e($savedAddr['neighborhood'] ?? '') ?> - <?= e($savedAddr['city'] ?? '') ?>
            </div>
            <div class="address-phone"><?= e(format_phone_br($savedAddr['phone'] ?? '')) ?></div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>

      <button type="button" id="add-new-address-btn" class="new-address-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Adicionar novo endereço
      </button>

      <input type="hidden" name="use_saved_address" id="use-saved-address" value="1">
      
      <!-- Campos hidden para dados do endereço salvo (preenchidos via JS) -->
      <input type="hidden" name="address[name]" id="saved-address-name" value="">
      <input type="hidden" name="address[phone]" id="saved-address-phone" value="">
      <input type="hidden" name="address[street]" id="saved-address-street" value="">
      <input type="hidden" name="address[number]" id="saved-address-number" value="">
      <input type="hidden" name="address[complement]" id="saved-address-complement" value="">
      <input type="hidden" name="address[reference]" id="saved-address-reference" value="">
      <input type="hidden" name="address[city_id]" id="saved-address-city-id" value="">
      <input type="hidden" name="address[zone_id]" id="saved-address-zone-id" value="">
      <input type="hidden" name="address[city]" id="saved-address-city" value="">
      <input type="hidden" name="address[neighborhood]" id="saved-address-neighborhood" value="">
    </section>
    <?php endif; ?>

    <section class="card address-card<?= $hasAddresses ? ' manual-address-hidden' : '' ?>" id="manual-address-form">
      <div class="address-section-header">
        <h2>Endereço de entrega</h2>
        <?php if ($hasAddresses): ?>
          <button type="button" id="cancel-new-address-btn" class="cancel-new-address-btn">Cancelar</button>
        <?php else: ?>
          <span class="badge">Entrega padrão</span>
        <?php endif; ?>
      </div>
      <label class="field">
        <span>Nome do destinatário</span>
        <input type="text" name="address[name]" placeholder="Quem vai receber" value="<?= e($address['name'] ?? '') ?>" required>
      </label>
      <input type="hidden" id="checkout-phone" name="address[phone]" value="<?= e($address['phone'] ?? $customerData['whatsapp'] ?? '') ?>">
      <label class="field">
        <span>Selecione a cidade</span>
        <select id="checkout-city" name="address[city_id]" required>
          <option value="">Selecione a cidade</option>
          <?php foreach ($cities as $city): $cityId = (int)($city['id'] ?? 0); ?>
            <option value="<?= $cityId ?>"<?= $cityId === $selectedCityId ? ' selected' : '' ?>><?= e($city['name'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php $initialZones = ($selectedCityId && isset($zonesByCity[$selectedCityId])) ? $zonesByCity[$selectedCityId] : []; ?>
      <label class="field">
        <span>Bairro</span>
        <select id="checkout-zone" name="address[zone_id]" required<?= $selectedCityId ? '' : ' disabled' ?>>
          <option value=""><?= $selectedCityId ? 'Selecione o bairro' : 'Escolha a cidade primeiro' ?></option>
          <?php foreach ($initialZones as $zone): $zoneId = (int)($zone['id'] ?? 0); ?>
            <option value="<?= $zoneId ?>" data-fee="<?= e(number_format((float)($zone['fee'] ?? 0), 2, '.', '')) ?>" data-city-name="<?= e($zone['city_name'] ?? '') ?>" data-zone-name="<?= e($zone['name'] ?? '') ?>"<?= $zoneId === $selectedZoneId ? ' selected' : '' ?>>
              <?= e($zone['name'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field field-relative">
        <span>Rua / Avenida</span>
        <input type="text" id="checkout-street" name="address[street]" placeholder="Digite para buscar a rua..." value="<?= e($address['street'] ?? '') ?>" required autocomplete="off">
        <div id="street-autocomplete-list" class="street-autocomplete-list"></div>
      </label>
      <label class="field">
        <span>Número</span>
        <input type="text" name="address[number]" placeholder="123" value="<?= e($address['number'] ?? '') ?>" required inputmode="numeric" pattern="[0-9]*">
      </label>
      <label class="field">
        <span>Complemento</span>
        <input type="text" name="address[complement]" placeholder="Apto, bloco, casa" value="<?= e($address['complement'] ?? '') ?>">
      </label>
      <label class="field">
        <span>Ponto de referência</span>
        <textarea name="address[reference]" placeholder="Ajude o entregador a encontrar mais rápido"><?= e($address['reference'] ?? '') ?></textarea>
      </label>

      <input type="hidden" name="address[city]" id="address-city-name" value="<?= e($addressCityName) ?>">
      <input type="hidden" name="address[neighborhood]" id="address-zone-name" value="<?= e($addressNeighborhoodName) ?>">
      <input type="hidden" name="order[delivery_fee]" id="delivery-fee-input" value="<?= e(number_format($deliveryFee, 2, '.', '')) ?>">
    </section>

    <!-- ======= RESUMO DO PEDIDO - SUBSTITUÍDO (LAYOUT TIPO PRINT) ======= -->
    <section class="card summary-card" id="checkout-summary" data-subtotal="<?= e(number_format($subtotal, 2, '.', '')) ?>" data-loyalty-discount="<?= e(number_format($loyaltyDiscount, 2, '.', '')) ?>">
      <div class="card-title">Resumo do pedido</div>

      <div class="summary-items" aria-live="polite">
        <?php foreach ($items as $item): 
          // Calcular preço base (unit_price - component_swap_extra - customization extras)
          $componentSwapExtra = (float)($item['combo']['component_swap_extra'] ?? 0);
          $customizationDelta = (float)($item['customization']['total_delta'] ?? 0);
          $displayPrice = ((float)($item['unit_price'] ?? 0) - $componentSwapExtra - $customizationDelta) * (int)($item['qty'] ?? 1);
        ?>
          <div class="summary-item-wrapper">
            <div class="summary-item">
              <div class="product"><?= e($item['qty'] ?? 1) ?>x <?= e($item['product']['name'] ?? 'Produto') ?></div>
              <div class="price"><?= price_br($displayPrice) ?></div>
            </div>
            
            <?php
            // Mostrar itens do combo (calcular delta baseado no item padrão do grupo)
            $comboItems = [];
            $comboPriceMode = $item['combo']['price_mode'] ?? $item['product']['price_mode'] ?? 'fixed';
            
            if (!empty($item['combo']['selected_items']) && is_array($item['combo']['selected_items'])) {
              // Primeiro, mapear itens padrão por grupo (dos dados completos dos grupos)
              $defaultPricesByGroup = [];
              $defaultDeltasByGroup = [];
              if (!empty($item['combo']['groups']) && is_array($item['combo']['groups'])) {
                foreach ($item['combo']['groups'] as $group) {
                  $groupId = $group['id'] ?? 0;
                  if (!empty($group['all_items']) && is_array($group['all_items'])) {
                    foreach ($group['all_items'] as $allItem) {
                      if (!empty($allItem['is_default']) || !empty($allItem['default'])) {
                        $defaultPricesByGroup[$groupId] = (float)($allItem['price_override'] ?? $allItem['base_price'] ?? 0);
                        $defaultDeltasByGroup[$groupId] = (float)($allItem['delta'] ?? 0);
                        break;
                      }
                    }
                  }
                }
              }
              
              // Mapear cada item selecionado ao seu grupo
              $groupIdBySimpleId = [];
              // Mapear personalizações de cada selected_item por simple_id
              $customizationsBySimpleId = [];
              if (!empty($item['combo']['groups']) && is_array($item['combo']['groups'])) {
                foreach ($item['combo']['groups'] as $group) {
                  $groupId = $group['id'] ?? 0;
                  if (!empty($group['all_items']) && is_array($group['all_items'])) {
                    foreach ($group['all_items'] as $allItem) {
                      $simpleId = $allItem['simple_id'] ?? 0;
                      if ($simpleId > 0) {
                        $groupIdBySimpleId[$simpleId] = $groupId;
                        // Armazenar personalizações se existirem
                        if (!empty($allItem['customization']['groups']) && is_array($allItem['customization']['groups'])) {
                          $customizationsBySimpleId[$simpleId] = $allItem['customization']['groups'];
                        }
                      }
                    }
                  }
                  
                  // Também verificar nos items selecionados do grupo
                  if (!empty($group['items']) && is_array($group['items'])) {
                    foreach ($group['items'] as $selectedItem) {
                      $simpleId = $selectedItem['simple_id'] ?? 0;
                      if ($simpleId > 0 && !empty($selectedItem['customization']['groups']) && is_array($selectedItem['customization']['groups'])) {
                        $customizationsBySimpleId[$simpleId] = $selectedItem['customization']['groups'];
                      }
                    }
                  }
                }
              }
              
              foreach ($item['combo']['selected_items'] as $comboItem) {
                $comboName = $comboItem['simple_name'] ?? $comboItem['name'] ?? '';
                $delta = (float)($comboItem['delta'] ?? 0);
                $extraPrice = (float)($comboItem['extra_price'] ?? 0);
                $isDefault = $comboItem['is_default'] ?? $comboItem['default'] ?? true;
                $basePrice = (float)($comboItem['base_price'] ?? 0);
                $priceOverride = $comboItem['price_override'] ?? null;
                $simpleId = $comboItem['simple_id'] ?? 0;
                
                // Calcular o preço a mostrar baseado no modo de preço:
                $displayPrice = 0;
                $groupId = $groupIdBySimpleId[$simpleId] ?? 0;
                
                if ($extraPrice > 0) {
                  // 1. Se tem extra_price explícito, usar ele
                  $displayPrice = $extraPrice;
                } elseif (!$isDefault && $simpleId > 0) {
                  $defaultDelta = $defaultDeltasByGroup[$groupId] ?? 0;
                  $deltaDiff = $delta - $defaultDelta;
                  
                  // Verificar se delta está configurado (não-zero)
                  if (abs($deltaDiff) > 0.009) {
                    // Usar delta configurado
                    $displayPrice = $deltaDiff;
                  } else {
                    // Se delta é zero, calcular diferença de preços dos produtos
                    $itemPrice = $priceOverride !== null ? (float)$priceOverride : $basePrice;
                    $defaultPrice = $defaultPricesByGroup[$groupId] ?? 0;
                    if ($defaultPrice > 0 && $itemPrice > 0) {
                      $displayPrice = $itemPrice - $defaultPrice;
                    }
                  }
                }
                
                // Pegar a quantidade do item (default_qty)
                $comboItemQty = isset($comboItem['qty']) ? (int)$comboItem['qty'] : (isset($comboItem['default_qty']) ? (int)$comboItem['default_qty'] : 1);
                if ($comboItemQty <= 0) $comboItemQty = 1;
                
                // Verificar se há unit_customizations para mostrar cada unidade separadamente
                $hasUnitCustomizations = $simpleId > 0 && 
                  !empty($item['component_customizations'][$simpleId]['unit_customizations']) &&
                  is_array($item['component_customizations'][$simpleId]['unit_customizations']);
                
                if ($hasUnitCustomizations && $comboItemQty > 1) {
                  // Mostrar cada unidade separadamente com suas customizações
                  foreach ($item['component_customizations'][$simpleId]['unit_customizations'] as $unitNum => $unitCust) {
                    // Nome com indicador de unidade: "Woll Smash (1º)"
                    $unitDisplayName = $comboName . ' (' . $unitNum . 'º)';
                    
                    // Adicionar o item do combo com indicador de unidade
                    $comboItems[] = ['name' => $unitDisplayName, 'price' => $displayPrice, 'qty' => 1];
                    
                    // Processar customizações desta unidade específica
                    if (!empty($unitCust['groups']) && is_array($unitCust['groups'])) {
                      foreach ($unitCust['groups'] as $custGroup) {
                        $custGroupType = $custGroup['type'] ?? 'extra';
                        $isCustChoiceGroup = in_array($custGroupType, ['single', 'addon', 'choice']);
                        
                        if (!empty($custGroup['items']) && is_array($custGroup['items'])) {
                          foreach ($custGroup['items'] as $custItem) {
                            $custName = $custItem['name'] ?? '';
                            $custQty = isset($custItem['qty']) ? (int)$custItem['qty'] : null;
                            $custDeltaQty = isset($custItem['delta_qty']) ? (int)$custItem['delta_qty'] : null;
                            $custPrice = (float)($custItem['price'] ?? 0);
                            $custDefaultQty = isset($custItem['default_qty']) ? (int)$custItem['default_qty'] : null;
                            $custUnitPrice = isset($custItem['unit_price']) ? (float)$custItem['unit_price'] : 0;
                            $custIsSelected = !empty($custItem['selected']) || ($custQty !== null && $custQty > 0);
                            $custIsRemoved = !empty($custItem['removed']) || ($custDefaultQty !== null && $custDefaultQty > 0 && ($custQty === 0 || $custQty === null));
                            
                            if ($custIsRemoved && $custName) {
                              $comboItems[] = ['name' => 'Sem ' . $custName, 'price' => 0, 'qty' => 1];
                              continue;
                            }
                            
                            if ($isCustChoiceGroup && $custIsSelected && $custQty > 0 && $custName) {
                              $comboItems[] = ['name' => $custName, 'price' => $custPrice, 'qty' => 1];
                              continue;
                            }
                            
                            if ($custDeltaQty === null && $custDefaultQty !== null && $custQty !== null && $custQty >= 0) {
                              $custDeltaQty = $custQty - $custDefaultQty;
                            }
                            
                            if ($custDeltaQty !== null && $custDeltaQty < 0 && $custName) {
                              $comboItems[] = ['name' => 'Sem ' . $custName, 'price' => 0, 'qty' => 1];
                              continue;
                            }
                            
                            if ($custPrice <= 0.009 && $custDeltaQty !== null && $custUnitPrice > 0) {
                              $custPrice = $custUnitPrice * abs($custDeltaQty);
                            }
                            
                            $shouldShow = false;
                            $itemDisplayName = '';
                            $effectiveQty = $custQty ?? 0;
                            
                            if ($custDeltaQty !== null && $custDeltaQty > 0) {
                              $shouldShow = true;
                              $displayQty = abs($custDeltaQty);
                              $itemDisplayName = '+' . ($displayQty > 1 ? "{$displayQty}x " : "") . $custName;
                            } else if ($custPrice > 0.009 && $effectiveQty > 0) {
                              $shouldShow = true;
                              $itemDisplayName = ($effectiveQty > 1 ? "{$effectiveQty}x " : "") . $custName;
                            }
                            
                            if ($shouldShow && $custName && $itemDisplayName) {
                              $comboItems[] = ['name' => $itemDisplayName, 'price' => $custPrice, 'qty' => 1];
                            }
                          }
                        }
                      }
                    }
                  }
                } else {
                  // Comportamento original: quantidade única ou sem unit_customizations
                  // Adicionar TODOS os itens do combo (inclusos e com preço extra)
                  if ($comboName) {
                    $comboItems[] = ['name' => $comboName, 'price' => $displayPrice, 'qty' => $comboItemQty];
                  }
                
                // Adicionar personalizações dos produtos simples dentro do combo
                // Tentar múltiplas fontes de dados para as personalizações
                $custGroups = null;
                
                // 1. Buscar em component_customizations (principal fonte)
                if ($simpleId > 0 && !empty($item['component_customizations'][$simpleId]['customization']['groups'])) {
                  $custGroups = $item['component_customizations'][$simpleId]['customization']['groups'];
                }
                
                // 2. Tenta pegar do comboItem direto (selected_items)
                if (empty($custGroups) && !empty($comboItem['customization']['groups']) && is_array($comboItem['customization']['groups'])) {
                  $custGroups = $comboItem['customization']['groups'];
                }
                
                // 3. Se não encontrar, busca no mapeamento por simple_id (all_items ou items dos grupos)
                if (empty($custGroups) && $simpleId > 0 && isset($customizationsBySimpleId[$simpleId])) {
                  $custGroups = $customizationsBySimpleId[$simpleId];
                }
                
                // 4. Se ainda não encontrou, busca diretamente nos groups->items
                if (empty($custGroups) && $simpleId > 0 && !empty($item['combo']['groups'])) {
                  foreach ($item['combo']['groups'] as $grp) {
                    if (!empty($grp['items']) && is_array($grp['items'])) {
                      foreach ($grp['items'] as $grpItem) {
                        if (($grpItem['simple_id'] ?? 0) == $simpleId) {
                          if (!empty($grpItem['customization']['groups']) && is_array($grpItem['customization']['groups'])) {
                            $custGroups = $grpItem['customization']['groups'];
                            break 2;
                          }
                        }
                      }
                    }
                  }
                }
                
                if (!empty($custGroups) && is_array($custGroups)) {
                  foreach ($custGroups as $custGroup) {
                    // Verificar se é um grupo de seleção/escolha (single, addon, choice)
                    $custGroupType = $custGroup['type'] ?? 'extra';
                    $isCustChoiceGroup = in_array($custGroupType, ['single', 'addon', 'choice']);
                    $custGroupName = $custGroup['name'] ?? '';
                    
                    if (!empty($custGroup['items']) && is_array($custGroup['items'])) {
                      foreach ($custGroup['items'] as $custItem) {
                        $custName = $custItem['name'] ?? '';
                        // Manter qty como null se não existir para detectar remoções corretamente
                        $custQty = isset($custItem['qty']) ? (int)$custItem['qty'] : null;
                        $custDeltaQty = isset($custItem['delta_qty']) ? (int)$custItem['delta_qty'] : null;
                        $custPrice = (float)($custItem['price'] ?? 0);
                        $custDefaultQty = isset($custItem['default_qty']) ? (int)$custItem['default_qty'] : null;
                        $custUnitPrice = isset($custItem['unit_price']) ? (float)$custItem['unit_price'] : 0;
                        $custIsSelected = !empty($custItem['selected']) || ($custQty !== null && $custQty > 0);
                        // Remoção: item marcado como removido OU tem default_qty > 0 e qty é 0 ou null
                        $custIsRemoved = !empty($custItem['removed']) || ($custDefaultQty !== null && $custDefaultQty > 0 && ($custQty === 0 || $custQty === null));
                        
                        // Verificar se é remoção (item removido)
                        if ($custIsRemoved && $custName) {
                          $comboItems[] = [
                            'name' => 'Sem ' . $custName,
                            'price' => 0,
                            'qty' => 1
                          ];
                          continue;
                        }
                        
                        // Para grupos de escolha: mostrar o item selecionado (qty > 0)
                        if ($isCustChoiceGroup && $custIsSelected && $custQty > 0 && $custName) {
                          $comboItems[] = [
                            'name' => $custName,
                            'price' => $custPrice,
                            'qty' => 1
                          ];
                          continue; // Não processar novamente abaixo
                        }
                        
                        // Calcular delta se não existir
                        if ($custDeltaQty === null && $custDefaultQty !== null && $custQty !== null && $custQty >= 0) {
                          $custDeltaQty = $custQty - $custDefaultQty;
                        }
                        
                        // Verificar se é remoção por delta negativo
                        if ($custDeltaQty !== null && $custDeltaQty < 0 && $custName) {
                          $comboItems[] = [
                            'name' => 'Sem ' . $custName,
                            'price' => 0,
                            'qty' => 1
                          ];
                          continue;
                        }
                        
                        // Calcular preço se não existir (usando unit_price * delta_qty)
                        if ($custPrice <= 0.009 && $custDeltaQty !== null && $custUnitPrice > 0) {
                          $custPrice = $custUnitPrice * abs($custDeltaQty);
                        }
                        
                        // Mostrar se: tem delta diferente de zero OU tem preço > 0
                        $shouldShow = false;
                        $itemDisplayName = '';
                        
                        // Usar qty efetivo: se null, assume 0 para verificação
                        $effectiveQty = $custQty ?? 0;
                        
                        if ($custDeltaQty !== null && $custDeltaQty > 0) {
                          // Tem adição
                          $shouldShow = true;
                          $displayQty = abs($custDeltaQty);
                          $prefix = '+';
                          $itemDisplayName = $prefix . ($displayQty > 1 ? "{$displayQty}x " : "") . $custName;
                        } else if ($custPrice > 0.009 && $effectiveQty > 0) {
                          // Tem preço mas sem delta (item opcional extra)
                          $shouldShow = true;
                          $itemDisplayName = ($effectiveQty > 1 ? "{$effectiveQty}x " : "") . $custName;
                        }
                        
                        if ($shouldShow && $custName && $itemDisplayName) {
                          $comboItems[] = [
                            'name' => $itemDisplayName,
                            'price' => $custPrice,
                            'qty' => 1
                          ];
                        }
                      }
                    }
                  }
                }
                } // Fecha o bloco else do hasUnitCustomizations
              }
            }
            
            // Mostrar personalizações (extras pagos, modificações E seleções de grupos de escolha)
            $customItems = [];
            if (!empty($item['customization']['groups']) && is_array($item['customization']['groups'])) {
              foreach ($item['customization']['groups'] as $group) {
                // Verificar se é um grupo de seleção/escolha (single, addon, choice)
                $groupType = $group['type'] ?? 'extra';
                $isChoiceGroup = in_array($groupType, ['single', 'addon', 'choice']);
                $groupName = $group['name'] ?? '';
                
                if (!empty($group['items']) && is_array($group['items'])) {
                  foreach ($group['items'] as $customItem) {
                    $itemName = $customItem['name'] ?? '';
                    // Manter qty como null se não existir para detectar remoções corretamente
                    $itemQty = isset($customItem['qty']) ? (int)$customItem['qty'] : null;
                    $deltaQty = isset($customItem['delta_qty']) ? (int)$customItem['delta_qty'] : null;
                    $itemPrice = (float)($customItem['price'] ?? 0);
                    $status = $customItem['status'] ?? '';
                    $isSelected = !empty($customItem['selected']) || ($itemQty !== null && $itemQty > 0);
                    $defaultQty = isset($customItem['default_qty']) ? (int)$customItem['default_qty'] : null;
                    // Remoção: item marcado como removido OU tem default_qty > 0 e qty é 0 ou null
                    $isRemoved = !empty($customItem['removed']) || ($defaultQty !== null && $defaultQty > 0 && ($itemQty === 0 || $itemQty === null));
                    
                    // Verificar se é remoção
                    if ($isRemoved && $itemName) {
                      $customItems[] = [
                        'name' => 'Sem ' . $itemName,
                        'price' => 0,
                        'qty' => 1
                      ];
                      continue;
                    }
                    
                    // Usar qty efetivo: se null, assume 0
                    $effectiveQty = $itemQty ?? 0;
                    
                    // Para grupos de escolha: mostrar o item selecionado (qty > 0)
                    if ($isChoiceGroup && $isSelected && $effectiveQty > 0 && $itemName) {
                      // Mostrar apenas o nome do item selecionado
                      $customItems[] = [
                        'name' => $itemName,
                        'price' => $itemPrice,
                        'qty' => 1
                      ];
                      continue; // Não processar novamente abaixo
                    }
                    
                    // Para grupos pool (montagem/açaí): mostrar itens selecionados
                    if ($groupType === 'pool' && $effectiveQty > 0 && $itemName) {
                      $paidQty = isset($customItem['paid_qty']) ? (int)$customItem['paid_qty'] : 0;
                      $qtyPrefix = $effectiveQty > 1 ? "{$effectiveQty}x " : "";
                      $customItems[] = [
                        'name' => $qtyPrefix . $itemName,
                        'price' => $itemPrice,
                        'qty' => 1
                      ];
                      continue;
                    }
                    
                    // --- Abaixo: lógica exclusiva para modo EXTRA/QTY ---
                    
                    // Calcular delta se não existir
                    if ($deltaQty === null && $defaultQty !== null && $itemQty !== null && $itemQty >= 0) {
                      $deltaQty = $itemQty - $defaultQty;
                    }
                    
                    // Verificar se é remoção por delta negativo
                    if ($deltaQty !== null && $deltaQty < 0 && $itemName) {
                      $customItems[] = [
                        'name' => 'Sem ' . $itemName,
                        'price' => 0,
                        'qty' => 1
                      ];
                      continue;
                    }
                    
                    // Mostrar apenas se não for "Incluso" (sem preço e sem modificação)
                    // No modo extra/qty, itens inclusos NÃO devem aparecer — só extras pagos e remoções
                    $isIncluso = ($status === 'Incluso' || ($itemPrice == 0 && ($deltaQty === null || $deltaQty == 0)));
                    
                    if ($itemName) {
                      if ($isIncluso) {
                        // Modo extra/qty: NÃO mostrar itens inclusos (pão, molho, etc.)
                        // Modo pool já tem tratamento próprio acima com continue
                        continue;
                      } elseif ($deltaQty !== null && $deltaQty > 0) {
                        // Se tem delta_qty positivo, mostrar com +
                        $displayQty = abs($deltaQty);
                        $customItems[] = [
                          'name' => '+' . ($displayQty > 1 ? "{$displayQty}x " : "") . $itemName,
                          'price' => $itemPrice,
                          'qty' => 1
                        ];
                      } elseif ($itemPrice > 0) {
                        // Mostra itens com preço
                        $customItems[] = [
                          'name' => ($effectiveQty > 1 ? "{$effectiveQty}x " : "") . $itemName,
                          'price' => $itemPrice,
                          'qty' => 1
                        ];
                      }
                    }
                  }
                }
              }
            }
            
            // Renderizar complementos
            if (!empty($comboItems) || !empty($customItems)):
            ?>
              <div class="summary-item-details">
                <?php foreach ($comboItems as $comboItem): ?>
                  <div class="summary-item-detail">
                    <?php 
                      // Montar nome com quantidade se maior que 1
                      $displayName = $comboItem['name'];
                      if ($comboItem['qty'] > 1) {
                        $displayName = $comboItem['qty'] . 'x ' . $displayName;
                      }
                      // NÃO adicionar + aqui - já está incluído no nome quando necessário
                    ?>
                    <span><?= e($displayName) ?></span>
                    <?php if ($comboItem['price'] > 0.009): ?>
                      <span>+ <?= price_br($comboItem['price']) ?></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
                
                <?php foreach ($customItems as $customItem): ?>
                  <div class="summary-item-detail">
                    <span><?= e($customItem['name']) ?></span>
                    <?php if ($customItem['price'] > 0): ?>
                      <span><?= price_br($customItem['price']) ?></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Linha de Taxa de entrega destacada - Novo estilo -->
      <div class="summary-delivery-row">
        <div class="delivery-main">
          <span class="label">Taxa de entrega</span>
          <span id="delivery-amount" class="value <?= $finalDeliveryFee <= 0 && $deliveryDiscountApplied > 0 ? 'delivery-free' : '' ?>"><?= e($deliveryLabel) ?></span>
        </div>
        <?php if ($deliveryDiscountApplied > 0): ?>
          <div class="delivery-discount-row" id="delivery-discount-info">
            <div>
              <span class="delivery-original"><?= price_br($deliveryFee) ?></span>
              <span class="delivery-discount">(– <?= price_br($deliveryDiscountApplied) ?>)</span>
            </div>
          </div>
          <div class="badge-saving" id="delivery-saving-badge">
            <span class="icon">🎉</span>
            <span>Você economizou <?= price_br($deliveryDiscountApplied) ?> na entrega</span>
          </div>
        <?php else: ?>
          <div class="delivery-discount-row is-hidden" id="delivery-discount-info">
            <div>
              <span class="delivery-original"></span>
              <span class="delivery-discount"></span>
            </div>
          </div>
          <div class="badge-saving is-hidden" id="delivery-saving-badge">
            <span class="icon">🎉</span>
            <span>Você economizou na entrega</span>
          </div>
        <?php endif; ?>
      </div>
      <div class="summary-total" role="status" aria-live="polite">
        <div class="row subtotal"><span>Subtotal</span><span id="subtotal-amount"><?= price_br($subtotal) ?></span></div>

        <?php if ($couponDiscount > 0): ?>
          <div class="row discount">
            <span>Cupom <?= e($couponCode) ?> (-<?= number_format($couponPercentage, 0) ?>%)</span>
            <span id="coupon-discount-amount">- <?= price_br($couponDiscount) ?></span>
          </div>
        <?php endif; ?>

        <?php if ($remainingLoyaltyDiscount > 0): ?>
          <div class="row discount" id="loyalty-discount-row"><span>Desconto Fidelidade</span><span id="loyalty-discount-amount">- <?= price_br($remainingLoyaltyDiscount) ?></span></div>
        <?php else: ?>
          <div class="row discount is-hidden" id="loyalty-discount-row"><span>Desconto Fidelidade</span><span id="loyalty-discount-amount"></span></div>
        <?php endif; ?>
        <div class="row grand"><span>Total</span><span id="total-amount"><?= price_br($total) ?></span></div>
      </div>

      <p class="note-muted">O valor de entrega será atualizado automaticamente após escolher o bairro.</p>
    </section>

    <section class="card" id="checkout-payment">
      <h2>Pagamento</h2>
      <?php if ($paymentMethods): ?>
        <div class="payment-methods">
          <?php 
          $pixMethods = [];
          $creditMethods = [];
          $debitMethods = [];
          $voucherMethods = [];
          $cashMethods = [];
          $otherMethods = [];
          
          foreach ($paymentMethods as $method) {
            $type = $method['type'] ?? 'others';
            if ($type === 'pix') {
              $pixMethods[] = $method;
            } elseif ($type === 'credit') {
              $creditMethods[] = $method;
            } elseif ($type === 'debit') {
              $debitMethods[] = $method;
            } elseif ($type === 'voucher') {
              $voucherMethods[] = $method;
            } elseif ($type === 'cash') {
              $cashMethods[] = $method;
            } else {
              $otherMethods[] = $method;
            }
          }
          ?>
          
          <?php if ($pixMethods): ?>
            <!-- PIX Payment Option -->
            <div class="payment-type-btn<?= ($selectedPayment && ($selectedPayment['type'] ?? '') === 'pix') ? ' active' : '' ?>" data-type="pix" data-payment-select="1">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/pix.svg') : '/assets/card-brands/pix.svg' ?>" alt="PIX" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">PIX</div>
                  <div class="payment-subtitle">Aprovação instantânea</div>
                </div>
              </div>
            </div>
            
            <!-- Payment Instructions Block -->
            <div id="payment-instructions" class="payment-note<?= $paymentInstructions ? '' : ' hidden' ?>">
              <?= $paymentInstructions ? nl2br(e($paymentInstructions)) : '' ?>
            </div>
          <?php endif; ?>
          
          <?php if ($cashMethods): ?>
            <!-- Cash Payment Option -->
            <div class="payment-type-btn<?= ($selectedPayment && ($selectedPayment['type'] ?? '') === 'cash') ? ' active' : '' ?>" data-type="cash" data-payment-select="1">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/cash.svg') : '/assets/card-brands/cash.svg' ?>" alt="Dinheiro" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">Dinheiro</div>
                  <div class="payment-subtitle">Pagamento na entrega</div>
                </div>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Cash Payment Information (appears after selecting cash) -->
          <div id="cash-payment-block" class="payment-note hidden">
            <div class="cash-payment-content">
              <h4 class="cash-title">Troco necessário?</h4>
              <div class="cash-form-layout">
                <label class="cash-field">
                  <span class="cash-help">Você precisa de troco para quanto? Se não precisar, deixe em branco</span>
                  <input type="number" id="cash-amount" name="cash_amount" placeholder="Ex: 50,00" step="0.01" min="0" 
                         class="cash-input">
                </label>
                <div id="change-info" class="change-info">
                  <div class="change-info-row">
                    <span>Total do pedido:</span>
                    <span id="order-total-display">R$ 0,00</span>
                  </div>
                  <div class="change-info-row-strong">
                    <span>Troco:</span>
                    <span id="change-amount" class="change-amount">R$ 0,00</span>
                  </div>
                </div>
                <div id="cash-error" class="cash-error"></div>
              </div>
            </div>
          </div>
          
          <?php if ($creditMethods): ?>
            <!-- Credit Card Payment Option -->
            <div class="payment-type-btn" data-type="credit" data-payment-select="1">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/credit.svg') : '/assets/card-brands/credit.svg' ?>" alt="Cartão de Crédito" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">Cartão de crédito</div>
                  <div class="payment-subtitle">
                    <?php 
                    $creditNames = array_map(function($method) { return (string)($method['name'] ?? 'Cartão'); }, $creditMethods);
                    echo e(implode(', ', array_slice($creditNames, 0, 3)) . (count($creditNames) > 3 ? ' e mais' : ''));
                    ?>
                  </div>
                </div>
              </div>
              <svg class="arrow" viewBox="0 0 24 24" fill="none">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            
            <!-- Credit Card Brands Selection -->
            <div class="card-brands" id="credit-brands">
              <?php foreach ($creditMethods as $creditMethod): ?>
                <?php
                $methodId = (int)($creditMethod['id'] ?? 0);
                $methodName = (string)($creditMethod['name'] ?? 'Cartão');
                $metaArr = [];
                if (!empty($creditMethod['meta'])) {
                  $metaArr = is_string($creditMethod['meta']) ? json_decode($creditMethod['meta'], true) : (is_array($creditMethod['meta']) ? $creditMethod['meta'] : []);
                }
                // prefer server-provided absolute URL when disponível
                $iconUrl = $creditMethod['icon_url'] ?? ($metaArr['icon'] ?? '');
                
                // Se não tiver ícone personalizado, tentar mapear baseado no nome
                if (empty($iconUrl)) {
                  $nameLower = strtolower((string)$methodName);
                  $detectedBrand = 'credit.svg';
                  foreach ($cardBrandMapping as $keyword => $brandFile) {
                    if (strpos($nameLower, $keyword) !== false) {
                      $detectedBrand = $brandFile;
                      break;
                    }
                  }
                  $iconUrl = 'assets/card-brands/' . $detectedBrand;
                }
                
                // Converter path relativo para URL completa se necessário (PHP 7 compatível)
                if ($iconUrl && !preg_match('/^https?:\/\//i', $iconUrl)) {
                  if (strpos($iconUrl, '/') === 0) {
                    $iconUrl = (function_exists('base_url') ? rtrim(base_url(), '/') . $iconUrl : $iconUrl);
                  } else {
                    $iconUrl = (function_exists('base_url') ? base_url($iconUrl) : '/' . ltrim($iconUrl, '/'));
                  }
                }
                ?>
                <div class="brand-btn" data-brand="<?= e(strtolower(str_replace(' ', '', (string)$methodName))) ?>" data-method-id="<?= $methodId ?>" data-payment-type="credit" data-brand-select="1">
                  <img src="<?= e($iconUrl) ?>" alt="<?= e($methodName) ?>" data-fallback-src="<?= function_exists('base_url') ? base_url('assets/card-brands/credit.svg') : '/assets/card-brands/credit.svg' ?>">
                  <span><?= e($methodName) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          
          <?php if ($debitMethods): ?>
            <!-- Debit Card Payment Option -->
            <div class="payment-type-btn" data-type="debit" data-payment-select="1">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/debit.svg') : '/assets/card-brands/debit.svg' ?>" alt="Cartão de Débito" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">Cartão de débito</div>
                  <div class="payment-subtitle">
                    <?php 
                    $debitNames = array_map(function($method) { return (string)($method['name'] ?? 'Débito'); }, $debitMethods);
                    echo e(implode(', ', array_slice($debitNames, 0, 3)) . (count($debitNames) > 3 ? ' e mais' : ''));
                    ?>
                  </div>
                </div>
              </div>
              <svg class="arrow" viewBox="0 0 24 24" fill="none">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            
            <!-- Debit Card Brands Selection -->
            <div class="card-brands" id="debit-brands">
              <?php foreach ($debitMethods as $debitMethod): ?>
                <?php
                $methodId = (int)($debitMethod['id'] ?? 0);
                $methodName = (string)($debitMethod['name'] ?? 'Débito');
                $metaArr = [];
                if (!empty($debitMethod['meta'])) {
                  $metaArr = is_string($debitMethod['meta']) ? json_decode($debitMethod['meta'], true) : (is_array($debitMethod['meta']) ? $debitMethod['meta'] : []);
                }
                $iconUrl = $debitMethod['icon_url'] ?? ($metaArr['icon'] ?? '');
                
                // Se não tiver ícone personalizado, tentar mapear baseado no nome
                if (empty($iconUrl)) {
                  $nameLower = strtolower((string)$methodName);
                  $detectedBrand = 'debit.svg';
                  foreach ($cardBrandMapping as $keyword => $brandFile) {
                    if (strpos($nameLower, $keyword) !== false) {
                      $detectedBrand = $brandFile;
                      break;
                    }
                  }
                  $iconUrl = 'assets/card-brands/' . $detectedBrand;
                }
                
                if ($iconUrl && !preg_match('/^https?:\/\//i', $iconUrl)) {
                  if (strpos($iconUrl, '/') === 0) {
                    $iconUrl = (function_exists('base_url') ? rtrim(base_url(), '/') . $iconUrl : $iconUrl);
                  } else {
                    $iconUrl = (function_exists('base_url') ? base_url($iconUrl) : '/' . ltrim($iconUrl, '/'));
                  }
                }
                ?>
                <div class="brand-btn" data-brand="<?= e(strtolower(str_replace(' ', '', (string)$methodName))) ?>" data-method-id="<?= $methodId ?>" data-payment-type="debit" data-brand-select="1">
                  <img src="<?= e($iconUrl) ?>" alt="<?= e($methodName) ?>" data-fallback-src="<?= function_exists('base_url') ? base_url('assets/card-brands/debit.svg') : '/assets/card-brands/debit.svg' ?>">
                  <span><?= e($methodName) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          
          <?php if ($voucherMethods): ?>
            <!-- Voucher Payment Option -->
            <div class="payment-type-btn" data-type="voucher" data-payment-select="1">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/voucher.svg') : '/assets/card-brands/voucher.svg' ?>" alt="Vale-refeição" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">Vale-refeição</div>
                  <div class="payment-subtitle">
                    <?php 
                    $voucherNames = array_map(function($method) { return (string)($method['name'] ?? 'Vale'); }, $voucherMethods);
                    echo e(implode(', ', array_slice($voucherNames, 0, 3)) . (count($voucherNames) > 3 ? ' e mais' : ''));
                    ?>
                  </div>
                </div>
              </div>
              <svg class="arrow" viewBox="0 0 24 24" fill="none">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            
            <!-- Voucher Brands Selection -->
            <div class="card-brands" id="voucher-brands">
              <?php foreach ($voucherMethods as $voucherMethod): ?>
                <?php
                $methodId = (int)($voucherMethod['id'] ?? 0);
                $methodName = (string)($voucherMethod['name'] ?? 'Vale');
                $metaArr = [];
                if (!empty($voucherMethod['meta'])) {
                  $metaArr = is_string($voucherMethod['meta']) ? json_decode($voucherMethod['meta'], true) : (is_array($voucherMethod['meta']) ? $voucherMethod['meta'] : []);
                }
                $iconUrl = $voucherMethod['icon_url'] ?? ($metaArr['icon'] ?? '');
                
                // Se não tiver ícone personalizado, usar ícone genérico
                if (empty($iconUrl)) {
                  $iconUrl = 'assets/card-brands/voucher.svg';
                }
                if ($iconUrl && !preg_match('/^https?:\/\//i', $iconUrl)) {
                  if (strpos($iconUrl, '/') === 0) {
                    $iconUrl = (function_exists('base_url') ? rtrim(base_url(), '/') . $iconUrl : $iconUrl);
                  } else {
                    $iconUrl = (function_exists('base_url') ? base_url($iconUrl) : '/' . ltrim($iconUrl, '/'));
                  }
                }
                ?>
                <div class="brand-btn" data-brand="<?= e(strtolower(str_replace(' ', '', (string)$methodName))) ?>" data-method-id="<?= $methodId ?>" data-payment-type="voucher" data-brand-select="1">
                  <img src="<?= e($iconUrl) ?>" alt="<?= e($methodName) ?>" data-fallback-src="<?= function_exists('base_url') ? base_url('assets/card-brands/voucher.svg') : '/assets/card-brands/voucher.svg' ?>">
                  <span><?= e($methodName) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          
          <?php if ($otherMethods): ?>
            <!-- Other Payment Option -->
            <div class="payment-type-btn" data-type="others" data-payment-select="1">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/others.svg') : '/assets/card-brands/others.svg' ?>" alt="Outros" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">Outros</div>
                  <div class="payment-subtitle">
                    <?php 
                    $otherNames = array_map(function($method) { return (string)($method['name'] ?? 'Outros'); }, $otherMethods);
                    echo e(implode(', ', array_slice($otherNames, 0, 3)) . (count($otherNames) > 3 ? ' e mais' : ''));
                    ?>
                  </div>
                </div>
              </div>
              <svg class="arrow" viewBox="0 0 24 24" fill="none">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            
            <!-- Other Methods Selection -->
            <div class="card-brands" id="others-brands">
              <?php foreach ($otherMethods as $otherMethod): ?>
                <?php
                $methodId = (int)($otherMethod['id'] ?? 0);
                $methodName = (string)($otherMethod['name'] ?? 'Outros');
                $metaArr = [];
                if (!empty($otherMethod['meta'])) {
                  $metaArr = is_string($otherMethod['meta']) ? json_decode($otherMethod['meta'], true) : (is_array($otherMethod['meta']) ? $otherMethod['meta'] : []);
                }
                $iconUrl = $otherMethod['icon_url'] ?? ($metaArr['icon'] ?? '');
                
                // Se não tiver ícone personalizado, usar ícone genérico
                if (empty($iconUrl)) {
                  $iconUrl = 'assets/card-brands/others.svg';
                }
                if ($iconUrl && !preg_match('/^https?:\/\//i', $iconUrl)) {
                  if (strpos($iconUrl, '/') === 0) {
                    $iconUrl = (function_exists('base_url') ? rtrim(base_url(), '/') . $iconUrl : $iconUrl);
                  } else {
                    $iconUrl = (function_exists('base_url') ? base_url($iconUrl) : '/' . ltrim($iconUrl, '/'));
                  }
                }
                ?>
                <div class="brand-btn" data-brand="<?= e(strtolower(str_replace(' ', '', (string)$methodName))) ?>" data-method-id="<?= $methodId ?>" data-payment-type="others" data-brand-select="1">
                  <img src="<?= e($iconUrl) ?>" alt="<?= e($methodName) ?>" data-fallback-src="<?= function_exists('base_url') ? base_url('assets/card-brands/others.svg') : '/assets/card-brands/others.svg' ?>">
                  <span><?= e($methodName) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Hidden inputs for payment method data -->
        <input type="hidden" name="payment[method_id]" id="payment-method-id" value="<?= $selectedPaymentId ?>">
        <input type="hidden" name="payment[type]" id="payment-type" value="">
        <input type="hidden" name="payment[brand]" id="payment-brand" value="">
      <?php else: ?>
        <div class="payment-note">Nenhum método de pagamento cadastrado. Entre em contato com a loja para mais informações.</div>
        <input type="hidden" name="payment[method_id]" id="payment-method-id" value="0">
        <input type="hidden" name="payment[type]" id="payment-type" value="">
        <input type="hidden" name="payment[brand]" id="payment-brand" value="">
        <div id="payment-instructions" class="payment-note hidden"></div>
      <?php endif; ?>
    </section>

  </form>
</div>

<div class="checkout-footer">
  <button class="cta" type="submit" form="checkout-form">Confirmar pedido</button>
</div>


<!-- Modal de Confirmação de Endereço -->
<div id="address-confirmation-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="address-modal-title">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-icon">📍</div>
      <h3 class="modal-title" id="address-modal-title">Confirmar endereço de entrega</h3>
    </div>
    <div class="modal-body">
      <p class="modal-description">Confira se o endereço selecionado está correto para a entrega do seu pedido:</p>
      <div class="modal-address-card" id="modal-address-display">
        <!-- Será preenchido dinamicamente -->
      </div>
    </div>
    <div class="modal-actions">
      <button type="button" class="modal-btn modal-btn-primary" id="confirm-address-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Sim, está correto
      </button>
      <button type="button" class="modal-btn modal-btn-secondary" id="change-address-btn">
        Alterar endereço
      </button>
    </div>
  </div>
</div>


<script id="checkout-page-config" type="application/json"><?= json_encode($checkoutConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= e(base_url('assets/checkout.js')) ?>?v=<?= e($checkoutJsVersion) ?>"></script>
</body>
</html>
