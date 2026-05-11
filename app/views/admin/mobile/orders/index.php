<?php
/**
 * Lista de Pedidos Mobile
 * Design baseado no desktop, 100% estruturado para mobile
 * 
 * @var array $company
 * @var array $u
 * @var array $orders
 * @var string $status
 * @var array $statusCounts
 * @var int $page
 * @var int $totalPages
 * @var string $pageTitle
 * @var string $activeNav
 */

// Extrai cores da configuração do sistema
$getData = function($key, $default) use ($company) {
    if (is_array($company)) {
        return $company[$key] ?? $default;
    }
    return $company->$key ?? $default;
};

// Cores do sistema configuradas em settings
$headerBgColor = $getData('menu_header_bg_color', $company['theme_color'] ?? '#4361ee');

// Gera uma versão mais escura da cor primária para hover/active
$primaryColorRgb = hexToRgb($headerBgColor);
$darkerPrimary = rgbToHex(
    max(0, $primaryColorRgb[0] - 40),
    max(0, $primaryColorRgb[1] - 40),
    max(0, $primaryColorRgb[2] - 40)
);

// Funções auxiliares para manipulação de cores
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return sscanf($hex, "%02x%02x%02x");
}

function rgbToHex($r, $g, $b) {
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

// Labels de status - apenas pendente, concluído e cancelado
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
    'preparing' => ['bg' => '#ede9fe', 'text' => htmlspecialchars($headerBgColor), 'border' => '#c4b5fd'],
    'ready'     => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'delivered' => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#fca5a5'],
    'paid'      => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'completed' => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'canceled'  => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#fca5a5'],
];

$q = $_GET['q'] ?? '';

// Buffer de conteúdo
ob_start();
?>

<style>
:root {
    --primary-color: <?= htmlspecialchars($headerBgColor) ?>;
    --primary-color-dark: <?= htmlspecialchars($darkerPrimary) ?>;
}

/* Estilos específicos para a listagem de pedidos - Mobile */
.orders-page-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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
    background: var(--primary-color);
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.search-btn:active {
    background: var(--primary-color-dark);
    transform: scale(0.95);
}

.search-btn svg {
    width: 20px;
    height: 20px;
}

.status-filter {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    overflow-x: auto;
    padding-bottom: 4px;
}

.status-filter::-webkit-scrollbar {
    height: 4px;
}

.status-filter::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}

.status-filter::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.filter-chip {
    padding: 8px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    background: white;
    color: #6b7280;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s;
}

.filter-chip:active {
    background: #f3f4f6;
}

.filter-chip.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Status pill igual desktop */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid;
}

/* Badges de origem */
.source-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 8px;
    font-size: 11px;
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

/* Tabela adaptada para mobile */
.orders-table {
    width: 100%;
}

.orders-table-header {
    display: none; /* Oculto em mobile, usar cards */
}

/* Card de pedido estilo desktop */
.order-row {
    display: flex;
    flex-direction: column;
    padding: 16px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}

.order-row:last-child {
    border-bottom: none;
}

.order-row:active {
    background: #f8fafc;
}

.order-row__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.order-row__id {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
}

.order-row__customer {
    flex: 1;
}

.order-row__customer-name {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
}

