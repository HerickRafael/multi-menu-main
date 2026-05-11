<?php
/**
 * Relatório Financeiro Mensal
 * Estilo consistente com Analytics
 */

$title = 'Relatório Mensal - ' . ($company['name'] ?? '');
ob_start();

// Nomes dos meses em português
$monthNames = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// $month vem do controller em formato Y-m (ex: "2025-04")
$monthParts = explode('-', $month ?? date('Y-m'));
$currentYear = (int)($monthParts[0] ?? date('Y'));
$currentMonth = (int)($monthParts[1] ?? date('n'));
$monthName = $monthNames[$currentMonth] ?? '';

// Data from controller
$revenue = $monthlySummary['gross_revenue'] ?? 0;
$expenses = $monthlySummary['total_expenses'] ?? 0;
$costs = $monthlySummary['production_cost'] ?? 0;
$profit = $monthlySummary['net_profit'] ?? ($revenue - $expenses - $costs);
$profitMargin = $monthlySummary['profit_margin'] ?? ($revenue > 0 ? ($profit / $revenue) * 100 : 0);

// Configuração do header padronizado
$pageTitle = 'Relatório Mensal';
$pageDescription = e($monthName) . ' de ' . e($currentYear);
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Financeiro', 'url' => base_url('admin/' . $activeSlug . '/financial')],
    ['label' => 'Mensal']
];
$actions = [];

// Seletor de mês como extra content
ob_start();
?>
<form action="<?= base_url('admin/' . $activeSlug . '/financial/monthly') ?>" method="GET" class="flex items-center gap-2">
  <select name="month" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
    <?php foreach ($availableMonths as $key => $name): ?>
    <option value="<?= e($key) ?>" <?= $key === $month ? 'selected' : '' ?>><?= e($name) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="inline-flex items-center gap-1 rounded-xl admin-gradient-bg px-4 py-2 text-white text-sm hover:opacity-95 transition">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Ver
  </button>
</form>
<?php $extraHeaderContent = ob_get_clean(); ?>

<div class="mx-auto max-w-6xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- Cards de Métricas -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">Receita</span>
    </div>
    <p class="text-xl font-bold text-slate-900">R$ <?= number_format($revenue, 2, ',', '.') ?></p>
  </div>
  
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">CMV</span>
    </div>
    <p class="text-xl font-bold text-slate-900">R$ <?= number_format($costs, 2, ',', '.') ?></p>
  </div>
  
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">Despesas</span>
    </div>
    <p class="text-xl font-bold text-slate-900">R$ <?= number_format($expenses, 2, ',', '.') ?></p>
  </div>
  
  <div class="rounded-2xl border <?= $profit >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' ?> p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg <?= $profit >= 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' ?>">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm <?= $profit >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">Lucro</span>
    </div>
    <p class="text-xl font-bold <?= $profit >= 0 ? 'text-emerald-700' : 'text-red-700' ?>">R$ <?= number_format($profit, 2, ',', '.') ?></p>
  </div>
  
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">Margem</span>
    </div>
    <p class="text-xl font-bold <?= $profitMargin >= 20 ? 'text-emerald-600' : ($profitMargin >= 10 ? 'text-amber-600' : 'text-red-600') ?>"><?= number_format($profitMargin, 1, ',', '.') ?>%</p>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  
  <!-- Despesas por Categoria -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" stroke-linecap="round" stroke-linejoin="round"/><path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Despesas por Categoria</h2>
    </div>
    
    <?php if (empty($expensesByCategory ?? [])): ?>
    <div class="text-center py-8 text-slate-500">
      <svg class="h-12 w-12 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <p>Nenhuma despesa neste período</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
      <?php foreach (($expensesByCategory ?? []) as $cat): ?>
      <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
        <div class="flex items-center gap-2">
          <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white text-xs" style="background-color: <?= e($cat['color'] ?? '#6b7280') ?>">
            <?= strtoupper(substr($cat['name'] ?? 'X', 0, 2)) ?>
          </span>
          <span class="font-medium text-slate-700"><?= e($cat['name'] ?? 'Sem categoria') ?></span>
        </div>
        <span class="font-bold text-slate-900">R$ <?= number_format($cat['total'] ?? 0, 2, ',', '.') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  
  <!-- Top Produtos -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Top Produtos</h2>
    </div>
    
    <?php if (empty($topProducts)): ?>
    <div class="text-center py-8 text-slate-500">
      <svg class="h-12 w-12 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <p>Nenhuma venda neste período</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
      <?php foreach (array_slice($topProducts, 0, 5) as $i => $product): ?>
      <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
        <div class="flex items-center gap-3">
          <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg admin-gradient-bg text-white text-sm font-bold"><?= $i + 1 ?></span>
          <span class="font-medium text-slate-700"><?= e($product['name'] ?? 'Produto') ?></span>
        </div>
        <div class="text-right">
          <p class="font-bold text-slate-900">R$ <?= number_format($product['revenue'] ?? 0, 2, ',', '.') ?></p>
          <p class="text-xs text-slate-500"><?= $product['quantity'] ?? 0 ?> vendas</p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  
</div>

<!-- Ações -->
<div class="mt-6 flex justify-end gap-3">
  <a href="<?= base_url('admin/' . $activeSlug . '/financial/yearly?year=' . $currentYear) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Ver Ano Completo
  </a>
</div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
