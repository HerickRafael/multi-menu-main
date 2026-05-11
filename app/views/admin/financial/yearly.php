<?php
/**
 * Relatório Financeiro Anual
 * Estilo consistente com Analytics
 */

$title = 'Relatório Anual - ' . ($company['name'] ?? '');
ob_start();

$currentYear = $year ?? date('Y');

// Nomes dos meses
$monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// Totais anuais
$totalRevenue = $yearlySummary['gross_revenue'] ?? 0;
$totalExpenses = $yearlySummary['total_expenses'] ?? 0;
$totalCosts = 0; // CMV não disponível no resumo anual
$totalProfit = $yearlySummary['net_profit'] ?? ($totalRevenue - $totalExpenses);
$avgMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

// Configuração do header padronizado
$pageTitle = 'Relatório Anual';
$pageDescription = 'Ano ' . e($currentYear);
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Financeiro', 'url' => base_url('admin/' . $activeSlug . '/financial')],
    ['label' => 'Anual']
];
$actions = [];

// Seletor de ano como extra content
ob_start();
?>
<form action="<?= base_url('admin/' . $activeSlug . '/financial/yearly') ?>" method="GET" class="flex items-center gap-2">
  <select name="year" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
    <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
    <?php endfor; ?>
  </select>
  <button type="submit" class="inline-flex items-center gap-1 rounded-xl admin-gradient-bg px-4 py-2 text-white text-sm hover:opacity-95 transition">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Ver
  </button>
</form>
<?php $extraHeaderContent = ob_get_clean(); ?>

<div class="mx-auto max-w-7xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- Cards de Totais Anuais -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">Receita Total</span>
    </div>
    <p class="text-xl font-bold text-slate-900">R$ <?= number_format($totalRevenue, 2, ',', '.') ?></p>
  </div>
  
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">CMV Total</span>
    </div>
    <p class="text-xl font-bold text-slate-900">R$ <?= number_format($totalCosts, 2, ',', '.') ?></p>
  </div>
  
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">Despesas Total</span>
    </div>
    <p class="text-xl font-bold text-slate-900">R$ <?= number_format($totalExpenses, 2, ',', '.') ?></p>
  </div>
  
  <div class="rounded-2xl border <?= $totalProfit >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' ?> p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg <?= $totalProfit >= 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' ?>">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm <?= $totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">Lucro Total</span>
    </div>
    <p class="text-xl font-bold <?= $totalProfit >= 0 ? 'text-emerald-700' : 'text-red-700' ?>">R$ <?= number_format($totalProfit, 2, ',', '.') ?></p>
  </div>
  
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">Margem Média</span>
    </div>
    <p class="text-xl font-bold <?= $avgMargin >= 20 ? 'text-emerald-600' : ($avgMargin >= 10 ? 'text-amber-600' : 'text-red-600') ?>"><?= number_format($avgMargin, 1, ',', '.') ?>%</p>
  </div>
</div>

<!-- Tabela Mensal -->
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
  <div class="p-4 border-b border-slate-200">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Detalhamento Mensal</h2>
    </div>
  </div>
  
<?php
// Indexar $monthlyTrend por número do mês do ano selecionado
$trendByMonth = [];
foreach ($monthlyTrend as $entry) {
    if (str_starts_with($entry['month'], $currentYear . '-')) {
        $mNum = (int)substr($entry['month'], 5, 2);
        $trendByMonth[$mNum] = $entry;
    }
}
?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left font-medium text-slate-700">Mês</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Receita</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Despesas</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Lucro</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Margem</th>
          <th class="px-4 py-3 text-center font-medium text-slate-700">Ação</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php for ($m = 1; $m <= 12; $m++):
          $monthData = $trendByMonth[$m] ?? [];
          $revenue = $monthData['revenue'] ?? 0;
          $expenses = $monthData['expenses'] ?? 0;
          $profit = $monthData['profit'] ?? ($revenue - $expenses);
          $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
          $monthKey = $currentYear . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3 font-medium text-slate-900"><?= $monthNames[$m - 1] ?></td>
          <td class="px-4 py-3 text-right text-slate-600">R$ <?= number_format($revenue, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right text-slate-600">R$ <?= number_format($expenses, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right font-medium <?= $profit >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">R$ <?= number_format($profit, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right">
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $margin >= 20 ? 'bg-emerald-100 text-emerald-700' : ($margin >= 10 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
              <?= number_format($margin, 1, ',', '.') ?>%
            </span>
          </td>
          <td class="px-4 py-3 text-center">
            <a href="<?= base_url('admin/' . $activeSlug . '/financial/monthly?month=' . $monthKey) ?>" class="inline-flex items-center gap-1 text-sm text-purple-600 hover:text-purple-700">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Ver
            </a>
          </td>
        </tr>
        <?php endfor; ?>
      </tbody>
      <tfoot class="bg-slate-100 font-bold">
        <tr>
          <td class="px-4 py-3 text-slate-900">Total</td>
          <td class="px-4 py-3 text-right text-slate-900">R$ <?= number_format($totalRevenue, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right text-slate-900">R$ <?= number_format($totalExpenses, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right <?= $totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">R$ <?= number_format($totalProfit, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right">
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $avgMargin >= 20 ? 'bg-emerald-100 text-emerald-700' : ($avgMargin >= 10 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
              <?= number_format($avgMargin, 1, ',', '.') ?>%
            </span>
          </td>
          <td class="px-4 py-3"></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
