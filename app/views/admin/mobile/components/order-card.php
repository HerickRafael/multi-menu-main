<?php
/**
 * Order Card Mobile - Card de pedido otimizado para toque
 * 
 * @param array $order - Dados do pedido
 * @param bool $showActions - Mostrar botões de ação
 */

$order = $order ?? null;
if (!$order) return;

$showActions = $showActions ?? true;

// Status com cores e ícones - apenas pendente, concluído e cancelado
$statusConfig = [
    // Status principais
    'pending' => ['label' => 'Pendente', 'color' => 'warning', 'icon' => '⏳'],
    'completed' => ['label' => 'Concluído', 'color' => 'success', 'icon' => '✅'],
    'cancelled' => ['label' => 'Cancelado', 'color' => 'danger', 'icon' => '✕'],
    'canceled' => ['label' => 'Cancelado', 'color' => 'danger', 'icon' => '✕'],
    // Mapeamento de status antigos para concluído
    'confirmed' => ['label' => 'Concluído', 'color' => 'success', 'icon' => '✅'],
    'preparing' => ['label' => 'Concluído', 'color' => 'success', 'icon' => '✅'],
    'ready' => ['label' => 'Concluído', 'color' => 'success', 'icon' => '✅'],
    'delivered' => ['label' => 'Concluído', 'color' => 'success', 'icon' => '✅'],
    'paid' => ['label' => 'Concluído', 'color' => 'success', 'icon' => '✅'],
];

$status = $order['status'] ?? 'pending';
$config = $statusConfig[$status] ?? $statusConfig['pending'];

$deliveryType = $order['delivery_type'] ?? 'delivery';
$deliveryIcon = $deliveryType === 'pickup' ? '🏃' : '🛵';
$deliveryLabel = $deliveryType === 'pickup' ? 'Retirada' : 'Entrega';

$orderId = $order['id'] ?? 0;
$orderNumber = $order['order_number'] ?? $order['id'] ?? 0; // Usa order_number para exibição
$createdAt = isset($order['created_at']) ? date('H:i', strtotime($order['created_at'])) : '--:--';
$total = isset($order['total']) ? 'R$ ' . number_format((float)$order['total'], 2, ',', '.') : 'R$ 0,00';
$customerName = $order['customer_name'] ?? 'Cliente';
$customerPhone = $order['customer_phone'] ?? '';
// Compatibilidade: o model retorna items_count ou item_count
$itemCount = (int)($order['item_count'] ?? $order['items_count'] ?? 0);
?>

<div class="order-card" data-order-id="<?= $orderId ?>" data-status="<?= htmlspecialchars($status) ?>">
    <div class="order-card__header">
        <div class="order-card__number">
            #<?= $orderNumber ?>
        </div>
        <div class="order-card__time">
            <?= $createdAt ?>
        </div>
        <div class="order-card__badge order-card__badge--<?= $config['color'] ?>">
            <?= $config['icon'] ?> <?= $config['label'] ?>
        </div>
    </div>
    
    <div class="order-card__body">
        <div class="order-card__customer">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <div class="order-card__customer-info">
                <span class="order-card__customer-name"><?= htmlspecialchars($customerName) ?></span>
                <?php if ($customerPhone): ?>
                    <span class="order-card__customer-phone"><?= htmlspecialchars($customerPhone) ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="order-card__info">
            <span class="order-card__delivery">
                <?= $deliveryIcon ?> <?= $deliveryLabel ?>
            </span>
            <span class="order-card__items">
                <?= $itemCount ?> <?= $itemCount === 1 ? 'item' : 'itens' ?>
            </span>
        </div>
        
        <div class="order-card__total">
            <?= $total ?>
        </div>
    </div>
    
    <?php if ($showActions): ?>
    <div class="order-card__actions">
        <?php if ($status === 'pending'): ?>
            <button type="button" class="btn-action btn-action--success" data-action="confirm" data-order="<?= $orderId ?>">
                Confirmar
            </button>
            <button type="button" class="btn-action btn-action--danger" data-action="cancel" data-order="<?= $orderId ?>">
                Recusar
            </button>
        <?php elseif ($status === 'confirmed'): ?>
            <button type="button" class="btn-action btn-action--primary" data-action="preparing" data-order="<?= $orderId ?>">
                Iniciar Preparo
            </button>
        <?php elseif ($status === 'paid'): ?>
            <button type="button" class="btn-action btn-action--success" data-action="completed" data-order="<?= $orderId ?>">
                Concluir
            </button>
        <?php elseif ($status === 'preparing'): ?>
            <button type="button" class="btn-action btn-action--success" data-action="ready" data-order="<?= $orderId ?>">
                Pronto
            </button>
        <?php elseif ($status === 'ready'): ?>
            <button type="button" class="btn-action btn-action--success" data-action="delivered" data-order="<?= $orderId ?>">
                Entregar
            </button>
        <?php endif; ?>
        
        <a href="/orders/show?id=<?= $orderId ?>" class="btn-action btn-action--secondary">
            Detalhes
        </a>
    </div>
    <?php endif; ?>
</div>
