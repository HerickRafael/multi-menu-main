<?php
// admin/ingredients/form.php — Formulário de ingrediente (versão moderna com toolbar fixa)

$title   = 'Ingrediente - ' . ($company['name'] ?? '');
$editing = !empty($ingredient['id']);
$slug    = rawurlencode((string)($company['slug'] ?? ''));
$action  = $editing
  ? "admin/{$slug}/ingredients/" . (int)($ingredient['id'] ?? 0)
  : "admin/{$slug}/ingredients";

$image = $ingredient['image_path'] ?? null;

$unitOptions = [
  ['value' => 'un', 'label' => 'Unidade (un)'],
  ['value' => 'kg', 'label' => 'Quilo (kg)'],
  ['value' => 'g',  'label' => 'Grama (g)'],
  ['value' => 'mg', 'label' => 'Miligrama (mg)'],
  ['value' => 'l',  'label' => 'Litro (L)'],
  ['value' => 'ml', 'label' => 'Mililitro (mL)'],
  ['value' => 'pc', 'label' => 'Peça (pc)'],
];

$unitLabelMap = ['un' => 'unidade','kg' => 'kg','g' => 'g','mg' => 'mg','l' => 'litro','ml' => 'mililitro','pc' => 'peça'];

$unitRaw = trim((string)($ingredient['unit'] ?? ''));
$unitSelectValue = '';

foreach ($unitOptions as $opt) {
    if (strcasecmp($unitRaw, $opt['value']) === 0) {
        $unitSelectValue = $opt['value'];
        break;
    }
}
$unitCustomValue = '';

if ($unitSelectValue === '') {
    if ($unitRaw !== '') {
        $unitSelectValue = 'custom';
        $unitCustomValue = $unitRaw;
    }
}

$unitLabelDisplay = $unitSelectValue === 'custom'
  ? ($unitCustomValue !== '' ? $unitCustomValue : 'unidade')
  : ($unitLabelMap[$unitSelectValue] ?? ($unitSelectValue !== '' ? $unitSelectValue : 'unidade'));
$unitLabelDisplay = $unitLabelDisplay !== '' ? $unitLabelDisplay : 'unidade';
$unitValuePlaceholder = trim('Ex.: 1 ' . $unitLabelDisplay);

$costVal = $ingredient['cost'] ?? '';
// Sempre formatar para brasileiro se for numérico
if ($costVal !== '' && $costVal !== null) {
    $costFloat = is_numeric($costVal) ? (float)$costVal : (float)str_replace(['.', ','], ['', '.'], $costVal);
    $costVal = number_format($costFloat, 2, ',', '.');
}

$saleVal = $ingredient['sale_price'] ?? '';
// Sempre formatar para brasileiro se for numérico
if ($saleVal !== '' && $saleVal !== null) {
    $saleFloat = is_numeric($saleVal) ? (float)$saleVal : (float)str_replace(['.', ','], ['', '.'], $saleVal);
    $saleVal = number_format($saleFloat, 2, ',', '.');
}

$unitValueVal = $ingredient['unit_value'] ?? '';
// Sempre formatar para brasileiro se for numérico
if ($unitValueVal !== '' && $unitValueVal !== null) {
    $unitFloat = is_numeric($unitValueVal) ? (float)$unitValueVal : (float)str_replace(['.', ','], ['', '.'], $unitValueVal);
    $unitValueVal = rtrim(rtrim(number_format($unitFloat, 3, ',', '.'), '0'), ',');
    // Garantir pelo menos uma casa decimal
    if (strpos($unitValueVal, ',') === false) {
        $unitValueVal .= ',0';
    }
}

// Configuração do header padronizado
$pageTitle = ($editing ? 'Editar' : 'Novo') . ' Ingrediente';
$pageDescription = $editing ? 'Altere os dados do ingrediente' : 'Cadastre um novo ingrediente';
$pageIcon = $editing 
    ? '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    : '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Ingredientes', 'url' => base_url("admin/{$slug}/ingredients")],
    ['label' => $editing ? 'Editar' : 'Novo']
];
$actions = [
    ['label' => 'Salvar', 'onclick' => "document.querySelector('form').submit()", 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'primary' => true]
];
$activeSlug = $slug;

ob_start(); ?>

