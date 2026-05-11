<?php
/**
 * Análise Financeira de Produtos
 * Estilo consistente com Analytics
 */

$title = 'Análise de Produtos - ' . ($company['name'] ?? '');
$products = $productsCosts ?? [];
ob_start();

// Configuração do header padronizado
$pageTitle = 'Análise de Produtos';
$pageDescription = 'Performance financeira por produto';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Financeiro', 'url' => base_url('admin/' . $activeSlug . '/financial')],
    ['label' => 'Produtos']
];
$actions = [
    ['label' => 'Custos', 'url' => base_url('admin/' . $activeSlug . '/product-costs'), 'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>']
];
?>

<div class="mx-auto max-w-7xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- Cards de Resumo -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">Total Produtos</span>
    </div>
    <p class="text-xl font-bold text-slate-900"><?= count($products ?? []) ?></p>
  </div>
  
  <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-emerald-600">Margem Alta</span>
    </div>
    <?php 
    $highMargin = 0;
    foreach ($products ?? [] as $p) {
      if (($p['profit_margin'] ?? 0) >= 30) $highMargin++;
    }
    ?>
    <p class="text-xl font-bold text-emerald-700"><?= $highMargin ?></p>
  </div>
  
  <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-amber-600">Margem Média</span>
    </div>
    <?php 
    $medMargin = 0;
    foreach ($products ?? [] as $p) {
      $m = $p['profit_margin'] ?? 0;
      if ($m >= 20 && $m < 30) $medMargin++;
    }
    ?>
    <p class="text-xl font-bold text-amber-700"><?= $medMargin ?></p>
  </div>
  
  <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-red-600">Margem Baixa</span>
    </div>
    <?php 
    $lowMargin = 0;
    foreach ($products ?? [] as $p) {
      if (($p['profit_margin'] ?? 0) < 20) $lowMargin++;
    }
    ?>
    <p class="text-xl font-bold text-red-700"><?= $lowMargin ?></p>
  </div>
</div>

<!-- Tabela de Produtos -->
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
  <div class="p-4 border-b border-slate-200 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Análise por Produto</h2>
    </div>
    <div class="relative">
      <input type="text" id="searchProduct" placeholder="Buscar produto..." class="pl-10 pr-4 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500 w-64">
      <svg class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
  </div>
  
  <?php if (empty($products)): ?>
  <div class="p-12 text-center">
    <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-4">
      <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <h3 class="text-lg font-medium text-slate-900 mb-1">Nenhum produto analisado</h3>
    <p class="text-slate-500 mb-4">Configure os custos dos produtos para ver a análise</p>
    <a href="<?= base_url('admin/' . $activeSlug . '/product-costs') ?>" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2.5 text-white shadow-sm hover:opacity-95 transition">
      Configurar Custos
    </a>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm" id="productsTable">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left font-medium text-slate-700">Produto</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Preço Venda</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Custo Total</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Lucro Unit.</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Margem</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Vendas</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Receita</th>
          <th class="px-4 py-3 text-center font-medium text-slate-700">Ação</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($products as $product): 
          $price = $product['sale_price'] ?? 0;
          $cost = $product['total_cost'] ?? 0;
          $profit = $product['profit'] ?? ($price - $cost);
          $margin = $product['profit_margin'] ?? ($price > 0 ? ($profit / $price) * 100 : 0);
          $quantity = $product['quantity_sold'] ?? 0;
          $revenue = $product['revenue'] ?? ($price * $quantity);
          $productId = $product['product_id'] ?? 0;
        ?>
        <tr class="hover:bg-slate-50 product-row">
          <td class="px-4 py-3">
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-100 flex-shrink-0">
                <?php if (!empty($product['image'])): ?>
                <img src="<?= e($product['image']) ?>" alt="" class="h-full w-full object-cover">
                <?php else: ?>
                <div class="h-full w-full flex items-center justify-center admin-gradient-bg text-white text-xs font-bold">
                  <?= strtoupper(substr($product['name'] ?? 'P', 0, 2)) ?>
                </div>
                <?php endif; ?>
              </div>
              <div>
                <p class="font-medium text-slate-900 product-name"><?= e($product['name'] ?? 'Produto') ?></p>
                <p class="text-xs text-slate-500"><?= e($product['category_name'] ?? '') ?></p>
              </div>
            </div>
          </td>
          <td class="px-4 py-3 text-right text-slate-600">R$ <?= number_format($price, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right text-slate-600">R$ <?= number_format($cost, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right font-medium <?= $profit >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">R$ <?= number_format($profit, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right">
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $margin >= 30 ? 'bg-emerald-100 text-emerald-700' : ($margin >= 20 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
              <?= number_format($margin, 1, ',', '.') ?>%
            </span>
          </td>
          <td class="px-4 py-3 text-right text-slate-600"><?= number_format($quantity, 0, ',', '.') ?></td>
          <td class="px-4 py-3 text-right font-medium text-slate-900">R$ <?= number_format($revenue, 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-center">
            <a href="<?= base_url('admin/' . $activeSlug . '/product-costs/' . $productId . '/edit') ?>" class="inline-flex items-center gap-1 text-sm text-purple-600 hover:text-purple-700">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Editar
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

</div>

<script>
document.getElementById('searchProduct')?.addEventListener('input', function(e) {
  const search = e.target.value.toLowerCase();
  document.querySelectorAll('.product-row').forEach(row => {
    const name = row.querySelector('.product-name')?.textContent.toLowerCase() || '';
    row.style.display = name.includes(search) ? '' : 'none';
  });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
