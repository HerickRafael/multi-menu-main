<?php
/**
 * Lista de Custos de Produtos
 * Estilo consistente com Analytics
 */

$title = 'Custos de Produtos - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage)) {
    $perPage = 10;
}

$allProductsCosts = $productsCosts ?? [];
$totalItems = count($allProductsCosts);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;
$paginatedProductsCosts = array_slice($allProductsCosts, $offset, $perPage);

ob_start();
?>

<div class="mx-auto max-w-7xl p-4">

<?php
// Configuração do Header Padrão
$pageTitle = 'Custos de Produtos';
$pageDescription = 'Configure custos adicionais para cálculo de margem';
$pageIcon = '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Custos de Produtos']
];
$actions = [
    ['label' => 'Dashboard', 'url' => base_url('admin/' . $activeSlug . '/financial'), 'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" stroke-linecap="round" stroke-linejoin="round"/><path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
    ['label' => 'Configurações', 'url' => base_url('admin/' . $activeSlug . '/financial/settings'), 'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
    ['label' => 'Aplicar em Lote', 'onclick' => 'showBulkModal()', 'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'primary' => true]
];
include __DIR__ . '/../components/page-header.php';
?>

<?php if ($success): ?>
<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="text-emerald-700">Custos atualizados com sucesso!</span>
</div>
<?php endif; ?>

<!-- MÉTRICAS -->
<div class="mb-6 grid grid-cols-2 md:grid-cols-5 gap-4">
  <?php
  $totalProducts = count($allProductsCosts);
  $highMargin = count(array_filter($allProductsCosts, fn($p) => ($p['profit_margin'] ?? 0) >= 30));
  $medMargin = count(array_filter($allProductsCosts, fn($p) => ($p['profit_margin'] ?? 0) >= 20 && ($p['profit_margin'] ?? 0) < 30));
  $lowMarginCount = count(array_filter($allProductsCosts, fn($p) => ($p['profit_margin'] ?? 0) < 20));
  $noIngredientCost = count(array_filter($allProductsCosts, fn($p) => ($p['ingredient_cost'] ?? 0) <= 0));
  ?>
  
  <!-- Total Produtos -->
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-500">Total Produtos</span>
    </div>
    <p class="text-xl font-bold text-slate-900"><?= $totalProducts ?></p>
  </div>
  
  <!-- Margem Alta -->
  <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-emerald-600">Margem Alta</span>
    </div>
    <p class="text-xl font-bold text-emerald-700"><?= $highMargin ?></p>
  </div>
  
  <!-- Margem Média -->
  <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-amber-600">Margem Média</span>
    </div>
    <p class="text-xl font-bold text-amber-700"><?= $medMargin ?></p>
  </div>
  
  <!-- Margem Baixa -->
  <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-red-600">Margem Baixa</span>
    </div>
    <p class="text-xl font-bold text-red-700"><?= $lowMarginCount ?></p>
  </div>
  
  <!-- Sem Custo Ingredientes -->
  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-200 text-slate-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-sm text-slate-600">Sem Custo Ingredientes</span>
    </div>
    <p class="text-xl font-bold text-slate-700"><?= $noIngredientCost ?></p>
  </div>
</div>

<!-- TABELA -->
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-200 p-4 flex justify-between items-center">
    <h3 class="text-lg font-semibold text-slate-900">Todos os Produtos</h3>
    <input type="text" id="searchProduct" placeholder="Buscar produto..." class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500">
  </div>
  <!-- Indicador de scroll horizontal em mobile -->
  <div class="relative">
    <div class="overflow-x-auto scrollbar-thin" id="tableScrollContainer">
      <table class="w-full text-sm min-w-[900px]" id="productsTable">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left font-medium text-slate-600">Produto</th>
          <th class="px-4 py-3 text-right font-medium text-slate-600">Preço</th>
          <th class="px-4 py-3 text-right font-medium text-slate-600">Ingredientes</th>
          <th class="px-4 py-3 text-right font-medium text-slate-600">Embalagem</th>
          <th class="px-4 py-3 text-right font-medium text-slate-600">M. Obra</th>
          <th class="px-4 py-3 text-right font-medium text-slate-600">Outros</th>
          <th class="px-4 py-3 text-right font-medium text-slate-600">Custo Total</th>
          <th class="px-4 py-3 text-right font-medium text-slate-600">Lucro</th>
          <th class="px-4 py-3 text-right font-medium text-slate-600">Margem</th>
          <th class="px-4 py-3 text-center font-medium text-slate-600">Ação</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($allProductsCosts)): ?>
          <tr><td colspan="10" class="px-4 py-12 text-center text-slate-500">Nenhum produto encontrado.</td></tr>
        <?php else: ?>
          <?php foreach ($paginatedProductsCosts as $product): ?>
            <?php
            $marginBadge = $product['profit_margin'] >= 40 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : ($product['profit_margin'] >= 20 ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-red-50 text-red-700 ring-1 ring-red-200');
            $hasWarning = $product['ingredient_cost'] <= 0;
            ?>
            <tr class="hover:bg-slate-50 transition product-row <?= $hasWarning ? 'bg-amber-50/50' : '' ?>" data-name="<?= strtolower($product['name']) ?>">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <?php if ($hasWarning): ?>
                    <span class="text-amber-500" title="Sem custo de ingredientes"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                  <?php endif; ?>
                  <span class="font-medium text-slate-800"><?= htmlspecialchars($product['name']) ?></span>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-slate-700">R$ <?= number_format($product['sale_price'], 2, ',', '.') ?></td>
              <td class="px-4 py-3 text-right <?= $hasWarning ? 'text-amber-600' : 'text-slate-700' ?>">R$ <?= number_format($product['ingredient_cost'], 2, ',', '.') ?></td>
              <td class="px-4 py-3 text-right text-slate-700">R$ <?= number_format($product['packaging_cost'], 2, ',', '.') ?></td>
              <td class="px-4 py-3 text-right text-slate-700">R$ <?= number_format($product['labor_cost'], 2, ',', '.') ?></td>
              <td class="px-4 py-3 text-right text-slate-700">R$ <?= number_format($product['other_costs'], 2, ',', '.') ?></td>
              <td class="px-4 py-3 text-right font-medium text-slate-900">R$ <?= number_format($product['total_cost'], 2, ',', '.') ?></td>
              <td class="px-4 py-3 text-right font-semibold <?= $product['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">R$ <?= number_format($product['profit'], 2, ',', '.') ?></td>
              <td class="px-4 py-3 text-right">
                <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-medium <?= $marginBadge ?>"><?= number_format($product['profit_margin'], 1, ',', '.') ?>%</span>
              </td>
              <td class="px-4 py-3 text-center">
                <a href="<?= base_url('admin/' . $activeSlug . '/product-costs/' . $product['product_id'] . '/edit') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-purple-600 transition">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
    <!-- Indicador de scroll (aparece em mobile) -->
    <div id="scrollIndicator" class="hidden md:hidden absolute right-0 top-0 bottom-0 w-8 pointer-events-none bg-gradient-to-l from-slate-200/80 to-transparent"></div>
  </div>
  
  <!-- PAGINAÇÃO -->
  <?php if ($totalPages > 1 || $totalItems > 10): ?>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 bg-slate-50 px-4 py-3">
    <div class="flex items-center gap-3 text-sm text-slate-600">
      <span>Itens por página:</span>
      <select onchange="updatePerPage(this.value)" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-sm">
        <?php foreach ([10, 25, 50, 100] as $opt): ?>
          <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
      <span class="text-slate-500">
        Mostrando <?= min($totalItems, $offset + 1) ?>-<?= min($totalItems, $offset + $perPage) ?> de <?= $totalItems ?>
      </span>
    </div>
    
    <div class="flex items-center gap-1">
      <?php
      $baseUrl = base_url('admin/' . $activeSlug . '/product-costs');
      $queryParams = ['per_page' => $perPage];
      
      $buildUrl = function($p) use ($baseUrl, $queryParams) {
          $queryParams['page'] = $p;
          return $baseUrl . '?' . http_build_query($queryParams);
      };
      ?>
      
      <!-- Anterior -->
      <a href="<?= $page > 1 ? e($buildUrl($page - 1)) : '#' ?>" 
         class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 <?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100' ?>">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      
      <!-- Números de página -->
      <?php
      $startPage = max(1, $page - 2);
      $endPage = min($totalPages, $page + 2);
      
      if ($startPage > 1): ?>
        <a href="<?= e($buildUrl(1)) ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-sm text-slate-600 hover:bg-slate-100">1</a>
        <?php if ($startPage > 2): ?>
          <span class="px-1 text-slate-400">...</span>
        <?php endif; ?>
      <?php endif; ?>
      
      <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <a href="<?= e($buildUrl($i)) ?>" 
           class="inline-flex h-8 w-8 items-center justify-center rounded-lg border text-sm <?= $i === $page ? 'admin-gradient-bg border-transparent text-white' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
      
      <?php if ($endPage < $totalPages): ?>
        <?php if ($endPage < $totalPages - 1): ?>
          <span class="px-1 text-slate-400">...</span>
        <?php endif; ?>
        <a href="<?= e($buildUrl($totalPages)) ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-sm text-slate-600 hover:bg-slate-100"><?= $totalPages ?></a>
      <?php endif; ?>
      
      <!-- Próximo -->
      <a href="<?= $page < $totalPages ? e($buildUrl($page + 1)) : '#' ?>" 
         class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 <?= $page >= $totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100' ?>">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </div>
  
  <script>
  function updatePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
  }
  </script>
  <?php endif; ?>
</div>

</div>

<!-- Modal de Confirmação Customizado -->
<div id="confirmDialog" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[60]">
  <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4 shadow-xl">
    <div class="flex items-center gap-3 mb-4">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <h3 class="text-lg font-semibold text-slate-900">Confirmar Ação</h3>
    </div>
    <p id="confirmDialogMessage" class="text-slate-600 mb-6"></p>
    <div class="flex justify-end gap-3">
      <button type="button" onclick="closeConfirmDialog()" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">Cancelar</button>
      <button type="button" onclick="confirmDialogAccept()" class="px-4 py-2 rounded-xl admin-gradient-bg text-white font-medium hover:opacity-95">Confirmar</button>
    </div>
  </div>
</div>

<!-- Modal Lote -->
<div id="bulkModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4 shadow-xl">
    <h3 class="text-lg font-semibold text-slate-900 mb-2">Aplicar Custos em Lote</h3>
    <p class="text-sm text-slate-600 mb-4">Os valores serão aplicados a <strong>todos os produtos</strong> ativos.</p>
    <form id="bulkForm" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Custo de Embalagem (R$)</label>
        <input type="number" name="packaging_cost" id="bulkPackaging" step="0.01" min="0" placeholder="0,00" class="w-full rounded-xl border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Custo de Mão de Obra (R$)</label>
        <input type="number" name="labor_cost" id="bulkLabor" step="0.01" min="0" placeholder="0,00" class="w-full rounded-xl border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Taxa de Imposto (%)</label>
        <input type="number" name="tax_rate" id="bulkTax" step="0.01" min="0" max="100" placeholder="0,00" class="w-full rounded-xl border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-purple-500">
      </div>
      <div class="flex justify-end gap-3 pt-4">
        <button type="button" onclick="closeBulkModal()" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">Cancelar</button>
        <button type="submit" class="px-4 py-2 rounded-xl admin-gradient-bg text-white font-medium hover:opacity-95">Aplicar a Todos</button>
      </div>
    </form>
  </div>
</div>

<script>
const baseUrl = '<?= base_url('admin/' . $activeSlug) ?>';

// Indicador de scroll horizontal em mobile
(function() {
    const container = document.getElementById('tableScrollContainer');
    const indicator = document.getElementById('scrollIndicator');
    if (container && indicator) {
        function updateScrollIndicator() {
            const canScroll = container.scrollWidth > container.clientWidth;
            const isAtEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 10;
            indicator.classList.toggle('hidden', !canScroll || isAtEnd);
        }
        container.addEventListener('scroll', updateScrollIndicator);
        window.addEventListener('resize', updateScrollIndicator);
        updateScrollIndicator();
    }
})();

document.getElementById('searchProduct').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.product-row').forEach(row => {
        row.style.display = row.dataset.name.includes(search) ? '' : 'none';
    });
});

