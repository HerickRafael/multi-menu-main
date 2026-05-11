<?php
/**
 * Analytics Mobile - Dashboard completo
 */
$fmt = function($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); };

// Preparar dados do gráfico de vendas por dia
$chartLabels = [];
$chartValues = [];
foreach ($salesByDay as $day) {
    $chartLabels[] = date('d/m', strtotime($day['date']));
    $chartValues[] = (float)$day['revenue'];
}

// Hora de pico
$peakHour = !empty($salesByHour) ? $salesByHour[0] : null;

// Melhor dia da semana
$bestWeekday = null;
$maxOrders = 0;
foreach ($salesByWeekday as $wd) {
    if ((int)$wd['orders'] > $maxOrders) {
        $maxOrders = (int)$wd['orders'];
        $bestWeekday = $wd;
    }
}
?>

<style>
.period-tabs {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding: 2px;
    margin-bottom: 16px;
    -webkit-overflow-scrolling: touch;
}
.period-tab {
    flex-shrink: 0;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    background: white;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    white-space: nowrap;
}
.period-tab.active {
    background: var(--primary, #7c3aed);
    color: white;
    border-color: var(--primary, #7c3aed);
}

.analytics-hero {
    background: linear-gradient(135deg, var(--header-bg, #7c3aed) 0%, #1e1b4b 100%);
    border-radius: 20px;
    padding: 20px;
    color: white;
    margin-bottom: 16px;
}
.analytics-hero-label {
    font-size: 13px;
    opacity: .8;
    margin-bottom: 4px;
}
.analytics-hero-value {
    font-size: 32px;
    font-weight: 800;
}
.analytics-hero-change {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    font-weight: 600;
    margin-top: 6px;
    padding: 3px 10px;
    border-radius: 20px;
}
.change-up { background: rgba(34,197,94,.2); color: #86efac; }
.change-down { background: rgba(239,68,68,.2); color: #fca5a5; }
.analytics-hero-sub {
    display: flex;
    gap: 20px;
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid rgba(255,255,255,.15);
}
.hero-sub-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.hero-sub-label {
    font-size: 11px;
    opacity: .7;
}
.hero-sub-value {
    font-size: 18px;
    font-weight: 700;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}
.stat-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.stat-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}
.stat-card-value {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
}
.stat-card-label {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    margin: 20px 0 12px;
}

.chart-container {
    background: white;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    margin-bottom: 16px;
    overflow: hidden;
}
.chart-title {
    font-size: 14px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 12px;
}
.mini-chart {
    display: flex;
    align-items: flex-end;
    gap: 3px;
    height: 100px;
    padding: 0 4px;
}
.mini-bar {
    flex: 1;
    border-radius: 4px 4px 0 0;
    background: var(--primary-light, rgba(124,58,237,.15));
    min-height: 4px;
    position: relative;
    transition: background .2s;
}
.mini-bar:hover, .mini-bar.highlight {
    background: var(--primary, #7c3aed);
}
.chart-labels {
    display: flex;
    gap: 3px;
    padding: 6px 4px 0;
}
.chart-label {
    flex: 1;
    text-align: center;
    font-size: 9px;
    color: #94a3b8;
    overflow: hidden;
    text-overflow: ellipsis;
}

.top-products {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    overflow: hidden;
    margin-bottom: 16px;
}
.top-product-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.top-product-item:last-child { border-bottom: none; }
.top-product-rank {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
}
.rank-1 { background: #fef3c7; color: #92400e; }
.rank-2 { background: #e2e8f0; color: #475569; }
.rank-3 { background: #fed7aa; color: #9a3412; }
.rank-default { background: #f1f5f9; color: #94a3b8; }
.top-product-info { flex: 1; min-width: 0; }
.top-product-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.top-product-meta {
    font-size: 12px;
    color: #94a3b8;
}
.top-product-revenue {
    font-size: 14px;
    font-weight: 700;
    color: #16a34a;
    flex-shrink: 0;
}

.payment-list {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    overflow: hidden;
    margin-bottom: 16px;
}
.payment-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.payment-item:last-child { border-bottom: none; }
.payment-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.payment-info { flex: 1; }
.payment-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}
.payment-bar {
    height: 4px;
    border-radius: 2px;
    background: #f1f5f9;
    margin-top: 6px;
    overflow: hidden;
}
.payment-bar-fill {
    height: 100%;
    border-radius: 2px;
    background: var(--primary, #7c3aed);
}
.payment-pct {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    flex-shrink: 0;
}

.weekday-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    margin-bottom: 16px;
}
.weekday-item {
    background: white;
    border-radius: 12px;
    padding: 10px 4px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.weekday-item.best {
    background: var(--primary, #7c3aed);
    color: white;
}
.weekday-name {
    font-size: 10px;
    font-weight: 600;
    margin-bottom: 4px;
}
.weekday-item.best .weekday-name { color: rgba(255,255,255,.8); }
.weekday-orders {
    font-size: 15px;
    font-weight: 700;
}
</style>

<!-- Filtro de Período -->
<div class="period-tabs">
    <a href="/analytics?period=today" class="period-tab <?= $period === 'today' ? 'active' : '' ?>">Hoje</a>
    <a href="/analytics?period=7" class="period-tab <?= $period === '7' ? 'active' : '' ?>">7 dias</a>
    <a href="/analytics?period=30" class="period-tab <?= $period === '30' ? 'active' : '' ?>">30 dias</a>
    <a href="/analytics?period=month" class="period-tab <?= $period === 'month' ? 'active' : '' ?>">Este mês</a>
    <a href="/analytics?period=90" class="period-tab <?= $period === '90' ? 'active' : '' ?>">90 dias</a>
    <a href="/analytics?period=year" class="period-tab <?= $period === 'year' ? 'active' : '' ?>">Este ano</a>
</div>

<!-- Hero - Faturamento -->
<div class="analytics-hero">
    <div class="analytics-hero-label">Faturamento no período</div>
    <div class="analytics-hero-value"><?= $fmt($summary['total_revenue']) ?></div>
    <?php if ($summary['revenue_change'] != 0): ?>
    <span class="analytics-hero-change <?= $summary['revenue_change'] > 0 ? 'change-up' : 'change-down' ?>">
        <?php if ($summary['revenue_change'] > 0): ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 9 11"/><polyline points="16 7 22 7 22 13"/></svg>
            +<?= abs($summary['revenue_change']) ?>%
        <?php else: ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 17 13.5 8.5 9 13"/><polyline points="16 17 22 17 22 11"/></svg>
            <?= $summary['revenue_change'] ?>%
        <?php endif; ?>
    </span>
    <?php endif; ?>
    <div class="analytics-hero-sub">
        <div class="hero-sub-item">
            <span class="hero-sub-label">Pedidos</span>
            <span class="hero-sub-value"><?= $summary['total_orders'] ?></span>
        </div>
        <div class="hero-sub-item">
            <span class="hero-sub-label">Ticket médio</span>
            <span class="hero-sub-value"><?= $fmt($summary['avg_ticket']) ?></span>
        </div>
        <div class="hero-sub-item">
            <span class="hero-sub-label">Clientes</span>
            <span class="hero-sub-value"><?= $summary['new_customers'] ?></span>
        </div>
    </div>
</div>

<!-- Stats Cards - Hoje -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:#dcfce7; color:#16a34a;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        </div>
        <div class="stat-card-value"><?= $fmt($todaySales['revenue']) ?></div>
        <div class="stat-card-label">Vendas Hoje</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:#dbeafe; color:#2563eb;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        </div>
        <div class="stat-card-value"><?= $todaySales['orders'] ?></div>
        <div class="stat-card-label">Pedidos Hoje</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:#fef3c7; color:#d97706;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        </div>
        <div class="stat-card-value"><?= $fmt($todaySales['avg_ticket']) ?></div>
        <div class="stat-card-label">Ticket Hoje</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:#fce7f3; color:#db2777;">
            <?php if ($peakHour): ?>
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <?php endif; ?>
        </div>
        <div class="stat-card-value"><?= $peakHour ? $peakHour['hour'] . 'h' : '-' ?></div>
        <div class="stat-card-label">Horário de Pico</div>
    </div>
</div>

<!-- Gráfico de Vendas por Dia -->
<?php if (!empty($salesByDay)): ?>
<div class="section-title">Vendas por Dia</div>
<div class="chart-container">
    <?php
    $maxRevenue = max(array_column($salesByDay, 'revenue'));
    $showEvery = max(1, (int)ceil(count($salesByDay) / 10));
    ?>
    <div class="mini-chart">
        <?php foreach ($salesByDay as $i => $day): ?>
        <div class="mini-bar" style="height: <?= $maxRevenue > 0 ? max(4, ((float)$day['revenue'] / $maxRevenue) * 100) : 4 ?>%"
             title="<?= date('d/m', strtotime($day['date'])) ?>: <?= $fmt($day['revenue']) ?>"></div>
        <?php endforeach; ?>
    </div>
    <div class="chart-labels">
        <?php foreach ($salesByDay as $i => $day): ?>
        <span class="chart-label"><?= ($i % $showEvery === 0) ? date('d/m', strtotime($day['date'])) : '' ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Vendas por Dia da Semana -->
<?php if (!empty($salesByWeekday)): ?>
<div class="section-title">Vendas por Dia da Semana</div>
<div class="weekday-grid">
    <?php
    $weekdayMap = [];
    foreach ($salesByWeekday as $wd) $weekdayMap[$wd['weekday']] = $wd;
    $dayNames = [1 => 'Dom', 2 => 'Seg', 3 => 'Ter', 4 => 'Qua', 5 => 'Qui', 6 => 'Sex', 7 => 'Sáb'];
    foreach ($dayNames as $num => $name):
        $wd = $weekdayMap[$num] ?? null;
        $isBest = $bestWeekday && $bestWeekday['weekday'] == $num;
    ?>
    <div class="weekday-item <?= $isBest ? 'best' : '' ?>">
        <div class="weekday-name"><?= $name ?></div>
        <div class="weekday-orders"><?= $wd ? $wd['orders'] : 0 ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Top Produtos -->
<?php if (!empty($topProducts)): ?>
<div class="section-title">Top Produtos</div>
<div class="top-products">
    <?php foreach ($topProducts as $i => $p):
        $rank = $i + 1;
        $rankClass = $rank <= 3 ? "rank-$rank" : 'rank-default';
    ?>
    <div class="top-product-item">
        <div class="top-product-rank <?= $rankClass ?>"><?= $rank ?></div>
        <div class="top-product-info">
            <div class="top-product-name"><?= htmlspecialchars($p['product_name']) ?></div>
            <div class="top-product-meta"><?= (int)$p['total_quantity'] ?> vendidos · <?= (int)$p['order_count'] ?> pedidos</div>
        </div>
        <div class="top-product-revenue"><?= $fmt($p['total_revenue']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Formas de Pagamento -->
<?php if (!empty($paymentMethods)): ?>
<div class="section-title">Formas de Pagamento</div>
<div class="payment-list">
    <?php
    $paymentIcons = [
        'pix' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13.17 10.83l-1.34-1.34a3.5 3.5 0 00-4.95 0l-2.12 2.12a3.5 3.5 0 000 4.95l1.41 1.41a3.5 3.5 0 004.95 0"/><path d="M10.83 13.17l1.34 1.34a3.5 3.5 0 004.95 0l2.12-2.12a3.5 3.5 0 000-4.95l-1.41-1.41a3.5 3.5 0 00-4.95 0"/></svg>',
        'credit' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'debit' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'cash' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>',
        'outros' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    ];
    $paymentColors = ['pix' => '#00b4d8', 'credit' => '#7c3aed', 'debit' => '#2563eb', 'cash' => '#16a34a', 'outros' => '#94a3b8'];
    foreach ($paymentMethods as $pm):
        $icon = $paymentIcons[$pm['payment_type']] ?? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
        $color = $paymentColors[$pm['payment_type']] ?? '#94a3b8';
    ?>
    <div class="payment-item">
        <div class="payment-icon" style="background: <?= $color ?>15; color: <?= $color ?>;">
            <?= $icon ?>
        </div>
        <div class="payment-info">
            <div class="payment-name"><?= htmlspecialchars($pm['payment_method']) ?></div>
            <div class="payment-bar">
                <div class="payment-bar-fill" style="width: <?= $pm['percentage'] ?>%; background: <?= $color ?>;"></div>
            </div>
        </div>
        <div class="payment-pct"><?= $pm['percentage'] ?>%</div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
