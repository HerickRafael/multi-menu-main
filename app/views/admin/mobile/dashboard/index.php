<?php
/**
 * Dashboard Mobile - Design baseado no desktop
 * 100% estruturado para mobile
 * 
 * @var array $company
 * @var array $u
 * @var array $stats
 * @var array $recentOrders
 * @var array $categories
 * @var array $products
 * @var string $pageTitle
 * @var string $activeNav
 */

// Normaliza variáveis
$company = is_array($company ?? null) ? $company : [];
$stats = is_array($stats ?? null) ? $stats : [];
$recentOrders = is_array($recentOrders ?? null) ? $recentOrders : [];
$categories = is_array($categories ?? null) ? $categories : [];
$products = is_array($products ?? null) ? $products : [];

// Formata valores
$todayRevenue = 'R$ ' . number_format($stats['today_revenue'] ?? 0, 2, ',', '.');
$todayOrders = $stats['today_orders'] ?? 0;
$pendingOrders = $stats['pending_orders'] ?? 0;
$activeProducts = $stats['active_products'] ?? count($products);
$categoriesCount = count($categories);
$productsCount = count($products);

// Status labels e cores - apenas pendente, concluído e cancelado
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
    'confirmed' => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'preparing' => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'ready'     => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'delivered' => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#fca5a5'],
    'paid'      => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'completed' => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#6ee7b7'],
    'canceled'  => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#fca5a5'],
];

$price = function ($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); };

// Buffer de conteúdo
ob_start();
?>

<style>
/* Dashboard Mobile - Estilo Desktop Adaptado */

/* Hero Section */
.dashboard-hero {
    position: relative;
    background: linear-gradient(135deg, var(--header-bg, #500075) 0%, #1e1b4b 100%);
    border-radius: 24px;
    padding: 20px;
    margin-bottom: 16px;
    overflow: hidden;
    color: white;
}

.dashboard-hero::before {
    content: '';
    position: absolute;
    top: -40px;
    right: -40px;
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    filter: blur(40px);
}

.dashboard-hero::after {
    content: '';
    position: absolute;
    bottom: -30px;
    left: -30px;
    width: 100px;
    height: 100px;
    background: rgba(0,0,0,0.1);
    border-radius: 50%;
    filter: blur(30px);
}

.hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 16px;
}

.hero-logo {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: rgba(255,255,255,0.1);
    padding: 2px;
    flex-shrink: 0;
}

.hero-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 14px;
}

.hero-logo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.05);
    border-radius: 14px;
    color: rgba(255,255,255,0.4);
}

.hero-info h1 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 4px;
    line-height: 1.3;
}

.hero-info p {
    font-size: 13px;
    color: rgba(255,255,255,0.8);
    margin: 0;
}

.hero-actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
}

.hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 12px;
    color: white;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
}

.hero-btn:active {
    background: rgba(255,255,255,0.15);
}

.hero-btn svg {
    width: 16px;
    height: 16px;
}

