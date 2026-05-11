<?php
/**
 * Relatório Mensal - Mobile
 */
$fmt = function($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); };
$revenue = (float)($monthlySummary['total_revenue'] ?? 0);
$orders = (int)($monthlySummary['total_orders'] ?? 0);
$avgTicket = (float)($monthlySummary['avg_ticket'] ?? 0);
?>

<style>
.fin-nav { display: flex; gap: 6px; margin-bottom: 16px; overflow-x: auto; }
.fin-nav a { flex-shrink: 0; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; background: white; color: #64748b; border: 1px solid #e2e8f0; }
.fin-nav a.active { background: var(--primary, #7c3aed); color: white; border-color: var(--primary); }
.month-selector { margin-bottom: 16px; }
.month-select { width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 15px; background: white; color: #1e293b; }
.summary-hero { background: white; border-radius: 16px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.summary-hero h2 { font-size: 14px; color: #94a3b8; margin-bottom: 6px; }
.summary-hero .value { font-size: 28px; font-weight: 800; color: #1e293b; }
.summary-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 10px; margin-top: 14px; padding-top: 14px; border-top: 1px solid #f1f5f9; }
.summary-item label { font-size: 11px; color: #94a3b8; display: block; }
.summary-item span { font-size: 16px; font-weight: 700; color: #1e293b; }
.section-title { font-size: 16px; font-weight: 700; color: #1e293b; margin: 20px 0 12px; }
.daily-list { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden; margin-bottom: 16px; }
.daily-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
.daily-item:last-child { border-bottom: none; }
.daily-date { font-size: 14px; font-weight: 500; color: #1e293b; }
.daily-info { text-align: right; }
.daily-revenue { font-size: 14px; font-weight: 700; color: #16a34a; }
.daily-orders { font-size: 12px; color: #94a3b8; }
.top-list { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden; margin-bottom: 16px; }
.top-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
.top-item:last-child { border-bottom: none; }
.top-rank { width: 26px; height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; background: #f1f5f9; color: #94a3b8; flex-shrink: 0; }
.top-rank.r1 { background: #fef3c7; color: #92400e; }
.top-rank.r2 { background: #e2e8f0; color: #475569; }
.top-rank.r3 { background: #fed7aa; color: #9a3412; }
.top-name { flex: 1; font-size: 14px; font-weight: 500; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-value { font-size: 14px; font-weight: 700; color: #16a34a; flex-shrink: 0; }
</style>

<div class="fin-nav">
    <a href="/financial">Visão Geral</a>
    <a href="/financial/monthly" class="active">Mensal</a>
    <a href="/financial/yearly">Anual</a>
    <a href="/expenses">Despesas</a>
    <a href="/expenses/categories">Categorias</a>
    <a href="/financial/settings">Config.</a>
</div>

<div class="month-selector">
    <select class="month-select" onchange="window.location='/financial/monthly?month='+this.value">
        <?php foreach ($availableMonths as $m => $label): ?>
        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="summary-hero">
    <h2>Faturamento - <?= htmlspecialchars($monthLabel) ?></h2>
    <div class="value"><?= $fmt($revenue) ?></div>
    <div class="summary-grid">
        <div class="summary-item"><label>Pedidos</label><span><?= $orders ?></span></div>
        <div class="summary-item"><label>Ticket Médio</label><span><?= $fmt($avgTicket) ?></span></div>
    </div>
</div>

<?php if (!empty($dailySales)): ?>
<div class="section-title">Vendas Diárias</div>
<div class="daily-list">
    <?php foreach (array_reverse($dailySales) as $day): ?>
    <div class="daily-item">
        <span class="daily-date"><?= date('d/m', strtotime($day['date'] ?? $day['day'] ?? '')) ?></span>
        <div class="daily-info">
            <div class="daily-revenue"><?= $fmt($day['revenue'] ?? $day['total_revenue'] ?? 0) ?></div>
            <div class="daily-orders"><?= (int)($day['orders'] ?? $day['total_orders'] ?? 0) ?> pedidos</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($topProducts)): ?>
<div class="section-title">Top Produtos</div>
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
