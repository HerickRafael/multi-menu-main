<?php
/**
 * Criação de Pedido Manual Mobile
 * Design consistente com outras views mobile
 * 
 * @var array $company
 * @var array $u
 * @var array $categories
 * @var array $products
 * @var string $pageTitle
 * @var string $activeNav
 * @var bool $showBackButton
 */

// Extrai cores da configuração do sistema
$getData = function($key, $default) use ($company) {
    if (is_array($company)) {
        return $company[$key] ?? $default;
    }
    return $company->$key ?? $default;
};

$headerBgColor = $getData('menu_header_bg_color', $company['theme_color'] ?? '#4361ee');

// Agrupa produtos por categoria
$productsByCategory = [];
foreach ($products as $product) {
    $catId = $product['category_id'] ?? 0;
    if (!isset($productsByCategory[$catId])) {
        $productsByCategory[$catId] = [];
    }
    $productsByCategory[$catId][] = $product;
}

// Mapa de categorias
$categoriesMap = [];
foreach ($categories as $cat) {
    $categoriesMap[$cat['id']] = $cat['name'];
}

$isEdit = !empty($isEdit);
$order = $order ?? null;
$orderId = $orderId ?? ($order['id'] ?? null);
$prefill = $prefill ?? [];
$defaults = [
    'customer_phone' => '',
    'customer_name' => '',
    'notes' => '',
    'delivery_fee' => 0,
    'discount' => 0,
    'delivery_type' => 'delivery',
    'street' => '',
    'number' => '',
    'complement' => '',
    'reference' => '',
    'neighborhood' => '',
    'city_id' => null,
    'zone_id' => null,
    'payment_method_id' => null,
];
$values = array_merge($defaults, $prefill);

$deliveryType = $values['delivery_type'] ?? 'delivery';
$isPickup = $deliveryType === 'pickup';

$phoneValue = $values['customer_phone'] ?? '';
if ($phoneValue !== '' && function_exists('format_phone_br')) {
    $phoneValue = format_phone_br($phoneValue);
}

$paymentMethodTypeMap = [];
foreach (($paymentMethods ?? []) as $method) {
    $rawType = $method['type'] ?? 'others';
    $dataType = in_array($rawType, ['pix', 'cash', 'credit', 'debit', 'voucher'], true) ? $rawType : 'others';
    $paymentMethodTypeMap[(int)$method['id']] = $dataType;
}

// Flash messages
$flashError = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

ob_start();
?>

<style>
:root {
    --primary-color: <?= htmlspecialchars($headerBgColor) ?>;
}

/* Form Container */
.order-create-form {
    padding: 0;
}

/* Section Cards */
.form-section-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 16px;
    overflow: visible;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.form-section-card__header {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 16px 16px 0 0;
}

.form-section-card__header svg {
    width: 18px;
    height: 18px;
    color: var(--primary-color);
}

.form-section-card__title {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.form-section-card__body {
    padding: 16px;
    overflow: visible;
}

/* Form Groups */
.form-group {
    margin-bottom: 16px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
    font-size: 13px;
}

.form-input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font-size: 14px;
    background: white;
    color: #1f2937;
    font-family: inherit;
    transition: all 0.2s;
    -webkit-appearance: none;
    appearance: none;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(91, 33, 182, 0.1);
}

.form-input::placeholder {
    color: #9ca3af;
}

/* Error States */
.form-input.input-error {
    border-color: #dc2626;
    background: #fef2f2;
}

.form-input.input-error:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.2);
}

.payment-methods.input-error .payment-type-btn {
    border-color: #dc2626;
    background: #fef2f2;
}

.form-label .required {
    color: #dc2626;
    font-weight: 600;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* Product Selection */
.products-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    overflow: visible;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
    box-sizing: border-box;
    overflow: hidden;
}

.product-item.selected {
    background: rgba(91, 33, 182, 0.05);
    border-color: var(--primary-color);
}

.product-item:active {
    transform: scale(0.98);
}

.product-image {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 10px;
    object-fit: cover;
    background: #e2e8f0;
    flex-shrink: 0;
}

.product-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.product-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.product-price {
    font-size: 13px;
    font-weight: 700;
    color: var(--primary-color);
}

.product-qty-controls {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}

.qty-btn {
    width: 28px;
    height: 28px;
    min-width: 28px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.qty-btn:active {
    background: #f3f4f6;
}

.qty-btn.primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.qty-input {
    width: 32px;
    min-width: 32px;
    text-align: center;
    padding: 4px 2px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    flex-shrink: 0;
}

/* Cart Summary */
.cart-summary {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #e2e8f0;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-name {
    font-size: 14px;
    color: #374151;
}

.cart-item-qty {
    font-size: 13px;
    color: #6b7280;
}

.cart-item-price {
    font-weight: 600;
    color: #1e293b;
}

.cart-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    margin-top: 8px;
    border-top: 2px solid #e2e8f0;
}

.cart-total-label {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.cart-total-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-color);
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 24px;
    color: #9ca3af;
}

.empty-cart svg {
    width: 48px;
    height: 48px;
    margin-bottom: 8px;
}

/* Category Tabs */
.category-tabs {
    display: flex;
    overflow-x: auto;
    gap: 8px;
    padding-bottom: 12px;
    margin-bottom: 16px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.category-tabs::-webkit-scrollbar {
    display: none;
}

.category-tab {
    flex-shrink: 0;
    padding: 8px 16px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    font-size: 13px;
    color: #6b7280;
    cursor: pointer;
    white-space: nowrap;
}

.category-tab.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

/* Delivery Type Toggle */
.delivery-toggle {
    display: flex;
    gap: 8px;
}

.delivery-option {
    flex: 1;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.delivery-option.selected {
    border-color: var(--primary-color);
    background: rgba(91, 33, 182, 0.05);
}

.delivery-option input {
    display: none;
}

.delivery-option-icon {
    width: 24px;
    height: 24px;
    margin: 0 auto 4px;
    color: #6b7280;
}

.delivery-option.selected .delivery-option-icon {
    color: var(--primary-color);
}

.delivery-option-label {
    font-size: 13px;
    font-weight: 500;
    color: #374151;
}

/* Alert */
.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: 14px;
}

.alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
}

.alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #16a34a;
}

/* Action Buttons - PWA Optimized Footer */
.form-actions {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: white;
    border-top: 1px solid #e5e7eb;
    position: fixed;
    bottom: calc(64px + env(safe-area-inset-bottom));
    left: 0;
    right: 0;
    z-index: 101;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
}

@supports (-webkit-touch-callout: none) {
    .form-actions {
        bottom: calc(64px + constant(safe-area-inset-bottom));
        bottom: calc(64px + env(safe-area-inset-bottom));
    }
}

.btn-primary,
.btn-secondary {
    flex: 1;
    padding: 14px 16px;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:active {
    transform: scale(0.96);
    opacity: 0.9;
}

.btn-primary:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:active {
    background: #e5e7eb;
}

/* Footer Spacer */
.form-footer-spacer {
    height: calc(100px + 64px + env(safe-area-inset-bottom));
}

@supports (-webkit-touch-callout: none) {
    .form-footer-spacer {
        height: calc(100px + 64px + constant(safe-area-inset-bottom));
        height: calc(100px + 64px + env(safe-area-inset-bottom));
    }
}

/* Payment Methods - Checkout Style Design */
.payment-methods {
    display: grid;
    gap: 12px;
}

.payment-type-btn {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    font-size: 16px;
    font-weight: 600;
    background: #f9fafb;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-align: left;
    transition: all 0.2s ease;
}

.payment-type-btn:hover {
    background: #f1f5f9;
}

.payment-type-btn.active {
    border-color: #f59e0b;
    background: #fef3c7;
}

.payment-type-btn input[type="radio"] {
    display: none;
}

.payment-type-btn .payment-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.payment-type-btn .payment-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    object-fit: contain;
}

.payment-type-btn .payment-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.payment-type-btn .payment-title {
    font-size: 16px;
    font-weight: 600;
}

.payment-type-btn .payment-subtitle {
    font-size: 13px;
    color: #64748b;
    font-weight: 400;
}

.payment-type-btn .arrow {
    width: 20px;
    height: 20px;
    opacity: 0.5;
    transition: transform 0.2s ease;
}

.payment-type-btn.active .arrow {
    transform: rotate(90deg);
    opacity: 1;
}

