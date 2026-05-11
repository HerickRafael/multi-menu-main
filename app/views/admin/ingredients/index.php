<?php
// admin/ingredients/index.php — Lista de ingredientes (versão moderna, sem coluna de Produtos)

// Helpers (caso a view seja renderizada isolada)

$title = 'Ingredientes - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));
$selectedProduct = $productId ?? null;
$search = trim((string)($q ?? ''));

// Normaliza lista de produtos (para filtro)
$products = $products ?? [];
$allItems = $items ?? [];

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage)) {
    $perPage = 10;
}

$totalItems = count($allItems);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;
$paginatedItems = array_slice($allItems, $offset, $perPage);

ob_start(); ?>

<div class="mx-auto max-w-6xl p-4">

<?php
// Configuração do Header Padrão
$pageTitle = 'Ingredientes';
$pageDescription = '';
$pageIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M13.902.334a.5.5 0 0 1-.28.65l-2.254.902-.4 1.927c.376.095.715.215.972.367.228.135.56.396.56.82q0 .069-.011.132l-.962 9.068a1.28 1.28 0 0 1-.524.93c-.488.34-1.494.87-3.01.87s-2.522-.53-3.01-.87a1.28 1.28 0 0 1-.524-.93L3.51 5.132A1 1 0 0 1 3.5 5c0-.424.332-.685.56-.82.262-.154.607-.276.99-.372C5.824 3.614 6.867 3.5 8 3.5c.712 0 1.389.045 1.985.127l.464-2.215a.5.5 0 0 1 .303-.356l2.5-1a.5.5 0 0 1 .65.278M9.768 4.607A14 14 0 0 0 8 4.5c-1.076 0-2.033.11-2.707.278A3.3 3.3 0 0 0 4.645 5c.146.073.362.15.648.222C5.967 5.39 6.924 5.5 8 5.5c.571 0 1.109-.03 1.588-.085zm.292 1.756C9.445 6.45 8.742 6.5 8 6.5c-1.133 0-2.176-.114-2.95-.308a6 6 0 0 1-.435-.127l.838 8.03c.013.121.06.186.102.215.357.249 1.168.69 2.438.69s2.081-.441 2.438-.69c.042-.029.09-.094.102-.215l.852-8.03a6 6 0 0 1-.435.127 9 9 0 0 1-.89.17zM4.467 4.884s.003.002.005.006zm7.066 0-.005.006zM11.354 5a3 3 0 0 0-.604-.21l-.099.445.055-.013c.286-.072.502-.149.648-.222"/></svg>';
$breadcrumbs = [
    ['label' => 'Ingredientes']
];
$actions = [
    ['label' => 'Novo', 'url' => base_url('admin/' . $slug . '/ingredients/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];
include __DIR__ . '/../components/page-header.php';
?>

<!-- ALERTA DE ERRO -->
<?php if (!empty($error)): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<!-- FILTROS -->
<form method="get" class="mb-4 grid gap-2 sm:grid-cols-[minmax(220px,280px)_1fr_auto]">
  <label class="grid">
    <span class="sr-only">Produto</span>
    <select name="product_id"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      <option value="">Todos os produtos</option>
      <?php foreach ($products as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= ($selectedProduct === (int)$p['id']) ? 'selected' : '' ?>>
          <?= e($p['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="relative">
    <span class="sr-only">Buscar</span>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por nome do ingrediente"
           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pl-9 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none">
      <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
  </label>

  <div class="flex gap-2">
    <button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      Filtrar
    </button>
    <?php if ($search !== '' || $selectedProduct): ?>
      <a href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>"
         class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
        Limpar
      </a>
    <?php endif; ?>
  </div>
</form>

<?php if (!empty($allItems)): ?>
  <!-- TABELA -->
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-[820px] w-full">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
          <tr>
            <th class="p-3">Ingrediente</th>
            <th class="p-3">Custo</th>
            <th class="p-3">Valor de venda</th>
            <th class="p-3">Unidade</th>
            <th class="p-3 text-center">Visível</th>
            <th class="p-3 text-right">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
        <?php foreach ($paginatedItems as $item): ?>
          <tr class="hover:bg-slate-50/60<?= empty($item['active']) ? ' ingredient-inactive' : '' ?>" data-id="<?= (int)$item['id'] ?>">
            <!-- Ingrediente + imagem -->
            <td class="p-3">
              <div class="flex items-center gap-3">
                <?php if (!empty($item['image_path'])): ?>
                  <img src="<?= e(base_url($item['image_path'])) ?>" alt=""
                       class="h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200">
                <?php else: ?>
                  <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
                <?php endif; ?>

                <div>
                  <div class="font-medium text-slate-800">
                    <?= e($item['name'] ?? '') ?>
                    <?php if (!empty($item['internal_name'])): ?>
                      <span class="text-slate-500">(<?= e($item['internal_name']) ?>)</span>
                    <?php endif; ?>
                  </div>
                  <?php $created = !empty($item['created_at']) ? date('d/m/Y', strtotime((string)$item['created_at'])) : null; ?>
                  <?php if ($created): ?>
                    <div class="text-xs text-slate-500">Criado em <?= e($created) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>

            <!-- Custo -->
            <td class="p-3 align-middle text-slate-700">
              <?= price_br($item['cost'] ?? 0) ?>
            </td>

            <!-- Venda -->
            <td class="p-3 align-middle text-slate-700">
              <?= price_br($item['sale_price'] ?? 0) ?>
            </td>

            <!-- Unidade -->
            <td class="p-3 align-middle text-slate-700">
              <?php
                $uVal = $item['unit_value'] ?? null;

            if ($uVal !== null && $uVal !== '') {
                if (!is_string($uVal)) {
                    $uVal = rtrim(rtrim(number_format((float)$uVal, 3, ',', '.'), '0'), ',');
                }
            }
            $uTxt = trim((string)($item['unit'] ?? ''));
            $unitDisplay = trim(($uVal !== null && $uVal !== '' ? $uVal : '1') . ' ' . $uTxt);
            ?>
              <?= e($unitDisplay) ?>
            </td>

            <!-- Visível -->
            <td class="p-3 align-middle text-center">
              <button type="button"
                      class="btn-toggle-ingredient inline-flex items-center justify-center rounded-lg p-1.5 transition-colors hover:bg-slate-100"
                      data-id="<?= (int)$item['id'] ?>"
                      data-active="<?= (int)($item['active'] ?? 1) ?>"
                      title="<?= empty($item['active']) ? 'Ativar' : 'Ocultar' ?>">
                <?php if (empty($item['active'])): ?>
                  <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                <?php else: ?>
                  <svg class="h-5 w-5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <?php endif; ?>
              </button>
            </td>

            <!-- Ações -->
            <td class="p-3 align-middle">
              <div class="flex justify-end gap-2">
                <a class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                   href="<?= e(base_url('admin/' . $slug . '/ingredients/' . (int)$item['id'] . '/edit')) ?>">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                  Editar
                </a>

                <form method="post"
                      action="<?= e(base_url('admin/' . $slug . '/ingredients/' . (int)$item['id'] . '/del')) ?>"
                      class="inline"
                      onsubmit="return confirm('Excluir ingrediente?');">
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
        $baseUrl = base_url('admin/' . $slug . '/ingredients');
        $queryParams = ['per_page' => $perPage];
        if ($selectedProduct) $queryParams['product_id'] = $selectedProduct;
        if ($search) $queryParams['q'] = $search;
        
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
<?php else: ?>
  <!-- EMPTY STATE -->
  <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
    <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M7 12h10M10 17h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </div>
    <h2 class="text-lg font-medium text-slate-800">Nenhum ingrediente encontrado</h2>
    <p class="mt-1 text-sm text-slate-500">Ajuste os filtros ou crie um novo ingrediente.</p>
    <div class="mt-4">
      <a href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>"
         class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        Criar ingrediente
      </a>
    </div>
  </div>
<?php endif; ?>

</div>

<style>
.ingredient-inactive { opacity: 0.45; }
.ingredient-inactive td:first-child .font-medium { text-decoration: line-through; }
</style>

<script>
(function() {
    var eyeOnSvg = '<svg class="h-5 w-5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var eyeOffSvg = '<svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    var slug = <?= json_encode($slug) ?>;

    document.querySelectorAll('.btn-toggle-ingredient').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var self = this;
            fetch('/admin/' + encodeURIComponent(slug) + '/ingredients/' + id + '/toggle', { method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'} })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) return;
                    self.dataset.active = data.active;
                    self.title = data.active ? 'Ocultar' : 'Ativar';
                    self.innerHTML = data.active ? eyeOnSvg : eyeOffSvg;
                    var row = self.closest('tr');
                    if (row) {
                        row.classList.toggle('ingredient-inactive', !data.active);
                    }
                });
        });
    });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
