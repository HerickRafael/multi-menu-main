<?php
// admin/products/form.php — Formulário de produtos (cards + drag)

// ===== Guard rails / Vars padrão =====
$p              = $p              ?? [];
$company        = $company        ?? [];
$cats           = $cats           ?? [];
$groups         = $groups         ?? [];           // COMBO
$simpleProducts = $simpleProducts ?? [];           // p/ combos
$ingredients    = $ingredients    ?? [];
$errors         = $errors         ?? [];

$simpleMap = [];

foreach ($simpleProducts as $spMeta) {
    $sid = isset($spMeta['id']) ? (int)$spMeta['id'] : null;

    if ($sid) {
        $simpleMap[$sid] = $spMeta;
    }
}

// Personalização
$customization  = $customization  ?? [];           // ['enabled'=>bool, 'groups'=>[...]]
$custEnabled    = !empty($customization['enabled']);
$custGroups     = $customization['groups'] ?? [];

// Título / Ação
$title   = 'Produto - ' . ($company['name'] ?? '');
$editing = !empty($p['id']);
$slug    = rawurlencode((string)($company['slug'] ?? ''));
$action  = $editing ? "admin/{$slug}/products/" . (int)$p['id'] : "admin/{$slug}/products";

// Configuração do header padronizado
$pageTitle = ($editing ? 'Editar' : 'Novo') . ' Produto';
$pageDescription = $editing ? 'Altere os dados do produto' : 'Cadastre um novo produto no cardápio';
$pageIcon = $editing 
    ? '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    : '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Produtos', 'url' => base_url("admin/{$slug}/products")],
    ['label' => $editing ? 'Editar' : 'Novo']
];
$actions = [
    ['label' => 'Salvar', 'onclick' => "document.getElementById('product-form').submit()", 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'primary' => true]
];
$activeSlug = $slug;

// Templates de personalização para o modal de copiar grupo
$custTemplates = $custTemplates ?? [];
$custTemplatesJson = json_encode($custTemplates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

?>
<?php ob_start(); ?>

<!-- CSS para Typeahead de Ingredientes -->
<style>
.ingredient-typeahead-wrapper {
  position: relative;
  width: 100%;
}
.ingredient-typeahead-input {
  width: 100%;
  cursor: text;
} 
.ingredient-typeahead-input::placeholder {
  color: #94a3b8;
}
.ingredient-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 240px;
  overflow-y: auto;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  z-index: 50;
  margin-top: 2px;
}
.ingredient-suggestion-item {
  padding: 0.625rem 0.75rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.15s;
}
.ingredient-suggestion-item:last-child {
  border-bottom: none;
}
.ingredient-suggestion-item:hover,
.ingredient-suggestion-item.highlighted {
  background-color: #f1f5f9;
}
.ingredient-suggestion-item.selected {
  background-color: #e0e7ff;
}
.ingredient-suggestion-name {
  font-weight: 500;
  color: #1e293b;
  font-size: 0.875rem;
}
.ingredient-suggestion-details {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 2px;
}
.ingredient-search-icon {
  transition: opacity 0.15s;
}
.ingredient-typeahead-wrapper:has(.ingredient-clear-btn) .ingredient-search-icon {
  opacity: 0;
  pointer-events: none;
}
.ingredient-clear-btn {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  padding: 4px;
  border-radius: 9999px;
  color: #94a3b8;
  cursor: pointer;
  transition: color 0.15s, background-color 0.15s;
}
.ingredient-clear-btn:hover {
  color: #ef4444;
  background-color: #fef2f2;
}
.ingredient-no-results {
  padding: 0.75rem;
  text-align: center;
  color: #64748b;
  font-size: 0.875rem;
}
</style>