.order-row__customer-phone {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

.order-row__middle {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.order-row__items {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: #f1f5f9;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    color: #475569;
}

.order-row__items svg {
    width: 14px;
    height: 14px;
}

.order-row__date {
    font-size: 12px;
    color: #64748b;
}

.order-row__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.order-row__total {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.order-row__actions {
    display: flex;
    gap: 8px;
}

.order-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    border: 1px solid;
    cursor: pointer;
}

.order-action-btn--view {
    background: white;
    color: #475569;
    border-color: #cbd5e1;
}

.order-action-btn--view:active {
    background: #f1f5f9;
}

.order-action-btn--delete {
    background: white;
    color: #dc2626;
    border-color: #fca5a5;
}

/* Empty state */
.empty-state {
    padding: 48px 24px;
    text-align: center;
}

.empty-state__icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    padding: 12px;
    background: #f1f5f9;
    border-radius: 16px;
    color: #64748b;
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
    background: var(--primary-color);
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
    gap: 12px;
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
    background: var(--primary-color);
    border-color: var(--primary-color);
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
    background: var(--primary-color);
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

<!-- Search Bar -->
<div class="search-container">
    <form method="get" action="/orders" class="search-box">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar pedido..." class="search-input">
        <button type="submit" class="search-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-3.5-3.5"/>
            </svg>
        </button>
    </form>
</div>

<!-- Status Filter -->
<div class="status-filter">
    <a href="/orders?status=all" class="filter-chip <?= $status === 'all' ? 'active' : '' ?>">
        Todos os status
    </a>
    <a href="/orders?status=pending" class="filter-chip <?= $status === 'pending' ? 'active' : '' ?>">
        Pendente
    </a>
    <a href="/orders?status=completed" class="filter-chip <?= $status === 'completed' ? 'active' : '' ?>">
        Concluído
    </a>
    <a href="/orders?status=cancelled" class="filter-chip <?= $status === 'cancelled' ? 'active' : '' ?>">
        Cancelado
    </a>
</div>

<!-- Lista de Pedidos -->
<?php if (empty($orders)): ?>
    <!-- Empty State -->
    <div class="orders-page-card">
        <div class="empty-state">
            <div class="empty-state__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M5 7h14M7 12h10M9 17h8"/>
                </svg>
            </div>
            <h2 class="empty-state__title">Nenhum pedido encontrado</h2>
            <p class="empty-state__text">Ajuste os filtros ou crie um novo pedido agora mesmo.</p>
            <a href="/orders/create" class="empty-state__btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Novo pedido
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Tabela de Pedidos -->
    <div class="orders-page-card">
        <?php foreach ($orders as $o): ?>
            <?php 
            $st = $o['status'] ?? 'pending';
            $stColor = $statusColors[$st] ?? $statusColors['pending'];
            $stLabel = $statusLabels[$st] ?? ucfirst($st);
            $itemsCount = (int)($o['items_qty'] ?? $o['items_count'] ?? 0);
            $orderNum = $o['order_number'] ?? $o['id'] ?? 0;
            $source = $o['source'] ?? 'manual';
            ?>
            <div class="order-row">
                <div class="order-row__top">
                    <span class="order-row__id">#<?= (int)$orderNum ?></span>
                    <?php if ($source === 'ifood'): ?>
                        <span class="source-badge source-badge--ifood">iFood</span>
                    <?php elseif ($source === 'website'): ?>
                        <span class="source-badge source-badge--site">Site</span>
                    <?php else: ?>
                        <span class="source-badge source-badge--manual">Manual</span>
                    <?php endif; ?>
                    <div class="order-row__customer">
                        <div class="order-row__customer-name"><?= htmlspecialchars($o['customer_name'] ?? '-') ?></div>
                        <?php if (!empty($o['customer_phone'])): ?>
                            <div class="order-row__customer-phone"><?= htmlspecialchars($o['customer_phone']) ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="status-pill" style="background: <?= $stColor['bg'] ?>; color: <?= $stColor['text'] ?>; border-color: <?= $stColor['border'] ?>;">
                        <?= htmlspecialchars($stLabel) ?>
                    </span>
                </div>
                
                <div class="order-row__middle">
                    <span class="order-row__items">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                        </svg>
                        <?= $itemsCount ?> <?= $itemsCount === 1 ? 'item' : 'itens' ?>
                    </span>
                    <?php if (!empty($o['created_at'])): ?>
                        <span class="order-row__date"><?= htmlspecialchars($o['created_at']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="order-row__bottom">
                    <span class="order-row__total">R$ <?= number_format((float)($o['total'] ?? 0), 2, ',', '.') ?></span>
                    <div class="order-row__actions">
                        <a href="/orders/show?id=<?= (int)($o['id'] ?? 0) ?>" class="order-action-btn order-action-btn--view">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Ver
                        </a>
                        <form method="post" action="/orders/<?= (int)($o['id'] ?? 0) ?>/del" style="display: inline;" onsubmit="return confirm('Excluir pedido?')">
                            <button type="submit" class="order-action-btn order-action-btn--delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
            <?php
            $totalOrders = $statusCounts['all'] ?? count($orders);
            $perPage = 20;
            $startItem = (($page - 1) * $perPage) + 1;
            $endItem = min($page * $perPage, $totalOrders);
            ?>
            <div class="pagination-bar">
                <div class="pagination-info">
                    Mostrando <?= $startItem ?>-<?= $endItem ?> de <?= $totalOrders ?>
                </div>
                <div class="pagination-nav">
                    <!-- Anterior -->
                    <a href="<?= $page > 1 ? '/orders?status=' . $status . '&page=' . ($page - 1) . ($q ? '&q=' . urlencode($q) : '') : '#' ?>" 
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
                        <a href="/orders?status=<?= $status ?>&page=1<?= $q ? '&q=' . urlencode($q) : '' ?>" class="pagination-btn">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="pagination-btn" style="border: none;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="/orders?status=<?= $status ?>&page=<?= $i ?><?= $q ? '&q=' . urlencode($q) : '' ?>" 
                           class="pagination-btn <?= $i === $page ? 'pagination-btn--active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="pagination-btn" style="border: none;">...</span>
                        <?php endif; ?>
                        <a href="/orders?status=<?= $status ?>&page=<?= $totalPages ?><?= $q ? '&q=' . urlencode($q) : '' ?>" class="pagination-btn"><?= $totalPages ?></a>
                    <?php endif; ?>
                    
                    <!-- Próximo -->
                    <a href="<?= $page < $totalPages ? '/orders?status=' . $status . '&page=' . ($page + 1) . ($q ? '&q=' . urlencode($q) : '') : '#' ?>" 
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

<!-- FAB Novo Pedido -->
<a href="/orders/create" class="fab-new">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M12 5v14M5 12h14"/>
    </svg>
</a>

<script>
// Auto-refresh a cada 30 segundos
let refreshTimer;

function startAutoRefresh() {
    refreshTimer = setInterval(() => {
        if (!document.hidden) {
            location.reload();
        }
    }, 30000);
}

function stopAutoRefresh() {
    clearInterval(refreshTimer);
}

startAutoRefresh();

document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});
</script>

<?php
$content = ob_get_clean();

// Inclui layout
include __DIR__ . '/../layout.php';
?>
