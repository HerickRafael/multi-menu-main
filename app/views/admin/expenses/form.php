<?php
/**
 * Formulário de Despesa
 * Estilo consistente com Analytics
 */

$isEdit = isset($expense) && $expense;
$title = ($isEdit ? 'Editar' : 'Nova') . ' Despesa - ' . ($company['name'] ?? '');
ob_start();

// Configuração do header padronizado
$pageTitle = ($isEdit ? 'Editar' : 'Nova') . ' Despesa';
$pageDescription = 'Registre despesas e custos operacionais';
$pageIcon = $isEdit 
    ? '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    : '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Despesas', 'url' => base_url('admin/' . $activeSlug . '/expenses')],
    ['label' => $isEdit ? 'Editar' : 'Nova']
];
$actions = [];
?>

<div class="mx-auto max-w-2xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<?php if (!empty($error)): ?>
<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="text-red-700"><?= e($error) ?></span>
</div>
<?php endif; ?>

<form action="<?= base_url('admin/' . $activeSlug . '/expenses/' . ($isEdit ? 'update/' . $expense['id'] : 'store')) ?>" method="POST" class="space-y-6">
  
  <!-- Informações Básicas -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Informações Básicas</h2>
    </div>
    
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Descrição *</label>
        <input type="text" name="description" value="<?= e($expense['description'] ?? '') ?>" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Ex: Aluguel, Energia Elétrica, Fornecedor...">
      </div>
      
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Valor *</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">R$</span>
            <input type="number" name="amount" value="<?= e($expense['amount'] ?? '') ?>" step="0.01" min="0" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 pl-10 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="0,00">
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Data *</label>
          <input type="date" name="expense_date" value="<?= e($expense['expense_date'] ?? date('Y-m-d')) ?>" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
        </div>
      </div>
    </div>
  </div>

  <!-- Categoria e Classificação -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Categoria e Classificação</h2>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Categoria *</label>
        <select name="category_id" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
          <option value="">Selecione...</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (($expense['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1"><a href="<?= base_url('admin/' . $activeSlug . '/expenses/categories') ?>" class="text-purple-600 hover:underline">Gerenciar categorias</a></p>
      </div>
      
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
        <select name="type" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
          <option value="fixed" <?= (($expense['type'] ?? 'fixed') == 'fixed') ? 'selected' : '' ?>>Custo Fixo</option>
          <option value="variable" <?= (($expense['type'] ?? '') == 'variable') ? 'selected' : '' ?>>Custo Variável</option>
        </select>
        <p class="text-xs text-slate-500 mt-1">Fixo: Aluguel, Salários. Variável: Insumos, Comissões</p>
      </div>
    </div>
  </div>

  <!-- Recorrência -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Recorrência</h2>
    </div>
    
    <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50">
      <input type="checkbox" name="is_recurring" id="is_recurring" value="1" <?= !empty($expense['is_recurring']) ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
      <label for="is_recurring" class="text-slate-700">Esta é uma despesa recorrente (mensal)</label>
    </div>
    <p class="text-sm text-slate-500 mt-2">Marque para despesas que se repetem todo mês (aluguel, assinaturas, etc.)</p>
  </div>

  <!-- Observações -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Observações</h2>
    </div>
    
    <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Notas adicionais sobre esta despesa..."><?= e($expense['notes'] ?? '') ?></textarea>
  </div>

  <!-- Botões -->
  <div class="flex justify-end gap-3">
    <a href="<?= base_url('admin/' . $activeSlug . '/expenses') ?>" class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">Cancelar</a>
    <button type="submit" class="px-6 py-2.5 rounded-xl admin-gradient-bg text-white font-medium hover:opacity-95 transition inline-flex items-center gap-2">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <?= $isEdit ? 'Atualizar' : 'Salvar' ?> Despesa
    </button>
  </div>
</form>

<?php if ($isEdit): ?>
<!-- Delete -->
<div class="mt-6 p-6 rounded-2xl border border-red-200 bg-red-50">
  <div class="flex items-center justify-between">
    <div>
      <h3 class="font-medium text-red-800">Excluir Despesa</h3>
      <p class="text-sm text-red-600">Esta ação não pode ser desfeita</p>
    </div>
    <form action="<?= base_url('admin/' . $activeSlug . '/expenses/delete/' . $expense['id']) ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta despesa?')">
      <button type="submit" class="px-4 py-2 rounded-xl border border-red-300 bg-white text-red-700 hover:bg-red-100 transition inline-flex items-center gap-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Excluir
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