.hero-btn--primary {
    background: white;
    color: #1e293b;
    border-color: white;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.stat-card__header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.stat-card__icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-card__title {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
}

.stat-card__value {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.stat-card--primary .stat-card__icon {
    background: var(--admin-primary-soft, #ede9fe);
    color: var(--admin-primary-color, #7c3aed);
}

.stat-card--warning .stat-card__icon {
    background: #fef3c7;
    color: #d97706;
}

.stat-card--success .stat-card__icon {
    background: #d1fae5;
    color: #059669;
}

.stat-card--info .stat-card__icon {
    background: #dbeafe;
    color: #2563eb;
}

/* Quick Actions */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.quick-action {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 16px;
    text-decoration: none;
    display: block;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: transform 0.15s, box-shadow 0.15s;
}

.quick-action:active {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.quick-action__icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}

.quick-action__icon--indigo {
    background: #eef2ff;
    color: #6366f1;
}

.quick-action__icon--emerald {
    background: #d1fae5;
    color: #059669;
}

.quick-action__icon--amber {
    background: #fef3c7;
    color: #d97706;
}

.quick-action__icon--sky {
    background: #e0f2fe;
    color: #0284c7;
}

.quick-action__title {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
}

.quick-action__desc {
    font-size: 12px;
    color: #64748b;
}

/* Dashboard Cards */
.dashboard-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.dashboard-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid #f1f5f9;
}

.dashboard-card__header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.dashboard-card__icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dashboard-card__icon--indigo {
    background: #eef2ff;
    color: #6366f1;
}

.dashboard-card__icon--emerald {
    background: #d1fae5;
    color: #059669;
}

.dashboard-card__icon--sky {
    background: #e0f2fe;
    color: #0284c7;
}

.dashboard-card__title {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
}

.dashboard-card__badge {
    background: #1e293b;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 10px;
}

.dashboard-card__content {
    max-height: 280px;
    overflow-y: auto;
}

.dashboard-card__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: inherit;
}

.dashboard-card__item:last-child {
    border-bottom: none;
}

.dashboard-card__item:active {
    background: #f8fafc;
}

.dashboard-card__item-img {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.dashboard-card__item-placeholder {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    flex-shrink: 0;
}

.dashboard-card__item-info {
    flex: 1;
    min-width: 0;
}

.dashboard-card__item-name {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dashboard-card__item-meta {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

.dashboard-card__item-price {
    font-weight: 600;
    color: #1e293b;
}

.dashboard-card__item-right {
    text-align: right;
}

.dashboard-card__empty {
    padding: 32px 16px;
    text-align: center;
    color: #64748b;
    font-size: 14px;
}

/* Status pill */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid;
    white-space: nowrap;
}

/* Footer link */
.dashboard-card__footer {
    padding: 12px 16px;
    border-top: 1px solid #f1f5f9;
    text-align: center;
}

.dashboard-card__footer a {
    color: var(--header-bg);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
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

<!-- Hero Section -->
<div class="dashboard-hero">
    <div class="hero-content">
        <div class="hero-logo">
            <?php if (!empty($company['logo'])): ?>
                <img src="<?= htmlspecialchars($company['logo']) ?>" alt="Logo" onerror="this.parentElement.innerHTML='<div class=\'hero-logo-placeholder\'><svg width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'1.5\'><path d=\'M4 6h16v12H4zM8 10l3 3 2-2 3 3\'/></svg></div>'">
            <?php else: ?>
                <div class="hero-logo-placeholder">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
        <div class="hero-info">
            <h1><?= htmlspecialchars($company['name'] ?? 'Minha Empresa') ?></h1>
            <p>Categorias: <?= $categoriesCount ?> • Produtos: <?= $productsCount ?></p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/settings" class="hero-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Configurações
        </a>
        <?php 
        $publicSlug = $company['slug'] ?? '';
        $menuUrl = !empty($publicSlug) ? "/{$publicSlug}" : '#';
        ?>
        <a href="/kds" class="hero-btn hero-btn--primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            KDS Cozinha
        </a>
        <a href="<?= htmlspecialchars($menuUrl) ?>" target="_blank" class="hero-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Ver Cardápio
        </a>
    </div>
</div>

<!-- Pausa Programada Mobile -->
<?php
// Carrega status da pausa programada
require_once __DIR__ . '/../../../../services/ScheduledPauseService.php';
$pauseService = new ScheduledPauseService(db());
$pauseStatus = $pauseService->getPauseStatus((int)$company['id']);
$isPaused = $pauseStatus['is_paused'];
?>
<div id="scheduled-pause-mobile" class="pause-card <?= $isPaused ? 'pause-card--active' : '' ?>">
    <div class="pause-card__content">
        <div class="pause-card__icon">
            <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor">
                <path d="M6 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5zm4 0a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5z"/>
            </svg>
        </div>
        <div class="pause-card__info">
            <div class="pause-card__title">
                Pausa Programada
                <?php if ($isPaused): ?>
                    <span class="pause-badge">ATIVO</span>
                <?php endif; ?>
            </div>
            <div class="pause-card__desc" id="pause-status-mobile">
                <?php if ($isPaused): ?>
                    <?php if ($pauseStatus['pause_type'] === 'indefinite'): ?>
                        Loja em pausa indefinida
                    <?php else: ?>
                        Retorna em: <strong id="pause-remaining-mobile"><?= e($pauseStatus['remaining_text'] ?? 'Calculando...') ?></strong>
                    <?php endif; ?>
                <?php else: ?>
                    Pause temporariamente o recebimento de pedidos
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="pause-card__actions">
        <?php if ($isPaused): ?>
            <?php if ($pauseStatus['pause_type'] !== 'indefinite'): ?>
                <button type="button" onclick="openExtendModalMobile()" class="pause-btn pause-btn--secondary">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                    </svg>
                </button>
            <?php endif; ?>
            <button type="button" onclick="disablePauseMobile()" class="pause-btn pause-btn--success">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M11.596 8.697l-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393"/>
                </svg>
                Retomar
            </button>
        <?php else: ?>
            <button type="button" onclick="openPauseModalMobile()" class="pause-btn pause-btn--warning">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M6 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5zm4 0a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5z"/>
                </svg>
                Pausar
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__header">
            <div class="stat-card__icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <span class="stat-card__title">Pedidos Hoje</span>
        </div>
        <div class="stat-card__value"><?= $todayOrders ?></div>
    </div>

    <div class="stat-card stat-card--warning">
        <div class="stat-card__header">
            <div class="stat-card__icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <span class="stat-card__title">Pendentes</span>
        </div>
        <div class="stat-card__value"><?= $pendingOrders ?></div>
    </div>

    <div class="stat-card stat-card--success">
        <div class="stat-card__header">
            <div class="stat-card__icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <span class="stat-card__title">Faturamento</span>
        </div>
        <div class="stat-card__value"><?= $todayRevenue ?></div>
    </div>

    <div class="stat-card stat-card--info">
        <div class="stat-card__header">
            <div class="stat-card__icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
            </div>
            <span class="stat-card__title">Produtos</span>
        </div>
        <div class="stat-card__value"><?= $activeProducts ?></div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="quick-actions-grid">
    <a href="/categories/create" class="quick-action">
        <div class="quick-action__icon quick-action__icon--indigo">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </div>
        <div class="quick-action__title">Nova Categoria</div>
        <div class="quick-action__desc">Organize o cardápio</div>
    </a>

    <a href="/products/create" class="quick-action">
        <div class="quick-action__icon quick-action__icon--emerald">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </div>
        <div class="quick-action__title">Novo Produto</div>
        <div class="quick-action__desc">Simples ou combo</div>
    </a>

    <a href="/orders/create" class="quick-action">
        <div class="quick-action__icon quick-action__icon--sky">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </div>
        <div class="quick-action__title">Novo Pedido</div>
        <div class="quick-action__desc">Registrar manual</div>
    </a>

    <a href="/financial" class="quick-action">
        <div class="quick-action__icon quick-action__icon--amber">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
        </div>
        <div class="quick-action__title">Financeiro</div>
        <div class="quick-action__desc">Lucros e vendas</div>
    </a>
</div>

<!-- Pedidos Recentes -->
<div class="dashboard-card">
    <div class="dashboard-card__header">
        <div class="dashboard-card__header-left">
            <div class="dashboard-card__icon dashboard-card__icon--sky">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
            </div>
            <span class="dashboard-card__title">Pedidos Recentes</span>
        </div>
        <span class="dashboard-card__badge"><?= count($recentOrders) ?></span>
    </div>
    
    <div class="dashboard-card__content">
        <?php if (empty($recentOrders)): ?>
            <div class="dashboard-card__empty">
                Nenhum pedido ainda
            </div>
        <?php else: ?>
            <?php $ordersToShow = array_slice($recentOrders, 0, 5); ?>
            <?php foreach ($ordersToShow as $o): ?>
                <?php 
                $st = $o['status'] ?? 'pending';
                $stColor = $statusColors[$st] ?? $statusColors['pending'];
                $stLabel = $statusLabels[$st] ?? ucfirst($st);
                ?>
                <a href="/orders/show?id=<?= (int)($o['id'] ?? 0) ?>" class="dashboard-card__item">
                    <div class="dashboard-card__item-info">
                        <div class="dashboard-card__item-name">#<?= (int)($o['id'] ?? 0) ?> · <?= htmlspecialchars($o['customer_name'] ?? 'Cliente') ?></div>
                        <div class="dashboard-card__item-meta"><?= htmlspecialchars($o['created_at'] ?? '') ?></div>
                    </div>
                    <div class="dashboard-card__item-right">
                        <div class="dashboard-card__item-price"><?= $price($o['total'] ?? 0) ?></div>
                        <span class="status-pill" style="background: <?= $stColor['bg'] ?>; color: <?= $stColor['text'] ?>; border-color: <?= $stColor['border'] ?>;">
                            <?= htmlspecialchars($stLabel) ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-card__footer">
        <a href="/orders">Ver todos os pedidos →</a>
    </div>
</div>

<!-- Produtos -->
<div class="dashboard-card">
    <div class="dashboard-card__header">
        <div class="dashboard-card__header-left">
            <div class="dashboard-card__icon dashboard-card__icon--emerald">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
            </div>
            <span class="dashboard-card__title">Produtos</span>
        </div>
        <span class="dashboard-card__badge"><?= $productsCount ?></span>
    </div>
    
    <div class="dashboard-card__content">
        <?php if (empty($products)): ?>
            <div class="dashboard-card__empty">
                Nenhum produto cadastrado
            </div>
        <?php else: ?>
            <?php $productsToShow = array_slice($products, 0, 5); ?>
            <?php foreach ($productsToShow as $p): ?>
                <a href="/products/<?= (int)($p['id'] ?? 0) ?>/edit" class="dashboard-card__item">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="" class="dashboard-card__item-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="dashboard-card__item-placeholder" style="display: none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3"/>
                            </svg>
                        </div>
                    <?php else: ?>
                        <div class="dashboard-card__item-placeholder">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="dashboard-card__item-info">
                        <div class="dashboard-card__item-name"><?= htmlspecialchars($p['name'] ?? '') ?></div>
                        <div class="dashboard-card__item-meta">
                            <?php if (isset($p['promo_price']) && $p['promo_price'] !== '' && $p['promo_price'] !== null): ?>
                                <span style="text-decoration: line-through; color: #94a3b8;"><?= $price($p['price'] ?? 0) ?></span>
                                <strong style="margin-left: 4px;"><?= $price($p['promo_price']) ?></strong>
                            <?php else: ?>
                                <?= $price($p['price'] ?? 0) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-card__footer">
        <a href="/products">Ver todos os produtos →</a>
    </div>
</div>

<!-- Categorias -->
<div class="dashboard-card">
    <div class="dashboard-card__header">
        <div class="dashboard-card__header-left">
            <div class="dashboard-card__icon dashboard-card__icon--indigo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
            </div>
            <span class="dashboard-card__title">Categorias</span>
        </div>
        <span class="dashboard-card__badge"><?= $categoriesCount ?></span>
    </div>
    
    <div class="dashboard-card__content">
        <?php if (empty($categories)): ?>
            <div class="dashboard-card__empty">
                Nenhuma categoria cadastrada
            </div>
        <?php else: ?>
            <?php $categoriesToShow = array_slice($categories, 0, 5); ?>
            <?php foreach ($categoriesToShow as $c): ?>
                <a href="/categories/<?= (int)($c['id'] ?? 0) ?>/edit" class="dashboard-card__item">
                    <div class="dashboard-card__item-info">
                        <div class="dashboard-card__item-name"><?= htmlspecialchars($c['name'] ?? '') ?></div>
                        <div class="dashboard-card__item-meta">#<?= (int)($c['id'] ?? 0) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-card__footer">
        <a href="/categories">Ver todas as categorias →</a>
    </div>
</div>

<!-- FAB Novo Pedido -->
<a href="/orders/create" class="fab-new">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M12 5v14M5 12h14"/>
    </svg>
</a>

<!-- Modal de Pausa Mobile -->
<div id="pause-modal-mobile" class="mobile-modal" style="display: none;">
    <div class="mobile-modal__overlay" onclick="closePauseModalMobile()"></div>
    <div class="mobile-modal__sheet">
        <div class="mobile-modal__header">
            <h3>Pausar Recebimento</h3>
            <button type="button" onclick="closePauseModalMobile()" class="mobile-modal__close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="mobile-modal__body">
            <!-- Tipo de Pausa -->
            <div class="form-group">
                <label>Tipo de Pausa</label>
                <div class="pause-type-grid">
                    <button type="button" onclick="setPauseTypeMobile('timed')" 
                            class="pause-type-option active" data-type="timed">
                        Temporizada
                    </button>
                    <button type="button" onclick="setPauseTypeMobile('scheduled')" 
                            class="pause-type-option" data-type="scheduled">
                        Até horário
                    </button>
                    <button type="button" onclick="setPauseTypeMobile('indefinite')" 
                            class="pause-type-option" data-type="indefinite">
                        Manual
                    </button>
                </div>
            </div>
            
            <!-- Duração (timed) -->
            <div id="pause-timed-mobile" class="form-group">
                <label>Duração</label>
                <div class="duration-grid">
                    <button type="button" onclick="setDurationMobile(15)" class="duration-option" data-min="15">15 min</button>
                    <button type="button" onclick="setDurationMobile(30)" class="duration-option active" data-min="30">30 min</button>
                    <button type="button" onclick="setDurationMobile(60)" class="duration-option" data-min="60">1 hora</button>
                    <button type="button" onclick="setDurationMobile(120)" class="duration-option" data-min="120">2 horas</button>
                </div>
                <input type="number" id="custom-minutes-mobile" placeholder="Minutos personalizados" 
                       class="form-input" min="1" max="1440" onchange="setDurationMobile(this.value)">
            </div>
            
            <!-- Até horário (scheduled) -->
            <div id="pause-scheduled-mobile" class="form-group" style="display: none;">
                <label>Retomar em</label>
                <input type="datetime-local" id="pause-until-mobile" class="form-input">
            </div>
            
            <!-- Indefinite info -->
            <div id="pause-indefinite-mobile" class="form-group" style="display: none;">
                <div class="info-box">
                    A loja permanecerá pausada até você retomar manualmente.
                </div>
            </div>
            
            <!-- Motivo -->
            <div class="form-group">
                <label>Motivo (opcional)</label>
                <select id="pause-reason-select-mobile" class="form-input" onchange="updateReasonMobile()">
                    <option value="">Selecione...</option>
                    <option value="Alta demanda no momento">Alta demanda no momento</option>
                    <option value="Problemas técnicos temporários">Problemas técnicos</option>
                    <option value="Preparando pedidos em andamento">Preparando pedidos</option>
                    <option value="Estoque limitado">Estoque limitado</option>
                    <option value="Intervalo para descanso">Intervalo</option>
                </select>
                <input type="text" id="pause-reason-mobile" placeholder="Ou digite um motivo..." class="form-input" style="margin-top: 8px;">
            </div>
        </div>
        <div class="mobile-modal__footer">
            <button type="button" onclick="closePauseModalMobile()" class="btn-cancel">Cancelar</button>
            <button type="button" onclick="confirmPauseMobile()" class="btn-confirm">Ativar Pausa</button>
        </div>
    </div>
</div>

<!-- Modal de Estender Pausa Mobile -->
<div id="extend-modal-mobile" class="mobile-modal" style="display: none;">
    <div class="mobile-modal__overlay" onclick="closeExtendModalMobile()"></div>
    <div class="mobile-modal__sheet mobile-modal__sheet--small">
        <div class="mobile-modal__header">
            <h3>Estender Pausa</h3>
            <button type="button" onclick="closeExtendModalMobile()" class="mobile-modal__close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="mobile-modal__body">
            <label class="form-label">Adicionar mais tempo</label>
            <div class="extend-grid">
                <button type="button" onclick="confirmExtendMobile(15)" class="extend-option">+15 min</button>
                <button type="button" onclick="confirmExtendMobile(30)" class="extend-option">+30 min</button>
                <button type="button" onclick="confirmExtendMobile(60)" class="extend-option">+1 hora</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Pausa Card Mobile */
.pause-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.pause-card--active {
    background: #fef3c7;
    border-color: #fcd34d;
}

.pause-card__content {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.pause-card__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    flex-shrink: 0;
}

.pause-card--active .pause-card__icon {
    background: #fcd34d;
    color: #92400e;
}

.pause-card__info {
    flex: 1;
    min-width: 0;
}

.pause-card__title {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.pause-badge {
    font-size: 10px;
    font-weight: 700;
    background: #f59e0b;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
}

.pause-card__desc {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pause-card--active .pause-card__desc {
    color: #92400e;
}

.pause-card__actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.pause-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
}

.pause-btn--warning {
    background: #f59e0b;
    color: white;
}

.pause-btn--success {
    background: #10b981;
    color: white;
}

.pause-btn--secondary {
    background: #fef3c7;
    color: #92400e;
    padding: 8px;
}

/* Mobile Modal */
.mobile-modal {
    position: fixed;
    inset: 0;
    z-index: 1000;
}

.mobile-modal__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.5);
}

.mobile-modal__sheet {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-radius: 20px 20px 0 0;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

.mobile-modal__sheet--small {
    max-height: 50vh;
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

.mobile-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.mobile-modal__header h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.mobile-modal__close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    cursor: pointer;
}

.mobile-modal__body {
    padding: 20px;
}

.mobile-modal__footer {
    display: flex;
    gap: 12px;
    padding: 16px 20px;
    border-top: 1px solid #e2e8f0;
    padding-bottom: calc(16px + var(--safe-area-bottom, 0px));
}

.form-group {
    margin-bottom: 16px;
}

.form-group label,
.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 8px;
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    background: white;
}

.pause-type-grid,
.duration-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.duration-grid {
    grid-template-columns: repeat(4, 1fr);
}

.pause-type-option,
.duration-option {
    padding: 10px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    background: white;
    color: #475569;
    cursor: pointer;
}

.pause-type-option.active,
.duration-option.active {
    border-color: #6366f1;
    background: #eef2ff;
    color: #4f46e5;
}

.extend-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.extend-option {
    padding: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    background: white;
    cursor: pointer;
}

.extend-option:active {
    background: #f1f5f9;
}

.info-box {
    background: #f1f5f9;
    border-radius: 10px;
    padding: 12px;
    font-size: 13px;
    color: #475569;
}

.btn-cancel {
    flex: 1;
    padding: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    background: white;
    color: #475569;
    cursor: pointer;
}

.btn-confirm {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    background: #f59e0b;
    color: white;
    cursor: pointer;
}
</style>

<script>
// Estado da pausa mobile
let pauseTypeMobile = 'timed';
let pauseMinutesMobile = 30;

function openPauseModalMobile() {
    document.getElementById('pause-modal-mobile').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePauseModalMobile() {
    document.getElementById('pause-modal-mobile').style.display = 'none';
    document.body.style.overflow = '';
}

function openExtendModalMobile() {
    document.getElementById('extend-modal-mobile').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeExtendModalMobile() {
    document.getElementById('extend-modal-mobile').style.display = 'none';
    document.body.style.overflow = '';
}

function setPauseTypeMobile(type) {
    pauseTypeMobile = type;
    document.querySelectorAll('.pause-type-option').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`.pause-type-option[data-type="${type}"]`).classList.add('active');
    
    document.getElementById('pause-timed-mobile').style.display = type === 'timed' ? 'block' : 'none';
    document.getElementById('pause-scheduled-mobile').style.display = type === 'scheduled' ? 'block' : 'none';
    document.getElementById('pause-indefinite-mobile').style.display = type === 'indefinite' ? 'block' : 'none';
}

function setDurationMobile(minutes) {
    pauseMinutesMobile = parseInt(minutes);
    document.querySelectorAll('.duration-option').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.querySelector(`.duration-option[data-min="${minutes}"]`);
    if (activeBtn) activeBtn.classList.add('active');
}

function updateReasonMobile() {
    const select = document.getElementById('pause-reason-select-mobile');
    const input = document.getElementById('pause-reason-mobile');
    if (select.value) input.value = select.value;
}

async function confirmPauseMobile() {
    const reason = document.getElementById('pause-reason-mobile').value || 
                   document.getElementById('pause-reason-select-mobile').value || 
                   'Estamos em pausa no momento';
    
    let payload = { type: pauseTypeMobile, reason };
    
    if (pauseTypeMobile === 'timed') {
        payload.minutes = pauseMinutesMobile;
    } else if (pauseTypeMobile === 'scheduled') {
        const datetime = document.getElementById('pause-until-mobile').value;
        if (!datetime) {
            alert('Selecione a data/hora de retorno');
            return;
        }
        payload.until = datetime.replace('T', ' ') + ':00';
    }
    
    try {
        const response = await fetch('/pause/enable', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao ativar pausa');
        }
    } catch (error) {
        alert('Erro de conexão');
    }
}

async function disablePauseMobile() {
    if (!confirm('Deseja retomar o atendimento?')) return;
    
    try {
        const response = await fetch('/pause/disable', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao desativar pausa');
        }
    } catch (error) {
        alert('Erro de conexão');
    }
}

async function confirmExtendMobile(minutes) {
    try {
        const response = await fetch('/pause/extend', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ minutes })
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao estender pausa');
        }
    } catch (error) {
        alert('Erro de conexão');
    }
}

// Atualização do tempo restante
<?php if ($isPaused && $pauseStatus['pause_type'] !== 'indefinite'): ?>
setInterval(async () => {
    try {
        const response = await fetch('/pause/status', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (data.success && data.data.is_paused) {
            const el = document.getElementById('pause-remaining-mobile');
            if (el && data.data.remaining_text) {
                el.textContent = data.data.remaining_text;
            }
        } else if (!data.data.is_paused) {
            location.reload();
        }
    } catch (e) {}
}, 30000);
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();

// Inclui layout
include __DIR__ . '/../layout.php';
?>
