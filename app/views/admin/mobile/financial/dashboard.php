<?php
/**
 * Dashboard Financeiro - Mobile
 */
$fmt = function($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); };
$revenue = (float)($monthlySummary['total_revenue'] ?? 0);
$orders = (int)($monthlySummary['total_orders'] ?? 0);
$avgTicket = (float)($monthlySummary['avg_ticket'] ?? 0);
$totalCost = (float)($monthlySummary['total_cost'] ?? 0);
$profit = (float)($monthlySummary['net_profit'] ?? ($revenue - $totalCost));
$margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;

// Comparação
$revChange = (float)($comparison['revenue_change'] ?? 0);
$ordChange = (float)($comparison['orders_change'] ?? 0);
?>

<style>
.fin-nav { display: flex; gap: 6px; margin-bottom: 16px; overflow-x: auto; }
.fin-nav a {
    flex-shrink: 0;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    background: white;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
.fin-nav a.active {
    background: var(--primary, #7c3aed);
    color: white;
    border-color: var(--primary);
}

.fin-hero {
    background: linear-gradient(135deg, #059669, #047857);
    border-radius: 20px;
    padding: 20px;
    color: white;
    margin-bottom: 16px;
}
.fin-hero-label { font-size: 13px; opacity: .8; }
.fin-hero-value { font-size: 30px; font-weight: 800; }
.fin-hero-change {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 4px;
    padding: 2px 8px;
    border-radius: 12px;
}
.fin-hero-sub {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid rgba(255,255,255,.2);
}
.fin-sub-item { text-align: center; }
.fin-sub-label { font-size: 11px; opacity: .7; }
.fin-sub-value { font-size: 16px; font-weight: 700; }

.fin-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 16px;
}
.fin-stat-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.fin-stat-label { font-size: 12px; color: #94a3b8; margin-bottom: 4px; }
.fin-stat-value { font-size: 20px; font-weight: 700; color: #1e293b; }
.fin-stat-value.positive { color: #16a34a; }
.fin-stat-value.negative { color: #dc2626; }

.section-title { font-size: 16px; font-weight: 700; color: #1e293b; margin: 20px 0 12px; }

.trend-list {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    overflow: hidden;
    margin-bottom: 16px;
}
.trend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.trend-item:last-child { border-bottom: none; }
.trend-month { font-size: 14px; font-weight: 600; color: #1e293b; }
.trend-info { display: flex; gap: 16px; align-items: center; }
.trend-revenue { font-size: 14px; font-weight: 700; color: #16a34a; }
.trend-orders { font-size: 12px; color: #94a3b8; }

.top-list {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    overflow: hidden;
    margin-bottom: 16px;
}
.top-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.top-item:last-child { border-bottom: none; }
.top-rank {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    background: #f1f5f9;
    color: #94a3b8;
    flex-shrink: 0;
}
.top-rank.r1 { background: #fef3c7; color: #92400e; }
.top-rank.r2 { background: #e2e8f0; color: #475569; }
.top-rank.r3 { background: #fed7aa; color: #9a3412; }
.top-name { flex: 1; font-size: 14px; font-weight: 500; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-value { font-size: 14px; font-weight: 700; color: #16a34a; flex-shrink: 0; }
</style>

<!-- Navegação -->
<div class="fin-nav">
    <a href="/financial" class="active">Visão Geral</a>
    <a href="/financial/monthly">Mensal</a>
    <a href="/financial/yearly">Anual</a>
    <a href="/expenses">Despesas</a>
    <a href="/expenses/categories">Categorias</a>
    <a href="/financial/settings">Config.</a>
</div>

<!-- Hero -->
<div class="fin-hero">
    <div class="fin-hero-label">Faturamento - <?= htmlspecialchars($currentMonthLabel) ?></div>
    <div class="fin-hero-value"><?= $fmt($revenue) ?></div>
    <?php if ($revChange != 0): ?>
    <span class="fin-hero-change" style="background:rgba(255,255,255,.2); display:inline-flex; align-items:center; gap:4px;">
        <?php if ($revChange > 0): ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 9 11"/><polyline points="16 7 22 7 22 13"/></svg>
            +<?= abs($revChange) ?>%
        <?php else: ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 17 13.5 8.5 9 13"/><polyline points="16 17 22 17 22 11"/></svg>
            <?= $revChange ?>%
        <?php endif; ?>
    </span>
    <?php endif; ?>
    <div class="fin-hero-sub">
        <div class="fin-sub-item">
            <div class="fin-sub-label">Pedidos</div>
            <div class="fin-sub-value"><?= $orders ?></div>
        </div>
        <div class="fin-sub-item">
            <div class="fin-sub-label">Ticket Médio</div>
            <div class="fin-sub-value"><?= $fmt($avgTicket) ?></div>
        </div>
        <div class="fin-sub-item">
            <div class="fin-sub-label">Margem</div>
            <div class="fin-sub-value"><?= $margin ?>%</div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="fin-stats">
    <div class="fin-stat-card">
        <div class="fin-stat-label">Lucro Estimado</div>
        <div class="fin-stat-value <?= $profit >= 0 ? 'positive' : 'negative' ?>"><?= $fmt($profit) ?></div>
    </div>
    <div class="fin-stat-card">
        <div class="fin-stat-label">Custos</div>
        <div class="fin-stat-value negative"><?= $fmt($totalCost) ?></div>
    </div>
</div>

<!-- Tendência últimos 6 meses -->
<?php if (!empty($monthlyTrend)): ?>
<div class="section-title">Últimos 6 Meses</div>
<div class="trend-list">
    <?php
    $months = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun',
               '07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
    foreach (array_reverse($monthlyTrend) as $t):
        $parts = explode('-', $t['month'] ?? '');
        $monthName = ($months[$parts[1] ?? ''] ?? '') . '/' . ($parts[0] ?? '');
    ?>
    <div class="trend-item">
        <span class="trend-month"><?= $monthName ?></span>
        <div class="trend-info">
            <span class="trend-orders"><?= (int)($t['total_orders'] ?? 0) ?> ped.</span>
            <span class="trend-revenue"><?= $fmt($t['total_revenue'] ?? 0) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Top Produtos -->
<?php if (!empty($topProducts)): ?>
<div class="section-title">Mais Vendidos</div>
<div class="top-list">
    <?php foreach ($topProducts as $i => $p): $r = $i+1; ?>
    <div class="top-item">
        <div class="top-rank <?= $r <= 3 ? "r$r" : '' ?>"><?= $r ?></div>
        <span class="top-name"><?= htmlspecialchars($p['product_name'] ?? $p['name'] ?? '') ?></span>
        <span class="top-value"><?= $fmt($p['total_revenue'] ?? $p['revenue'] ?? 0) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Mais Lucrativos -->
<?php if (!empty($profitableProducts)): ?>
<div class="section-title">Mais Lucrativos</div>
<div class="top-list">
    <?php foreach ($profitableProducts as $i => $p): $r = $i+1; ?>
    <div class="top-item">
        <div class="top-rank <?= $r <= 3 ? "r$r" : '' ?>"><?= $r ?></div>
        <span class="top-name"><?= htmlspecialchars($p['product_name'] ?? $p['name'] ?? '') ?></span>
        <span class="top-value"><?= $fmt($p['profit'] ?? $p['total_revenue'] ?? 0) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
