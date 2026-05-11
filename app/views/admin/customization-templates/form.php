<?php
/**
 * Formulário de criação/edição de Template de Personalização
 * Usa a MESMA estrutura do bloco de personalização do produto
 */
ob_start();

$isEdit = !empty($template);
$templateName = $template['name'] ?? '';
$templateType = $template['type'] ?? 'extra';
$templateMode = in_array($templateType, ['single', 'addon', 'choice'], true) ? 'choice' : ($templateType === 'pool' ? 'pool' : 'extra');
$templateMinQty = $template['min_qty'] ?? 0;
$templateMaxQty = $template['max_qty'] ?? 1;
$templateActive = $template['active'] ?? 1;
$templateHideDuplicates = $template['hide_duplicates'] ?? 0;
$items = $template['items'] ?? [];

// Preparar dados de ingredientes para JS
$ingredientsJs = json_encode(array_map(function($ing) {
    return [
        'id' => (int)$ing['id'],
        'name' => $ing['name'],
        'internal_name' => $ing['internal_name'] ?? '',
        'delta' => (float)($ing['delta'] ?? 0),
        'min_qty' => (int)($ing['min_qty'] ?? 0),
        'max_qty' => (int)($ing['max_qty'] ?? 1)
    ];
}, $ingredients ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<!-- Breadcrumb -->
<nav class="mb-4 flex items-center gap-2 text-sm text-slate-500">
  <a href="<?= base_url('admin/' . rawurlencode($company['slug']) . '/customization-templates') ?>" class="hover:text-slate-700">
    Grupos de Personalização
  </a>
  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <span class="text-slate-700"><?= $isEdit ? 'Editar' : 'Novo' ?></span>
</nav>

<!-- Título -->
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900"><?= $isEdit ? 'Editar Grupo' : 'Novo Grupo de Personalização' ?></h1>
  <p class="text-sm text-slate-500 mt-1">Crie grupos reutilizáveis para copiar em vários produtos</p>
</div>

<!-- CSS para Typeahead de Ingredientes -->
<style>
  .ingredient-typeahead-wrapper { position: relative; }
  .ingredient-suggestions {
    position: absolute; top: 100%; left: 0; right: 0; z-index: 50;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 0.5rem;
    max-height: 200px; overflow-y: auto; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
  }
  .ingredient-suggestion {
    padding: 0.5rem 0.75rem; cursor: pointer; font-size: 0.875rem;
    display: flex; align-items: center; gap: 0.5rem;
  }
  .ingredient-suggestion:hover, .ingredient-suggestion.active { background: #f1f5f9; }
  .ingredient-suggestion .name { font-weight: 500; color: #1e293b; }
  .ingredient-suggestion .internal { color: #64748b; font-size: 0.75rem; }
  .ingredient-suggestion .delta { color: #10b981; font-size: 0.75rem; margin-left: auto; }
  .ingredient-clear-btn {
    position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%);
    background: #e2e8f0; border: none; border-radius: 50%; width: 1.25rem; height: 1.25rem;
    display: flex; align-items: center; justify-content: center; cursor: pointer;
    font-size: 0.75rem; color: #64748b;
  }
  .ingredient-clear-btn:hover { background: #cbd5e1; color: #334155; }
  .ingredient-typeahead-wrapper:has(.ingredient-clear-btn) .ingredient-search-icon { display: none; }
  
  /* Drag & Drop */
  .cust-group.dragging { opacity: 0.5; }
  .cust-item.dragging { opacity: 0.5; background: #f8fafc; }
</style>

<!-- JSON de Ingredientes para Typeahead -->
<script>
  window.INGREDIENTS_DATA = <?= $ingredientsJs ?>;
</script>

<form method="POST" action="<?= base_url('admin/' . rawurlencode($company['slug']) . '/customization-templates' . ($isEdit ? '/' . (int)$template['id'] : '')) ?>" class="space-y-6">
  
  <!-- Toggle Ativo -->
  <div class="flex items-center gap-3 mb-4">
    <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
      <input type="checkbox" name="active" value="1" <?= $templateActive ? 'checked' : '' ?> class="hidden" id="active-toggle">
      <span class="w-10 h-6 rounded-full relative transition-colors <?= $templateActive ? 'admin-primary-bg' : 'bg-slate-300' ?>" id="active-track">
        <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" id="active-thumb" style="transform: translateX(<?= $templateActive ? '16px' : '0px' ?>)"></span>
      </span>
      <span class="text-slate-700 font-medium">Grupo ativo</span>
    </label>
    <span class="text-xs text-slate-500">(Grupos inativos não aparecem para copiar)</span>
  </div>
  <script>
    document.getElementById('active-toggle')?.addEventListener('change', function() {
      const track = document.getElementById('active-track');
      const thumb = document.getElementById('active-thumb');
      if (this.checked) {
        track.classList.remove('bg-slate-300');
        track.classList.add('admin-primary-bg');
        thumb.style.transform = 'translateX(16px)';
      } else {
        track.classList.add('bg-slate-300');
        track.classList.remove('admin-primary-bg');
        thumb.style.transform = 'translateX(0px)';
      }
    });
  </script>
  
  <!-- Toggle Ocultar Duplicados -->
  <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 mb-4">
    <label class="flex items-start gap-3 cursor-pointer group">
      <input type="checkbox" name="hide_duplicates" value="1" <?= $templateHideDuplicates ? 'checked' : '' ?> class="hidden" id="hide-duplicates-toggle">
      <span class="w-10 h-6 rounded-full relative transition-colors flex-shrink-0 mt-0.5 <?= $templateHideDuplicates ? 'admin-primary-bg' : 'bg-slate-300' ?>" id="hide-duplicates-track">
        <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" id="hide-duplicates-thumb" style="transform: translateX(<?= $templateHideDuplicates ? '16px' : '0px' ?>)"></span>
      </span>
      <div>
        <span class="text-sm font-medium text-slate-800 group-hover:text-slate-900">Ocultar ingredientes repetidos</span>
        <a href="/admin/<?= rawurlencode($company['slug']) ?>/guide/customization-templates#form" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
        <p class="text-xs text-slate-500 mt-0.5">
          Se o produto já tiver um ingrediente em outro grupo (ex: Cebola Caramelizada), ele será ocultado neste grupo na página de personalização do cliente.
        </p>
      </div>
    </label>
  </div>
  <script>
    document.getElementById('hide-duplicates-toggle')?.addEventListener('change', function() {
      const track = document.getElementById('hide-duplicates-track');
      const thumb = document.getElementById('hide-duplicates-thumb');
      if (this.checked) {
        track.classList.remove('bg-slate-300');
        track.classList.add('admin-primary-bg');
        thumb.style.transform = 'translateX(16px)';
      } else {
        track.classList.add('bg-slate-300');
        track.classList.remove('admin-primary-bg');
        thumb.style.transform = 'translateX(0px)';
      }
    });
  </script>

  <?php if ($isEdit && !empty($productsUsing)): ?>
  <!-- Produtos usando este grupo -->
  <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
    <div class="flex items-center gap-2 mb-3">
      <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
      </div>
      <div>
        <h3 class="text-sm font-semibold text-slate-800">Produtos usando este grupo</h3>
        <p class="text-xs text-slate-500"><?= count($productsUsing) ?> produto<?= count($productsUsing) > 1 ? 's' : '' ?></p>
      </div>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($productsUsing as $prod): ?>
        <a href="<?= base_url('admin/' . rawurlencode($company['slug']) . '/products/' . (int)$prod['id'] . '/edit') ?>" 
           class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200 transition-colors"
           title="Editar <?= htmlspecialchars($prod['name']) ?>">
          <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
          </svg>
          <?= htmlspecialchars($prod['name']) ?>
          <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
          </svg>
        </a>
      <?php endforeach; ?>
    </div>
    
    <!-- Sincronização automática -->
    <div class="mt-4 pt-4 border-t border-slate-200">
      <label class="flex items-start gap-3 cursor-pointer group">
        <input type="checkbox" name="sync_products" value="1" checked
               class="hidden" id="sync-products-toggle">
        <span class="w-10 h-6 rounded-full relative transition-colors admin-primary-bg flex-shrink-0 mt-0.5" id="sync-track">
          <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" id="sync-thumb" style="transform: translateX(16px)"></span>
        </span>
        <div>
          <span class="text-sm font-medium text-slate-800 group-hover:text-slate-900">Sincronizar alterações com os produtos</span>
          <a href="/admin/<?= rawurlencode($company['slug']) ?>/guide/customization-templates#sync" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
          <p class="text-xs text-slate-500 mt-0.5">
            Ao salvar, as alterações serão aplicadas automaticamente em todos os <?= count($productsUsing) ?> produto(s) que usam este grupo.
          </p>
        </div>
      </label>
    </div>
  </div>
  <script>
    document.getElementById('sync-products-toggle')?.addEventListener('change', function() {
      const track = document.getElementById('sync-track');
      const thumb = document.getElementById('sync-thumb');
      if (this.checked) {
        track.classList.remove('bg-slate-300');
        track.classList.add('admin-primary-bg');
        thumb.style.transform = 'translateX(16px)';
      } else {
        track.classList.add('bg-slate-300');
        track.classList.remove('admin-primary-bg');
        thumb.style.transform = 'translateX(0px)';
      }
    });
  </script>
  <?php endif; ?>
  
  <!-- CARD: Informações do Grupo -->
  <div class="cust-group rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="0" data-mode="<?= e($templateMode) ?>">
    
    <!-- Header do Grupo -->
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4">
      <div class="flex items-center gap-3">
        <button type="button" class="inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400" title="Arrastar">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
          </svg>
        </button>
        <input type="text" name="name" required
               class="cust-group-name w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
               placeholder="Nome do grupo (ex: Adicionais, Molhos...)" value="<?= e($templateName) ?>"/>
      </div>
      
      <div class="grid items-start gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
        <label class="grid gap-1 text-sm">
          <span class="text-xs text-slate-500">Modo de seleção</span>
          <a href="/admin/<?= rawurlencode($company['slug']) ?>/guide/customization-templates#modes" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
          <select name="type" class="cust-mode-select rounded-lg border border-slate-300 bg-white px-3 py-2">
            <option value="extra" <?= $templateMode === 'extra' ? 'selected' : '' ?>>Adicionar ingredientes livremente</option>
            <option value="choice" <?= $templateMode === 'choice' ? 'selected' : '' ?>>Escolher ingrediente</option>
            <option value="pool" <?= $templateMode === 'pool' ? 'selected' : '' ?>>Montagem (açaí, poke...)</option>
          </select>
        </label>
        <div class="cust-choice-settings <?= $templateMode === 'choice' ? '' : 'hidden' ?>">
          <div class="grid gap-2 md:grid-cols-2">
            <label class="grid gap-1 text-xs text-slate-500">
              <span>Seleções mínimas</span>
              <input type="number" class="cust-choice-min rounded-lg border border-slate-300 px-3 py-2"
                     name="min_qty" value="<?= $templateMode === 'choice' ? $templateMinQty : 0 ?>" min="0" step="1" <?= $templateMode !== 'choice' ? 'disabled' : '' ?>>
            </label>
            <label class="grid gap-1 text-xs text-slate-500">
              <span>Seleções máximas</span>
              <input type="number" class="cust-choice-max rounded-lg border border-slate-300 px-3 py-2"
                     name="max_qty" value="<?= $templateMode === 'choice' ? $templateMaxQty : 1 ?>" min="1" step="1" <?= $templateMode !== 'choice' ? 'disabled' : '' ?>>
            </label>
          </div>
          <p class="mt-1 text-xs text-slate-500">Defina quantas opções o cliente pode marcar.</p>
        </div>
        <div class="cust-pool-settings <?= $templateMode === 'pool' ? '' : 'hidden' ?>">
          <div class="grid gap-2 md:grid-cols-2">
            <label class="grid gap-1 text-xs text-slate-500">
              <span>Total mínimo</span>
              <input type="number" class="cust-pool-min rounded-lg border border-slate-300 px-3 py-2"
                     name="min_qty" value="<?= $templateMode === 'pool' ? $templateMinQty : 0 ?>" min="0" step="1" <?= $templateMode !== 'pool' ? 'disabled' : '' ?>>
            </label>
            <label class="grid gap-1 text-xs text-slate-500">
              <span>Total máximo</span>
              <input type="number" class="cust-pool-max rounded-lg border border-slate-300 px-3 py-2"
                     name="max_qty" value="<?= $templateMode === 'pool' ? $templateMaxQty : 4 ?>" min="1" step="1" <?= $templateMode !== 'pool' ? 'disabled' : '' ?>>
            </label>
          </div>
          <p class="mt-1 text-xs text-slate-500">Total de itens que o cliente pode montar (ex: 4 frutas no açaí).</p>
        </div>
      </div>
    </div>
    
    <!-- Itens do Grupo -->
    <div id="cust-items-wrapper">
      <?php if (!empty($items)): foreach ($items as $ii => $item): $ii = (int)$ii;
          $selId = isset($item['ingredient_id']) ? (int)$item['ingredient_id'] : 0;
          $itemLabel = $item['label'] ?? '';
          $def = !empty($item['is_default']);
          $minQ = isset($item['min_qty']) ? (int)$item['min_qty'] : 0;
          $maxQ = isset($item['max_qty']) ? (int)$item['max_qty'] : 1;
          $defQty = isset($item['default_qty']) ? (int)$item['default_qty'] : $minQ;
          $itemDelta = isset($item['delta']) ? (float)$item['delta'] : 0;
          ?>
      <div class="cust-item border-t border-slate-100 p-4" data-item-index="<?= $ii ?>" draggable="true">
        <div class="flex items-start gap-4">
          <!-- Handle para arrastar -->
          <button type="button" class="cust-item-drag-handle mt-4 p-1 text-slate-400 hover:text-slate-600" title="Arrastar ingrediente">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
            </svg>
          </button>
          <input type="hidden" class="cust-item-order" name="items[<?= $ii ?>][sort_order]" value="<?= $ii ?>">
          
          <!-- Ingrediente -->
          <div class="flex-1 min-w-0">
            <label class="block text-xs font-medium text-slate-500 mb-1">Ingrediente</label>
            <div class="ingredient-typeahead-wrapper">
              <input type="text"
                     class="ingredient-typeahead-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                     placeholder="Digite para buscar..."
                     autocomplete="off"
                     data-default-min="<?= $minQ ?>" 
                     data-default-max="<?= $maxQ ?>"
                     value="<?php 
                       if ($selId) {
                         foreach ($ingredients as $ing) {
                           if ((int)$ing['id'] === $selId) {
                             echo e($ing['name']);
                             if (!empty($ing['internal_name'])) {
                               echo ' (' . e($ing['internal_name']) . ')';
                             }
                             break;
                           }
                         }
                       } else {
                         echo e($itemLabel);
                       }
                     ?>">
              <input type="hidden" 
                     name="items[<?= $ii ?>][ingredient_id]"
                     class="ingredient-id-hidden"
                     value="<?= $selId ?>">
              <input type="hidden" name="items[<?= $ii ?>][label]" class="item-label-hidden" value="<?= e($itemLabel) ?>">
              <input type="hidden" name="items[<?= $ii ?>][delta]" class="item-delta-hidden" value="<?= $itemDelta ?>">
              <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none ingredient-search-icon">
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                  <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div class="ingredient-suggestions hidden"></div>
              <?php if ($selId): ?>
              <button type="button" class="ingredient-clear-btn" title="Limpar">✕</button>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Quantidades Min/Max -->
          <div class="cust-limits-wrap flex gap-2">
            <div class="cust-limits flex gap-2" data-min="<?= $minQ ?>" data-max="<?= $maxQ ?>">
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Mín</label>
                <input type="number" class="cust-min-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                       name="items[<?= $ii ?>][min_qty]" value="<?= $minQ ?>" min="0" step="1">
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Máx</label>
                <input type="number" class="cust-max-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                       name="items[<?= $ii ?>][max_qty]" value="<?= $maxQ ?>" min="0" step="1">
              </div>
            </div>
          </div>
          
          <!-- Checkbox Padrão -->
          <input type="hidden" class="cust-default-flag" name="items[<?= $ii ?>][is_default]" value="<?= $def ? '1' : '0' ?>">
          <div class="cust-default-toggle-wrap">
            <label class="block text-xs font-medium text-slate-500 mb-1">Padrão</label>
            <button type="button" class="cust-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors <?= $def ? 'admin-primary-bg border-transparent text-white' : 'bg-white border-slate-300 text-slate-600 hover:bg-slate-50' ?>">
              <?= $def ? 'Sim' : 'Não' ?>
            </button>
            <input type="checkbox" class="cust-default-toggle hidden" <?= $def ? 'checked' : '' ?>>
          </div>
          
          <!-- Quantidade Padrão -->
          <div class="cust-default-qty-wrap">
            <label class="block text-xs font-medium text-slate-500 mb-1">Qtd padrão</label>
            <input type="number" class="cust-default-qty w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                   name="items[<?= $ii ?>][default_qty]"
                   value="<?= $defQty ?>" min="0" step="1">
          </div>
          
          <!-- Botão Remover -->
          <button type="button" class="cust-remove-item rounded-full p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 mt-4" title="Remover ingrediente">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>
      <?php endforeach; else: ?>
      <!-- Item vazio inicial -->
      <div class="cust-item border-t border-slate-100 p-4" data-item-index="0" draggable="true">
        <div class="flex items-start gap-4">
          <button type="button" class="cust-item-drag-handle mt-4 p-1 text-slate-400 hover:text-slate-600" title="Arrastar ingrediente">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
            </svg>
          </button>
          <input type="hidden" class="cust-item-order" name="items[0][sort_order]" value="0">
          
          <div class="flex-1 min-w-0">
            <label class="block text-xs font-medium text-slate-500 mb-1">Ingrediente</label>
            <div class="ingredient-typeahead-wrapper">
              <input type="text"
                     class="ingredient-typeahead-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                     placeholder="Digite para buscar..."
                     autocomplete="off"
                     data-default-min="0" 
                     data-default-max="10">
              <input type="hidden" name="items[0][ingredient_id]" class="ingredient-id-hidden" value="">
              <input type="hidden" name="items[0][label]" class="item-label-hidden" value="">
              <input type="hidden" name="items[0][delta]" class="item-delta-hidden" value="0">
              <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none ingredient-search-icon">
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                  <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div class="ingredient-suggestions hidden"></div>
            </div>
          </div>
          
          <div class="cust-limits-wrap flex gap-2">
            <div class="cust-limits flex gap-2" data-min="0" data-max="10">
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Mín</label>
                <input type="number" class="cust-min-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                       name="items[0][min_qty]" value="0" min="0" step="1">
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Máx</label>
                <input type="number" class="cust-max-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                       name="items[0][max_qty]" value="10" min="0" step="1">
              </div>
            </div>
          </div>
          
          <!-- Checkbox Padrão -->
          <input type="hidden" class="cust-default-flag" name="items[0][is_default]" value="0">
          <div class="cust-default-toggle-wrap">
            <label class="block text-xs font-medium text-slate-500 mb-1">Padrão</label>
            <button type="button" class="cust-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors bg-white border-slate-300 text-slate-600 hover:bg-slate-50">
              Não
            </button>
            <input type="checkbox" class="cust-default-toggle hidden">
          </div>
          
          <div class="cust-default-qty-wrap">
            <label class="block text-xs font-medium text-slate-500 mb-1">Qtd padrão</label>
            <input type="number" class="cust-default-qty w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                   name="items[0][default_qty]" value="1" min="0" step="1">
          </div>
          
          <button type="button" class="cust-remove-item rounded-full p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 mt-4" title="Remover ingrediente">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>
      <?php endif; ?>
    </div>
    
    <!-- Footer do Grupo - Botões -->
    <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
      <button type="button" id="add-item-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Ingrediente</button>
      <button type="button" id="add-choice-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Escolher ingrediente</button>
    </div>
  </div>
  
  <!-- Botões de Ação -->
  <div class="flex items-center justify-between gap-4">
    <a href="<?= base_url('admin/' . rawurlencode($company['slug']) . '/customization-templates') ?>" 
       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
      Cancelar
    </a>
    <button type="submit" class="rounded-lg admin-primary-bg px-6 py-2 text-sm font-medium text-white hover:opacity-90">
      <?= $isEdit ? 'Salvar Alterações' : 'Criar Grupo' ?>
    </button>
  </div>
</form>

<!-- Template para novo item -->
<template id="tpl-cust-item">
  <div class="cust-item border-t border-slate-100 p-4" data-item-index="__II__" draggable="true">
    <div class="flex items-start gap-4">
      <button type="button" class="cust-item-drag-handle mt-4 p-1 text-slate-400 hover:text-slate-600" title="Arrastar ingrediente">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
        </svg>
      </button>
      <input type="hidden" class="cust-item-order" name="items[__II__][sort_order]" value="0">
      
      <div class="flex-1 min-w-0">
        <label class="block text-xs font-medium text-slate-500 mb-1">Ingrediente</label>
        <div class="ingredient-typeahead-wrapper">
          <input type="text"
                 class="ingredient-typeahead-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                 placeholder="Digite para buscar..."
                 autocomplete="off"
                 data-default-min="0" 
                 data-default-max="10">
          <input type="hidden" name="items[__II__][ingredient_id]" class="ingredient-id-hidden" value="">
          <input type="hidden" name="items[__II__][label]" class="item-label-hidden" value="">
          <input type="hidden" name="items[__II__][delta]" class="item-delta-hidden" value="0">
          <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none ingredient-search-icon">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
              <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="ingredient-suggestions hidden"></div>
        </div>
      </div>
      
      <div class="cust-limits-wrap flex gap-2">
        <div class="cust-limits flex gap-2" data-min="0" data-max="10">
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Mín</label>
            <input type="number" class="cust-min-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                   name="items[__II__][min_qty]" value="0" min="0" step="1">
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Máx</label>
            <input type="number" class="cust-max-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                   name="items[__II__][max_qty]" value="10" min="0" step="1">
          </div>
        </div>
      </div>
      
      <!-- Checkbox Padrão -->
      <input type="hidden" class="cust-default-flag" name="items[__II__][is_default]" value="0">
      <div class="cust-default-toggle-wrap">
        <label class="block text-xs font-medium text-slate-500 mb-1">Padrão</label>
        <button type="button" class="cust-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors bg-white border-slate-300 text-slate-600 hover:bg-slate-50">
          Não
        </button>
        <input type="checkbox" class="cust-default-toggle hidden">
      </div>
      
      <div class="cust-default-qty-wrap">
        <label class="block text-xs font-medium text-slate-500 mb-1">Qtd padrão</label>
        <input type="number" class="cust-default-qty w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
               name="items[__II__][default_qty]" value="1" min="0" step="1">
      </div>
      
      <button type="button" class="cust-remove-item rounded-full p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 mt-4" title="Remover ingrediente">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>
  </div>
</template>

<script>
(function() {
  const itemsWrapper = document.getElementById('cust-items-wrapper');
  const addItemBtn = document.getElementById('add-item-btn');
  const addChoiceBtn = document.getElementById('add-choice-btn');
  const tplItem = document.getElementById('tpl-cust-item');
  const modeSelect = document.querySelector('.cust-mode-select');
  const choiceSettings = document.querySelector('.cust-choice-settings');
  const poolSettings = document.querySelector('.cust-pool-settings');
  const ingredients = window.INGREDIENTS_DATA || [];
  
  // Próximo índice de item
  function nextItemIndex() {
    const items = itemsWrapper.querySelectorAll('.cust-item');
    const indices = Array.from(items).map(i => parseInt(i.dataset.itemIndex) || 0);
    return indices.length ? Math.max(...indices) + 1 : 0;
  }
  
  // Adicionar item
  function addItem() {
    const idx = nextItemIndex();
    const html = tplItem.innerHTML.replaceAll('__II__', idx);
    const wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    const node = wrap.firstElementChild;
    itemsWrapper.appendChild(node);
    setupIngredientTypeahead(node.querySelector('.ingredient-typeahead-input'));
    setupItemEvents(node);
    return node;
  }
  
  // Setup eventos do item
  function setupItemEvents(item) {
    // Remover item
    item.querySelector('.cust-remove-item')?.addEventListener('click', () => {
      item.remove();
    });
    
    // Sincronização max/qtd padrão
    const maxInput = item.querySelector('.cust-max-input');
    const qtyInput = item.querySelector('.cust-default-qty');
    
    // Quando qtd padrão subir acima de max, aumentar max
    if (qtyInput) {
      qtyInput.addEventListener('input', () => {
        const qty = Number(qtyInput.value || 0);
        if (maxInput) {
          const currentMax = Number(maxInput.value || 0);
          if (qty > currentMax) {
            maxInput.value = qty;
          }
        }
      });
    }
    
    // Quando max diminuir abaixo de qtd padrão, diminuir qtd padrão
    if (maxInput) {
      maxInput.addEventListener('input', () => {
        const newMax = Number(maxInput.value || 0);
        if (qtyInput) {
          const qty = Number(qtyInput.value || 0);
          if (newMax < qty) {
            qtyInput.value = newMax;
          }
        }
      });
    }
    
    // Botão Padrão (toggle Sim/Não)
    const toggleBtn = item.querySelector('.cust-default-btn');
    const checkbox = item.querySelector('.cust-default-toggle');
    const flag = item.querySelector('.cust-default-flag');
    
    if (toggleBtn && checkbox && flag) {
      toggleBtn.addEventListener('click', () => {
        checkbox.checked = !checkbox.checked;
        flag.value = checkbox.checked ? '1' : '0';
        
        if (checkbox.checked) {
          toggleBtn.textContent = 'Sim';
          toggleBtn.classList.remove('bg-white', 'border-slate-300', 'text-slate-600', 'hover:bg-slate-50');
          toggleBtn.classList.add('admin-primary-bg', 'border-transparent', 'text-white');
        } else {
          toggleBtn.textContent = 'Não';
          toggleBtn.classList.remove('admin-primary-bg', 'border-transparent', 'text-white');
          toggleBtn.classList.add('bg-white', 'border-slate-300', 'text-slate-600', 'hover:bg-slate-50');
        }
      });
    }
    
    // Drag & Drop
    item.addEventListener('dragstart', handleDragStart);
    item.addEventListener('dragend', handleDragEnd);
    item.addEventListener('dragover', handleDragOver);
    item.addEventListener('drop', handleDrop);
  }
  
  // Drag & Drop handlers
  let draggedItem = null;
  
  function handleDragStart(e) {
    draggedItem = e.target.closest('.cust-item');
    if (draggedItem) {
      draggedItem.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    }
  }
  
  function handleDragEnd() {
    if (draggedItem) {
      draggedItem.classList.remove('dragging');
      draggedItem = null;
      updateItemOrder();
    }
  }
  
  function handleDragOver(e) {
    e.preventDefault();
    const target = e.target.closest('.cust-item');
    if (target && target !== draggedItem) {
      const rect = target.getBoundingClientRect();
      const midY = rect.top + rect.height / 2;
      if (e.clientY < midY) {
        itemsWrapper.insertBefore(draggedItem, target);
      } else {
        itemsWrapper.insertBefore(draggedItem, target.nextSibling);
      }
    }
  }
  
  function handleDrop(e) {
    e.preventDefault();
  }
  
  function updateItemOrder() {
    itemsWrapper.querySelectorAll('.cust-item').forEach((item, idx) => {
      const orderInput = item.querySelector('.cust-item-order');
      if (orderInput) orderInput.value = idx;
    });
  }
  
  // Typeahead de ingredientes
  function setupIngredientTypeahead(input) {
    if (!input) return;
    
    const wrapper = input.closest('.ingredient-typeahead-wrapper');
    const hiddenInput = wrapper.querySelector('.ingredient-id-hidden');
    const labelInput = wrapper.querySelector('.item-label-hidden');
    const deltaInput = wrapper.querySelector('.item-delta-hidden');
    const suggestionsDiv = wrapper.querySelector('.ingredient-suggestions');
    
    let selectedIndex = -1;
    
    function showSuggestions(query) {
      const q = query.toLowerCase().trim();
      let filtered = ingredients;
      if (q) {
        filtered = ingredients.filter(ing => 
          ing.name.toLowerCase().includes(q) || 
          (ing.internal_name && ing.internal_name.toLowerCase().includes(q))
        );
      }
      
      if (filtered.length === 0) {
        suggestionsDiv.innerHTML = '<div class="ingredient-suggestion text-slate-400">Nenhum ingrediente encontrado</div>';
      } else {
        suggestionsDiv.innerHTML = filtered.slice(0, 10).map((ing, i) => `
          <div class="ingredient-suggestion ${i === selectedIndex ? 'active' : ''}" data-id="${ing.id}" data-name="${escapeHtml(ing.name)}" data-delta="${ing.delta || 0}" data-min="${ing.min_qty || 0}" data-max="${ing.max_qty || 10}">
            <span class="name">${escapeHtml(ing.name)}</span>
            ${ing.internal_name ? `<span class="internal">(${escapeHtml(ing.internal_name)})</span>` : ''}
            ${ing.delta ? `<span class="delta">+R$ ${parseFloat(ing.delta).toFixed(2)}</span>` : ''}
          </div>
        `).join('');
      }
      
      suggestionsDiv.classList.remove('hidden');
      
      // Click nas sugestões
      suggestionsDiv.querySelectorAll('.ingredient-suggestion[data-id]').forEach(el => {
        el.addEventListener('click', () => selectIngredient(el));
      });
    }
    
    function selectIngredient(el) {
      const id = el.dataset.id;
      const name = el.dataset.name;
      const delta = el.dataset.delta;
      const min = el.dataset.min;
      const max = el.dataset.max;
      
      input.value = name;
      hiddenInput.value = id;
      if (labelInput) labelInput.value = name;
      if (deltaInput) deltaInput.value = delta;
      
      // Atualizar min/max
      const item = input.closest('.cust-item');
      const minInput = item?.querySelector('.cust-min-input');
      const maxInput = item?.querySelector('.cust-max-input');
      if (minInput) minInput.value = min;
      if (maxInput) maxInput.value = max;
      
      suggestionsDiv.classList.add('hidden');
      
      // Adicionar botão de limpar
      addClearButton(wrapper);
    }
    
    function addClearButton(wrapper) {
      if (wrapper.querySelector('.ingredient-clear-btn')) return;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ingredient-clear-btn';
      btn.title = 'Limpar';
      btn.textContent = '✕';
      btn.addEventListener('click', () => clearSelection(wrapper));
      wrapper.appendChild(btn);
    }
    
    function clearSelection(wrapper) {
      const input = wrapper.querySelector('.ingredient-typeahead-input');
      const hidden = wrapper.querySelector('.ingredient-id-hidden');
      const label = wrapper.querySelector('.item-label-hidden');
      const delta = wrapper.querySelector('.item-delta-hidden');
      const clearBtn = wrapper.querySelector('.ingredient-clear-btn');
      
      input.value = '';
      hidden.value = '';
      if (label) label.value = '';
      if (delta) delta.value = '0';
      if (clearBtn) clearBtn.remove();
      input.focus();
    }
    
    // Eventos
    input.addEventListener('input', () => {
      showSuggestions(input.value);
    });
    
    input.addEventListener('focus', () => {
      showSuggestions(input.value);
    });
    
    input.addEventListener('keydown', (e) => {
      const suggestions = suggestionsDiv.querySelectorAll('.ingredient-suggestion[data-id]');
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
        updateActiveItem(suggestions);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex = Math.max(selectedIndex - 1, 0);
        updateActiveItem(suggestions);
      } else if (e.key === 'Enter' && selectedIndex >= 0) {
        e.preventDefault();
        selectIngredient(suggestions[selectedIndex]);
      } else if (e.key === 'Escape') {
        suggestionsDiv.classList.add('hidden');
      }
    });
    
    function updateActiveItem(suggestions) {
      suggestions.forEach((el, i) => {
        el.classList.toggle('active', i === selectedIndex);
      });
    }
    
    // Setup clear button se já existir seleção
    if (hiddenInput.value) {
      addClearButton(wrapper);
    }
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Modo de seleção - quando muda, ajusta os valores padrão
  modeSelect?.addEventListener('change', () => {
    const choiceMin = document.querySelector('.cust-choice-min');
    const choiceMax = document.querySelector('.cust-choice-max');
    const poolMin = document.querySelector('.cust-pool-min');
    const poolMax = document.querySelector('.cust-pool-max');
    const mode = modeSelect.value;
    
    // Toggle visibility
    choiceSettings?.classList.toggle('hidden', mode !== 'choice');
    poolSettings?.classList.toggle('hidden', mode !== 'pool');
    
    // Enable/disable name attributes to avoid conflicting min_qty/max_qty submissions
    if (choiceMin) choiceMin.disabled = (mode !== 'choice');
    if (choiceMax) choiceMax.disabled = (mode !== 'choice');
    if (poolMin) poolMin.disabled = (mode !== 'pool');
    if (poolMax) poolMax.disabled = (mode !== 'pool');
    
    if (mode === 'choice') {
      if (choiceMax && (parseInt(choiceMax.value) > 10 || !choiceMax.dataset.userEdited)) {
        choiceMax.value = '1';
      }
      if (choiceMin && !choiceMin.dataset.userEdited) {
        choiceMin.value = '0';
      }
    } else if (mode === 'pool') {
      if (poolMax && !poolMax.dataset.userEdited) {
        poolMax.value = '4';
      }
      if (poolMin && !poolMin.dataset.userEdited) {
        poolMin.value = '0';
      }
    }
  });
  
  // Marcar quando o usuário edita os campos manualmente
  document.querySelector('.cust-choice-min')?.addEventListener('input', function() {
    this.dataset.userEdited = 'true';
  });
  document.querySelector('.cust-choice-max')?.addEventListener('input', function() {
    this.dataset.userEdited = 'true';
  });
  
  // Botões
  addItemBtn?.addEventListener('click', addItem);
  addChoiceBtn?.addEventListener('click', () => {
    if (modeSelect) modeSelect.value = 'choice';
    choiceSettings?.classList.remove('hidden');
    addItem();
  });
  
  // Inicializar itens existentes
  itemsWrapper.querySelectorAll('.cust-item').forEach(item => {
    setupIngredientTypeahead(item.querySelector('.ingredient-typeahead-input'));
    setupItemEvents(item);
  });
  
  // Fechar sugestões ao clicar fora
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.ingredient-typeahead-wrapper')) {
      document.querySelectorAll('.ingredient-suggestions').forEach(div => div.classList.add('hidden'));
    }
  });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
