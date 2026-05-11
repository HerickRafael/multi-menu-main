<?php
/**
 * Dashboard Financeiro - Redesign SaaS
 * Foco: decisão rápida, hierarquia clara, ações concretas
 */

$title = 'Financeiro - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));
$currentMonth = date('Y-m');

// Cálculos auxiliares
$grossRevenue    = (float)($monthlySummary['gross_revenue'] ?? 0);
$netRevenue      = (float)($monthlySummary['net_revenue'] ?? 0);
$netProfit       = (float)($monthlySummary['net_profit'] ?? 0);
$grossProfit     = (float)($monthlySummary['gross_profit'] ?? 0);
$totalExpenses   = (float)($monthlySummary['total_expenses'] ?? 0);
$productionCost  = (float)($monthlySummary['production_cost'] ?? 0);
$profitMargin    = (float)($monthlySummary['profit_margin'] ?? 0);
$discounts       = (float)($monthlySummary['discounts'] ?? 0);

// Comparison
$revChange    = (float)($comparison['gross_revenue']['change_percent'] ?? 0);
$profitChange = (float)($comparison['net_profit']['change_percent'] ?? 0);
$marginChange = (float)($comparison['profit_margin']['change_percent'] ?? 0);
$prevProfit   = (float)($comparison['net_profit']['previous'] ?? 0);
$prevRevenue  = (float)($comparison['gross_revenue']['previous'] ?? 0);

// Custos detalhados
$ingredientCost    = (float)($monthlySummary['ingredient_cost'] ?? 0);
$packagingCost     = (float)($monthlySummary['packaging_cost'] ?? 0);
$laborCost         = (float)($monthlySummary['labor_cost'] ?? 0);
$fixedExpenses     = (float)($monthlySummary['fixed_expenses'] ?? 0);
$variableExpenses  = (float)($monthlySummary['variable_expenses'] ?? 0);
$totalCosts        = $productionCost + $totalExpenses;

// Smart insight
$hasRevenue = $grossRevenue > 0;
$hasCosts   = $totalCosts > 0;
$hasData    = $hasRevenue || $hasCosts;

if (!$hasData) {
    $insightType = 'empty';
    $insightIcon = '📊';
    $insightText = 'Seu primeiro passo: registre suas despesas e custos para ter controle total.';
    $insightColor = '#64748b';
    $insightBg = '#f8fafc';
    $insightBorder = '#e2e8f0';
} elseif ($netProfit < 0) {
    $insightType = 'critical';
    $insightIcon = '🚨';
    $insightText = 'Seu negócio está no vermelho: prejuízo de R$ ' . number_format(abs($netProfit), 2, ',', '.') . ' este mês.';
    $insightColor = '#dc2626';
    $insightBg = '#fef2f2';
    $insightBorder = '#fecaca';
} elseif ($profitMargin < 10 && $hasRevenue) {
    $insightType = 'warning';
    $insightIcon = '⚠️';
    $insightText = 'Margem perigosa: ' . number_format($profitMargin, 1, ',', '.') . '% — abaixo de 10% é zona de risco.';
    $insightColor = '#d97706';
    $insightBg = '#fffbeb';
    $insightBorder = '#fde68a';
} elseif ($profitChange < -10 && $prevProfit > 0) {
    $insightType = 'declining';
    $insightIcon = '📉';
    $insightText = 'Seu lucro caiu ' . abs(round($profitChange)) . '% em relação ao mês passado.';
    $insightColor = '#d97706';
    $insightBg = '#fffbeb';
    $insightBorder = '#fde68a';
} elseif ($profitChange > 10 && $prevProfit > 0) {
    $insightType = 'growing';
    $insightIcon = '🚀';
    $insightText = 'Seu lucro cresceu +' . round($profitChange) . '% em relação ao mês passado!';
    $insightColor = '#059669';
    $insightBg = '#ecfdf5';
    $insightBorder = '#a7f3d0';
} elseif ($hasRevenue && $profitMargin >= 20) {
    $insightType = 'healthy';
    $insightIcon = '✅';
    $insightText = 'Negócio saudável: margem de ' . number_format($profitMargin, 1, ',', '.') . '% — continue assim.';
    $insightColor = '#059669';
    $insightBg = '#ecfdf5';
    $insightBorder = '#a7f3d0';
} else {
    $insightType = 'neutral';
    $insightIcon = '📊';
    $insightText = 'Receita de R$ ' . number_format($grossRevenue, 2, ',', '.') . ' com margem de ' . number_format($profitMargin, 1, ',', '.') . '%.';
    $insightColor = '#475569';
    $insightBg = '#f8fafc';
    $insightBorder = '#e2e8f0';
}