.card-brands {
    margin-top: 12px;
    display: none;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 8px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.card-brands.show {
    display: grid;
}

.brand-btn {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 6px;
    transition: all 0.2s ease;
}

.brand-btn:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.brand-btn.active {
    border-color: #f59e0b;
    background: #fef3c7;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
}

.brand-btn img {
    width: 32px;
    height: 20px;
    object-fit: contain;
}

.brand-btn span {
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
}

/* Validation Toast */
.validation-toast {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 10000;
    background: #dc2626;
    color: white;
    padding: 16px 20px;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    animation: slideDown 0.3s ease;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.validation-toast svg {
    flex-shrink: 0;
    margin-top: 1px;
}

.validation-toast-content {
    flex: 1;
}

.validation-toast-title {
    font-weight: 600;
    margin-bottom: 4px;
}

.validation-toast-items {
    font-size: 13px;
    opacity: 0.95;
}

.validation-toast-close {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    opacity: 0.8;
}

@keyframes slideDown {
    from { transform: translateY(-100%); }
    to { transform: translateY(0); }
}

@keyframes slideUp {
    from { transform: translateY(0); }
    to { transform: translateY(-100%); }
}

/* Products grid error */
#productsGrid.input-error {
    border: 2px dashed #dc2626;
    border-radius: 12px;
    padding: 8px;
    background: #fef2f2;
}

/* ===== MODAL DE CUSTOMIZAÇÃO ===== */
.cmod-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,.45);
    align-items: flex-end;
    justify-content: center;
}
.cmod-overlay.open { display: flex; }
.cmod-sheet {
    background: #fff;
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-height: 90dvh;
    display: flex;
    flex-direction: column;
    font-family: 'Inter', system-ui, sans-serif;
    overflow: hidden;
}
.cmod-drag {
    width: 40px; height: 4px;
    background: #d1d5db;
    border-radius: 99px;
    margin: 12px auto 4px;
    flex-shrink: 0;
}
.cmod-header {
    padding: 8px 20px 14px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.cmod-title {
    font-size: 17px;
    font-weight: 700;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: calc(100% - 40px);
}
.cmod-close {
    background: #f3f4f6;
    border: none;
    width: 30px; height: 30px;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    flex-shrink: 0;
}
.cmod-body {
    overflow-y: auto;
    flex: 1;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 8px;
}
.cmod-footer {
    padding: 14px 16px;
    border-top: 1px solid #f3f4f6;
    display: flex;
    gap: 10px;
    flex-shrink: 0;
    background: #fff;
    padding-bottom: calc(14px + env(safe-area-inset-bottom));
}
.cmod-btn-cancel {
    flex: 1;
    padding: 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 999px;
    background: #fff;
    font-size: 15px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
}
.cmod-btn-confirm {
    flex: 2;
    padding: 14px;
    border: none;
    border-radius: 999px;
    background: #fbbf24;
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    cursor: pointer;
}
.cgroup {
    padding: 0;
    margin-bottom: 2px;
}
.cgroup-heading {
    padding: 14px 20px 6px;
    font-size: 15px;
    font-weight: 700;
    color: #111827;
}
.cpool-counter {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 20px 10px;
    font-size: 14px;
    color: #6b7280;
}
.cpool-counter-num {
    font-size: 28px;
    font-weight: 800;
    color: #374151;
    line-height: 1;
    transition: color .2s;
}
.cpool-counter.full .cpool-counter-num { color: #16a34a; }
.cpool-counter.extras .cpool-counter-num { color: #f59e0b; }
.cpool-extra-badge {
    font-size: 12px;
    color: #f59e0b;
    font-weight: 700;
}
.crow {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-top: 1px solid #f3f4f6;
}
.crow-thumb {
    width: 46px; height: 46px;
    min-width: 46px;
    border-radius: 50%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    background-size: cover;
    background-position: center;
}
.crow-info { flex: 1; min-width: 0; }
.crow-name {
    font-size: 14px;
    font-weight: 500;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.crow-price {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
}
.crow-pool-price {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
    min-height: 16px;
    white-space: nowrap;
}
.crow-pool-price.free { color: #16a34a; font-weight: 600; }
.crow-pool-price.charged { color: #f59e0b; font-weight: 600; }
.cstepper {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1.5px solid #e5e7eb;
    border-radius: 999px;
    padding: 5px 10px;
    min-width: 96px;
    justify-content: space-between;
    flex-shrink: 0;
}
.cst-btn {
    width: 28px; height: 28px;
    border-radius: 50%;
    border: none;
    background: #f3f4f6;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #374151;
    flex-shrink: 0;
    line-height: 1;
}
.cst-btn:disabled { opacity: .35; }
.cst-val {
    font-size: 16px;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
    color: #111827;
}
.crow-radio {
    width: 34px; height: 34px;
    border-radius: 50%;
    border: 2.5px solid #fbbf24;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    cursor: pointer;
}
.crow-radio.sel { background: #fbbf24; border-color: #fbbf24; }
.crow-radio svg { display: none; }
.crow-radio.sel svg { display: block; }
.radio-row { cursor: pointer; }

/* Street Autocomplete */
.street-ac-list {
  display: none;
  position: absolute;
  left: 0; right: 0;
  top: 100%;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  max-height: 200px;
  overflow-y: auto;
  z-index: 100;
}
.street-ac-list.active { display: block; }
.street-ac-item {
  padding: 10px 14px;
  font-size: 14px;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  color: #1e293b;
  transition: background 0.15s;
}
.street-ac-item:last-child { border-bottom: none; }
.street-ac-item:hover,
.street-ac-item.active { background: #f0f4ff; }
.street-ac-item small {
  display: block;
  color: #94a3b8;
  font-size: 12px;
  margin-top: 2px;
}
.street-ac-loading {
  padding: 12px 14px;
  font-size: 13px;
  color: #94a3b8;
  text-align: center;
}
</style>

<?php if ($flashError): ?>
    <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>

<!-- Modal de Personalização -->
<div class="cmod-overlay" id="custModal">
  <div class="cmod-sheet">
    <div class="cmod-drag"></div>
    <div class="cmod-header">
      <div class="cmod-title" id="custModalTitle">Personalizar</div>
      <button type="button" class="cmod-close" id="custModalClose">×</button>
    </div>
    <div class="cmod-body" id="custModalBody"></div>
    <div class="cmod-footer">
      <button type="button" class="cmod-btn-cancel" id="custModalCancel">Cancelar</button>
      <button type="button" class="cmod-btn-confirm" id="custModalConfirm">Adicionar — R$ 0,00</button>
    </div>
  </div>
</div>

<form method="POST" action="<?= $isEdit && $orderId ? '/orders/' . (int)$orderId : '/orders' ?>" id="orderForm" class="order-create-form">

    <!-- Cliente -->
    <div class="form-section-card">
        <div class="form-section-card__header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span class="form-section-card__title">Dados do Cliente</span>
        </div>
        <div class="form-section-card__body">
            <div class="form-group">
                <label class="form-label">WhatsApp / Telefone *</label>
                <input type="tel" name="customer_phone" id="customerPhone" class="form-input" required
                      placeholder="(00) 00000-0000" autocomplete="off"
                      value="<?= htmlspecialchars($phoneValue) ?>">
                <div id="customerSearchStatus" class="text-xs mt-1" style="display: none;"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Nome do Cliente *</label>
                <input type="text" name="customer_name" id="customerName" class="form-input" required
                      placeholder="Nome completo"
                      value="<?= htmlspecialchars((string)($values['customer_name'] ?? '')) ?>">
            </div>
        </div>
    </div>

    <!-- Tipo de Entrega -->
    <div class="form-section-card">
        <div class="form-section-card__header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="1" y="3" width="15" height="13"/>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                <circle cx="5.5" cy="18.5" r="2.5"/>
                <circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <span class="form-section-card__title">Tipo de Pedido</span>
        </div>
        <div class="form-section-card__body">
            <div class="delivery-toggle">
                <label class="delivery-option <?= $isPickup ? '' : 'selected' ?>" id="opt-delivery">
                    <input type="radio" name="delivery_type" value="delivery" <?= $isPickup ? '' : 'checked' ?>>
                    <svg class="delivery-option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="1" y="3" width="15" height="13"/>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                    <div class="delivery-option-label">Entrega</div>
                </label>
                <label class="delivery-option <?= $isPickup ? 'selected' : '' ?>" id="opt-pickup">
                    <input type="radio" name="delivery_type" value="pickup" <?= $isPickup ? 'checked' : '' ?>>
                    <svg class="delivery-option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <div class="delivery-option-label">Retirada</div>
                </label>
            </div>
            
            <div id="address-fields" style="margin-top: 16px;<?= $isPickup ? ' display: none;' : '' ?>">
                <?php if (!empty($cities) && !empty($zonesPresent)): ?>
                <!-- Cidade e Bairro Cadastrados -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Cidade *</label>
                        <select name="city_id" id="citySelect" class="form-input">
                            <option value="">Selecione a cidade</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= (int)$city['id'] ?>" <?= (int)($values['city_id'] ?? 0) === (int)$city['id'] ? 'selected' : '' ?>><?= htmlspecialchars($city['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bairro *</label>
                        <select name="zone_id" id="zoneSelect" class="form-input" disabled>
                            <option value="">Escolha a cidade primeiro</option>
                        </select>
                        <input type="hidden" name="neighborhood" id="neighborhoodInput" value="<?= htmlspecialchars((string)($values['neighborhood'] ?? '')) ?>">
                    </div>
                </div>
                <?php else: ?>
                <!-- Bairro Manual (sem zonas cadastradas) -->
                <div class="form-group">
                    <label class="form-label">Bairro *</label>
                    <input type="text" name="neighborhood" class="form-input" 
                              placeholder="Nome do bairro" id="neighborhoodInput"
                              value="<?= htmlspecialchars((string)($values['neighborhood'] ?? '')) ?>">
                </div>
                <?php endif; ?>
                
                <!-- Rua e Número -->
                <div class="form-row">
                    <div class="form-group" style="flex: 2; position: relative;">
                        <label class="form-label">Rua / Avenida *</label>
                        <input type="text" name="street" class="form-input" 
                               placeholder="Nome da rua" id="streetInput" autocomplete="off"
                               value="<?= htmlspecialchars((string)($values['street'] ?? '')) ?>">
                        <div id="street-ac-list" class="street-ac-list"></div>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Número *</label>
                        <input type="text" name="number" class="form-input" 
                               placeholder="123" id="numberInput"
                               value="<?= htmlspecialchars((string)($values['number'] ?? '')) ?>">
                    </div>
                </div>
                
                <!-- Complemento e Referência -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Complemento</label>
                        <input type="text" name="complement" class="form-input" 
                               placeholder="Apto, bloco, casa..." id="complementInput"
                               value="<?= htmlspecialchars((string)($values['complement'] ?? '')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Referência</label>
                        <input type="text" name="reference" class="form-input" 
                               placeholder="Próximo a..." id="referenceInput"
                               value="<?= htmlspecialchars((string)($values['reference'] ?? '')) ?>">
                    </div>
                </div>
                
                <!-- Taxa de Entrega -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Taxa de Entrega <a href="/guide/manual-order#address" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></label>
                        <?php if (!empty($cities) && !empty($zonesPresent)): ?>
                        <?php $deliveryFeeDisplay = (float)($values['delivery_fee'] ?? 0); ?>
                        <div class="form-input" id="deliveryFeeDisplay" style="background: #f3f4f6; color: #6b7280;">
                            <?= $deliveryFeeDisplay > 0.009 ? 'R$ ' . number_format($deliveryFeeDisplay, 2, ',', '.') : 'Selecione o bairro' ?>
                        </div>
                        <input type="hidden" name="delivery_fee" id="deliveryFeeInput" value="<?= number_format((float)($values['delivery_fee'] ?? 0), 2, '.', '') ?>">
                        <?php else: ?>
                        <input type="text" name="delivery_fee" class="form-input" 
                               placeholder="0,00" inputmode="decimal" id="deliveryFeeInput"
                               value="<?= number_format((float)($values['delivery_fee'] ?? 0), 2, ',', '.') ?>">
                        <?php endif; ?>
                    </div>
                    <div></div>
                </div>

                <!-- Desconto -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Desconto</label>
                        <input type="text" name="discount" class="form-input" 
                               placeholder="0,00" inputmode="decimal" id="discountInput"
                               value="<?= number_format((float)($values['discount'] ?? 0), 2, ',', '.') ?>">
                    </div>
                    <div></div>
                </div>
                
                <!-- Campo hidden para endereço formatado -->
                <input type="hidden" name="customer_address" id="customerAddressHidden">
            </div>
        </div>
    </div>

    <!-- Produtos -->
    <div class="form-section-card">
        <div class="form-section-card__header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <span class="form-section-card__title">Produtos</span>
        </div>
        <div class="form-section-card__body">
            <!-- Category Tabs -->
            <div class="category-tabs">
                <div class="category-tab active" data-category="all">Todos</div>
                <?php foreach ($categories as $cat): ?>
                    <div class="category-tab" data-category="<?= $cat['id'] ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Products Grid -->
            <div class="products-grid" id="productsGrid">
                <?php if (empty($products)): ?>
                    <div class="empty-cart">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                        </svg>
                        <p>Nenhum produto cadastrado</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $prod): 
                        $prodActive = $prod['active'] ?? 1;
                        if (!$prodActive) continue;
                    ?>
                        <div class="product-item" 
                             data-id="<?= $prod['id'] ?>"
                             data-name="<?= htmlspecialchars($prod['name']) ?>"
                             data-price="<?= (float)$prod['price'] ?>"
                             data-category="<?= $prod['category_id'] ?? 0 ?>"
                             data-has-custom="<?= isset($customizationMap[$prod['id']]) ? '1' : '0' ?>">
                            <?php if (!empty($prod['image'])): ?>
                                <img src="/<?= htmlspecialchars($prod['image']) ?>" class="product-image" alt="">
                            <?php else: ?>
                                <div class="product-image" style="display:flex;align-items:center;justify-content:center;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <path d="m21 15-5-5L5 21"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <div class="product-info">
                                <div class="product-name"><?= htmlspecialchars($prod['name']) ?></div>
                                <div class="product-price">R$ <?= number_format((float)$prod['price'], 2, ',', '.') ?></div>
                            </div>
                            <div class="product-qty-controls">
                                <button type="button" class="qty-btn qty-minus">−</button>
                                <input type="number" class="qty-input" value="0" min="0" readonly>
                                <button type="button" class="qty-btn qty-plus primary">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Resumo do Carrinho -->
    <div class="form-section-card">
        <div class="form-section-card__header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="9" cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <span class="form-section-card__title">Resumo do Pedido</span>
        </div>
        <div class="form-section-card__body">
            <div class="cart-summary" id="cartSummary">
                <div class="empty-cart" id="emptyCart">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <p>Adicione produtos ao pedido</p>
                </div>
                <div id="cartItems"></div>
                <div class="cart-total" id="cartTotalRow" style="display: none;">
                    <span class="cart-total-label">Total</span>
                    <span class="cart-total-value" id="cartTotalValue">R$ 0,00</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Forma de Pagamento -->
    <div class="form-section-card">
        <div class="form-section-card__header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            <span class="form-section-card__title">Pagamento</span>
        </div>
        <div class="form-section-card__body">
            <?php
            // Agrupar métodos por tipo
            $paymentMethods = $paymentMethods ?? [];
            $pixMethods = [];
            $creditMethods = [];
            $debitMethods = [];
            $cashMethods = [];
            $voucherMethods = [];
            $otherMethods = [];
            
            foreach ($paymentMethods as $method) {
                $type = $method['type'] ?? 'others';
                switch ($type) {
                    case 'pix': $pixMethods[] = $method; break;
                    case 'credit': $creditMethods[] = $method; break;
                    case 'debit': $debitMethods[] = $method; break;
                    case 'cash': $cashMethods[] = $method; break;
                    case 'voucher': $voucherMethods[] = $method; break;
                    default: $otherMethods[] = $method; break;
                }
            }
            
            // Nomes das bandeiras de crédito
            $creditNames = array_map(function($m) { return $m['name']; }, $creditMethods);
            $creditSubtitle = count($creditNames) > 0 ? implode(', ', array_slice($creditNames, 0, 3)) . (count($creditNames) > 3 ? ' e mais' : '') : '';
            
            // Nomes das bandeiras de débito
            $debitNames = array_map(function($m) { return $m['name']; }, $debitMethods);
            $debitSubtitle = count($debitNames) > 0 ? implode(', ', array_slice($debitNames, 0, 3)) . (count($debitNames) > 3 ? ' e mais' : '') : '';
            ?>
            
            <div class="payment-methods">
                <?php if (empty($paymentMethods)): ?>
                    <div style="color: #9ca3af; text-align: center; font-size: 14px; padding: 20px;">
                        Nenhum método de pagamento cadastrado
                    </div>
                <?php else: ?>
                    
                    <?php if ($pixMethods): ?>
                    <!-- PIX -->
                    <div class="payment-type-btn" data-type="pix" data-method-id="<?= $pixMethods[0]['id'] ?>" onclick="selectPaymentType('pix', <?= $pixMethods[0]['id'] ?>)">
                        <input type="radio" name="payment_method" value="pix">
                        <div class="payment-info">
                            <img src="<?= base_url('assets/card-brands/pix.svg') ?>" alt="PIX" class="payment-icon">
                            <div class="payment-text">
                                <div class="payment-title">PIX</div>
                                <div class="payment-subtitle">Aprovação instantânea</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($cashMethods): ?>
                    <!-- Dinheiro -->
                    <div class="payment-type-btn" data-type="cash" data-method-id="<?= $cashMethods[0]['id'] ?>" onclick="selectPaymentType('cash', <?= $cashMethods[0]['id'] ?>)">
                        <input type="radio" name="payment_method" value="cash">
                        <div class="payment-info">
                            <img src="<?= base_url('assets/card-brands/cash.svg') ?>" alt="Dinheiro" class="payment-icon">
                            <div class="payment-text">
                                <div class="payment-title">Dinheiro</div>
                                <div class="payment-subtitle">Pagamento na entrega</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($creditMethods): ?>
                    <!-- Cartão de Crédito -->
                    <div class="payment-type-btn" data-type="credit" onclick="selectPaymentType('credit')">
                        <input type="radio" name="payment_method" value="credit">
                        <div class="payment-info">
                            <img src="<?= base_url('assets/card-brands/credit.svg') ?>" alt="Crédito" class="payment-icon">
                            <div class="payment-text">
                                <div class="payment-title">Cartão de crédito</div>
                                <div class="payment-subtitle"><?= htmlspecialchars($creditSubtitle) ?></div>
                            </div>
                        </div>
                        <svg class="arrow" viewBox="0 0 24 24" fill="none">
                            <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <!-- Credit Card Brands -->
                    <div class="card-brands" id="credit-brands">
                        <?php foreach ($creditMethods as $method): 
                            $methodName = $method['name'] ?? 'Cartão';
                            $nameLower = strtolower($methodName);
                            $brandMapping = ['visa' => 'visa.svg', 'mastercard' => 'mastercard.svg', 'master' => 'mastercard.svg', 'elo' => 'elo.svg', 'hipercard' => 'hipercard.svg', 'hiper' => 'hipercard.svg', 'diners' => 'diners.svg', 'amex' => 'others.svg'];
                            $icon = 'credit.svg';
                            foreach ($brandMapping as $keyword => $file) {
                                if (strpos($nameLower, $keyword) !== false) { $icon = $file; break; }
                            }
                        ?>
                        <div class="brand-btn" data-method-id="<?= $method['id'] ?>" onclick="selectCardBrand('credit', <?= $method['id'] ?>)">
                            <img src="<?= base_url('assets/card-brands/' . $icon) ?>" alt="<?= htmlspecialchars($methodName) ?>" onerror="this.src='<?= base_url('assets/card-brands/credit.svg') ?>'">
                            <span><?= htmlspecialchars($methodName) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($debitMethods): ?>
                    <!-- Cartão de Débito -->
                    <div class="payment-type-btn" data-type="debit" onclick="selectPaymentType('debit')">
                        <input type="radio" name="payment_method" value="debit">
                        <div class="payment-info">
                            <img src="<?= base_url('assets/card-brands/debit.svg') ?>" alt="Débito" class="payment-icon">
                            <div class="payment-text">
                                <div class="payment-title">Cartão de débito</div>
                                <div class="payment-subtitle"><?= htmlspecialchars($debitSubtitle) ?></div>
                            </div>
                        </div>
                        <svg class="arrow" viewBox="0 0 24 24" fill="none">
                            <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <!-- Debit Card Brands -->
                    <div class="card-brands" id="debit-brands">
                        <?php foreach ($debitMethods as $method): 
                            $methodName = $method['name'] ?? 'Débito';
                            $nameLower = strtolower($methodName);
                            $brandMapping = ['visa' => 'visa.svg', 'mastercard' => 'mastercard.svg', 'master' => 'mastercard.svg', 'elo' => 'elo.svg', 'hipercard' => 'hipercard.svg', 'hiper' => 'hipercard.svg'];
                            $icon = 'debit.svg';
                            foreach ($brandMapping as $keyword => $file) {
                                if (strpos($nameLower, $keyword) !== false) { $icon = $file; break; }
                            }
                        ?>
                        <div class="brand-btn" data-method-id="<?= $method['id'] ?>" onclick="selectCardBrand('debit', <?= $method['id'] ?>)">
                            <img src="<?= base_url('assets/card-brands/' . $icon) ?>" alt="<?= htmlspecialchars($methodName) ?>" onerror="this.src='<?= base_url('assets/card-brands/debit.svg') ?>'">
                            <span><?= htmlspecialchars($methodName) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($voucherMethods): ?>
                    <!-- Vale -->
                    <div class="payment-type-btn" data-type="voucher" data-method-id="<?= $voucherMethods[0]['id'] ?>" onclick="selectPaymentType('voucher', <?= $voucherMethods[0]['id'] ?>)">
                        <input type="radio" name="payment_method" value="voucher">
                        <div class="payment-info">
                            <img src="<?= base_url('assets/card-brands/voucher.svg') ?>" alt="Vale" class="payment-icon">
                            <div class="payment-text">
                                <div class="payment-title">Vale Alimentação/Refeição</div>
                                <div class="payment-subtitle">Pagamento na entrega</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($otherMethods): ?>
                    <?php foreach ($otherMethods as $method): ?>
                    <div class="payment-type-btn" data-type="others" onclick="selectPaymentType('others', <?= $method['id'] ?>)">
                        <input type="radio" name="payment_method" value="<?= htmlspecialchars($method['type']) ?>">
                        <div class="payment-info">
                            <img src="<?= base_url('assets/card-brands/others.svg') ?>" alt="Outros" class="payment-icon">
                            <div class="payment-text">
                                <div class="payment-title"><?= htmlspecialchars($method['name']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Observações -->
    <div class="form-section-card">
        <div class="form-section-card__header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span class="form-section-card__title">Observações</span>
        </div>
        <div class="form-section-card__body">
            <textarea name="notes" class="form-input" rows="2"
                      placeholder="Observações do pedido..."><?= htmlspecialchars((string)($values['notes'] ?? '')) ?></textarea>
        </div>
    </div>

    <!-- Hidden input para payment_method_id -->
    <input type="hidden" name="payment_method_id" id="paymentMethodIdInput" value="<?= htmlspecialchars((string)($values['payment_method_id'] ?? '')) ?>">

    <!-- Hidden inputs for items -->
    <div id="hiddenItemsContainer"></div>

    <!-- Footer Spacer -->
    <div class="form-footer-spacer"></div>

    <!-- Action Buttons -->
    <div class="form-actions">
        <a href="/orders" class="btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
            Cancelar
        </a>
        <button type="submit" class="btn-primary" id="submitBtn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 7L9 18l-5-5"/>
            </svg>
            <?= $isEdit ? 'Atualizar Pedido' : 'Criar Pedido' ?>
        </button>
    </div>
</form>

<script>
const productCustomizations = <?= json_encode($customizationMap ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // cart: id -> {id, name, price, qty, customizationData (null or object), customKey}
    const cart = {};
    let cartIndex = 0; // unique key per cart entry (allows same product with diff customizations)

    const initialItems = <?= json_encode($initialItems ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const initialCityId = <?= json_encode($values['city_id'] ?? null) ?>;
    const initialZoneId = <?= json_encode($values['zone_id'] ?? null) ?>;
    const initialDeliveryType = <?= json_encode($deliveryType ?? 'delivery') ?>;
    const initialPaymentMethodId = <?= json_encode($values['payment_method_id'] ?? null) ?>;
    const paymentMethodTypeMap = <?= json_encode($paymentMethodTypeMap ?? [], JSON_UNESCAPED_UNICODE) ?>;

    // ===== DADOS DE ZONAS POR CIDADE =====
    const zonesByCity = <?= json_encode($zonesByCity ?? []) ?>;
    const zonesPresent = <?= !empty($zonesPresent) ? 'true' : 'false' ?>;
    
    // ===== SELEÇÃO DE CIDADE/BAIRRO =====
    const citySelect = document.getElementById('citySelect');
    const zoneSelect = document.getElementById('zoneSelect');
    const deliveryFeeInput = document.getElementById('deliveryFeeInput');
    const deliveryFeeDisplay = document.getElementById('deliveryFeeDisplay');
    const neighborhoodInput = document.getElementById('neighborhoodInput');
    
    if (citySelect && zoneSelect) {
        citySelect.addEventListener('change', function() {
            const cityId = this.value;
            
            // Limpar e popular bairros
            zoneSelect.innerHTML = '';
            
            if (!cityId) {
                zoneSelect.innerHTML = '<option value="">Escolha a cidade primeiro</option>';
                zoneSelect.disabled = true;
                if (deliveryFeeDisplay) deliveryFeeDisplay.textContent = 'Selecione o bairro';
                if (deliveryFeeInput) deliveryFeeInput.value = '0';
                if (neighborhoodInput) neighborhoodInput.value = '';
                if (typeof updateCartDisplay === 'function') updateCartDisplay();
                return;
            }
            
            // Tentar acessar como string e como número
            const zones = zonesByCity[cityId] || zonesByCity[parseInt(cityId)] || [];
            
            if (zones.length === 0) {
                zoneSelect.innerHTML = '<option value="">Nenhum bairro cadastrado</option>';
                zoneSelect.disabled = true;
            } else {
                zoneSelect.innerHTML = '<option value="">Selecione o bairro</option>';
                zones.forEach(zone => {
                    const opt = document.createElement('option');
                    opt.value = zone.id;
                    opt.textContent = zone.name;
                    opt.dataset.fee = zone.fee;
                    opt.dataset.name = zone.name;
                    zoneSelect.appendChild(opt);
                });
                zoneSelect.disabled = false;
            }
            
            // Limpar taxa
            if (deliveryFeeDisplay) deliveryFeeDisplay.textContent = 'Selecione o bairro';
            if (deliveryFeeInput) deliveryFeeInput.value = '0';
            if (neighborhoodInput) neighborhoodInput.value = '';
            if (typeof updateCartDisplay === 'function') updateCartDisplay();
            
            // Remover erro
            this.classList.remove('input-error');
        });
        
        zoneSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const fee = parseFloat(selectedOption?.dataset?.fee || 0);
            const zoneName = selectedOption?.dataset?.name || '';
            
            if (deliveryFeeDisplay) {
                deliveryFeeDisplay.textContent = fee > 0 ? `R$ ${fee.toFixed(2).replace('.', ',')}` : 'Grátis';
            }
            if (deliveryFeeInput) {
                deliveryFeeInput.value = fee.toFixed(2);
            }
            if (neighborhoodInput) {
                neighborhoodInput.value = zoneName;
            }
            
            // Remover erro
            this.classList.remove('input-error');
            
            if (typeof updateCartDisplay === 'function') updateCartDisplay();
        });
    }

    if (initialCityId && citySelect) {
        citySelect.value = String(initialCityId);
        citySelect.dispatchEvent(new Event('change'));
        if (initialZoneId && zoneSelect) {
            setTimeout(() => {
                zoneSelect.value = String(initialZoneId);
                zoneSelect.dispatchEvent(new Event('change'));
            }, 50);
        }
    }
    
    // ===== MÁSCARA DE TELEFONE E BUSCA DE CLIENTE =====
    const phoneInput = document.getElementById('customerPhone');
    const nameInput = document.getElementById('customerName');
    const searchStatus = document.getElementById('customerSearchStatus');
    let searchTimeout = null;
    
    // Strip DDI 55 se presente (igual ao desktop)
    function stripDDI(digits) {
        if (digits.length >= 12 && digits.startsWith('55')) {
            return digits.substring(2);
        }
        return digits;
    }

    // Máscara de telefone
    function formatPhone(value) {
        let numbers = value.replace(/\D/g, '');
        numbers = stripDDI(numbers);
        const limited = numbers.substring(0, 11);
        if (limited.length <= 2) {
            return limited.length ? `(${limited}` : '';
        } else if (limited.length <= 7) {
            return `(${limited.slice(0, 2)}) ${limited.slice(2)}`;
        } else if (limited.length <= 10) {
            return `(${limited.slice(0, 2)}) ${limited.slice(2, 6)}-${limited.slice(6)}`;
        } else {
            return `(${limited.slice(0, 2)}) ${limited.slice(2, 7)}-${limited.slice(7, 11)}`;
        }
    }
    
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            const cursorPos = e.target.selectionStart;
            const oldLength = e.target.value.length;
            e.target.value = formatPhone(e.target.value);
            const newLength = e.target.value.length;
            const newCursor = cursorPos + (newLength - oldLength);
            e.target.setSelectionRange(newCursor, newCursor);
            
            // Buscar cliente após digitar
            const numbers = e.target.value.replace(/\D/g, '');
            if (numbers.length >= 10) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => searchCustomer(numbers), 500);
            } else if (searchStatus) {
                searchStatus.style.display = 'none';
            }
        });
    }
    
    // Buscar cliente por telefone
    async function searchCustomer(phone) {
        if (!searchStatus) return;
        
        searchStatus.style.display = 'block';
        searchStatus.innerHTML = '<span style="color: #64748b;">Buscando cliente...</span>';
        
        try {
            const response = await fetch(`/api/customers/search?phone=${encodeURIComponent(phone)}`);
            const result = await response.json();
            
            if (result.success && result.data.found) {
                const customer = result.data.customer;
                if (nameInput) nameInput.value = customer.name || '';
                
                // Preencher endereço se disponível
                if (customer.address) {
                    const streetInput = document.getElementById('streetInput');
                    const numberInput = document.getElementById('numberInput');
                    const neighborhoodInputEl = document.getElementById('neighborhoodInput');
                    const complementInput = document.getElementById('complementInput');
                    const referenceInput = document.getElementById('referenceInput');
                    const deliveryFeeInputEl = document.getElementById('deliveryFeeInput');
                    
                    if (streetInput) streetInput.value = customer.address.street || '';
                    if (numberInput) numberInput.value = customer.address.number || '';
                    if (complementInput) complementInput.value = customer.address.complement || '';
                    if (referenceInput) referenceInput.value = customer.address.reference || '';
                    
                    // Preencher cidade/bairro - selects (com zonas) ou texto
                    const citySelectEl = document.getElementById('citySelect');
                    const zoneSelectEl = document.getElementById('zoneSelect');
                    
                    if (citySelectEl && zoneSelectEl && customer.address.city_id) {
                        // Selecionar a cidade
                        citySelectEl.value = customer.address.city_id;
                        citySelectEl.dispatchEvent(new Event('change'));
                        
                        // Aguardar o change popular os bairros, depois selecionar a zona
                        setTimeout(() => {
                            if (customer.address.zone_id) {
                                zoneSelectEl.value = customer.address.zone_id;
                                zoneSelectEl.dispatchEvent(new Event('change'));
                            }
                        }, 100);
                    } else {
                        // Sem zonas cadastradas - preencher bairro como texto
                        if (neighborhoodInputEl) neighborhoodInputEl.value = customer.address.neighborhood || '';
                        
                        // Preencher taxa de entrega manual
                        if (deliveryFeeInputEl && customer.address.delivery_fee !== undefined) {
                            deliveryFeeInputEl.value = customer.address.delivery_fee.toFixed(2).replace('.', ',');
                        }
                    }
                    
                    if (typeof updateCartDisplay === 'function') updateCartDisplay();
                }
                
                searchStatus.innerHTML = '<span style="color: #10b981;">✓ Cliente encontrado!</span>';
                setTimeout(() => { if (searchStatus) searchStatus.style.display = 'none'; }, 2000);
            } else {
                searchStatus.innerHTML = '<span style="color: #64748b;">Novo cliente</span>';
                setTimeout(() => { if (searchStatus) searchStatus.style.display = 'none'; }, 2000);
            }
        } catch (error) {
            console.error('Erro ao buscar cliente:', error);
            if (searchStatus) searchStatus.style.display = 'none';
        }
    }
    
    // Delivery type toggle
    const deliveryOptions = document.querySelectorAll('.delivery-option');
    const addressFields = document.getElementById('address-fields');
    
    deliveryOptions.forEach(opt => {
        opt.addEventListener('click', function() {
            deliveryOptions.forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input').checked = true;
            
            if (this.querySelector('input').value === 'pickup') {
                addressFields.style.display = 'none';
            } else {
                addressFields.style.display = 'block';
            }
        });
    });

    if (addressFields && initialDeliveryType === 'pickup') {
        addressFields.style.display = 'none';
    }
    
    // Category tabs
    const categoryTabs = document.querySelectorAll('.category-tab');
    const productItems = document.querySelectorAll('.product-item');
    
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            categoryTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const catId = this.dataset.category;
            
            productItems.forEach(item => {
                if (catId === 'all' || item.dataset.category === catId) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Product quantity controls
    productItems.forEach(item => {
        const plusBtn = item.querySelector('.qty-plus');
        const minusBtn = item.querySelector('.qty-minus');
        const qtyInput = item.querySelector('.qty-input');
        const productId = item.dataset.id;
        const productName = item.dataset.name;
        const productPrice = parseFloat(item.dataset.price);
        const hasCustom = item.dataset.hasCustom === '1';
        
        plusBtn.addEventListener('click', function(e) {
            e.stopPropagation();

            // Limpar erro de validação ao adicionar produto
            document.getElementById('productsGrid')?.classList.remove('input-error');

            if (hasCustom) {
                // Abre modal de customização
                openCustModal(productId, productName, productPrice, null, null);
                return;
            }

            // Produto sem customização — comportamento original
            let qty = parseInt(qtyInput.value) || 0;
            qty++;
            qtyInput.value = qty;
            item.classList.add('selected');
            
            const key = 'p_' + productId;
            if (!cart[key]) {
                cart[key] = { id: productId, name: productName, price: productPrice, qty: 0, customizationData: null, customKey: key };
            }
            cart[key].qty = qty;
            
            updateCartDisplay();
        });
        
        minusBtn.addEventListener('click', function(e) {
            e.stopPropagation();

            if (hasCustom) {
                // Remove a última entrada com este productId
                const keys = Object.keys(cart).filter(k => cart[k].id === productId);
                if (keys.length > 0) {
                    const lastKey = keys[keys.length - 1];
                    delete cart[lastKey];
                    const remaining = Object.keys(cart).filter(k => cart[k].id === productId).length;
                    qtyInput.value = remaining;
                    if (remaining === 0) item.classList.remove('selected');
                    updateCartDisplay();
                }
                return;
            }

            const key = 'p_' + productId;
            let qty = parseInt(qtyInput.value) || 0;
            if (qty > 0) {
                qty--;
                qtyInput.value = qty;
                
                if (qty === 0) {
                    item.classList.remove('selected');
                    delete cart[key];
                } else {
                    cart[key].qty = qty;
                }
                
                updateCartDisplay();
            }
        });
    });
    
    function fmtBrl(v) {
        return 'R$ ' + Number(v).toFixed(2).replace('.', ',');
    }

    function buildCustomSummaryMobile(data) {
        if (!data || !data.groups) return '';
        const parts = [];
        data.groups.forEach(g => {
            g.items.forEach(it => {
                if ((it.qty || it._qty || 0) > 0) {
                    const q = it.qty || it._qty;
                    parts.push((q > 1 ? q + '× ' : '') + (it.name || it.label || ''));
                } else if (it.selected) {
                    parts.push(it.name || it.label || '');
                }
            });
        });
        return parts.length ? parts.slice(0, 3).join(', ') + (parts.length > 3 ? '…' : '') : '';
    }

    function updateCartDisplay() {
        const cartItemsEl = document.getElementById('cartItems');
        const emptyCart = document.getElementById('emptyCart');
        const cartTotalRow = document.getElementById('cartTotalRow');
        const cartTotalValue = document.getElementById('cartTotalValue');
        const hiddenContainer = document.getElementById('hiddenItemsContainer');
        
        const items = Object.values(cart);
        
        if (items.length === 0) {
            emptyCart.style.display = 'block';
            cartTotalRow.style.display = 'none';
            cartItemsEl.innerHTML = '';
            hiddenContainer.innerHTML = '';
            return;
        }
        
        emptyCart.style.display = 'none';
        cartTotalRow.style.display = 'flex';
        
        let html = '';
        let hiddenHtml = '';
        let subtotal = 0;
        let idx = 0;
        
        items.forEach(item => {
            const qty = item.qty || 1;
            const price = item.price;
            const itemTotal = price * qty;
            subtotal += itemTotal;

            const customSummary = buildCustomSummaryMobile(item.customizationData);
            const customLabel = customSummary ? `<div style="font-size:12px;color:#9ca3af;margin-top:2px">${customSummary}</div>` : '';
            
            html += `
                <div class="cart-item" style="align-items:flex-start">
                    <div style="flex:1;min-width:0">
                        <div class="cart-item-name">${escHtmlCart(item.name)}</div>
                        ${customLabel}
                        <div class="cart-item-qty">Qtd: ${qty} × ${fmtBrl(price)}</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                        <div class="cart-item-price">${fmtBrl(itemTotal)}</div>
                        <button type="button" onclick="removeCartItem('${item.customKey}')" style="background:#fee2e2;border:none;border-radius:50%;width:22px;height:22px;cursor:pointer;color:#dc2626;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0">×</button>
                    </div>
                </div>
            `;

            const customJson = item.customizationData ? JSON.stringify(item.customizationData) : '';
            hiddenHtml += `
                <input type="hidden" name="items[${idx}][product_id]" value="${item.id}">
                <input type="hidden" name="items[${idx}][quantity]" value="${qty}">
                <input type="hidden" name="items[${idx}][unit_price]" value="${item.price}">
                <input type="hidden" name="customization_data_json[${idx}]" value="${escAttr(customJson)}">
            `;
            idx++;
        });
        
        cartItemsEl.innerHTML = html;
        hiddenContainer.innerHTML = hiddenHtml;
        
        // Add delivery fee to total
        const deliveryFeeInput = document.getElementById('deliveryFeeInput');
        let deliveryFee = 0;
        if (deliveryFeeInput && deliveryFeeInput.value) {
            deliveryFee = parseFloat(deliveryFeeInput.value.replace(',', '.')) || 0;
        }

        const discountInput = document.getElementById('discountInput');
        let discount = 0;
        if (discountInput && discountInput.value) {
            discount = parseFloat(discountInput.value.replace(',', '.')) || 0;
        }

        const total = Math.max(0, subtotal + deliveryFee - discount);
        cartTotalValue.textContent = fmtBrl(total);
    }

    if (Array.isArray(initialItems) && initialItems.length > 0) {
        initialItems.forEach(item => {
            const productId = String(item.product_id || '');
            if (!productId) return;
            const qty = parseInt(item.quantity, 10) || 0;
            if (qty <= 0) return;

            const productEl = document.querySelector('.product-item[data-id="' + productId + '"]');
            const productName = item.product_name || (productEl ? productEl.dataset.name : 'Produto');
            const unitPrice = parseFloat(item.unit_price) || 0;
            const customData = item.customization_data || null;
            const hasCustom = productEl && productEl.dataset.hasCustom === '1';
            const treatAsCustom = hasCustom || (customData && Object.keys(customData).length > 0);

            if (treatAsCustom) {
                for (let i = 0; i < qty; i++) {
                    const key = 'c_' + productId + '_' + (++cartIndex);
                    cart[key] = { id: productId, name: productName, price: unitPrice, qty: 1, customizationData: customData, customKey: key };
                }
            } else {
                const key = 'p_' + productId;
                if (!cart[key]) {
                    cart[key] = { id: productId, name: productName, price: unitPrice, qty: 0, customizationData: null, customKey: key };
                }
                cart[key].qty += qty;
            }
        });

        document.querySelectorAll('.product-item').forEach(item => {
            const id = item.dataset.id;
            const qtyInput = item.querySelector('.qty-input');
            if (!qtyInput) return;
            const hasCustom = item.dataset.hasCustom === '1';
            if (hasCustom) {
                qtyInput.value = Object.values(cart).filter(c => c.id === id).length;
            } else {
                const entry = Object.values(cart).find(c => c.id === id);
                qtyInput.value = entry ? entry.qty : 0;
            }
            if (parseInt(qtyInput.value, 10) > 0) item.classList.add('selected');
        });

        updateCartDisplay();
    }

    function escHtmlCart(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escAttr(s) {
        return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;');
    }

    window.removeCartItem = function(key) {
        if (!cart[key]) return;
        const productId = cart[key].id;
        delete cart[key];
        // Atualiza qty no produto
        const productEl = document.querySelector(`.product-item[data-id="${productId}"]`);
        if (productEl) {
            const remaining = Object.values(cart).filter(c => c.id === productId).length ||
                              Object.values(cart).filter(c => c.id === productId).reduce((s,c) => s + (c.qty||1), 0);
            // Para produtos sem customização usa .qty, com customização conta entradas
            const hasCustom = productEl.dataset.hasCustom === '1';
            const qtyInput = productEl.querySelector('.qty-input');
            if (qtyInput) {
                if (hasCustom) {
                    qtyInput.value = Object.values(cart).filter(c => c.id === productId).length;
                } else {
                    const entry = Object.values(cart).find(c => c.id === productId);
                    qtyInput.value = entry ? entry.qty : 0;
                }
                if (parseInt(qtyInput.value) <= 0) productEl.classList.remove('selected');
            }
        }
        updateCartDisplay();
    };
    
    // ===== MODAL DE CUSTOMIZAÇÃO =====
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function makeThumb(imgPath) {
        const svgPlaceholder =
            '<svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        if (!imgPath) return '<div class="crow-thumb">' + svgPlaceholder + '</div>';
        // Garante path absoluto para não herdar prefixo da rota atual
        const absPath = /^https?:\/\//.test(imgPath) ? imgPath : '/' + imgPath.replace(/^\/+/, '');
        const safeUrl = encodeURI(absPath);
        return '<div class="crow-thumb" style="background:url(' + safeUrl + ') center/cover no-repeat #f3f4f6"></div>';
    }

    let custState = null;

    function renderCustModal() {
        if (!custState) return;
        let html = '';
        custState.groups.forEach(function(g, gi) {
            let headingExtra = '';
            let counterHtml = '';
            let itemsHtml = '';
            if (g.type === 'pool') {
                const poolFree = g.pool_free || g.max || 4;
                counterHtml =
                    '<div class="cpool-counter" id="cpool-counter-' + gi + '">' +
                    '<span class="cpool-counter-num" id="cpool-num-' + gi + '">' + g._poolTotal + '</span>' +
                    '<span> / ' + poolFree + ' inclusos</span>' +
                    '<span class="cpool-extra-badge" id="cpool-extra-badge-' + gi + '"></span>' +
                    '</div>';
                itemsHtml = g.items.map(function(it, ii) {
                    return '<div class="crow">' +
                        makeThumb(it.image_path || it.img || null) +
                        '<div class="crow-info"><div class="crow-name">' + escHtml(it.name || it.label || '') + '</div>' +
                        '<div class="crow-pool-price" id="pprice-' + gi + '-' + ii + '"></div></div>' +
                        '<div class="cstepper">' +
                        '<button type="button" class="cst-btn" data-gi="' + gi + '" data-ii="' + ii + '" data-dir="minus" disabled>−</button>' +
                        '<span class="cst-val" id="cst-val-' + gi + '-' + ii + '">0</span>' +
                        '<button type="button" class="cst-btn" data-gi="' + gi + '" data-ii="' + ii + '" data-dir="plus">+</button>' +
                        '</div></div>';
                }).join('');
            } else if (g.type === 'single') {
                const cl = g.max === 1 ? 'Escolha 1 opção' : 'Escolha até ' + g.max;
                headingExtra = '<div style="padding:2px 20px 10px;font-size:13px;color:#6b7280">' + cl + '</div>';
                itemsHtml = g.items.map(function(it, ii) {
                    const delta = it.delta || it.sale_price || 0;
                    const priceHtml = delta > 0 ? '<div class="crow-price">+' + fmtBrl(delta) + '</div>' : '';
                    const isSel = g._selectedIdx === ii;
                    const radioHtml = '<div class="crow-radio' + (isSel ? ' sel' : '') + '" id="cradio-' + gi + '-' + ii + '">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>';
                    return '<div class="crow radio-row" data-gi="' + gi + '" data-ii="' + ii + '">' +
                        makeThumb(it.image_path || it.img || null) +
                        '<div class="crow-info"><div class="crow-name">' + escHtml(it.name || it.label || '') + '</div>' + priceHtml + '</div>' +
                        radioHtml + '</div>';
                }).join('');
            } else {
                itemsHtml = g.items.map(function(it, ii) {
                    const sale = it.sale_price || it.delta || 0;
                    const priceHtml = sale > 0 ? '<div class="crow-price">' + fmtBrl(sale) + '</div>' : '';
                    const qty = it._qty || 0;
                    const minQty = it.min !== undefined ? it.min : (it.min_qty || 0);
                    return '<div class="crow">' +
                        makeThumb(it.image_path || it.img || null) +
                        '<div class="crow-info"><div class="crow-name">' + escHtml(it.name || it.label || '') + '</div>' + priceHtml + '</div>' +
                        '<div class="cstepper">' +
                        '<button type="button" class="cst-btn" data-gi="' + gi + '" data-ii="' + ii + '" data-dir="minus"' + (qty <= minQty ? ' disabled' : '') + '>−</button>' +
                        '<span class="cst-val" id="cst-val-' + gi + '-' + ii + '">' + qty + '</span>' +
                        '<button type="button" class="cst-btn" data-gi="' + gi + '" data-ii="' + ii + '" data-dir="plus">+</button>' +
                        '</div></div>';
                }).join('');
            }
            html += '<div class="cgroup">' +
                '<div class="cgroup-heading">' + escHtml(g.name) + (g.min > 0 ? ' <span style="color:#ef4444;font-size:13px">*</span>' : '') + '</div>' +
                headingExtra + counterHtml +
                '<div>' + itemsHtml + '</div></div>';
        });
        document.getElementById('custModalBody').innerHTML = html;
        updateCustPrice();
        bindCustEvents();
    }

    function updateCustPrice() {
        if (!custState) return;
        var delta = 0;
        custState.groups.forEach(function(g, gi) {
            const poolFree = g.pool_free || g.max || 4;
            const extras = Math.max(0, (g._poolTotal || 0) - poolFree);
            const isAtCapacity = (g._poolTotal || 0) >= poolFree;
            if (g.type === 'pool') {
                var freeRemaining = poolFree;
                g.items.forEach(function(it, ii) {
                    const qty = it._qty || 0;
                    const free = Math.min(qty, freeRemaining);
                    const paid = qty - free;
                    freeRemaining -= free;
                    const unitPrice = it.sale_price || it.extra_price || 0;
                    const pEl = document.getElementById('pprice-' + gi + '-' + ii);
                    if (pEl) {
                        if (qty === 0) {
                            if (isAtCapacity && unitPrice > 0) {
                                pEl.textContent = 'R$ ' + unitPrice.toFixed(2).replace('.', ',');
                                pEl.className = 'crow-pool-price';
                            } else {
                                pEl.textContent = '';
                                pEl.className = 'crow-pool-price';
                            }
                        } else if (paid > 0) {
                            pEl.textContent = 'R$ ' + (paid * unitPrice).toFixed(2).replace('.', ',') + ' · extra';
                            pEl.className = 'crow-pool-price charged';
                        } else {
                            pEl.textContent = (isAtCapacity && unitPrice > 0)
                                ? 'Incluso · R$ ' + unitPrice.toFixed(2).replace('.', ',')
                                : 'Incluso';
                            pEl.className = 'crow-pool-price free';
                        }
                    }
                    if (paid > 0) delta += paid * unitPrice;
                });
            } else if (g.type === 'single') {
                if (g._selectedIdx >= 0) delta += (g.items[g._selectedIdx].delta || g.items[g._selectedIdx].sale_price || 0);
            } else {
                g.items.forEach(function(it) {
                    var qty = it._qty || 0;
                    var defQty = it.default_qty || 0;
                    var extraQty = Math.max(0, qty - defQty);
                    delta += (it.delta || it.sale_price || 0) * extraQty;
                });
            }
        });
        custState._totalDelta = delta;
        const total = custState.basePrice + delta;
        const btn = document.getElementById('custModalConfirm');
        if (btn) btn.textContent = 'Adicionar — ' + fmtBrl(total);
    }

    function bindCustEvents() {
        document.querySelectorAll('#custModalBody .cst-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const gi = parseInt(this.dataset.gi);
                const ii = parseInt(this.dataset.ii);
                const dir = this.dataset.dir;
                const g = custState.groups[gi];
                const it = g.items[ii];
                const poolFree = g.pool_free || g.max || 4;
                if (g.type === 'pool') {
                    if (dir === 'plus') { it._qty = (it._qty || 0) + 1; g._poolTotal = (g._poolTotal || 0) + 1; }
                    else if ((it._qty || 0) > 0) { it._qty--; g._poolTotal = (g._poolTotal || 0) - 1; }
                    const numEl = document.getElementById('cpool-num-' + gi);
                    if (numEl) numEl.textContent = g._poolTotal;
                    const extra = Math.max(0, g._poolTotal - poolFree);
                    const badgeEl = document.getElementById('cpool-extra-badge-' + gi);
                    if (badgeEl) badgeEl.textContent = extra > 0 ? '+' + extra + ' extra' + (extra > 1 ? 's' : '') : '';
                    const counterEl = document.getElementById('cpool-counter-' + gi);
                    if (counterEl) {
                        counterEl.classList.toggle('full', g._poolTotal >= poolFree && extra === 0);
                        counterEl.classList.toggle('extras', extra > 0);
                    }
                } else {
                    if (dir === 'plus') it._qty = (it._qty || 0) + 1;
                    else if ((it._qty || 0) > 0) it._qty--;
                }
                const valEl = document.getElementById('cst-val-' + gi + '-' + ii);
                if (valEl) valEl.textContent = it._qty || 0;
                const minusBtn = this.closest('.cstepper') && this.closest('.cstepper').querySelector('[data-dir="minus"]');
                if (minusBtn) minusBtn.disabled = (it._qty || 0) <= (it.min || it.min_qty || 0);
                updateCustPrice();
            });
        });
        document.querySelectorAll('#custModalBody .radio-row').forEach(function(row) {
            row.addEventListener('click', function() {
                const gi = parseInt(this.dataset.gi);
                const ii = parseInt(this.dataset.ii);
                custState.groups[gi]._selectedIdx = ii;
                document.querySelectorAll('#custModalBody [id^="cradio-' + gi + '-"]').forEach(function(r, i) {
                    r.classList.toggle('sel', i === ii);
                });
                updateCustPrice();
            });
        });
    }

    function buildCustomizationData() {
        if (!custState) return null;
        var totalDelta = 0;
        var groups = [];
        custState.groups.forEach(function(g) {
            var poolFree = g.pool_free || g.max || 4;
            var groupItems = [];
            if (g.type === 'pool') {
                var freeRemaining = poolFree;
                g.items.forEach(function(it) {
                    var qty = it._qty || 0;
                    if (qty <= 0) return;
                    var free = Math.min(qty, freeRemaining);
                    var paid = qty - free;
                    freeRemaining -= free;
                    var unitPrice = it.sale_price || it.extra_price || 0;
                    var price = paid * unitPrice;
                    totalDelta += price;
                    groupItems.push({ name: it.name || it.label || '', qty: qty, unit_price: unitPrice, price: price, free_qty: free, paid_qty: paid, extra_price: unitPrice });
                });
                if (groupItems.length > 0) groups.push({ name: g.name, type: 'pool', pool_free: poolFree, items: groupItems });
            } else if (g.type === 'single') {
                if (g._selectedIdx >= 0 && g._selectedIdx < g.items.length) {
                    var it = g.items[g._selectedIdx];
                    var delta = it.delta || it.sale_price || 0;
                    totalDelta += delta;
                    groupItems.push({ name: it.name || it.label || '', qty: 1, unit_price: delta, price: delta, selected: true });
                    groups.push({ name: g.name, type: 'single', items: groupItems });
                }
            } else {
                g.items.forEach(function(it) {
                    var qty = it._qty || 0;
                    var defQty = it.default_qty || 0;
                    var extraQty = Math.max(0, qty - defQty);
                    var unitPrice = it.delta || it.sale_price || 0;
                    var price = extraQty * unitPrice;
                    totalDelta += price;
                    if (qty > 0) {
                        groupItems.push({ name: it.name || it.label || '', qty: qty, unit_price: unitPrice, price: price, default_qty: defQty, delta_qty: qty - defQty });
                    } else if (defQty > 0) {
                        // Item padrão removido pelo operador
                        groupItems.push({ name: it.name || it.label || '', qty: 0, unit_price: 0, price: 0, default_qty: defQty, delta_qty: -defQty });
                    }
                });
                if (groupItems.length > 0) groups.push({ name: g.name, type: 'extra', items: groupItems });
            }
        });
        return { groups: groups, total_delta: totalDelta, has_customization: true };
    }

    function openCustModal(productId, productName, basePrice, existingData, existingKey) {
        const rawGroups = productCustomizations[productId];
        if (!rawGroups || rawGroups.length === 0) return;
        const groups = JSON.parse(JSON.stringify(rawGroups));
        groups.forEach(function(g) {
            g._poolTotal = 0;
            g._selectedIdx = -1;
            if (g.type === 'pool') {
                g.items.forEach(function(it) { it._qty = 0; });
            } else if (g.type === 'single') {
                g.items.forEach(function(it, idx) {
                    if (it.selected || it['default']) g._selectedIdx = idx;
                });
            } else {
                // extra — inicia na quantidade padrão do item
                g.items.forEach(function(it) {
                    it._qty = it.qty !== undefined ? it.qty : (it.default_qty || 0);
                });
            }
        });
        if (existingData && existingData.groups) {
            existingData.groups.forEach(function(eg, gi) {
                if (!groups[gi]) return;
                const g = groups[gi];
                eg.items.forEach(function(eit, ii) {
                    if (!g.items[ii]) return;
                    if (g.type === 'pool') { g.items[ii]._qty = eit.qty || 0; g._poolTotal += eit.qty || 0; }
                    else if (g.type === 'single') { if (eit.selected) g._selectedIdx = ii; }
                    else { g.items[ii]._qty = eit.qty || 0; }
                });
            });
        }
        custState = { productId: productId, productName: productName, basePrice: basePrice, groups: groups, _totalDelta: 0, _existingKey: existingKey || null };
        document.getElementById('custModalTitle').textContent = 'Personalizar: ' + productName;
        renderCustModal();
        document.getElementById('custModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeCustModal() {
        document.getElementById('custModal').classList.remove('open');
        document.body.style.overflow = '';
        custState = null;
    }

    document.getElementById('custModalClose').addEventListener('click', closeCustModal);
    document.getElementById('custModalCancel').addEventListener('click', closeCustModal);
    document.getElementById('custModal').addEventListener('click', function(e) {
        if (e.target === this) closeCustModal();
    });

    document.getElementById('custModalConfirm').addEventListener('click', function() {
        if (!custState) return;
        const customizationData = buildCustomizationData();
        const finalPrice = custState.basePrice + (custState._totalDelta || 0);
        const productId = custState.productId;
        const productName = custState.productName;
        if (custState._existingKey && cart[custState._existingKey]) delete cart[custState._existingKey];
        const key = 'c_' + (++cartIndex);
        cart[key] = { id: productId, name: productName, price: finalPrice, qty: 1, customizationData: customizationData, customKey: key };
        const productEl = document.querySelector('.product-item[data-id="' + productId + '"]');
        if (productEl) {
            const qi = productEl.querySelector('.qty-input');
            const count = Object.values(cart).filter(function(c) { return c.id === productId; }).length;
            if (qi) qi.value = count;
            productEl.classList.add('selected');
        }
        closeCustModal();
        document.getElementById('productsGrid') && document.getElementById('productsGrid').classList.remove('input-error');
        updateCartDisplay();
    });

    // Update total when delivery fee changes
    if (deliveryFeeInput) {
        deliveryFeeInput.addEventListener('input', updateCartDisplay);
    }

    const discountInput = document.getElementById('discountInput');
    if (discountInput) {
        discountInput.addEventListener('input', updateCartDisplay);
    }
    
    // Payment Selection - Checkout Style
    let selectedPaymentType = null;
    let selectedMethodId = null;
    
    // Remove error class when user interacts with address fields
    ['streetInput', 'numberInput', 'neighborhoodInput'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', function() {
                this.classList.remove('input-error');
            });
        }
    });
    
    window.selectPaymentType = function(type, methodId = null) {
        // Remove erro de pagamento ao selecionar
        document.querySelector('.payment-methods')?.classList.remove('input-error');
        
        const btn = document.querySelector(`.payment-type-btn[data-type="${type}"]`);
        const isAlreadyActive = btn && btn.classList.contains('active');
        
        // Se clicar no mesmo tipo (crédito/débito), fazer toggle
        if (isAlreadyActive && (type === 'credit' || type === 'debit')) {
            const brandsSection = document.getElementById(`${type}-brands`);
            if (brandsSection && brandsSection.classList.contains('show')) {
                brandsSection.classList.remove('show');
                return;
            }
        }
        
        // Remove active from all payment buttons
        document.querySelectorAll('.payment-type-btn').forEach(b => {
            b.classList.remove('active');
        });
        
        // Hide all card brand sections
        document.querySelectorAll('.card-brands').forEach(section => {
            section.classList.remove('show');
        });
        
        // Find and activate the clicked button
        if (btn) {
            btn.classList.add('active');
            const radio = btn.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }
        
        selectedPaymentType = type;
        
        // Show card brands for credit/debit
        if (type === 'credit' || type === 'debit') {
            const brandsSection = document.getElementById(`${type}-brands`);
            if (brandsSection) {
                brandsSection.classList.add('show');
            }
        }
        
        // If methodId provided (for others), set it
        if (methodId) {
            selectedMethodId = methodId;
            const hiddenPaymentId = document.getElementById('paymentMethodIdInput');
            if (hiddenPaymentId) hiddenPaymentId.value = methodId;
        }
    };
    
    window.selectCardBrand = function(type, methodId) {
        // Remove active from all brand buttons in this section
        const brandsSection = document.getElementById(`${type}-brands`);
        if (brandsSection) {
            brandsSection.querySelectorAll('.brand-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Activate the clicked brand
            const brandBtn = brandsSection.querySelector(`.brand-btn[data-method-id="${methodId}"]`);
            if (brandBtn) {
                brandBtn.classList.add('active');
            }
        }
        
        selectedMethodId = methodId;
        const hiddenPaymentId = document.getElementById('paymentMethodIdInput');
        if (hiddenPaymentId) hiddenPaymentId.value = methodId;
    };

    if (initialPaymentMethodId) {
        const initialType = paymentMethodTypeMap[String(initialPaymentMethodId)] || null;
        if (initialType) {
            if (initialType === 'credit' || initialType === 'debit') {
                selectPaymentType(initialType);
                setTimeout(() => {
                    selectCardBrand(initialType, initialPaymentMethodId);
                }, 0);
            } else {
                selectPaymentType(initialType, initialPaymentMethodId);
            }
        }
    }
    
    // Combinar campos de endereço antes do submit
    const orderForm = document.querySelector('form');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            const deliveryType = document.querySelector('input[name="delivery_type"]:checked');
            const errors = [];
            let firstErrorField = null;
            
            // Limpar erros anteriores
            document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
            document.querySelectorAll('.section-error').forEach(el => el.classList.remove('section-error'));
            
            // ===== 1. VALIDAR DADOS DO CLIENTE =====
            const phoneVal = document.getElementById('customerPhone');
            const nameVal = document.getElementById('customerName');
            
            if (!phoneVal?.value?.trim() || phoneVal.value.replace(/\D/g, '').length < 10) {
                errors.push('WhatsApp / Telefone');
                if (phoneVal) phoneVal.classList.add('input-error');
                if (!firstErrorField) firstErrorField = phoneVal;
            }
            if (!nameVal?.value?.trim()) {
                errors.push('Nome do Cliente');
                if (nameVal) nameVal.classList.add('input-error');
                if (!firstErrorField) firstErrorField = nameVal;
            }
            
            // ===== 2. VALIDAR ENDEREÇO (se entrega) =====
            if (deliveryType && deliveryType.value === 'delivery') {
                const streetInput = document.getElementById('streetInput');
                const numberInput = document.getElementById('numberInput');
                const neighborhoodInputEl = document.getElementById('neighborhoodInput');
                const citySelectEl = document.getElementById('citySelect');
                const zoneSelectEl = document.getElementById('zoneSelect');
                
                // Se tem zonas cadastradas, validar cidade e bairro selecionados
                if (zonesPresent && citySelectEl && zoneSelectEl) {
                    if (!citySelectEl.value) {
                        errors.push('Cidade');
                        citySelectEl.classList.add('input-error');
                        if (!firstErrorField) firstErrorField = citySelectEl;
                    }
                    if (!zoneSelectEl.value) {
                        errors.push('Bairro');
                        zoneSelectEl.classList.add('input-error');
                        if (!firstErrorField) firstErrorField = zoneSelectEl;
                    }
                } else {
                    const neighborhood = neighborhoodInputEl?.value?.trim() || '';
                    if (!neighborhood) {
                        errors.push('Bairro');
                        if (neighborhoodInputEl) neighborhoodInputEl.classList.add('input-error');
                        if (!firstErrorField) firstErrorField = neighborhoodInputEl;
                    }
                }
                
                if (!streetInput?.value?.trim()) {
                    errors.push('Rua/Avenida');
                    if (streetInput) streetInput.classList.add('input-error');
                    if (!firstErrorField) firstErrorField = streetInput;
                }
                if (!numberInput?.value?.trim()) {
                    errors.push('Número');
                    if (numberInput) numberInput.classList.add('input-error');
                    if (!firstErrorField) firstErrorField = numberInput;
                }
            }
            
            // ===== 3. VALIDAR PRODUTOS =====
            const hasItems = Object.keys(cart).length > 0;
            if (!hasItems) {
                errors.push('Produtos (adicione pelo menos 1 item)');
                const productsGrid = document.getElementById('productsGrid');
                if (productsGrid) {
                    productsGrid.classList.add('input-error');
                    if (!firstErrorField) firstErrorField = productsGrid;
                }
            }
            
            // ===== 4. VALIDAR PAGAMENTO =====
            const paymentSelected = document.querySelector('.payment-type-btn.active');
            if (!paymentSelected) {
                errors.push('Forma de Pagamento');
                const paymentSection = document.querySelector('.payment-methods');
                if (paymentSection) {
                    paymentSection.classList.add('input-error');
                    if (!firstErrorField) firstErrorField = paymentSection;
                }
            }
            
            // ===== SE HOUVER ERROS =====
            if (errors.length > 0) {
                e.preventDefault();
                
                // Mostrar toast de erro
                showValidationToast(errors);
                
                // Scroll para o primeiro campo com erro
                if (firstErrorField) {
                    // Encontrar o card-section pai para scroll mais preciso
                    const section = firstErrorField.closest('.form-section-card') || firstErrorField;
                    const headerHeight = 70; // altura do header fixo
                    const rect = section.getBoundingClientRect();
                    const targetY = window.pageYOffset + rect.top - headerHeight - 20;
                    
                    window.scrollTo({ top: targetY, behavior: 'smooth' });
                    
                    setTimeout(() => {
                        if (firstErrorField.focus) firstErrorField.focus();
                    }, 600);
                }
                
                return false;
            }
            
            // Se validou, montar endereço formatado
            if (deliveryType && deliveryType.value === 'delivery') {
                const street = document.getElementById('streetInput')?.value || '';
                const number = document.getElementById('numberInput')?.value || '';
                const neighborhood = document.getElementById('neighborhoodInput')?.value || '';
                const complement = document.querySelector('input[name="complement"]')?.value || '';
                const reference = document.querySelector('input[name="reference"]')?.value || '';
                
                // Pegar nome da cidade se tiver zonas cadastradas
                let cityName = '';
                const citySelectEl = document.getElementById('citySelect');
                if (zonesPresent && citySelectEl && citySelectEl.selectedIndex > 0) {
                    cityName = citySelectEl.options[citySelectEl.selectedIndex].text;
                }
                
                let fullAddress = `${street}, ${number}`;
                if (complement.trim()) {
                    fullAddress += ` - ${complement}`;
                }
                fullAddress += `\n${neighborhood}`;
                if (cityName) {
                    fullAddress += ` - ${cityName}`;
                }
                if (reference.trim()) {
                    fullAddress += `\nRef: ${reference}`;
                }
                
                const hiddenAddress = document.getElementById('customerAddressHidden');
                if (hiddenAddress) {
                    hiddenAddress.value = fullAddress;
                }
            }
        });
    }

    // ===== TOAST DE VALIDAÇÃO =====
    function showValidationToast(errors) {
        // Remover toast anterior se existir
        const existing = document.querySelector('.validation-toast');
        if (existing) existing.remove();
        
        const toast = document.createElement('div');
        toast.className = 'validation-toast';
        toast.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div class="validation-toast-content">
                <div class="validation-toast-title">Preencha os campos obrigatórios</div>
                <div class="validation-toast-items">• ${errors.join(' • ')}</div>
            </div>
            <button class="validation-toast-close" onclick="this.parentElement.remove()">×</button>
        `;
        document.body.appendChild(toast);
        
        // Auto-remover após 5 segundos
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideUp 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }

});
</script>

<!-- Street Autocomplete JS -->
<script>
(function() {
  var streetInput = document.getElementById('streetInput');
  var acList = document.getElementById('street-ac-list');
  if (!streetInput || !acList) return;

  var acTimer = null;
  var acAbort = null;
  var acIndex = -1;
  var selectedFromList = false;

  function getCityName() {
    var sel = document.getElementById('citySelect');
    if (!sel) return '';
    var opt = sel.options[sel.selectedIndex];
    return opt && opt.value ? opt.textContent.trim() : '';
  }

  function getNeighborhoodName() {
    var sel = document.getElementById('zoneSelect');
    if (sel) {
      var opt = sel.options[sel.selectedIndex];
      if (opt && opt.value) return opt.dataset.name || opt.textContent.trim();
    }
    var nb = document.getElementById('neighborhoodInput');
    return nb ? nb.value.trim() : '';
  }

  function closeList() {
    acList.innerHTML = '';
    acList.classList.remove('active');
    acIndex = -1;
  }

  function trackPopularity(streetId) {
    if (!streetId || streetId <= 0) return;
    try {
      fetch('/api/street-autocomplete/popularity', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ street_id: streetId })
      }).catch(function() {});
    } catch(e) {}
  }

  function learnStreet(street) {
    if (!street || street.length < 3) return;
    var city = getCityName();
    if (!city) return;
    try {
      fetch('/api/street-autocomplete/learn', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ city: city, neighborhood: getNeighborhoodName(), street: street })
      }).catch(function() {});
    } catch(e) {}
  }

  function fetchStreets(query) {
    var city = getCityName();
    var neighborhood = getNeighborhoodName();
    if (!city || query.length < 2) { closeList(); return; }

    if (acAbort) { try { acAbort.abort(); } catch(e){} }
    acAbort = new AbortController();

    acList.innerHTML = '<div class="street-ac-loading">Buscando...</div>';
    acList.classList.add('active');

    var url = '/api/street-autocomplete'
      + '?q=' + encodeURIComponent(query)
      + '&city=' + encodeURIComponent(city)
      + '&neighborhood=' + encodeURIComponent(neighborhood);

    fetch(url, { signal: acAbort.signal, cache: 'no-store' })
      .then(function(res) { return res.json(); })
      .then(function(json) {
        var results = json.results || [];
        acList.innerHTML = '';
        if (results.length === 0) {
          acList.innerHTML = '<div class="street-ac-loading">Nenhuma rua encontrada</div>';
          acList.classList.add('active');
          return;
        }
        acIndex = -1;
        results.forEach(function(item) {
          var div = document.createElement('div');
          div.className = 'street-ac-item';
          div.textContent = item.street;
          if (item.neighborhood) {
            var small = document.createElement('small');
            small.textContent = item.neighborhood;
            div.appendChild(small);
          }
          div.addEventListener('mousedown', function(e) {
            e.preventDefault();
            streetInput.value = item.street;
            selectedFromList = true;
            closeList();
            if (item.id) trackPopularity(item.id);
            var numInput = document.getElementById('numberInput');
            if (numInput) numInput.focus();
          });
          acList.appendChild(div);
        });
        acList.classList.add('active');
      })
      .catch(function(err) {
        if (err.name !== 'AbortError') closeList();
      });
  }

  streetInput.addEventListener('input', function() {
    selectedFromList = false;
    var val = streetInput.value.trim();
    if (val.length < 2) { closeList(); return; }
    clearTimeout(acTimer);
    acTimer = setTimeout(function() { fetchStreets(val); }, 300);
  });

  streetInput.addEventListener('keydown', function(e) {
    var items = acList.querySelectorAll('.street-ac-item');
    if (!items.length) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      acIndex = Math.min(acIndex + 1, items.length - 1);
      items.forEach(function(el, i) { el.classList.toggle('active', i === acIndex); });
      items[acIndex].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      acIndex = Math.max(acIndex - 1, 0);
      items.forEach(function(el, i) { el.classList.toggle('active', i === acIndex); });
      items[acIndex].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter' && acIndex >= 0) {
      e.preventDefault();
      items[acIndex].dispatchEvent(new Event('mousedown'));
    } else if (e.key === 'Escape') {
      closeList();
    }
  });

  streetInput.addEventListener('blur', function() {
    var val = streetInput.value.trim();
    if (val.length >= 5 && !selectedFromList) {
      learnStreet(val);
    }
    setTimeout(closeList, 200);
  });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
