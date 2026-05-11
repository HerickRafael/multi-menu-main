<?php
/**
 * Edição de Custos do Produto
 * Estilo consistente com Analytics
 */

$title = 'Custos: ' . ($product['name'] ?? 'Produto') . ' - ' . ($company['name'] ?? '');
ob_start();

// Use data from controller
$ingredientsCost = $ingredientCost ?? $costBreakdown['ingredient_cost'] ?? 0;
$packagingCost = $packagingCostFromLinks ?? $additionalCosts['packaging_cost'] ?? $costBreakdown['packaging_cost'] ?? 0;
$totalCost = $ingredientsCost + $packagingCost;
$price = $product['price'] ?? 0;
$margin = $price > 0 ? (($price - $totalCost) / $price) * 100 : 0;

// Configuração do header padronizado
$pageTitle = e($product['name'] ?? 'Produto');
$pageDescription = 'Configure os custos deste produto';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Custos de Produtos', 'url' => base_url('admin/' . $activeSlug . '/product-costs')],
    ['label' => e($product['name'] ?? 'Produto')]
];

// Preparar conteúdo extra com preço de venda
ob_start();
?>
<div class="text-right">
  <p class="text-sm text-slate-500">Preço de Venda</p>
  <p class="text-xl font-bold text-slate-900">R$ <?= number_format($price, 2, ',', '.') ?></p>
</div>
<?php
$extraHeaderContent = ob_get_clean();
$actions = [];
?>

<div class="mx-auto max-w-4xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- Resumo de Custos -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <p class="text-sm text-slate-500 mb-1">Ingredientes</p>
    <p class="text-xl font-bold text-slate-900">R$ <?= number_format($ingredientsCost, 2, ',', '.') ?></p>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <p class="text-sm text-slate-500 mb-1">Embalagens</p>
    <p class="text-xl font-bold text-slate-900">R$ <?= number_format($packagingCost, 2, ',', '.') ?></p>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <p class="text-sm text-slate-500 mb-1">Custo Total</p>
    <p class="text-xl font-bold text-purple-600">R$ <?= number_format($totalCost, 2, ',', '.') ?></p>
  </div>
  <div class="rounded-2xl border <?= $margin >= 30 ? 'border-emerald-200 bg-emerald-50' : ($margin >= 20 ? 'border-amber-200 bg-amber-50' : 'border-red-200 bg-red-50') ?> p-4 shadow-sm">
    <p class="text-sm <?= $margin >= 30 ? 'text-emerald-600' : ($margin >= 20 ? 'text-amber-600' : 'text-red-600') ?> mb-1">Margem</p>
    <p class="text-xl font-bold <?= $margin >= 30 ? 'text-emerald-700' : ($margin >= 20 ? 'text-amber-700' : 'text-red-700') ?>"><?= number_format($margin, 1, ',', '.') ?>%</p>
  </div>
</div>

<!-- Ingredientes -->
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm mb-6">
  <div class="mb-4 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Ingredientes</h2>
    </div>
    <span class="text-sm text-slate-500">Calculado automaticamente pela receita</span>
  </div>
  
  <?php if (empty($ingredients)): ?>
  <div class="text-center py-8 text-slate-500">
    <svg class="h-12 w-12 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <p>Nenhum ingrediente cadastrado na receita</p>
    <a href="<?= base_url('admin/' . $activeSlug . '/products/' . $product['id'] . '/edit') ?>" class="text-purple-600 hover:underline text-sm">Editar produto</a>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left font-medium text-slate-700">Ingrediente</th>
          <th class="px-4 py-3 text-center font-medium text-slate-700">Qtd</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Custo Unit.</th>
          <th class="px-4 py-3 text-right font-medium text-slate-700">Total</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($ingredients as $ing): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3 text-slate-900"><?= e($ing['name']) ?></td>
          <td class="px-4 py-3 text-center text-slate-600"><?= number_format((float)$ing['quantity'], 2, ',', '.') ?> <?= e($ing['unit'] ?? '') ?></td>
          <td class="px-4 py-3 text-right text-slate-600">R$ <?= number_format((float)($ing['unit_cost'] ?? 0), 2, ',', '.') ?></td>
          <td class="px-4 py-3 text-right font-medium text-slate-900">R$ <?= number_format((float)($ing['total_cost'] ?? 0), 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="bg-slate-50 font-medium">
        <tr>
          <td colspan="3" class="px-4 py-3 text-right text-slate-700">Total Ingredientes:</td>
          <td class="px-4 py-3 text-right text-slate-900">R$ <?= number_format($ingredientsCost, 2, ',', '.') ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($singleChoiceVariations)): 
