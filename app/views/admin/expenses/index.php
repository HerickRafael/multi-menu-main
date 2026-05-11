<?php
/**
 * Lista de Despesas
 * Estilo consistente com Analytics
 */

$title = 'Despesas - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage)) {
    $perPage = 10;
}

$allExpenses = $expenses ?? [];
$totalItems = count($allExpenses);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;
$paginatedExpenses = array_slice($allExpenses, $offset, $perPage);

ob_start();
?>

<style>
.comparison-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
}
.comparison-badge.positive { color: #10B981; }
.comparison-badge.negative { color: #EF4444; }
</style>

<div class="mx-auto max-w-7xl p-4">

<?php
// Configuração do header padronizado
$pageTitle = 'Despesas';
$pageDescription = htmlspecialchars($monthLabel);
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Despesas']
];
$actions = [
    ['label' => 'Categorias', 'url' => base_url('admin/' . $activeSlug . '/expenses/categories'), 'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
    ['label' => 'Nova', 'url' => base_url('admin/' . $activeSlug . '/expenses/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];
$extraHeaderContent = '
<select id="monthSelector" 
        onchange="window.location.href=\'' . e(base_url('admin/' . $activeSlug . '/expenses')) . '?month=\' + this.value"
        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm focus:ring-2 focus:ring-purple-500">
    ' . implode('', array_map(function($m, $label) use ($month) {
        return '<option value="' . $m . '" ' . ($m === $month ? 'selected' : '') . '>' . $label . '</option>';
    }, array_keys($availableMonths), $availableMonths)) . '
</select>';
include __DIR__ . '/../components/page-header.php';
?>

<!-- MENSAGENS -->
<?php if ($success): ?>
<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <span class="text-emerald-700">
        <?php
        echo match($success) {
            'created' => 'Despesa cadastrada com sucesso!',
            'updated' => 'Despesa atualizada com sucesso!',
            'deleted' => 'Despesa excluída com sucesso!',
            default => 'Operação realizada com sucesso!'
        };
        ?>
    </span>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <span class="text-red-700">
        <?php
        echo match($error) {
            'notfound' => 'Despesa não encontrada.',
            'delete' => 'Erro ao excluir despesa.',
            default => 'Ocorreu um erro.'
        };
        ?>
    </span>
</div>
<?php endif; ?>

<!-- MÉTRICAS PRINCIPAIS -->
<div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
  
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
    <div class="mb-3 flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="text-sm font-medium text-slate-600">Total do mês</span>
    </div>
    <span class="text-3xl font-bold text-slate-900">R$ <?= number_format($summary['total'] ?? 0, 2, ',', '.') ?></span>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
    <div class="mb-3 flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="text-sm font-medium text-slate-600">Despesas fixas</span>
    </div>
    <span class="text-3xl font-bold text-slate-900">R$ <?= number_format($summary['fixed'] ?? 0, 2, ',', '.') ?></span>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
    <div class="mb-3 flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="text-sm font-medium text-slate-600">Despesas variáveis</span>
    </div>
    <span class="text-3xl font-bold text-slate-900">R$ <?= number_format($summary['variable'] ?? 0, 2, ',', '.') ?></span>
  </div>

</div>

<!-- TABELA DE DESPESAS -->
<div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-200 p-4">
    <h3 class="text-lg font-semibold text-slate-900">Lista de Despesas</h3>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left font-medium text-slate-600">Descrição</th>
          <th class="px-4 py-3 text-left font-medium text-slate-600">Categoria</th>
          <th class="px-4 py-3 text-center font-medium text-slate-600">Tipo</th>
          <th class="px-4 py-3 text-right font-medium text-slate-600">Valor</th>
          <th class="px-4 py-3 text-center font-medium text-slate-600">Pagamento</th>
          <th class="px-4 py-3 text-center font-medium text-slate-600">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($allExpenses)): ?>
          <tr>
            <td colspan="6" class="px-4 py-12 text-center">
              <div class="flex flex-col items-center">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400 mb-3">
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <p class="text-slate-500 mb-2">Nenhuma despesa cadastrada para este mês.</p>
                <a href="<?= base_url('admin/' . $activeSlug . '/expenses/create') ?>" class="text-purple-600 hover:text-purple-700 font-medium">Cadastrar primeira despesa →</a>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($paginatedExpenses as $expense): ?>
            <tr class="hover:bg-slate-50 transition">
              <td class="px-4 py-3">
                <p class="font-medium text-slate-800"><?= htmlspecialchars($expense['description']) ?></p>
                <?php if ($expense['notes']): ?>
                  <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($expense['notes']) ?></p>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($expense['category_name'] ?? 'Sem categoria') ?></td>
              <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-medium <?= ($expense['category_type'] ?? 'fixed') === 'fixed' ? 'bg-orange-50 text-orange-700 ring-1 ring-orange-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' ?>">
                  <?= ($expense['category_type'] ?? 'fixed') === 'fixed' ? 'Fixa' : 'Variável' ?>
                </span>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-red-600">R$ <?= number_format($expense['amount'], 2, ',', '.') ?></td>
              <td class="px-4 py-3 text-center">
                <?php if ($expense['payment_date']): ?>
                  <span class="inline-flex items-center gap-1 text-emerald-600 text-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?= date('d/m/Y', strtotime($expense['payment_date'])) ?>
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 text-slate-400 text-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Pendente
                  </span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-center">
                <div class="flex items-center justify-center gap-1">
                  <a href="<?= base_url('admin/' . $activeSlug . '/expenses/' . $expense['id'] . '/edit') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-purple-600 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </a>
                  <button onclick="confirmDelete(<?= $expense['id'] ?>)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
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
      $baseUrl = base_url('admin/' . $activeSlug . '/expenses');
      $queryParams = ['per_page' => $perPage];
      if (!empty($month)) $queryParams['month'] = $month;
      
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

<?php if (!empty($allExpenses)): ?>
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
  <div class="mb-4 flex items-center gap-2">
    <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <h3 class="text-lg font-semibold text-slate-900">Por Categoria</h3>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
    <?php
    $byCategory = [];
    foreach ($allExpenses as $exp) {
        $cat = $exp['category_name'] ?? 'Sem categoria';
        if (!isset($byCategory[$cat])) $byCategory[$cat] = 0;
        $byCategory[$cat] += (float)$exp['amount'];
    }
    arsort($byCategory);
    foreach ($byCategory as $catName => $catTotal): ?>
      <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
        <span class="font-medium text-slate-700"><?= htmlspecialchars($catName) ?></span>
        <span class="font-semibold text-red-600">R$ <?= number_format($catTotal, 2, ',', '.') ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

</div>

<script>
function confirmDelete(id) {
    if (confirm('Tem certeza que deseja excluir esta despesa?')) {
        window.location.href = '<?= base_url('admin/' . $activeSlug . '/expenses/') ?>' + id + '/delete?month=<?= $month ?>';
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
