<?php
/**
 * Lista de Clientes Mobile
 * Design baseado no desktop, 100% estruturado para mobile
 * 
 * @var array $company
 * @var array $customers
 * @var array $stats
 * @var string $search
 * @var int $page
 * @var int $totalPages
 * @var int $totalItems
 * @var string $success
 * @var string $error
 */

// Buffer de conteúdo
ob_start();
?>

<style>
/* Clientes Mobile - Estilo Desktop Adaptado */

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.stat-card__header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.stat-card__icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-card__icon--blue {
    background: #dbeafe;
    color: #2563eb;
}

.stat-card__icon--green {
    background: #d1fae5;
    color: #059669;
}

.stat-card__icon--purple {
    background: var(--admin-primary-soft, #ede9fe);
    color: var(--admin-primary-color, #7c3aed);
}

.stat-card__value {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
}

.stat-card__label {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}

/* Search Bar Design */
.search-container {
    margin-bottom: 16px;
}

.search-box {
    position: relative;
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
    background: white;
    color: #1f2937;
}

.search-input::placeholder {
    color: #9ca3af;
}

.search-input:focus {
    outline: none;
    border-color: #d1d5db;
}

.search-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--admin-primary-color, #7c3aed);
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.search-btn:active {
    opacity: 0.85;
    transform: scale(0.95);
}

.search-btn svg {
    width: 20px;
    height: 20px;
}

/* Alert */
.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.alert--success {
    background: #d1fae5;
    border: 1px solid #6ee7b7;
    color: #065f46;
}

.alert--error {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

/* Card de listagem */
.customers-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

/* Customer Row */
.customer-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: inherit;
}

.customer-row:last-child {
    border-bottom: none;
}

.customer-row:active {
    background: #f8fafc;
}

.customer-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--header-bg, #500075), #1e1b4b);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.customer-info {
    flex: 1;
    min-width: 0;
}

.customer-name {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0 0 2px;
}

.customer-phone {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

.customer-phone svg {
    width: 14px;
    height: 14px;
    color: #22c55e;
}

.customer-meta {
    text-align: right;
}

.customer-orders {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    background: #dbeafe;
    color: #1e40af;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 4px;
}

.customer-total {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}

.customer-chevron {
    width: 20px;
    height: 20px;
    color: #94a3b8;
    flex-shrink: 0;
}

/* Empty state */
.empty-state {
    padding: 48px 24px;
    text-align: center;
}

.empty-state__icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    color: #cbd5e1;
}

.empty-state__title {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.empty-state__text {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 16px;
}

.empty-state__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--header-bg);
    color: white;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
}

/* Paginação */
.pagination-bar {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.pagination-info {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #64748b;
}

.pagination-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.pagination-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 8px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: white;
    font-size: 14px;
    color: #475569;
    text-decoration: none;
}

.pagination-btn:active {
    background: #f1f5f9;
}

.pagination-btn--active {
    background: var(--header-bg);
    border-color: var(--header-bg);
    color: white;
}

.pagination-btn--disabled {
    opacity: 0.5;
    pointer-events: none;
}

