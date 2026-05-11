<?php
/**
 * Detalhes do Pedido Mobile
 * Design baseado no desktop, 100% estruturado para mobile
 * 
 * @var array $company
 * @var array $u
 * @var array $order
 * @var array $items
 * @var array|null $customer
 * @var string $pageTitle
 * @var string $activeNav
 * @var bool $showBackButton
 */

$o = $order ?? [];
$status = $o['status'] ?? 'pending';

// Opções de status para o dropdown (apenas as 3 opções)
$statusOptions = [
    'pending'   => 'Pendente',
    'completed' => 'Concluído',
    'canceled'  => 'Cancelado',
];

// Labels de status - mapeia todos os status para exibição
$statusLabels = [
    'pending'   => 'Pendente',
    'completed' => 'Concluído',
    'cancelled' => 'Cancelado',
    'canceled'  => 'Cancelado',
    // Mapeamento de status antigos para novos
    'confirmed' => 'Concluído',
    'preparing' => 'Concluído',
    'ready'     => 'Concluído',
    'delivered' => 'Concluído',
    'paid'      => 'Concluído',
];

$statusColors = [
    'pending'   => ['bg' => '#fef3c7', 'text' => '#92400e', 'border' => '#fcd34d'],
    'confirmed' => ['bg' => '#dbeafe', 'text' => '#1e40af', 'border' => '#93c5fd'],
    'preparing' => ['bg' => '#ede9fe', 'text' => '#7c3aed', 'border' => '#c4b5fd'],
    'ready'     => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'delivered' => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#fca5a5'],
    'paid'      => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'completed' => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'canceled'  => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#fca5a5'],
];

$stColor = $statusColors[$status] ?? $statusColors['pending'];

// Origem do pedido
$source = $o['source'] ?? 'manual';

$canEdit = $status === 'pending' && $source !== 'ifood';
$orderEvents = $orderEvents ?? [];
$historyEvents = array_values(array_filter($orderEvents, static function ($event) {
    return in_array($event['event_type'] ?? '', ['order.created', 'order.updated', 'order.status_changed', 'order.canceled'], true);
}));

// Valores formatados
$subtotalVal   = (float)($o['subtotal'] ?? 0);
$deliveryFeeVal = (float)($o['delivery_fee'] ?? 0);
$discountVal   = (float)($o['discount'] ?? 0) + (float)($o['loyalty_discount'] ?? 0);
$totalVal      = (float)($o['total'] ?? 0);
$createdAt     = $o['created_at'] ?? '';

// Cliente - usa dados do pedido preferencialmente
$clientName  = !empty($o['customer_name']) ? $o['customer_name'] : ($customer['name'] ?? 'Cliente');
$clientPhone = !empty($o['customer_phone']) ? $o['customer_phone'] : ($customer['phone'] ?? '');
$clientAddress = $o['customer_address'] ?? '';

// WhatsApp link
$orderNumber = $o['order_number'] ?? $o['id'] ?? 0; // Número do pedido para exibição
$waLink = null;
if (!empty($clientPhone)) {
    $digits = preg_replace('/\D+/', '', $clientPhone);
    if ($digits) {
        // Garante código do país 55 (Brasil)
        if (!str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }
        $waText = rawurlencode('Olá! Sobre o pedido #' . (int)$orderNumber . '.');
        $waLink = "https://wa.me/{$digits}?text={$waText}";
    }
}

// Delivery type
$deliveryType = $o['delivery_type'] ?? 'delivery';
$deliveryLabel = $deliveryType === 'pickup' ? 'Retirada no local' : 'Entrega';

// Buffer de conteúdo
ob_start();
?>

<style>
/* Estilos específicos para a página de detalhes do pedido - Mobile */
.order-detail-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.order-detail-card__header {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.order-detail-card__title {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.order-detail-card__body {
    padding: 16px;
}

/* Status pill igual desktop */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 9999px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid;
}

/* Formulário de status */
.status-form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 16px;
}

.status-form label {
    font-size: 14px;
    color: #475569;
}

.status-form select {
    flex: 1;
    min-width: 120px;
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-size: 14px;
    background: white;
    color: #1e293b;
}

.status-form button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: white;
    font-size: 14px;
    color: #475569;
    cursor: pointer;
}

.status-form button:active {
    background: #f1f5f9;
}