function showBulkModal() {
    document.getElementById('bulkModal').classList.remove('hidden');
    document.getElementById('bulkModal').classList.add('flex');
}

function closeBulkModal() {
    document.getElementById('bulkModal').classList.add('hidden');
    document.getElementById('bulkModal').classList.remove('flex');
}

document.getElementById('bulkModal').addEventListener('click', function(e) {
    if (e.target === this) closeBulkModal();
});

// Fechar modal com Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBulkModal();
        closeConfirmDialog();
    }
});

// Sistema de diálogo de confirmação customizado
function showConfirmDialog(message, onConfirm) {
    const dialog = document.getElementById('confirmDialog');
    document.getElementById('confirmDialogMessage').textContent = message;
    dialog.classList.remove('hidden');
    dialog.classList.add('flex');
    window._pendingConfirmCallback = onConfirm;
    // Focus trap
    dialog.querySelector('button').focus();
}

function closeConfirmDialog() {
    const dialog = document.getElementById('confirmDialog');
    dialog.classList.add('hidden');
    dialog.classList.remove('flex');
    window._pendingConfirmCallback = null;
}

function confirmDialogAccept() {
    if (window._pendingConfirmCallback) {
        window._pendingConfirmCallback();
    }
    closeConfirmDialog();
}

document.getElementById('bulkForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    showConfirmDialog('Aplicar estes custos a TODOS os produtos ativos?', async () => {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin h-4 w-4 inline-block mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processando...';
        
        const data = {
            packaging_cost: parseFloat(document.getElementById('bulkPackaging').value) || 0,
            labor_cost: parseFloat(document.getElementById('bulkLabor').value) || 0,
            tax_rate: parseFloat(document.getElementById('bulkTax').value) || 0
        };
        
        try {
            const response = await fetch(baseUrl + '/product-costs/bulk-update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                ToastSystem.success(result.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                ToastSystem.error('Erro: ' + (result.message || 'Erro desconhecido'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            ToastSystem.error('Erro: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