<!-- JSON de Ingredientes para Typeahead -->
<script>
window.__INGREDIENTS_DATA__ = <?= json_encode(array_map(function($ing) {
    return [
        'id' => (int)$ing['id'],
        'name' => $ing['name'],
        'internal_name' => $ing['internal_name'] ?? '',
        'min_qty' => (int)($ing['min_qty'] ?? 0),
        'max_qty' => (int)($ing['max_qty'] ?? 1),
        'image_path' => $ing['image_path'] ?? '',
        'price' => isset($ing['price']) ? (float)$ing['price'] : 0,
    ];
}, $ingredients), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<div class="mx-auto max-w-4xl p-4 space-y-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- ERROS -->
<?php if (!empty($errors) && is_array($errors)): ?>
  <div class="rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <strong class="mb-1 block">Por favor, corrija os campos abaixo:</strong>
    <ul class="list-disc space-y-0.5 pl-5">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form id="product-form"
      method="post"
      action="<?= e(base_url($action)) ?>"
      enctype="multipart/form-data"
      class="relative grid gap-6 rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">

  <!-- CSRF / METHOD -->
  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>
  <?php if ($editing): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

 <!-- CARD: Dados básicos -->
<fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
  <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
      <path d="M5 7h14M5 12h10M5 17h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    Dados básicos
    <a href="<?= e(base_url('admin/' . $slug . '/guide/products')) ?>#form" target="_blank" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda: Dados básicos">?</a>
  </legend>

  <label for="category_id" class="grid gap-1 mb-3">
    <span class="text-sm text-slate-700">Categoria</span>
    <select name="category_id" id="category_id" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 focus:ring-2 focus:ring-indigo-400" aria-describedby="help-cat">
      <option value="">— sem categoria —</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (isset($p['category_id']) && (int)$p['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
          <?= e($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small id="help-cat" class="text-xs text-slate-500">Usado para agrupar o cardápio.</small>
  </label>

  <div class="grid gap-3 md:grid-cols-2">
    <label for="name" class="grid gap-1">
      <span class="text-sm text-slate-700">Nome <span class="text-red-500">*</span></span>
      <input required name="name" id="name" value="<?= e($p['name'] ?? '') ?>" autocomplete="off"
             class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
    </label>

    <label for="sku" class="grid gap-1">
      <span class="text-sm text-slate-700">SKU</span>
      <div class="sku-lock relative">
        <input name="sku" id="sku" value="<?= e($p['sku'] ?? '') ?>" placeholder="Gerado automaticamente" autocomplete="off"
               readonly
               class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 pr-12 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
<button type="button" 
  class="sku-lock-btn focus:outline-none focus:ring-0">
<svg width="16" height="16" fill="currentColor" class="bi bi-lock-fill" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M8 0a4 4 0 0 1 4 4v2.05a2.5 2.5 0 0 1 2 2.45v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4m0 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/>
</svg>
  <span class="sku-lock-tooltip">Definido automaticamente em ordem crescente e sem repetições.</span>
</button>

      </div>
    </label>
  </div>
</fieldset>

  <!-- CARD: Tipo & Preço -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M7 12h10M12 7v10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      Tipo & Preço
      <a href="<?= e(base_url('admin/' . $slug . '/guide/products')) ?>#pricing" target="_blank" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda: Tipo & Preço">?</a>
    </legend>

    <div class="grid gap-3 md:grid-cols-2">
      <label for="type" class="grid gap-1">
        <span class="text-sm text-slate-700">Tipo</span>
        <?php $ptype = $p['type'] ?? 'simple'; ?>
        <select name="type" id="type" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 focus:ring-2 focus:ring-indigo-400">
          <option value="simple" <?= $ptype === 'simple' ? 'selected' : '' ?>>Simples</option>
          <option value="combo"  <?= $ptype === 'combo' ? 'selected' : '' ?>>Combo</option>
        </select>
        <small class="text-xs text-slate-500">Combos usam Grupos. Simples têm Personalização.</small>
      </label>

      <label for="price_mode" class="grid gap-1">
        <span class="text-sm text-slate-700">Modo de preço</span>
        <?php $pmode = $p['price_mode'] ?? 'fixed'; ?>
        <select name="price_mode" id="price_mode" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 focus:ring-2 focus:ring-indigo-400">
          <option value="fixed" <?= $pmode === 'fixed' ? 'selected' : '' ?>>Fixo (preço base)</option>
          <option value="sum"   <?= $pmode === 'sum' ? 'selected' : '' ?>>Somar itens do grupo</option>
        </select>
        <small class="text-xs text-slate-500">Em “Somar itens”, total = <code class="rounded bg-slate-100 px-1">preço base + deltas</code>.</small>
      </label>
    </div>

    <!-- Preço e Ordem -->
    <div class="mt-3 grid gap-3 md:grid-cols-2">
      <label for="price" class="grid gap-1">
        <span class="text-sm text-slate-700">Preço base (R$)</span>
        <input name="price" id="price" type="number" step="0.01" min="0" value="<?= e($p['price'] ?? '') ?>"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400"
               placeholder="0,00" inputmode="decimal" autocomplete="off" required>
        <small class="text-xs text-slate-500">Em modo "Somar", será calculado automaticamente</small>
      </label>

      <label for="sort_order" class="grid gap-1">
        <span class="text-sm text-slate-700">Ordem de exibição</span>
        <input name="sort_order" id="sort_order" type="number" step="1" value="<?= e($p['sort_order'] ?? 0) ?>"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
        <small class="text-xs text-slate-500">Determina a posição no cardápio</small>
      </label>
    </div>

    <!-- Seção de Promoções -->
    <div class="mt-4">
      <!-- Campo promocional para modo FIXO -->
      <div id="promo-fixed-field">
        <label for="promo_price" class="grid gap-1">
          <span class="text-sm font-medium text-slate-700 flex items-center gap-2">
            <svg class="h-4 w-4 text-amber-600" viewBox="0 0 24 24" fill="none">
              <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Preço promocional (opcional)
          </span>
          <input name="promo_price" id="promo_price" type="number" step="0.01" min="0" value="<?= e($p['promo_price'] ?? '') ?>"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-amber-400"
                 placeholder="Ex: 45,00" inputmode="decimal" autocomplete="off">
          <small class="text-xs text-slate-500">Deixe vazio se não houver promoção ativa</small>
        </label>
      </div>

      <!-- Prazo da promoção (opcional) -->
      <div id="promo-dates-field" class="mt-3 grid grid-cols-2 gap-3">
        <label class="grid gap-1">
          <span class="text-xs font-medium text-slate-600"><svg class="inline-block w-3.5 h-3.5 mr-0.5 -mt-0.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Início da promoção</span>
          <input name="promo_start_at" type="datetime-local"
                 value="<?= !empty($p['promo_start_at']) ? date('Y-m-d\TH:i', strtotime($p['promo_start_at'])) : '' ?>"
                 class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-400">
          <small class="text-[10px] text-slate-400">Vazio = imediato</small>
        </label>
        <label class="grid gap-1">
          <span class="text-xs font-medium text-slate-600"><svg class="inline-block w-3.5 h-3.5 mr-0.5 -mt-0.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Fim da promoção</span>
          <input name="promo_end_at" type="datetime-local"
                 value="<?= !empty($p['promo_end_at']) ? date('Y-m-d\TH:i', strtotime($p['promo_end_at'])) : '' ?>"
                 class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-400">
          <small class="text-[10px] text-slate-400">Vazio = sem prazo (permanente)</small>
        </label>
      </div>

      <!-- Campos promocionais para modo SOMAR -->
      <div id="promo-sum-fields" class="hidden space-y-4">
        <!-- Card de Desconto -->
        <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-amber-50 to-orange-50 p-4 shadow-sm">
          <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100">
              <svg class="h-5 w-5 text-amber-600" viewBox="0 0 24 24" fill="none">
                <path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="flex-1">
              <label for="promo_percentage" class="grid gap-2">
                <span class="text-sm font-semibold text-slate-800">Desconto promocional</span>
                <div class="relative">
                  <input name="promo_percentage" id="promo_percentage" type="number" step="0.1" min="0" max="100"
                         class="w-full rounded-lg border-2 border-amber-300 bg-white px-4 py-2.5 pr-12 text-slate-900 placeholder-slate-400 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-400"
                         placeholder="Ex: 15" inputmode="decimal" autocomplete="off">
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-base font-bold text-amber-600">%</span>
                </div>
                <small class="flex items-start gap-1 text-xs leading-relaxed text-slate-600">
                  <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none">
                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span>Digite a porcentagem de desconto que será aplicada ao preço total calculado do combo</span>
                </small>
              </label>
            </div>
          </div>
        </div>

        <!-- Preview do Preço Promocional -->
        <div id="promo-preview" class="hidden">
          <div class="rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-4 shadow-sm">
            <div class="mb-3 flex items-center gap-2 border-b border-green-200 pb-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100">
                <svg class="h-4 w-4 text-green-600" viewBox="0 0 24 24" fill="none">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <span class="text-sm font-bold text-green-700">Preview do Preço Promocional</span>
            </div>
            <div class="space-y-2.5">
              <div class="flex items-center justify-between rounded-lg bg-white/60 px-3 py-2">
                <span class="text-sm text-slate-600">Preço base:</span>
                <span id="preview-base" class="text-base font-semibold text-slate-800">R$ 0,00</span>
              </div>
              <div class="flex items-center justify-between rounded-lg bg-red-50/80 px-3 py-2">
                <span class="text-sm text-red-700 font-medium">Desconto aplicado:</span>
                <span id="preview-discount" class="text-base font-bold text-red-600">-R$ 0,00</span>
              </div>
              <div class="flex items-center justify-between rounded-lg bg-green-100 px-3 py-2.5 shadow-sm">
                <span class="text-sm font-bold text-green-800">Preço final com desconto:</span>
                <span id="preview-discounted" class="text-xl font-black text-green-700">R$ 0,00</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </fieldset>

  <!-- CARD: Descrição -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M5 12h14M5 17h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      Descrição
      <a href="<?= e(base_url('admin/' . $slug . '/guide/products')) ?>#form" target="_blank" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda: Descrição">?</a>
    </legend>

    <label for="description" class="grid gap-2">
      <span class="text-sm text-slate-700">Conteúdo exibido na página do produto</span>
      <textarea name="description" id="description" rows="5" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400" placeholder="Ex.: Pão artesanal, burger 180g, queijo prato e molho especial."><?= e($p['description'] ?? '') ?></textarea>
      <div class="flex items-center justify-between text-xs text-slate-500">
        <span>Use este campo para destacar ingredientes, diferenciais ou modo de preparo.</span>
        <span id="description-counter"></span>
      </div>
    </label>
  </fieldset>

  <!-- CARD: Imagem -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Imagem
      <a href="<?= e(base_url('admin/' . $slug . '/guide/products')) ?>#form" target="_blank" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda: Imagem">?</a>
    </legend>

    <div class="grid items-start gap-3 md:grid-cols-[1fr_auto]">
      <div class="grid gap-2">
        <label for="image" class="text-sm text-slate-700">Upload (jpg/png/webp)</label>
        <label class="inline-flex w-fit cursor-pointer items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
          <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp" class="hidden">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          Selecionar arquivo
        </label>
        <small class="text-xs text-slate-500">Recomendado: 1000×750px ou maior (4:3). Máx. 5 MB.</small>
      </div>

      <div class="flex flex-col items-center gap-2">
        <?php if (!empty($p['image'])): ?>
          <span class="text-xs text-slate-500">Pré-visualização</span>
          <img id="image-preview-img"
               src="<?= e(base_url($p['image'])) ?>"
               class="h-20 w-20 rounded-xl border border-slate-200 object-cover shadow-sm"
               alt="Pré-visualização"
               onerror="this.style.display='none'; document.getElementById('image-preview-placeholder').style.display='flex';">
        <?php else: ?>
          <img id="image-preview-img" class="h-20 w-20 rounded-xl border border-slate-200 object-cover shadow-sm hidden" alt="Pré-visualização">
        <?php endif; ?>
        <div id="image-preview-placeholder" class="<?= !empty($p['image']) ? 'hidden' : 'flex' ?> h-20 w-20 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 shadow-sm">
          <svg class="h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none">
            <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
    </div>
  </fieldset>

  <!-- ===== DRAG ESTILOS ===== -->
  <style>
    .sku-lock-btn{
      position:absolute;
      right:.75rem;
      top:50%;
      transform:translateY(-50%);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      height:2rem;
      width:2rem;
      border-radius:9999px;
      color:rgba(71,85,105,1);
      background-color:transparent;
      border:none;
      cursor:help;
      padding:0;
    }
    .sku-lock-btn:hover,
    .sku-lock-btn:focus{
      color:rgba(30,41,59,1);
    }
    .sku-lock-btn:focus{
      outline:2px solid rgba(99,102,241,.4);
      outline-offset:2px;
    }
    .sku-lock-tooltip{
      position:absolute;
      bottom:-0.5rem;
      right:2.5rem;
      transform:translateY(100%);
      display:none;
      max-width:16rem;
      padding:.5rem .75rem;
      border-radius:.5rem;
      background-color:rgba(15,23,42,.92);
      color:white;
      font-size:.75rem;
      line-height:1.1;
      box-shadow:0 10px 30px -15px rgba(15,23,42,.55);
      text-align:left;
      pointer-events:none;
      z-index:30;
    }
    .sku-lock-btn:hover .sku-lock-tooltip,
    .sku-lock-btn:focus-visible .sku-lock-tooltip,
    .sku-lock-btn:active .sku-lock-tooltip{
      display:block;
    }
    .form-toolbar{
      position:sticky;
    }
    @media (max-width: 639px){
      .form-toolbar{
        margin:0;
        border-radius:1rem 1rem 0 0;
      }
      .form-toolbar-actions > *{
        width:100%;
      }
    }
    /* Personalização */
    #cust-groups-container .cust-group{transition:transform .18s ease,box-shadow .18s ease,opacity .18s ease}
    #cust-groups-container .cust-group.dragging{opacity:.85;transform:scale(.985);box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
    .cust-drag-ghost{box-sizing:border-box;border-radius:.75rem;box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
    /* Drag de items de ingredientes */
    .cust-item{transition:transform .15s ease,box-shadow .15s ease,opacity .15s ease}
    .cust-item.dragging{opacity:.7;background:#f8fafc;box-shadow:0 8px 20px -10px rgba(15,23,42,.3)}
    .cust-item-drag-handle{cursor:grab;touch-action:none}
    .cust-item-drag-handle:active{cursor:grabbing}

    /* Combo */
    #groups-container .group-card{transition:transform .18s ease,box-shadow .18s ease,opacity .18s ease}
    #groups-container .group-card.dragging{opacity:.85;transform:scale(.985);box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
    .combo-drag-ghost{box-sizing:border-box;border-radius:.75rem;box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
    /* Drag de items dentro de grupos combo */
    .item-row{transition:transform .15s ease,box-shadow .15s ease,opacity .15s ease}
    .item-row.dragging{opacity:.7;background:#f8fafc;box-shadow:0 8px 20px -10px rgba(15,23,42,.3)}
    .combo-item-drag-handle{cursor:grab;touch-action:none}
    .combo-item-drag-handle:active{cursor:grabbing}
    .combo-custom-toggle.is-active{background:#dbeafe;border-color:#2563eb;color:#1d4ed8;font-weight:600}
    .combo-custom-toggle.hidden{display:none}
    .combo-group-customizable{margin:12px 18px;padding:12px 16px;border-radius:12px;border:1px dashed #c7d2fe;background:#eef2ff;color:#3730a3;display:flex;flex-direction:column;gap:6px}
    .combo-group-customizable.hidden{display:none}
    .combo-group-custom-label{font-weight:600}
    .combo-group-custom-help{color:#475569}
    .combo-custom-wrapper.hidden{display:none}
    
    /* Autocomplete de produtos - usando mesmo estilo do ingredientes */
    .product-suggestions{
      max-height: 240px;
      overflow-y: auto;
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 0.5rem;
      box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      z-index: 50;
      margin-top: 2px;
    }
    .product-suggestion-item{
      padding: 0.625rem 0.75rem;
      cursor: pointer;
      border-bottom: 1px solid #f1f5f9;
      transition: background-color 0.15s;
    }
    .product-suggestion-item:hover,
    .product-suggestion-item.highlighted{
      background-color: #f1f5f9;
    }
    .product-suggestion-item:last-child{
      border-bottom: none;
    }
    .product-suggestion-name{
      font-weight: 500;
      color: #1e293b;
      font-size: 0.875rem;
    }
    .product-suggestion-details{
      font-size: 0.75rem;
      color: #64748b;
      margin-top: 2px;
    }
    .product-suggestion-price{
      font-weight: 600;
      color: #059669;
    }
    .price-override-input.is-customized{
      border-color:#3b82f6;
      background-color:#eff6ff;
    }
  </style>

  <!-- CARD: Grupos (Combo) - Só aparece quando tipo = combo -->
  <?php $hasGroups = !empty($groups); ?>
  <fieldset id="combo-groups-card" class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm <?= ($p['type'] ?? 'simple') !== 'combo' ? 'hidden' : '' ?>" aria-labelledby="legend-groups">
    <legend id="legend-groups" class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M6 12h12M6 17h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      Grupos de opções (Combo)
      <a href="<?= e(base_url('admin/' . $slug . '/guide/products')) ?>#combos" target="_blank" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda: Combos">?</a>
    </legend>

    <input type="hidden" id="use_groups_hidden" name="use_groups" value="<?= $hasGroups ? '1' : '0' ?>">

    <label class="mb-2 inline-flex items-center gap-2 text-sm cursor-pointer" id="groups-toggle-label">
      <input type="checkbox" id="groups-toggle" name="use_groups" value="1"
             class="hidden"
             <?= $hasGroups ? 'checked' : '' ?> aria-controls="groups-wrap" aria-expanded="<?= $hasGroups ? 'true' : 'false' ?>">
      <span class="groups-toggle-track w-10 h-6 <?= $hasGroups ? 'admin-primary-bg' : 'bg-slate-300' ?> rounded-full relative transition-colors">
        <span class="groups-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(<?= $hasGroups ? '16px' : '0px' ?>)"></span>
      </span>
      <span class="text-slate-700">Usar grupos de opções (para combos/componentes)</span>
    </label>

    <div id="groups-wrap" class="<?= $hasGroups ? '' : 'hidden' ?>" aria-hidden="<?= $hasGroups ? 'false' : 'true' ?>">
      <?php if (empty($simpleProducts)): ?>
        <div class="mb-2 rounded-lg border border-amber-300 bg-amber-50 p-2 text-sm text-amber-900">
          Nenhum <strong>produto simples</strong> encontrado para esta empresa. Cadastre ao menos um e marque como ativo.
        </div>
      <?php endif; ?>

      <div class="mb-2 rounded-lg bg-slate-50 p-3 text-sm leading-relaxed text-slate-700">
        Cada <em>grupo</em> é uma etapa (ex.: “Lanche”, “Acompanhamento”, “Bebida”). Itens são
        <strong>produtos simples</strong>.
      </div>

      <div id="groups-container" class="grid gap-3">
        <?php if (!empty($groups)): foreach ($groups as $gi => $g): $gi = (int)$gi;
            $gItems = $g['items'] ?? [];
            $min    = (int)($g['min_qty'] ?? $g['min'] ?? 0);
            $max    = (int)($g['max_qty'] ?? $g['max'] ?? 1);
            $sort   = isset($g['sort_order']) ? (int)$g['sort_order'] : $gi;
            $groupHasCustom = false;

            foreach ($gItems as $itCheck) {
                if (!empty($itCheck['customizable']) || !empty($itCheck['allow_customize'])) {
                    $groupHasCustom = true;
                    break;
                }
            }
            ?>
        <div class="group-card rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="<?= $gi ?>" data-custom-group="<?= $groupHasCustom ? '1' : '0' ?>">
          <div class="flex items-center gap-3 border-b border-slate-200 p-3">
<button 
  type="button" 
  draggable="true" 
  class="combo-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar"
>
<svg width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>            <input type="text" name="groups[<?= $gi ?>][name]"
                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
                   placeholder="Nome do grupo" value="<?= e($g['name'] ?? '') ?>" required />
            <input type="hidden" class="combo-order-input" name="groups[<?= $gi ?>][sort_order]" value="<?= $sort ?>">
            <button type="button" class="remove-group shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover grupo">✕</button>
          </div>

          <div class="combo-group-customizable <?= $ptype === 'combo' ? '' : 'hidden' ?>">
            <label class="combo-group-custom-label inline-flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" class="combo-group-custom-switch hidden" <?= $groupHasCustom ? 'checked' : '' ?>>
              <span class="combo-toggle-track w-10 h-6 <?= $groupHasCustom ? 'admin-primary-bg' : 'bg-slate-300' ?> rounded-full relative transition-colors">
                <span class="combo-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(<?= $groupHasCustom ? '16px' : '0px' ?>)"></span>
              </span>
              <span class="text-indigo-700">Grupo personalizável</span>
            </label>
            <p class="combo-group-custom-help mt-1 text-xs text-slate-500">
              Ative para que o cliente personalize o item escolhido deste grupo (quando o produto simples permitir).
            </p>
          </div>

          <?php if (!empty($gItems)): foreach ($gItems as $ii => $it):
              $ii    = (int)$ii;
              $selId = (int)($it['product_id'] ?? $it['simple_id'] ?? $it['simple_product_id'] ?? 0);
              $isDef = !empty($it['is_default'] ?? $it['default']);
              $itemQty = isset($it['default_qty']) ? (int)$it['default_qty'] : ($isDef ? 1 : 0);
              ?>
          <?php
                $spInfo = $simpleMap[(int)$selId] ?? null;
              $canCustom = !empty($spInfo['allow_customize']) && (int)($spInfo['ingredient_count'] ?? 0) > 2;
              $isCustomizable = $canCustom && !empty($it['customizable']);
              ?>
          <div class="item-row flex items-start gap-3 p-3 border-t border-slate-100" data-item-index="<?= $ii ?>" draggable="true">
            <!-- Handle para arrastar -->
            <button type="button" class="combo-item-drag-handle mt-6 p-1 text-slate-400 hover:text-slate-600 shrink-0" title="Arrastar produto">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
              </svg>
            </button>
            <input type="hidden" class="combo-item-order" name="groups[<?= $gi ?>][items][<?= $ii ?>][sort_order]" value="<?= $ii ?>">
            <input type="hidden" class="combo-item-customizable" name="groups[<?= $gi ?>][items][<?= $ii ?>][customizable]" value="<?= $isCustomizable ? '1' : '0' ?>">
            
            <!-- Produto -->
            <div class="product-autocomplete-wrapper flex-1 min-w-0">
              <label class="block text-xs text-slate-500">Produto</label>
              <div class="relative">
                <input type="text" 
                       class="product-search-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                       placeholder="Digite para buscar produto..."
                       autocomplete="off"
                       value="<?= $spInfo ? e($spInfo['name']) : '' ?>"
                       data-selected-id="<?= $selId ?>"
                       data-selected-price="<?= $spInfo ? e((string)($spInfo['price'] ?? '0')) : '0' ?>"
                       data-selected-customize="<?= $spInfo && !empty($spInfo['allow_customize']) ? '1' : '0' ?>"
                       data-selected-ingredients="<?= $spInfo ? (int)($spInfo['ingredient_count'] ?? 0) : 0 ?>">
                <input type="hidden" name="groups[<?= $gi ?>][items][<?= $ii ?>][product_id]" 
                       class="product-id-input" 
                       value="<?= $selId ?>" 
                       required>
                <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">
                  <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div class="product-suggestions absolute top-full left-0 right-0 z-10 hidden max-h-60 overflow-y-auto rounded-lg border border-slate-300 bg-white shadow-lg">
                </div>
              </div>
            </div>
            
            <!-- Mín/Máx -->
            <div class="flex gap-2">
              <div class="w-16">
                <label class="block text-xs text-slate-500">Mín</label>
                <input type="number" min="0" name="groups[<?= $gi ?>][min]" value="<?= $min ?>" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
              </div>
              <div class="w-16">
                <label class="block text-xs text-slate-500">Máx</label>
                <input type="number" min="1" name="groups[<?= $gi ?>][max]" value="<?= $max ?>" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
              </div>
            </div>
            
            <!-- Preço (Override) -->
            <?php 
              // Preço original do produto simples
              $originalPrice = $spInfo ? (float)($spInfo['price'] ?? 0) : 0;
              
              // Preço a exibir no campo:
              // 1. Se price_override existe e não é vazio, usar ele
              // 2. Senão, usar o preço original do produto simples
              $itemPrice = $originalPrice;
              if (isset($it['price_override']) && $it['price_override'] !== null && $it['price_override'] !== '' && (float)$it['price_override'] > 0) {
                  $itemPrice = (float)$it['price_override'];
              }
            ?>
            <div class="w-24">
              <label class="block text-xs text-slate-500">Preço (R$)</label>
              <input type="number" step="0.01" min="0" 
                     name="groups[<?= $gi ?>][items][<?= $ii ?>][price_override]" 
                     value="<?= number_format($itemPrice, 2, '.', '') ?>" 
                     data-original-price="<?= number_format($originalPrice, 2, '.', '') ?>"
                     class="price-override-input w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
            </div>

            <!-- Quantidade Padrão -->
            <div class="w-16">
              <label class="block text-xs text-slate-500">Qtd</label>
              <input type="number" min="0" 
                     name="groups[<?= $gi ?>][items][<?= $ii ?>][default_qty]" 
                     value="<?= $itemQty ?>" 
                     class="default-qty-input w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
            </div>

            <!-- Padrão Sim/Não -->
            <div class="w-16">
              <label class="block text-xs text-slate-500">Padrão</label>
              <input type="hidden" class="combo-default-flag" name="groups[<?= $gi ?>][items][<?= $ii ?>][default]" value="<?= $isDef ? '1' : '0' ?>">
              <button type="button" class="combo-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors <?= $isDef ? 'admin-primary-bg border-transparent text-white' : 'bg-white border-slate-300 text-slate-600 hover:bg-slate-50' ?>" data-active-class="admin-primary-bg border-transparent text-white" data-inactive-class="bg-white border-slate-300 text-slate-600 hover:bg-slate-50">
                <?= $isDef ? 'Sim' : 'Não' ?>
              </button>
            </div>
            
            <!-- Botão Remover -->
            <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600 mt-4" aria-label="Remover item">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
          <?php endforeach;
          else: ?>
          <div class="item-row flex items-start gap-3 p-3 border-t border-slate-100" data-item-index="0" draggable="true">
            <!-- Handle para arrastar -->
            <button type="button" class="combo-item-drag-handle mt-6 p-1 text-slate-400 hover:text-slate-600 shrink-0" title="Arrastar produto">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
              </svg>
            </button>
            <input type="hidden" class="combo-item-order" name="groups[<?= $gi ?>][items][0][sort_order]" value="0">
            <input type="hidden" class="combo-item-customizable" name="groups[<?= $gi ?>][items][0][customizable]" value="0">
            
            <!-- Produto -->
            <div class="product-autocomplete-wrapper flex-1 min-w-0">
              <label class="block text-xs text-slate-500">Produto</label>
              <div class="relative">
                <input type="text" 
                       class="product-search-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                       placeholder="Digite para buscar produto..."
                       autocomplete="off"
                       data-selected-id=""
                       data-selected-price="0"
                       data-selected-customize="0"
                       data-selected-ingredients="0">
                <input type="hidden" name="groups[<?= $gi ?>][items][0][product_id]" 
                       class="product-id-input" 
                       value="" 
                       required>
                <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">
                  <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div class="product-suggestions absolute top-full left-0 right-0 z-10 hidden max-h-60 overflow-y-auto rounded-lg border border-slate-300 bg-white shadow-lg">
                </div>
              </div>
            </div>

            <!-- Mín/Máx -->
            <div class="flex gap-2">
              <div class="w-16">
                <label class="block text-xs text-slate-500">Mín</label>
                <input type="number" min="0" name="groups[<?= $gi ?>][min]" value="<?= $min ?>" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
              </div>
              <div class="w-16">
                <label class="block text-xs text-slate-500">Máx</label>
                <input type="number" min="1" name="groups[<?= $gi ?>][max]" value="<?= $max ?>" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
              </div>
            </div>
            
            <!-- Preço (Override) -->
            <div class="w-24">
              <label class="block text-xs text-slate-500">Preço (R$)</label>
              <input type="number" step="0.01" min="0" 
                     name="groups[<?= $gi ?>][items][0][price_override]" 
                     value="" 
                     data-original-price="0"
                     placeholder="0.00"
                     class="price-override-input w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
            </div>

            <!-- Quantidade Padrão -->
            <div class="w-16">
              <label class="block text-xs text-slate-500">Qtd</label>
              <input type="number" min="0" 
                     name="groups[<?= $gi ?>][items][0][default_qty]" 
                     value="0" 
                     class="default-qty-input w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
            </div>

            <!-- Padrão Sim/Não -->
            <div class="w-16">
              <label class="block text-xs text-slate-500">Padrão</label>
              <input type="hidden" class="combo-default-flag" name="groups[<?= $gi ?>][items][0][default]" value="0">
              <button type="button" class="combo-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors bg-white border-slate-300 text-slate-600 hover:bg-slate-50" data-active-class="admin-primary-bg border-transparent text-white" data-inactive-class="bg-white border-slate-300 text-slate-600 hover:bg-slate-50">
                Não
              </button>
            </div>
            
            <!-- Botão Remover -->
            <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600 mt-4" aria-label="Remover item">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
          <?php endif; ?>

          <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
            <button type="button" class="add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Item</button>
            <div class="group-base-price text-sm text-slate-600">Preço base: R$ 0,00</div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="mt-1">
        <button type="button" id="add-group" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Grupo</button>
      </div>
    </div>
  </fieldset>

  <!-- CARD: Personalização -->
  <fieldset id="customization-card" class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm" aria-labelledby="legend-custom">
    <legend id="legend-custom" class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 8h12M6 12h8M6 16h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      Personalização
      <a href="<?= e(base_url('admin/' . $slug . '/guide/products')) ?>#modes" target="_blank" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda: Personalização">?</a>
    </legend>

    <input type="hidden" id="customization-enabled-hidden" name="customization[enabled]" value="<?= $custEnabled ? '1' : '0' ?>">
    <label class="mb-2 inline-flex items-center gap-2 text-sm cursor-pointer" id="customization-toggle-label">
      <input type="checkbox" id="customization-enabled" name="customization[enabled]" value="1"
             class="hidden" <?= $custEnabled ? 'checked' : '' ?>>
      <span class="customization-toggle-track w-10 h-6 <?= $custEnabled ? 'admin-primary-bg' : 'bg-slate-300' ?> rounded-full relative transition-colors">
        <span class="customization-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(<?= $custEnabled ? '16px' : '0px' ?>)"></span>
      </span>
      <span class="text-slate-700">Permitir personalização de itens</span>
    </label>

    <div id="customization-wrap" class="<?= $custEnabled ? '' : 'hidden' ?>" aria-hidden="<?= $custEnabled ? 'false' : 'true' ?>">
      <div class="mb-2 rounded-lg bg-slate-50 p-3 text-sm leading-relaxed text-slate-700">
        Crie grupos (ex.: <strong>Ingredientes</strong>, <strong>Molhos</strong>) e escolha os ingredientes já cadastrados.
        Ative <strong>Ingrediente padrão</strong> para definir a quantidade exibida ao cliente.
      </div>

      <div id="cust-groups-container" class="grid gap-3">
        <?php if (!empty($custGroups)): foreach ($custGroups as $gi => $cg): $gi = (int)$gi;
            $cgName = $cg['name'] ?? '';
            $cItems = $cg['items'] ?? [[]];
            $gType  = $cg['type'] ?? 'extra';
            $gMode  = in_array($gType, ['single','addon'], true) ? 'choice' : ($gType === 'pool' ? 'pool' : 'extra');
            $gMin   = isset($cg['min']) ? max(0, (int)$cg['min']) : 0;
            $gMax   = isset($cg['max']) ? max($gMin, (int)$cg['max']) : ($gMode === 'extra' ? 99 : max(1, count($cItems)));
            ?>
        <div class="cust-group rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="<?= $gi ?>" data-mode="<?= e($gMode) ?>">
          <div class="flex flex-col gap-3 border-b border-slate-200 p-3">
            <div class="flex items-center gap-3">
              <button 
  type="button" 
  draggable="true" 
                class="cust-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar grupo"
>
<svg width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>
              <input type="text" name="customization[groups][<?= $gi ?>][name]"
                     class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
                     placeholder="Nome do grupo" value="<?= e($cgName) ?>"/>
              <input type="hidden" class="cust-order-input" name="customization[groups][<?= $gi ?>][sort_order]" value="<?= isset($cg['sort_order']) ? (int)$cg['sort_order'] : $gi ?>">
              <button type="button" class="cust-remove-group rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover grupo">✕</button>
            </div>
            <div class="grid items-start gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
              <label class="grid gap-1 text-sm">
                <span class="text-xs text-slate-500">Modo de seleção</span>
                <select name="customization[groups][<?= $gi ?>][mode]" class="cust-mode-select rounded-lg border border-slate-300 bg-white px-3 py-2">
                  <option value="extra" <?= $gMode === 'extra' ? 'selected' : '' ?>>Adicionar ingredientes livremente</option>
                  <option value="choice" <?= $gMode === 'choice' ? 'selected' : '' ?>>Escolher ingrediente</option>
                  <option value="pool" <?= $gMode === 'pool' ? 'selected' : '' ?>>Montagem (açaí, poke...)</option>
                </select>
              </label>
              <div class="cust-choice-settings <?= $gMode === 'choice' ? '' : 'hidden' ?>">
                <div class="grid gap-2 md:grid-cols-2">
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções mínimas</span>
                    <input type="number" class="cust-choice-min rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][<?= $gi ?>][choice][min]" value="<?= $gMode === 'choice' ? $gMin : 0 ?>" min="0" step="1">
                  </label>
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções máximas</span>
                    <input type="number" class="cust-choice-max rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][<?= $gi ?>][choice][max]" value="<?= $gMode === 'choice' ? $gMax : 1 ?>" min="1" step="1">
                  </label>
                </div>
                <p class="mt-1 text-xs text-slate-500">Defina quantas opções o cliente pode marcar.</p>
              </div>
              <div class="cust-pool-settings <?= $gMode === 'pool' ? '' : 'hidden' ?>">
                <div class="grid gap-2 md:grid-cols-2">
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Total mínimo</span>
                    <input type="number" class="cust-pool-min rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][<?= $gi ?>][pool][min]" value="<?= $gMode === 'pool' ? $gMin : 0 ?>" min="0" step="1">
                  </label>
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Total máximo</span>
                    <input type="number" class="cust-pool-max rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][<?= $gi ?>][pool][max]" value="<?= $gMode === 'pool' ? $gMax : 4 ?>" min="1" step="1">
                  </label>
                </div>
                <p class="mt-1 text-xs text-slate-500">Itens inclusos (gratuitos e obrigatórios). Após o máximo, extras serão cobrados.</p>
              </div>
            </div>
          </div>
          <?php foreach ($cItems as $ii => $ci): $ii = (int)$ii;
              $selId = isset($ci['ingredient_id']) ? (int)$ci['ingredient_id'] : 0;
              $def   = !empty($ci['default']);
              $minQ  = isset($ci['min_qty']) ? (int)$ci['min_qty'] : 0;
              $maxQ  = isset($ci['max_qty']) ? (int)$ci['max_qty'] : 1;

              if ($maxQ < $minQ) {
                  $maxQ = $minQ;
              }
              // Sempre usar o valor do banco, independente de ser default ou não
              $defQty = isset($ci['default_qty']) ? (int)$ci['default_qty'] : $minQ;
              $itemOrder = isset($ci['sort_order']) ? (int)$ci['sort_order'] : $ii;
              ?>
          <div class="cust-item border-t border-slate-100 p-4" data-item-index="<?= $ii ?>" draggable="true">
            <div class="flex items-start gap-4">
              <!-- Handle para arrastar -->
              <button type="button" class="cust-item-drag-handle mt-4 p-1 text-slate-400 hover:text-slate-600" title="Arrastar ingrediente">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
              </button>
              <input type="hidden" class="cust-item-order" name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][sort_order]" value="<?= $itemOrder ?>">
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
                           }
                         ?>">
                  <input type="hidden" 
                         name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][ingredient_id]"
                         class="ingredient-id-hidden"
                         value="<?= $selId ?>">
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
                           name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][min_qty]" value="<?= $minQ ?>" min="0" step="1">
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Máx</label>
                    <input type="number" class="cust-max-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                           name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][max_qty]" value="<?= $maxQ ?>" min="0" step="1">
                  </div>
                </div>
              </div>
              
              <!-- Checkbox Ingrediente Padrão -->
              <input type="hidden" class="cust-default-flag" name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][default]" value="<?= $def ? '1' : '0' ?>">
              <div class="cust-default-toggle-wrap">
                <label class="block text-xs font-medium text-slate-500 mb-1">Padrão</label>
                <button type="button" class="cust-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors <?= $def ? 'admin-primary-bg border-transparent text-white' : 'bg-white border-slate-300 text-slate-600 hover:bg-slate-50' ?>">
                  <?= $def ? 'Sim' : 'Não' ?>
                </button>
                <input type="checkbox" class="cust-default-toggle hidden" <?= $def ? 'checked' : '' ?>>
              </div>
              
              <!-- Quantidade Padrão - sempre visível em modo choice -->
              <div class="cust-default-qty-wrap">
                <label class="block text-xs font-medium text-slate-500 mb-1">Qtd padrão</label>
                <input type="number" class="cust-default-qty w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                       name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][default_qty]"
                       value="<?= $defQty ?>" min="0" step="1">
              </div>
              
              <!-- Botão Remover -->
              <button type="button" class="cust-remove-item rounded-full p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 mt-4" title="Remover ingrediente">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
            </div>
          </div>
          <?php endforeach; ?>

          <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
            <button type="button" class="cust-add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Ingrediente</button>
            <button type="button" class="cust-add-choice rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Escolher ingrediente</button>
          </div>
        </div>
        <?php endforeach;
        else: ?>
        <!-- grupo vazio inicial -->
        <div class="cust-group rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="0" data-mode="extra">
          <div class="flex flex-col gap-3 border-b border-slate-200 p-3">
            <div class="flex items-center gap-3">
<button 
  type="button" 
  draggable="true" 
  class="cust-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar grupo"
>
<svg width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>
              <input type="text" name="customization[groups][0][name]"
                     class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
                     placeholder="Nome do grupo" value=""/>
              <input type="hidden" class="cust-order-input" name="customization[groups][0][sort_order]" value="0">
              <button type="button" class="cust-remove-group rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover grupo">✕</button>
            </div>
            <div class="grid items-start gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
              <label class="grid gap-1 text-sm">
                <span class="text-xs text-slate-500">Modo de seleção</span>
                <select name="customization[groups][0][mode]" class="cust-mode-select rounded-lg border border-slate-300 bg-white px-3 py-2">
                  <option value="extra" selected>Adicionar ingredientes livremente</option>
                  <option value="choice">Escolher ingrediente</option>
                  <option value="pool">Montagem (açaí, poke...)</option>
                </select>
              </label>
              <div class="cust-choice-settings hidden">
                <div class="grid gap-2 md:grid-cols-2">
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções mínimas</span>
                    <input type="number" class="cust-choice-min rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][0][choice][min]" value="0" min="0" step="1">
                  </label>
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções máximas</span>
                    <input type="number" class="cust-choice-max rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][0][choice][max]" value="1" min="1" step="1">
                  </label>
                </div>
                <p class="mt-1 text-xs text-slate-500">Defina quantas opções o cliente pode marcar.</p>
              </div>
              <div class="cust-pool-settings hidden">
                <div class="grid gap-2 md:grid-cols-2">
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Total mínimo</span>
                    <input type="number" class="cust-pool-min rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][0][pool][min]" value="0" min="0" step="1">
                  </label>
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Total máximo</span>
                    <input type="number" class="cust-pool-max rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][0][pool][max]" value="4" min="1" step="1">
                  </label>
                </div>
                <p class="mt-1 text-xs text-slate-500">Itens inclusos (gratuitos e obrigatórios). Após o máximo, extras serão cobrados.</p>
              </div>
            </div>
          </div>

          <div class="cust-item border-t border-slate-100 p-4" data-item-index="0" draggable="true">
            <div class="flex items-start gap-4">
              <!-- Handle para arrastar -->
              <button type="button" class="cust-item-drag-handle mt-4 p-1 text-slate-400 hover:text-slate-600" title="Arrastar ingrediente">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
              </button>
              <input type="hidden" class="cust-item-order" name="customization[groups][0][items][0][sort_order]" value="0">
              <!-- Ingrediente -->
              <div class="flex-1 min-w-0">
                <label class="block text-xs font-medium text-slate-500 mb-1">Ingrediente</label>
                <div class="ingredient-typeahead-wrapper">
                  <input type="text"
                         class="ingredient-typeahead-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                         placeholder="Digite para buscar..."
                         autocomplete="off"
                         data-default-min="0" 
                         data-default-max="1">
                  <input type="hidden" 
                         name="customization[groups][0][items][0][ingredient_id]"
                         class="ingredient-id-hidden"
                         value="">
                  <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none ingredient-search-icon">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                      <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>
                  <div class="ingredient-suggestions hidden"></div>
                </div>
              </div>
              
              <!-- Quantidades Min/Max -->
              <div class="cust-limits-wrap flex gap-2">
                <div class="cust-limits flex gap-2" data-min="0" data-max="1">
                  <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Mín</label>
                    <input type="number" class="cust-min-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                           name="customization[groups][0][items][0][min_qty]" value="0" min="0" step="1">
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Máx</label>
                    <input type="number" class="cust-max-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                           name="customization[groups][0][items][0][max_qty]" value="1" min="0" step="1">
                  </div>
                </div>
              </div>
              
              <!-- Checkbox Ingrediente Padrão -->
              <input type="hidden" class="cust-default-flag" name="customization[groups][0][items][0][default]" value="0">
              <div class="cust-default-toggle-wrap">
                <label class="block text-xs font-medium text-slate-500 mb-1">Padrão</label>
                <button type="button" class="cust-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors bg-white border-slate-300 text-slate-600 hover:bg-slate-50" data-active-class="admin-primary-bg border-transparent text-white" data-inactive-class="bg-white border-slate-300 text-slate-600 hover:bg-slate-50">
                  Não
                </button>
                <input type="checkbox" class="cust-default-toggle hidden">
              </div>
              
              <!-- Quantidade Padrão -->
              <div class="cust-default-qty-wrap">
                <label class="block text-xs font-medium text-slate-500 mb-1">Qtd padrão</label>
                <input type="number" class="cust-default-qty w-16 rounded-lg border border-slate-300 px-2 py-2 text-center"
                       name="customization[groups][0][items][0][default_qty]" value="0" min="0" step="1">
              </div>
              
              <!-- Botão Remover -->
              <button type="button" class="cust-remove-item rounded-full p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 mt-4" title="Remover ingrediente">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
            <button type="button" class="cust-add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Ingrediente</button>
            <button type="button" class="cust-add-choice rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Escolher ingrediente</button>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="mt-1 flex gap-2">
        <button type="button" id="cust-add-group" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
          + Novo grupo
        </button>
        <button type="button" id="cust-copy-template" class="flex-1 rounded-lg admin-primary-bg text-white px-3 py-2 text-sm hover:opacity-90 flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Copiar grupo
        </button>
      </div>
    </div>
  </fieldset>

  <!-- CARD: Publicação -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 12h12M12 6v12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      Publicação
      <a href="<?= e(base_url('admin/' . $slug . '/guide/products')) ?>#form" target="_blank" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda: Publicação">?</a>
    </legend>
    <label for="active" class="inline-flex items-center gap-2 cursor-pointer" id="active-toggle-label">
      <input type="checkbox" name="active" id="active" <?= !isset($p['active']) || $p['active'] ? 'checked' : '' ?>
             class="hidden">
      <span class="active-toggle-track w-10 h-6 <?= !isset($p['active']) || $p['active'] ? 'admin-primary-bg' : 'bg-slate-300' ?> rounded-full relative transition-colors">
        <span class="active-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(<?= !isset($p['active']) || $p['active'] ? '16px' : '0px' ?>)"></span>
      </span>
      <span class="text-slate-700">Produto ativo</span>
    </label>
  </fieldset>

  <!-- ===== Templates (Combo) ===== -->
  <template id="tpl-group">
    <div class="group-card rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="__GI__" data-custom-group="0">
      <div class="flex items-center gap-3 border-b border-slate-200 p-3">
<button 
  type="button" 
  draggable="true" 
  class="combo-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar"
>
<svg width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>        <input type="text" name="groups[__GI__][name]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400" placeholder="Nome do grupo" value="" required />
        <input type="hidden" class="combo-order-input" name="groups[__GI__][sort_order]" value="__GI__">
        <button type="button" class="remove-group shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover grupo">✕</button>
      </div>

      <div class="combo-group-customizable <?= $ptype === 'combo' ? '' : 'hidden' ?>">
        <label class="combo-group-custom-label inline-flex items-center gap-2 text-sm cursor-pointer">
          <input type="checkbox" class="combo-group-custom-switch hidden">
          <span class="combo-toggle-track w-10 h-6 bg-slate-300 rounded-full relative transition-colors">
            <span class="combo-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(0px)"></span>
          </span>
          <span class="text-indigo-700">Grupo personalizável</span>
        </label>
        <p class="combo-group-custom-help mt-1 text-xs text-slate-500">
          Ative para liberar personalização dos itens selecionados neste grupo.
        </p>
      </div>

      <div class="item-row flex items-start gap-3 p-3 border-t border-slate-100" data-item-index="0" draggable="true">
        <!-- Handle para arrastar -->
        <button type="button" class="combo-item-drag-handle mt-6 p-1 text-slate-400 hover:text-slate-600 shrink-0" title="Arrastar produto">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
          </svg>
        </button>
        <input type="hidden" class="combo-item-order" name="groups[__GI__][items][0][sort_order]" value="0">
        <input type="hidden" class="combo-item-customizable" name="groups[__GI__][items][0][customizable]" value="0">
        
        <!-- Produto -->
        <div class="product-autocomplete-wrapper flex-1 min-w-0">
          <label class="block text-xs text-slate-500">Produto</label>
          <div class="relative">
            <input type="text" 
                   class="product-search-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                   placeholder="Digite para buscar produto..."
                   autocomplete="off"
                   data-selected-id=""
                   data-selected-price="0"
                   data-selected-customize="0"
                   data-selected-ingredients="0">
            <input type="hidden" name="groups[__GI__][items][0][product_id]" 
                   class="product-id-input" 
                   value="" 
                   required>
            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">
              <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="product-suggestions absolute top-full left-0 right-0 z-10 hidden max-h-60 overflow-y-auto rounded-lg border border-slate-300 bg-white shadow-lg">
            </div>
          </div>
        </div>

        <!-- Mín/Máx -->
        <div class="flex gap-2">
          <div class="w-16">
            <label class="block text-xs text-slate-500">Mín</label>
            <input type="number" min="0" name="groups[__GI__][min]" value="0" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
          </div>
          <div class="w-16">
            <label class="block text-xs text-slate-500">Máx</label>
            <input type="number" min="1" name="groups[__GI__][max]" value="1" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
          </div>
        </div>
        
        <!-- Preço (Override) -->
        <div class="w-24">
          <label class="block text-xs text-slate-500">Preço (R$)</label>
          <input type="number" step="0.01" min="0" 
                 name="groups[__GI__][items][0][price_override]" 
                 value="" 
                 data-original-price="0"
                 placeholder="0.00"
                 class="price-override-input w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
        </div>

        <!-- Quantidade Padrão -->
        <div class="w-16">
          <label class="block text-xs text-slate-500">Qtd</label>
          <input type="number" min="0" 
                 name="groups[__GI__][items][0][default_qty]" 
                 value="0" 
                 class="default-qty-input w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
        </div>

        <!-- Padrão Sim/Não -->
        <div class="w-16">
          <label class="block text-xs text-slate-500">Padrão</label>
          <input type="hidden" class="combo-default-flag" name="groups[__GI__][items][0][default]" value="0">
          <button type="button" class="combo-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors bg-white border-slate-300 text-slate-600 hover:bg-slate-50" data-active-class="admin-primary-bg border-transparent text-white" data-inactive-class="bg-white border-slate-300 text-slate-600 hover:bg-slate-50">
            Não
          </button>
        </div>
        
        <!-- Botão Remover -->
        <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600 mt-4" aria-label="Remover item">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>

      <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
        <button type="button" class="add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Item</button>
        <div class="group-base-price text-sm text-slate-600">Preço base: R$ 0,00</div>
      </div>
    </div>
  </template>

  <template id="tpl-item">
    <div class="item-row flex items-start gap-3 p-3 border-t border-slate-100" data-item-index="__II__" draggable="true">
      <!-- Handle para arrastar -->
      <button type="button" class="combo-item-drag-handle mt-6 p-1 text-slate-400 hover:text-slate-600 shrink-0" title="Arrastar produto">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
        </svg>
      </button>
      <input type="hidden" class="combo-item-order" name="groups[__GI__][items][__II__][sort_order]" value="0">
      <input type="hidden" class="combo-item-customizable" name="groups[__GI__][items][__II__][customizable]" value="0">
      
      <!-- Produto -->
      <div class="product-autocomplete-wrapper flex-1 min-w-0">
        <label class="block text-xs text-slate-500">Produto</label>
        <div class="relative">
          <input type="text" 
                 class="product-search-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                 placeholder="Digite para buscar produto..."
                 autocomplete="off"
                 data-selected-id=""
                 data-selected-price="0"
                 data-selected-customize="0"
                 data-selected-ingredients="0">
          <input type="hidden" name="groups[__GI__][items][__II__][product_id]" 
                 class="product-id-input" 
                 value="" 
                 required>
          <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
              <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="product-suggestions absolute top-full left-0 right-0 z-10 hidden max-h-60 overflow-y-auto rounded-lg border border-slate-300 bg-white shadow-lg">
          </div>
        </div>
      </div>

      <!-- Mín/Máx -->
      <div class="flex gap-2">
        <div class="w-16">
          <label class="block text-xs text-slate-500">Mín</label>
          <input type="number" min="0" name="groups[__GI__][min]" value="0" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
        </div>
        <div class="w-16">
          <label class="block text-xs text-slate-500">Máx</label>
          <input type="number" min="1" name="groups[__GI__][max]" value="1" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
        </div>
      </div>
      
      <!-- Preço (Override) -->
      <div class="w-24">
        <label class="block text-xs text-slate-500">Preço (R$)</label>
        <input type="number" step="0.01" min="0" 
               name="groups[__GI__][items][__II__][price_override]" 
               value="" 
               data-original-price="0"
               placeholder="0.00"
               class="price-override-input w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
      </div>

      <!-- Quantidade Padrão -->
      <div class="w-16">
        <label class="block text-xs text-slate-500">Qtd</label>
        <input type="number" min="0" 
               name="groups[__GI__][items][__II__][default_qty]" 
               value="0" 
               class="default-qty-input w-full rounded-lg border border-slate-300 px-2 py-2 text-center"/>
      </div>

      <!-- Padrão Sim/Não -->
      <div class="w-16">
        <label class="block text-xs text-slate-500">Padrão</label>
        <input type="hidden" class="combo-default-flag" name="groups[__GI__][items][__II__][default]" value="0">
        <button type="button" class="combo-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors bg-white border-slate-300 text-slate-600 hover:bg-slate-50" data-active-class="admin-primary-bg border-transparent text-white" data-inactive-class="bg-white border-slate-300 text-slate-600 hover:bg-slate-50">
          Não
        </button>
      </div>
      
      <!-- Botão Remover -->
      <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600 mt-4" aria-label="Remover item">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>
  </template>

  <!-- ===== Templates (Personalização) ===== -->
  <template id="tpl-cust-group">
    <div class="cust-group rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="__CGI__" data-mode="extra">
      <div class="flex flex-col gap-3 border-b border-slate-200 p-3">
        <div class="flex items-center gap-3">
<button 
  type="button" 
  draggable="true" 
  class="cust-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar"
>
<svg width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>          <input type="text" name="customization[groups][__CGI__][name]"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
                 placeholder="Nome do grupo" value=""/>
          <input type="hidden" class="cust-order-input" name="customization[groups][__CGI__][sort_order]" value="0">
          <button type="button" class="cust-remove-group rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover grupo">✕</button>
        </div>
        <div class="grid items-start gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
          <label class="grid gap-1 text-sm">
            <span class="text-xs text-slate-500">Modo de seleção</span>
            <select name="customization[groups][__CGI__][mode]" class="cust-mode-select rounded-lg border border-slate-300 bg-white px-3 py-2">
              <option value="extra" selected>Adicionar ingredientes livremente</option>
              <option value="choice">Escolher ingrediente</option>
              <option value="pool">Montagem (açaí, poke...)</option>
            </select>
          </label>
          <div class="cust-choice-settings hidden">
            <div class="grid gap-2 md:grid-cols-2">
              <label class="grid gap-1 text-xs text-slate-500">
                <span>Seleções mínimas</span>
                <input type="number" class="cust-choice-min rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][choice][min]" value="0" min="0" step="1">
              </label>
              <label class="grid gap-1 text-xs text-slate-500">
                <span>Seleções máximas</span>
                <input type="number" class="cust-choice-max rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][choice][max]" value="1" min="1" step="1">
              </label>
            </div>
            <p class="mt-1 text-xs text-slate-500">Defina quantas opções o cliente pode marcar.</p>
          </div>
          <div class="cust-pool-settings hidden">
            <div class="grid gap-2 md:grid-cols-2">
              <label class="grid gap-1 text-xs text-slate-500">
                <span>Total mínimo</span>
                <input type="number" class="cust-pool-min rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][pool][min]" value="0" min="0" step="1">
              </label>
              <label class="grid gap-1 text-xs text-slate-500">
                <span>Total máximo</span>
                <input type="number" class="cust-pool-max rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][pool][max]" value="4" min="1" step="1">
              </label>
            </div>
            <p class="mt-1 text-xs text-slate-500">Itens inclusos (gratuitos e obrigatórios). Após o máximo, extras serão cobrados.</p>
          </div>
        </div>
      </div>

      <div class="cust-item border-t border-slate-100 p-4" data-item-index="0">
        <div class="flex items-start gap-4">
          <!-- Ingrediente -->
          <div class="flex-1 min-w-0">
            <label class="block text-xs font-medium text-slate-500 mb-1">Ingrediente</label>
            <div class="ingredient-typeahead-wrapper">
              <input type="text"
                     class="ingredient-typeahead-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                     placeholder="Digite para buscar..."
                     autocomplete="off"
                     data-default-min="0" 
                     data-default-max="1">
              <input type="hidden" 
                     name="customization[groups][__CGI__][items][0][ingredient_id]"
                     class="ingredient-id-hidden"
                     value="">
              <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none ingredient-search-icon">
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                  <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div class="ingredient-suggestions hidden"></div>
            </div>
          </div>
          
          <!-- Quantidades Min/Max -->
          <div class="cust-limits-wrap flex gap-2">
            <div class="cust-limits flex gap-2" data-min="0" data-max="1">
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Mín</label>
                <input type="number" class="cust-min-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center" name="customization[groups][__CGI__][items][0][min_qty]" value="0" min="0" step="1">
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Máx</label>
                <input type="number" class="cust-max-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center" name="customization[groups][__CGI__][items][0][max_qty]" value="1" min="0" step="1">
              </div>
            </div>
          </div>
          
          <!-- Checkbox Ingrediente Padrão -->
          <input type="hidden" class="cust-default-flag" name="customization[groups][__CGI__][items][0][default]" value="0">
          <div class="cust-default-toggle-wrap">
            <label class="block text-xs font-medium text-slate-500 mb-1">Padrão</label>
            <button type="button" class="cust-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors bg-white border-slate-300 text-slate-600 hover:bg-slate-50" data-active-class="admin-primary-bg border-transparent text-white" data-inactive-class="bg-white border-slate-300 text-slate-600 hover:bg-slate-50">
              Não
            </button>
            <input type="checkbox" class="cust-default-toggle hidden">
          </div>
          
          <!-- Quantidade Padrão -->
          <div class="cust-default-qty-wrap">
            <label class="block text-xs font-medium text-slate-500 mb-1">Qtd padrão</label>
            <input type="number" class="cust-default-qty w-16 rounded-lg border border-slate-300 px-2 py-2 text-center" name="customization[groups][__CGI__][items][0][default_qty]" value="0" min="0" step="1">
          </div>
          
          <!-- Botão Remover -->
          <button type="button" class="cust-remove-item rounded-full p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 mt-4" title="Remover ingrediente">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>

      <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
        <button type="button" class="cust-add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Ingrediente</button>
        <button type="button" class="cust-add-choice rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Escolher ingrediente</button>
      </div>
    </div>
  </template>

  <template id="tpl-cust-item">
    <div class="cust-item border-t border-slate-100 p-4" data-item-index="__CII__" draggable="true">
      <div class="flex items-start gap-4">
        <!-- Handle para arrastar -->
        <button type="button" class="cust-item-drag-handle mt-4 p-1 text-slate-400 hover:text-slate-600" title="Arrastar ingrediente">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
          </svg>
        </button>
        <input type="hidden" class="cust-item-order" name="customization[groups][__CGI__][items][__CII__][sort_order]" value="0">
        <!-- Ingrediente -->
        <div class="flex-1 min-w-0">
          <label class="block text-xs font-medium text-slate-500 mb-1">Ingrediente</label>
          <div class="ingredient-typeahead-wrapper">
            <input type="text"
                   class="ingredient-typeahead-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                   placeholder="Digite para buscar..."
                   autocomplete="off"
                   data-default-min="0" 
                   data-default-max="1">
            <input type="hidden" 
                   name="customization[groups][__CGI__][items][__CII__][ingredient_id]"
                   class="ingredient-id-hidden"
                   value="">
            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none ingredient-search-icon">
              <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="ingredient-suggestions hidden"></div>
          </div>
        </div>
        
        <!-- Quantidades Min/Max -->
        <div class="cust-limits-wrap flex gap-2">
          <div class="cust-limits flex gap-2" data-min="0" data-max="1">
            <div>
              <label class="block text-xs font-medium text-slate-500 mb-1">Mín</label>
              <input type="number" class="cust-min-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center" name="customization[groups][__CGI__][items][__CII__][min_qty]" value="0" min="0" step="1">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-500 mb-1">Máx</label>
              <input type="number" class="cust-max-input w-16 rounded-lg border border-slate-300 px-2 py-2 text-center" name="customization[groups][__CGI__][items][__CII__][max_qty]" value="1" min="0" step="1">
            </div>
          </div>
        </div>
        
        <!-- Checkbox Ingrediente Padrão -->
        <input type="hidden" class="cust-default-flag" name="customization[groups][__CGI__][items][__CII__][default]" value="0">
        <div class="cust-default-toggle-wrap">
          <label class="block text-xs font-medium text-slate-500 mb-1">Padrão</label>
          <button type="button" class="cust-default-btn w-16 h-[42px] rounded-lg border text-sm font-medium transition-colors bg-white border-slate-300 text-slate-600 hover:bg-slate-50" data-active-class="admin-primary-bg border-transparent text-white" data-inactive-class="bg-white border-slate-300 text-slate-600 hover:bg-slate-50">
            Não
          </button>
          <input type="checkbox" class="cust-default-toggle hidden">
        </div>
        
        <!-- Quantidade Padrão -->
        <div class="cust-default-qty-wrap">
          <label class="block text-xs font-medium text-slate-500 mb-1">Qtd padrão</label>
          <input type="number" class="cust-default-qty w-16 rounded-lg border border-slate-300 px-2 py-2 text-center" name="customization[groups][__CGI__][items][__CII__][default_qty]" value="0" min="0" step="1">
        </div>
        
        <!-- Botão Remover -->
        <button type="button" class="cust-remove-item rounded-full p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 mt-4" title="Remover ingrediente">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>
    </div>
  </template>

  <!-- script externalizado: admin-products.js -->
  <script src="<?= base_url('assets/js/admin-products.js') ?>?v=<?= time() ?>"></script>
  
  <!-- Script para detectar mudanças não salvas -->
  <script>
  (function() {
    const form = document.getElementById('product-form');
    if (!form) return;
    
    let formChanged = false;
    let formSubmitted = false;
    
    // Função do beforeunload
    function handleBeforeUnload(e) {
      if (formChanged && !formSubmitted) {
        const confirmationMessage = 'Você tem alterações não salvas. Deseja realmente sair?';
        e.preventDefault();
        e.returnValue = confirmationMessage;
        return confirmationMessage;
      }
    }
    
    // Registrar o listener
    window.addEventListener('beforeunload', handleBeforeUnload);
    
    // Interceptar o método submit() do formulário
    const originalSubmit = form.submit.bind(form);
    form.submit = function() {
      formSubmitted = true;
      window.removeEventListener('beforeunload', handleBeforeUnload);
      return originalSubmit();
    };
    
    // Detectar mudanças em qualquer input
    form.addEventListener('input', function() {
      formChanged = true;
    });
    
    form.addEventListener('change', function() {
      formChanged = true;
    });
    
    // Detectar cliques em botões que modificam o formulário
    form.addEventListener('click', function(e) {
      const target = e.target;
      
      if (target.matches('button[type="button"]') || 
          target.closest('button[type="button"]') ||
          target.matches('.cust-add-item, .cust-add-group, .cust-remove-item, .cust-remove-group') ||
          target.closest('.cust-add-item, .cust-add-group, .cust-remove-item, .cust-remove-group')) {
        formChanged = true;
      }
    });
    
    // Também capturar evento submit normal (caso seja disparado por enter ou outro meio)
    form.addEventListener('submit', function() {
      formSubmitted = true;
      window.removeEventListener('beforeunload', handleBeforeUnload);
    });
  })();
  </script>
</form>

<!-- Modal: Copiar Grupo de Personalização -->
<div id="copy-template-modal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="copy-template-backdrop"></div>
  <div class="fixed inset-0 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col animate-[fadeInUp_0.2s_ease-out]">
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-slate-200">
        <div>
          <h3 class="text-lg font-semibold text-slate-900">Copiar Grupo de Personalização</h3>
          <p class="text-sm text-slate-500">Selecione os grupos que deseja adicionar</p>
        </div>
        <button type="button" id="close-copy-template-modal" class="rounded-lg p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>
      
      <!-- Search -->
      <div class="p-4 border-b border-slate-100">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <input type="text" id="copy-template-search" placeholder="Buscar grupos..." 
                 class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
        </div>
      </div>
      
      <!-- Lista de Templates -->
      <div class="flex-1 overflow-y-auto p-4" id="copy-template-list">
        <div class="flex items-center justify-center py-8 text-slate-400">
          <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          <span class="ml-2">Carregando...</span>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="p-4 border-t border-slate-200 flex items-center justify-between">
        <span id="copy-template-selected" class="text-sm text-slate-500">0 selecionado(s)</span>
        <div class="flex gap-2">
          <button type="button" id="cancel-copy-template" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">
            Cancelar
          </button>
          <button type="button" id="confirm-copy-template" class="px-4 py-2 text-sm font-medium text-white admin-primary-bg rounded-lg hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            Adicionar
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.template-item.selected { background-color: #f3e8ff; border-color: var(--admin-primary-color); }
.template-item.selected .template-checkbox { background-color: var(--admin-primary-color); border-color: var(--admin-primary-color); }
.template-item.selected .template-checkbox svg { display: block; }
</style>

<script>
(function() {
  const modal = document.getElementById('copy-template-modal');
  const backdrop = document.getElementById('copy-template-backdrop');
  const closeBtn = document.getElementById('close-copy-template-modal');
  const cancelBtn = document.getElementById('cancel-copy-template');
  const confirmBtn = document.getElementById('confirm-copy-template');
  const searchInput = document.getElementById('copy-template-search');
  const listContainer = document.getElementById('copy-template-list');
  const selectedCount = document.getElementById('copy-template-selected');
  const openBtn = document.getElementById('cust-copy-template');
  
  // Dados dos templates carregados diretamente do PHP
  const allTemplates = <?= $custTemplatesJson ?>;
  
  let filteredTemplates = [...allTemplates];
  let selectedTemplates = new Set();
  
  // Abrir modal
  openBtn?.addEventListener('click', () => {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    filterTemplates('');
  });
  
  // Fechar modal
  function closeModal() {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    selectedTemplates.clear();
    updateSelectedCount();
  }
  
  backdrop?.addEventListener('click', closeModal);
  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  
  // Filtrar templates por busca
  function filterTemplates(search = '') {
    search = search.toLowerCase().trim();
    if (search) {
      filteredTemplates = allTemplates.filter(t => 
        t.name.toLowerCase().includes(search)
      );
    } else {
      filteredTemplates = [...allTemplates];
    }
    renderTemplates();
  }
  
  // Renderizar lista
  function renderTemplates() {
    if (!filteredTemplates.length) {
      listContainer.innerHTML = `
        <div class="text-center py-8">
          <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" stroke-linecap="round" stroke-linejoin="round"/><polyline points="3.27 6.96 12 12.01 20.73 6.96" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="22.08" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <p class="text-slate-500">Nenhum grupo encontrado</p>
          <p class="text-sm text-slate-400 mt-1">Crie grupos em "Grupos de Personalização" no menu</p>
        </div>
      `;
      return;
    }
    
    listContainer.innerHTML = filteredTemplates.map(tpl => `
      <div class="template-item flex items-center gap-3 p-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50 transition mb-2 ${selectedTemplates.has(String(tpl.id)) ? 'selected' : ''}" 
           data-id="${tpl.id}">
        <div class="template-checkbox w-5 h-5 rounded border-2 border-slate-300 flex items-center justify-center flex-shrink-0">
          <svg class="w-3 h-3 text-white hidden" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        </div>
        <div class="flex-1 min-w-0">
          <div class="font-medium text-slate-900 truncate">${escapeHtml(tpl.name)}</div>
          <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
            <span class="inline-flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>
              ${(tpl.items && tpl.items.length) || 0} itens
            </span>
            <span class="inline-flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" stroke-linecap="round" stroke-linejoin="round"/><line x1="7" y1="7" x2="7.01" y2="7" stroke-linecap="round" stroke-linejoin="round"/></svg>
              ${tpl.type === 'extra' ? 'Adicional' : tpl.type === 'pool' ? 'Montagem' : tpl.type === 'substitute' ? 'Substituição' : 'Escolha'}
            </span>
          </div>
        </div>
      </div>
    `).join('');
    
    // Bind click
    listContainer.querySelectorAll('.template-item').forEach(item => {
      item.addEventListener('click', () => {
        const id = item.dataset.id;
        if (selectedTemplates.has(id)) {
          selectedTemplates.delete(id);
          item.classList.remove('selected');
        } else {
          selectedTemplates.add(id);
          item.classList.add('selected');
        }
        updateSelectedCount();
      });
    });
  }
  
  function updateSelectedCount() {
    const count = selectedTemplates.size;
    selectedCount.textContent = `${count} selecionado(s)`;
    confirmBtn.disabled = count === 0;
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Busca com debounce
  let searchTimeout;
  searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      filterTemplates(searchInput.value.trim());
    }, 300);
  });
  
  // Confirmar seleção
  confirmBtn?.addEventListener('click', () => {
    if (!selectedTemplates.size) return;
    
    // Adicionar cada template selecionado
    let added = 0;
    for (const templateId of selectedTemplates) {
      // Buscar template nos dados locais
      const template = allTemplates.find(t => String(t.id) === String(templateId));
      if (template) {
        addTemplateAsGroup(template);
        added++;
      }
    }
    
    closeModal();
    
    // Mostrar toast de sucesso
    if (window.showToast && added > 0) {
      window.showToast(`${added} grupo(s) adicionado(s)!`, 'success');
    }
    
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = 'Adicionar';
  });
  
  // Adicionar template como grupo no formulário
  function addTemplateAsGroup(template) {
    // Usa a função existente do admin-products.js
    if (typeof window.addCustGroup === 'function') {
      // Cria o grupo
      const newGroup = window.addCustGroup();
      
      if (newGroup) {
        // Preencher nome - input que tem name com [name]
        const nameInput = newGroup.querySelector('input[name*="[name]"]');
        if (nameInput) nameInput.value = template.name || '';
        
        // Preencher tipo/modo
        const modeSelect = newGroup.querySelector('.cust-mode-select');
        if (modeSelect) {
          // Converter type do template para mode
          const mode = (template.type === 'single' || template.type === 'addon') ? 'choice' : (template.type === 'pool' ? 'pool' : 'extra');
          modeSelect.value = mode;
          
          // Disparar evento de change para atualizar UI
          modeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        // Preencher quantidades de seleção conforme o modo
        if (template.type === 'pool') {
          const poolMinInput = newGroup.querySelector('.cust-pool-min');
          const poolMaxInput = newGroup.querySelector('.cust-pool-max');
          if (poolMinInput) poolMinInput.value = template.min_qty ?? 0;
          if (poolMaxInput) poolMaxInput.value = template.max_qty ?? 4;
        } else {
          const minInput = newGroup.querySelector('.cust-choice-min');
          const maxInput = newGroup.querySelector('.cust-choice-max');
          if (minInput) minInput.value = template.min_qty ?? 0;
          if (maxInput) maxInput.value = template.max_qty ?? 99;
        }
        
        // Remover o item vazio inicial que foi criado automaticamente
        const emptyItems = newGroup.querySelectorAll('.cust-item');
        emptyItems.forEach(item => {
          const ingInput = item.querySelector('input[name*="[ingredient_id]"]');
          if (!ingInput || !ingInput.value) {
            item.remove();
          }
        });
        
        // Adicionar itens do template
        if (template.items && template.items.length > 0 && typeof window.addCustItemWithData === 'function') {
          template.items.forEach((item) => {
            window.addCustItemWithData(newGroup, item);
          });
        }
        
        // Aplicar modo correto
        if (typeof window.applyCustMode === 'function') {
          window.applyCustMode(newGroup);
        }
      }
    } else {
      console.error('Função addCustGroup não encontrada');
    }
  }
})();
</script>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