/* Badges de origem */
.source-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.source-badge--ifood {
    background: #fee2e2;
    color: #b91c1c;
}

.source-badge--site {
    background: #dbeafe;
    color: #1d4ed8;
}

.source-badge--manual {
    background: #f1f5f9;
    color: #475569;
}

/* Grid de cards Cliente/Resumo */
.order-cards-grid {
    display: grid;
    gap: 16px;
    margin-bottom: 16px;
}

/* Cliente info */
.client-name {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.client-phone {
    font-size: 14px;
    color: #475569;
}

.client-address {
    margin-top: 8px;
    font-size: 14px;
    color: #475569;
    line-height: 1.5;
}

.client-notes {
    margin-top: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 12px;
}

.client-notes__label {
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 4px;
}

/* Resumo financeiro */
.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 14px;
}

.summary-row dt {
    color: #64748b;
}

.summary-row dd {
    color: #1e293b;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 12px;
}

.summary-total__label {
    font-size: 14px;
    color: #64748b;
}

.summary-total__value {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
}

/* Item do pedido */
.order-item {
    padding: 16px;
    transition: background 0.15s;
}

.order-item:not(:last-child) {
    border-bottom: 1px solid #f1f5f9;
}

.order-item:active {
    background: #f8fafc;
}

.order-item__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.order-item__qty {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    padding: 4px 10px;
    background: #f1f5f9;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}

.order-item__name {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
}

.order-item__unit-price {
    font-size: 13px;
    color: #64748b;
}

.order-item__total {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    text-align: right;
}

/* Seção de detalhes do item (combo, personalização) */
.order-item__section {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}

.order-item__section-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 8px;
}

.order-item__detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    font-size: 13px;
}

.order-item__detail-row:nth-child(odd) .order-item__detail-name {
    font-weight: 500;
    color: #475569;
}

.order-item__detail-row:nth-child(even) .order-item__detail-name {
    color: #64748b;
}

.order-item__detail-value {
    color: #94a3b8;
}
.order-item__detail-value.free  { color: #16a34a; font-weight: 600; }
.order-item__detail-value.charged { color: #f59e0b; font-weight: 600; }
.order-item__detail-value.removed { color: #ef4444; }

.order-item__notes {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}

/* Ações rápidas */
.quick-actions-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}

.quick-action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid;
    cursor: pointer;
}

.quick-action-btn--whatsapp {
    background: #dcfce7;
    color: #166534;
    border-color: #86efac;
}

.quick-action-btn--delete {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fca5a5;
}

.quick-action-btn--print {
    background: #f1f5f9;
    color: #475569;
    border-color: #cbd5e1;
}

.quick-action-btn--edit {
    background: #e0e7ff;
    color: #3730a3;
    border-color: #c7d2fe;
}

/* Empty state */
.empty-items {
    padding: 32px;
    text-align: center;
}

.empty-items svg {
    width: 48px;
    height: 48px;
    color: #cbd5e1;
    margin: 0 auto 12px;
}

.empty-items p {
    font-size: 14px;
    color: #64748b;
}
</style>

<!-- Cabeçalho com Status, Origem e Data -->
<div class="order-detail-card">
    <div class="order-detail-card__body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span class="status-pill" style="background: <?= $stColor['bg'] ?>; color: <?= $stColor['text'] ?>; border-color: <?= $stColor['border'] ?>;">
                <?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status)) ?>
            </span>
            <?php if ($source === 'ifood'): ?>
                <span class="source-badge source-badge--ifood">iFood</span>
            <?php elseif ($source === 'website'): ?>
                <span class="source-badge source-badge--site">Site</span>
            <?php else: ?>
                <span class="source-badge source-badge--manual">Manual</span>
            <?php endif; ?>
            <?php if ($createdAt): ?>
                <span style="font-size: 13px; color: #64748b;">
                    <?= htmlspecialchars($createdAt) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="quick-actions-bar">
    <?php if ($waLink): ?>
        <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" class="quick-action-btn quick-action-btn--whatsapp">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M7 20l1.5-4.5a7 7 0 1 1 2.5 2.5L7 20z"/>
            </svg>
            WhatsApp
        </a>
    <?php endif; ?>
    <?php if ($canEdit): ?>
        <a href="/orders/<?= (int)$o['id'] ?>/edit" class="quick-action-btn quick-action-btn--edit">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M4 20h4l10-10-4-4L4 16v4Z"/>
            </svg>
            Editar
        </a>
    <?php endif; ?>
    <a href="/orders/print?id=<?= (int)$o['id'] ?>" target="_blank" class="quick-action-btn quick-action-btn--print">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M7 9V4h10v5M7 14H5a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-2m-10 0h10v6H7v-6Z"/>
        </svg>
        Imprimir
    </a>
    <form method="post" action="/orders/<?= (int)$o['id'] ?>/del" style="flex: 1; display: flex;" onsubmit="return confirm('Excluir pedido?')">
        <button type="submit" class="quick-action-btn quick-action-btn--delete" style="width: 100%;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2"/>
            </svg>
            Excluir
        </button>
    </form>
