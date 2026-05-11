<?php
/**
 * iFood Order Detail - Mobile
 */
$activeNav = 'orders';

$statusLabels = [
    'PLACED' => ['label' => 'Novo Pedido', 'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => '🔔'],
    'CONFIRMED' => ['label' => 'Confirmado', 'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => '✅'],
    'READY_TO_PICKUP' => ['label' => 'Pronto', 'bg' => '#e0e7ff', 'color' => '#3730a3', 'icon' => '📦'],
    'DISPATCHED' => ['label' => 'Em Entrega', 'bg' => '#ede9fe', 'color' => '#5b21b6', 'icon' => '🛵'],
    'CONCLUDED' => ['label' => 'Concluído', 'bg' => '#dcfce7', 'color' => '#166534', 'icon' => '✔️'],
    'CANCELLED' => ['label' => 'Cancelado', 'bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => '❌'],
];

$si = $statusLabels[$order['status']] ?? ['label' => $order['status'], 'bg' => '#f1f5f9', 'color' => '#64748b', 'icon' => '❓'];

ob_start();
?>

<style>
.ifd-page { padding: 1rem; padding-bottom: 6rem; }
.ifd-back { display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-secondary, #64748b); text-decoration: none; font-size: 0.875rem; margin-bottom: 0.75rem; }

/* Header card */
.ifd-hero { background: linear-gradient(135deg, #dc2626, #b91c1c); border-radius: 1rem; padding: 1.25rem; color: #fff; margin-bottom: 1rem; }
.ifd-hero-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.5rem; }
.ifd-hero h2 { font-size: 1.125rem; font-weight: 700; margin: 0; }
.ifd-hero .sub { font-size: 0.75rem; opacity: 0.8; margin-top: 0.25rem; }
.ifd-hero-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
.ifd-hero-scheduled { display: inline-flex; align-items: center; gap: 0.25rem; background: rgba(255,255,255,0.2); padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; margin-top: 0.375rem; }

/* Action buttons */
.ifd-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
.ifd-action-btn { flex: 1; min-width: 0; display: flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.625rem 0.5rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 600; border: none; cursor: pointer; }
.ifd-action-btn.confirm { background: #dcfce7; color: #166534; }
.ifd-action-btn.ready { background: #dbeafe; color: #1e40af; }
.ifd-action-btn.dispatch { background: #e0e7ff; color: #3730a3; }
.ifd-action-btn.cancel { background: #fee2e2; color: #991b1b; }

/* Cards */
.ifd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; margin-bottom: 0.75rem; overflow: hidden; }
.ifd-card-header { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; font-weight: 600; font-size: 0.875rem; color: var(--text-primary, #1e293b); display: flex; align-items: center; gap: 0.5rem; }
.ifd-card-body { padding: 0.875rem 1rem; }

/* Items */
.ifd-item { display: flex; gap: 0.75rem; padding: 0.625rem 0; border-bottom: 1px solid #f8fafc; }
.ifd-item:last-child { border-bottom: none; }
.ifd-item-img { width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; object-fit: cover; flex-shrink: 0; }
.ifd-item-info { flex: 1; min-width: 0; }
.ifd-item-name { font-size: 0.8125rem; font-weight: 600; color: var(--text-primary, #1e293b); }
.ifd-item-obs { font-size: 0.6875rem; color: #f59e0b; margin-top: 0.125rem; }
.ifd-item-opts { display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.25rem; }
.ifd-item-opt { font-size: 0.625rem; background: #f1f5f9; color: #475569; padding: 0.125rem 0.375rem; border-radius: 0.25rem; }
.ifd-item-qty { font-size: 0.75rem; color: var(--text-secondary, #64748b); text-align: center; min-width: 1.5rem; }
.ifd-item-price { font-size: 0.8125rem; font-weight: 600; color: var(--text-primary, #1e293b); text-align: right; white-space: nowrap; }

/* Totals */
.ifd-totals { border-top: 1px solid #e2e8f0; padding-top: 0.75rem; margin-top: 0.5rem; }
.ifd-total-row { display: flex; justify-content: space-between; margin-bottom: 0.375rem; font-size: 0.8125rem; }
.ifd-total-row.discount { color: #16a34a; }
.ifd-total-row.grand { font-size: 1rem; font-weight: 700; color: var(--text-primary, #1e293b); padding-top: 0.375rem; border-top: 1px solid #e2e8f0; margin-top: 0.375rem; }

/* Info rows */
.ifd-info-row { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.5rem 0; }
.ifd-info-row .icon { width: 2rem; height: 2rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; flex-shrink: 0; }
.ifd-info-label { font-size: 0.75rem; color: var(--text-secondary, #64748b); }
.ifd-info-value { font-size: 0.8125rem; font-weight: 500; color: var(--text-primary, #1e293b); }
.ifd-info-value a { color: var(--primary, #4361ee); text-decoration: none; }

/* Timeline */
.ifd-timeline { position: relative; padding-left: 1.5rem; }
.ifd-timeline::before { content: ''; position: absolute; left: 0.5rem; top: 0.5rem; bottom: 0.5rem; width: 2px; background: #e2e8f0; }
.ifd-tl-item { position: relative; padding: 0.375rem 0 0.75rem; }
.ifd-tl-dot { position: absolute; left: -1.25rem; top: 0.5rem; width: 0.625rem; height: 0.625rem; border-radius: 50%; border: 2px solid #fff; }
.ifd-tl-label { font-size: 0.8125rem; font-weight: 500; color: var(--text-primary, #1e293b); }
.ifd-tl-time { font-size: 0.6875rem; color: var(--text-secondary, #64748b); }
.ifd-tl-reason { font-size: 0.6875rem; color: #dc2626; margin-top: 0.125rem; }

/* Link to local */
.ifd-link-local { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #f8fafc; border-radius: 0.75rem; text-decoration: none; color: var(--primary, #4361ee); font-size: 0.8125rem; font-weight: 500; margin-top: 0.5rem; }
</style>

<div class="ifd-page">
    <a href="/ifood/orders" class="ifd-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="12" x2="5" y2="12" stroke-linecap="round" stroke-linejoin="round"/><polyline points="12 19 5 12 12 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Pedidos iFood
    </a>

    <!-- Hero -->
    <div class="ifd-hero">
        <div class="ifd-hero-top">
            <div>
                <h2>Pedido #<?= htmlspecialchars($order['ifood_display_id'] ?? substr($order['ifood_order_id'], 0, 8)) ?></h2>
                <div class="sub">
                    📅 <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                </div>
                <?php if (($order['order_timing'] ?? '') === 'SCHEDULED'): ?>
                    <div class="ifd-hero-scheduled">📆 Agendado</div>
                <?php endif; ?>
            </div>
            <span class="ifd-hero-badge" style="background:<?= $si['bg'] ?>;color:<?= $si['color'] ?>;">
                <?= $si['icon'] ?> <?= $si['label'] ?>
            </span>
        </div>
    </div>

    <!-- Actions -->
    <div class="ifd-actions">
        <?php if ($order['status'] === 'PLACED'): ?>
            <button class="ifd-action-btn confirm" onclick="confirmOrder(<?= $order['id'] ?>)">✅ Confirmar</button>
        <?php endif; ?>
        <?php if ($order['status'] === 'CONFIRMED'): ?>
            <button class="ifd-action-btn ready" onclick="readyOrder(<?= $order['id'] ?>)">📦 Pronto</button>
        <?php endif; ?>
        <?php if ($order['status'] === 'READY_TO_PICKUP' && ($order['delivered_by'] ?? '') === 'MERCHANT'): ?>
            <button class="ifd-action-btn dispatch" onclick="dispatchOrder(<?= $order['id'] ?>)">🛵 Despachar</button>
        <?php endif; ?>
        <?php if (in_array($order['status'], ['PLACED', 'CONFIRMED'])): ?>
            <button class="ifd-action-btn cancel" onclick="cancelOrder(<?= $order['id'] ?>)">❌ Cancelar</button>
        <?php endif; ?>
    </div>

    <!-- Items -->
    <div class="ifd-card">
        <div class="ifd-card-header">🛒 Itens do Pedido</div>
        <div class="ifd-card-body">
            <?php foreach ($order['items'] as $item): ?>
                <div class="ifd-item">
                    <?php if (!empty($item['imageUrl'])): ?>
                        <img src="<?= htmlspecialchars($item['imageUrl']) ?>" class="ifd-item-img" alt="">
                    <?php endif; ?>
                    <div class="ifd-item-info">
                        <div class="ifd-item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <?php if (!empty($item['observations'])): ?>
                            <div class="ifd-item-obs">📝 <?= htmlspecialchars($item['observations']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['options'])): ?>
                            <div class="ifd-item-opts">
                                <?php foreach ($item['options'] as $opt): ?>
                                    <span class="ifd-item-opt"><?= htmlspecialchars(($opt['groupName'] ?? '') . ': ' . ($opt['name'] ?? '')) ?><?php if (($opt['quantity'] ?? 1) > 1): ?> x<?= $opt['quantity'] ?><?php endif; ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ifd-item-qty"><?= $item['quantity'] ?? 1 ?>x</div>
                    <div class="ifd-item-price">R$ <?= number_format($item['totalPrice'] ?? 0, 2, ',', '.') ?></div>
                </div>
            <?php endforeach; ?>

            <!-- Totals -->
            <div class="ifd-totals">
                <div class="ifd-total-row">
                    <span>Subtotal</span>
                    <span>R$ <?= number_format($order['subtotal'], 2, ',', '.') ?></span>
                </div>
                <?php if ($order['delivery_fee'] > 0): ?>
                    <div class="ifd-total-row">
                        <span>Taxa de entrega</span>
                        <span>R$ <?= number_format($order['delivery_fee'], 2, ',', '.') ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order['benefits_total'] > 0): ?>
                    <div class="ifd-total-row discount">
                        <span>Descontos</span>
                        <span>- R$ <?= number_format($order['benefits_total'], 2, ',', '.') ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order['additional_fees'] > 0): ?>
                    <div class="ifd-total-row">
                        <span>Taxas adicionais</span>
                        <span>R$ <?= number_format($order['additional_fees'], 2, ',', '.') ?></span>
                    </div>
                <?php endif; ?>
                <div class="ifd-total-row grand">
                    <span>Total</span>
                    <span>R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment -->
    <div class="ifd-card">
        <div class="ifd-card-header">💳 Pagamento</div>
        <div class="ifd-card-body">
            <?php if (!empty($order['payments']['methods'])): ?>
                <?php foreach ($order['payments']['methods'] as $method): ?>
                    <div class="ifd-info-row">
                        <div class="icon" style="background:<?= $method['type'] === 'ONLINE' ? '#dcfce7' : '#fef3c7' ?>;">
                            <?= $method['type'] === 'ONLINE' ? '✅' : '💵' ?>
                        </div>
                        <div>
                            <div class="ifd-info-value">
                                <?= $method['type'] === 'ONLINE' ? 'Pago Online' : 'Pagar na Entrega' ?>
                                - R$ <?= number_format($method['value'] ?? 0, 2, ',', '.') ?>
                            </div>
                            <div class="ifd-info-label">
                                <?= htmlspecialchars($method['method'] ?? '') ?>
                                <?php if (!empty($method['card']['brand'])): ?> - <?= htmlspecialchars($method['card']['brand']) ?><?php endif; ?>
                            </div>
                            <?php if (!empty($method['cash']['changeFor'])): ?>
                                <div class="ifd-info-label" style="color: #f59e0b;">💰 Troco para: R$ <?= number_format($method['cash']['changeFor'], 2, ',', '.') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--text-secondary, #64748b); font-size: 0.8125rem;">Informações não disponíveis</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer -->
    <div class="ifd-card">
        <div class="ifd-card-header">👤 Cliente</div>
        <div class="ifd-card-body">
            <div class="ifd-info-value" style="margin-bottom: 0.375rem;"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></div>
            <?php if ($order['customer_phone']): ?>
                <div class="ifd-info-label">
                    📞 <a href="tel:<?= htmlspecialchars($order['customer_phone']) ?>"><?= htmlspecialchars($order['customer_phone']) ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delivery Address -->
    <?php if ($order['order_type'] === 'DELIVERY' && !empty($order['delivery_address'])): ?>
        <?php $addr = $order['delivery_address']; ?>
        <div class="ifd-card">
            <div class="ifd-card-header">📍 Endereço de Entrega</div>
            <div class="ifd-card-body">
                <div class="ifd-info-value">
                    <?= htmlspecialchars($addr['streetName'] ?? '') ?>, <?= htmlspecialchars($addr['streetNumber'] ?? '') ?>
                </div>
                <?php if (!empty($addr['complement'])): ?>
                    <div class="ifd-info-label"><?= htmlspecialchars($addr['complement']) ?></div>
                <?php endif; ?>
                <div class="ifd-info-label">
                    <?= htmlspecialchars($addr['neighborhood'] ?? '') ?> - <?= htmlspecialchars($addr['city'] ?? '') ?>/<?= htmlspecialchars($addr['state'] ?? '') ?>
                </div>
                <?php if (!empty($addr['postalCode'])): ?>
                    <div class="ifd-info-label">CEP: <?= htmlspecialchars($addr['postalCode']) ?></div>
                <?php endif; ?>
                <?php if (!empty($addr['reference'])): ?>
                    <div class="ifd-info-label" style="color: #f59e0b;">ℹ️ <?= htmlspecialchars($addr['reference']) ?></div>
                <?php endif; ?>
                <?php if (!empty($addr['coordinates'])): ?>
                    <a href="https://www.google.com/maps?q=<?= $addr['coordinates']['latitude'] ?>,<?= $addr['coordinates']['longitude'] ?>" target="_blank" style="display:inline-flex;align-items:center;gap:0.375rem;margin-top:0.5rem;font-size:0.8125rem;color:var(--primary, #4361ee);text-decoration:none;">
                        🗺️ Ver no Mapa
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Delivery info -->
    <?php if ($order['delivered_by']): ?>
        <div class="ifd-card">
            <div class="ifd-card-header">🚚 Entrega</div>
            <div class="ifd-card-body">
                <div class="ifd-info-row">
                    <div class="icon" style="background:<?= $order['delivered_by'] === 'IFOOD' ? '#fee2e2' : '#dbeafe' ?>;">
                        <?= $order['delivered_by'] === 'IFOOD' ? '🔴' : '🔵' ?>
                    </div>
                    <div>
                        <div class="ifd-info-value"><?= $order['delivered_by'] === 'IFOOD' ? 'Logística iFood' : 'Entrega Própria' ?></div>
                        <?php if ($order['pickup_code']): ?>
                            <div class="ifd-info-label">Código: <strong><?= htmlspecialchars($order['pickup_code']) ?></strong></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Timeline -->
    <div class="ifd-card">
        <div class="ifd-card-header">📋 Histórico</div>
        <div class="ifd-card-body">
            <div class="ifd-timeline">
                <div class="ifd-tl-item">
                    <div class="ifd-tl-dot" style="background: #f59e0b;"></div>
                    <div class="ifd-tl-label">Pedido Recebido</div>
                    <div class="ifd-tl-time"><?= $order['ifood_created_at'] ? date('d/m H:i', strtotime($order['ifood_created_at'])) : '-' ?></div>
                </div>
                <?php if ($order['confirmed_at']): ?>
                    <div class="ifd-tl-item">
                        <div class="ifd-tl-dot" style="background: #3b82f6;"></div>
                        <div class="ifd-tl-label">Confirmado</div>
                        <div class="ifd-tl-time"><?= date('d/m H:i', strtotime($order['confirmed_at'])) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($order['ready_at']): ?>
                    <div class="ifd-tl-item">
                        <div class="ifd-tl-dot" style="background: #6366f1;"></div>
                        <div class="ifd-tl-label">Pronto</div>
                        <div class="ifd-tl-time"><?= date('d/m H:i', strtotime($order['ready_at'])) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($order['dispatched_at']): ?>
                    <div class="ifd-tl-item">
                        <div class="ifd-tl-dot" style="background: #8b5cf6;"></div>
                        <div class="ifd-tl-label">Despachado</div>
                        <div class="ifd-tl-time"><?= date('d/m H:i', strtotime($order['dispatched_at'])) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($order['concluded_at']): ?>
                    <div class="ifd-tl-item">
                        <div class="ifd-tl-dot" style="background: #22c55e;"></div>
                        <div class="ifd-tl-label">Concluído</div>
                        <div class="ifd-tl-time"><?= date('d/m H:i', strtotime($order['concluded_at'])) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($order['cancelled_at']): ?>
                    <div class="ifd-tl-item">
                        <div class="ifd-tl-dot" style="background: #ef4444;"></div>
                        <div class="ifd-tl-label">Cancelado</div>
                        <div class="ifd-tl-time"><?= date('d/m H:i', strtotime($order['cancelled_at'])) ?></div>
                        <?php if ($order['cancellation_reason']): ?>
                            <div class="ifd-tl-reason"><?= htmlspecialchars($order['cancellation_reason']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Link to local order -->
    <?php if ($order['local_order_id']): ?>
        <a href="/orders/show?id=<?= $order['local_order_id'] ?>" class="ifd-link-local">
            📋 Ver Pedido Local #<?= $order['local_order_id'] ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-left:auto;">
        </a>
    <?php endif; ?>
</div>

<!-- Toast -->
<div id="pollToast" style="display:none; position:fixed; top:1rem; left:50%; transform:translateX(-50%); z-index:9999; padding:0.625rem 1.25rem; border-radius:0.75rem; font-size:0.8125rem; font-weight:500; box-shadow:0 4px 12px rgba(0,0,0,0.15);"></div>

<script>
function showToast(msg, type) {
    var t = document.getElementById('pollToast');
    t.textContent = msg;
    t.style.display = 'block';
    t.style.background = type === 'success' ? '#dcfce7' : type === 'error' ? '#fee2e2' : '#dbeafe';
    t.style.color = type === 'success' ? '#166534' : type === 'error' ? '#991b1b' : '#1e40af';
    setTimeout(function() { t.style.display = 'none'; }, 3000);
}

function confirmOrder(id) {
    if (!confirm('Confirmar este pedido?')) return;
    apiAction('/ifood/orders/' + id + '/confirm', 'Pedido confirmado!');
}

function readyOrder(id) {
    if (!confirm('Marcar como pronto?')) return;
    apiAction('/ifood/orders/' + id + '/ready', 'Pedido pronto!');
}

function dispatchOrder(id) {
    if (!confirm('Despachar pedido?')) return;
    apiAction('/ifood/orders/' + id + '/dispatch', 'Pedido despachado!');
}

function cancelOrder(id) {
    showToast('Carregando motivos...', 'info');
    fetch('/ifood/orders/' + id + '/cancel-reasons')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var reasons = data.reasons || [];
        var reasonCode;
        if (reasons.length > 0) {
            var msg = 'Selecione o motivo:\n';
            reasons.forEach(function(r, i) { msg += (i+1) + '. ' + (r.description || r.cancelCodeId || r.code) + '\n'; });
            var choice = prompt(msg);
            if (choice === null) return;
            var idx = parseInt(choice) - 1;
            if (idx >= 0 && idx < reasons.length) {
                reasonCode = reasons[idx].cancelCodeId || reasons[idx].code || reasons[idx].cancellationCode;
            } else {
                showToast('Opção inválida', 'error'); return;
            }
        } else {
            reasonCode = prompt('Motivo do cancelamento:');
            if (reasonCode === null) return;
        }
        fetch('/ifood/orders/' + id + '/cancel', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason_code: reasonCode })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) { showToast('Pedido cancelado', 'success'); setTimeout(function(){ location.reload(); }, 1000); }
            else showToast(d.error || 'Erro', 'error');
        })
        .catch(function(e) { showToast('Erro: ' + e.message, 'error'); });
    })
    .catch(function(e) { showToast('Erro ao carregar motivos', 'error'); });
}

function apiAction(url, successMsg) {
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) { showToast(successMsg, 'success'); setTimeout(function(){ location.reload(); }, 1000); }
        else showToast(data.error || 'Erro', 'error');
    })
    .catch(function(e) { showToast('Erro: ' + e.message, 'error'); });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