// Calcular custo mínimo e máximo considerando variações
$minDelta = 0;
$maxDelta = 0;
foreach ($singleChoiceVariations as $group) {
    $groupDeltas = array_column($group['items'], 'cost_delta');
    $minDelta += min($groupDeltas);
    $maxDelta += max($groupDeltas);
}
$minCost = $totalCost + $minDelta;
$maxCost = $totalCost + $maxDelta;
$minMargin = $price > 0 ? (($price - $minCost) / $price) * 100 : 0;
$maxMargin = $price > 0 ? (($price - $maxCost) / $price) * 100 : 0;
?>
<!-- Variações de Custo por Escolha -->
<div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm mb-6">
  <div class="mb-4 flex items-center gap-2">
    <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path d="M8 9l4-4 4 4m0 6l-4 4-4-4" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <h2 class="text-lg font-semibold text-amber-900">Variações de Custo por Escolha</h2>
  </div>
  <p class="text-sm text-amber-700 mb-4">O custo pode variar dependendo da escolha do cliente</p>
  
  <?php foreach ($singleChoiceVariations as $group): ?>
  <div class="bg-white rounded-xl p-4 mb-3 border border-amber-100">
    <h3 class="font-medium text-slate-900 mb-3"><?= e($group['name']) ?></h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
      <?php foreach ($group['items'] as $item): ?>
      <div class="flex items-center justify-between p-3 rounded-lg <?= $item['is_default'] ? 'bg-purple-50 border border-purple-200' : 'bg-slate-50 border border-slate-100' ?>">
        <span class="text-sm <?= $item['is_default'] ? 'font-medium text-purple-700' : 'text-slate-600' ?>">
          <?= e($item['name']) ?>
          <?php if ($item['is_default']): ?><span class="text-xs">(padrão)</span><?php endif; ?>
        </span>
        <span class="text-sm font-medium <?= $item['cost_delta'] > 0 ? 'text-red-600' : ($item['cost_delta'] < 0 ? 'text-emerald-600' : 'text-slate-500') ?>">
          <?php if ($item['cost_delta'] > 0): ?>
            +R$ <?= number_format($item['cost_delta'], 2, ',', '.') ?>
          <?php elseif ($item['cost_delta'] < 0): ?>
            -R$ <?= number_format(abs($item['cost_delta']), 2, ',', '.') ?>
          <?php else: ?>
            R$ <?= number_format($item['cost'], 2, ',', '.') ?>
          <?php endif; ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
  
  <!-- Resumo Custo Mín/Máx e Margens -->
  <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="p-3 bg-white rounded-lg border border-amber-200 text-center">
      <p class="text-xs text-slate-500 mb-1">Custo Mínimo</p>
      <p class="text-lg font-bold text-emerald-600">R$ <?= number_format($minCost, 2, ',', '.') ?></p>
    </div>
    <div class="p-3 bg-white rounded-lg border border-amber-200 text-center">
      <p class="text-xs text-slate-500 mb-1">Custo Máximo</p>
      <p class="text-lg font-bold text-red-600">R$ <?= number_format($maxCost, 2, ',', '.') ?></p>
    </div>
    <div class="p-3 <?= $maxMargin >= 20 ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' ?> rounded-lg border text-center">
      <p class="text-xs <?= $maxMargin >= 20 ? 'text-emerald-600' : 'text-red-600' ?> mb-1">Margem Mínima</p>
      <p class="text-lg font-bold <?= $maxMargin >= 20 ? 'text-emerald-700' : 'text-red-700' ?>"><?= number_format($maxMargin, 1, ',', '.') ?>%</p>
    </div>
    <div class="p-3 <?= $minMargin >= 30 ? 'bg-emerald-50 border-emerald-200' : ($minMargin >= 20 ? 'bg-amber-50 border-amber-200' : 'bg-red-50 border-red-200') ?> rounded-lg border text-center">
      <p class="text-xs <?= $minMargin >= 30 ? 'text-emerald-600' : ($minMargin >= 20 ? 'text-amber-600' : 'text-red-600') ?> mb-1">Margem Máxima</p>
      <p class="text-lg font-bold <?= $minMargin >= 30 ? 'text-emerald-700' : ($minMargin >= 20 ? 'text-amber-700' : 'text-red-700') ?>"><?= number_format($minMargin, 1, ',', '.') ?>%</p>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Custos Adicionais -->
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm mb-6">
  <div class="mb-4 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Custos Adicionais</h2>
    </div>
    <!-- Indicador de salvamento automático -->
    <div id="save-indicator" class="hidden items-center gap-2 text-sm">
      <span id="save-spinner" class="hidden">
        <svg class="h-4 w-4 animate-spin text-purple-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </span>
      <span id="save-text" class="text-slate-500">Salvando...</span>
    </div>
  </div>
  
  <!-- Embalagens Vinculadas -->
  <div class="mb-6">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h3 class="font-medium text-slate-900">Embalagens</h3>
      </div>
      <a href="<?= base_url('admin/' . $activeSlug . '/packaging') ?>" class="text-xs text-purple-600 hover:underline">
        Gerenciar insumos →
      </a>
    </div>
    
    <div id="packaging-list" class="space-y-2">
      <?php if (!empty($productPackaging)): ?>
        <?php foreach ($productPackaging as $index => $pkg): ?>
        <div class="packaging-row flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200" data-supply-id="<?= e($pkg['supply_id']) ?>">
          <select class="packaging-select flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            <option value="">Selecione uma embalagem...</option>
            <?php foreach ($availablePackaging as $supply): ?>
            <option value="<?= e($supply['id']) ?>" 
                    data-cost="<?= e($supply['cost_per_unit']) ?>"
                    data-unit="<?= e($supply['unit']) ?>"
                    <?= (int)$pkg['supply_id'] === (int)$supply['id'] ? 'selected' : '' ?>>
              <?= e($supply['name']) ?> (R$ <?= number_format((float)$supply['cost_per_unit'], 2, ',', '.') ?>)
            </option>
            <?php endforeach; ?>
          </select>
          <div class="flex items-center gap-2">
            <input type="number" 
                   value="<?= e((int)$pkg['quantity']) ?>" 
                   step="1" min="1" 
                   placeholder="Qtd"
                   class="packaging-qty w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm text-center focus:ring-2 focus:ring-purple-500">
            <span class="text-sm text-slate-500 unit-label"><?= e($pkg['unit'] ?? 'un') ?></span>
          </div>
          <span class="text-sm font-medium text-purple-600 item-cost w-24 text-right">
            R$ <?= number_format((float)$pkg['total_cost'], 2, ',', '.') ?>
          </span>
          <button type="button" class="remove-packaging-btn p-1.5 text-slate-400 hover:text-red-500 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    
    <button type="button" id="add-packaging-btn"
            class="mt-3 inline-flex items-center gap-1.5 text-sm text-purple-600 hover:text-purple-700 font-medium">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
      </svg>
      Adicionar Embalagem
    </button>
    
    <div class="mt-3 pt-3 border-t border-slate-200 flex items-center justify-between">
      <span class="text-sm text-slate-600">Total de Embalagens:</span>
      <span id="packaging-total" class="text-lg font-bold text-purple-600">R$ <?= number_format($packagingCost, 2, ',', '.') ?></span>
    </div>
  </div>