</div>

<!-- Formulário de Status -->
<form method="post" action="/orders/setStatus" class="status-form">
    <input type="hidden" name="id" value="<?= (int)($o['id'] ?? 0) ?>">
    <label>Status:</label>
    <select name="status">
        <?php foreach ($statusOptions as $k => $label): ?>
            <?php 
            // Marca como selected se o status atual mapeia para este valor
            $isSelected = ($status === $k) || ($statusLabels[$status] ?? '') === $label;
            ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= $isSelected ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 7 9 18l-5-5"/>
        </svg>
        Aplicar
    </button>
</form>

<!-- Cards: Cliente e Resumo -->
<div class="order-cards-grid">
    <!-- Cliente -->
    <div class="order-detail-card">
        <div class="order-detail-card__header">
            <span class="order-detail-card__title">Cliente</span>
        </div>
        <div class="order-detail-card__body">
            <div class="client-name"><?= htmlspecialchars($clientName) ?></div>
            <?php if ($clientPhone): ?>
                <div class="client-phone">
                    <a href="tel:<?= htmlspecialchars($clientPhone) ?>" style="color: inherit; text-decoration: none;">
                        <?= htmlspecialchars($clientPhone) ?>
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($clientAddress): ?>
                <div class="client-address"><?= nl2br(htmlspecialchars($clientAddress)) ?></div>
            <?php endif; ?>
            <?php if (!empty($o['notes'])): ?>
                <div class="client-notes">
                    <div class="client-notes__label">Observações</div>
                    <div style="font-size: 14px; color: #475569;"><?= nl2br(htmlspecialchars($o['notes'])) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumo -->
    <div class="order-detail-card">
        <div class="order-detail-card__header">
            <span class="order-detail-card__title">Resumo</span>
        </div>
        <div class="order-detail-card__body">
            <dl style="margin: 0;">
                <div class="summary-row">
                    <dt>Subtotal</dt>
                    <dd>R$ <?= number_format($subtotalVal, 2, ',', '.') ?></dd>
                </div>
                <div class="summary-row">
                    <dt>Entrega</dt>
                    <dd>R$ <?= number_format($deliveryFeeVal, 2, ',', '.') ?></dd>
                </div>
                <div class="summary-row">
                    <dt>Desconto</dt>
                    <dd>R$ <?= number_format($discountVal, 2, ',', '.') ?></dd>
                </div>
            </dl>
            <div class="summary-total">
                <span class="summary-total__label">Total</span>
                <span class="summary-total__value">R$ <?= number_format($totalVal, 2, ',', '.') ?></span>
            </div>
            <?php 
            // Buscar método de pagamento do banco
            $paymentMethodId = (int)($o['payment_method_id'] ?? 0);
            $paymentDisplay = 'Não informado';
            $paymentType = 'others';
            
            if ($paymentMethodId > 0) {
                $pmStmt = db()->prepare("SELECT name, type FROM payment_methods WHERE id = ?");
                $pmStmt->execute([$paymentMethodId]);
                $pmData = $pmStmt->fetch(PDO::FETCH_ASSOC);
                if ($pmData) {
                    $paymentDisplay = $pmData['name'];
                    $paymentType = $pmData['type'] ?? 'others';
                }
            }
            
            // Mapeamento de tipos para ícones SVG
            $iconMapping = [
                'pix' => 'pix',
                'cash' => 'cash',
                'credit' => 'credit',
                'debit' => 'debit',
                'voucher' => 'voucher',
                'others' => 'others',
            ];
            $iconFile = $iconMapping[$paymentType] ?? 'others';
            ?>
            <div style="margin-top: 12px; padding: 12px; background: #f8fafc; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                <img src="<?= base_url('assets/card-brands/' . $iconFile . '.svg') ?>" 
                     alt="<?= htmlspecialchars($paymentDisplay) ?>" 
                     style="width: 24px; height: 24px; object-fit: contain;"
                     onerror="this.src='<?= base_url('assets/card-brands/others.svg') ?>'">
                <div>
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 500;">Pagamento</div>
                    <div style="font-size: 14px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($paymentDisplay) ?></div>
                </div>
            </div>
            <div style="margin-top: 8px; font-size: 12px; color: #64748b;">
                Status atual: <?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status)) ?>
            </div>
        </div>
    </div>
