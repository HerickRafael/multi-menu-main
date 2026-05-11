<?php
/**
 * Packaging Index - Mobile
 * Lista de insumos & embalagens
 */
$activeNav = 'settings';
$allSupplies = $supplies ?? [];

$totalSupplies = count($allSupplies);
$activeSupplies = 0;
$lowStock = 0;
$totalValue = 0;
foreach ($allSupplies as $s) {
    if (!empty($s['active'])) $activeSupplies++;
    $totalValue += (float)$s['cost_per_unit'] * (float)$s['stock_quantity'];
    if ((float)$s['stock_quantity'] <= (float)$s['min_stock_alert'] && (float)$s['min_stock_alert'] > 0) $lowStock++;
}

$successMessages = [
    'created' => 'Insumo criado com sucesso!',
    'updated' => 'Insumo atualizado com sucesso!',
    'deleted' => 'Insumo removido com sucesso!',
];
$errorMessages = [
    'notfound' => 'Insumo não encontrado.',
    'name' => 'O nome é obrigatório.',
    'duplicate' => 'Já existe um insumo com este nome.',
];

ob_start();
?>

<style>
.pkg-page { padding: 1rem; padding-bottom: 6rem; }
.pkg-back { display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-secondary, #64748b); text-decoration: none; font-size: 0.875rem; margin-bottom: 0.75rem; }
.pkg-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.pkg-title { font-size: 1.125rem; font-weight: 700; color: var(--text-primary, #1e293b); }
.pkg-add-btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 0.875rem; background: var(--primary, #4361ee); color: #fff; border: none; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 500; text-decoration: none; cursor: pointer; }
.pkg-add-btn svg { width: 1rem; height: 1rem; }

/* Metrics */
.pkg-metrics { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 1rem; }
.pkg-metric { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.75rem; }
.pkg-metric-label { font-size: 0.6875rem; color: var(--text-secondary, #64748b); margin-bottom: 0.25rem; }
.pkg-metric-value { font-size: 1.25rem; font-weight: 700; }

/* Search */
.pkg-search { width: 100%; padding: 0.625rem 0.875rem 0.625rem 2.5rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") 0.75rem center no-repeat; margin-bottom: 0.75rem; }
.pkg-search:focus { outline: none; border-color: var(--primary, #4361ee); }

/* Card */
.pkg-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 0.875rem; margin-bottom: 0.625rem; }
.pkg-card.inactive { opacity: 0.55; }
.pkg-card-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.375rem; }
.pkg-card-name { font-size: 0.9375rem; font-weight: 600; color: var(--text-primary, #1e293b); flex: 1; }
.pkg-badge-active { background: #dcfce7; color: #166534; }
.pkg-badge-inactive { background: #f1f5f9; color: #64748b; }
.pkg-badge { display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 600; flex-shrink: 0; }
.pkg-card-desc { font-size: 0.75rem; color: var(--text-secondary, #64748b); margin-bottom: 0.5rem; }
.pkg-card-mid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.5rem; }
.pkg-stat { text-align: center; }
.pkg-stat-label { font-size: 0.625rem; color: var(--text-secondary, #64748b); text-transform: uppercase; }
.pkg-stat-value { font-size: 0.8125rem; font-weight: 600; color: var(--text-primary, #1e293b); }
.pkg-card-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 0.5rem; border-top: 1px solid #f1f5f9; }
.pkg-card-products { font-size: 0.75rem; color: var(--text-secondary, #64748b); }
.pkg-card-actions { display: flex; gap: 0.5rem; }
.pkg-btn-edit, .pkg-btn-del { width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; border: 1px solid #e2e8f0; cursor: pointer; }
.pkg-btn-edit { background: #fff; color: var(--primary, #4361ee); text-decoration: none; }
.pkg-btn-del { background: #fff; color: #dc2626; border-color: #fecaca; }

/* Alert */
.pkg-alert { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border-radius: 0.75rem; margin-bottom: 0.75rem; font-size: 0.8125rem; }
.pkg-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.pkg-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Empty */
.pkg-empty { text-align: center; padding: 3rem 1rem; color: var(--text-secondary, #64748b); }
.pkg-empty svg { margin: 0 auto 1rem; }
</style>

<div class="pkg-page">
    <a href="/settings" class="pkg-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Configurações
    </a>

    <div class="pkg-header">
        <h1 class="pkg-title">Insumos & Embalagens</h1>
        <a href="/packaging/create" class="pkg-add-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Novo
        </a>
    </div>

    <?php if (!empty($success) && isset($successMessages[$success])): ?>
    <div class="pkg-alert pkg-alert-success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?= htmlspecialchars($successMessages[$success]) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($error) && isset($errorMessages[$error])): ?>
    <div class="pkg-alert pkg-alert-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?= htmlspecialchars($errorMessages[$error]) ?>
    </div>
    <?php endif; ?>

    <!-- Metrics -->
    <div class="pkg-metrics">
        <div class="pkg-metric">
            <div class="pkg-metric-label">Total / Ativos</div>
            <div class="pkg-metric-value" style="color: var(--text-primary, #1e293b);"><?= $totalSupplies ?> / <?= $activeSupplies ?></div>
        </div>
        <div class="pkg-metric">
            <div class="pkg-metric-label">Valor em Estoque</div>
            <div class="pkg-metric-value" style="color: #7c3aed;">R$ <?= number_format($totalValue, 2, ',', '.') ?></div>
        </div>
    </div>

    <?php if ($lowStock > 0): ?>
    <div class="pkg-alert" style="background:#fffbeb; color:#92400e; border:1px solid #fde68a; margin-bottom:0.75rem;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="12" x2="5" y2="12" stroke-linecap="round" stroke-linejoin="round"/><polyline points="12 19 5 12 12 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?= $lowStock ?> insumo(s) com estoque baixo
    </div>
    <?php endif; ?>

    <!-- Search -->
    <input type="text" id="searchSupply" class="pkg-search" placeholder="Buscar insumo...">

    <!-- Supply list -->
    <?php if (empty($allSupplies)): ?>
    <div class="pkg-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        <p style="font-weight:600; color:var(--text-primary, #1e293b); margin-bottom:0.25rem;">Nenhum insumo cadastrado</p>
        <p>Comece cadastrando embalagens e insumos</p>
    </div>
    <?php else: ?>
    <div id="supplyList">
        <?php foreach ($allSupplies as $supply):
            $isLowStock = (float)$supply['stock_quantity'] <= (float)$supply['min_stock_alert'] && (float)$supply['min_stock_alert'] > 0;
        ?>
        <div class="pkg-card <?= empty($supply['active']) ? 'inactive' : '' ?> supply-row" data-name="<?= strtolower(htmlspecialchars($supply['name'])) ?>">
            <div class="pkg-card-top">
                <span class="pkg-card-name">
                    <?php if ($isLowStock): ?>
                        <span style="color:#d97706; margin-right:0.25rem;">⚠️</span>
                    <?php endif; ?>
                    <?= htmlspecialchars($supply['name']) ?>
                </span>
                <span class="pkg-badge <?= $supply['active'] ? 'pkg-badge-active' : 'pkg-badge-inactive' ?>"><?= $supply['active'] ? 'Ativo' : 'Inativo' ?></span>
            </div>
            <?php if (!empty($supply['description'])): ?>
            <div class="pkg-card-desc"><?= htmlspecialchars(mb_substr($supply['description'], 0, 60)) ?><?= mb_strlen($supply['description']) > 60 ? '...' : '' ?></div>
            <?php endif; ?>
            <div class="pkg-card-mid">
                <div class="pkg-stat">
                    <div class="pkg-stat-label">Custo/Un</div>
                    <div class="pkg-stat-value">R$ <?= number_format((float)$supply['cost_per_unit'], 2, ',', '.') ?></div>
                </div>
                <div class="pkg-stat">
                    <div class="pkg-stat-label">Estoque</div>
                    <div class="pkg-stat-value" style="<?= $isLowStock ? 'color:#d97706;' : '' ?>"><?= number_format((float)$supply['stock_quantity'], 1, ',', '.') ?> <?= htmlspecialchars($supply['unit'] ?? 'un') ?></div>
                </div>
                <div class="pkg-stat">
                    <div class="pkg-stat-label"><?= !empty($supply['supplier']) ? 'Fornecedor' : 'Unidade' ?></div>
                    <div class="pkg-stat-value" style="font-size:0.75rem;"><?= htmlspecialchars(!empty($supply['supplier']) ? mb_substr($supply['supplier'], 0, 12) : ($supply['unit'] ?? 'un')) ?></div>
                </div>
            </div>
            <div class="pkg-card-bottom">
                <span class="pkg-card-products"><?= (int)($supply['product_count'] ?? 0) ?> produto(s)</span>
                <div class="pkg-card-actions">
                    <a href="/packaging/<?= $supply['id'] ?>/edit" class="pkg-btn-edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <form action="/packaging/<?= $supply['id'] ?>/delete" method="POST" onsubmit="return confirm('Excluir este insumo?');" style="display:inline;">
                        <button type="submit" class="pkg-btn-del">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('searchSupply').addEventListener('input', function() {
    var search = this.value.toLowerCase();
    document.querySelectorAll('.supply-row').forEach(function(row) {
        row.style.display = row.getAttribute('data-name').indexOf(search) !== -1 ? '' : 'none';
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