</div>

<script>
const productId = <?= (int)$product['id'] ?>;
const baseUrl = '<?= base_url('admin/' . $activeSlug) ?>';
const availablePackaging = <?= json_encode($availablePackaging ?? []) ?>;
let saveTimeout = null;

// Mostrar indicador de salvamento
function showSaveIndicator(status) {
  const indicator = document.getElementById('save-indicator');
  const spinner = document.getElementById('save-spinner');
  const text = document.getElementById('save-text');
  
  indicator.classList.remove('hidden');
  indicator.classList.add('flex');
  
  if (status === 'saving') {
    spinner.classList.remove('hidden');
    text.textContent = 'Salvando...';
    text.className = 'text-slate-500';
  } else if (status === 'saved') {
    spinner.classList.add('hidden');
    text.innerHTML = '<svg class="h-4 w-4 inline text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg> Salvo';
    text.className = 'text-emerald-600 font-medium';
    setTimeout(() => {
      indicator.classList.add('hidden');
      indicator.classList.remove('flex');
    }, 2000);
  } else if (status === 'error') {
    spinner.classList.add('hidden');
    text.innerHTML = '<svg class="h-4 w-4 inline text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg> Erro ao salvar';
    text.className = 'text-red-600 font-medium';
  }
}

// Salvar automaticamente
async function autoSave() {
  const rows = document.querySelectorAll('.packaging-row');
  const packaging = [];
  
  rows.forEach(row => {
    const select = row.querySelector('.packaging-select');
    const qty = row.querySelector('.packaging-qty');
    if (select.value) {
      packaging.push({
        supply_id: parseInt(select.value),
        quantity: parseInt(qty.value) || 1
      });
    }
  });
  
  showSaveIndicator('saving');
  
  try {
    const response = await fetch(baseUrl + '/product-costs/' + productId + '/update-packaging', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ packaging })
    });
    
    const data = await response.json();
    
    if (data.success) {
      showSaveIndicator('saved');
      // Atualizar o resumo de custos
      if (data.packaging_cost !== undefined) {
        updateCostSummary(data.packaging_cost);
      }
    } else {
      showSaveIndicator('error');
    }
  } catch (e) {
    console.error('Erro ao salvar:', e);
    showSaveIndicator('error');
  }
}