// Helper para badges de comparação
function renderFinBadge(float $change, string $suffix = '%'): string {
    if (abs($change) < 0.1) return '<span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">—</span>';
    $positive = $change > 0;
    $color = $positive ? 'text-emerald-700 bg-emerald-50' : 'text-red-700 bg-red-50';
    $arrow = $positive ? '↑' : '↓';
    return '<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ' . $color . '">' . $arrow . ' ' . abs(round($change, 1)) . $suffix . '</span>';
}

// Meses pt-BR
$meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
$mesAtual = $meses[(int)date('n') - 1] . '/' . date('Y');

ob_start();
?>

<style>
.fin-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;transition:box-shadow .2s}
.fin-card:hover{box-shadow:0 4px 20px -4px rgba(0,0,0,.08)}
.fin-hero{border-radius:16px;padding:20px 24px;display:flex;align-items:center;gap:16px}
.fin-kpi{display:flex;flex-direction:column;gap:2px}
.fin-kpi-value{font-size:28px;font-weight:800;line-height:1.1;letter-spacing:-.5px}
.fin-kpi-label{font-size:13px;font-weight:500;color:#64748b}
.fin-kpi-sub{font-size:12px;color:#94a3b8}
.dre-line{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:14px}
.dre-line:last-child{border-bottom:none}
.dre-line.indent{padding-left:20px;color:#64748b}
.dre-line.subtotal{background:#f8fafc;margin:4px -20px;padding:10px 20px;border-radius:8px;font-weight:600}
.dre-line.result{background:linear-gradient(135deg,#f8fafc,#eff6ff);margin:8px -20px 0;padding:14px 20px;border-radius:12px;border:1px solid #e2e8f0;font-weight:700}
.cost-bar{display:flex;height:10px;border-radius:5px;overflow:hidden;background:#f1f5f9}
.action-card{border:1px solid #fde68a;border-radius:14px;padding:16px;background:#fff}
.action-card:hover{border-color:#fbbf24;box-shadow:0 2px 12px -2px rgba(245,158,11,.15)}
.chart-wrap{position:relative;height:280px}
@media(max-width:639px){
  .fin-kpi-value{font-size:22px}
  .fin-hero{flex-direction:column;text-align:center}
  .chart-wrap{height:220px}
}
</style>

<div class="mx-auto max-w-7xl p-4">

<?php
$pageTitle = 'Financeiro';
$pageDescription = $mesAtual;
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [['label' => 'Financeiro']];
$actions = [
    ['label' => 'Despesas', 'url' => base_url('admin/' . $activeSlug . '/expenses'), 'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
    ['label' => 'Custos', 'url' => base_url('admin/' . $activeSlug . '/product-costs'), 'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
];
include __DIR__ . '/../components/page-header.php';
?>

<!-- 1. INSIGHT PRINCIPAL -->
<div class="fin-hero mb-6" style="background:<?= $insightBg ?>;border:1px solid <?= $insightBorder ?>">
  <span class="text-3xl flex-shrink-0"><?= $insightIcon ?></span>
  <div>
    <p class="text-base font-bold" style="color:<?= $insightColor ?>"><?= $insightText ?></p>
    <?php if ($insightType === 'critical' || $insightType === 'warning' || $insightType === 'declining'): ?>
      <p class="mt-1 text-sm text-slate-500">Veja os detalhes abaixo para entender onde agir.</p>
    <?php elseif ($insightType === 'empty'): ?>
      <a href="<?= base_url('admin/' . $activeSlug . '/expenses') ?>" class="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-purple-600 hover:text-purple-700">Cadastrar despesas →</a>
    <?php endif; ?>
  </div>
</div>

<!-- 2. KPIs PRINCIPAIS — 3 cards, lucro maior -->
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

  <!-- LUCRO (destaque) -->
  <div class="fin-card sm:col-span-1" style="border-color:<?= $netProfit >= 0 ? '#a7f3d0' : '#fecaca' ?>;background:<?= $netProfit >= 0 ? 'linear-gradient(to bottom right,#ecfdf5,#fff)' : 'linear-gradient(to bottom right,#fef2f2,#fff)' ?>">
    <div class="flex items-center justify-between mb-3">
      <span class="fin-kpi-label" style="color:<?= $netProfit >= 0 ? '#059669' : '#dc2626' ?>">Lucro Líquido</span>
      <?= renderFinBadge($profitChange) ?>
    </div>
    <div class="fin-kpi">
      <span class="fin-kpi-value" style="color:<?= $netProfit >= 0 ? '#059669' : '#dc2626' ?>">R$ <?= number_format($netProfit, 2, ',', '.') ?></span>
      <span class="fin-kpi-sub">Mês anterior: R$ <?= number_format($prevProfit, 2, ',', '.') ?></span>
    </div>
  </div>

  <!-- RECEITA -->
  <div class="fin-card">
    <div class="flex items-center justify-between mb-3">
      <span class="fin-kpi-label">Receita Total</span>
      <?= renderFinBadge($revChange) ?>
    </div>
    <div class="fin-kpi">
      <span class="fin-kpi-value text-slate-900">R$ <?= number_format($grossRevenue, 2, ',', '.') ?></span>
      <span class="fin-kpi-sub"><?= $monthlySummary['completed_orders'] ?? 0 ?> pedidos • Ticket R$ <?= number_format($monthlySummary['avg_ticket'] ?? 0, 0, ',', '.') ?></span>
    </div>
  </div>

  <!-- MARGEM -->
  <div class="fin-card">
    <div class="flex items-center justify-between mb-3">
      <span class="fin-kpi-label">Margem de Lucro</span>
      <?= renderFinBadge($marginChange, ' p.p.') ?>
    </div>
    <div class="fin-kpi">
      <span class="fin-kpi-value <?= $profitMargin >= 20 ? 'text-slate-900' : ($profitMargin >= 10 ? 'text-amber-600' : 'text-red-600') ?>"><?= number_format($profitMargin, 1, ',', '.') ?>%</span>
      <div class="mt-2 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
        <div class="h-full rounded-full transition-all duration-700" style="width:<?= min(max($profitMargin, 0), 100) ?>%;background:<?= $profitMargin >= 20 ? '#10b981' : ($profitMargin >= 10 ? '#f59e0b' : '#ef4444') ?>"></div>
      </div>
      <span class="fin-kpi-sub mt-1"><?= $profitMargin >= 20 ? 'Saudável (>20%)' : ($profitMargin >= 10 ? 'Atenção (10-20%)' : 'Risco (<10%)') ?></span>
    </div>
  </div>
</div>

<!-- 3. GRÁFICO PRINCIPAL — Evolução Financeira -->
<div class="fin-card mb-6">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h3 class="text-base font-bold text-slate-900">Evolução Financeira</h3>
      <p class="text-xs text-slate-500">Últimos 6 meses</p>
    </div>
    <div class="flex gap-4 text-xs font-medium">
      <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Receita</span>
      <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-slate-400"></span>Custos</span>
      <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:#8b5cf6"></span>Lucro</span>
    </div>
  </div>
  <div class="chart-wrap"><canvas id="mainChart"></canvas></div>
</div>

<!-- 4. AÇÕES — Produtos com baixa margem -->
<?php if (!empty($lowMarginProducts)): ?>
<div class="mb-6">
  <div class="flex items-center gap-3 mb-4">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <div>
      <h3 class="text-base font-bold text-slate-900">Produtos que precisam de ação</h3>
      <p class="text-xs text-slate-500">Margem abaixo de 20% — ajuste preço ou reduza custos</p>
    </div>
  </div>
  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach (array_slice($lowMarginProducts, 0, 6) as $lp):
      $lpPrice  = (float)($lp['price'] ?? 0);
      $lpCost   = (float)($lp['total_cost'] ?? $lp['production_cost'] ?? 0);
      $lpMargin = (float)($lp['profit_margin'] ?? 0);
      $lpProfit = (float)($lp['profit'] ?? ($lpPrice - $lpCost));
      $targetMargin  = 0.25;
      $suggestedPrice = $lpCost > 0 ? round($lpCost / (1 - $targetMargin), 2) : 0;
      $priceDiff     = $suggestedPrice - $lpPrice;
      $costReduction = $lpPrice > 0 ? round($lpCost - ($lpPrice * (1 - $targetMargin)), 2) : 0;
    ?>
    <div class="action-card">
      <div class="flex items-center justify-between mb-2">
        <p class="font-semibold text-slate-800 truncate text-sm"><?= htmlspecialchars($lp['name']) ?></p>
        <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-bold <?= $lpMargin < 10 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' ?>"><?= number_format($lpMargin, 0) ?>%</span>
      </div>
      <div class="flex items-center gap-4 text-xs text-slate-500 mb-3">
        <span>Preço: R$ <?= number_format($lpPrice, 2, ',', '.') ?></span>
        <span>Custo: R$ <?= number_format($lpCost, 2, ',', '.') ?></span>
      </div>
      <?php if ($suggestedPrice > $lpPrice && $priceDiff > 0): ?>
      <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs">
        <p class="font-semibold text-emerald-800">💡 Sugestão</p>
        <p class="text-emerald-700 mt-0.5">Aumentar para <strong>R$ <?= number_format($suggestedPrice, 2, ',', '.') ?></strong> (+R$ <?= number_format($priceDiff, 2, ',', '.') ?>)</p>
        <?php if ($costReduction > 0): ?>
          <p class="text-emerald-600 mt-0.5">ou reduzir custo em R$ <?= number_format($costReduction, 2, ',', '.') ?></p>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="mt-3">
    <a href="<?= base_url('admin/' . $activeSlug . '/product-costs') ?>" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">
      Revisar todos os custos
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
  </div>
</div>
<?php endif; ?>

<!-- 5. DETALHES — DRE + Composição -->
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

  <!-- DRE Simplificado -->
  <div class="fin-card">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-bold text-slate-900">DRE Simplificado</h3>
      <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500"><?= $mesAtual ?></span>
    </div>
    <div>
      <div class="dre-line">
        <span class="text-slate-700">Receita Bruta</span>
        <span class="font-semibold text-slate-900">R$ <?= number_format($grossRevenue, 2, ',', '.') ?></span>
      </div>
      <div class="dre-line indent">
        <span>(-) Descontos</span>
        <span class="text-red-500">- R$ <?= number_format($discounts, 2, ',', '.') ?></span>
      </div>
      <div class="dre-line subtotal">
        <span>Receita Líquida</span>
        <span>R$ <?= number_format($netRevenue, 2, ',', '.') ?></span>
      </div>
      <div class="dre-line indent">
        <span>(-) Custo dos Produtos</span>
        <span class="text-red-500">- R$ <?= number_format($productionCost, 2, ',', '.') ?></span>
      </div>
      <div class="dre-line subtotal">
        <span>Lucro Bruto</span>
        <span>R$ <?= number_format($grossProfit, 2, ',', '.') ?></span>
      </div>
      <div class="dre-line indent">
        <span>(-) Despesas Operacionais</span>
        <span class="text-red-500">- R$ <?= number_format($totalExpenses, 2, ',', '.') ?></span>
      </div>
      <div class="dre-line result">
        <span style="color:<?= $netProfit >= 0 ? '#059669' : '#dc2626' ?>">Lucro Líquido</span>
        <span class="text-lg" style="color:<?= $netProfit >= 0 ? '#059669' : '#dc2626' ?>">R$ <?= number_format($netProfit, 2, ',', '.') ?></span>
      </div>
    </div>
    <?php if (!$hasCosts && $hasRevenue): ?>
    <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
      <strong>⚠️ Custos e despesas zerados.</strong> Cadastre seus custos de produção e despesas para ter um DRE real.
      <div class="mt-2 flex gap-2">
        <a href="<?= base_url('admin/' . $activeSlug . '/product-costs') ?>" class="font-semibold text-amber-700 hover:text-amber-800 underline">Cadastrar custos</a>
        <a href="<?= base_url('admin/' . $activeSlug . '/expenses') ?>" class="font-semibold text-amber-700 hover:text-amber-800 underline">Cadastrar despesas</a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Composição de Custos -->
  <div class="fin-card">
    <h3 class="text-base font-bold text-slate-900 mb-4">Composição de Custos</h3>
    <?php if ($totalCosts > 0): ?>
    <div class="mb-5">
      <div class="cost-bar">
        <div style="width:<?= ($ingredientCost / $totalCosts) * 100 ?>%;background:#fb923c" title="Ingredientes"></div>
        <div style="width:<?= ($packagingCost / $totalCosts) * 100 ?>%;background:#fbbf24" title="Embalagens"></div>
        <div style="width:<?= ($laborCost / $totalCosts) * 100 ?>%;background:#a78bfa" title="Mão de Obra"></div>
        <div style="width:<?= ($fixedExpenses / $totalCosts) * 100 ?>%;background:#94a3b8" title="Fixas"></div>
        <div style="width:<?= ($variableExpenses / $totalCosts) * 100 ?>%;background:#cbd5e1" title="Variáveis"></div>
      </div>
    </div>
    <div class="space-y-3">
      <?php
      $costItems = [
        ['Ingredientes', $ingredientCost, '#fb923c'],
        ['Embalagens', $packagingCost, '#fbbf24'],
        ['Mão de Obra', $laborCost, '#a78bfa'],
        ['Despesas Fixas', $fixedExpenses, '#94a3b8'],
        ['Despesas Variáveis', $variableExpenses, '#cbd5e1'],
      ];
      foreach ($costItems as [$label, $value, $color]):
        $pct = $totalCosts > 0 ? ($value / $totalCosts) * 100 : 0;
        if ($value <= 0) continue;
      ?>
      <div class="flex items-center gap-3">
        <span class="h-3 w-3 rounded-full flex-shrink-0" style="background:<?= $color ?>"></span>
        <span class="text-sm text-slate-700 flex-1"><?= $label ?></span>
        <span class="text-sm font-semibold text-slate-900">R$ <?= number_format($value, 2, ',', '.') ?></span>
        <span class="text-xs text-slate-400 w-10 text-right"><?= number_format($pct, 0) ?>%</span>
      </div>
      <?php endforeach; ?>
      <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
        <span class="h-3 w-3 flex-shrink-0"></span>
        <span class="text-sm font-bold text-slate-900 flex-1">Total</span>
        <span class="text-sm font-bold text-slate-900">R$ <?= number_format($totalCosts, 2, ',', '.') ?></span>
        <span class="text-xs text-slate-400 w-10 text-right">100%</span>
      </div>
    </div>
    <?php else: ?>
    <div class="py-8 text-center">
      <p class="text-sm text-slate-400">Nenhum custo registrado ainda.</p>
      <a href="<?= base_url('admin/' . $activeSlug . '/expenses') ?>" class="mt-2 inline-flex text-sm font-semibold text-purple-600 hover:text-purple-700">Cadastrar despesas →</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- 6. Produtos Lucrativos + Vendas por Hora -->
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

  <!-- Produtos Mais Lucrativos -->
  <div class="fin-card">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-bold text-slate-900">Produtos Mais Lucrativos</h3>
      <?php if (!empty($profitableProducts)): ?>
        <a href="<?= base_url('admin/' . $activeSlug . '/product-costs') ?>" class="text-xs font-semibold text-purple-600 hover:text-purple-700">Ver todos →</a>
      <?php endif; ?>
    </div>
    <?php if (empty($profitableProducts)): ?>
      <div class="py-8 text-center">
        <p class="text-3xl mb-2">📦</p>
        <p class="text-sm text-slate-500">Nenhum dado de lucratividade ainda.</p>
        <p class="text-xs text-slate-400 mt-1">Os dados aparecem conforme os pedidos são processados.</p>
      </div>
    <?php else: ?>
      <div class="space-y-1">
        <?php foreach (array_slice($profitableProducts, 0, 5) as $i => $p): ?>
        <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-slate-50">
          <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-xs font-bold <?= $i === 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>"><?= $i + 1 ?></span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($p['name']) ?></p>
            <p class="text-xs text-slate-400"><?= $p['quantity_sold'] ?> vendidos</p>
          </div>
          <div class="text-right flex-shrink-0">
            <p class="text-sm font-bold text-emerald-600">R$ <?= number_format($p['total_profit'], 2, ',', '.') ?></p>
            <p class="text-xs text-slate-400"><?= number_format($p['avg_margin'], 0) ?>% margem</p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Faturamento por Hora -->
  <div class="fin-card">
    <div class="mb-4">
      <h3 class="text-base font-bold text-slate-900">Horários de Pico</h3>
      <p class="text-xs text-slate-500">Faturamento por hora do dia</p>
    </div>
    <div class="chart-wrap"><canvas id="hourlyChart"></canvas></div>
  </div>
</div>

<!-- 7. Vendas do Mês (compacto) -->
<div class="fin-card mb-6">
  <h3 class="text-base font-bold text-slate-900 mb-4">Vendas do Mês</h3>
  <div class="grid grid-cols-3 gap-4 sm:grid-cols-6">
    <div class="text-center">
      <p class="text-2xl font-bold text-slate-900"><?= $monthlySummary['total_orders'] ?? 0 ?></p>
      <p class="text-xs text-slate-500">Pedidos</p>
    </div>
    <div class="text-center">
      <p class="text-2xl font-bold text-emerald-600"><?= $monthlySummary['completed_orders'] ?? 0 ?></p>
      <p class="text-xs text-slate-500">Concluídos</p>
    </div>
    <div class="text-center">
      <p class="text-2xl font-bold text-red-500"><?= $monthlySummary['cancelled_orders'] ?? 0 ?></p>
      <p class="text-xs text-slate-500">Cancelados</p>
    </div>
    <div class="text-center">
      <p class="text-2xl font-bold text-slate-900"><?= $monthlySummary['total_items_sold'] ?? 0 ?></p>
      <p class="text-xs text-slate-500">Itens</p>
    </div>
    <div class="text-center">
      <p class="text-2xl font-bold text-slate-900">R$ <?= number_format($monthlySummary['avg_ticket'] ?? 0, 0, ',', '.') ?></p>
      <p class="text-xs text-slate-500">Ticket Médio</p>
    </div>
    <div class="text-center">
      <p class="text-2xl font-bold text-orange-500">R$ <?= number_format($discounts, 0, ',', '.') ?></p>
      <p class="text-xs text-slate-500">Descontos</p>
    </div>
  </div>
</div>

</div><!-- .max-w-7xl -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const trendData = <?= json_encode($monthlyTrend ?? []) ?>;
    const hourlyData = <?= json_encode($hourlySales ?? []) ?>;

    const chartFont = { family: 'Inter, system-ui, sans-serif' };
    const gridColor = '#f1f5f9';
    const tooltipStyle = {
        backgroundColor: '#fff', titleColor: '#0f172a', bodyColor: '#475569',
        borderColor: '#e2e8f0', borderWidth: 1, padding: 12, cornerRadius: 10,
        titleFont: { weight: '700', ...chartFont }, bodyFont: chartFont,
        callbacks: { label: ctx => ctx.dataset.label + ': R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2}) }
    };

    // Gráfico Principal — Receita + Custos (linhas) + Lucro (barras)
    const mainCtx = document.getElementById('mainChart');
    if (mainCtx && trendData.length > 0) {
        new Chart(mainCtx, {
            type: 'bar',
            data: {
                labels: trendData.map(d => d.month_label),
                datasets: [
                    {
                        type: 'line',
                        label: 'Receita',
                        data: trendData.map(d => d.revenue),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        fill: true, tension: 0.4, borderWidth: 2.5,
                        pointRadius: 4, pointBackgroundColor: '#10b981',
                        order: 1
                    },
                    {
                        type: 'line',
                        label: 'Custos',
                        data: trendData.map(d => d.expenses),
                        borderColor: '#94a3b8',
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        fill: false, tension: 0.4, borderWidth: 2,
                        pointRadius: 3, pointBackgroundColor: '#94a3b8',
                        order: 2
                    },
                    {
                        type: 'bar',
                        label: 'Lucro',
                        data: trendData.map(d => d.profit),
                        backgroundColor: trendData.map(d => d.profit >= 0 ? 'rgba(139,92,246,0.7)' : 'rgba(239,68,68,0.7)'),
                        borderRadius: 6, borderSkipped: false,
                        barPercentage: 0.5,
                        order: 3
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: tooltipStyle
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { font: chartFont, callback: v => 'R$ ' + v.toLocaleString('pt-BR') } },
                    x: { grid: { display: false }, ticks: { font: chartFont } }
                }
            }
        });
    }

    // Gráfico de Horas de Pico
    const hourlyCtx = document.getElementById('hourlyChart');
    if (hourlyCtx) {
        const hours = Array.from({length: 24}, (_, i) => i);
        const hourlyMap = {};
        (hourlyData || []).forEach(d => hourlyMap[d.hour] = parseFloat(d.revenue));
        const hourValues = hours.map(h => hourlyMap[h] || 0);
        const maxHourVal = Math.max(...hourValues);

        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: hours.map(h => h + 'h'),
                datasets: [{
                    label: 'Faturamento',
                    data: hourValues,
                    backgroundColor: hourValues.map(v => v === maxHourVal && v > 0 ? 'rgba(139,92,246,0.9)' : 'rgba(139,92,246,0.3)'),
                    borderRadius: 4, borderSkipped: false
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipStyle, callbacks: { label: ctx => 'R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2}) } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { font: chartFont, callback: v => 'R$ ' + v.toLocaleString('pt-BR') } },
                    x: { grid: { display: false }, ticks: { font: chartFont, maxRotation: 0 } }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