<div class="mx-auto max-w-4xl p-4 space-y-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- ALERTA DE ERRO -->
<?php if (!empty($error)): ?>
  <div class="rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data"
      action="<?= e(base_url($action)) ?>"
      class="relative grid gap-6 rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">

  <!-- CSRF / METHOD -->
  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>
  <?php if ($editing): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

  <!-- CARD: Dados do ingrediente -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 8h12M6 12h8M6 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Dados do ingrediente
      <a href="<?= e(base_url('admin/' . $slug . '/guide/ingredients')) ?>#form" target="_blank" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda: Dados do ingrediente">?</a>
    </legend>

    <label class="grid gap-1 mb-3">
      <span class="text-sm text-slate-700">Nome <span class="text-red-500">*</span></span>
      <input name="name" value="<?= e($ingredient['name'] ?? '') ?>" required autocomplete="off"
             class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
    </label>

    <label class="grid gap-1 mb-3">
      <span class="text-sm text-slate-700">Nomenclatura interna <span class="text-slate-400 text-xs">(opcional)</span></span>
      <input name="internal_name" value="<?= e($ingredient['internal_name'] ?? '') ?>" autocomplete="off"
             placeholder="Ex.: Big Fries, Porção G, 180g..."
             class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
      <span class="text-xs text-slate-500">Complemento visível apenas no painel admin. Ex.: "Batata frita (Big Fries)"</span>
    </label>

    <div class="grid gap-3 md:grid-cols-2">
      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Custo <span class="text-red-500">*</span> <a href="/admin/<?= $slug ?>/guide/ingredients#pricing" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
        <input type="text" name="cost" value="<?= e($costVal) ?>" inputmode="decimal" placeholder="Ex.: 3,50" required
               class="money-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      </label>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Valor de venda <span class="text-red-500">*</span></span>
        <input type="text" name="sale_price" value="<?= e($saleVal) ?>" inputmode="decimal" placeholder="Ex.: 5,90" required
               class="money-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      </label>
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-2">
      <div class="grid gap-1">
        <span class="text-sm text-slate-700">Unidade de medida <span class="text-red-500">*</span> <a href="/admin/<?= $slug ?>/guide/ingredients#units" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
        <div class="grid gap-2">
          <select name="unit_select" id="unit_select"
                  class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400" required>
            <option value="">Selecione</option>
            <?php foreach ($unitOptions as $opt): ?>
              <option value="<?= e($opt['value']) ?>" <?= $unitSelectValue === $opt['value'] ? 'selected' : '' ?>>
                <?= e($opt['label']) ?>
              </option>
            <?php endforeach; ?>
            <option value="custom" <?= $unitSelectValue === 'custom' ? 'selected' : '' ?>>Outra unidade…</option>
          </select>
          <input type="text" name="unit_custom" id="unit_custom" value="<?= e($unitCustomValue) ?>"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2 <?= $unitSelectValue === 'custom' ? '' : 'hidden' ?>"
                 placeholder="Informe a unidade" maxlength="30">
        </div>
      </div>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Valor por <span id="unit_label" data-unit-label><?= e($unitLabelDisplay) ?></span> <span class="text-red-500">*</span></span>
        <input type="text" name="unit_value" id="unit_value" value="<?= e($unitValueVal) ?>" inputmode="decimal"
               placeholder="<?= e($unitValuePlaceholder) ?>" required
               class="decimal-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      </label>
    </div>
  </fieldset>

  <!-- CARD: Imagem -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Imagem (opcional)
      <a href="<?= e(base_url('admin/' . $slug . '/guide/ingredients')) ?>#form" target="_blank" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda: Imagem">?</a>
    </legend>

    <div class="grid items-start gap-3 md:grid-cols-[1fr_auto]">
      <div class="grid gap-2">
        <label for="image" class="text-sm text-slate-700">Upload (jpg/png/webp)</label>
        <label class="inline-flex w-fit cursor-pointer items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
          <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp" class="hidden">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Selecionar arquivo
        </label>
        <small class="text-xs text-slate-500">Recomendado: 800×800px quadrado. Máx. 5 MB.</small>
      </div>

      <div class="flex flex-col items-center gap-2">
        <?php if (!empty($image)): ?>
          <span class="text-xs text-slate-500">Pré-visualização</span>
          <img id="image-preview-img"
               src="<?= e(base_url($image)) ?>"
               class="h-20 w-20 rounded-xl border border-slate-200 object-cover shadow-sm"
               alt="Pré-visualização"
               onerror="this.style.display='none'; document.getElementById('image-preview-placeholder').style.display='flex';">
        <?php else: ?>
          <img id="image-preview-img" class="h-20 w-20 rounded-xl border border-slate-200 object-cover shadow-sm hidden" alt="Pré-visualização">
        <?php endif; ?>
        <div id="image-preview-placeholder" class="<?= !empty($image) ? 'hidden' : 'flex' ?> h-20 w-20 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 shadow-sm">
          <svg class="h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none">
            <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
    </div>

  </fieldset>

  <script>
  // Preview de imagem
  const imageInput = document.getElementById('image');
  const imagePreviewImg = document.getElementById('image-preview-img');
  const imagePreviewPlaceholder = document.getElementById('image-preview-placeholder');
  
  if (imageInput) {
    imageInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(event) {
          // Atualizar a imagem
          imagePreviewImg.src = event.target.result;
          imagePreviewImg.style.display = 'block';
          imagePreviewImg.classList.remove('hidden');
          
          // Adicionar label "Pré-visualização" se não existir
          const container = imagePreviewImg.parentElement;
          let label = container.querySelector('.text-xs.text-slate-500');
          if (!label) {
            label = document.createElement('span');
            label.className = 'text-xs text-slate-500';
            label.textContent = 'Pré-visualização';
            container.insertBefore(label, imagePreviewImg);
          }
          
          // Ocultar placeholder
          imagePreviewPlaceholder.style.display = 'none';
          imagePreviewPlaceholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);
      }
    });
  }
  </script>

</div>

<?php
$content = ob_get_clean();

// Provide unit label map for admin.js to read (data attribute on the form wrapper)
// We'll print it into a wrapping div so script can find it via dataset.
// The form has id 'ingredientForm' — we add data-unit-label-map to document body near content.
// (the admin.js will attempt to read the map from an element with id 'ingredientForm').

// To ensure the JSON is available for the JS initializer, include it in a small inline data element.
?>
<script type="application/json" id="unit-label-map-json" data-target="ingredientForm"><?= json_encode($unitLabelMap, JSON_UNESCAPED_UNICODE) ?></script>

<?php include __DIR__ . '/../layout.php';

// Ensure the form wrapper is properly closed for templates that expect a trailing </form>
// (Some fragments were missing the explicit closing tag.)
echo "\n";
