<?php
// admin/products/index.php — Lista de produtos (versão moderna + filtro de status)

$title = 'Produtos - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));

// helper de escape (se ainda não existir)

$q       = trim((string)($_GET['q'] ?? ''));
$cat     = (string)($_GET['cat'] ?? '');
$status  = (string)($_GET['status'] ?? ''); // '' | '1' | '0'

// mapa de categorias por id
$catsById = [];

foreach (($cats ?? []) as $c) {
    $catsById[(string)$c['id']] = $c['name'];
}

ob_start(); ?>

<div class="mx-auto max-w-6xl p-4">

<?php
// Configuração do Header Padrão
$pageTitle = 'Produtos';
$pageDescription = '';
$pageIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/></svg>';
$breadcrumbs = [
    ['label' => 'Produtos']
];
$actions = [
    ['label' => 'Cross-Sell', 'url' => base_url('admin/' . $slug . '/cross-sell-groups'), 'icon' => '<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg>'],
    ['label' => 'Novo', 'url' => base_url('admin/' . $slug . '/products/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];
include __DIR__ . '/../components/page-header.php';
?>

<!-- ALERTA DE ERRO -->
<?php if (!empty($error)): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<!-- BUSCA / FILTROS -->
<form method="get" class="mb-4 grid gap-2 sm:grid-cols-[1fr_220px_180px_auto]">
  <label class="relative">
    <span class="sr-only">Buscar</span>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome ou descrição"
           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pl-9 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none">
      <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
  </label>

  <label>
    <span class="sr-only">Categoria</span>
    <select name="cat" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      <option value="">Todas as categorias</option>
      <?php foreach (($cats ?? []) as $c): ?>
        <option value="<?= e((string)$c['id']) ?>" <?= $cat !== '' && (string)$c['id'] === $cat ? 'selected' : '' ?>>
          <?= e($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>
    <span class="sr-only">Status</span>
    <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      <option value=""  <?= $status === '' ? 'selected' : '' ?>>Todos</option>
      <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Ativos</option>
      <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inativos</option>
    </select>
  </label>

  <div class="flex gap-2">
    <button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      Filtrar
    </button>
    <?php if ($q !== '' || $cat !== '' || $status !== ''): ?>
      <a href="<?= e(base_url('admin/' . $slug . '/products')) ?>"
         class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
        Limpar
      </a>
    <?php endif; ?>
  </div>
</form>

<?php
// aplica filtro na lista em memória (se o controller já não filtrar)
$filtered = $items ?? [];

// texto
if ($q !== '') {
    $qNorm = mb_strtolower($q, 'UTF-8');
    $filtered = array_filter($filtered, function ($p) use ($qNorm) {
        $name = mb_strtolower((string)($p['name'] ?? ''), 'UTF-8');
        $desc = mb_strtolower((string)($p['description'] ?? ''), 'UTF-8');

        return (strpos($name, $qNorm) !== false) || (strpos($desc, $qNorm) !== false);
    });
}

// categoria
if ($cat !== '') {
    $filtered = array_filter($filtered, fn ($p) => (string)($p['category_id'] ?? '') === $cat);
}

// status (aqui mostramos TODOS por padrão; só filtramos se usuário escolher)
if ($status !== '') {
    $filtered = array_filter($filtered, fn ($p) => (string)((int)($p['active'] ?? 0)) === $status);
}

// Reindexar array após filtros
$filtered = array_values($filtered);

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage)) {
    $perPage = 10;
}

$totalItems = count($filtered);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages); // Não permitir página maior que o total

$offset = ($page - 1) * $perPage;
$paginatedItems = array_slice($filtered, $offset, $perPage);
?>

<?php if (empty($filtered)): ?>
  <!-- EMPTY STATE -->
  <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
    <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M7 12h10M10 17h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </div>
    <h2 class="text-lg font-medium text-slate-800">Nenhum produto encontrado</h2>
    <p class="mt-1 text-sm text-slate-500">Ajuste os filtros ou crie um novo produto.</p>
    <div class="mt-4">
      <a href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>"
        class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        Criar produto
      </a>
    </div>
  </div>
<?php else: ?>

  <!-- TABELA -->
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-[720px] w-full">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
          <tr>
            <th class="p-3">Imagem</th>
            <th class="p-3">Nome</th>
            <th class="p-3">Categoria</th>
            <th class="p-3">Preço</th>
            <th class="p-3">Promo</th>
            <th class="p-3">Status</th>
            <th class="p-3 text-right">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach ($paginatedItems as $p): ?>
            <tr class="hover:bg-slate-50/60">
              <td class="p-3">
                <?php if (!empty($p['image'])): ?>
                  <img src="<?= e(base_url($p['image'])) ?>" alt="" class="h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200">
                <?php else: ?>
                  <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
                <?php endif; ?>
              </td>

              <td class="p-3 align-middle">
                <div class="font-medium text-slate-800"><?= e($p['name'] ?? '-') ?></div>
                <?php if (!empty($p['sku'])): ?>
                  <div class="text-xs text-slate-500">SKU: <?= e($p['sku']) ?></div>
                <?php endif; ?>
              </td>

              <td class="p-3 align-middle text-slate-700">
                <?= e($catsById[(string)($p['category_id'] ?? '')] ?? '-') ?>
              </td>

              <td class="p-3 align-middle">
                <span class="whitespace-nowrap text-slate-800">
                  R$ <?= number_format((float)($p['price'] ?? 0), 2, ',', '.') ?>
                </span>
              </td>

              <td class="p-3 align-middle">
                <?php if (!empty($p['promo_price'])): ?>
                  <?php 
                    // Detectar se é modo porcentagem ou valor fixo
                    $isPercentageMode = ($p['price_mode'] ?? 'fixed') === 'sum';
                    $promoValue = (float)$p['promo_price'];
                    $basePrice = (float)($p['price'] ?? 0);
                    
                    if ($isPercentageMode && $promoValue > 0 && $promoValue <= 100) {
                      // Modo porcentagem: calcular o valor com desconto
                      $discountedPrice = $basePrice * (1 - ($promoValue / 100));
                      $displayText = 'R$ ' . number_format($discountedPrice, 2, ',', '.') . ' (' . number_format($promoValue, 0) . '% OFF)';
                    } else {
                      // Modo fixo: mostrar valor promocional diretamente
                      $displayText = 'R$ ' . number_format($promoValue, 2, ',', '.');
                    }
                  ?>
                  <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg bg-green-50 px-2 py-0.5 text-[12px] font-medium text-green-700 ring-1 ring-green-200">
                    <?= $displayText ?>
                    <span class="hidden sm:inline text-green-600/70">promo</span>
                  </span>
                <?php else: ?>
                  <span class="text-slate-400">—</span>
                <?php endif; ?>
              </td>

              <td class="p-3 align-middle">
                <?php if (!empty($p['active'])): ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ativo
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[12px] font-medium text-slate-600 ring-1 ring-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inativo
                  </span>
                <?php endif; ?>
              </td>

              <td class="p-3 align-middle">
                <div class="flex justify-end gap-2">
                  <a class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                     href="<?= e(base_url('admin/' . $slug . '/products/' . (int)$p['id'] . '/edit')) ?>">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    Editar
                  </a>

                  <form method="post"
                        action="<?= e(base_url('admin/' . $slug . '/products/' . (int)$p['id'] . '/del')) ?>"
                        class="inline"
                        onsubmit="return confirm('Excluir produto?');">
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
        $baseUrl = base_url('admin/' . $slug . '/products');
        $queryParams = [];
        if ($q) $queryParams['q'] = $q;
        if ($cat) $queryParams['cat'] = $cat;
        if ($status !== '') $queryParams['status'] = $status;
        $queryParams['per_page'] = $perPage;
        
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
