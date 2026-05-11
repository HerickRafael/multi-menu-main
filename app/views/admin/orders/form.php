<?php
// admin/orders/form.php — Criar/editar pedido (estilo consistente com mobile)

$isEdit = !empty($isEdit);
$order = $order ?? null;
$orderNumber = $order['order_number'] ?? $order['id'] ?? null;
$slug = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));

$defaults = $defaults ?? [];
$prefill = $prefill ?? [];
$values = array_merge($defaults, $prefill);

$deliveryType = $values['delivery_type'] ?? 'delivery';
$isPickup = $deliveryType === 'pickup';

$phoneValue = $values['customer_phone'] ?? '';
if ($phoneValue !== '' && function_exists('format_phone_br')) {
  $phoneValue = format_phone_br($phoneValue);
}

$title = $isEdit && $orderNumber ? 'Editar Pedido #' . $orderNumber : 'Novo Pedido';

// Configuração do header padronizado
$pageTitle = $title;
$pageDescription = $isEdit ? 'Atualize os dados do pedido pendente' : 'Crie um pedido manualmente para um cliente';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>';
$breadcrumbs = [
  ['label' => 'Pedidos', 'url' => base_url('admin/' . $slug . '/orders')],
  ['label' => $isEdit && $orderNumber ? 'Editar #' . $orderNumber : 'Novo']
];
$actions = [];

$paymentMethodTypeMap = [];
foreach (($paymentMethods ?? []) as $pm) {
  $rawType = $pm['type'] ?? 'others';
  $dataType = in_array($rawType, ['pix', 'cash', 'credit', 'debit', 'voucher'], true) ? $rawType : 'others';
  $paymentMethodTypeMap[(int)$pm['id']] = $dataType;
}

ob_start(); ?>

<style>
/* ===== Card Components (estilo mobile) ===== */
.order-section-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: visible;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.order-section-card__header {
    padding: 14px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    gap: 10px;
    border-radius: 16px 16px 0 0;
}

.order-section-card__header svg {
    width: 20px;
    height: 20px;
    color: var(--admin-primary-color);
    flex-shrink: 0;
}

.order-section-card__title {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
}

.order-section-card__body {
    padding: 20px;
    overflow: visible;
}

/* ===== Form Elements ===== */
.order-form-label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    font-size: 13px;
}

.order-form-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font-size: 14px;
    background: white;
    color: #1f2937;
    font-family: inherit;
    transition: all 0.2s;
    -webkit-appearance: none;
    appearance: none;
    box-sizing: border-box;
}

.order-form-input:focus {
    outline: none;
    border-color: var(--admin-primary-color);
    box-shadow: 0 0 0 2px rgba(91, 33, 182, 0.1);
}

.order-form-input::placeholder {
    color: #9ca3af;
}

.order-form-input.input-error {
    border-color: #dc2626;
    background: #fef2f2;
}

.order-form-group {
    margin-bottom: 16px;
}

.order-form-group:last-child {
    margin-bottom: 0;
}

.order-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

/* ===== Category Tabs ===== */
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
    transition: all 0.2s;
}

.category-tab:hover {
    background: #e5e7eb;
}

.category-tab.active {
    background: var(--admin-primary-color);
    border-color: var(--admin-primary-color);
    color: white;
}

/* ===== Product Items Grid ===== */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
    width: 100%;
}

.products-grid.input-error {
    border: 2px dashed #dc2626;
    border-radius: 12px;
    padding: 8px;
    background: #fef2f2;
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
    box-sizing: border-box;
    overflow: hidden;
}

.product-item:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}

.product-item.selected {
    background: rgba(91, 33, 182, 0.05);
    border-color: var(--admin-primary-color);
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
    color: var(--admin-primary-color);
}

.product-qty-controls {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}

.qty-btn {
    width: 30px;
    height: 30px;
    min-width: 30px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: white;
    color: #374151;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s;
}

.qty-btn:hover {
    background: #f3f4f6;
}

.qty-btn.primary {
    background: var(--admin-primary-color);
    border-color: var(--admin-primary-color);
    color: white;
}

.qty-btn.primary:hover {
    opacity: 0.9;
}

.qty-input-display {
    width: 32px;
    min-width: 32px;
    text-align: center;
    padding: 4px 2px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    flex-shrink: 0;
    background: white;
}

.empty-products-state {
    grid-column: 1 / -1;
    border: 2px dashed #e2e8f0;
    border-radius: 12px;
    padding: 32px 20px;
    text-align: center;
    background: #f8fafc;
}

.empty-products-state svg {
    width: 40px;
    height: 40px;
    margin: 0 auto 8px;
    color: #94a3b8;
}

.empty-products-state p {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.empty-products-state p small {
    font-size: 12px;
    color: #94a3b8;
}

/* ===== Sidebar Summary ===== */
.summary-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
}

.summary-line span {
    font-size: 14px;
    color: #64748b;
}

.summary-line strong {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.summary-total-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    margin-top: 10px;
    border-top: 2px solid #e2e8f0;
}