/* FAB */
.fab-new {
    position: fixed;
    bottom: calc(var(--bottom-nav-height, 60px) + var(--safe-area-bottom, 0px) + 16px);
    right: 16px;
    width: 56px;
    height: 56px;
    background: var(--header-bg);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(91, 33, 182, 0.4);
    z-index: 50;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.fab-new:active {
    transform: scale(0.95);
}

.fab-new svg {
    width: 24px;
    height: 24px;
}
</style>

<!-- Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--blue">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value"><?= number_format((int)($stats['total_customers'] ?? $stats['total'] ?? 0), 0, ',', '.') ?></div>
        <div class="stat-card__label">Total</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--green">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <path d="M20 8v6m3-3h-6"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value"><?= number_format((int)($stats['new_customers_30d'] ?? $stats['new_30d'] ?? 0), 0, ',', '.') ?></div>
        <div class="stat-card__label">Novos (30d)</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--purple">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value"><?= number_format((int)($stats['active_7d'] ?? 0), 0, ',', '.') ?></div>
        <div class="stat-card__label">Ativos (7d)</div>
    </div>
</div>

<!-- Search Bar -->
<div class="search-container">
    <form method="get" action="/customers" class="search-box">
        <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Buscar por nome ou WhatsApp..." class="search-input">
        <button type="submit" class="search-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-3.5-3.5"/>
            </svg>
        </button>
    </form>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert--success">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <path d="M22 4L12 14.01l-3-3"/>
        </svg>
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert--error">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <path d="M15 9l-6 6m0-6l6 6"/>
        </svg>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Lista de Clientes -->
<?php if (empty($customers)): ?>
    <div class="customers-card">
        <div class="empty-state">
            <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <h2 class="empty-state__title">Nenhum cliente encontrado</h2>
            <p class="empty-state__text">
                <?= !empty($search) ? 'Nenhum resultado para a busca' : 'Cadastre seu primeiro cliente' ?>
            </p>
            <?php if (empty($search)): ?>
                <a href="/customers/create" class="empty-state__btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Novo Cliente
                </a>
            <?php else: ?>
                <a href="/customers" class="empty-state__btn">Limpar busca</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="customers-card">
        <?php foreach ($customers as $c): ?>
            <a href="/customers/<?= (int)$c['id'] ?>" class="customer-row">
                <div class="customer-avatar">
                    <?= strtoupper(mb_substr($c['name'] ?? 'C', 0, 1)) ?>
                </div>
                
                <div class="customer-info">
                    <h3 class="customer-name"><?= htmlspecialchars($c['name'] ?? 'Sem nome') ?></h3>
                    <p class="customer-phone">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <?= htmlspecialchars($c['whatsapp'] ?? 'Sem telefone') ?>
                    </p>
                </div>
                
                <div class="customer-meta">
                    <?php if ((int)($c['total_orders'] ?? 0) > 0): ?>
                        <div class="customer-orders"><?= (int)$c['total_orders'] ?></div>
                        <div class="customer-total">R$ <?= number_format((float)($c['total_spent'] ?? 0), 2, ',', '.') ?></div>
                    <?php else: ?>
                        <div class="customer-orders" style="background: #f1f5f9; color: #64748b;">0</div>
                        <div class="customer-total" style="color: #94a3b8;">Sem pedidos</div>
                    <?php endif; ?>
                </div>
                
                <svg class="customer-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </a>
        <?php endforeach; ?>
        
        <!-- Paginação -->
        <?php if (($totalPages ?? 1) > 1): ?>
            <?php
            $perPage = 10;
            $startItem = (($page - 1) * $perPage) + 1;
            $endItem = min($page * $perPage, $totalItems ?? count($customers));
            ?>
            <div class="pagination-bar">
                <div class="pagination-info">
                    Mostrando <?= $startItem ?>-<?= $endItem ?> de <?= $totalItems ?? count($customers) ?>
                </div>
                <div class="pagination-nav">
                    <!-- Anterior -->
                    <a href="<?= $page > 1 ? '/customers?page=' . ($page - 1) . (!empty($search) ? '&q=' . urlencode($search) : '') : '#' ?>" 
                       class="pagination-btn <?= $page <= 1 ? 'pagination-btn--disabled' : '' ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </a>
                    
                    <!-- Números -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <a href="/customers?page=1<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="pagination-btn">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="pagination-btn" style="border: none;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="/customers?page=<?= $i ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
                           class="pagination-btn <?= $i === $page ? 'pagination-btn--active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="pagination-btn" style="border: none;">...</span>
                        <?php endif; ?>
                        <a href="/customers?page=<?= $totalPages ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="pagination-btn"><?= $totalPages ?></a>
                    <?php endif; ?>
                    
                    <!-- Próximo -->
                    <a href="<?= $page < $totalPages ? '/customers?page=' . ($page + 1) . (!empty($search) ? '&q=' . urlencode($search) : '') : '#' ?>" 
                       class="pagination-btn <?= $page >= $totalPages ? 'pagination-btn--disabled' : '' ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- FAB Novo Cliente -->
<a href="/customers/create" class="fab-new">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M12 5v14M5 12h14"/>
    </svg>
</a>

<?php
$content = ob_get_clean();

// Inclui layout
include __DIR__ . '/../layout.php';
?>
