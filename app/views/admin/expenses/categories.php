<?php
/**
 * Categorias de Despesas
 * Estilo consistente com Analytics
 */

$title = 'Categorias de Despesas - ' . ($company['name'] ?? '');
ob_start();

// Configuração do header padronizado
$pageTitle = 'Categorias de Despesas';
$pageDescription = 'Organize suas despesas por categoria';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Despesas', 'url' => base_url('admin/' . $activeSlug . '/expenses')],
    ['label' => 'Categorias']
];
$actions = [
    ['label' => 'Nova', 'onclick' => 'openModal()', 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];
?>

<div class="mx-auto max-w-4xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<?php if (!empty($success)): ?>
<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="text-emerald-700"><?= e($success) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="text-red-700"><?= e($error) ?></span>
</div>
<?php endif; ?>

<!-- Lista de Categorias -->
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
  <?php if (empty($categories)): ?>
  <div class="p-12 text-center">
    <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-4">
      <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <h3 class="text-lg font-medium text-slate-900 mb-1">Nenhuma categoria cadastrada</h3>
    <p class="text-slate-500 mb-4">Crie categorias para organizar suas despesas</p>
    <button type="button" onclick="openModal()" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2.5 text-white shadow-sm hover:opacity-95 transition">
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Criar Primeira Categoria
    </button>
  </div>
  <?php else: ?>
  <div class="divide-y divide-slate-200">
    <?php foreach ($categories as $cat): ?>
    <div class="flex items-center justify-between p-4 hover:bg-slate-50 transition">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-sm" style="background-color: <?= e($cat['color'] ?? '#6b7280') ?>">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div>
          <p class="font-medium text-slate-900"><?= e($cat['name']) ?></p>
          <?php if (!empty($cat['description'])): ?>
          <p class="text-sm text-slate-500"><?= e($cat['description']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 transition">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <form action="<?= base_url('admin/' . $activeSlug . '/expenses/categories/delete/' . $cat['id']) ?>" method="POST" onsubmit="return confirm('Excluir esta categoria?')">
          <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-300 bg-white text-red-600 hover:bg-red-50 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 z-50 hidden">
  <div class="fixed inset-0 bg-black/50" onclick="closeModal()"></div>
  <div class="fixed inset-0 flex items-center justify-center p-4">
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
      <button type="button" onclick="closeModal()" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      
      <h3 id="modalTitle" class="text-lg font-semibold text-slate-900 mb-4">Nova Categoria</h3>
      
      <form id="categoryForm" method="POST" class="space-y-4">
        <input type="hidden" name="id" id="categoryId">
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nome *</label>
          <input type="text" name="name" id="categoryName" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500" placeholder="Ex: Aluguel, Energia, Insumos...">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
          <input type="text" name="description" id="categoryDescription" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500" placeholder="Descrição opcional">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Cor</label>
          <div class="flex items-center gap-2">
            <input type="color" name="color" id="categoryColor" value="#8b5cf6" class="h-10 w-16 rounded-lg border border-slate-300 cursor-pointer">
            <span class="text-sm text-slate-500">Cor para identificar a categoria</span>
          </div>
        </div>
        
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">Cancelar</button>
          <button type="submit" class="px-4 py-2.5 rounded-xl admin-gradient-bg text-white font-medium hover:opacity-95 transition">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const baseUrl = '<?= base_url('admin/' . $activeSlug) ?>';

function openModal() {
    document.getElementById('modalTitle').textContent = 'Nova Categoria';
    document.getElementById('categoryForm').action = baseUrl + '/expenses/categories/store';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryDescription').value = '';
    document.getElementById('categoryColor').value = '#8b5cf6';
    document.getElementById('modal').classList.remove('hidden');
}

function editCategory(cat) {
    document.getElementById('modalTitle').textContent = 'Editar Categoria';
    document.getElementById('categoryForm').action = baseUrl + '/expenses/categories/update/' + cat.id;
    document.getElementById('categoryId').value = cat.id;
    document.getElementById('categoryName').value = cat.name || '';
    document.getElementById('categoryDescription').value = cat.description || '';
    document.getElementById('categoryColor').value = cat.color || '#8b5cf6';
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
