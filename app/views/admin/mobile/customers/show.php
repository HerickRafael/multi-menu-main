<?php
/**
 * Detalhes do Cliente Mobile
 */
ob_start();
?>

<div class="customer-detail">
    <!-- Header do Cliente -->
    <div class="customer-header">
        <div class="customer-avatar-lg">
            <?= strtoupper(substr($customer['name'] ?: 'C', 0, 1)) ?>
        </div>
        <h2 class="customer-detail-name"><?= htmlspecialchars($customer['name'] ?: 'Cliente') ?></h2>
        
        <div class="customer-contact-btns">
            <?php if (!empty($customer['whatsapp'])): ?>
                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $customer['whatsapp']) ?>" 
                   class="btn-icon-circle whatsapp" target="_blank">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </a>
            <?php endif; ?>
            
            <a href="tel:<?= htmlspecialchars($customer['whatsapp'] ?? '') ?>" class="btn-icon-circle phone">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </a>
            
            <a href="/customers/<?= $customer['id'] ?>/edit" class="btn-icon-circle edit">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </a>
        </div>
    </div>
    
    <!-- Info Cards -->
    <div class="info-cards">
        <div class="info-card">
            <span class="info-label">WhatsApp</span>
            <span class="info-value"><?= htmlspecialchars($customer['whatsapp'] ?? 'Não informado') ?></span>
        </div>
        
        <?php if (!empty($customer['email'])): ?>
        <div class="info-card">
            <span class="info-label">Email</span>
            <span class="info-value"><?= htmlspecialchars($customer['email']) ?></span>
        </div>
        <?php endif; ?>
        
        <div class="info-card">
            <span class="info-label">Cliente desde</span>
            <span class="info-value"><?= date('d/m/Y', strtotime($customer['created_at'])) ?></span>
        </div>
        
        <?php if (!empty($customer['notes'])): ?>
        <div class="info-card full">
            <span class="info-label">Observações</span>
            <span class="info-value"><?= nl2br(htmlspecialchars($customer['notes'])) ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Endereços -->
    <?php if (!empty($addresses)): ?>
    <div class="section mt-lg">
        <h3 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            Endereços
        </h3>
        <div class="address-list">
            <?php foreach ($addresses as $addr): ?>
                <div class="address-card">
                    <?php if ($addr['is_default']): ?>
                        <span class="address-badge">Principal</span>
                    <?php endif; ?>
                    <p class="address-street"><?= htmlspecialchars($addr['street'] ?? '') ?>, <?= htmlspecialchars($addr['number'] ?? '') ?></p>
                    <?php if (!empty($addr['complement'])): ?>
                        <p class="address-complement"><?= htmlspecialchars($addr['complement']) ?></p>
                    <?php endif; ?>
                    <p class="address-city"><?= htmlspecialchars($addr['neighborhood'] ?? '') ?> - <?= htmlspecialchars($addr['city'] ?? '') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Últimos Pedidos -->
    <div class="section mt-lg">
        <h3 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Últimos Pedidos
        </h3>
        
        <?php if (empty($orders)): ?>
            <p class="text-muted">Nenhum pedido registrado</p>
        <?php else: ?>
            <div class="orders-mini-list">
                <?php foreach ($orders as $order): 
                    $orderNum = $order['order_number'] ?? $order['id'] ?? 0;
                ?>
                    <a href="/orders?id=<?= $order['id'] ?>" class="order-mini-card">
                        <div class="order-mini-info">
                            <span class="order-mini-id">#<?= $orderNum ?></span>
                            <span class="order-mini-date"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="order-mini-total">
                            R$ <?= number_format((float)$order['total'], 2, ',', '.') ?>
                        </div>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
