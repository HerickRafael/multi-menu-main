<?php
/**
 * Yearly Financial Report - Mobile
 */
$activeNav = 'settings';

$currentYear = $year ?? (int)date('Y');
$monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

$totalRevenue = (float)($report['total_revenue'] ?? 0);
$totalExpenses = (float)($report['total_expenses'] ?? 0);
$totalCosts = (float)($report['total_costs'] ?? 0);
$totalProfit = (float)($report['total_profit'] ?? ($totalRevenue - $totalExpenses - $totalCosts));
$avgMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

ob_start();
?>

<style>
.yr-page { padding: 1rem; padding-bottom: 6rem; }
.yr-back { display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-secondary, #64748b); text-decoration: none; font-size: 0.875rem; margin-bottom: 0.75rem; }
.yr-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.yr-title { font-size: 1.125rem; font-weight: 700; color: var(--text-primary, #1e293b); }

/* Year selector */
.yr-selector { display: flex; align-items: center; gap: 0.5rem; }
.yr-selector select { padding: 0.375rem 0.625rem; border: 1px solid #e2e8f0; border-radius: 0.625rem; font-size: 0.8125rem; background: #fff; }
.yr-selector button { padding: 0.375rem 0.75rem; background: var(--primary, #4361ee); color: #fff; border: none; border-radius: 0.625rem; font-size: 0.8125rem; cursor: pointer; }

/* Summary cards */
.yr-summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 1rem; }
.yr-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.75rem; }
.yr-card-label { font-size: 0.6875rem; color: var(--text-secondary, #64748b); margin-bottom: 0.25rem; }
.yr-card-value { font-size: 1.125rem; font-weight: 700; }

/* Monthly table as cards */
.yr-month-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 0.75rem; margin-bottom: 0.5rem; }
.yr-month-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
.yr-month-name { font-size: 0.9375rem; font-weight: 600; color: var(--text-primary, #1e293b); }
.yr-margin-badge { display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 600; }
.yr-month-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.375rem; }
.yr-month-stat-label { font-size: 0.625rem; color: var(--text-secondary, #64748b); text-transform: uppercase; }
.yr-month-stat-value { font-size: 0.8125rem; font-weight: 500; }
.yr-month-link { display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem; font-size: 0.75rem; color: var(--primary, #4361ee); margin-top: 0.375rem; padding-top: 0.375rem; border-top: 1px solid #f1f5f9; text-decoration: none; }

/* Total footer */
.yr-total { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 0.875rem; padding: 0.875rem; margin-top: 0.75rem; }
.yr-total-title { font-size: 0.875rem; font-weight: 700; color: var(--text-primary, #1e293b); margin-bottom: 0.5rem; }
</style>

<div class="yr-page">
    <a href="/financial" class="yr-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="12" x2="5" y2="12" stroke-linecap="round" stroke-linejoin="round"/><polyline points="12 19 5 12 12 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Financeiro
    </a>

    <div class="yr-header">
        <h1 class="yr-title">Relatório Anual</h1>
        <form class="yr-selector" method="GET" action="/financial/yearly">
            <select name="year">
                <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
                <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit">Ver</button>
        </form>
    </div>

    <!-- Summary cards -->
    <div class="yr-summary">
        <div class="yr-card">
            <div class="yr-card-label">Receita Total</div>
            <div class="yr-card-value" style="color: #16a34a;">R$ <?= number_format($totalRevenue, 2, ',', '.') ?></div>
        </div>
        <div class="yr-card">
            <div class="yr-card-label">CMV Total</div>
            <div class="yr-card-value" style="color: #d97706;">R$ <?= number_format($totalCosts, 2, ',', '.') ?></div>
        </div>
        <div class="yr-card">
            <div class="yr-card-label">Despesas Total</div>
            <div class="yr-card-value" style="color: #dc2626;">R$ <?= number_format($totalExpenses, 2, ',', '.') ?></div>
        </div>
        <div class="yr-card" style="background: <?= $totalProfit >= 0 ? '#f0fdf4' : '#fef2f2' ?>; border-color: <?= $totalProfit >= 0 ? '#bbf7d0' : '#fecaca' ?>;">
            <div class="yr-card-label"><?= $totalProfit >= 0 ? 'Lucro Total' : 'Prejuízo Total' ?></div>
            <div class="yr-card-value" style="color: <?= $totalProfit >= 0 ? '#16a34a' : '#dc2626' ?>;">R$ <?= number_format($totalProfit, 2, ',', '.') ?></div>
        </div>
    </div>

    <!-- Margin card -->
    <div class="yr-card" style="margin-bottom:1rem; text-align:center;">
        <div class="yr-card-label">Margem Média Anual</div>
        <div class="yr-card-value" style="color: <?= $avgMargin >= 20 ? '#16a34a' : ($avgMargin >= 10 ? '#d97706' : '#dc2626') ?>;"><?= number_format($avgMargin, 1, ',', '.') ?>%</div>
    </div>

    <!-- Monthly breakdown -->
    <h2 style="font-size:0.9375rem; font-weight:600; color:var(--text-primary, #1e293b); margin-bottom:0.625rem;">Detalhamento Mensal</h2>

    <?php for ($m = 1; $m <= 12; $m++):
        $md = $report['months'][$m] ?? [];
        $rev = (float)($md['revenue'] ?? 0);
        $cos = (float)($md['costs'] ?? 0);
        $exp = (float)($md['expenses'] ?? 0);
        $pro = (float)($md['profit'] ?? ($rev - $cos - $exp));
        $mar = $rev > 0 ? ($pro / $rev) * 100 : 0;
        $marClass = $mar >= 20 ? 'background:#dcfce7;color:#166534;' : ($mar >= 10 ? 'background:#fef3c7;color:#92400e;' : 'background:#fee2e2;color:#991b1b;');
    ?>
    <div class="yr-month-card">
        <div class="yr-month-top">
            <span class="yr-month-name"><?= $monthNames[$m - 1] ?></span>
            <span class="yr-margin-badge" style="<?= $marClass ?>"><?= number_format($mar, 1, ',', '.') ?>%</span>
        </div>
        <div class="yr-month-grid">
            <div>
                <div class="yr-month-stat-label">Receita</div>
                <div class="yr-month-stat-value" style="color:#16a34a;">R$ <?= number_format($rev, 2, ',', '.') ?></div>
            </div>
            <div>
                <div class="yr-month-stat-label">Custos</div>
                <div class="yr-month-stat-value" style="color:#d97706;">R$ <?= number_format($cos, 2, ',', '.') ?></div>
            </div>
            <div>
                <div class="yr-month-stat-label">Despesas</div>
                <div class="yr-month-stat-value" style="color:#dc2626;">R$ <?= number_format($exp, 2, ',', '.') ?></div>
            </div>
            <div>
                <div class="yr-month-stat-label">Lucro</div>
                <div class="yr-month-stat-value" style="color:<?= $pro >= 0 ? '#16a34a' : '#dc2626' ?>; font-weight:600;">R$ <?= number_format($pro, 2, ',', '.') ?></div>
            </div>
        </div>
        <a href="/financial/monthly?month=<?= $m ?>&year=<?= $currentYear ?>" class="yr-month-link">
            Ver detalhes
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
    <?php endfor; ?>

    <!-- Total -->
    <div class="yr-total">
        <div class="yr-total-title">Total <?= $currentYear ?></div>
        <div class="yr-month-grid">
            <div>
                <div class="yr-month-stat-label">Receita</div>
                <div class="yr-month-stat-value" style="color:#16a34a; font-weight:700;">R$ <?= number_format($totalRevenue, 2, ',', '.') ?></div>
            </div>
            <div>
                <div class="yr-month-stat-label">Custos</div>
                <div class="yr-month-stat-value" style="color:#d97706; font-weight:700;">R$ <?= number_format($totalCosts, 2, ',', '.') ?></div>
            </div>
            <div>
                <div class="yr-month-stat-label">Despesas</div>
                <div class="yr-month-stat-value" style="color:#dc2626; font-weight:700;">R$ <?= number_format($totalExpenses, 2, ',', '.') ?></div>
            </div>
            <div>
                <div class="yr-month-stat-label">Lucro</div>
                <div class="yr-month-stat-value" style="color:<?= $totalProfit >= 0 ? '#16a34a' : '#dc2626' ?>; font-weight:700;">R$ <?= number_format($totalProfit, 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
