<?php
/**
 * View: Admin - Lista de Clientes
 */

$slug = $company['slug'] ?? '';
$activeSlug = $slug;

// Configuração do header padronizado
$pageTitle = 'Clientes';
$pageDescription = 'Gerencie os clientes cadastrados';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Clientes']
];
$actions = [
    [
        'label' => 'Novo Cliente',
        'url' => base_url("admin/{$slug}/customers/create"),
        'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'primary' => true
    ]
];

ob_start();
?>

<div class="mx-auto max-w-6xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- Mensagens de sucesso/erro -->
<?php if (!empty($success)): ?>
<div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 flex items-center gap-2">
    <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <?= e($success) ?>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 flex items-center gap-2">
    <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="12" cy="12" r="10"/>
        <path d="M15 9l-6 6m0-6l6 6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <?= e($error) ?>
</div>
<?php endif; ?>

<!-- Estatísticas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-blue-100 p-2">
                <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900"><?= number_format((int)($stats['total_customers'] ?? 0), 0, ',', '.') ?></div>
                <div class="text-sm text-slate-500">Total de Clientes</div>
            </div>
        </div>
    </div>
    
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-green-100 p-2">
                <svg class="h-5 w-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <path d="M20 8v6m3-3h-6"/>
                </svg>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900"><?= number_format((int)($stats['new_customers_30d'] ?? 0), 0, ',', '.') ?></div>
                <div class="text-sm text-slate-500">Novos (30 dias)</div>
            </div>
        </div>
    </div>
    
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-purple-100 p-2">
                <svg class="h-5 w-5 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900"><?= number_format((int)($stats['active_7d'] ?? 0), 0, ',', '.') ?></div>
                <div class="text-sm text-slate-500">Ativos (7 dias)</div>
            </div>
        </div>
    </div>
</div>

<!-- Busca -->
<form method="get" class="mb-6">
    <div class="flex gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" 
                   name="q" 
                   value="<?= e($search) ?>" 
                   placeholder="Buscar por nome ou WhatsApp..."
                   class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200">
        </div>
        <button type="submit" class="rounded-xl bg-slate-100 px-5 py-2.5 text-slate-700 font-medium hover:bg-slate-200 transition">
            Buscar
        </button>
        <?php if ($search): ?>
        <a href="?" class="rounded-xl border border-slate-300 px-4 py-2.5 text-slate-600 hover:bg-slate-50 transition flex items-center gap-1">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Limpar
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- Info de resultados -->
<div class="mb-4 flex flex-wrap items-center justify-between gap-4 text-sm text-slate-500">
    <div>
        <?php if ($search): ?>
            Mostrando <?= count($customers) ?> de <?= $totalItems ?> resultado(s) para "<?= e($search) ?>"
        <?php else: ?>
            Mostrando <?= count($customers) ?> de <?= $totalItems ?> cliente(s)
        <?php endif; ?>
    </div>
    <div class="flex items-center gap-2">
        <span>Itens por página:</span>
        <select onchange="updatePerPage(this.value)" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-sm">
            <?php 
            $currentPerPage = (int)($_GET['per_page'] ?? 10);
            foreach ([10, 25, 50, 100] as $opt): ?>
                <option value="<?= $opt ?>" <?= $currentPerPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
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

<!-- Tabela de Clientes -->
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 font-medium">Cliente</th>
                    <th class="px-4 py-3 font-medium">WhatsApp</th>
                    <th class="px-4 py-3 font-medium text-center">Pedidos</th>
                    <th class="px-4 py-3 font-medium text-right">Total Gasto</th>
                    <th class="px-4 py-3 font-medium">Cadastro</th>
                    <th class="px-4 py-3 font-medium text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <p class="text-slate-500">Nenhum cliente encontrado</p>
                            <?php if ($search): ?>
                            <a href="?" class="text-indigo-600 hover:text-indigo-700 text-sm">Limpar busca</a>
                            <?php else: ?>
                            <a href="<?= e(base_url("admin/{$slug}/customers/create")) ?>" class="text-indigo-600 hover:text-indigo-700 text-sm">Cadastrar primeiro cliente</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full admin-gradient-bg flex items-center justify-center text-white font-medium text-sm">
                                <?= strtoupper(mb_substr($c['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-medium text-slate-900"><?= e($c['name']) ?></div>
                                <?php if (!empty($c['last_order_at'])): ?>
                                <div class="text-xs text-slate-500">Último pedido: <?= date('d/m/Y', strtotime($c['last_order_at'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <a href="https://wa.me/<?= e($c['whatsapp_e164']) ?>" 
                           target="_blank" 
                           class="inline-flex items-center gap-1.5 text-green-600 hover:text-green-700 transition">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <?= e(format_phone_br($c['whatsapp'])) ?>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center min-w-[2rem] rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                            <?= (int)$c['total_orders'] ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="font-medium text-slate-900">R$ <?= number_format((float)$c['total_spent'], 2, ',', '.') ?></span>
                    </td>
                    <td class="px-4 py-3 text-slate-500">
                        <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="<?= e(base_url("admin/{$slug}/customers/{$c['id']}/edit")) ?>" 
                               class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" 
                               title="Editar">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            <form method="post" 
                                  action="<?= e(base_url("admin/{$slug}/customers/{$c['id']}/delete")) ?>" 
                                  onsubmit="return confirm('Tem certeza que deseja remover este cliente?');"
                                  class="inline">
                                <button type="submit" 
                                        class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600 transition" 
                                        title="Excluir">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <path d="M10 11v6m4-6v6"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginação -->
<?php if ($totalPages > 1): ?>
<nav class="mt-6 flex items-center justify-center gap-1">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>" 
       class="rounded-lg px-3 py-2 text-sm bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>
    <?php endif; ?>
    
    <?php 
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
    
    if ($startPage > 1): ?>
    <a href="?page=1<?= $search ? '&q=' . urlencode($search) : '' ?>" 
       class="rounded-lg px-3 py-2 text-sm bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">1</a>
    <?php if ($startPage > 2): ?>
    <span class="px-2 text-slate-400">...</span>
    <?php endif; ?>
    <?php endif; ?>
    
    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
    <a href="?page=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>" 
       class="rounded-lg px-3 py-2 text-sm <?= $i === $page ? 'admin-gradient-bg text-white font-medium' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?> transition">
        <?= $i ?>
    </a>
    <?php endfor; ?>
    
    <?php if ($endPage < $totalPages): ?>
    <?php if ($endPage < $totalPages - 1): ?>
    <span class="px-2 text-slate-400">...</span>
    <?php endif; ?>
    <a href="?page=<?= $totalPages ?><?= $search ? '&q=' . urlencode($search) : '' ?>" 
       class="rounded-lg px-3 py-2 text-sm bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition"><?= $totalPages ?></a>
    <?php endif; ?>
    
    <?php if ($page < $totalPages): ?>
    <a href="?page=<?= $page + 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>" 
       class="rounded-lg px-3 py-2 text-sm bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>
    <?php endif; ?>
</nav>
<?php endif; ?>

</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
