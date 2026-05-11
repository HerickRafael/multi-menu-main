<?php
/**
 * iFood Orders - Mobile
 */
$activeNav = 'orders';

$statusLabels = [
    'PLACED' => ['label' => 'Novo', 'bg' => '#fef3c7', 'color' => '#92400e'],
    'CONFIRMED' => ['label' => 'Confirmado', 'bg' => '#dbeafe', 'color' => '#1e40af'],
    'READY_TO_PICKUP' => ['label' => 'Pronto', 'bg' => '#e0e7ff', 'color' => '#3730a3'],
    'DISPATCHED' => ['label' => 'Em Entrega', 'bg' => '#ede9fe', 'color' => '#5b21b6'],
    'CONCLUDED' => ['label' => 'Concluído', 'bg' => '#dcfce7', 'color' => '#166534'],
    'CANCELLED' => ['label' => 'Cancelado', 'bg' => '#fee2e2', 'color' => '#991b1b'],
];

ob_start();
?>

<style>
.ifo-page { padding: 1rem; padding-bottom: 6rem; }
.ifo-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.ifo-back { display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-secondary, #64748b); text-decoration: none; font-size: 0.875rem; }
.ifo-title { font-size: 1.125rem; font-weight: 700; color: var(--text-primary, #1e293b); }
.ifo-header-actions { display: flex; gap: 0.5rem; }
.ifo-btn-sm { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 0.75rem; border-radius: 0.625rem; font-size: 0.75rem; font-weight: 500; border: 1px solid #e2e8f0; background: #fff; color: var(--text-primary, #1e293b); cursor: pointer; text-decoration: none; }
.ifo-btn-sm svg { width: 0.875rem; height: 0.875rem; }

/* Filters */
.ifo-filters { display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.5rem; margin-bottom: 1rem; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
.ifo-filters::-webkit-scrollbar { display: none; }
.ifo-filter-chip { flex-shrink: 0; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-decoration: none; border: 1px solid #e2e8f0; background: #fff; color: var(--text-secondary, #64748b); white-space: nowrap; }
.ifo-filter-chip.active { background: var(--primary, #4361ee); color: #fff; border-color: var(--primary, #4361ee); }

/* Empty */
.ifo-empty { text-align: center; padding: 3rem 1rem; color: var(--text-secondary, #64748b); }
.ifo-empty svg { width: 3rem; height: 3rem; margin: 0 auto 1rem; opacity: 0.5; }
.ifo-empty p { font-size: 0.875rem; margin-bottom: 1rem; }
.ifo-empty-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.75rem; background: var(--primary, #4361ee); color: #fff; border: none; font-size: 0.875rem; font-weight: 500; cursor: pointer; }

/* Order card */
.ifo-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 0.875rem; margin-bottom: 0.75rem; text-decoration: none; display: block; color: inherit; }
.ifo-card-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.5rem; }
.ifo-card-id { font-weight: 700; font-size: 0.9375rem; color: var(--text-primary, #1e293b); }
.ifo-card-local { font-size: 0.6875rem; color: var(--text-secondary, #64748b); }
.ifo-badge { display: inline-flex; padding: 0.1875rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 600; }
.ifo-card-mid { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.375rem; }
.ifo-card-customer { font-size: 0.8125rem; color: var(--text-primary, #1e293b); font-weight: 500; }
.ifo-card-amount { font-size: 0.9375rem; font-weight: 700; color: var(--text-primary, #1e293b); }
.ifo-card-bottom { display: flex; align-items: center; justify-content: space-between; }
.ifo-card-type { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.6875rem; font-weight: 500; padding: 0.125rem 0.5rem; border-radius: 9999px; }
.ifo-card-type.delivery { background: #dbeafe; color: #1e40af; }
.ifo-card-type.takeout { background: #fef3c7; color: #92400e; }
.ifo-card-date { font-size: 0.6875rem; color: var(--text-secondary, #64748b); }
.ifo-card-chevron { color: #cbd5e1; }
</style>

<div class="ifo-page">
    <!-- Header -->
    <div class="ifo-header">
        <div>
            <a href="/ifood/config" class="ifo-back">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                iFood
            </a>
            <h1 class="ifo-title">Pedidos iFood</h1>
        </div>
        <div class="ifo-header-actions">
            <button type="button" class="ifo-btn-sm" onclick="pollEvents()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M23 4v6h-6" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 20v-6h6" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Atualizar
            </button>
            <a href="/ifood/config" class="ifo-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="ifo-filters">
        <a href="/ifood/orders" class="ifo-filter-chip <?= !$currentStatus ? 'active' : '' ?>">Todos</a>
        <?php foreach ($statusLabels as $status => $info): ?>
            <a href="/ifood/orders?status=<?= $status ?>" class="ifo-filter-chip <?= $currentStatus === $status ? 'active' : '' ?>"><?= $info['label'] ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Orders -->
    <?php if (empty($orders)): ?>
        <div class="ifo-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <p>Nenhum pedido encontrado</p>
            <button type="button" class="ifo-empty-btn" onclick="pollEvents()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Buscar Pedidos
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php $si = $statusLabels[$order['status']] ?? ['label' => $order['status'], 'bg' => '#f1f5f9', 'color' => '#64748b']; ?>
            <a href="/ifood/orders/<?= $order['id'] ?>" class="ifo-card">
                <div class="ifo-card-top">
                    <div>
                        <div class="ifo-card-id">#<?= htmlspecialchars($order['ifood_display_id'] ?? substr($order['ifood_order_id'], 0, 8)) ?></div>
                        <?php if ($order['local_order_id']): ?>
                            <div class="ifo-card-local">Local: #<?= $order['local_order_id'] ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="ifo-badge" style="background:<?= $si['bg'] ?>;color:<?= $si['color'] ?>;"><?= $si['label'] ?></span>
                </div>
                <div class="ifo-card-mid">
                    <span class="ifo-card-customer"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></span>
                    <span class="ifo-card-amount">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></span>
                </div>
                <div class="ifo-card-bottom">
                    <?php if ($order['order_type'] === 'DELIVERY'): ?>
                        <span class="ifo-card-type delivery">🛵 Entrega</span>
                    <?php elseif ($order['order_type'] === 'TAKEOUT'): ?>
                        <span class="ifo-card-type takeout">🛍️ Retirada</span>
                    <?php else: ?>
                        <span class="ifo-card-type" style="background:#f1f5f9;color:#64748b;"><?= htmlspecialchars($order['order_type']) ?></span>
                    <?php endif; ?>
                    <span class="ifo-card-date"><?= date('d/m H:i', strtotime($order['created_at'])) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Poll toast -->
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

function pollEvents() {
    showToast('Buscando pedidos...', 'info');

    fetch('/ifood/poll', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.events > 0) {
            showToast(data.events + ' evento(s) processado(s)!', 'success');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast('Nenhum pedido novo', 'info');
        }
    })
    .catch(function(e) {
        showToast('Erro: ' + e.message, 'error');
    });
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
