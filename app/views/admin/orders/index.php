<?php
// admin/orders/index.php — Pedidos (versão moderna)

$title = 'Pedidos - ' . ($company['name'] ?? 'Empresa');
$slug  = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));
$backUrl = $slug ? base_url('admin/' . $slug . '/dashboard') : base_url('admin');

// filtros (status, origem e busca por cliente)
$status = (string)($_GET['status'] ?? '');
$source = (string)($_GET['source'] ?? '');
$q      = trim((string)($_GET['q'] ?? ''));

// mapeamento de status -> label
$statusLabels = [
  'pending'   => 'Pendente',
  'completed' => 'Concluído',
  'canceled'  => 'Cancelado',
];

// Paginação
$pagination = $pagination ?? ['page' => 1, 'perPage' => 10, 'total' => 0, 'totalPages' => 1];
$page = $pagination['page'];
$perPage = $pagination['perPage'];
$totalOrders = $pagination['total'];
$totalPages = $pagination['totalPages'];

// Usa os pedidos já filtrados pelo controller
$filtered = $orders ?? [];

// Busca já é feita no controller/model, não precisa filtrar novamente aqui

ob_start(); ?>
<div class="mx-auto max-w-6xl p-4">
  <?php
  // Configuração do Header Padrão
  $pageTitle = 'Pedidos';
  $pageDescription = '';
  $pageIcon = '<svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5M3.14 5l.5 2H5V5zM6 5v2h2V5zm3 0v2h2V5zm3 0v2h1.36l.5-2zm1.11 3H12v2h.61zM11 8H9v2h2zM8 8H6v2h2zM5 8H3.89l.5 2H5zm0 5a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0m9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0"/></svg>';
  $breadcrumbs = [
      ['label' => 'Pedidos']
  ];
  $actions = [
      ['label' => 'Novo pedido', 'url' => base_url('admin/' . $slug . '/orders/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>', 'primary' => true]
  ];
  include __DIR__ . '/../components/page-header.php';
  ?>

  <!-- FILTROS -->
  <form class="mb-4 grid gap-2 sm:grid-cols-[180px_140px_minmax(0,1fr)_auto] sm:items-center"
        method="get" action="<?= e(base_url('admin/' . $slug . '/orders')) ?>">
    <label class="grid gap-1">
      <span class="sr-only">Status</span>
      <select name="status"
              class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
        <option value="">Todos status</option>
        <?php foreach ($statusLabels as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="grid gap-1">
      <span class="sr-only">Origem</span>
      <select name="source"
              class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
        <option value="">Todas origens</option>
        <option value="manual" <?= ($source ?? '') === 'manual' ? 'selected' : '' ?>>Manual</option>
        <option value="website" <?= ($source ?? '') === 'website' ? 'selected' : '' ?>>Site</option>
        <option value="ifood" <?= ($source ?? '') === 'ifood' ? 'selected' : '' ?>>iFood</option>
      </select>
    </label>

    <label class="relative">
      <span class="sr-only">Buscar</span>
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por #, cliente ou telefone"
             class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pl-9 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
      <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none">
        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </label>

    <div class="flex gap-2">
      <button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
        Filtrar
      </button>
      <?php if ($status !== '' || $q !== '' || ($source ?? '') !== ''): ?>
        <a href="<?= e(base_url('admin/' . $slug . '/orders')) ?>"
           class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
          Limpar
        </a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (empty($filtered)): ?>
    <!-- EMPTY STATE -->
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
      <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M7 12h10M9 17h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      </div>
      <h2 class="text-lg font-medium text-slate-800">Nenhum pedido encontrado</h2>
      <p class="mt-1 text-sm text-slate-500">Ajuste os filtros ou crie um novo pedido agora mesmo.</p>
      <div class="mt-4">
        <a href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>"
          class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          Novo pedido
        </a>
      </div>
    </div>
  <?php else: ?>

    <!-- TABELA -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="max-w-full overflow-x-auto">
        <table class="min-w-[760px] w-full">
          <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
            <tr>
              <th class="p-3">#</th>
              <th class="p-3">Origem</th>
              <th class="p-3">Cliente</th>
              <th class="p-3">Itens</th>
              <th class="p-3">Status</th>
              <th class="p-3">Total</th>
              <th class="p-3">Criado</th>
              <th class="p-3 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm">
            <?php foreach ($filtered as $o): ?>
              <?php
                $st = (string)($o['status'] ?? 'pending');
                $label = $statusLabels[$st] ?? ucfirst($st);
                $orderNum = (int)($o['order_number'] ?? $o['id'] ?? 0);
                $source = $o['source'] ?? 'manual';
                ?>
              <tr class="hover:bg-slate-50/60">
                <td class="p-3 align-middle font-medium text-slate-800">#<?= $orderNum ?></td>

                <td class="p-3 align-middle">
                  <?php if ($source === 'ifood'): ?>
                    <span class="inline-flex items-center gap-1 rounded-lg bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                      </svg>
                      iFood
                    </span>
                  <?php elseif ($source === 'website'): ?>
                    <span class="inline-flex items-center gap-1 rounded-lg bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                      </svg>
                      Site
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <path d="M20 8v6M23 11h-6"/>
                      </svg>
                      Manual
                    </span>
                  <?php endif; ?>
                </td>

                <td class="p-3 align-middle">
                  <div class="text-slate-800"><?= e($o['customer_name'] ?? '-') ?></div>
                  <?php if (!empty($o['customer_phone'])): ?>
                    <div class="text-xs text-slate-500"><?= e(format_phone_br($o['customer_phone'])) ?></div>
                  <?php endif; ?>
                </td>

                <td class="p-3 align-middle">
                  <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                      <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?= (int)($o['items_qty'] ?? $o['items_count'] ?? 0) ?>
                  </span>
                </td>

                <td class="p-3 align-middle">
                  <?= status_pill($st, $label) ?>
                </td>

                <td class="p-3 align-middle whitespace-nowrap font-medium text-slate-800">
                  R$ <?= number_format((float)($o['total'] ?? 0), 2, ',', '.') ?>
                </td>

                <td class="p-3 align-middle text-slate-700 whitespace-nowrap">
                  <?= e($o['created_at'] ?? '') ?>
                </td>

                <td class="p-3 align-middle">
                  <div class="flex justify-end gap-2">
                    <a class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                       href="<?= e(base_url('admin/' . $slug . '/orders/show?id=' . (int)($o['id'] ?? 0))) ?>">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Zm9.5-3a3 3 0 1 1-3 3 3 3 0 0 1 3-3Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Ver
                    </a>
                    <form method="post"
                          action="<?= e(base_url('admin/' . $slug . '/orders/' . (int)($o['id'] ?? 0) . '/del')) ?>"
                          class="inline"
                          onsubmit="return confirm('Excluir pedido?');">
                      <?php if (function_exists('csrf_field')): ?>
                        <?= csrf_field() ?>
                      <?php elseif (function_exists('csrf_token')): ?>
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <?php endif; ?>
                      <button class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
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
      <?php if ($totalPages > 1 || $totalOrders > 10): ?>
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 bg-slate-50 px-4 py-3">
        <div class="flex items-center gap-3 text-sm text-slate-600">
          <span>Itens por página:</span>
          <select onchange="updatePerPage(this.value)" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-sm">
            <?php foreach ([10, 25, 50, 100] as $opt): ?>
              <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
          <span class="text-slate-500">
            Mostrando <?= min($totalOrders, ($page - 1) * $perPage + 1) ?>-<?= min($totalOrders, $page * $perPage) ?> de <?= $totalOrders ?>
          </span>
        </div>
        
        <div class="flex items-center gap-1">
          <?php
          $baseUrl = base_url('admin/' . $slug . '/orders');
          $queryParams = [];
          if ($status) $queryParams['status'] = $status;
          if ($q) $queryParams['q'] = $q;
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
