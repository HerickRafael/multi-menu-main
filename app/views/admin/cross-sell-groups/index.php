<?php
/**
 * View: Gerenciar Grupos de Cross-Sell Otimizados
 * 1 Categoria Disparadora → Múltiplas Categorias Recomendadas
 */

$title = 'Cross-Sell - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage)) {
    $perPage = 10;
}

$allGroups = $groups ?? [];
$totalItems = count($allGroups);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;
$paginatedGroups = array_slice($allGroups, $offset, $perPage);

ob_start();

// Configuração do header padronizado
$pageTitle = 'Cross-Sell';
$pageDescription = 'Gerencie grupos de venda cruzada';
$pageIcon = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg>';
$breadcrumbs = [
    ['label' => 'Produtos', 'url' => base_url('admin/' . $slug . '/products')],
    ['label' => 'Cross-Sell']
];
$actions = [
    ['label' => 'Dashboard', 'url' => base_url('admin/' . $slug . '/dashboard'), 'icon' => '<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/></svg>'],
    ['label' => 'Nova', 'onclick' => 'openAddModal()', 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];
?>

<div class="mx-auto max-w-6xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<?php if (isset($_GET['success'])): ?>
  <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="font-medium"><?= e($_GET['success']) ?></span>
    </div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="font-medium"><?= e($_GET['error']) ?></span>
    </div>
  </div>
<?php endif; ?>

<?php if (empty($allGroups)): ?>
  <!-- EMPTY STATE -->
  <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
    <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
        <path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <h2 class="text-lg font-medium text-slate-800">Nenhuma regra configurada</h2>
    <p class="mt-1 text-sm text-slate-500">Crie regras de cross-sell para aumentar suas vendas.</p>
    <div class="mt-4">
      <button onclick="openAddModal()"
              class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
          <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        Criar regra
      </button>
    </div>
  </div>
<?php else: ?>

  <!-- TABELA -->
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-[800px] w-full">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
          <tr>
            <th class="p-3">Categoria Disparadora</th>
            <th class="p-3">Recomendações</th>
            <th class="p-3">Status</th>
            <th class="p-3 text-right">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach ($paginatedGroups as $group): ?>
            <tr class="hover:bg-slate-50/60">
              
              <!-- Categoria Disparadora -->
              <td class="p-3 align-middle">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                      <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </span>
                  <span class="font-medium text-slate-800"><?= e($group['trigger_category_name']) ?></span>
                </div>
              </td>

              <!-- Recomendações -->
              <td class="p-3 align-middle">
                <div class="flex flex-wrap gap-1.5">
                  <?php foreach ($group['recommendations'] as $rec): ?>
                    <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                      <?= e($rec['category_name']) ?>
                      <span class="text-emerald-400">·</span>
                      <span class="italic"><?= e($rec['section_title']) ?></span>
                    </span>
                  <?php endforeach; ?>
                </div>
              </td>

              <!-- Status -->
              <td class="p-3 align-middle">
                <?php if ($group['active']): ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ativa
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[12px] font-medium text-slate-600 ring-1 ring-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inativa
                  </span>
                <?php endif; ?>
              </td>

              <!-- Ações -->
              <td class="p-3 align-middle">
                <div class="flex justify-end gap-2">
                  <button onclick='openEditModal(<?= json_encode($group) ?>)'
                          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                          title="Editar">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                      <path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                    Editar
                  </button>

                  <button onclick="toggleGroup(<?= $group['id'] ?>)"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                          title="<?= $group['active'] ? 'Desativar' : 'Ativar' ?>">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                      <?php if ($group['active']): ?>
                        <path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                      <?php else: ?>
                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                      <?php endif; ?>
                    </svg>
                    <?= $group['active'] ? 'Desativar' : 'Ativar' ?>
                  </button>

                  <button onclick="deleteGroup(<?= $group['id'] ?>)"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-600 shadow-sm hover:bg-red-50"
                          title="Excluir">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                      <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Excluir
                  </button>
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
        $baseUrl = base_url('admin/' . $slug . '/cross-sell-groups');
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

</div><!-- Fecha mx-auto -->

<!-- Modal Adicionar/Editar -->
<div id="modal" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
  <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl">
    
    <div class="sticky top-0 z-10 border-b border-slate-200 bg-white px-6 py-4">
      <button onclick="closeModal()" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
          <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </button>
      
      <h2 class="admin-gradient-text bg-clip-text text-xl font-semibold text-transparent" id="modal-title">Nova Regra</h2>
      <p class="mt-1 text-sm text-slate-500">Configure múltiplas recomendações para uma categoria</p>
    </div>

    <form method="POST" action="<?= e(base_url('admin/' . $slug . '/cross-sell-groups/save')) ?>" class="p-6 space-y-4" id="mainForm">
      
      <!-- Categoria Disparadora -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Categoria Disparadora <a href="<?= e(base_url('admin/' . $slug . '/guide/cross-sell')) ?>#form" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></label>
        <select name="trigger_category_id" id="trigger_category" required
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-700 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-200">
          <option value="">Selecione a categoria</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>">
              <?= e($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="mt-1 text-xs text-slate-500">Quando o cliente visualiza esta categoria</p>
      </div>
      
      <!-- Categorias Recomendadas -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Categorias Recomendadas <a href="<?= e(base_url('admin/' . $slug . '/guide/cross-sell')) ?>#form" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></label>
        <div class="space-y-2 max-h-96 overflow-y-auto rounded-xl border border-slate-300 bg-slate-50 p-3" id="recommendations-container">
          <?php foreach ($categories as $cat): ?>
            <div class="rounded-lg border border-slate-200 bg-white p-3">
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="recommended_categories[<?= (int)$cat['id'] ?>][selected]" value="1"
                       class="h-4 w-4 mt-0.5 rounded border-slate-300 text-purple-600 focus:ring-2 focus:ring-purple-200 category-checkbox"
                       data-category-id="<?= (int)$cat['id'] ?>"
                       onchange="toggleTitleField(<?= (int)$cat['id'] ?>)">
                <div class="flex-1">
                  <span class="font-medium text-slate-800"><?= e($cat['name']) ?></span>
                  
                  <!-- Campo de Título -->
                  <div id="title-field-<?= (int)$cat['id'] ?>" style="display:none" class="mt-2">
                    <input type="text" 
                           name="recommended_categories[<?= (int)$cat['id'] ?>][title]" 
                           placeholder="Ex: Que tal uma <?= strtolower(e($cat['name'])) ?>?" 
                           maxlength="100"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-200"/>
                    <p class="mt-1 text-xs text-slate-500">Título que aparecerá no site</p>
                  </div>
                </div>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="mt-1.5 text-xs text-slate-500">Marque as categorias e defina um título para cada</p>
      </div>

      <!-- Botões -->
      <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
        <button type="button" onclick="closeModal()"
                class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
          Cancelar
        </button>
        <button type="submit" 
                class="flex-1 rounded-xl admin-gradient-bg px-4 py-2.5 text-sm font-medium text-white shadow hover:opacity-95">
          Salvar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const categories = <?= json_encode($categories) ?>;
const categoriesMap = <?= json_encode(array_column($categories, 'name', 'id')) ?>;

function openAddModal() {
    document.getElementById('modal-title').textContent = 'Nova Regra de Cross-Sell';
    document.getElementById('trigger_category').value = '';
    document.querySelectorAll('.category-checkbox').forEach(cb => {
        cb.checked = false;
        toggleTitleField(cb.dataset.categoryId);
    });
    document.getElementById('modal').style.display = 'flex';
}

function openEditModal(group) {
    document.getElementById('modal-title').textContent = 'Editar Regra de Cross-Sell';
    document.getElementById('trigger_category').value = group.trigger_category_id;
    
    // Desmarcar tudo primeiro
    document.querySelectorAll('.category-checkbox').forEach(cb => {
        cb.checked = false;
        toggleTitleField(cb.dataset.categoryId);
    });
    
    // Marcar as recomendações existentes
    group.recommendations.forEach(rec => {
        const checkbox = document.querySelector(`input[name="recommended_categories[${rec.category_id}][selected]"]`);
        if (checkbox) {
            checkbox.checked = true;
            toggleTitleField(rec.category_id);
            const titleInput = document.querySelector(`input[name="recommended_categories[${rec.category_id}][title]"]`);
            if (titleInput) {
                titleInput.value = rec.section_title;
            }
        }
    });
    
    document.getElementById('modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

function toggleTitleField(categoryId) {
    const checkbox = document.querySelector(`input[name="recommended_categories[${categoryId}][selected]"]`);
    const titleField = document.getElementById('title-field-' + categoryId);
    
    if (checkbox.checked) {
        titleField.style.display = 'block';
        titleField.querySelector('input').focus();
    } else {
        titleField.style.display = 'none';
        titleField.querySelector('input').value = '';
    }
}

async function toggleGroup(groupId) {
    if (!confirm('Deseja alterar o status deste grupo?')) return;
    
    try {
        const response = await fetch('<?= e(base_url("admin/{$slug}/cross-sell-groups/toggle/")) ?>' + groupId, {
            method: 'POST'
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Erro ao atualizar status');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao atualizar status');
    }
}

async function deleteGroup(groupId) {
    if (!confirm('Tem certeza que deseja excluir este grupo? Todas as recomendações serão removidas. Esta ação não pode ser desfeita.')) return;
    
    try {
        const response = await fetch('<?= e(base_url("admin/{$slug}/cross-sell-groups/delete/")) ?>' + groupId, {
            method: 'POST'
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Erro ao excluir grupo');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao excluir grupo');
    }
}

// Fechar modal ao clicar fora
document.getElementById('modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Validação do formulário
document.getElementById('mainForm').addEventListener('submit', function(e) {
    const triggerCategory = document.getElementById('trigger_category').value;
    const checkedBoxes = document.querySelectorAll('.category-checkbox:checked');
    
    if (!triggerCategory) {
        e.preventDefault();
        alert('Selecione a categoria disparadora');
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Selecione pelo menos uma categoria para recomendar');
        return false;
    }
    
    // Validar títulos
    let missingTitle = false;
    checkedBoxes.forEach(cb => {
        const catId = cb.dataset.categoryId;
        const titleInput = document.querySelector(`input[name="recommended_categories[${catId}][title]"]`);
        if (!titleInput || !titleInput.value.trim()) {
            missingTitle = true;
        }
    });
    
    if (missingTitle) {
        e.preventDefault();
        alert('Preencha o título para todas as categorias selecionadas');
        return false;
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
