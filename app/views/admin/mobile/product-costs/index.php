<?php
/**
 * Product Costs Index - Mobile
 */
$activeNav = 'settings';

$allProducts = $productsCosts ?? [];
$totalProducts = count($allProducts);

// Metrics
$highMargin = 0;
$medMargin = 0;
$lowMargin = 0;
$noIngredient = 0;
foreach ($allProducts as $p) {
    $m = $p['profit_margin'] ?? 0;
    if ($m >= 30) $highMargin++;
    elseif ($m >= 20) $medMargin++;
    else $lowMargin++;
    if (($p['ingredient_cost'] ?? 0) <= 0) $noIngredient++;
}

ob_start();
?>

<style>
.pc-page { padding: 1rem; padding-bottom: 6rem; }
.pc-back { display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-secondary, #64748b); text-decoration: none; font-size: 0.875rem; margin-bottom: 0.75rem; }
.pc-title { font-size: 1.125rem; font-weight: 700; color: var(--text-primary, #1e293b); margin-bottom: 1rem; }

/* Metrics */
.pc-metrics { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 1rem; }
.pc-metric { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.75rem; }
.pc-metric-label { font-size: 0.6875rem; color: var(--text-secondary, #64748b); margin-bottom: 0.25rem; }
.pc-metric-value { font-size: 1.25rem; font-weight: 700; }

/* Search */
.pc-search { width: 100%; padding: 0.625rem 0.875rem 0.625rem 2.5rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") 0.75rem center no-repeat; margin-bottom: 0.75rem; }
.pc-search:focus { outline: none; border-color: var(--primary, #4361ee); }

/* Bulk button */
.pc-bulk-btn { display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.625rem; border-radius: 0.75rem; background: #fff; border: 1px solid #e2e8f0; font-size: 0.8125rem; font-weight: 500; color: var(--text-primary, #1e293b); cursor: pointer; margin-bottom: 1rem; }
.pc-bulk-btn svg { width: 1rem; height: 1rem; }

/* Product card */
.pc-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 0.875rem; margin-bottom: 0.625rem; text-decoration: none; display: block; color: inherit; }
.pc-card-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.5rem; }
.pc-card-name { font-size: 0.875rem; font-weight: 600; color: var(--text-primary, #1e293b); flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pc-card-warn { color: #f59e0b; margin-right: 0.375rem; flex-shrink: 0; }
.pc-margin-badge { display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 600; flex-shrink: 0; }
.pc-margin-high { background: #dcfce7; color: #166534; }
.pc-margin-med { background: #fef3c7; color: #92400e; }
.pc-margin-low { background: #fee2e2; color: #991b1b; }

.pc-card-mid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.5rem; }
.pc-card-stat { text-align: center; }
.pc-card-stat-label { font-size: 0.625rem; color: var(--text-secondary, #64748b); text-transform: uppercase; }
.pc-card-stat-value { font-size: 0.8125rem; font-weight: 600; color: var(--text-primary, #1e293b); }

.pc-card-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 0.375rem; border-top: 1px solid #f1f5f9; }
.pc-card-profit { font-size: 0.875rem; font-weight: 700; }
.pc-card-profit.positive { color: #16a34a; }
.pc-card-profit.negative { color: #dc2626; }
.pc-card-edit { display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; color: var(--primary, #4361ee); font-weight: 500; }

/* Bulk modal */
.pc-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: flex-end; }
.pc-modal-overlay.show { display: flex; }
.pc-modal { width: 100%; background: #fff; border-radius: 1rem 1rem 0 0; padding: 1.5rem; max-height: 80vh; overflow-y: auto; }
.pc-modal h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem; }
.pc-modal p { font-size: 0.8125rem; color: var(--text-secondary, #64748b); margin-bottom: 1rem; }
.pc-modal-field { margin-bottom: 1rem; }
.pc-modal-field label { display: block; font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.375rem; }
.pc-modal-field input { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; }
.pc-modal-actions { display: flex; gap: 0.75rem; margin-top: 1.25rem; }
.pc-modal-btn { flex: 1; padding: 0.625rem; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; }
.pc-modal-btn.cancel { background: #f1f5f9; color: var(--text-primary, #1e293b); }
.pc-modal-btn.apply { background: var(--primary, #4361ee); color: #fff; }
</style>

<div class="pc-page">
    <a href="/settings" class="pc-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Configurações
    </a>

    <h1 class="pc-title">Custos de Produtos</h1>

    <!-- Metrics -->
    <div class="pc-metrics">
        <div class="pc-metric">
            <div class="pc-metric-label">Total Produtos</div>
            <div class="pc-metric-value" style="color: var(--text-primary, #1e293b);"><?= $totalProducts ?></div>
        </div>
        <div class="pc-metric" style="border-color: #bbf7d0;">
            <div class="pc-metric-label" style="color: #16a34a;">Margem Alta (≥30%)</div>
            <div class="pc-metric-value" style="color: #16a34a;"><?= $highMargin ?></div>
        </div>
        <div class="pc-metric" style="border-color: #fde68a;">
            <div class="pc-metric-label" style="color: #d97706;">Margem Média (20-30%)</div>
            <div class="pc-metric-value" style="color: #d97706;"><?= $medMargin ?></div>
        </div>
        <div class="pc-metric" style="border-color: #fecaca;">
            <div class="pc-metric-label" style="color: #dc2626;">Margem Baixa (&lt;20%)</div>
            <div class="pc-metric-value" style="color: #dc2626;"><?= $lowMargin ?></div>
        </div>
    </div>

    <!-- Search -->
    <input type="text" id="searchProduct" class="pc-search" placeholder="Buscar produto...">

    <!-- Bulk -->
    <button type="button" class="pc-bulk-btn" onclick="showBulkModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Aplicar Custos em Lote
    </button>

    <!-- Products -->
    <?php if (empty($allProducts)): ?>
        <div style="text-align:center; padding:3rem 1rem; color:var(--text-secondary, #64748b);">
            <p>Nenhum produto encontrado</p>
        </div>
    <?php else: ?>
        <div id="productList">
        <?php foreach ($allProducts as $product): ?>
            <?php
            $pm = $product['profit_margin'] ?? 0;
            $marginClass = $pm >= 30 ? 'pc-margin-high' : ($pm >= 20 ? 'pc-margin-med' : 'pc-margin-low');
            $hasWarning = ($product['ingredient_cost'] ?? 0) <= 0;
            ?>
            <a href="/product-costs/<?= $product['product_id'] ?>/edit" class="pc-card product-row" data-name="<?= strtolower(htmlspecialchars($product['name'])) ?>">
                <div class="pc-card-top">
                    <div style="display:flex;align-items:center;flex:1;min-width:0;">
                        <?php if ($hasWarning): ?>
                            <svg class="pc-card-warn" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="17" x2="12.01" y2="17" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?php endif; ?>
                        <span class="pc-card-name"><?= htmlspecialchars($product['name']) ?></span>
                    </div>
                    <span class="pc-margin-badge <?= $marginClass ?>"><?= number_format($pm, 1, ',', '.') ?>%</span>
                </div>
                <div class="pc-card-mid">
                    <div class="pc-card-stat">
                        <div class="pc-card-stat-label">Preço</div>
                        <div class="pc-card-stat-value">R$ <?= number_format($product['sale_price'], 2, ',', '.') ?></div>
                    </div>
                    <div class="pc-card-stat">
                        <div class="pc-card-stat-label">Custo</div>
                        <div class="pc-card-stat-value">R$ <?= number_format($product['total_cost'], 2, ',', '.') ?></div>
                    </div>
                    <div class="pc-card-stat">
                        <div class="pc-card-stat-label">Ingredientes</div>
                        <div class="pc-card-stat-value" style="<?= $hasWarning ? 'color:#d97706;' : '' ?>">R$ <?= number_format($product['ingredient_cost'], 2, ',', '.') ?></div>
                    </div>
                </div>
                <div class="pc-card-bottom">
                    <span class="pc-card-profit <?= $product['profit'] >= 0 ? 'positive' : 'negative' ?>">
                        Lucro: R$ <?= number_format($product['profit'], 2, ',', '.') ?>
                    </span>
                    <span class="pc-card-edit">
                        Editar
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Bulk Modal -->
<div id="bulkModal" class="pc-modal-overlay" onclick="if(event.target===this)closeBulkModal()">
    <div class="pc-modal">
        <h3>Aplicar Custos em Lote</h3>
        <p>Os valores serão aplicados a <strong>todos os produtos</strong> ativos.</p>
        <form id="bulkForm">
            <div class="pc-modal-field">
                <label>Custo de Embalagem (R$)</label>
                <input type="number" id="bulkPackaging" step="0.01" min="0" placeholder="0,00">
            </div>
            <div class="pc-modal-field">
                <label>Mão de Obra (R$)</label>
                <input type="number" id="bulkLabor" step="0.01" min="0" placeholder="0,00">
            </div>
            <div class="pc-modal-field">
                <label>Taxa de Imposto (%)</label>
                <input type="number" id="bulkTax" step="0.01" min="0" max="100" placeholder="0,00">
            </div>
            <div class="pc-modal-actions">
                <button type="button" class="pc-modal-btn cancel" onclick="closeBulkModal()">Cancelar</button>
                <button type="submit" class="pc-modal-btn apply">Aplicar a Todos</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div id="toast" style="display:none; position:fixed; top:1rem; left:50%; transform:translateX(-50%); z-index:9999; padding:0.625rem 1.25rem; border-radius:0.75rem; font-size:0.8125rem; font-weight:500; box-shadow:0 4px 12px rgba(0,0,0,0.15);"></div>

<script>
function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.style.display = 'block';
    t.style.background = type === 'success' ? '#dcfce7' : type === 'error' ? '#fee2e2' : '#dbeafe';
    t.style.color = type === 'success' ? '#166534' : type === 'error' ? '#991b1b' : '#1e40af';
    setTimeout(function() { t.style.display = 'none'; }, 3000);
}

// Search
document.getElementById('searchProduct').addEventListener('input', function() {
    var search = this.value.toLowerCase();
    document.querySelectorAll('.product-row').forEach(function(row) {
        row.style.display = row.getAttribute('data-name').indexOf(search) !== -1 ? '' : 'none';
    });
});

// Bulk modal
function showBulkModal() { document.getElementById('bulkModal').classList.add('show'); }
function closeBulkModal() { document.getElementById('bulkModal').classList.remove('show'); }

document.getElementById('bulkForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!confirm('Aplicar custos a TODOS os produtos ativos?')) return;

    var data = {
        packaging_cost: parseFloat(document.getElementById('bulkPackaging').value) || 0,
        labor_cost: parseFloat(document.getElementById('bulkLabor').value) || 0,
        tax_rate: parseFloat(document.getElementById('bulkTax').value) || 0
    };

    fetch('/product-costs/bulk-update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            showToast(result.message, 'success');
            closeBulkModal();
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            showToast(result.message || 'Erro', 'error');
        }
    })
    .catch(function(err) { showToast('Erro: ' + err.message, 'error'); });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
