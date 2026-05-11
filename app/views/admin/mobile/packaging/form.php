<?php
/**
 * Packaging Form - Mobile
 * Criação/Edição de insumo
 */
$activeNav = 'settings';
$isEditMode = $isEdit ?? false;
$s = $supply ?? [];

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

ob_start();
?>

<style>
.pkf-page { padding: 1rem; padding-bottom: 6rem; }
.pkf-back { display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-secondary, #64748b); text-decoration: none; font-size: 0.875rem; margin-bottom: 0.75rem; }
.pkf-title { font-size: 1.125rem; font-weight: 700; color: var(--text-primary, #1e293b); margin-bottom: 1rem; }

.pkf-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; margin-bottom: 0.75rem; }
.pkf-section-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9375rem; font-weight: 600; color: var(--text-primary, #1e293b); margin-bottom: 0.75rem; }
.pkf-section-title svg { width: 1.125rem; height: 1.125rem; color: var(--text-secondary, #64748b); }

.pkf-field { margin-bottom: 0.875rem; }
.pkf-label { display: block; font-size: 0.8125rem; font-weight: 500; color: var(--text-primary, #1e293b); margin-bottom: 0.375rem; }
.pkf-input, .pkf-select, .pkf-textarea { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background: #fff; }
.pkf-input:focus, .pkf-select:focus, .pkf-textarea:focus { outline: none; border-color: var(--primary, #4361ee); }
.pkf-textarea { resize: vertical; min-height: 3rem; }
.pkf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

.pkf-input-prefix { position: relative; }
.pkf-input-prefix span { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 0.875rem; color: var(--text-secondary, #64748b); }
.pkf-input-prefix input { padding-left: 2.25rem; }

.pkf-hint { font-size: 0.6875rem; color: var(--text-secondary, #64748b); margin-top: 0.25rem; }

/* Toggle */
.pkf-toggle { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; }
.pkf-toggle input[type=checkbox] { width: 1.25rem; height: 1.25rem; accent-color: var(--primary, #4361ee); }
.pkf-toggle-label { font-weight: 500; color: var(--text-primary, #1e293b); font-size: 0.875rem; }
.pkf-toggle-desc { font-size: 0.75rem; color: var(--text-secondary, #64748b); }

/* Products using */
.pkf-product-list { font-size: 0.8125rem; color: var(--text-secondary, #64748b); }
.pkf-product-list li { padding: 0.25rem 0; }

/* Actions */
.pkf-actions { display: flex; gap: 0.75rem; margin-top: 1rem; }
.pkf-btn { flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-size: 0.9375rem; font-weight: 500; border: none; cursor: pointer; text-align: center; text-decoration: none; }
.pkf-btn-cancel { background: #f1f5f9; color: var(--text-primary, #1e293b); display: flex; align-items: center; justify-content: center; }
.pkf-btn-save { background: var(--primary, #4361ee); color: #fff; }
</style>

<div class="pkf-page">
    <a href="/packaging" class="pkf-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="12" x2="5" y2="12" stroke-linecap="round" stroke-linejoin="round"/><polyline points="12 19 5 12 12 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Insumos & Embalagens
    </a>

    <h1 class="pkf-title"><?= $isEditMode ? 'Editar Insumo' : 'Novo Insumo' ?></h1>

    <form action="/packaging/store" method="POST">
        <?php if ($isEditMode && !empty($s['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
        <?php endif; ?>

        <!-- Info section -->
        <div class="pkf-section">
            <div class="pkf-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Informações do Insumo
            </div>

            <div class="pkf-field">
                <label class="pkf-label">Nome *</label>
                <input type="text" name="name" required class="pkf-input" placeholder="Ex: Caixa para Hambúrguer" value="<?= htmlspecialchars($s['name'] ?? '') ?>">
            </div>

            <div class="pkf-field">
                <label class="pkf-label">Descrição</label>
                <textarea name="description" class="pkf-textarea" rows="2" placeholder="Descrição opcional..."><?= htmlspecialchars($s['description'] ?? '') ?></textarea>
            </div>

            <div class="pkf-row">
                <div class="pkf-field">
                    <label class="pkf-label">Unidade de Medida</label>
                    <select name="unit" class="pkf-select">
                        <?php foreach ($units as $val => $lbl): ?>
                        <option value="<?= htmlspecialchars($val) ?>" <?= ($s['unit'] ?? 'un') === $val ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pkf-field">
                    <label class="pkf-label">Custo por Unidade *</label>
                    <div class="pkf-input-prefix">
                        <span>R$</span>
                        <input type="number" name="cost_per_unit" required step="0.01" min="0" class="pkf-input" value="<?= number_format((float)($s['cost_per_unit'] ?? 0), 2, '.', '') ?>">
                    </div>
                </div>
            </div>

            <div class="pkf-field">
                <label class="pkf-label">Fornecedor</label>
                <input type="text" name="supplier" class="pkf-input" placeholder="Nome do fornecedor (opcional)" value="<?= htmlspecialchars($s['supplier'] ?? '') ?>">
            </div>
        </div>

        <!-- Stock section -->
        <div class="pkf-section">
            <div class="pkf-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Controle de Estoque
            </div>

            <div class="pkf-row">
                <div class="pkf-field">
                    <label class="pkf-label">Quantidade em Estoque</label>
                    <input type="number" name="stock_quantity" step="0.01" min="0" class="pkf-input" value="<?= htmlspecialchars($s['stock_quantity'] ?? '0') ?>">
                </div>
                <div class="pkf-field">
                    <label class="pkf-label">Alerta Estoque Mín.</label>
                    <input type="number" name="min_stock_alert" step="0.01" min="0" class="pkf-input" value="<?= htmlspecialchars($s['min_stock_alert'] ?? '0') ?>" placeholder="0 = sem alerta">
                    <div class="pkf-hint">Alerta quando atingir este valor</div>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="pkf-section">
            <label class="pkf-toggle">
                <input type="checkbox" name="active" value="1" <?= ($s['active'] ?? 1) ? 'checked' : '' ?>>
                <div>
                    <div class="pkf-toggle-label">Insumo Ativo</div>
                    <div class="pkf-toggle-desc">Inativos não aparecem na seleção de embalagens</div>
                </div>
            </label>
        </div>

        <?php if ($isEditMode && !empty($products)): ?>
        <!-- Products using this supply -->
        <div class="pkf-section">
            <div class="pkf-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" stroke-linecap="round" stroke-linejoin="round"/><polyline points="3.27 6.96 12 12.01 20.73 6.96" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="22.08" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Produtos que usam este insumo
            </div>
            <ul class="pkf-product-list">
                <?php foreach ($products as $prod): ?>
                <li><?= htmlspecialchars($prod['name'] ?? 'Produto #' . ($prod['id'] ?? '?')) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="pkf-actions">
            <a href="/packaging" class="pkf-btn pkf-btn-cancel">Cancelar</a>
            <button type="submit" class="pkf-btn pkf-btn-save"><?= $isEditMode ? 'Salvar' : 'Criar Insumo' ?></button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
