<?php
// admin/categories/index.php — Lista de categorias (versão moderna)

$title = 'Categorias - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));

// helper de escape (se ainda não existir)

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage)) {
    $perPage = 10;
}

$allCats = $cats ?? [];
$totalItems = count($allCats);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;
$paginatedCats = array_slice($allCats, $offset, $perPage);

ob_start(); ?>

<div class="mx-auto max-w-6xl p-4">

<?php
// Configuração do Header Padrão
$pageTitle = 'Categorias';
$pageDescription = '';
$pageIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m-3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/></svg>';
$breadcrumbs = [
    ['label' => 'Categorias']
];
$actions = [
    ['label' => 'Nova', 'url' => base_url('admin/' . $slug . '/categories/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];
include __DIR__ . '/../components/page-header.php';
?>

<?php $isMostOrderedEnabled = !isset($company['show_most_ordered']) || (int)$company['show_most_ordered'] === 1; ?>
<div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
  <form method="post" action="<?= e(base_url('admin/' . $slug . '/categories/most-ordered')) ?>" class="flex items-center justify-between">
    <?php if (function_exists('csrf_field')): ?>
      <?= csrf_field() ?>
    <?php elseif (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php endif; ?>
    <input type="hidden" name="show_most_ordered" value="<?= $isMostOrderedEnabled ? '0' : '1' ?>">

    <span class="text-sm font-medium text-slate-700">Exibir seção "Mais Pedidos" na home do cardápio</span>

    <button type="submit"
            class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 <?= $isMostOrderedEnabled ? 'admin-primary-bg' : 'bg-slate-300' ?>"
            role="switch"
            aria-checked="<?= $isMostOrderedEnabled ? 'true' : 'false' ?>"
            aria-label="Exibir seção Mais Pedidos">
      <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out <?= $isMostOrderedEnabled ? 'translate-x-5' : 'translate-x-0' ?>"></span>
    </button>
  </form>
</div>

<?php if (empty($allCats)): ?>
  <!-- EMPTY STATE -->
  <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
    <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M6 6h12v12H6z" stroke="currentColor" stroke-width="1.6"/></svg>
    </div>
    <h2 class="text-lg font-medium text-slate-800">Nenhuma categoria cadastrada</h2>
    <p class="mt-1 text-sm text-slate-500">Crie a primeira categoria para organizar seus produtos.</p>
    <div class="mt-4">
      <a href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>"
         class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        Criar categoria
      </a>
    </div>
  </div>
<?php else: ?>

  <!-- TABELA -->
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-[600px] w-full">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
          <tr>
            <th class="p-3">Nome</th>
            <th class="p-3">Ordem</th>
            <th class="p-3">Status</th>
            <th class="p-3 text-right">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach ($paginatedCats as $c): ?>
            <tr class="hover:bg-slate-50/60">
              <td class="p-3 align-middle">
                <div class="font-medium text-slate-800"><?= e($c['name'] ?? '-') ?></div>
              </td>

              <td class="p-3 align-middle">
                <span class="rounded-lg bg-slate-50 px-2 py-0.5 text-[12px] text-slate-700 ring-1 ring-slate-200">
                  <?= (int)($c['sort_order'] ?? 0) ?>
                </span>
              </td>

              <td class="p-3 align-middle">
                <?php if (!empty($c['active'])): ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ativa
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[12px] font-medium text-slate-600 ring-1 ring-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inativa
                  </span>
                <?php endif; ?>
              </td>

              <td class="p-3 align-middle">
                <div class="flex justify-end gap-2">
                  <a class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                     href="<?= e(base_url('admin/' . $slug . '/categories/' . (int)$c['id'] . '/edit')) ?>">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    Editar
                  </a>

                  <form method="post"
                        action="<?= e(base_url('admin/' . $slug . '/categories/' . (int)$c['id'] . '/del')) ?>"
                        class="inline"
                        onsubmit="return confirm('Excluir categoria?');">
                    <?php if (function_exists('csrf_field')): ?>
                      <?= csrf_field() ?>
                    <?php elseif (function_exists('csrf_token')): ?>
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <?php endif; ?>
                    <button class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                      Excluir
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
        $baseUrl = base_url('admin/' . $slug . '/categories');
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
<?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