// Atualizar resumo de custos
function updateCostSummary(packagingCost) {
  const ingredientsCost = <?= (float)$ingredientsCost ?>;
  const price = <?= (float)$price ?>;
  const totalCost = ingredientsCost + packagingCost;
  const margin = price > 0 ? ((price - totalCost) / price) * 100 : 0;
  
  // Atualizar cards de resumo
  document.querySelector('.grid.grid-cols-2 > div:nth-child(2) p:last-child').textContent = 
    'R$ ' + packagingCost.toFixed(2).replace('.', ',');
  document.querySelector('.grid.grid-cols-2 > div:nth-child(3) p:last-child').textContent = 
    'R$ ' + totalCost.toFixed(2).replace('.', ',');
  
  // Atualizar margem
  const marginCard = document.querySelector('.grid.grid-cols-2 > div:nth-child(4)');
  const marginP = marginCard.querySelectorAll('p');
  marginP[1].textContent = margin.toFixed(1).replace('.', ',') + '%';
  
  // Atualizar cores do card de margem
  marginCard.className = 'rounded-2xl border p-4 shadow-sm ';
  if (margin >= 30) {
    marginCard.classList.add('border-emerald-200', 'bg-emerald-50');
    marginP[0].className = 'text-sm text-emerald-600 mb-1';
    marginP[1].className = 'text-xl font-bold text-emerald-700';
  } else if (margin >= 20) {
    marginCard.classList.add('border-amber-200', 'bg-amber-50');
    marginP[0].className = 'text-sm text-amber-600 mb-1';
    marginP[1].className = 'text-xl font-bold text-amber-700';
  } else {
    marginCard.classList.add('border-red-200', 'bg-red-50');
    marginP[0].className = 'text-sm text-red-600 mb-1';
    marginP[1].className = 'text-xl font-bold text-red-700';
  }
}

