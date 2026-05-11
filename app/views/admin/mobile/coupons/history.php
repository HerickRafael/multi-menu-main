<?php
/**
 * Histórico de Uso de Cupons - Mobile
 */
?>

<style>
.history-item {
    background: #f8fafc;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 10px;
    border: 1px solid #e2e8f0;
}
.history-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.history-code {
    font-family: monospace;
    font-size: 14px;
    font-weight: 700;
    color: #059669;
    background: #ecfdf5;
    padding: 4px 10px;
    border-radius: 8px;
}
.history-time {
    font-size: 11px;
    color: #94a3b8;
}
.history-details {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #64748b;
}
.history-detail {
    display: flex;
    align-items: center;
    gap: 4px;
}
.history-detail svg {
    width: 14px;
    height: 14px;
}
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
.stat-card-value.purple { color: var(--admin-primary-color, #7c3aed); }
</style>

<?php $activeCouponTab = 'history'; ?>
<?php require __DIR__ . '/coupons-nav.php'; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Total de Usos
        </div>
        <div class="stat-card-value green"><?= (int)($historyStats['total_uses'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--primary,#7c3aed)" stroke-width="1.5"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Clientes Únicos
        </div>
        <div class="stat-card-value purple"><?= (int)($historyStats['unique_customers'] ?? 0) ?></div>
    </div>
</div>

<!-- Lista de histórico -->
<?php if (empty($history)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
        <div class="empty-state-title">Nenhum uso registrado</div>
        <div class="empty-state-desc">O histórico aparecerá quando cupons forem utilizados</div>
    </div>
<?php else: ?>
    <?php foreach ($history as $item): ?>
    <div class="history-item">
        <div class="history-header">
            <span class="history-code"><?= htmlspecialchars($item['coupon_code']) ?></span>
            <span class="history-time"><?= htmlspecialchars($item['time_ago']) ?></span>
        </div>
        <div class="history-details">
            <div class="history-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?= htmlspecialchars($item['customer_phone'] ?? '-') ?>
            </div>
            <?php if (!empty($item['order_id'])): ?>
            <div class="history-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
                #<?= (int)$item['order_id'] ?>
            </div>
            <?php endif; ?>
            <div class="history-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?= htmlspecialchars($item['used_at']) ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