.summary-total-line span {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.summary-total-value {
    font-size: 22px;
    font-weight: 800;
    color: var(--admin-primary-color) !important;
}

.summary-fee-input {
    width: 110px;
    padding: 6px 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    text-align: right;
    font-weight: 600;
    color: #1e293b;
    background: white;
    box-sizing: border-box;
}

.summary-fee-input:focus {
    outline: none;
    border-color: var(--admin-primary-color);
    box-shadow: 0 0 0 2px rgba(91, 33, 182, 0.1);
}

/* ===== Payment Methods (checkout style) ===== */
.payment-methods-grid {
    display: grid;
    gap: 10px;
}

.payment-type-btn {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 15px;
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
    font-size: 15px;
    font-weight: 600;
}

.payment-type-btn .payment-subtitle {
    font-size: 12px;
    color: #64748b;
    font-weight: 400;
}

.payment-type-btn .arrow {
    width: 18px;
    height: 18px;
    opacity: 0.5;
    transition: transform 0.2s ease;
}

.payment-type-btn.active .arrow {
    transform: rotate(90deg);
    opacity: 1;
}

.card-brands {
    margin-top: 8px;
    display: none;
    grid-template-columns: repeat(auto-fit, minmax(75px, 1fr));
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
    padding: 10px 8px;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 4px;
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

.payment-methods-grid.input-error .payment-type-btn {
    border-color: #dc2626;
    background: #fef2f2;
}

/* ===== Submit Button ===== */
.btn-submit-order {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 14px 24px;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    color: white;
    background: var(--admin-primary-color);
    background-image: var(--admin-primary-gradient);
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.btn-submit-order:hover {
    opacity: 0.92;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.btn-submit-order svg {
    width: 20px;
    height: 20px;
}

/* ===== Cart Summary (in sidebar) ===== */
.cart-item-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: 13px;
}

.cart-item-line .cart-item-name {
    color: #374151;
    flex: 1;
    min-width: 0;
}

.cart-item-line .cart-item-qty {
    color: #6b7280;
    font-size: 12px;
    margin-left: 4px;
}

.cart-item-line .cart-item-price {
    font-weight: 600;
    color: #1e293b;
    margin-left: 12px;
    white-space: nowrap;
}

/* ===== Validation Toast ===== */
.validation-toast {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 10000;
    background: #dc2626;
    color: white;
    padding: 16px 24px;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    animation: vToastIn 0.3s ease;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.validation-toast svg { flex-shrink: 0; margin-top: 2px; }
.validation-toast-content { flex: 1; }
.validation-toast-title { font-weight: 600; margin-bottom: 4px; }
.validation-toast-items { font-size: 13px; opacity: 0.95; }
.validation-toast-close { background: none; border: none; color: white; font-size: 20px; cursor: pointer; padding: 0; line-height: 1; opacity: 0.8; }

@keyframes vToastIn {
    from { transform: translateY(-100%); }
    to { transform: translateY(0); }
}
@keyframes vToastOut {
    from { transform: translateY(0); }
    to { transform: translateY(-100%); }
}

/* ===== Customization Modal ===== */
.cmodal-overlay {
    position: fixed; inset: 0; z-index: 9000;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
    display: flex; align-items: flex-end; justify-content: center;
}
@media (min-width: 640px) {
    .cmodal-overlay { align-items: center; }
}
.cmodal-box {
    background: #fff;
    border-radius: 20px 20px 0 0;
    width: 100%; max-width: 480px;
    max-height: 92vh; display: flex; flex-direction: column;
    box-shadow: 0 -8px 32px rgba(0,0,0,0.18);
    font-family: Inter, system-ui, -apple-system, sans-serif;
}
@media (min-width: 640px) {
    .cmodal-box { border-radius: 20px; max-height: 82vh; }
}
.cmodal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px 14px; border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
}
.cmodal-title { font-size: 17px; font-weight: 700; color: #111827; letter-spacing: -.2px; }
.cmodal-close {
    width: 34px; height: 34px; border: 1.5px solid #e5e7eb; background: #f9fafb;
    border-radius: 50%; cursor: pointer; font-size: 20px; color: #6b7280;
    display: flex; align-items: center; justify-content: center; line-height: 1;
    transition: background .15s, color .15s;
}
.cmodal-close:hover { background: #f3f4f6; color: #111827; }
.cmodal-body { flex: 1; overflow-y: auto; }
.cmodal-footer {
    padding: 14px 20px; border-top: 1px solid #e5e7eb;
    display: flex; align-items: center; gap: 10px; flex-shrink: 0;
    background: #fff;
}
.cmodal-btn-cancel {
    flex: 0 0 auto; padding: 11px 18px; border-radius: 999px;
    border: 1.5px solid #e5e7eb; background: #fff; color: #374151;
    font-size: 14px; font-weight: 500; cursor: pointer;
    transition: background .15s;
}
.cmodal-btn-cancel:hover { background: #f9fafb; }
.cmodal-btn-confirm {
    flex: 1; padding: 12px 16px; border-radius: 999px;
    border: none; background: #fbbf24; color: #111827;
    font-size: 15px; font-weight: 700; cursor: pointer;
    transition: background .15s, transform .1s;
}
.cmodal-btn-confirm:hover { background: #f59e0b; }
.cmodal-btn-confirm:active { transform: scale(.98); }

/* Group heading */
.cgroup { padding: 0; }
.cgroup-heading {
    padding: 22px 20px 4px;
    font-size: 20px; font-weight: 800; color: #111827;
    letter-spacing: -.3px; line-height: 1.2;
}

/* Pool counter */
.cpool-counter {
    display: flex; align-items: center; gap: 4px;
    padding: 0 20px 10px;
    font-size: 14px; font-weight: 600; color: #6b7280;
}
.cpool-counter-num {
    font-size: 20px; font-weight: 800; color: #111827;
    transition: color .25s;
}
.cpool-counter.full .cpool-counter-num { color: #16a34a; }
.cpool-counter.extras .cpool-counter-num { color: #f59e0b; }
.cpool-extra-badge {
    font-size: 12px; color: #f59e0b; font-weight: 700;
    margin-left: 4px; display: none;
}
.cpool-counter.extras .cpool-extra-badge { display: inline; }

/* Item rows — mesmo estilo das linhas do cliente */
.crow {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 20px; border-top: 1px solid #e5e7eb;
}
.crow:first-of-type { border-top: none; }
.crow-thumb {
    width: 50px; height: 50px; min-width: 50px;
    border-radius: 50%; background: #f3f4f6;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; flex-shrink: 0;
}
.crow-thumb img { width: 100%; height: 100%; object-fit: cover; }
.crow-thumb-placeholder { color: #94a3b8; }
.crow-thumb-placeholder svg { width: 22px; height: 22px; }
.crow-info { flex: 1; min-width: 0; }
.crow-name { font-weight: 700; font-size: 14px; color: #111827; }
.crow-price { font-size: 13px; color: #374151; margin-top: 2px; }
.crow-pool-price { font-size: 12px; color: #6b7280; white-space: nowrap; margin-top: 2px; }
.crow-pool-price.charged { color: #f59e0b; font-weight: 600; }
.crow-pool-price.free { color: #16a34a; font-weight: 600; }

/* Stepper pílula */
.cstepper {
    display: flex; align-items: center; gap: 10px;
    border: 1.5px solid #e5e7eb; border-radius: 999px;
    padding: 5px 10px; min-width: 104px;
    justify-content: space-between;
    transition: border-color .2s;
}
.cstepper.shake { animation: cshake .4s ease; }
@keyframes cshake { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-4px)} 40%,80%{transform:translateX(4px)} }
.cst-btn {
    width: 28px; height: 28px; border-radius: 50%;
    background: #fff; border: none; cursor: pointer;
    font-size: 18px; font-weight: 600; color: #111827;
    display: flex; align-items: center; justify-content: center;
    transition: background .1s;
}
.cst-btn:disabled { opacity: .3; cursor: default; }
.cst-btn:not(:disabled):active { background: #f3f4f6; }
.cst-val { min-width: 16px; text-align: center; font-weight: 700; font-size: 14px; color: #111827; }

/* Radio selector (single) */
.crow-radio {
    width: 38px; height: 38px; min-width: 38px;
    border-radius: 50%; border: 2.5px solid #fbbf24;
    background: #fff; display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .2s, border-color .2s;
    flex-shrink: 0;
}
.crow-radio.sel { background: #fbbf24; border-color: #fbbf24; }
.crow-radio svg { display: none; width: 16px; height: 16px; }
.crow-radio.sel svg { display: block; }
.crow.radio-row { cursor: pointer; }
.crow.radio-row:hover { background: #fffbeb; }

/* Cart items in sidebar */
.cart-items-section {
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 20px 12px;
}
.cart-items-empty { font-size: 13px; color: #94a3b8; text-align: center; padding: 6px 0; }
.cart-item-row {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 7px 0; border-bottom: 1px solid #f1f5f9;
}
.cart-item-row:last-child { border-bottom: none; }
.cart-item-info { flex: 1; min-width: 0; }
.cart-item-info-name { font-size: 13px; color: #374151; font-weight: 500; }
.cart-item-info-custom { font-size: 11px; color: #6b7280; margin-top: 2px; line-height: 1.4; }
.cart-item-price-col { text-align: right; flex-shrink: 0; }
.cart-item-price-col .price { font-size: 13px; font-weight: 600; color: #1e293b; }
.cart-item-remove {
    flex-shrink: 0; padding: 2px 5px; background: none; border: none;
    color: #d1d5db; cursor: pointer; font-size: 16px; line-height: 1;
}
.cart-item-remove:hover { color: #ef4444; }

/* ===== Delivery Type Toggle ===== */
.delivery-toggle {
    display: flex;
    gap: 10px;
}

.delivery-option {
    flex: 1;
    padding: 14px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.delivery-option.selected {
    border-color: var(--admin-primary-color);
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
    color: var(--admin-primary-color);
}

.delivery-option-label {
    font-size: 13px;
    font-weight: 500;
    color: #374151;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
}

/* ===== Street Autocomplete ===== */
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
  max-height: 220px;
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

<div class="mx-auto max-w-7xl p-4 pb-24">

  <?php include __DIR__ . '/../components/page-header.php'; ?>

  <?php
    $formAction = $isEdit && !empty($order['id'])
      ? base_url('admin/' . $slug . '/orders/' . (int)$order['id'])
      : base_url('admin/' . $slug . '/orders');
  ?>
  <form method="post" action="<?= e($formAction) ?>" id="order-form" class="grid gap-6 lg:grid-cols-[1fr_380px]">
    
    <?php if (function_exists('csrf_field')): ?>
      <?= csrf_field() ?>
    <?php endif; ?>

    <!-- Hidden input para payment_method_id -->
    <input type="hidden" name="payment_method_id" id="paymentMethodIdInput" value="<?= e((string)($values['payment_method_id'] ?? '')) ?>">

    <!-- COLUNA PRINCIPAL -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
      
      <!-- CARD: Informações do Cliente -->
      <div class="order-section-card">
        <div class="order-section-card__header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          <span class="order-section-card__title">Dados do Cliente</span>
        </div>
        <div class="order-section-card__body">
          <!-- Cliente Existente -->
          <div class="order-form-group">
            <label class="order-form-label">Cliente cadastrado (opcional)</label>
            <select id="customer-select" class="order-form-input">
              <option value="">Ou preencha os dados manualmente...</option>
              <?php if (isset($customers)): foreach ($customers as $cust): ?>
                <option value="<?= (int)$cust['id'] ?>" 
                        data-name="<?= e($cust['name']) ?>" 
                        data-phone="<?= e(format_phone_br($cust['whatsapp'])) ?>">
                  <?= e($cust['name']) ?> <?= $cust['whatsapp'] ? '— ' . e(format_phone_br($cust['whatsapp'])) : '' ?>
                </option>
              <?php endforeach; endif; ?>
            </select>
          </div>

          <div class="order-form-row">
            <!-- Telefone -->
            <div class="order-form-group">
              <label class="order-form-label">WhatsApp *</label>
              <input type="tel" name="customer_phone" id="customer-phone"
                class="order-form-input" required placeholder="(51) 99999-0000" autocomplete="off"
                value="<?= e($phoneValue) ?>">
              <div id="customer-search-status" class="mt-1 text-xs" style="display: none;"></div>
            </div>

            <!-- Nome -->
            <div class="order-form-group">
              <label class="order-form-label">Nome completo *</label>
              <input type="text" name="customer_name" id="customer-name"
                class="order-form-input" required placeholder="Nome do cliente"
                value="<?= e((string)($values['customer_name'] ?? '')) ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- CARD: Tipo de Pedido -->
      <div class="order-section-card">
        <div class="order-section-card__header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="1" y="3" width="15" height="13"/>
            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
            <circle cx="5.5" cy="18.5" r="2.5"/>
            <circle cx="18.5" cy="18.5" r="2.5"/>
          </svg>
          <span class="order-section-card__title">Tipo de Pedido</span>
        </div>
        <div class="order-section-card__body">
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
        </div>
      </div>

      <!-- CARD: Endereço de Entrega -->
      <div class="order-section-card" id="address-card">
        <div class="order-section-card__header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
          <span class="order-section-card__title">Endereço de Entrega</span>
        </div>
        <div class="order-section-card__body">
          <div class="order-form-row">
            <!-- Cidade -->
            <div class="order-form-group">
              <label class="order-form-label">Cidade *</label>
              <select name="city_id" id="city-select" class="order-form-input" required>
                <option value="">Selecione a cidade</option>
                <?php if (isset($cities)): foreach ($cities as $city): ?>
                  <option value="<?= (int)$city['id'] ?>" <?= (int)($values['city_id'] ?? 0) === (int)$city['id'] ? 'selected' : '' ?>><?= e($city['name']) ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>

            <!-- Bairro -->
            <div class="order-form-group">
              <label class="order-form-label">Bairro *</label>
              <select name="zone_id" id="zone-select" class="order-form-input" required disabled>
                <option value="">Escolha a cidade primeiro</option>
              </select>
            </div>
          </div>

          <div class="order-form-row">
            <!-- Rua -->
            <div class="order-form-group" style="position:relative;">
              <label class="order-form-label">Rua / Avenida *</label>
              <input type="text" name="street" id="street-input"
                class="order-form-input" required placeholder="Nome da rua" autocomplete="off"
                value="<?= e((string)($values['street'] ?? '')) ?>">
              <div id="street-autocomplete-list" class="street-ac-list"></div>
            </div>

            <!-- Número -->
            <div class="order-form-group">
              <label class="order-form-label">Número *</label>
              <input type="text" name="number" id="number-input"
                class="order-form-input" required placeholder="123"
                value="<?= e((string)($values['number'] ?? '')) ?>">
            </div>
          </div>

          <!-- Complemento -->
          <div class="order-form-group">
            <label class="order-form-label">Complemento</label>
            <input type="text" name="complement" id="complement-input"
                   class="order-form-input" placeholder="Apto, bloco, casa..."
                   value="<?= e((string)($values['complement'] ?? '')) ?>">
          </div>
        </div>
      </div>

      <!-- CARD: Produtos -->
      <div class="order-section-card">
        <div class="order-section-card__header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
          <span class="order-section-card__title">Produtos</span>
        </div>
        <div class="order-section-card__body">
          <!-- Category Tabs -->
          <div class="category-tabs">
            <div class="category-tab active" data-category="all">Todos</div>
            <?php if (isset($categories)): foreach ($categories as $cat): ?>
              <div class="category-tab" data-category="<?= (int)$cat['id'] ?>">
                <?= e($cat['name']) ?>
              </div>
            <?php endforeach; endif; ?>
          </div>

          <!-- Products Grid -->
          <div class="products-grid" id="productsGrid">
            <?php if (empty($products)): ?>
              <div class="empty-products-state">
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
                $pp = (float)($prod['promo_price'] ?: $prod['price']);
              ?>
                <div class="product-item"
                     data-id="<?= (int)$prod['id'] ?>"
                     data-name="<?= e($prod['name']) ?>"
                     data-price="<?= $pp ?>"
                     data-category="<?= (int)($prod['category_id'] ?? 0) ?>">
                  <?php if (!empty($prod['image'])): ?>
                    <img src="/<?= e($prod['image']) ?>" class="product-image" alt="" loading="lazy">
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
                    <div class="product-name"><?= e($prod['name']) ?></div>
                    <div class="product-price">R$ <?= number_format($pp, 2, ',', '.') ?></div>
                  </div>
                  <div class="product-qty-controls">
                    <button type="button" class="qty-btn qty-minus">−</button>
                    <input type="number" class="qty-input-display" value="0" min="0" readonly>
                    <button type="button" class="qty-btn qty-plus primary">+</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Hidden inputs para itens do carrinho -->
          <div id="hiddenItemsContainer"></div>
        </div>
      </div>

      <!-- CARD: Observações -->
      <div class="order-section-card">
        <div class="order-section-card__header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <span class="order-section-card__title">Observações</span>
        </div>
        <div class="order-section-card__body">
          <textarea name="notes" rows="3" class="order-form-input"
                    placeholder="Ex.: Sem cebola, entregar no portão dos fundos, troco para R$ 50..."><?= e((string)($values['notes'] ?? '')) ?></textarea>
        </div>
      </div>

    </div>

    <!-- SIDEBAR: RESUMO + PAGAMENTO -->
    <div>
      <div class="order-section-card" style="position: sticky; top: 16px;">
        
        <!-- Resumo do Pedido -->
        <div class="order-section-card__header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="9" cy="21" r="1"/>
            <circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
          <span class="order-section-card__title">Resumo do Pedido</span>
        </div>

        <!-- Itens do carrinho -->
        <div class="cart-items-section">
          <div id="cart-items-list">
            <div class="cart-items-empty">Nenhum produto adicionado</div>
          </div>
        </div>

        <div class="order-section-card__body" style="border-bottom: 1px solid #e2e8f0;">
          <div class="summary-line">
            <span>Subtotal</span>
            <strong id="subtot-view">R$ 0,00</strong>
          </div>

          <div class="summary-line" style="align-items: center;">
            <span>Taxa de entrega <a href="/admin/<?= $slug ?>/guide/manual-order#summary" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda" style="flex-shrink:0">?</a></span>
                 <input type="number" step="0.01" name="delivery_fee" id="delivery-fee" value="<?= number_format((float)($values['delivery_fee'] ?? 0), 2, '.', '') ?>"
                   class="summary-fee-input">
          </div>

          <div class="summary-line" style="align-items: center;">
            <span>Desconto</span>
                 <input type="number" step="0.01" name="discount" id="discount" value="<?= number_format((float)($values['discount'] ?? 0), 2, '.', '') ?>"
                   class="summary-fee-input">
          </div>

          <div class="summary-total-line">
            <span>Total</span>
            <span id="total-view" class="summary-total-value">R$ 0,00</span>
          </div>
        </div>

        <!-- Forma de Pagamento -->
        <div style="padding: 16px 20px 6px;">
          <div class="order-section-card__header" style="padding: 0 0 12px; background: none; border-bottom: none; border-radius: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
              <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            <span class="order-section-card__title">Pagamento</span>
          </div>
        </div>
        <div style="padding: 0 20px 20px;">
          <?php
          // Agrupar métodos por tipo
          $pixMethods = [];
          $creditMethods = [];
          $debitMethods = [];
          $cashMethods = [];
          $voucherMethods = [];
          $otherMethods = [];
          
          if (isset($paymentMethods)) {
              foreach ($paymentMethods as $pm) {
                  $type = $pm['type'] ?? 'others';
                  switch ($type) {
                      case 'pix': $pixMethods[] = $pm; break;
                      case 'credit': $creditMethods[] = $pm; break;
                      case 'debit': $debitMethods[] = $pm; break;
                      case 'cash': $cashMethods[] = $pm; break;
                      case 'voucher': $voucherMethods[] = $pm; break;
                      default: $otherMethods[] = $pm; break;
                  }
              }
          }
          
          $creditNames = array_map(function($m) { return $m['name']; }, $creditMethods);
          $creditSubtitle = count($creditNames) > 0 ? implode(', ', array_slice($creditNames, 0, 3)) . (count($creditNames) > 3 ? ' e mais' : '') : '';
          
          $debitNames = array_map(function($m) { return $m['name']; }, $debitMethods);
          $debitSubtitle = count($debitNames) > 0 ? implode(', ', array_slice($debitNames, 0, 3)) . (count($debitNames) > 3 ? ' e mais' : '') : '';
          ?>
          
          <div class="payment-methods-grid" id="payment-methods-container">
            <?php if (empty($paymentMethods ?? [])): ?>
              <p style="color: #9ca3af; text-align: center; font-size: 14px; padding: 16px 0;">Nenhum método de pagamento cadastrado</p>
            <?php else: ?>
            
              <?php if ($pixMethods): ?>
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
              <!-- Bloco troco (aparece ao selecionar Dinheiro) -->
              <div id="cash-change-block" style="display:none; margin: 4px 0 8px 0; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                <div style="font-size:13px; font-weight:600; color:#374151; margin-bottom:8px;">Troco necessário?</div>
                <label style="display:flex; flex-direction:column; gap:4px;">
                  <span style="font-size:12px; color:#6b7280;">Troco para quanto? (deixe em branco se não precisar)</span>
                          <?php $cashAmountValue = $values['cash_amount'] ?? null; ?>
                          <input type="number" id="cash-change-input" name="cash_amount" placeholder="Ex: 50.00" step="0.01" min="0"
                            value="<?= $cashAmountValue !== null ? e(number_format((float)$cashAmountValue, 2, '.', '')) : '' ?>"
                         style="padding:8px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; width:100%;"
                         oninput="calcAdminChange()">
                </label>
                <div id="admin-change-info" style="display:none; margin-top:8px; padding:8px 10px; background:#f0fdf4; border-radius:8px; font-size:13px;">
                  <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                    <span style="color:#374151;">Total do pedido:</span>
                    <span id="admin-order-total-display">R$ 0,00</span>
                  </div>
                  <div style="display:flex; justify-content:space-between; font-weight:600;">
                    <span style="color:#374151;">Troco:</span>
                    <span id="admin-change-amount" style="color:#059669;">R$ 0,00</span>
                  </div>
                </div>
                <div id="admin-cash-error" style="display:none; color:#dc2626; font-size:12px; margin-top:4px;"></div>
              </div>
              <?php endif; ?>
              
              <?php if ($creditMethods): ?>
              <div class="payment-type-btn" data-type="credit" onclick="selectPaymentType('credit')">
                <input type="radio" name="payment_method" value="credit">
                <div class="payment-info">
                  <img src="<?= base_url('assets/card-brands/credit.svg') ?>" alt="Crédito" class="payment-icon">
                  <div class="payment-text">
                    <div class="payment-title">Cartão de crédito</div>
                    <div class="payment-subtitle"><?= e($creditSubtitle) ?></div>
                  </div>
                </div>
                <svg class="arrow" viewBox="0 0 24 24" fill="none">
                  <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div class="card-brands" id="credit-brands">
                <?php foreach ($creditMethods as $method): 
                    $methodName = $method['name'] ?? 'Cartão';
                    $nameLower = strtolower($methodName);
                    $brandMapping = function_exists('payment_card_brand_filenames') ? payment_card_brand_filenames() : [];
                    $icon = 'credit.svg';
                    foreach ($brandMapping as $keyword => $file) {
                        if (strpos($nameLower, $keyword) !== false) { $icon = $file; break; }
                    }
                ?>
                <div class="brand-btn" data-method-id="<?= $method['id'] ?>" onclick="selectCardBrand('credit', <?= $method['id'] ?>)">
                  <img src="<?= base_url('assets/card-brands/' . $icon) ?>" alt="<?= e($methodName) ?>" onerror="this.src='<?= base_url('assets/card-brands/credit.svg') ?>'">
                  <span><?= e($methodName) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              
              <?php if ($debitMethods): ?>
              <div class="payment-type-btn" data-type="debit" onclick="selectPaymentType('debit')">
                <input type="radio" name="payment_method" value="debit">
                <div class="payment-info">
                  <img src="<?= base_url('assets/card-brands/debit.svg') ?>" alt="Débito" class="payment-icon">
                  <div class="payment-text">
                    <div class="payment-title">Cartão de débito</div>
                    <div class="payment-subtitle"><?= e($debitSubtitle) ?></div>
                  </div>
                </div>
                <svg class="arrow" viewBox="0 0 24 24" fill="none">
                  <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div class="card-brands" id="debit-brands">
                <?php foreach ($debitMethods as $method): 
                    $methodName = $method['name'] ?? 'Débito';
                    $nameLower = strtolower($methodName);
                    $brandMapping = function_exists('payment_card_brand_filenames') ? payment_card_brand_filenames() : [];
                    $icon = 'debit.svg';
                    foreach ($brandMapping as $keyword => $file) {
                        if (strpos($nameLower, $keyword) !== false) { $icon = $file; break; }
                    }
                ?>
                <div class="brand-btn" data-method-id="<?= $method['id'] ?>" onclick="selectCardBrand('debit', <?= $method['id'] ?>)">
                  <img src="<?= base_url('assets/card-brands/' . $icon) ?>" alt="<?= e($methodName) ?>" onerror="this.src='<?= base_url('assets/card-brands/debit.svg') ?>'">
                  <span><?= e($methodName) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              
              <?php if ($voucherMethods): ?>
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
              <?php foreach ($otherMethods as $pm): ?>
              <div class="payment-type-btn" data-type="others" data-method-id="<?= $pm['id'] ?>" onclick="selectPaymentType('others', <?= $pm['id'] ?>)">
                <input type="radio" name="payment_method" value="<?= e($pm['type']) ?>">
                <div class="payment-info">
                  <img src="<?= base_url('assets/card-brands/others.svg') ?>" alt="Outros" class="payment-icon">
                  <div class="payment-text">
                    <div class="payment-title"><?= e($pm['name']) ?></div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
              
            <?php endif; ?>
          </div>
        </div>

        <!-- Botão Criar Pedido -->
        <div style="padding: 0 20px 20px;">
          <button type="submit" class="btn-submit-order">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M20 7 9 18l-5-5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?= $isEdit ? 'Atualizar Pedido' : 'Criar Pedido' ?>
          </button>
        </div>
      </div>
    </div>

  </form>

</div>

<!-- Modal de Personalização -->
<div id="customization-modal" class="cmodal-overlay" style="display:none;" onclick="if(event.target===this)closeCustomizationModal()">
  <div class="cmodal-box">
    <div class="cmodal-header">
      <div class="cmodal-title" id="cmodal-title">Personalizar</div>
      <button type="button" class="cmodal-close" onclick="closeCustomizationModal()">×</button>
    </div>
    <div class="cmodal-body" id="cmodal-body"></div>
    <div class="cmodal-footer">
      <button type="button" class="cmodal-btn-cancel" onclick="closeCustomizationModal()">Cancelar</button>
      <div class="cmodal-price-preview" id="cmodal-price-preview"></div>
      <button type="button" class="cmodal-btn-confirm" id="cmodal-btn-confirm" onclick="confirmCustomization()">Adicionar</button>
    </div>
  </div>
</div>

<script>
(() => {
  const zonesByCity = <?= json_encode($zonesByCity ?? [], JSON_UNESCAPED_UNICODE) ?>;
  const adminSlug = <?= json_encode($slug) ?>;
  const initialOrder = <?= json_encode(['items' => $initialItems ?? []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const initialCityId = <?= json_encode($values['city_id'] ?? null) ?>;
  const initialZoneId = <?= json_encode($values['zone_id'] ?? null) ?>;
  const initialDeliveryType = <?= json_encode($deliveryType ?? 'delivery') ?>;
  const initialPaymentMethodId = <?= json_encode($values['payment_method_id'] ?? null) ?>;
  const paymentMethodTypeMap = <?= json_encode($paymentMethodTypeMap ?? [], JSON_UNESCAPED_UNICODE) ?>;
  
  // === Elementos ===
  const phoneInput = document.getElementById('customer-phone');
  const searchStatus = document.getElementById('customer-search-status');
  const citySelect = document.getElementById('city-select');
  const zoneSelect = document.getElementById('zone-select');
  const deliveryFeeInput = document.getElementById('delivery-fee');
  const paymentMethodIdInput = document.getElementById('paymentMethodIdInput');
  let searchTimeout = null;
  
  // === Delivery Type Toggle ===
  const deliveryOptions = document.querySelectorAll('.delivery-option');
  const addressCard = document.getElementById('address-card');
  const addressRequiredFields = addressCard ? addressCard.querySelectorAll('[required]') : [];
  
  function setAddressRequired(required) {
    addressRequiredFields.forEach(el => {
      if (required) {
        el.setAttribute('required', '');
      } else {
        el.removeAttribute('required');
      }
    });
  }
  
  deliveryOptions.forEach(opt => {
    opt.addEventListener('click', function() {
      deliveryOptions.forEach(o => o.classList.remove('selected'));
      this.classList.add('selected');
      this.querySelector('input').checked = true;
      
      if (this.querySelector('input').value === 'pickup') {
        addressCard.style.display = 'none';
        setAddressRequired(false);
        // Zerar taxa de entrega ao selecionar retirada
        if (deliveryFeeInput) deliveryFeeInput.value = '0.00';
        recalc();
      } else {
        addressCard.style.display = '';
        setAddressRequired(true);
      }
    });
  });

  if (addressCard && initialDeliveryType === 'pickup') {
    addressCard.style.display = 'none';
    setAddressRequired(false);
  }
  
  function isPickupMode() {
    const checked = document.querySelector('input[name="delivery_type"]:checked');
    return checked && checked.value === 'pickup';
  }
  
  // === Strip DDI 55 se presente ===
  const stripDDI = (digits) => {
    if (digits.length >= 12 && digits.startsWith('55')) {
      return digits.substring(2);
    }
    return digits;
  };

  // === Máscara de Telefone ===
  const applyPhoneMask = (value) => {
    let digits = value.replace(/\D/g, '');
    digits = stripDDI(digits);
    const limited = digits.substring(0, 11);
    let formatted = '';
    if (limited.length > 0) {
      formatted = '(' + limited.substring(0, 2);
      if (limited.length > 2) formatted += ') ' + limited.substring(2, 7);
      if (limited.length > 7) formatted += '-' + limited.substring(7, 11);
    }
    return formatted;
  };
  
  // === Buscar cliente por telefone ===
  async function searchCustomerByPhone(phone) {
    searchStatus.style.display = 'block';
    searchStatus.innerHTML = '<span style="color: #64748b;">Buscando cliente...</span>';
    
    try {
      const response = await fetch('/admin/' + adminSlug + '/api/customers/search?phone=' + encodeURIComponent(phone));
      const result = await response.json();
      
      if (result.success && result.data.found) {
        const customer = result.data.customer;
        document.getElementById('customer-name').value = customer.name || '';
        
        if (customer.address) {
          const streetInput = document.getElementById('street-input');
          const numberInput = document.getElementById('number-input');
          const complementInput = document.getElementById('complement-input');
          
          if (streetInput) streetInput.value = customer.address.street || '';
          if (numberInput) numberInput.value = customer.address.number || '';
          if (complementInput) complementInput.value = customer.address.complement || '';
          
          // Preencher cidade e bairro
          if (customer.address.city_id && citySelect) {
            citySelect.value = customer.address.city_id;
            citySelect.dispatchEvent(new Event('change'));
            
            setTimeout(() => {
              if (customer.address.zone_id && zoneSelect) {
                zoneSelect.value = customer.address.zone_id;
                zoneSelect.dispatchEvent(new Event('change'));
              }
            }, 100);
          }
          
          // Preencher taxa de entrega
          if (customer.address.delivery_fee !== undefined && deliveryFeeInput) {
            deliveryFeeInput.value = customer.address.delivery_fee.toFixed(2);
            recalc();
          }
        }
        
        searchStatus.innerHTML = '<span style="color: #10b981;">✓ Cliente encontrado!</span>';
        setTimeout(() => { searchStatus.style.display = 'none'; }, 2000);
      } else {
        searchStatus.innerHTML = '<span style="color: #64748b;">Novo cliente</span>';
        setTimeout(() => { searchStatus.style.display = 'none'; }, 2000);
      }
    } catch (error) {
      console.error('Erro ao buscar cliente:', error);
      searchStatus.style.display = 'none';
    }
  }
  
  if (phoneInput) {
    phoneInput.addEventListener('input', (e) => {
      e.target.value = applyPhoneMask(e.target.value);
      
      const numbers = e.target.value.replace(/\D/g, '');
      if (numbers.length >= 10) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => searchCustomerByPhone(numbers), 500);
      } else if (searchStatus) {
        searchStatus.style.display = 'none';
      }
    });
  }

  // === Selecionar cliente cadastrado ===
  const customerSelect = document.getElementById('customer-select');
  const customerName = document.getElementById('customer-name');

  if (customerSelect) {
    customerSelect.addEventListener('change', (e) => {
      const selected = e.target.options[e.target.selectedIndex];
      if (selected.value) {
        customerName.value = selected.dataset.name || '';
        const phone = selected.dataset.phone || '';
        if (phoneInput && phone) {
          phoneInput.value = applyPhoneMask(phone);
        }
      } else {
        customerName.value = '';
        if (phoneInput) phoneInput.value = '';
      }
    });
  }

  // === City/Zone dinâmico ===
  if (citySelect && zoneSelect) {
    citySelect.addEventListener('change', () => {
      const cityId = parseInt(citySelect.value) || 0;
      const zones = zonesByCity[cityId] || zonesByCity[String(cityId)] || [];
      
      zoneSelect.innerHTML = '<option value="">Selecione o bairro</option>';
      
      if (zones.length === 0) {
        zoneSelect.disabled = true;
        return;
      }
      
      zones.forEach(zone => {
        const option = document.createElement('option');
        option.value = zone.id;
        option.textContent = zone.neighborhood;
        option.dataset.fee = zone.fee;
        zoneSelect.appendChild(option);
      });
      
      zoneSelect.disabled = false;
      citySelect.classList.remove('input-error');
    });

    zoneSelect.addEventListener('change', () => {
      const selected = zoneSelect.options[zoneSelect.selectedIndex];
      if (selected && selected.dataset.fee) {
        deliveryFeeInput.value = parseFloat(selected.dataset.fee).toFixed(2);
        recalc();
      }
      zoneSelect.classList.remove('input-error');
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

  // === Carrinho de produtos ===
  // productCustomizations: map of product_id -> groups (from PHP)
  const productCustomizations = <?= json_encode($customizationMap ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  const cart = {};            // non-customizable items, keyed by product_id
  const customCart = [];      // customizable items: [{key, productId, name, basePrice, totalPrice, qty, customizationData, summary}]
  const productsGrid = document.getElementById('productsGrid');
  const hiddenItemsContainer = document.getElementById('hiddenItemsContainer');
  const cartItemsList = document.getElementById('cart-items-list');

  function fmtBrl(v) { return 'R$ ' + v.toFixed(2).replace('.', ','); }

  // Category tabs filter
  document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const cat = tab.dataset.category;
      document.querySelectorAll('.product-item').forEach(item => {
        if (cat === 'all' || item.dataset.category === cat) {
          item.style.display = '';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });

  // +/- buttons on each product
  document.querySelectorAll('.product-item').forEach(item => {
    const id = item.dataset.id;
    const name = item.dataset.name;
    const price = parseFloat(item.dataset.price) || 0;
    const qtyDisplay = item.querySelector('.qty-input-display');
    const btnMinus = item.querySelector('.qty-minus');
    const btnPlus = item.querySelector('.qty-plus');
    const hasCustom = !!(productCustomizations[id] && productCustomizations[id].length > 0);

    btnPlus.addEventListener('click', (e) => {
      e.stopPropagation();
      if (hasCustom) {
        openCustomizationModal(id, name, price);
      } else {
        if (!cart[id]) cart[id] = { id, name, price, qty: 0 };
        cart[id].qty++;
        qtyDisplay.value = cart[id].qty;
        item.classList.add('selected');
        updateCartDisplay();
        recalc();
      }
      if (productsGrid) productsGrid.classList.remove('input-error');
    });

    btnMinus.addEventListener('click', (e) => {
      e.stopPropagation();
      if (hasCustom) {
        // Remove the last customCart entry for this product
        const idx = customCart.map(c => String(c.productId)).lastIndexOf(String(id));
        if (idx !== -1) {
          customCart.splice(idx, 1);
          const total = customCart.filter(c => String(c.productId) === String(id)).reduce((s, c) => s + c.qty, 0);
          qtyDisplay.value = total;
          if (total === 0) item.classList.remove('selected');
          updateCartDisplay();
          recalc();
        }
      } else {
        if (cart[id] && cart[id].qty > 0) {
          cart[id].qty--;
          if (cart[id].qty <= 0) {
            delete cart[id];
            qtyDisplay.value = 0;
            item.classList.remove('selected');
          } else {
            qtyDisplay.value = cart[id].qty;
          }
          updateCartDisplay();
          recalc();
        }
      }
    });
  });

  function removeCustomCartItem(key) {
    const idx = customCart.findIndex(c => c.key === key);
    if (idx === -1) return;
    const productId = customCart[idx].productId;
    customCart.splice(idx, 1);
    // Update qty display in product grid
    const gridItem = document.querySelector('.product-item[data-id="' + productId + '"]');
    if (gridItem) {
      const total = customCart.filter(c => String(c.productId) === String(productId)).reduce((s, c) => s + c.qty, 0);
      const qd = gridItem.querySelector('.qty-input-display');
      if (qd) qd.value = total;
      if (total === 0) gridItem.classList.remove('selected');
    }
    updateCartDisplay();
    recalc();
  }
  window.removeCustomCartItem = removeCustomCartItem;

  // Update hidden inputs + cart items list
  function updateCartDisplay() {
    let html = '';
    // Non-customizable items
    Object.values(cart).forEach(item => {
      if (item.qty > 0) {
        html += '<input type="hidden" name="product_id[]" value="' + item.id + '">';
        html += '<input type="hidden" name="quantity[]" value="' + item.qty + '">';
        html += '<input type="hidden" name="customization_data_json[]" value="">';
      }
    });
    // Customizable items (one entry per customCart item, qty=1 each)
    customCart.forEach(ci => {
      html += '<input type="hidden" name="product_id[]" value="' + ci.productId + '">';
      html += '<input type="hidden" name="quantity[]" value="' + ci.qty + '">';
      html += '<input type="hidden" name="customization_data_json[]" value="' + escHtml(JSON.stringify(ci.customizationData)) + '">';
    });
    hiddenItemsContainer.innerHTML = html;

    // Update cart items list in sidebar
    const allItems = [];
    Object.values(cart).forEach(item => {
      if (item.qty > 0) allItems.push({ key: null, name: item.name, qty: item.qty, price: item.price, summary: '' });
    });
    customCart.forEach(ci => {
      allItems.push({ key: ci.key, name: ci.name, qty: ci.qty, price: ci.totalPrice, summary: ci.summary });
    });

    if (allItems.length === 0) {
      cartItemsList.innerHTML = '<div class="cart-items-empty">Nenhum produto adicionado</div>';
      return;
    }

    cartItemsList.innerHTML = allItems.map(it => {
      const removeBtn = it.key
        ? '<button type="button" class="cart-item-remove" onclick="removeCustomCartItem(\'' + escHtml(it.key) + '\')" title="Remover">×</button>'
        : '';
      const customHtml = it.summary
        ? '<div class="cart-item-info-custom">' + escHtml(it.summary) + '</div>'
        : '';
      return '<div class="cart-item-row">' +
        '<div class="cart-item-info">' +
          '<div class="cart-item-info-name">' + escHtml(it.name) + ' <span style="color:#94a3b8;font-size:11px;">×' + it.qty + '</span></div>' +
          customHtml +
        '</div>' +
        '<div class="cart-item-price-col"><div class="price">' + fmtBrl(it.price * it.qty) + '</div></div>' +
        removeBtn +
      '</div>';
    }).join('');
  }

  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  // === Recalcular totais ===
  function recalc() {
    let subtotal = 0;
    Object.values(cart).forEach(item => { subtotal += item.price * item.qty; });
    customCart.forEach(ci => { subtotal += ci.totalPrice * ci.qty; });

    const fee = parseFloat(deliveryFeeInput.value) || 0;
    const disc = parseFloat(document.getElementById('discount').value) || 0;
    const total = Math.max(0, subtotal + fee - disc);

    document.getElementById('subtot-view').textContent = fmtBrl(subtotal);
    document.getElementById('total-view').textContent = fmtBrl(total);
  }

  document.getElementById('delivery-fee').addEventListener('input', recalc);
  document.getElementById('discount').addEventListener('input', recalc);

  if (initialOrder && Array.isArray(initialOrder.items) && initialOrder.items.length > 0) {
    initialOrder.items.forEach(it => {
      const productId = String(it.product_id || '');
      if (!productId) return;
      const qty = parseInt(it.quantity, 10) || 0;
      if (qty <= 0) return;

      const productEl = document.querySelector('.product-item[data-id="' + productId + '"]');
      const productName = it.product_name || (productEl ? productEl.dataset.name : 'Produto');
      const unitPrice = parseFloat(it.unit_price) || 0;
      const customData = it.customization_data || null;
      const hasCustom = !!(productCustomizations[productId] && productCustomizations[productId].length > 0);
      const isCustom = hasCustom || (customData && Object.keys(customData).length > 0);

      if (isCustom) {
        for (let i = 0; i < qty; i++) {
          const key = 'c_' + productId + '_' + Date.now() + '_' + i;
          customCart.push({
            key,
            productId,
            name: productName,
            basePrice: unitPrice,
            totalPrice: unitPrice,
            qty: 1,
            customizationData: customData,
            summary: buildCustomSummary(customData),
          });
        }
      } else {
        if (!cart[productId]) {
          cart[productId] = { id: productId, name: productName, price: unitPrice, qty: 0 };
        }
        cart[productId].qty += qty;
      }
    });

    document.querySelectorAll('.product-item').forEach(item => {
      const id = item.dataset.id;
      const qd = item.querySelector('.qty-input-display');
      if (!qd) return;
      const customQty = customCart.filter(c => String(c.productId) === String(id)).reduce((s, c) => s + c.qty, 0);
      const baseQty = cart[id] ? cart[id].qty : 0;
      const totalQty = customQty + baseQty;
      qd.value = totalQty;
      if (totalQty > 0) item.classList.add('selected');
    });

    updateCartDisplay();
    recalc();
  }

  // ================================================================
  // === Modal de Personalização ===
  // ================================================================
  let modalProductId = null;
  let modalProductName = '';
  let modalBasePrice = 0;
  let modalGroups = [];   // groups from productCustomizations, with runtime .state

  function openCustomizationModal(productId, productName, basePrice) {
    modalProductId = productId;
    modalProductName = productName;
    modalBasePrice = basePrice;

    const rawGroups = productCustomizations[productId] || [];
    // Deep-clone and attach runtime state
    modalGroups = rawGroups.map((g, gi) => {
      const group = JSON.parse(JSON.stringify(g));
      group._gi = gi;
      if (group.type === 'pool') {
        group._poolTotal = 0;
        group.items.forEach(it => { it._qty = 0; });
      } else if (group.type === 'single') {
        group._selectedIdx = -1;
        // Pre-select default if any
        group.items.forEach((it, idx) => {
          if (it.selected || it.default) group._selectedIdx = idx;
        });
      } else {
        // extra / other — steppers
        group.items.forEach(it => {
          it._qty = it.qty !== undefined ? it.qty : (it.default_qty || 0);
        });
      }
      return group;
    });

    document.getElementById('cmodal-title').textContent = 'Personalizar: ' + productName;
    renderModalBody();
    updateModalPrice();
    document.getElementById('customization-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeCustomizationModal() {
    document.getElementById('customization-modal').style.display = 'none';
    document.body.style.overflow = '';
    modalProductId = null;
    modalGroups = [];
  }
  window.closeCustomizationModal = closeCustomizationModal;

  function renderModalBody() {
    const body = document.getElementById('cmodal-body');
    body.innerHTML = modalGroups.map((g, gi) => renderGroup(g, gi)).join('');
    // Attach events via delegation on cst-btn (pool & extra)
    body.querySelectorAll('.cst-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const gi = parseInt(btn.dataset.gi);
        const ii = parseInt(btn.dataset.ii);
        const delta = btn.dataset.dir === 'plus' ? 1 : -1;
        const g = modalGroups[gi];
        if (g.type === 'pool') handlePoolChange(gi, ii, delta);
        else handleExtraChange(gi, ii, delta);
      });
    });
    // Single radio rows
    body.querySelectorAll('.crow.radio-row').forEach(el => {
      el.addEventListener('click', () => {
        const gi = parseInt(el.dataset.gi);
        const idx = parseInt(el.dataset.ii);
        handleSingleSelect(gi, idx);
      });
    });
  }

  function makeThumb(imgPath) {
    const svgPlaceholder =
      '<svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    if (!imgPath) {
      return '<div class="crow-thumb">' + svgPlaceholder + '</div>';
    }
    // Garante path absoluto para não herdar prefixo da rota atual
    const absPath = /^https?:\/\//.test(imgPath) ? imgPath : '/' + imgPath.replace(/^\/+/, '');
    const safeUrl = encodeURI(absPath);
    return '<div class="crow-thumb" style="background:url(' + safeUrl + ') center/cover no-repeat #f3f4f6"></div>';
  }

  function renderGroup(g, gi) {
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

      itemsHtml = g.items.map((it, ii) => {
        return '<div class="crow">' +
          makeThumb(it.image_path || it.img || null) +
          '<div class="crow-info">' +
            '<div class="crow-name">' + escHtml(it.name || it.label || '') + '</div>' +
            '<div class="crow-pool-price" id="pprice-' + gi + '-' + ii + '"></div>' +
          '</div>' +
          '<div class="cstepper">' +
            '<button type="button" class="cst-btn" data-gi="' + gi + '" data-ii="' + ii + '" data-dir="minus" disabled>−</button>' +
            '<span class="cst-val" id="cst-val-' + gi + '-' + ii + '">0</span>' +
            '<button type="button" class="cst-btn" data-gi="' + gi + '" data-ii="' + ii + '" data-dir="plus">+</button>' +
          '</div>' +
        '</div>';
      }).join('');

    } else if (g.type === 'single') {
      const choiceLabel = g.max === 1 ? 'Escolha 1 opção' : 'Escolha até ' + g.max;
      headingExtra = '<div style="padding:2px 20px 12px;font-size:13px;color:#6b7280">' + choiceLabel + '</div>';

      itemsHtml = g.items.map((it, ii) => {
        const delta = it.delta || it.sale_price || 0;
        const priceHtml = delta > 0 ? '<div class="crow-price">+' + fmtBrl(delta) + '</div>' : '';
        const isSel = g._selectedIdx === ii;
        const radioHtml = '<div class="crow-radio' + (isSel ? ' sel' : '') + '" id="cradio-' + gi + '-' + ii + '">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
          '</div>';
        return '<div class="crow radio-row" data-gi="' + gi + '" data-ii="' + ii + '">' +
          makeThumb(it.image_path || it.img || null) +
          '<div class="crow-info">' +
            '<div class="crow-name">' + escHtml(it.name || it.label || '') + '</div>' +
            priceHtml +
          '</div>' +
          radioHtml +
        '</div>';
      }).join('');

    } else {
      // extra / addon — steppers
      itemsHtml = g.items.map((it, ii) => {
        const sale = it.sale_price || it.delta || 0;
        const priceHtml = sale > 0 ? '<div class="crow-price">' + fmtBrl(sale) + '</div>' : '';
        const qty = it._qty || 0;
        const minQty = it.min !== undefined ? it.min : (it.min_qty || 0);
        return '<div class="crow">' +
          makeThumb(it.image_path || it.img || null) +
          '<div class="crow-info">' +
            '<div class="crow-name">' + escHtml(it.name || it.label || '') + '</div>' +
            priceHtml +
          '</div>' +
          '<div class="cstepper">' +
            '<button type="button" class="cst-btn" data-gi="' + gi + '" data-ii="' + ii + '" data-dir="minus"' + (qty <= minQty ? ' disabled' : '') + '>−</button>' +
            '<span class="cst-val" id="cst-val-' + gi + '-' + ii + '">' + qty + '</span>' +
            '<button type="button" class="cst-btn" data-gi="' + gi + '" data-ii="' + ii + '" data-dir="plus">+</button>' +
          '</div>' +
        '</div>';
      }).join('');
    }

    return '<div class="cgroup">' +
      '<div class="cgroup-heading">' + escHtml(g.name) + (g.min > 0 ? ' <span style="color:#ef4444;font-size:14px">*</span>' : '') + '</div>' +
      headingExtra +
      counterHtml +
      '<div>' + itemsHtml + '</div>' +
    '</div>';
  }

  function handlePoolChange(gi, ii, delta) {
    const g = modalGroups[gi];
    const it = g.items[ii];
    const newQty = (it._qty || 0) + delta;
    if (newQty < 0) return;
    it._qty = newQty;
    g._poolTotal = g.items.reduce((s, x) => s + (x._qty || 0), 0);
    const poolFree = g.pool_free || g.max || 4;
    const extras = Math.max(0, g._poolTotal - poolFree);
    const freeUsed = Math.min(g._poolTotal, poolFree);
    const isAtCapacity = g._poolTotal >= poolFree;

    // counter number (mostra só os que cabem na cota, igual ao cliente)
    const numEl = document.getElementById('cpool-num-' + gi);
    if (numEl) numEl.textContent = freeUsed;
    const cEl = document.getElementById('cpool-counter-' + gi);
    if (cEl) {
      cEl.classList.toggle('full', isAtCapacity && extras === 0);
      cEl.classList.toggle('extras', extras > 0);
    }
    const badgeEl = document.getElementById('cpool-extra-badge-' + gi);
    if (badgeEl) {
      badgeEl.textContent = extras > 0 ? '+' + extras + ' extra' + (extras > 1 ? 's' : '') : '';
    }

    // qty display + min button
    const valEl = document.getElementById('cst-val-' + gi + '-' + ii);
    if (valEl) valEl.textContent = it._qty;
    const minusBtn = document.querySelector('.cst-btn[data-gi="' + gi + '"][data-ii="' + ii + '"][data-dir="minus"]');
    if (minusBtn) minusBtn.disabled = it._qty <= 0;

    // Pool price label per item — idêntico ao cliente
    let freeRemaining = poolFree;
    g.items.forEach((item, idx) => {
      const qty = item._qty || 0;
      const free = Math.min(qty, freeRemaining);
      const paid = qty - free;
      freeRemaining -= free;
      const unitPrice = item.sale_price || item.extra_price || 0;
      const pEl = document.getElementById('pprice-' + gi + '-' + idx);
      if (!pEl) return;
      if (qty === 0) {
        if (isAtCapacity && unitPrice > 0) {
          pEl.textContent = 'R$ ' + unitPrice.toFixed(2).replace('.', ',');
          pEl.className = 'crow-pool-price';
        } else {
          pEl.textContent = '';
          pEl.className = 'crow-pool-price';
        }
        return;
      }
      if (paid > 0) {
        pEl.textContent = 'R$ ' + (paid * unitPrice).toFixed(2).replace('.', ',') + ' · extra';
        pEl.className = 'crow-pool-price charged';
      } else {
        pEl.textContent = (isAtCapacity && unitPrice > 0)
          ? 'Incluso · R$ ' + unitPrice.toFixed(2).replace('.', ',')
          : 'Incluso';
        pEl.className = 'crow-pool-price free';
      }
    });

    updateModalPrice();
  }

  function handleSingleSelect(gi, idx) {
    const g = modalGroups[gi];
    g._selectedIdx = idx;
    // Update radio DOM
    g.items.forEach((_, i) => {
      const el = document.getElementById('cradio-' + gi + '-' + i);
      if (el) el.className = 'crow-radio' + (i === idx ? ' sel' : '');
    });
    updateModalPrice();
  }

  function handleExtraChange(gi, ii, delta) {
    const g = modalGroups[gi];
    const it = g.items[ii];
    const minQty = it.min !== undefined ? it.min : (it.min_qty || 0);
    const newQty = (it._qty || 0) + delta;
    if (newQty < minQty) return;
    it._qty = newQty;
    const qEl = document.getElementById('cst-val-' + gi + '-' + ii);
    if (qEl) qEl.textContent = it._qty;
    const minusBtn = document.querySelector('.cst-btn[data-gi="' + gi + '"][data-ii="' + ii + '"][data-dir="minus"]');
    if (minusBtn) minusBtn.disabled = it._qty <= minQty;
    updateModalPrice();
  }

  function calcModalDelta() {
    let delta = 0;
    modalGroups.forEach(g => {
      if (g.type === 'pool') {
        const poolFree = g.pool_free || g.max || 4;
        let freeRemaining = poolFree;
        g.items.forEach(it => {
          const qty = it._qty || 0;
          const free = Math.min(qty, freeRemaining);
          const paid = qty - free;
          freeRemaining -= free;
          delta += paid * (it.sale_price || 0);
        });
      } else if (g.type === 'single') {
        if (g._selectedIdx >= 0 && g._selectedIdx < g.items.length) {
          const it = g.items[g._selectedIdx];
          delta += it.delta || it.sale_price || 0;
        }
      } else {
        g.items.forEach(it => {
          const defQty = it.default_qty || 0;
          const qty = it._qty || 0;
          const extraQty = Math.max(0, qty - defQty);
          delta += extraQty * (it.delta || it.sale_price || it.sale_price || 0);
        });
      }
    });
    return delta;
  }

  function updateModalPrice() {
    const delta = calcModalDelta();
    const total = modalBasePrice + delta;
    const priceEl = document.getElementById('cmodal-price-preview');
    if (priceEl) {
      priceEl.textContent = delta > 0 ? '+' + fmtBrl(delta) + ' extra' : '';
    }
    const confirmBtn = document.getElementById('cmodal-btn-confirm');
    if (confirmBtn) {
      confirmBtn.textContent = 'Adicionar — ' + fmtBrl(total);
    }
  }

  function buildCustomizationData() {
    const groups = [];
    let totalDelta = 0;

    modalGroups.forEach(g => {
      const groupItems = [];

      if (g.type === 'pool') {
        const poolFree = g.pool_free || g.max || 4;
        let freeRemaining = poolFree;
        g.items.forEach(it => {
          const qty = it._qty || 0;
          if (qty <= 0) return;
          const free = Math.min(qty, freeRemaining);
          const paid = qty - free;
          freeRemaining -= free;
          const price = paid * (it.sale_price || 0);
          totalDelta += price;
          groupItems.push({
            name: it.name || it.label || '',
            qty: qty,
            unit_price: it.sale_price || 0,
            price: price,
            free_qty: free,
            paid_qty: paid,
          });
        });
        if (groupItems.length > 0) {
          groups.push({ name: g.name, type: 'pool', items: groupItems });
        }
      } else if (g.type === 'single') {
        if (g._selectedIdx >= 0 && g._selectedIdx < g.items.length) {
          const it = g.items[g._selectedIdx];
          const delta = it.delta || 0;
          totalDelta += delta;
          groupItems.push({
            name: it.name || it.label || '',
            qty: 1,
            unit_price: delta,
            price: delta,
            delta_qty: 0,
            default_qty: 1,
          });
          groups.push({ name: g.name, type: 'single', items: groupItems });
        }
      } else {
        // extra
        g.items.forEach(it => {
          const qty = it._qty || 0;
          const defQty = it.default_qty || 0;
          const extraQty = Math.max(0, qty - defQty);
          const unitPrice = it.delta || it.sale_price || 0;
          const price = extraQty * unitPrice;
          totalDelta += price;
          if (qty > 0) {
            groupItems.push({
              name: it.name || it.label || '',
              qty: qty,
              unit_price: unitPrice,
              price: price,
              default_qty: defQty,
              delta_qty: qty - defQty,
            });
          }
        });
        if (groupItems.length > 0) {
          groups.push({ name: g.name, type: 'extra', items: groupItems });
        }
      }
    });

    return { groups, total_delta: totalDelta, has_customization: true };
  }

  function buildCustomSummary(customData) {
    const parts = [];
    (customData.groups || []).forEach(g => {
      (g.items || []).forEach(it => {
        if ((it.qty || 0) > 0) {
          const n = it.qty > 1 ? it.qty + 'x ' + it.name : it.name;
          parts.push(n);
        }
      });
    });
    return parts.join(', ');
  }

  function confirmCustomization() {
    const customData = buildCustomizationData();
    const delta = customData.total_delta || 0;
    const totalPrice = modalBasePrice + delta;
    const summary = buildCustomSummary(customData);
    const key = 'c_' + modalProductId + '_' + Date.now();

    customCart.push({
      key,
      productId: modalProductId,
      name: modalProductName,
      basePrice: modalBasePrice,
      totalPrice: totalPrice,
      qty: 1,
      customizationData: customData,
      summary,
    });

    // Update product grid qty display
    const gridItem = document.querySelector('.product-item[data-id="' + modalProductId + '"]');
    if (gridItem) {
      const total = customCart.filter(c => String(c.productId) === String(modalProductId)).reduce((s, c) => s + c.qty, 0);
      const qd = gridItem.querySelector('.qty-input-display');
      if (qd) qd.value = total;
      gridItem.classList.add('selected');
    }

    closeCustomizationModal();
    updateCartDisplay();
    recalc();
    if (productsGrid) productsGrid.classList.remove('input-error');
  }
  window.confirmCustomization = confirmCustomization;

  // === Payment Selection ===
  let selectedPaymentType = null;
  let selectedMethodId = null;
  
  window.selectPaymentType = function(type, methodId) {
    const btn = document.querySelector('.payment-type-btn[data-type="' + type + '"]');
    const isAlreadyActive = btn && btn.classList.contains('active');
    
    if (isAlreadyActive && (type === 'credit' || type === 'debit')) {
      const brandsSection = document.getElementById(type + '-brands');
      if (brandsSection && brandsSection.classList.contains('show')) {
        brandsSection.classList.remove('show');
        return;
      }
    }
    
    document.querySelectorAll('.payment-type-btn').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.card-brands').forEach(function(section) { section.classList.remove('show'); });
    
    if (btn) {
      btn.classList.add('active');
      var radio = btn.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
    }
    
    selectedPaymentType = type;
    
    if (type === 'credit' || type === 'debit') {
      var brandsSection = document.getElementById(type + '-brands');
      if (brandsSection) {
        brandsSection.classList.add('show');
        // Auto-selecionar bandeira se só houver uma
        if (!methodId) {
          var brandBtns = brandsSection.querySelectorAll('.brand-btn');
          if (brandBtns.length === 1) {
            methodId = parseInt(brandBtns[0].dataset.methodId);
          }
        }
      }
    }
    
    if (methodId) {
      selectedMethodId = methodId;
      if (paymentMethodIdInput) paymentMethodIdInput.value = methodId;
    }

    // Mostrar/esconder bloco de troco
    var cashChangeBlock = document.getElementById('cash-change-block');
    if (cashChangeBlock) {
      cashChangeBlock.style.display = (type === 'cash') ? 'block' : 'none';
      if (type !== 'cash') {
        var ci = document.getElementById('cash-change-input');
        if (ci) ci.value = '';
        var aci = document.getElementById('admin-change-info');
        if (aci) aci.style.display = 'none';
      }
    }
    
    // Remover erro visual
    var container = document.getElementById('payment-methods-container');
    if (container) container.classList.remove('input-error');
  };
  
  window.selectCardBrand = function(type, methodId) {
    var brandsSection = document.getElementById(type + '-brands');
    if (brandsSection) {
      brandsSection.querySelectorAll('.brand-btn').forEach(function(btn) { btn.classList.remove('active'); });
      
      var brandBtn = brandsSection.querySelector('.brand-btn[data-method-id="' + methodId + '"]');
      if (brandBtn) brandBtn.classList.add('active');
    }
    
    selectedMethodId = methodId;
    if (paymentMethodIdInput) paymentMethodIdInput.value = methodId;
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
      if (initialType === 'cash') {
        setTimeout(() => {
          if (typeof calcAdminChange === 'function') calcAdminChange();
        }, 0);
      }
    }
  }

  // === Validação do Formulário ===
  function showValidationToast(errors) {
    var existing = document.querySelector('.validation-toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.className = 'validation-toast';
    toast.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      '<div class="validation-toast-content">' +
        '<div class="validation-toast-title">Preencha os campos obrigatórios</div>' +
        '<div class="validation-toast-items">' + errors.join(' • ') + '</div>' +
      '</div>' +
      '<button class="validation-toast-close" onclick="this.parentElement.remove()">×</button>';
    document.body.appendChild(toast);
    setTimeout(function() {
      if (toast.parentElement) {
        toast.style.animation = 'vToastOut 0.3s ease forwards';
        setTimeout(function() { toast.remove(); }, 300);
      }
    }, 5000);
  }

  var orderForm = document.getElementById('order-form');
  orderForm.addEventListener('submit', function(e) {
    var errors = [];
    var firstErrorEl = null;

    // Telefone
    var phone = phoneInput ? phoneInput.value.replace(/\D/g, '') : '';
    if (phone.length < 10) {
      errors.push('Telefone');
      if (phoneInput) { phoneInput.classList.add('input-error'); if (!firstErrorEl) firstErrorEl = phoneInput; }
    } else if (phoneInput) {
      phoneInput.classList.remove('input-error');
    }

    // Nome
    var nameVal = customerName ? customerName.value.trim() : '';
    if (!nameVal) {
      errors.push('Nome');
      if (customerName) { customerName.classList.add('input-error'); if (!firstErrorEl) firstErrorEl = customerName; }
    } else if (customerName) {
      customerName.classList.remove('input-error');
    }

    // Endereço (só valida se não for retirada)
    var streetInput = document.getElementById('street-input');
    var numberInput = document.getElementById('number-input');
    if (!isPickupMode()) {
      if (streetInput && !streetInput.value.trim()) {
        errors.push('Rua');
        streetInput.classList.add('input-error');
        if (!firstErrorEl) firstErrorEl = streetInput;
      } else if (streetInput) {
        streetInput.classList.remove('input-error');
      }
      if (numberInput && !numberInput.value.trim()) {
        errors.push('Número');
        numberInput.classList.add('input-error');
        if (!firstErrorEl) firstErrorEl = numberInput;
      } else if (numberInput) {
        numberInput.classList.remove('input-error');
      }

      // Cidade/Bairro
      if (citySelect && !citySelect.value) {
        errors.push('Cidade');
        citySelect.classList.add('input-error');
        if (!firstErrorEl) firstErrorEl = citySelect;
      } else if (citySelect) {
        citySelect.classList.remove('input-error');
      }
      if (zoneSelect && !zoneSelect.disabled && !zoneSelect.value) {
        errors.push('Bairro');
        zoneSelect.classList.add('input-error');
        if (!firstErrorEl) firstErrorEl = zoneSelect;
      } else if (zoneSelect) {
        zoneSelect.classList.remove('input-error');
      }
    } else {
      // Limpar erros dos campos de endereço no modo retirada
      if (streetInput) streetInput.classList.remove('input-error');
      if (numberInput) numberInput.classList.remove('input-error');
      if (citySelect) citySelect.classList.remove('input-error');
      if (zoneSelect) zoneSelect.classList.remove('input-error');
    }

    // Produtos
    var hasProduct = Object.values(cart).some(function(item) { return item.qty > 0; }) || customCart.length > 0;
    if (!hasProduct) {
      errors.push('Produtos');
      if (productsGrid) { productsGrid.classList.add('input-error'); if (!firstErrorEl) firstErrorEl = productsGrid; }
    } else if (productsGrid) {
      productsGrid.classList.remove('input-error');
    }

    // Pagamento
    if (!selectedPaymentType) {
      errors.push('Pagamento');
      var payContainer = document.getElementById('payment-methods-container');
      if (payContainer) { payContainer.classList.add('input-error'); if (!firstErrorEl) firstErrorEl = payContainer; }
    } else if ((selectedPaymentType === 'credit' || selectedPaymentType === 'debit') && !selectedMethodId) {
      errors.push('Bandeira do cartão');
      var payContainer = document.getElementById('payment-methods-container');
      if (payContainer) { payContainer.classList.add('input-error'); if (!firstErrorEl) firstErrorEl = payContainer; }
    }

    if (errors.length > 0) {
      e.preventDefault();
      showValidationToast(errors);
      if (firstErrorEl) {
        var rect = firstErrorEl.getBoundingClientRect();
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        window.scrollTo({ top: scrollTop + rect.top - 100, behavior: 'smooth' });
      }
      return false;
    }
  });

  // === Troco para Dinheiro ===
  window.calcAdminChange = function() {
    var input = document.getElementById('cash-change-input');
    var infoBox = document.getElementById('admin-change-info');
    var changeEl = document.getElementById('admin-change-amount');
    var totalEl = document.getElementById('admin-order-total-display');
    var errorEl = document.getElementById('admin-cash-error');
    if (!input || !infoBox || !changeEl || !totalEl || !errorEl) return;

    var cashValue = parseFloat(input.value) || 0;
    var totalText = (document.getElementById('total-view') || {}).textContent || '0';
    var orderTotal = parseFloat(totalText.replace(/[^\d,\.]/g, '').replace(',', '.')) || 0;

    totalEl.textContent = 'R$ ' + orderTotal.toFixed(2).replace('.', ',');

    if (cashValue === 0) { infoBox.style.display = 'none'; errorEl.style.display = 'none'; return; }

    if (cashValue < orderTotal) {
      infoBox.style.display = 'none';
      errorEl.style.display = 'block';
      errorEl.textContent = 'Valor insuficiente. Total: R$ ' + orderTotal.toFixed(2).replace('.', ',');
      return;
    }
    var change = cashValue - orderTotal;
    changeEl.textContent = 'R$ ' + change.toFixed(2).replace('.', ',');
    infoBox.style.display = 'block';
    errorEl.style.display = 'none';
  };

  // === Remover erro visual ao interagir ===
  document.querySelectorAll('.order-form-input').forEach(function(input) {
    input.addEventListener('input', function() { this.classList.remove('input-error'); });
    input.addEventListener('change', function() { this.classList.remove('input-error'); });
  });

  // ======= AUTOCOMPLETE DE RUA =======
  (function() {
    var streetInput = document.getElementById('street-input');
    var acList = document.getElementById('street-autocomplete-list');
    if (!streetInput || !acList) return;

    var acTimer = null;
    var acAbort = null;
    var acIndex = -1;
    var selectedFromList = false;

    function getCityName() {
      if (!citySelect) return '';
      var opt = citySelect.options[citySelect.selectedIndex];
      return opt && opt.value ? opt.textContent.trim() : '';
    }

    function getNeighborhoodName() {
      if (!zoneSelect) return '';
      var opt = zoneSelect.options[zoneSelect.selectedIndex];
      return opt && opt.value ? opt.textContent.trim() : '';
    }

    function closeList() {
      acList.innerHTML = '';
      acList.classList.remove('active');
      acIndex = -1;
    }

    function trackPopularity(streetId) {
      if (!streetId || streetId <= 0) return;
      try {
        fetch('/' + encodeURIComponent(adminSlug) + '/street-autocomplete/popularity', {
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
        fetch('/' + encodeURIComponent(adminSlug) + '/street-autocomplete/learn', {
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

      var url = '/' + encodeURIComponent(adminSlug) + '/street-autocomplete'
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
              var numInput = document.getElementById('number-input');
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

  // === Inicializar ===
  recalc();
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
