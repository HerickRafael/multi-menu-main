<?php $hideTopbar = false; ?>
<?php include __DIR__ . '/../layout.php'; ?>

<div class="super-admin-content">
    <!-- Flash Message -->
    <?php if (!empty($flash)): ?>
        <div class="flash-message flash-<?= htmlspecialchars($flash['type']); ?>">
            <span><?= htmlspecialchars($flash['message']); ?></span>
            <button type="button" class="flash-close" onclick="this.parentElement.style.display='none';">×</button>
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1>Dashboard Global</h1>
        <p>Visão geral operacional de todas as lojas</p>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <!-- Total Lojas -->
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: rgba(59, 130, 246, 0.1);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="kpi-content">
                <p class="kpi-label">Total de Lojas</p>
                <p class="kpi-value"><?= $summary['total_stores']; ?></p>
                <p class="kpi-hint">Todas as lojas cadastradas</p>
            </div>
        </div>

        <!-- Lojas Online -->
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: rgba(34, 197, 94, 0.1);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                </svg>
            </div>
            <div class="kpi-content">
                <p class="kpi-label">Lojas Online</p>
                <p class="kpi-value"><?= $summary['online_stores']; ?></p>
                <p class="kpi-hint">Ativas e funcionando</p>
            </div>
        </div>

        <!-- Lojas Offline -->
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: rgba(239, 68, 68, 0.1);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="kpi-content">
                <p class="kpi-label">Lojas Offline</p>
                <p class="kpi-value"><?= $summary['offline_stores']; ?></p>
                <p class="kpi-hint">Sem atividade nas últimas 2h</p>
            </div>
        </div>

        <!-- Pedidos Ativos -->
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: rgba(147, 51, 234, 0.1);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 3H5a2 2 0 0 0-2 2v0a2 2 0 0 0 2 2h0a2 2 0 0 1 2 2v0a2 2 0 0 1-2 2H5a2 2 0 0 0-2 2v0a2 2 0 0 0 2 2h4"></path>
                    <line x1="16" y1="3" x2="16" y2="17"></line>
                </svg>
            </div>
            <div class="kpi-content">
                <p class="kpi-label">Pedidos Ativos</p>
                <p class="kpi-value"><?= $summary['global_active_orders']; ?></p>
                <p class="kpi-hint">Pendentes e em processamento</p>
            </div>
        </div>

        <!-- Lojas Suspensas -->
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: rgba(251, 146, 60, 0.1);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 9v2m0 4v2m1.71-16.04a10 10 0 1 0 14.14 14.14 10 10 0 0 0-14.14-14.14"></path>
                </svg>
            </div>
            <div class="kpi-content">
                <p class="kpi-label">Suspensas</p>
                <p class="kpi-value"><?= $summary['suspended_stores']; ?></p>
                <p class="kpi-hint">Aguardando ação</p>
            </div>
        </div>

        <!-- Receita Hoje -->
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: rgba(168, 85, 247, 0.1);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="kpi-content">
                <p class="kpi-label">Receita Hoje</p>
                <p class="kpi-value">R$ <?= number_format($summary['global_revenue_today'], 2, ',', '.'); ?></p>
                <p class="kpi-hint">Total de pedidos confirmados</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Ações Rápidas</h2>
        <div class="action-grid">
            <a href="<?= base_url('superadmin/stores'); ?>" class="action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Gestão de Lojas</span>
            </a>
            <a href="<?= base_url('superadmin/orders/global'); ?>" class="action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span>Pedidos (Fase 3)</span>
            </a>
            <a href="<?= base_url('superadmin/audit'); ?>" class="action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"></path>
                </svg>
                <span>Auditoria (Fase 2)</span>
            </a>
            <a href="<?= base_url('superadmin/logs'); ?>" class="action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path>
                </svg>
                <span>Logs (Fase 3)</span>
            </a>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="dashboard-footer">
        <p>Última atualização: <?= date('d/m/Y H:i:s'); ?></p>
    </div>
</div>

<style>
.dashboard-header {
    margin-bottom: 2rem;
}

.dashboard-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.dashboard-header p {
    font-size: 0.875rem;
    color: #6b7280;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.kpi-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.kpi-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3b82f6;
    flex-shrink: 0;
}

.kpi-content {
    flex: 1;
}

.kpi-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    margin: 0 0 0.5rem 0;
}

.kpi-value {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
}

.kpi-hint {
    font-size: 0.75rem;
    color: #9ca3af;
    margin: 0;
}

.quick-actions {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.quick-actions h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: #1f2937;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    text-decoration: none;
    color: #1f2937;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: #e5e7eb;
    border-color: #d1d5db;
    color: #111827;
}

.action-btn svg {
    color: #6b7280;
}

.dashboard-footer {
    text-align: center;
    padding: 1rem;
    color: #9ca3af;
    font-size: 0.875rem;
}

.flash-message {
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 0.375rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: slideDown 0.3s ease;
}

.flash-success {
    background-color: rgba(34, 197, 94, 0.1);
    border: 1px solid #86efac;
    color: #166534;
}

.flash-error {
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid #fca5a5;
    color: #991b1b;
}

.flash-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: inherit;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-1rem);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