</div>

<!-- Itens do Pedido -->
<div class="order-detail-card">
    <div class="order-detail-card__header">
        <span class="order-detail-card__title">Itens do Pedido</span>
    </div>
    
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $it): ?>
            <div class="order-item">
                <!-- Nome e Quantidade -->
                <div class="order-item__header">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="order-item__qty"><?= (int)($it['quantity'] ?? 0) ?>x</span>
                            <span class="order-item__name"><?= htmlspecialchars($it['product_name'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="order-item__unit-price">R$ <?= number_format((float)($it['unit_price'] ?? 0), 2, ',', '.') ?></div>
                        <div class="order-item__total">R$ <?= number_format((float)($it['line_total'] ?? 0), 2, ',', '.') ?></div>
                    </div>
                </div>
                
                <?php 
                // Decodificar dados de combo
                $comboData = null;
                if (!empty($it['combo_data'])) {
                    $comboData = is_string($it['combo_data']) ? json_decode($it['combo_data'], true) : $it['combo_data'];
                }
                ?>
                
                <!-- Opções do Combo -->
                <?php if ($comboData && !empty($comboData['selected_items'])): ?>
                    <div class="order-item__section">
                        <div class="order-item__section-title">Opções do Combo</div>
                        <?php foreach ($comboData['selected_items'] as $idx => $comboItem): ?>
                            <?php 
                              $itemName = $comboItem['simple_name'] ?? $comboItem['name'] ?? '';
                              $itemQty = isset($comboItem['qty']) ? (int)$comboItem['qty'] : (isset($comboItem['default_qty']) ? (int)$comboItem['default_qty'] : 1);
                              if ($itemQty <= 0) $itemQty = 1;
                              $displayName = $itemQty > 1 ? "{$itemQty}x {$itemName}" : $itemName;
                            ?>
                            <?php if ($itemName): ?>
                                <div class="order-item__detail-row">
                                    <span class="order-item__detail-name"><?= htmlspecialchars($displayName) ?></span>
                                    <span class="order-item__detail-value">
                                        <?php
                                        $delta = (float)($comboItem['delta'] ?? $comboItem['delta_price'] ?? 0);
                                        echo $delta > 0.009 ? '+ R$ ' . number_format($delta, 2, ',', '.') : 'Incluso';
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php 
                // Decodificar dados de personalização
                $customData = null;
                if (!empty($it['customization_data'])) {
                    $customData = is_string($it['customization_data']) ? json_decode($it['customization_data'], true) : $it['customization_data'];
                }
                ?>
                
                <!-- Personalização -->
                <?php if ($customData && !empty($customData['groups'])): ?>
                    <?php 
                    $customItems = [];
                    foreach ($customData['groups'] as $group) {
                        $groupType = $group['type'] ?? 'extra';
                        $isChoiceGroup = in_array($groupType, ['single', 'addon', 'choice']);
                        $isPoolGroup = $groupType === 'pool';

                        if (!empty($group['items'])) {
                            // Pool: calcula inclusas vs extras com contagem progressiva
                            if ($isPoolGroup) {
                                $poolFree = (int)($group['pool_free'] ?? 99);
                                $freeRemaining = $poolFree;
                                foreach ($group['items'] as $customItem) {
                                    $itemName = $customItem['name'] ?? '';
                                    $qty = (int)($customItem['qty'] ?? 0);
                                    if (!$itemName || $qty <= 0) continue;
                                    $free = min($qty, $freeRemaining);
                                    $paid = $qty - $free;
                                    $freeRemaining -= $free;
                                    $unitPrice = (float)($customItem['extra_price'] ?? $customItem['unit_price'] ?? $customItem['price'] ?? 0);
                                    $displayText = $qty > 1 ? "{$qty}x {$itemName}" : $itemName;
                                    if ($paid > 0 && $unitPrice > 0.009) {
                                        $statusText = '+ R$ ' . number_format($paid * $unitPrice, 2, ',', '.') . ' · extra';
                                        $statusClass = 'charged';
                                    } else {
                                        $statusText = 'Incluso';
                                        $statusClass = 'free';
                                    }
                                    $customItems[] = ['text' => $displayText, 'status' => $statusText, 'class' => $statusClass];
                                }
                                continue; // próximo grupo
                            }

                            foreach ($group['items'] as $customItem) {
                                $itemName = $customItem['name'] ?? '';
                                $qty = isset($customItem['qty']) ? (int)$customItem['qty'] : null;
                                $deltaQty = isset($customItem['delta_qty']) ? (int)$customItem['delta_qty'] : null;
                                $defaultQty = isset($customItem['default_qty']) ? (int)$customItem['default_qty'] : null;
                                $price = (float)($customItem['price'] ?? 0);
                                $isSelected = !empty($customItem['selected']) || ($qty !== null && $qty > 0);
                                $isRemoved = !empty($customItem['removed']) || ($defaultQty !== null && $defaultQty > 0 && ($qty === 0 || $qty === null));
                                
                                if ($isRemoved && $itemName) {
                                    $customItems[] = ['text' => "Sem " . $itemName, 'status' => 'Removido', 'class' => 'removed'];
                                    continue;
                                }
                                
                                $effectiveQty = $qty ?? 0;
                                if ($deltaQty === null && $defaultQty !== null && $qty !== null) {
                                    $deltaQty = $qty - $defaultQty;
                                }
                                
                                if ($isChoiceGroup && $isSelected && $effectiveQty > 0 && $itemName) {
                                    $statusText = $price > 0.009 ? '+ R$ ' . number_format($price, 2, ',', '.') : 'Incluso';
                                    $customItems[] = ['text' => $itemName, 'status' => $statusText, 'class' => $price > 0.009 ? 'charged' : 'free'];
                                    continue;
                                }
                                
                                if ($itemName && $deltaQty !== null && $deltaQty !== 0) {
                                    if ($deltaQty > 0) {
                                        $displayText = '+' . ($deltaQty > 1 ? "{$deltaQty}x " : "") . $itemName;
                                        $statusText = $price > 0 ? '+ R$ ' . number_format($price, 2, ',', '.') : 'Extra';
                                        $customItems[] = ['text' => $displayText, 'status' => $statusText, 'class' => 'charged'];
                                    } elseif ($deltaQty < 0) {
                                        $customItems[] = ['text' => "Sem " . $itemName, 'status' => 'Removido', 'class' => 'removed'];
                                    }
                                } elseif (!$isChoiceGroup && $itemName && $price > 0.009 && $effectiveQty > 0) {
                                    $displayText = ($effectiveQty > 1 ? "{$effectiveQty}x " : "") . $itemName;
                                    $customItems[] = ['text' => $displayText, 'status' => '+ R$ ' . number_format($price, 2, ',', '.'), 'class' => 'charged'];
                                }
                            }
                        }
                    }
                    ?>
                    <?php if (!empty($customItems)): ?>
                        <div class="order-item__section">
                            <div class="order-item__section-title">Personalização</div>
                            <?php foreach ($customItems as $custom): ?>
                                <div class="order-item__detail-row">
                                    <span class="order-item__detail-name"><?= htmlspecialchars($custom['text']) ?></span>
                                    <span class="order-item__detail-value <?= htmlspecialchars($custom['class'] ?? '') ?>"><?= htmlspecialchars($custom['status']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Observações do item -->
                <?php if (!empty($it['notes'])): ?>
                    <div class="order-item__section">
                        <div class="order-item__section-title">Observações</div>
                        <p class="order-item__notes"><?= nl2br(htmlspecialchars($it['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-items">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/>
            </svg>
            <p>Sem itens neste pedido.</p>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($historyEvents)): ?>
    <?php
        $eventLabelMap = [
            'order.created' => 'Pedido criado',
            'order.updated' => 'Pedido atualizado',
            'order.status_changed' => 'Status alterado',
            'order.canceled' => 'Pedido cancelado',
        ];
        $formatMoney = static function ($value) {
            return 'R$ ' . number_format((float)$value, 2, ',', '.');
        };
        $formatStatus = static function ($status) use ($statusLabels) {
            $status = (string)$status;
            return $statusLabels[$status] ?? ucfirst($status);
        };
    ?>
    <div class="order-detail-card">
        <div class="order-detail-card__header">
            <span class="order-detail-card__title">Histórico</span>
        </div>
        <div class="order-detail-card__body" style="display: grid; gap: 16px;">
            <?php foreach ($historyEvents as $event): ?>
                <?php
                    $payload = $event['payload'] ?? [];
                    $meta = $payload['meta'] ?? [];
                    $statusChange = $meta['status_change'] ?? null;
                    $updateMeta = $meta['order_update'] ?? [];
                    $before = $updateMeta['before'] ?? null;
                    $after = $updateMeta['after'] ?? null;
                    $label = $eventLabelMap[$event['event_type'] ?? ''] ?? ($event['event_type'] ?? 'Evento');
                    $createdAt = $event['created_at'] ?? '';
                    $createdAtLabel = $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : '';
                ?>
                <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; justify-content: space-between; gap: 8px; align-items: baseline;">
                        <div style="font-weight: 600; color: #1e293b; font-size: 14px;">
                            <?= htmlspecialchars($label) ?>
                        </div>
                        <?php if ($createdAtLabel): ?>
                            <div style="font-size: 12px; color: #94a3b8;">
                                <?= htmlspecialchars($createdAtLabel) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($statusChange)): ?>
                        <div style="margin-top: 4px; font-size: 13px; color: #475569;">
                            Status: <?= htmlspecialchars($formatStatus($statusChange['from'] ?? '')) ?> → <?= htmlspecialchars($formatStatus($statusChange['to'] ?? '')) ?>
                        </div>
                    <?php elseif (($event['event_type'] ?? '') === 'order.status_changed'): ?>
                        <div style="margin-top: 4px; font-size: 13px; color: #475569;">
                            Status: <?= htmlspecialchars($formatStatus($event['status'] ?? '')) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (($event['event_type'] ?? '') === 'order.updated' && $before && $after): ?>
                        <div style="margin-top: 10px; display: grid; gap: 10px;">
                            <div>
                                <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600;">Antes</div>
                                <div style="font-size: 13px; color: #475569;">Subtotal: <?= htmlspecialchars($formatMoney($before['subtotal'] ?? 0)) ?></div>
                                <div style="font-size: 13px; color: #475569;">Entrega: <?= htmlspecialchars($formatMoney($before['delivery_fee'] ?? 0)) ?></div>
                                <?php $beforeDiscount = (float)($before['discount'] ?? 0) + (float)($before['loyalty_discount'] ?? 0); ?>
                                <div style="font-size: 13px; color: #475569;">Desconto: <?= htmlspecialchars($beforeDiscount > 0 ? '- ' . $formatMoney($beforeDiscount) : $formatMoney(0)) ?></div>
                                <div style="font-size: 14px; font-weight: 600; color: #1e293b;">Total: <?= htmlspecialchars($formatMoney($before['total'] ?? 0)) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600;">Depois</div>
                                <div style="font-size: 13px; color: #475569;">Subtotal: <?= htmlspecialchars($formatMoney($after['subtotal'] ?? 0)) ?></div>
                                <div style="font-size: 13px; color: #475569;">Entrega: <?= htmlspecialchars($formatMoney($after['delivery_fee'] ?? 0)) ?></div>
                                <?php $afterDiscount = (float)($after['discount'] ?? 0) + (float)($after['loyalty_discount'] ?? 0); ?>
                                <div style="font-size: 13px; color: #475569;">Desconto: <?= htmlspecialchars($afterDiscount > 0 ? '- ' . $formatMoney($afterDiscount) : $formatMoney(0)) ?></div>
                                <div style="font-size: 14px; font-weight: 600; color: #1e293b;">Total: <?= htmlspecialchars($formatMoney($after['total'] ?? 0)) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();

// Inclui layout
include __DIR__ . '/../layout.php';
?>