// Agendar salvamento com debounce
function scheduleSave() {
  if (saveTimeout) clearTimeout(saveTimeout);
  saveTimeout = setTimeout(autoSave, 500);
}

// Atualizar custo do item e total
function updatePackagingCost(row) {
  const select = row.querySelector('.packaging-select');
  const input = row.querySelector('.packaging-qty');
  const costLabel = row.querySelector('.item-cost');
  const unitLabel = row.querySelector('.unit-label');
  
  const option = select.selectedOptions[0];
  const cost = parseFloat(option?.dataset?.cost || 0);
  const unit = option?.dataset?.unit || 'un';
  const qty = parseInt(input.value) || 1;
  
  unitLabel.textContent = unit;
  const total = cost * qty;
  costLabel.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
  
  updateTotalPackaging();
  scheduleSave();
}

function updateTotalPackaging() {
  let total = 0;
  document.querySelectorAll('.packaging-row').forEach(row => {
    const select = row.querySelector('.packaging-select');
    const input = row.querySelector('.packaging-qty');
    const option = select.selectedOptions[0];
    const cost = parseFloat(option?.dataset?.cost || 0);
    const qty = parseInt(input.value) || 1;
    total += cost * qty;
  });
  document.getElementById('packaging-total').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
}

// Adicionar nova linha de embalagem
function addPackagingRow() {
  const list = document.getElementById('packaging-list');
  const optionsHtml = availablePackaging.map(s => 
    `<option value="${s.id}" data-cost="${s.cost_per_unit}" data-unit="${s.unit}">
      ${s.name} (R$ ${parseFloat(s.cost_per_unit).toFixed(2).replace('.', ',')})
    </option>`
  ).join('');
  
  const row = document.createElement('div');
  row.className = 'packaging-row flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200 border-purple-300';
  row.innerHTML = `
    <select class="packaging-select flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
      <option value="">Selecione uma embalagem...</option>
      ${optionsHtml}
    </select>
    <div class="flex items-center gap-2">
      <input type="number" value="1" step="1" min="1" placeholder="Qtd"
             class="packaging-qty w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm text-center focus:ring-2 focus:ring-purple-500">
      <span class="text-sm text-slate-500 unit-label">un</span>
    </div>
    <span class="text-sm font-medium text-purple-600 item-cost w-24 text-right">R$ 0,00</span>
    <button type="button" class="remove-packaging-btn p-1.5 text-slate-400 hover:text-red-500 transition">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  `;
  list.appendChild(row);
  
  // Inicializar eventos
  initRowEvents(row);
  
  // Highlight animation
  setTimeout(() => {
    row.classList.remove('border-purple-300');
  }, 1000);
}

// Remover linha de embalagem
function removePackagingRow(row) {
  row.style.opacity = '0.5';
  row.style.transform = 'translateX(10px)';
  setTimeout(() => {
    row.remove();
    updateTotalPackaging();
    scheduleSave();
  }, 200);
}

// Inicializar eventos para uma linha
function initRowEvents(row) {
  row.querySelector('.packaging-select').addEventListener('change', () => updatePackagingCost(row));
  row.querySelector('.packaging-qty').addEventListener('input', () => updatePackagingCost(row));
  row.querySelector('.remove-packaging-btn').addEventListener('click', () => removePackagingRow(row));
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
  // Eventos para linhas existentes
  document.querySelectorAll('.packaging-row').forEach(initRowEvents);
  
  // Botão adicionar
  document.getElementById('add-packaging-btn').addEventListener('click', addPackagingRow);
});
</script>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
