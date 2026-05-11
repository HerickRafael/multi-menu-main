<?php
/**
 * Formulário de Produto Mobile - Versão Completa
 * Paridade com desktop: combos, personalização, ingredientes
 */

$p = $product ?? [];
$isEdit = !empty($p['id']);
$ptype = $p['type'] ?? 'simple';
$pmode = $p['price_mode'] ?? 'fixed';

// Personalização
$customization = $customization ?? ['enabled' => false, 'groups' => []];
$custEnabled = !empty($customization['enabled']);
$custGroups = $customization['groups'] ?? [];

// Grupos de combo
$groups = $groups ?? [];
$hasGroups = !empty($groups);

// Ingredientes e produtos simples
$ingredients = $ingredients ?? [];
$simpleProducts = $simpleProducts ?? [];

// Templates de personalização para copiar grupo
$custTemplates = $custTemplates ?? [];
$custTemplatesJson = json_encode($custTemplates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// Criar mapa de produtos simples
$simpleMap = [];
foreach ($simpleProducts as $sp) {
    $simpleMap[(int)$sp['id']] = $sp;
}

// Flash messages
$flashError = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Cores do sistema
$getData = function($key, $default) use ($company) {
    if (is_array($company)) {
        return $company[$key] ?? $default;
    }
    return $company->$key ?? $default;
};
$headerBgColor = $getData('menu_header_bg_color', $company['theme_color'] ?? '#4361ee');

ob_start();
?>

<style>
:root {
    --primary-color: <?= htmlspecialchars($headerBgColor) ?>;
}

/* === Estilos Mobile Form === */

/* Form wrapper */
form#productForm {
    padding: 0px;
}

