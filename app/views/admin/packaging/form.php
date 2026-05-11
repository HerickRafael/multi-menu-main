<?php
/**
 * Formulário de Insumo/Embalagem
 * Estilo consistente com Analytics
 */

$title = ($isEdit ? 'Editar' : 'Novo') . ' Insumo - ' . ($company['name'] ?? '');
ob_start();

$units = [
    'un' => 'Unidade (un)',
    'cx' => 'Caixa (cx)',
    'pct' => 'Pacote (pct)',
    'rolo' => 'Rolo',
    'kg' => 'Quilograma (kg)',
    'g' => 'Grama (g)',
    'l' => 'Litro (l)',
    'ml' => 'Mililitro (ml)',
    'm' => 'Metro (m)',
    'cm' => 'Centímetro (cm)',
];

// Configuração do header padronizado
$pageTitle = $isEdit ? 'Editar Insumo' : 'Novo Insumo';
$pageDescription = $isEdit ? 'Altere os dados do insumo' : 'Cadastre uma nova embalagem ou insumo';
$pageIcon = $isEdit 
    ? '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    : '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Insumos', 'url' => base_url('admin/' . $activeSlug . '/packaging')],
    ['label' => $isEdit ? 'Editar' : 'Novo']
];
$actions = [];
?>

<div class="mx-auto max-w-2xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- FORMULÁRIO -->
<form action="<?= e(base_url('admin/' . $activeSlug . '/packaging/store')) ?>" method="POST" class="space-y-6">
  <?php if ($isEdit): ?>
  <input type="hidden" name="id" value="<?= e($supply['id']) ?>">
  <?php endif; ?>
  
  <!-- Card Principal -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
      </svg>
      <h2 class="text-lg font-semibold text-slate-900">Informações do Insumo</h2>
    </div>
    
    <div class="space-y-4">
      <!-- Nome -->
      <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nome *</label>
        <input type="text" id="name" name="name" required
               value="<?= e($supply['name'] ?? '') ?>"
               placeholder="Ex: Caixa para Hambúrguer"
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
      </div>
      
      <!-- Descrição -->
      <div>
        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
        <textarea id="description" name="description" rows="2"
                  placeholder="Descrição opcional do insumo..."
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent"><?= e($supply['description'] ?? '') ?></textarea>
      </div>
      
      <!-- Unidade e Custo -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="unit" class="block text-sm font-medium text-slate-700 mb-1">Unidade de Medida</label>
          <select id="unit" name="unit"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            <?php foreach ($units as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= ($supply['unit'] ?? 'un') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="cost_per_unit" class="block text-sm font-medium text-slate-700 mb-1">Custo por Unidade *</label>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">R$</span>
            <input type="number" id="cost_per_unit" name="cost_per_unit" required step="0.01" min="0"
                   value="<?= e(number_format((float)($supply['cost_per_unit'] ?? 0), 2, '.', '')) ?>"
                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 pl-10 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
          </div>
        </div>
      </div>
      
      <!-- Fornecedor -->
      <div>
        <label for="supplier" class="block text-sm font-medium text-slate-700 mb-1">Fornecedor</label>
        <input type="text" id="supplier" name="supplier"
               value="<?= e($supply['supplier'] ?? '') ?>"
               placeholder="Nome do fornecedor (opcional)"
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
      </div>
    </div>
  </div>
  
  <!-- Card Estoque -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <h2 class="text-lg font-semibold text-slate-900">Controle de Estoque</h2>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label for="stock_quantity" class="block text-sm font-medium text-slate-700 mb-1">Quantidade em Estoque</label>
        <input type="number" id="stock_quantity" name="stock_quantity" step="0.01" min="0"
               value="<?= e($supply['stock_quantity'] ?? '0') ?>"
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
      </div>
      <div>
        <label for="min_stock_alert" class="block text-sm font-medium text-slate-700 mb-1">Alerta de Estoque Mínimo</label>
        <input type="number" id="min_stock_alert" name="min_stock_alert" step="0.01" min="0"
               value="<?= e($supply['min_stock_alert'] ?? '0') ?>"
               placeholder="0 = sem alerta"
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
        <p class="text-xs text-slate-500 mt-1">Você será alertado quando o estoque atingir este valor</p>
      </div>
    </div>
  </div>
  
  <!-- Status -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="checkbox" name="active" value="1" 
             <?= ($supply['active'] ?? 1) ? 'checked' : '' ?>
             class="h-5 w-5 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
      <div>
        <span class="font-medium text-slate-900">Insumo Ativo</span>
        <p class="text-sm text-slate-500">Insumos inativos não aparecem na seleção de embalagens dos produtos</p>
      </div>
    </label>
  </div>
  
  <!-- Ações -->
  <div class="flex justify-end gap-3">
    <a href="<?= e(base_url('admin/' . $activeSlug . '/packaging')) ?>" 
       class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition font-medium">
      Cancelar
    </a>
    <button type="submit" 
            class="px-5 py-2.5 rounded-xl admin-gradient-bg text-white font-medium hover:opacity-95 transition inline-flex items-center gap-2">
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
      </svg>
      <?= $isEdit ? 'Salvar Alterações' : 'Criar Insumo' ?>
    </button>
  </div>
</form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
