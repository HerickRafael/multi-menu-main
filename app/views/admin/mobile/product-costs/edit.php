<?php
/**
 * Product Cost Edit - Mobile
 */
$activeNav = 'settings';

$productId = (int)$product['id'];
$salePrice = (float)$product['price'];
$ingCost = (float)($ingredientCost ?? 0);
$pkgCost = (float)($packagingCostFromLinks ?? 0);
$totalCost = (float)($costBreakdown['total_cost'] ?? 0);
$profit = $salePrice - $totalCost;
$margin = $salePrice > 0 ? ($profit / $salePrice) * 100 : 0;

$marginColor = $margin >= 30 ? '#16a34a' : ($margin >= 20 ? '#d97706' : '#dc2626');
$marginBg = $margin >= 30 ? '#f0fdf4' : ($margin >= 20 ? '#fffbeb' : '#fef2f2');

$showSuccess = isset($_GET['success']);

ob_start();
?>

<style>
.pce-page { padding: 1rem; padding-bottom: 6rem; }
.pce-back { display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-secondary, #64748b); text-decoration: none; font-size: 0.875rem; margin-bottom: 0.75rem; }
.pce-title { font-size: 1.125rem; font-weight: 700; color: var(--text-primary, #1e293b); margin-bottom: 0.25rem; }
.pce-subtitle { font-size: 0.8125rem; color: var(--text-secondary, #64748b); margin-bottom: 1rem; }

/* Save indicator */
.pce-save-indicator { display: none; position: fixed; top: 1rem; left: 50%; transform: translateX(-50%); z-index: 9999; padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 500; background: #dbeafe; color: #1d4ed8; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

/* Cost summary */
.pce-summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 1.25rem; }
.pce-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.75rem; }
.pce-card-label { font-size: 0.6875rem; color: var(--text-secondary, #64748b); margin-bottom: 0.25rem; }
.pce-card-value { font-size: 1.125rem; font-weight: 700; }

/* Section */
.pce-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; margin-bottom: 0.75rem; }
.pce-section-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9375rem; font-weight: 600; color: var(--text-primary, #1e293b); margin-bottom: 0.75rem; }
.pce-section-title svg { width: 1.125rem; height: 1.125rem; color: var(--text-secondary, #64748b); }

/* Ingredients table */
.pce-ing-row { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; }
.pce-ing-row:last-child { border-bottom: none; }
.pce-ing-name { font-size: 0.8125rem; font-weight: 500; color: var(--text-primary, #1e293b); flex: 1; }
.pce-ing-detail { font-size: 0.75rem; color: var(--text-secondary, #64748b); }
.pce-ing-cost { font-size: 0.8125rem; font-weight: 600; color: var(--text-primary, #1e293b); text-align: right; min-width: 4rem; }
.pce-empty { text-align: center; padding: 1.5rem 0; color: var(--text-secondary, #64748b); font-size: 0.8125rem; }

/* Packaging row */
.pce-pkg-row { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.625rem; }
.pce-pkg-select { flex: 1; padding: 0.5rem 0.625rem; border: 1px solid #e2e8f0; border-radius: 0.625rem; font-size: 0.8125rem; background: #fff; }
.pce-pkg-qty { width: 3.5rem; padding: 0.5rem 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.625rem; font-size: 0.8125rem; text-align: center; }
.pce-pkg-cost { font-size: 0.8125rem; font-weight: 500; color: var(--text-primary, #1e293b); min-width: 3.5rem; text-align: right; }
.pce-pkg-remove { width: 1.75rem; height: 1.75rem; display: flex; align-items: center; justify-content: center; border: none; background: #fee2e2; border-radius: 0.5rem; color: #dc2626; cursor: pointer; flex-shrink: 0; }

.pce-add-pkg { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border: 1px dashed #cbd5e1; border-radius: 0.625rem; background: none; cursor: pointer; width: 100%; font-size: 0.8125rem; color: var(--primary, #4361ee); justify-content: center; }

/* Variation */
.pce-var-group { margin-bottom: 0.75rem; }
.pce-var-group-name { font-size: 0.8125rem; font-weight: 600; color: var(--text-secondary, #64748b); margin-bottom: 0.375rem; }
.pce-var-item { display: flex; justify-content: space-between; align-items: center; padding: 0.375rem 0; font-size: 0.8125rem; }
.pce-var-delta { font-weight: 500; }
.pce-var-default { font-size: 0.6875rem; background: #dbeafe; color: #1e40af; padding: 0.125rem 0.375rem; border-radius: 9999px; margin-left: 0.375rem; }

/* Toast */
.pce-toast { display: none; position: fixed; top: 1rem; left: 50%; transform: translateX(-50%); z-index: 9999; padding: 0.625rem 1.25rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15); background: #dcfce7; color: #166534; }
</style>

<div class="pce-page">
    <a href="/product-costs" class="pce-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="12" x2="5" y2="12" stroke-linecap="round" stroke-linejoin="round"/><polyline points="12 19 5 12 12 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Custos de Produtos
    </a>

    <h1 class="pce-title"><?= htmlspecialchars($product['name']) ?></h1>
    <p class="pce-subtitle">Preço de venda: R$ <?= number_format($salePrice, 2, ',', '.') ?></p>

    <!-- Save indicator -->
    <div id="saveIndicator" class="pce-save-indicator">Salvando...</div>

    <!-- Cost summary cards -->
    <div class="pce-summary">
        <div class="pce-card">
            <div class="pce-card-label">Ingredientes</div>
            <div id="sumIngredients" class="pce-card-value" style="color: #64748b;">R$ <?= number_format($ingCost, 2, ',', '.') ?></div>
        </div>
        <div class="pce-card">
            <div class="pce-card-label">Embalagem</div>
            <div id="sumPackaging" class="pce-card-value" style="color: #64748b;">R$ <?= number_format($pkgCost, 2, ',', '.') ?></div>
        </div>
        <div class="pce-card" style="border-color: #e2e8f0;">
            <div class="pce-card-label">Custo Total</div>
            <div id="sumTotal" class="pce-card-value" style="color: var(--text-primary, #1e293b);">R$ <?= number_format($totalCost, 2, ',', '.') ?></div>
        </div>
        <div class="pce-card" style="background: <?= $marginBg ?>; border-color: <?= $marginColor ?>33;">
            <div class="pce-card-label">Margem de Lucro</div>
            <div id="sumMargin" class="pce-card-value" style="color: <?= $marginColor ?>;"><?= number_format($margin, 1, ',', '.') ?>%</div>
        </div>
    </div>

    <!-- Ingredients (read-only) -->
    <div class="pce-section">
        <div class="pce-section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Ingredientes da Receita
        </div>
        <?php if (empty($ingredients)): ?>
            <div class="pce-empty">Nenhum ingrediente cadastrado para este produto.</div>
        <?php else: ?>
            <?php foreach ($ingredients as $ing): ?>
            <div class="pce-ing-row">
                <div>
                    <div class="pce-ing-name"><?= htmlspecialchars($ing['name']) ?></div>
                    <div class="pce-ing-detail"><?= number_format($ing['quantity'], 2, ',', '.') ?> <?= htmlspecialchars($ing['unit']) ?> × R$ <?= number_format($ing['unit_cost'], 2, ',', '.') ?></div>
                </div>
                <div class="pce-ing-cost">R$ <?= number_format($ing['total_cost'], 2, ',', '.') ?></div>
            </div>
            <?php endforeach; ?>
            <div class="pce-ing-row" style="border-top: 2px solid #e2e8f0; margin-top: 0.25rem; padding-top: 0.625rem; border-bottom: none;">
                <div class="pce-ing-name" style="font-weight: 700;">Total Ingredientes</div>
                <div class="pce-ing-cost" style="font-weight: 700;">R$ <?= number_format($ingCost, 2, ',', '.') ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Single choice variations -->
    <?php if (!empty($singleChoiceVariations)): ?>
    <div class="pce-section">
        <div class="pce-section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Variações de Escolha Única
        </div>
        <?php foreach ($singleChoiceVariations as $group): ?>
        <div class="pce-var-group">
            <div class="pce-var-group-name"><?= htmlspecialchars($group['name'] ?? 'Grupo') ?></div>
            <?php foreach (($group['items'] ?? []) as $item): ?>
            <div class="pce-var-item">
                <span>
                    <?= htmlspecialchars($item['name'] ?? '') ?>
                    <?php if (!empty($item['is_default'])): ?>
                        <span class="pce-var-default">Padrão</span>
                    <?php endif; ?>
                </span>
                <span class="pce-var-delta" style="color: <?= ($item['cost_delta'] ?? 0) > 0 ? '#dc2626' : '#16a34a' ?>;">
                    <?= ($item['cost_delta'] ?? 0) >= 0 ? '+' : '' ?>R$ <?= number_format($item['cost_delta'] ?? 0, 2, ',', '.') ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Packaging management -->
    <div class="pce-section">
        <div class="pce-section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Embalagens
        </div>

        <div id="packagingContainer">
            <?php if (empty($productPackaging)): ?>
                <!-- Start with one empty row -->
            <?php else: ?>
                <?php foreach ($productPackaging as $i => $pkg): ?>
                <div class="pce-pkg-row" data-row="<?= $i ?>">
                    <select class="pce-pkg-select pkg-select" onchange="onPackagingChange()">
                        <option value="">Selecione...</option>
                        <?php foreach ($availablePackaging as $ap): ?>
                            <option value="<?= $ap['id'] ?>" data-cost="<?= $ap['cost_per_unit'] ?>" data-unit="<?= htmlspecialchars($ap['unit'] ?? 'un') ?>" <?= (int)$ap['id'] === (int)$pkg['supply_id'] ? 'selected' : '' ?>><?= htmlspecialchars($ap['name']) ?> (R$ <?= number_format($ap['cost_per_unit'], 2, ',', '.') ?>/<?= htmlspecialchars($ap['unit'] ?? 'un') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" class="pce-pkg-qty pkg-qty" min="1" step="1" value="<?= (int)$pkg['quantity'] ?>" onchange="onPackagingChange()" oninput="onPackagingChange()">
                    <span class="pce-pkg-cost pkg-row-cost">R$ <?= number_format($pkg['total_cost'] ?? 0, 2, ',', '.') ?></span>
                    <button type="button" class="pce-pkg-remove" onclick="removePackagingRow(this)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" class="pce-add-pkg" onclick="addPackagingRow()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Adicionar Embalagem
        </button>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="pce-toast"></div>

<script>
var productId = <?= $productId ?>;
var salePrice = <?= $salePrice ?>;
var ingredientCost = <?= $ingCost ?>;
var availablePackaging = <?= json_encode($availablePackaging ?: []) ?>;

var saveTimeout = null;

function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    t.style.color = type === 'success' ? '#166534' : '#991b1b';
    t.style.display = 'block';
    setTimeout(function() { t.style.display = 'none'; }, 2500);
}

<?php if ($showSuccess): ?>
setTimeout(function() { showToast('Custos salvos com sucesso!', 'success'); }, 300);
<?php endif; ?>

function addPackagingRow() {
    var container = document.getElementById('packagingContainer');
    var idx = container.querySelectorAll('.pce-pkg-row').length;
    var div = document.createElement('div');
    div.className = 'pce-pkg-row';
    div.setAttribute('data-row', idx);

    var options = '<option value="">Selecione...</option>';
    for (var i = 0; i < availablePackaging.length; i++) {
        var ap = availablePackaging[i];
        var unit = ap.unit || 'un';
        var costStr = parseFloat(ap.cost_per_unit).toFixed(2).replace('.', ',');
        options += '<option value="' + ap.id + '" data-cost="' + ap.cost_per_unit + '" data-unit="' + unit + '">' + ap.name + ' (R$ ' + costStr + '/' + unit + ')</option>';
    }

    div.innerHTML = '<select class="pce-pkg-select pkg-select" onchange="onPackagingChange()">' + options + '</select>' +
        '<input type="number" class="pce-pkg-qty pkg-qty" min="1" step="1" value="1" onchange="onPackagingChange()" oninput="onPackagingChange()">' +
        '<span class="pce-pkg-cost pkg-row-cost">R$ 0,00</span>' +
        '<button type="button" class="pce-pkg-remove" onclick="removePackagingRow(this)">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
        '</button>';

    container.appendChild(div);
}

function removePackagingRow(btn) {
    btn.closest('.pce-pkg-row').remove();
    onPackagingChange();
}

function onPackagingChange() {
    // Update row costs
    var totalPkg = 0;
    document.querySelectorAll('.pce-pkg-row').forEach(function(row) {
        var sel = row.querySelector('.pkg-select');
        var qtyInput = row.querySelector('.pkg-qty');
        var costSpan = row.querySelector('.pkg-row-cost');
        var opt = sel.options[sel.selectedIndex];
        var cost = parseFloat(opt.getAttribute('data-cost')) || 0;
        var qty = parseInt(qtyInput.value) || 0;
        var rowCost = cost * qty;
        costSpan.textContent = 'R$ ' + rowCost.toFixed(2).replace('.', ',');
        if (sel.value) totalPkg += rowCost;
    });

    updateCostSummary(totalPkg);
    debounceSave();
}

function updateCostSummary(pkgCost) {
    var total = ingredientCost + pkgCost;
    var profit = salePrice - total;
    var margin = salePrice > 0 ? (profit / salePrice) * 100 : 0;

    document.getElementById('sumPackaging').textContent = 'R$ ' + pkgCost.toFixed(2).replace('.', ',');
    document.getElementById('sumTotal').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');

    var marginEl = document.getElementById('sumMargin');
    marginEl.textContent = margin.toFixed(1).replace('.', ',') + '%';

    var color, bg;
    if (margin >= 30) { color = '#16a34a'; bg = '#f0fdf4'; }
    else if (margin >= 20) { color = '#d97706'; bg = '#fffbeb'; }
    else { color = '#dc2626'; bg = '#fef2f2'; }
    marginEl.style.color = color;
    marginEl.closest('.pce-card').style.background = bg;
    marginEl.closest('.pce-card').style.borderColor = color + '33';
}

function debounceSave() {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(autoSave, 500);
}

function autoSave() {
    var rows = document.querySelectorAll('.pce-pkg-row');
    var packaging = [];
    rows.forEach(function(row) {
        var sel = row.querySelector('.pkg-select');
        var qty = parseInt(row.querySelector('.pkg-qty').value) || 0;
        if (sel.value && qty > 0) {
            packaging.push({ supply_id: parseInt(sel.value), quantity: qty });
        }
    });

    var indicator = document.getElementById('saveIndicator');
    indicator.style.display = 'block';

    fetch('/product-costs/' + productId + '/update-packaging', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ packaging: packaging })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        indicator.style.display = 'none';
        if (data.success) {
            showToast('Salvo', 'success');
        } else {
            showToast(data.message || 'Erro ao salvar', 'error');
        }
    })
    .catch(function(err) {
        indicator.style.display = 'none';
        showToast('Erro: ' + err.message, 'error');
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