/* Cards de Tipo */
.type-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.type-card { padding: 14px 12px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; text-align: center; transition: all 0.2s; background: white; }
.type-card.active { border-color: var(--primary-color); background: #f5f3ff; }
.type-card-icon { width: 28px; height: 28px; margin: 0 auto 6px; color: #6b7280; }
.type-card.active .type-card-icon { color: var(--primary-color); }
.type-card-title { font-weight: 600; color: #1e293b; font-size: 13px; }
.type-card-desc { font-size: 11px; color: #6b7280; margin-top: 2px; }
.type-card input[type="radio"] { display: none; }

/* Seções */
.form-section {
    padding: 20px 16px;
    border-bottom: 1px solid #e5e7eb;
}

.section-title { display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1e293b; margin-bottom: 12px; font-size: 14px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
.section-title svg { width: 18px; height: 18px; color: var(--primary-color); flex-shrink: 0; }
.section-help-btn { margin-left: auto; display: flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; border: 1.5px solid #d1d5db; background: #fff; color: #9ca3af; font-size: 12px; font-weight: 700; text-decoration: none; flex-shrink: 0; transition: all .2s; }
.section-help-btn:active { border-color: var(--primary-color); color: var(--primary-color); background: var(--primary-light, #f3f4f6); }

/* Form groups */
.form-group { margin-bottom: 16px; }
.form-group:last-child { margin-bottom: 0; }

.form-label { display: block; font-weight: 500; color: #374151; margin-bottom: 8px; font-size: 13px; }
.form-input { width: 100%; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; color: #1f2937; font-family: inherit; transition: all 0.2s; -webkit-appearance: none; appearance: none; }
.form-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(91, 33, 182, 0.1); background: white; }
.form-input:disabled { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
.form-input::placeholder { color: #d1d5db; }

/* Helper */
.form-help { font-size: 11px; color: #6b7280; margin-top: 4px; line-height: 1.4; }

/* Promo Section */
.promo-section { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #fbbf24 !important; }
.promo-section .section-title { color: #92400e; border-color: #fbbf24; }
.promo-section .section-title svg { color: #d97706; }
.promo-section .form-label { color: #78350f; }

/* Campos de preço */
.input-with-prefix { position: relative; display: flex; align-items: center; }
.input-with-prefix .prefix { position: absolute; left: 14px; color: #6b7280; font-weight: 500; pointer-events: none; font-size: 14px; background: none; padding: 0; border: none; z-index: 1; }
.input-with-prefix .form-input { padding-left: 38px; border: 1px solid #d1d5db; border-radius: 8px; }

.input-with-suffix { position: relative; }
.input-with-suffix .suffix { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; font-weight: 500; pointer-events: none; font-size: 14px; background: none; padding: 0; border: none; }
.input-with-suffix .form-input { padding-right: 32px; border: 1px solid #d1d5db; border-radius: 8px; }

/* SKU */
.sku-field { position: relative; }
.sku-field .form-input { padding-right: 40px; background: #f8fafc; }
.sku-lock { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }

/* Row layout */
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-row.full { grid-template-columns: 1fr; }
.flex-1 { flex: 1; }

/* Image Upload */
.image-upload-area {
    position: relative;
    width: 100%;
    aspect-ratio: 4/3;
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    margin-bottom: 12px;
}

.image-upload-area:active {
    border-color: var(--primary-color);
    background: rgba(91, 33, 182, 0.05);
}

.image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: #9ca3af;
}

.image-placeholder svg {
    width: 48px;
    height: 48px;
}

#imagePreview {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Toggle Switch */
.toggle-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; }
.toggle-track { width: 44px; height: 24px; background: #cbd5e1; border-radius: 12px; position: relative; transition: background 0.2s; cursor: pointer; flex-shrink: 0; }
.toggle-track.active { background: var(--primary-color); }
.toggle-thumb { position: absolute; left: 2px; top: 2px; width: 20px; height: 20px; background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: transform 0.2s; }
.toggle-track.active .toggle-thumb { transform: translateX(20px); }
.toggle-text { font-size: 14px; color: #374151; }

/* Grupos de Combo/Personalização */
.group-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; overflow: hidden; }
.group-header { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.group-header input[type="text"] { flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
.btn-remove-group { width: 32px; height: 32px; border: none; background: #fee2e2; color: #dc2626; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; }

/* Items dentro de grupos */
.group-items { padding: 12px; }
.group-item { display: flex; flex-wrap: wrap; gap: 8px; padding: 10px; background: #f9fafb; border-radius: 8px; margin-bottom: 8px; align-items: flex-start; }
.group-item:last-child { margin-bottom: 0; }
.item-select { flex: 1 1 100%; min-width: 0; }
.item-select select, .item-select input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; }
.item-fields { display: flex; gap: 8px; flex: 1 1 100%; }
.item-field { flex: 1; }
.item-field label { display: block; font-size: 11px; color: #6b7280; margin-bottom: 4px; }
.item-field input { width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; text-align: center; }
.btn-remove-item { width: 36px; height: 36px; border: none; background: #fef2f2; color: #ef4444; border-radius: 8px; cursor: pointer; align-self: flex-end; flex-shrink: 0; }

/* Botão Padrão */
.btn-default { padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; cursor: pointer; background: white; color: #6b7280; height: 36px; display: flex; align-items: center; justify-content: center; }
.btn-default.active { background: var(--primary-color); border-color: var(--primary-color); color: white; }

/* Adicionar */
.group-footer { padding: 12px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; }
.btn-add-item { flex: 1; padding: 10px; border: 1px dashed #d1d5db; background: white; border-radius: 8px; color: #6b7280; font-size: 13px; cursor: pointer; }
.btn-add-item:active { background: #f8fafc; }

.btn-add-group { width: 100%; padding: 14px; border: 2px dashed #d1d5db; background: white; border-radius: 12px; color: #6b7280; font-size: 14px; font-weight: 500; cursor: pointer; margin-top: 8px; }
.btn-add-group:active { background: #f8fafc; border-color: var(--primary-color); color: var(--primary-color); }

/* Wrap de grupos */
.groups-wrap { display: none; }
.groups-wrap.visible { display: block; }

/* Mode Select */
.mode-row { display: flex; gap: 8px; margin-bottom: 12px; }
.mode-row select { flex: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; }

/* Choice settings */
.choice-settings { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px; margin: 8px 0; }
.choice-settings.hidden { display: none; }
/* Pool settings */
.pool-settings { background: #fdf4ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 10px; margin: 8px 0; }
.pool-settings.hidden { display: none; }

/* Erro/Sucesso */
.alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 12px; font-size: 14px; }
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

/* Contador descrição */
.desc-counter { text-align: right; font-size: 11px; color: #9ca3af; margin-top: 4px; }

/* Action Buttons - PWA Optimized Footer (acima da bottom-nav) */
.form-actions {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: white;
    border-top: 1px solid #e5e7eb;
    position: fixed;
    /* Posicionar ACIMA da bottom navigation (64px + safe-area) */
    bottom: calc(64px + env(safe-area-inset-bottom));
    left: 0;
    right: 0;
    z-index: 101; /* Acima da bottom-nav que tem z-index: 100 */
    box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
}

/* Espaçamento no final para compensar footer + bottom-nav */
.form-footer-spacer {
    height: calc(80px + 64px + env(safe-area-inset-bottom));
}

/* iOS Safari específico */
@supports (-webkit-touch-callout: none) {
    .form-actions {
        bottom: calc(64px + constant(safe-area-inset-bottom));
        bottom: calc(64px + env(safe-area-inset-bottom));
    }
    
    .form-footer-spacer {
        height: calc(80px + 64px + constant(safe-area-inset-bottom));
        height: calc(80px + 64px + env(safe-area-inset-bottom));
    }
}

.btn-primary,
.btn-secondary,
.btn-danger {
    flex: 1;
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:active {
    transform: scale(0.96);
    opacity: 0.9;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:active {
    background: #e5e7eb;
}

.btn-danger {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.btn-danger:active {
    background: #fee2e2;
}

/* Adicionar */
.group-footer { padding: 12px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; }
.btn-add-item { flex: 1; padding: 10px; border: 1px dashed #d1d5db; background: white; border-radius: 8px; color: #6b7280; font-size: 13px; cursor: pointer; }
.btn-add-item:active { background: #f8fafc; }

.btn-add-group { width: 100%; padding: 14px; border: 2px dashed #d1d5db; background: white; border-radius: 12px; color: #6b7280; font-size: 14px; font-weight: 500; cursor: pointer; margin-top: 8px; }
.btn-add-group:active { background: #f8fafc; border-color: var(--primary, #6366f1); color: var(--primary, #6366f1); }

/* Wrap de grupos */
.groups-wrap { display: none; }
.groups-wrap.visible { display: block; }

/* Mode Select */
.mode-row { display: flex; gap: 8px; margin-bottom: 12px; }
.mode-row select { flex: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; }

/* Choice settings */
.choice-settings { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px; margin: 8px 0; }
.choice-settings.hidden { display: none; }
/* Pool settings */
.pool-settings { background: #fdf4ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 10px; margin: 8px 0; }
.pool-settings.hidden { display: none; }

/* Erro/Sucesso */
.alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 12px; font-size: 14px; }
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

/* Contador descrição */
.desc-counter { text-align: right; font-size: 11px; color: #9ca3af; margin-top: 4px; }
</style>

<form method="POST" action="<?= $isEdit ? "/products/{$p['id']}" : '/products' ?>" 
      enctype="multipart/form-data" id="productForm">

    <?php if ($flashError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>

    <!-- ========== IMAGEM ========== -->
    <div class="form-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Imagem
            <a href="/guide/products#form" class="section-help-btn" title="Ajuda: Imagem">?</a>
        </div>
        <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
            <?php if ($isEdit && !empty($p['image'])): ?>
                <img src="/<?= htmlspecialchars($p['image']) ?>" alt="Produto" id="imagePreview">
            <?php else: ?>
                <div class="image-placeholder" id="imagePlaceholder">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Toque para adicionar foto</span>
                </div>
                <img src="" alt="Preview" id="imagePreview" style="display: none;">
            <?php endif; ?>
        </div>
        <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;" onchange="previewImage(this)">
        <p class="form-help">Recomendado: 1000×750px (4:3). Máx. 5MB.</p>
    </div>

    <!-- ========== DADOS BÁSICOS ========== -->
    <div class="form-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Dados Básicos
            <a href="/guide/products#form" class="section-help-btn" title="Ajuda: Dados Básicos">?</a>
        </div>
        
        <div class="form-group">
            <label class="form-label">Nome do Produto *</label>
            <input type="text" name="name" class="form-input" required
                   value="<?= htmlspecialchars($p['name'] ?? '') ?>"
                   placeholder="Ex: X-Burger Especial">
        </div>
        
        <div class="form-row">
            <div class="form-group flex-1">
                <label class="form-label">SKU</label>
                <div class="sku-field">
                    <input type="text" name="sku" class="form-input" readonly
                           value="<?= htmlspecialchars($p['sku'] ?? '') ?>"
                           placeholder="Automático">
                    <span class="sku-lock">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a5 5 0 00-5 5v3H6a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2v-8a2 2 0 00-2-2h-1V7a5 5 0 00-5-5zm3 8H9V7a3 3 0 116 0v3z"/></svg>
                    </span>
                </div>
            </div>
            <div class="form-group flex-1">
                <label class="form-label">Categoria</label>
                <select name="category_id" class="form-input">
                    <option value="">— sem —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($p['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Descrição</label>
            <textarea name="description" id="descField" class="form-input" rows="3"
                      placeholder="Descreva o produto..." maxlength="500"
                      oninput="updateDescCounter()"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
            <div class="desc-counter"><span id="descCount"><?= strlen($p['description'] ?? '') ?></span>/500</div>
        </div>
    </div>

    <!-- ========== TIPO DO PRODUTO ========== -->
    <div class="form-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Tipo do Produto
            <a href="/guide/products#overview" class="section-help-btn" title="Ajuda: Tipo do Produto">?</a>
        </div>
        
        <div class="type-cards">
            <label class="type-card <?= $ptype === 'simple' ? 'active' : '' ?>" data-type="simple">
                <input type="radio" name="type" value="simple" <?= $ptype === 'simple' ? 'checked' : '' ?>>
                <svg class="type-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <div class="type-card-title">Simples</div>
                <div class="type-card-desc">Produto único</div>
            </label>
            <label class="type-card <?= $ptype === 'combo' ? 'active' : '' ?>" data-type="combo">
                <input type="radio" name="type" value="combo" <?= $ptype === 'combo' ? 'checked' : '' ?>>
                <svg class="type-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <div class="type-card-title">Combo</div>
                <div class="type-card-desc">Múltiplos itens</div>
            </label>
        </div>
        <p class="form-help"><b>Simples:</b> Produto com personalização de ingredientes. <b>Combo:</b> Monte kits com outros produtos.</p>
    </div>

    <!-- ========== PREÇO ========== -->
    <div class="form-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Preço
            <a href="/guide/products#pricing" class="section-help-btn" title="Ajuda: Preço">?</a>
        </div>
        
        <div class="form-row">
            <div class="form-group flex-1">
                <label class="form-label">Preço Base (R$) *</label>
                <div class="input-with-prefix">
                    <span class="prefix">R$</span>
                    <input type="text" name="price" class="form-input" required inputmode="decimal"
                           value="<?= isset($p['price']) ? number_format((float)$p['price'], 2, ',', '') : '' ?>"
                           placeholder="0,00">
                </div>
            </div>
            <div class="form-group flex-1">
                <label class="form-label">Ordem</label>
                <input type="number" name="sort_order" class="form-input" 
                       value="<?= (int)($p['sort_order'] ?? 0) ?>" placeholder="0">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Modo de Preço</label>
            <select name="price_mode" id="priceModeSelect" class="form-input" onchange="togglePromoFields()">
                <option value="fixed" <?= $pmode === 'fixed' ? 'selected' : '' ?>>Fixo (preço base)</option>
                <option value="sum" <?= $pmode === 'sum' ? 'selected' : '' ?>>Somar itens do grupo</option>
            </select>
            <p class="form-help">Em "Somar", total = preço base + deltas dos itens selecionados.</p>
        </div>
    </div>

    <!-- ========== PROMOÇÃO ========== -->
    <div class="form-section promo-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Promoção
            <a href="/guide/products#pricing" class="section-help-btn" title="Ajuda: Promoção" style="border-color:#fbbf24;color:#d97706">?</a>
        </div>
        
        <!-- Modo FIXO -->
        <div id="promoFixedField" class="<?= $pmode === 'sum' ? 'hidden' : '' ?>">
            <div class="form-group">
                <label class="form-label">Preço Promocional</label>
                <div class="input-with-prefix">
                    <span class="prefix">R$</span>
                    <input type="text" name="promo_price" class="form-input" inputmode="decimal"
                           value="<?= !empty($p['promo_price']) ? number_format((float)$p['promo_price'], 2, ',', '') : '' ?>"
                           placeholder="0,00">
                </div>
            </div>
        </div>
        
        <!-- Modo SOMAR -->
        <div id="promoSumField" class="<?= $pmode !== 'sum' ? 'hidden' : '' ?>">
            <div class="form-group">
                <label class="form-label">Desconto (%)</label>
                <div class="input-with-suffix">
                    <input type="number" name="promo_percentage" class="form-input" 
                           min="0" max="100" step="1"
                           value="<?= !empty($p['promo_percentage']) ? (int)$p['promo_percentage'] : '' ?>"
                           placeholder="Ex: 15">
                    <span class="suffix">%</span>
                </div>
                <p class="form-help">Desconto aplicado ao preço total calculado do combo.</p>
            </div>
        </div>

        <!-- Prazo da promoção -->
        <div class="form-group" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div>
                <label class="form-label" style="font-size:12px;">⏰ Início promoção</label>
                <input type="datetime-local" name="promo_start_at" class="form-input" style="font-size:13px;"
                       value="<?= !empty($p['promo_start_at']) ? date('Y-m-d\TH:i', strtotime($p['promo_start_at'])) : '' ?>">
            </div>
            <div>
                <label class="form-label" style="font-size:12px;">⏰ Fim promoção</label>
                <input type="datetime-local" name="promo_end_at" class="form-input" style="font-size:13px;"
                       value="<?= !empty($p['promo_end_at']) ? date('Y-m-d\TH:i', strtotime($p['promo_end_at'])) : '' ?>">
            </div>
        </div>
    </div>

    <!-- ========== GRUPOS DE COMBO ========== -->
    <div class="form-section" id="comboSection" style="display: <?= $ptype === 'combo' ? 'block' : 'none' ?>;">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Grupos do Combo
            <a href="/guide/products#combos" class="section-help-btn" title="Ajuda: Combos">?</a>
        </div>
        
        <input type="hidden" name="use_groups" value="<?= $hasGroups ? '1' : '0' ?>">
        
        <div class="toggle-row" onclick="toggleGroups(this)">
            <div class="toggle-track <?= $hasGroups ? 'active' : '' ?>" id="groupsToggle">
                <div class="toggle-thumb"></div>
            </div>
            <span class="toggle-text">Usar grupos de opções</span>
        </div>
        
        <div class="groups-wrap <?= $hasGroups ? 'visible' : '' ?>" id="groupsWrap">
            <?php if (empty($simpleProducts)): ?>
                <div class="alert alert-error">Nenhum produto simples cadastrado. Crie produtos simples primeiro.</div>
            <?php else: ?>
                <p class="form-help" style="margin-bottom: 12px;">Cada grupo é uma etapa (ex: "Lanche", "Bebida"). Selecione produtos simples para cada grupo.</p>
                
                <div id="groupsContainer">
                    <?php if (!empty($groups)): foreach ($groups as $gi => $g): 
                        $gItems = $g['items'] ?? [];
                        $min = (int)($g['min_qty'] ?? $g['min'] ?? 0);
                        $max = (int)($g['max_qty'] ?? $g['max'] ?? 1);
                    ?>
                    <div class="group-card" data-index="<?= $gi ?>">
                        <div class="group-header">
                            <input type="text" name="groups[<?= $gi ?>][name]" value="<?= htmlspecialchars($g['name'] ?? '') ?>" placeholder="Nome do grupo" required>
                            <button type="button" class="btn-remove-group" onclick="removeGroup(this)">✕</button>
                        </div>
                        <div class="group-items">
                            <div class="mode-row">
                                <div class="item-field">
                                    <label>Mín</label>
                                    <input type="number" name="groups[<?= $gi ?>][min]" value="<?= $min ?>" min="0">
                                </div>
                                <div class="item-field">
                                    <label>Máx</label>
                                    <input type="number" name="groups[<?= $gi ?>][max]" value="<?= $max ?>" min="1">
                                </div>
                            </div>
                            
                            <?php foreach ($gItems as $ii => $it):
                                $selId = (int)($it['product_id'] ?? $it['simple_id'] ?? 0);
                                $isDef = !empty($it['is_default'] ?? $it['default']);
                                $itemPrice = isset($it['price_override']) ? (float)$it['price_override'] : 0;
                                $itemQty = isset($it['default_qty']) ? (int)$it['default_qty'] : ($isDef ? 1 : 0);
                            ?>
                            <div class="group-item" data-item="<?= $ii ?>">
                                <div class="item-select">
                                    <select name="groups[<?= $gi ?>][items][<?= $ii ?>][product_id]" required>
                                        <option value="">— Selecione —</option>
                                        <?php foreach ($simpleProducts as $sp): ?>
                                            <option value="<?= $sp['id'] ?>" <?= $selId == $sp['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($sp['name']) ?> - R$ <?= number_format((float)$sp['price'], 2, ',', '.') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="item-fields">
                                    <div class="item-field">
                                        <label>Qtd</label>
                                        <input type="number" name="groups[<?= $gi ?>][items][<?= $ii ?>][default_qty]" 
                                               value="<?= $itemQty ?>" min="0" placeholder="0">
                                    </div>
                                    <div class="item-field">
                                        <label>Preço</label>
                                        <input type="number" step="0.01" name="groups[<?= $gi ?>][items][<?= $ii ?>][price_override]" 
                                               value="<?= $itemPrice > 0 ? number_format($itemPrice, 2, '.', '') : '' ?>" placeholder="0.00">
                                    </div>
                                    <input type="hidden" name="groups[<?= $gi ?>][items][<?= $ii ?>][default]" value="<?= $isDef ? '1' : '0' ?>">
                                    <button type="button" class="btn-default <?= $isDef ? 'active' : '' ?>" onclick="toggleDefault(this)">
                                        <?= $isDef ? 'Padrão' : 'Não' ?>
                                    </button>
                                    <button type="button" class="btn-remove-item" onclick="removeItem(this)">✕</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="group-footer">
                            <button type="button" class="btn-add-item" onclick="addItem(this, <?= $gi ?>)">+ Produto</button>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                
                <button type="button" class="btn-add-group" onclick="addGroup()">+ Adicionar Grupo</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== PERSONALIZAÇÃO (Ingredientes) ========== -->
    <div class="form-section" id="custSection" style="display: <?= $ptype === 'simple' ? 'block' : 'none' ?>;">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Personalização
            <a href="/guide/products#modes" class="section-help-btn" title="Ajuda: Personalização">?</a>
        </div>
        
        <input type="hidden" name="customization[enabled]" value="<?= $custEnabled ? '1' : '0' ?>" id="custEnabledHidden">
        
        <div class="toggle-row" onclick="toggleCust(this)">
            <div class="toggle-track <?= $custEnabled ? 'active' : '' ?>" id="custToggle">
                <div class="toggle-thumb"></div>
            </div>
            <span class="toggle-text">Permitir personalização</span>
        </div>
        
        <div class="groups-wrap <?= $custEnabled ? 'visible' : '' ?>" id="custWrap">
            <?php if (empty($ingredients)): ?>
                <div class="alert alert-error">Nenhum ingrediente cadastrado. Crie ingredientes primeiro.</div>
            <?php else: ?>
                <p class="form-help" style="margin-bottom: 12px;">Crie grupos de ingredientes (ex: "Molhos", "Extras"). O cliente pode personalizar o produto.</p>
                
                <div id="custContainer">
                    <?php if (!empty($custGroups)): foreach ($custGroups as $gi => $cg): 
                        $cgName = $cg['name'] ?? '';
                        $cItems = $cg['items'] ?? [];
                        $gMode = in_array($cg['type'] ?? '', ['single','addon','choice']) ? 'choice' : (($cg['type'] ?? '') === 'pool' ? 'pool' : 'extra');
                        $gMin = isset($cg['min']) ? max(0, (int)$cg['min']) : 0;
                        $gMax = isset($cg['max']) ? max($gMin, (int)$cg['max']) : 99;
                    ?>
                    <div class="group-card" data-cust-index="<?= $gi ?>">
                        <div class="group-header">
                            <input type="text" name="customization[groups][<?= $gi ?>][name]" value="<?= htmlspecialchars($cgName) ?>" placeholder="Nome do grupo">
                            <button type="button" class="btn-remove-group" onclick="removeCustGroup(this)">✕</button>
                        </div>
                        <div class="group-items">
                            <div class="mode-row">
                                <select name="customization[groups][<?= $gi ?>][mode]" class="cust-mode-select" onchange="toggleChoiceSettings(this)">
                                    <option value="extra" <?= $gMode === 'extra' ? 'selected' : '' ?>>Adicionar livremente</option>
                                    <option value="choice" <?= $gMode === 'choice' ? 'selected' : '' ?>>Escolher ingrediente</option>
                                    <option value="pool" <?= $gMode === 'pool' ? 'selected' : '' ?>>Montagem (açaí, poke...)</option>
                                </select>
                            </div>
                            
                            <div class="choice-settings <?= $gMode !== 'choice' ? 'hidden' : '' ?>">
                                <div class="item-fields">
                                    <div class="item-field">
                                        <label>Mín seleções</label>
                                        <input type="number" name="customization[groups][<?= $gi ?>][choice][min]" value="<?= $gMin ?>" min="0">
                                    </div>
                                    <div class="item-field">
                                        <label>Máx seleções</label>
                                        <input type="number" name="customization[groups][<?= $gi ?>][choice][max]" value="<?= $gMax ?>" min="1">
                                    </div>
                                </div>
                            </div>
                            <div class="pool-settings <?= $gMode !== 'pool' ? 'hidden' : '' ?>">
                                <div class="item-fields">
                                    <div class="item-field">
                                        <label>Total mínimo</label>
                                        <input type="number" name="customization[groups][<?= $gi ?>][pool][min]" value="<?= $gMode === 'pool' ? $gMin : 0 ?>" min="0">
                                    </div>
                                    <div class="item-field">
                                        <label>Total máximo</label>
                                        <input type="number" name="customization[groups][<?= $gi ?>][pool][max]" value="<?= $gMode === 'pool' ? $gMax : 4 ?>" min="1">
                                    </div>
                                </div>
                            </div>
                            
                            <?php foreach ($cItems as $ii => $ci):
                                $selIngId = isset($ci['ingredient_id']) ? (int)$ci['ingredient_id'] : 0;
                                $def = !empty($ci['default']);
                                $minQ = isset($ci['min_qty']) ? (int)$ci['min_qty'] : 0;
                                $maxQ = isset($ci['max_qty']) ? (int)$ci['max_qty'] : 1;
                                $defQty = isset($ci['default_qty']) ? (int)$ci['default_qty'] : $minQ;
                            ?>
                            <div class="group-item" data-cust-item="<?= $ii ?>">
                                <div class="item-select">
                                    <select name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][ingredient_id]" required>
                                        <option value="">— Ingrediente —</option>
                                        <?php foreach ($ingredients as $ing): ?>
                                            <option value="<?= $ing['id'] ?>" <?= $selIngId == $ing['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ing['name']) ?>
                                                <?php if (!empty($ing['price']) && $ing['price'] > 0): ?>
                                                    (+R$ <?= number_format((float)$ing['price'], 2, ',', '.') ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="item-fields">
                                    <div class="item-field">
                                        <label>Mín</label>
                                        <input type="number" name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][min_qty]" value="<?= $minQ ?>" min="0">
                                    </div>
                                    <div class="item-field">
                                        <label>Máx</label>
                                        <input type="number" name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][max_qty]" value="<?= $maxQ ?>" min="0">
                                    </div>
                                    <div class="item-field">
                                        <label>Qtd</label>
                                        <input type="number" name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][default_qty]" value="<?= $defQty ?>" min="0">
                                    </div>
                                    <input type="hidden" name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][default]" value="<?= $def ? '1' : '0' ?>">
                                    <button type="button" class="btn-default <?= $def ? 'active' : '' ?>" onclick="toggleDefault(this)">
                                        <?= $def ? 'Sim' : 'Não' ?>
                                    </button>
                                    <button type="button" class="btn-remove-item" onclick="removeItem(this)">✕</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="group-footer">
                            <button type="button" class="btn-add-item" onclick="addCustItem(this, <?= $gi ?>)">+ Ingrediente</button>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                
                <div style="display:flex; gap:8px; margin-top:8px;">
                    <button type="button" class="btn-add-group" style="flex:1; margin-top:0;" onclick="addCustGroup()">+ Novo Grupo</button>
                    <button type="button" id="cust-copy-template" class="btn-add-group" style="flex:1; margin-top:0; border-style:solid; border-color:var(--primary-color,#7c3aed); background:var(--primary-color,#7c3aed); color:white; display:inline-flex; align-items:center; justify-content:center; gap:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        Copiar Grupo
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== STATUS ========== -->
    <div class="form-section">
        <div class="toggle-row" onclick="toggleActive(this)">
            <input type="hidden" name="active" id="activeHidden" value="<?= ($p['active'] ?? 1) ? '1' : '0' ?>">
            <div class="toggle-track <?= ($p['active'] ?? 1) ? 'active' : '' ?>" id="activeToggle">
                <div class="toggle-thumb"></div>
            </div>
            <span class="toggle-text">Produto ativo no cardápio</span>
        </div>
    </div>

    <!-- ========== BOTÕES ========== -->
    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?= $isEdit ? 'Salvar Alterações' : 'Criar Produto' ?>
        </button>
        
        <?php if ($isEdit): ?>
            <button type="button" class="btn-danger" onclick="confirmDelete()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Excluir Produto
            </button>
        <?php endif; ?>
    </div>
</form>

<!-- Espaçador para compensar footer fixo -->
<div class="form-footer-spacer"></div>

<?php if ($isEdit): ?>
<form id="deleteForm" method="POST" action="/products/<?= $p['id'] ?>/delete" style="display: none;"></form>
<?php endif; ?>

<!-- ========== SCRIPTS ========== -->
<script>
// Dados para templates
const simpleProducts = <?= json_encode(array_values($simpleProducts), JSON_HEX_TAG | JSON_HEX_APOS) ?>;
const ingredients = <?= json_encode(array_values($ingredients), JSON_HEX_TAG | JSON_HEX_APOS) ?>;

let groupIndex = <?= count($groups) ?>;
let custGroupIndex = <?= count($custGroups) ?>;

// Preview de imagem
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('imagePlaceholder');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Contador de descrição
function updateDescCounter() {
    const field = document.getElementById('descField');
    const counter = document.getElementById('descCount');
    if (field && counter) counter.textContent = field.value.length;
}

// Toggle Tipo de Produto
document.querySelectorAll('.type-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        this.querySelector('input').checked = true;
        
        const type = this.dataset.type;
        document.getElementById('comboSection').style.display = type === 'combo' ? 'block' : 'none';
        document.getElementById('custSection').style.display = type === 'simple' ? 'block' : 'none';
    });
});

// Toggle campos de promoção
function togglePromoFields() {
    const mode = document.getElementById('priceModeSelect').value;
    document.getElementById('promoFixedField').classList.toggle('hidden', mode === 'sum');
    document.getElementById('promoSumField').classList.toggle('hidden', mode !== 'sum');
}

// Toggle grupos combo
function toggleGroups(row) {
    const toggle = document.getElementById('groupsToggle');
    const wrap = document.getElementById('groupsWrap');
    const hidden = row.closest('.form-section').querySelector('input[name="use_groups"]');
    
    toggle.classList.toggle('active');
    wrap.classList.toggle('visible');
    hidden.value = toggle.classList.contains('active') ? '1' : '0';
}

// Toggle personalização
function toggleCust(row) {
    const toggle = document.getElementById('custToggle');
    const wrap = document.getElementById('custWrap');
    const hidden = document.getElementById('custEnabledHidden');
    
    toggle.classList.toggle('active');
    wrap.classList.toggle('visible');
    hidden.value = toggle.classList.contains('active') ? '1' : '0';
}

// Toggle ativo
function toggleActive(row) {
    const toggle = document.getElementById('activeToggle');
    const hidden = document.getElementById('activeHidden');
    
    toggle.classList.toggle('active');
    hidden.value = toggle.classList.contains('active') ? '1' : '0';
}

// Toggle padrão
function toggleDefault(btn) {
    const isActive = btn.classList.toggle('active');
    const hidden = btn.previousElementSibling;
    hidden.value = isActive ? '1' : '0';
    btn.textContent = isActive ? 'Padrão' : 'Não';
    
    // Ajustar quantidade automaticamente
    // Se marcar padrão e qty=0, muda para 1. Se já tiver valor > 0, mantém.
    // Se desmarcar padrão, muda para 0.
    const itemFields = btn.closest('.item-fields');
    if (itemFields) {
        const qtyInput = itemFields.querySelector('input[name*="default_qty"]');
        if (qtyInput) {
            const currentQty = parseInt(qtyInput.value) || 0;
            if (isActive) {
                // Marcou como padrão: se estava 0, coloca 1
                if (currentQty === 0) {
                    qtyInput.value = '1';
                }
                // Se já tinha valor > 0, mantém
            } else {
                // Desmarcou padrão: volta para 0
                qtyInput.value = '0';
            }
        }
    }
}

// Toggle choice settings
function toggleChoiceSettings(select) {
    const groupItems = select.closest('.group-items');
    const choiceWrap = groupItems.querySelector('.choice-settings');
    const poolWrap = groupItems.querySelector('.pool-settings');
    if (choiceWrap) choiceWrap.classList.toggle('hidden', select.value !== 'choice');
    if (poolWrap) poolWrap.classList.toggle('hidden', select.value !== 'pool');
}

// Adicionar grupo combo
function addGroup() {
    const container = document.getElementById('groupsContainer');
    const idx = groupIndex++;
    
    const productOptions = simpleProducts.map(p => 
        `<option value="${p.id}">${p.name} - R$ ${parseFloat(p.price).toFixed(2).replace('.', ',')}</option>`
    ).join('');
    
    const html = `
        <div class="group-card" data-index="${idx}">
            <div class="group-header">
                <input type="text" name="groups[${idx}][name]" placeholder="Nome do grupo" required>
                <button type="button" class="btn-remove-group" onclick="removeGroup(this)">✕</button>
            </div>
            <div class="group-items">
                <div class="mode-row">
                    <div class="item-field"><label>Mín</label><input type="number" name="groups[${idx}][min]" value="0" min="0"></div>
                    <div class="item-field"><label>Máx</label><input type="number" name="groups[${idx}][max]" value="1" min="1"></div>
                </div>
                <div class="group-item" data-item="0">
                    <div class="item-select">
                        <select name="groups[${idx}][items][0][product_id]" required>
                            <option value="">— Selecione —</option>
                            ${productOptions}
                        </select>
                    </div>
                    <div class="item-fields">
                        <div class="item-field"><label>Qtd</label><input type="number" name="groups[${idx}][items][0][default_qty]" value="0" min="0" placeholder="0"></div>
                        <div class="item-field"><label>Preço</label><input type="number" step="0.01" name="groups[${idx}][items][0][price_override]" placeholder="0.00"></div>
                        <input type="hidden" name="groups[${idx}][items][0][default]" value="0">
                        <button type="button" class="btn-default" onclick="toggleDefault(this)">Não</button>
                        <button type="button" class="btn-remove-item" onclick="removeItem(this)">✕</button>
                    </div>
                </div>
            </div>
            <div class="group-footer">
                <button type="button" class="btn-add-item" onclick="addItem(this, ${idx})">+ Produto</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

// Adicionar item no grupo combo
function addItem(btn, groupIdx) {
    const items = btn.closest('.group-card').querySelector('.group-items');
    const existingItems = items.querySelectorAll('.group-item');
    const itemIdx = existingItems.length;
    
    const productOptions = simpleProducts.map(p => 
        `<option value="${p.id}">${p.name} - R$ ${parseFloat(p.price).toFixed(2).replace('.', ',')}</option>`
    ).join('');
    
    const html = `
        <div class="group-item" data-item="${itemIdx}">
            <div class="item-select">
                <select name="groups[${groupIdx}][items][${itemIdx}][product_id]" required>
                    <option value="">— Selecione —</option>
                    ${productOptions}
                </select>
            </div>
            <div class="item-fields">
                <div class="item-field"><label>Qtd</label><input type="number" name="groups[${groupIdx}][items][${itemIdx}][default_qty]" value="0" min="0" placeholder="0"></div>
                <div class="item-field"><label>Preço</label><input type="number" step="0.01" name="groups[${groupIdx}][items][${itemIdx}][price_override]" placeholder="0.00"></div>
                <input type="hidden" name="groups[${groupIdx}][items][${itemIdx}][default]" value="0">
                <button type="button" class="btn-default" onclick="toggleDefault(this)">Não</button>
                <button type="button" class="btn-remove-item" onclick="removeItem(this)">✕</button>
            </div>
        </div>
    `;
    items.insertAdjacentHTML('beforeend', html);
}

// Adicionar grupo personalização
function addCustGroup() {
    const container = document.getElementById('custContainer');
    const idx = custGroupIndex++;
    
    const ingOptions = ingredients.map(i => {
        const priceStr = i.price > 0 ? ` (+R$ ${parseFloat(i.price).toFixed(2).replace('.', ',')})` : '';
        return `<option value="${i.id}">${i.name}${priceStr}</option>`;
    }).join('');
    
    const html = `
        <div class="group-card" data-cust-index="${idx}">
            <div class="group-header">
                <input type="text" name="customization[groups][${idx}][name]" placeholder="Nome do grupo">
                <button type="button" class="btn-remove-group" onclick="removeCustGroup(this)">✕</button>
            </div>
            <div class="group-items">
                <div class="mode-row">
                    <select name="customization[groups][${idx}][mode]" class="cust-mode-select" onchange="toggleChoiceSettings(this)">
                        <option value="extra">Adicionar livremente</option>
                        <option value="choice">Escolher ingrediente</option>
                        <option value="pool">Montagem (açaí, poke...)</option>
                    </select>
                </div>
                <div class="choice-settings hidden">
                    <div class="item-fields">
                        <div class="item-field"><label>Mín seleções</label><input type="number" name="customization[groups][${idx}][choice][min]" value="0" min="0"></div>
                        <div class="item-field"><label>Máx seleções</label><input type="number" name="customization[groups][${idx}][choice][max]" value="1" min="1"></div>
                    </div>
                </div>
                <div class="pool-settings hidden">
                    <div class="item-fields">
                        <div class="item-field"><label>Total mínimo</label><input type="number" name="customization[groups][${idx}][pool][min]" value="0" min="0"></div>
                        <div class="item-field"><label>Total máximo</label><input type="number" name="customization[groups][${idx}][pool][max]" value="4" min="1"></div>
                    </div>
                </div>
                <div class="group-item" data-cust-item="0">
                    <div class="item-select">
                        <select name="customization[groups][${idx}][items][0][ingredient_id]" required>
                            <option value="">— Ingrediente —</option>
                            ${ingOptions}
                        </select>
                    </div>
                    <div class="item-fields">
                        <div class="item-field"><label>Mín</label><input type="number" name="customization[groups][${idx}][items][0][min_qty]" value="0" min="0"></div>
                        <div class="item-field"><label>Máx</label><input type="number" name="customization[groups][${idx}][items][0][max_qty]" value="1" min="0"></div>
                        <div class="item-field"><label>Qtd</label><input type="number" name="customization[groups][${idx}][items][0][default_qty]" value="0" min="0"></div>
                        <input type="hidden" name="customization[groups][${idx}][items][0][default]" value="0">
                        <button type="button" class="btn-default" onclick="toggleDefault(this)">Não</button>
                        <button type="button" class="btn-remove-item" onclick="removeItem(this)">✕</button>
                    </div>
                </div>
            </div>
            <div class="group-footer">
                <button type="button" class="btn-add-item" onclick="addCustItem(this, ${idx})">+ Ingrediente</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

// Adicionar item personalização
function addCustItem(btn, groupIdx) {
    const items = btn.closest('.group-card').querySelector('.group-items');
    const existingItems = items.querySelectorAll('.group-item');
    const itemIdx = existingItems.length;
    
    const ingOptions = ingredients.map(i => {
        const priceStr = i.price > 0 ? ` (+R$ ${parseFloat(i.price).toFixed(2).replace('.', ',')})` : '';
        return `<option value="${i.id}">${i.name}${priceStr}</option>`;
    }).join('');
    
    const html = `
        <div class="group-item" data-cust-item="${itemIdx}">
            <div class="item-select">
                <select name="customization[groups][${groupIdx}][items][${itemIdx}][ingredient_id]" required>
                    <option value="">— Ingrediente —</option>
                    ${ingOptions}
                </select>
            </div>
            <div class="item-fields">
                <div class="item-field"><label>Mín</label><input type="number" name="customization[groups][${groupIdx}][items][${itemIdx}][min_qty]" value="0" min="0"></div>
                <div class="item-field"><label>Máx</label><input type="number" name="customization[groups][${groupIdx}][items][${itemIdx}][max_qty]" value="1" min="0"></div>
                <div class="item-field"><label>Qtd</label><input type="number" name="customization[groups][${groupIdx}][items][${itemIdx}][default_qty]" value="0" min="0"></div>
                <input type="hidden" name="customization[groups][${groupIdx}][items][${itemIdx}][default]" value="0">
                <button type="button" class="btn-default" onclick="toggleDefault(this)">Não</button>
                <button type="button" class="btn-remove-item" onclick="removeItem(this)">✕</button>
            </div>
        </div>
    `;
    items.insertAdjacentHTML('beforeend', html);
}

// Remover grupo
function removeGroup(btn) {
    if (confirm('Remover este grupo?')) {
        btn.closest('.group-card').remove();
    }
}

function removeCustGroup(btn) {
    if (confirm('Remover este grupo?')) {
        btn.closest('.group-card').remove();
    }
}

// Remover item
function removeItem(btn) {
    const item = btn.closest('.group-item');
    const items = item.parentElement.querySelectorAll('.group-item');
    if (items.length > 1) {
        item.remove();
    } else {
        alert('O grupo precisa de pelo menos um item.');
    }
}

// Confirmar exclusão
function confirmDelete() {
    if (confirm('Tem certeza que deseja excluir este produto?')) {
        document.getElementById('deleteForm').submit();
    }
}

// Init
updateDescCounter();

// ===== Copiar Grupo de Template =====
(function() {
    const allTemplates = <?= $custTemplatesJson ?>;
    const openBtn = document.getElementById('cust-copy-template');
    if (!openBtn) return;

    function initCopyModal() {
        const modal = document.getElementById('copy-template-modal');
        if (!modal) return false;

    const listEl = document.getElementById('copy-tpl-list');
    const searchEl = document.getElementById('copy-tpl-search');
    const countEl = document.getElementById('copy-tpl-count');
    const confirmEl = document.getElementById('copy-tpl-confirm');
    let selected = new Set();

    function esc(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    function render(filter) {
        filter = (filter || '').toLowerCase();
        const list = filter ? allTemplates.filter(t => t.name.toLowerCase().includes(filter)) : allTemplates;
        if (!list.length) {
            listEl.innerHTML = '<div style="text-align:center;padding:24px;color:#94a3b8;">Nenhum grupo encontrado</div>';
            return;
        }
        listEl.innerHTML = list.map(t => {
            const sel = selected.has(String(t.id));
            const count = (t.items && t.items.length) || 0;
            const typeLabel = t.type === 'extra' ? 'Adicional' : t.type === 'pool' ? 'Montagem' : t.type === 'substitute' ? 'Substituição' : 'Escolha';
            return `<div class="tpl-item${sel ? ' sel' : ''}" data-id="${t.id}" style="display:flex;align-items:center;gap:10px;padding:12px;border-radius:10px;border:1px solid ${sel ? 'var(--primary-color,#7c3aed)' : '#e2e8f0'};background:${sel ? '#f5f3ff' : '#f8fafc'};margin-bottom:8px;cursor:pointer;">
                <div style="width:20px;height:20px;border-radius:4px;border:2px solid ${sel ? 'var(--primary-color,#7c3aed)' : '#cbd5e1'};background:${sel ? 'var(--primary-color,#7c3aed)' : 'white'};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    ${sel ? '<svg width="12" height="12" viewBox="0 0 20 20" fill="white"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>' : ''}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:14px;color:#1e293b;">${esc(t.name)}</div>
                    <div style="font-size:11px;color:#64748b;margin-top:2px;">${count} itens · ${typeLabel}</div>
                </div>
            </div>`;
        }).join('');
        listEl.querySelectorAll('.tpl-item').forEach(el => {
            el.addEventListener('click', () => {
                const id = el.dataset.id;
                if (selected.has(id)) selected.delete(id); else selected.add(id);
                render(searchEl.value);
                updateCount();
            });
        });
    }

    function updateCount() {
        countEl.textContent = selected.size + ' selecionado(s)';
        confirmEl.disabled = selected.size === 0;
        confirmEl.style.opacity = selected.size ? '1' : '.5';
    }

    function openModal() {
        selected.clear();
        searchEl.value = '';
        render('');
        updateCount();
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.getElementById('copy-tpl-backdrop').addEventListener('click', closeModal);
    document.getElementById('copy-tpl-close').addEventListener('click', closeModal);
    document.getElementById('copy-tpl-cancel').addEventListener('click', closeModal);

    let debounce;
    searchEl.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => render(searchEl.value), 200);
    });

    confirmEl.addEventListener('click', () => {
        if (!selected.size) return;
        let added = 0;
        for (const id of selected) {
            const tpl = allTemplates.find(t => String(t.id) === String(id));
            if (tpl) { addTemplateAsGroup(tpl); added++; }
        }
        closeModal();
        if (added > 0) alert(added + ' grupo(s) adicionado(s)!');
    });

    function addTemplateAsGroup(tpl) {
        const container = document.getElementById('custContainer');
        if (!container) return;
        const idx = custGroupIndex++;

        const ingOptions = ingredients.map(i => {
            const ps = i.price > 0 ? ` (+R$ ${parseFloat(i.price).toFixed(2).replace('.', ',')})` : '';
            return `<option value="${i.id}">${esc(i.name)}${ps}</option>`;
        }).join('');

        const mode = (tpl.type === 'single' || tpl.type === 'addon') ? 'choice' : (tpl.type === 'pool' ? 'pool' : 'extra');
        const minQ = tpl.min_qty ?? 0;
        const maxQ = tpl.max_qty ?? 99;

        let itemsHtml = '';
        if (tpl.items && tpl.items.length) {
            tpl.items.forEach((item, ii) => {
                const selId = item.ingredient_id || '';
                const opts = ingredients.map(i => {
                    const ps = i.price > 0 ? ` (+R$ ${parseFloat(i.price).toFixed(2).replace('.', ',')})` : '';
                    return `<option value="${i.id}" ${String(i.id) === String(selId) ? 'selected' : ''}>${esc(i.name)}${ps}</option>`;
                }).join('');
                itemsHtml += `
                <div class="group-item" data-cust-item="${ii}">
                    <div class="item-select">
                        <select name="customization[groups][${idx}][items][${ii}][ingredient_id]" required>
                            <option value="">— Ingrediente —</option>
                            ${opts}
                        </select>
                    </div>
                    <div class="item-fields">
                        <div class="item-field"><label>Mín</label><input type="number" name="customization[groups][${idx}][items][${ii}][min_qty]" value="${item.min_qty || 0}" min="0"></div>
                        <div class="item-field"><label>Máx</label><input type="number" name="customization[groups][${idx}][items][${ii}][max_qty]" value="${item.max_qty || 1}" min="0"></div>
                        <div class="item-field"><label>Qtd</label><input type="number" name="customization[groups][${idx}][items][${ii}][default_qty]" value="${item.default_qty || 0}" min="0"></div>
                        <input type="hidden" name="customization[groups][${idx}][items][${ii}][default]" value="${item.is_default ? '1' : '0'}">
                        <button type="button" class="btn-default ${item.is_default ? 'active' : ''}" onclick="toggleDefault(this)">${item.is_default ? 'Sim' : 'Não'}</button>
                        <button type="button" class="btn-remove-item" onclick="removeItem(this)">✕</button>
                    </div>
                </div>`;
            });
        } else {
            itemsHtml = `
            <div class="group-item" data-cust-item="0">
                <div class="item-select">
                    <select name="customization[groups][${idx}][items][0][ingredient_id]" required>
                        <option value="">— Ingrediente —</option>
                        ${ingOptions}
                    </select>
                </div>
                <div class="item-fields">
                    <div class="item-field"><label>Mín</label><input type="number" name="customization[groups][${idx}][items][0][min_qty]" value="0" min="0"></div>
                    <div class="item-field"><label>Máx</label><input type="number" name="customization[groups][${idx}][items][0][max_qty]" value="1" min="0"></div>
                    <div class="item-field"><label>Qtd</label><input type="number" name="customization[groups][${idx}][items][0][default_qty]" value="0" min="0"></div>
                    <input type="hidden" name="customization[groups][${idx}][items][0][default]" value="0">
                    <button type="button" class="btn-default" onclick="toggleDefault(this)">Não</button>
                    <button type="button" class="btn-remove-item" onclick="removeItem(this)">✕</button>
                </div>
            </div>`;
        }

        const html = `
        <div class="group-card" data-cust-index="${idx}">
            <div class="group-header">
                <input type="text" name="customization[groups][${idx}][name]" value="${esc(tpl.name || '')}" placeholder="Nome do grupo">
                <button type="button" class="btn-remove-group" onclick="removeCustGroup(this)">✕</button>
            </div>
            <div class="group-items">
                <div class="mode-row">
                    <select name="customization[groups][${idx}][mode]" class="cust-mode-select" onchange="toggleChoiceSettings(this)">
                        <option value="extra" ${mode === 'extra' ? 'selected' : ''}>Adicionar livremente</option>
                        <option value="choice" ${mode === 'choice' ? 'selected' : ''}>Escolher ingrediente</option>
                        <option value="pool" ${mode === 'pool' ? 'selected' : ''}>Montagem (açaí, poke...)</option>
                    </select>
                </div>
                <div class="choice-settings ${mode !== 'choice' ? 'hidden' : ''}">
                    <div class="item-fields">
                        <div class="item-field"><label>Mín seleções</label><input type="number" name="customization[groups][${idx}][choice][min]" value="${minQ}" min="0"></div>
                        <div class="item-field"><label>Máx seleções</label><input type="number" name="customization[groups][${idx}][choice][max]" value="${maxQ}" min="1"></div>
                    </div>
                </div>
                <div class="pool-settings ${mode !== 'pool' ? 'hidden' : ''}">
                    <div class="item-fields">
                        <div class="item-field"><label>Total mínimo</label><input type="number" name="customization[groups][${idx}][pool][min]" value="${mode === 'pool' ? minQ : '0'}" min="0"></div>
                        <div class="item-field"><label>Total máximo</label><input type="number" name="customization[groups][${idx}][pool][max]" value="${mode === 'pool' ? maxQ : '4'}" min="1"></div>
                    </div>
                </div>
                ${itemsHtml}
            </div>
            <div class="group-footer">
                <button type="button" class="btn-add-item" onclick="addCustItem(this, ${idx})">+ Ingrediente</button>
            </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);

        // Ativar personalização se desligada
        const toggle = document.getElementById('custToggle');
        const hidden = document.getElementById('custEnabledHidden');
        const wrap = document.getElementById('custWrap');
        if (toggle && !toggle.classList.contains('active')) {
            toggle.classList.add('active');
            hidden.value = '1';
            wrap.classList.add('visible');
        }
    }

    return { openModal: openModal };
    } // end initCopyModal

    let copyApi = null;
    openBtn.addEventListener('click', function() {
        if (!copyApi) copyApi = initCopyModal();
        if (copyApi && copyApi.openModal) {
            copyApi.openModal();
        } else if (!allTemplates.length) {
            alert('Nenhum grupo de personalização cadastrado. Crie em "Grupos Personalizados" no menu.');
        }
    });
})();
</script>

<!-- Modal: Copiar Grupo de Personalização -->
<div id="copy-template-modal" style="display:none; position:fixed; inset:0; z-index:1000; align-items:flex-end; justify-content:center;">
    <div id="copy-tpl-backdrop" style="position:absolute; inset:0; background:rgba(0,0,0,.5);"></div>
    <div style="position:relative; width:100%; max-height:85vh; background:white; border-radius:16px 16px 0 0; display:flex; flex-direction:column; animation:slideUp .2s ease-out;">
        <!-- Header -->
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px; border-bottom:1px solid #e2e8f0;">
            <div>
                <div style="font-size:16px; font-weight:700; color:#1e293b;">Copiar Grupo</div>
                <div style="font-size:12px; color:#64748b;">Selecione os grupos para adicionar</div>
            </div>
            <button type="button" id="copy-tpl-close" style="padding:8px; border:none; background:none; color:#94a3b8; cursor:pointer;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <!-- Search -->
        <div style="padding:12px 16px; border-bottom:1px solid #f1f5f9;">
            <input type="text" id="copy-tpl-search" placeholder="Buscar grupos..." style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; outline:none; box-sizing:border-box;">
        </div>
        <!-- List -->
        <div id="copy-tpl-list" style="flex:1; overflow-y:auto; padding:12px 16px; max-height:50vh;"></div>
        <!-- Footer -->
        <div style="padding:12px 16px; border-top:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between;">
            <span id="copy-tpl-count" style="font-size:13px; color:#64748b;">0 selecionado(s)</span>
            <div style="display:flex; gap:8px;">
                <button type="button" id="copy-tpl-cancel" style="padding:10px 16px; font-size:13px; font-weight:600; border:1px solid #e2e8f0; border-radius:8px; background:white; color:#64748b; cursor:pointer;">Cancelar</button>
                <button type="button" id="copy-tpl-confirm" disabled style="padding:10px 16px; font-size:13px; font-weight:600; border:none; border-radius:8px; background:var(--primary-color,#7c3aed); color:white; cursor:pointer; opacity:.5;">Adicionar</button>
            </div>
        </div>
    </div>
</div>
<style>
@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
