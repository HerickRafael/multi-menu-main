<?php
/**
 * Lista de Insumos/Embalagens
 * Estilo consistente com Analytics
 */

$title = 'Insumos & Embalagens - ' . ($company['name'] ?? '');

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage)) {
    $perPage = 10;
}

$allSupplies = $supplies ?? [];
$totalItems = count($allSupplies);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;
$paginatedSupplies = array_slice($allSupplies, $offset, $perPage);

ob_start();

// Configuração do header padronizado
$pageTitle = 'Insumos & Embalagens';
$pageDescription = 'Gerencie embalagens e outros insumos para cálculo de custos';
$pageIcon = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
$breadcrumbs = [
    ['label' => 'Insumos & Embalagens']
];
$actions = [
    ['label' => 'Novo', 'url' => base_url('admin/' . $activeSlug . '/packaging/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];
?>

<div class="mx-auto max-w-6xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<?php if (!empty($success)): ?>
<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="text-emerald-700">
        <?php 
        $messages = [
            'created' => 'Insumo criado com sucesso!',
            'updated' => 'Insumo atualizado com sucesso!',
            'deleted' => 'Insumo removido com sucesso!',
        ];
        echo e($messages[$success] ?? 'Operação realizada com sucesso!');
        ?>
    </span>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="text-red-700">
        <?php 
        $errors = [
            'notfound' => 'Insumo não encontrado.',
            'name' => 'O nome é obrigatório.',
            'duplicate' => 'Já existe um insumo com este nome.',
        ];
        echo e($errors[$error] ?? 'Erro ao processar a operação.');
        ?>
    </span>
</div>
<?php endif; ?>

<!-- Resumo -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <?php 
  $totalSupplies = count($allSupplies);
  $activeSupplies = count(array_filter($allSupplies, fn($s) => $s['active']));
  $totalValue = array_sum(array_map(fn($s) => (float)$s['cost_per_unit'] * (float)$s['stock_quantity'], $allSupplies));
  $lowStock = count(array_filter($allSupplies, fn($s) => $s['stock_quantity'] <= $s['min_stock_alert'] && $s['min_stock_alert'] > 0));
  ?>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <p class="text-sm text-slate-500 mb-1">Total de Insumos</p>
    <p class="text-2xl font-bold text-slate-900"><?= $totalSupplies ?></p>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <p class="text-sm text-slate-500 mb-1">Ativos</p>
    <p class="text-2xl font-bold text-emerald-600"><?= $activeSupplies ?></p>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <p class="text-sm text-slate-500 mb-1">Valor em Estoque</p>
    <p class="text-2xl font-bold text-purple-600">R$ <?= number_format($totalValue, 2, ',', '.') ?></p>
  </div>
  <div class="rounded-2xl border <?= $lowStock > 0 ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' ?> p-4 shadow-sm">
    <p class="text-sm <?= $lowStock > 0 ? 'text-amber-600' : 'text-slate-500' ?> mb-1">Estoque Baixo</p>
    <p class="text-2xl font-bold <?= $lowStock > 0 ? 'text-amber-700' : 'text-slate-900' ?>"><?= $lowStock ?></p>
  </div>
</div>

<!-- Lista -->
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
  <?php if (empty($allSupplies)): ?>
  <div class="text-center py-16">
    <svg class="h-16 w-16 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
    </svg>
    <h3 class="text-lg font-medium text-slate-700 mb-2">Nenhum insumo cadastrado</h3>
    <p class="text-slate-500 mb-4">Comece cadastrando embalagens e outros insumos</p>
    <a href="<?= e(base_url('admin/' . $activeSlug . '/packaging/create')) ?>" 
       class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-5 py-2.5 text-white font-medium hover:opacity-90 transition">
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
      </svg>
      Cadastrar Primeiro Insumo
    </a>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr>
          <th class="px-4 py-3 text-left font-medium text-slate-700">Nome</th>
          <th class="px-4 py-3 text-center font-medium text-slate-700">Unidade</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Custo/Un</th>
          <th class="px-4 py-3 text-center font-medium text-slate-700">Estoque</th>
          <th class="px-4 py-3 text-center font-medium text-slate-700">Status</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($paginatedSupplies as $supply): 
          $lowStockClass = ($supply['stock_quantity'] <= $supply['min_stock_alert'] && $supply['min_stock_alert'] > 0) 
            ? 'text-amber-600 font-medium' : 'text-slate-600';
        ?>
        <tr class="hover:bg-slate-50 transition <?= !$supply['active'] ? 'opacity-50' : '' ?>">
          <td class="px-4 py-3">
            <div class="font-medium text-slate-900"><?= e($supply['name']) ?></div>
            <?php if (!empty($supply['description'])): ?>
            <div class="text-xs text-slate-500 mt-0.5"><?= e(mb_substr($supply['description'], 0, 50)) ?><?= mb_strlen($supply['description']) > 50 ? '...' : '' ?></div>
            <?php endif; ?>
            <?php if (!empty($supply['supplier'])): ?>
            <div class="text-xs text-slate-400 mt-0.5">📦 <?= e($supply['supplier']) ?></div>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-center text-slate-600"><?= e($supply['unit']) ?></td>
          <td class="px-4 py-3 text-right font-medium text-slate-900">R$ <?= number_format((float)$supply['cost_per_unit'], 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-center <?= $lowStockClass ?>">
            <?= number_format((float)$supply['stock_quantity'], 2, ',', '.') ?>
            <?php if ($supply['stock_quantity'] <= $supply['min_stock_alert'] && $supply['min_stock_alert'] > 0): ?>
            <span class="ml-1" title="Estoque baixo">⚠️</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-center">
            <?php if ($supply['active']): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Ativo</span>
            <?php else: ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Inativo</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-right">
            <div class="flex items-center justify-end gap-2">
              <a href="<?= e(base_url('admin/' . $activeSlug . '/packaging/' . $supply['id'] . '/edit')) ?>" 
                 class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-purple-600 transition"
                 title="Editar">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </a>
              <form action="<?= e(base_url('admin/' . $activeSlug . '/packaging/' . $supply['id'] . '/delete')) ?>" method="POST" 
                    onsubmit="return confirm('Tem certeza que deseja excluir este insumo?');" class="inline">
                <button type="submit" 
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition"
                        title="Excluir">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
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
      $baseUrl = base_url('admin/' . $activeSlug . '/packaging');
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
  <?php endif; ?>
</div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
