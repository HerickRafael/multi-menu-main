<?php
/**
 * Lista de Cupons - Mobile
 */
?>

<style>
/* Stats grid - matches loyalty */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 16px;
}
.stat-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 14px;
    border: 1px solid #e2e8f0;
}
.stat-card-label {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.stat-card-label svg {
    width: 14px;
    height: 14px;
}
.stat-card-value {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
}
.stat-card-value.green { color: #059669; }
.stat-card-value.slate { color: #64748b; }
.stat-card-value.purple { color: var(--admin-primary-color, #7c3aed); }

/* Coupon list - matches loyalty */
.coupon-item {
    background: #f8fafc;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 10px;
    border: 1px solid #e2e8f0;
}
.coupon-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.coupon-code {
    font-family: monospace;
    font-size: 14px;
    font-weight: 700;
    color: #059669;
    background: #ecfdf5;
    padding: 4px 10px;
    border-radius: 8px;
}
.coupon-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 20px;
}
.coupon-badge.active {
    background: #dcfce7;
    color: #15803d;
}
.coupon-badge.used {
    background: #f1f5f9;
    color: #64748b;
}
.coupon-details {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #64748b;
}
.coupon-detail {
    display: flex;
    align-items: center;
    gap: 4px;
}
.coupon-detail svg {
    width: 14px;
    height: 14px;
}
.coupon-actions {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e2e8f0;
}
.coupon-actions a,
.coupon-actions button {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    border: 1px solid #e2e8f0;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}
.coupon-actions a svg,
.coupon-actions button svg {
    width: 14px;
    height: 14px;
}
.coupon-actions .btn-edit { color: #2563eb; }
.coupon-actions .btn-toggle { color: #d97706; }
.coupon-actions .btn-delete { color: #dc2626; border-color: #fecaca; }

/* Empty state - matches loyalty */
.empty-state {
    text-align: center;
    padding: 48px 16px;
    color: #94a3b8;
}
.empty-state svg {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    opacity: .5;
}
.empty-state-title {
    font-size: 16px;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 6px;
}
.empty-state-desc {
    font-size: 13px;
    color: #94a3b8;
}

.search-container { margin-bottom: 16px; }
.search-box {
    display: flex;
    align-items: center;
    gap: 8px;
}
.search-input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    background: #fff;
}
.search-input:focus { border-color: var(--primary, #7c3aed); }
</style>

<?php $activeCouponTab = 'list'; ?>
<?php require __DIR__ . '/coupons-nav.php'; ?>

<!-- Alerta de sucesso/erro -->
<?php if (!empty($success)): ?>
<div style="background:#dcfce7; color:#16a34a; padding:12px 16px; border-radius:12px; margin-bottom:12px; font-size:14px; font-weight:500; display:flex; align-items:center; gap:8px;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div style="background:#fee2e2; color:#dc2626; padding:12px 16px; border-radius:12px; margin-bottom:12px; font-size:14px; font-weight:500; display:flex; align-items:center; gap:8px;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-label">
            <svg viewBox="0 0 24 24" fill="currentColor" style="color:#6366f1"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
            Total
        </div>
        <div class="stat-card-value"><?= (int)$stats['total'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Ativos
        </div>
        <div class="stat-card-value green"><?= (int)$stats['active'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5"><path d="M5 13l4 4L19 7"/></svg>
            Usados
        </div>
        <div class="stat-card-value slate"><?= (int)$stats['used'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--primary,#7c3aed)" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Usos
        </div>
        <div class="stat-card-value purple"><?= (int)($stats['total_usage'] ?? 0) ?></div>
    </div>
</div>

<!-- Busca -->
<?php if (count($coupons) > 5): ?>
<div class="search-container">
    <div class="search-box">
        <input type="text" class="search-input" id="searchCoupon" placeholder="Buscar por código ou telefone..." oninput="filterCoupons()">
    </div>
</div>
<?php endif; ?>

<!-- Lista de cupons -->
<div id="couponsList">
<?php if (empty($coupons)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
            <path d="M10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
        </svg>
        <div class="empty-state-title">Nenhum cupom cadastrado</div>
        <div class="empty-state-desc">Toque em "Criar" para começar</div>
    </div>
<?php else: ?>
    <?php foreach ($coupons as $c):
        $isUsed = (int)$c['is_used'] === 1;
        $timesUsed = (int)($c['times_used'] ?? 0);
        $usageLimit = (int)($c['usage_limit'] ?? 1);
        $isExpired = $isUsed || ($usageLimit > 0 && $timesUsed >= $usageLimit);
        $phone = $c['customer_phone'] ?? '-';
        $created = date('d/m/Y', strtotime($c['created_at']));
    ?>
    <div class="coupon-item" data-code="<?= htmlspecialchars(strtolower($c['coupon_code'])) ?>" data-phone="<?= htmlspecialchars(strtolower($phone)) ?>">
        <div class="coupon-header">
            <span class="coupon-code"><?= htmlspecialchars($c['coupon_code']) ?></span>
            <?php if ($isExpired): ?>
                <span class="coupon-badge used">Esgotado</span>
            <?php else: ?>
                <span class="coupon-badge active">Ativo</span>
            <?php endif; ?>
        </div>
        <div class="coupon-details">
            <div class="coupon-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?= htmlspecialchars($phone) ?>
            </div>
            <div class="coupon-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?= number_format((float)$c['discount_percentage'], 0) ?>%
            </div>
            <div class="coupon-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?= $timesUsed ?>/<?= $usageLimit ?: '∞' ?>
            </div>
        </div>
        <div class="coupon-details" style="margin-top: 6px;">
            <div class="coupon-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?= $created ?>
            </div>
        </div>
        <div class="coupon-actions">
            <a href="/coupons/<?= (int)$c['id'] ?>/edit" class="btn-edit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Editar
            </a>
            <form method="post" action="/coupons/<?= (int)$c['id'] ?>/toggle" style="flex:1; display:flex;">
                <button type="submit" class="btn-toggle" style="flex:1;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?= $isExpired ? 'Ativar' : 'Desativar' ?>
                </button>
            </form>
            <form method="post" action="/coupons/<?= (int)$c['id'] ?>/delete" style="flex:1; display:flex;" onsubmit="return confirm('Excluir cupom?')">
                <button type="submit" class="btn-delete" style="flex:1;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Excluir
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<script>
function filterCoupons() {
    const q = document.getElementById('searchCoupon').value.toLowerCase();
    document.querySelectorAll('.coupon-item').forEach(card => {
        const code = card.dataset.code || '';
        const phone = card.dataset.phone || '';
        card.style.display = (code.includes(q) || phone.includes(q)) ? '' : 'none';
    });
}
</script>
