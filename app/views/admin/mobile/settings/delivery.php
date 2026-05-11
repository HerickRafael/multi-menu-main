<?php
/**
 * Configurações de Entrega Mobile
 * Design igual ao desktop, adaptado para toque
 */
$cities = $cities ?? [];
$zones = $zones ?? [];
$zoneCountByCity = $zoneCountByCity ?? [];
$editCity = $editCity ?? null;
$editZone = $editZone ?? null;

ob_start();
?>

<style>
/* Tabs - estilo grid card */
.delivery-tabs {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    margin-bottom: 16px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
}

.delivery-tab {
    padding: 12px 4px;
    border: none;
    background: none;
    font-size: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    line-height: 1.1;
    text-align: center;
}

.delivery-tab.active {
    background: var(--admin-primary-color, #4361ee);
    color: #fff;
    font-weight: 600;
    border-bottom-color: var(--admin-primary-color, #4361ee);
}

.delivery-tab:not(.active) {
    background: none;
    color: #6b7280;
}

.section-panel {
    display: none;
}

.section-panel.active {
    display: block;
}

/* Cards */
.delivery-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.delivery-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.delivery-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.delivery-card-icon.purple { background: var(--admin-primary-soft, #ede9fe); color: var(--admin-primary-color, #7c3aed); }
.delivery-card-icon.amber { background: #fef3c7; color: #d97706; }
.delivery-card-icon.green { background: #d1fae5; color: #059669; }
.delivery-card-icon.blue { background: #dbeafe; color: #2563eb; }

.delivery-card-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

/* Form inputs */
.form-group {
    margin-bottom: 12px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 16px;
    background: white;
    color: #1f2937;
    -webkit-appearance: none;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.1);
}

.form-select {
    width: 100%;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 16px;
    background: white;
    color: #1f2937;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 20px;
    padding-right: 40px;
}

.form-hint {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
}

/* Buttons */
.btn-primary {
    width: 100%;
    padding: 14px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary:active {
    transform: scale(0.98);
}

.btn-secondary {
    width: 100%;
    padding: 12px;
    background: white;
    color: #374151;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    margin-top: 8px;
}

.btn-danger {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}

/* List items */
.list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px;
    background: white;
    border-radius: 12px;
    margin-bottom: 8px;
    border: 1px solid #e5e7eb;
}

.list-item-content {
    flex: 1;
}

.list-item-title {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

.list-item-subtitle {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
}

.list-item-actions {
    display: flex;
    gap: 8px;
}

.list-item-action {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.list-item-action.edit { color: #6b7280; }
.list-item-action.delete { color: #dc2626; background: #fef2f2; border-color: #fecaca; }

/* Zone with fee */
.zone-fee {
    font-size: 15px;
    font-weight: 700;
    color: var(--primary);
    white-space: nowrap;
    margin-left: 12px;
}

.zone-fee.free {
    color: #059669;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 32px 16px;
    color: #9ca3af;
}

.empty-state svg {
    margin: 0 auto 12px;
    color: #d1d5db;
}

.empty-state p {
    font-size: 14px;
}

/* Grouped zones */
.city-group {
    margin-bottom: 16px;
}

.city-group-header {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 4px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 8px;
}

/* Flash messages */
.flash-success {
    background: #d1fae5;
    color: #065f46;
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    font-size: 14px;
}

.flash-error {
    background: #fef2f2;
    color: #991b1b;
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    font-size: 14px;
}

/* Adjust input row */
.input-row {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.input-row .form-group {
    flex: 1;
    margin-bottom: 0;
}

.input-row .btn-primary {
    width: auto;
    padding: 12px 16px;
    flex-shrink: 0;
}

/* Toggle switch */
.toggle-switch {
    position: relative;
    width: 48px;
    height: 28px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #d1d5db;
    border-radius: 28px;
    transition: 0.3s;
}

.toggle-slider:before {
    content: "";
    position: absolute;
    width: 22px;
    height: 22px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: 0.3s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

.toggle-switch input:checked + .toggle-slider {
    background: #059669;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

/* Status badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #f1f5f9;
    color: #64748b;
}

/* Info alert */
.info-alert {
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    margin-top: 12px;
}

.info-alert.success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.info-alert.warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
}

/* Search Bar Design */
.search-container {
    margin-bottom: 12px;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    color: #1f2937;
}

.search-input::placeholder {
    color: #9ca3af;
}

.search-input:focus {
    outline: none;
    border-color: #d1d5db;
}

.search-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--primary, #7c3aed);
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.search-btn:active {
    opacity: 0.85;
    transform: scale(0.95);
}

.search-btn svg {
    width: 20px;
    height: 20px;
}

.search-no-results {
    text-align: center;
    padding: 24px 16px;
    color: #9ca3af;
    font-size: 14px;
    display: none;
}
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="flash-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="flash-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Tabs de navegação -->
<div class="delivery-tabs">
    <button class="delivery-tab active" onclick="switchDeliveryTab('ajustes')" data-tab="ajustes">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
        </svg>
        Ajustes
    </button>
    <button class="delivery-tab" onclick="switchDeliveryTab('cidades')" data-tab="cidades">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
        </svg>
        Cidades
    </button>
    <button class="delivery-tab" onclick="switchDeliveryTab('bairros')" data-tab="bairros">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
            <circle cx="12" cy="11" r="3"/>
        </svg>
        Bairros
    </button>
</div>

<!-- PAINEL 1: AJUSTES RÁPIDOS -->
<div class="section-panel active" id="panel-ajustes">
    
    <!-- Ajuste em Lote -->
    <div class="delivery-card">
        <div class="delivery-card-header">
            <div class="delivery-card-icon purple">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 6v12M6 12h12"/>
                </svg>
            </div>
            <div class="delivery-card-title">Ajuste em Lote</div>
        </div>
        
        <form method="POST" action="/settings/delivery/adjust">
            <div class="form-group">
                <label class="form-label">Valor do ajuste (R$) <a href="/guide/delivery-fees#ajustes" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <input type="number" step="0.01" name="delta" class="form-input" placeholder="Ex: 2.00 ou -1.50">
                <div class="form-hint">Positivo = aumentar | Negativo = diminuir todas as taxas</div>
            </div>
            <button type="submit" class="btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 7L9 18l-5-5"/>
                </svg>
                Aplicar Ajuste
            </button>
        </form>
    </div>
    
    <!-- Resumo -->
    <div class="delivery-card">
        <div class="delivery-card-header">
            <div class="delivery-card-icon blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="1"/>
                </svg>
            </div>
            <div class="delivery-card-title">Resumo</div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div style="text-align: center; padding: 12px; background: #f9fafb; border-radius: 12px;">
                <div style="font-size: 24px; font-weight: 700; color: var(--primary);"><?= count($cities) ?></div>
                <div style="font-size: 12px; color: #6b7280;">Cidades</div>
            </div>
            <div style="text-align: center; padding: 12px; background: #f9fafb; border-radius: 12px;">
                <div style="font-size: 24px; font-weight: 700; color: var(--primary);"><?= count($zones) ?></div>
                <div style="font-size: 12px; color: #6b7280;">Bairros</div>
            </div>
        </div>
    </div>

    <!-- Taxa Após 18h -->
    <div class="delivery-card">
        <div class="delivery-card-header">
            <div class="delivery-card-icon amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
            </div>
            <div class="delivery-card-title">Taxa Após 18h</div>
        </div>
        
        <form method="POST" action="/settings/delivery/options">
            <input type="hidden" name="free_delivery" value="<?= (int)($company['delivery_free_enabled'] ?? 0) ?>">
            <div class="form-group">
                <label class="form-label">Adicional (R$) <a href="/guide/delivery-fees#ajustes" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <input type="number" step="0.01" min="0" name="after_hours_fee" class="form-input"
                       value="<?= number_format((float)($company['delivery_after_hours_fee'] ?? 0), 2, '.', '') ?>"
                       placeholder="0.00">
                <div class="form-hint">Valor somado automaticamente às entregas após as 18h</div>
            </div>
            <button type="submit" class="btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 7L9 18l-5-5"/>
                </svg>
                Salvar Adicional
            </button>
        </form>
    </div>

    <!-- Taxa Gratuita (toggle) -->
    <div class="delivery-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="delivery-card-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="delivery-card-title">Taxa Gratuita <a href="/guide/delivery-fees#ajustes" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></div>
            </div>
            <form method="POST" action="/settings/delivery/options" id="freeDeliveryForm">
                <input type="hidden" name="after_hours_fee" value="<?= number_format((float)($company['delivery_after_hours_fee'] ?? 0), 2, '.', '') ?>">
                <input type="hidden" name="free_delivery" value="<?= (int)($company['delivery_free_enabled'] ?? 0) ? 0 : 1 ?>">
                <label class="toggle-switch">
                    <input type="checkbox" <?= (int)($company['delivery_free_enabled'] ?? 0) ? 'checked' : '' ?> 
                           onchange="document.getElementById('freeDeliveryForm').submit()">
                    <span class="toggle-slider"></span>
                </label>
            </form>
        </div>
        <div style="margin-top: 12px;">
            <?php if ((int)($company['delivery_free_enabled'] ?? 0)): ?>
                <span class="status-badge active">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                    Ativado — Todas as entregas estão gratuitas
                </span>
                <div class="info-alert warning" style="margin-top: 10px;">
                    <strong>⚠️ Atenção:</strong> O frete grátis promocional foi desativado automaticamente.
                </div>
            <?php else: ?>
                <span class="status-badge inactive">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                    Desativado — Taxas aplicadas normalmente
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Frete Grátis Promocional -->
    <div class="delivery-card">
        <div class="delivery-card-header">
            <div class="delivery-card-icon green">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
                </svg>
            </div>
            <div class="delivery-card-title">Frete Grátis Promocional</div>
        </div>
        
        <form method="POST" action="/settings/delivery/free-shipping">
            <div class="form-group">
                <label class="form-label">Valor mínimo do pedido (R$) <a href="/guide/delivery-fees#ajustes" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <input type="number" step="0.01" min="0" name="delivery_free_min_value" class="form-input"
                       value="<?= number_format((float)($company['delivery_free_min_value'] ?? 0), 2, '.', '') ?>"
                       placeholder="Ex: 50.00">
                <div class="form-hint">Ao atingir este valor, o frete é grátis. 0 = desativado.</div>
            </div>
            <button type="submit" class="btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 7L9 18l-5-5"/>
                </svg>
                Salvar Promoção
            </button>
        </form>
        
        <?php if ((float)($company['delivery_free_min_value'] ?? 0) > 0): ?>
            <div class="info-alert success">
                <strong>✓ Ativo:</strong> Frete grátis em pedidos acima de R$ <?= number_format((float)$company['delivery_free_min_value'], 2, ',', '.') ?>
            </div>
            <div class="info-alert warning">
                <strong>⚠️ Atenção:</strong> A taxa gratuita para todos foi desativada automaticamente.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- PAINEL 2: CIDADES -->
<div class="section-panel" id="panel-cidades">
    
    <!-- Formulário Cidade -->
    <div class="delivery-card">
        <div class="delivery-card-header">
            <div class="delivery-card-icon amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 6v12M6 12h12"/>
                </svg>
            </div>
            <div class="delivery-card-title"><?= $editCity ? 'Editar Cidade' : 'Nova Cidade' ?></div>
        </div>
        
        <form method="POST" action="/settings/delivery/city">
            <?php if ($editCity): ?>
                <input type="hidden" name="id" value="<?= (int)$editCity['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label">Nome da cidade *</label>
                <input type="text" name="name" class="form-input" required
                       value="<?= htmlspecialchars($editCity['name'] ?? '') ?>"
                       placeholder="Ex: São Paulo">
            </div>
            
            <button type="submit" class="btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 7L9 18l-5-5"/>
                </svg>
                <?= $editCity ? 'Atualizar Cidade' : 'Cadastrar Cidade' ?>
            </button>
            
            <?php if ($editCity): ?>
                <a href="/settings/delivery" class="btn-secondary" style="display: block; text-align: center; text-decoration: none;">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Lista de Cidades -->
    <div style="margin-top: 16px;">
        <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 8px;">
            Cidades cadastradas (<?= count($cities) ?>)
        </div>
        
        <?php if (empty($cities)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
                </svg>
                <p>Nenhuma cidade cadastrada</p>
            </div>
        <?php else: ?>
            <?php foreach ($cities as $city): ?>
                <div class="list-item">
                    <div class="list-item-content">
                        <div class="list-item-title"><?= htmlspecialchars($city['name']) ?></div>
                        <div class="list-item-subtitle"><?= (int)($zoneCountByCity[(int)$city['id']] ?? 0) ?> bairro(s)</div>
                    </div>
                    <div class="list-item-actions">
                        <a href="/settings/delivery?edit_city=<?= (int)$city['id'] ?>" class="list-item-action edit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>
                        <form method="POST" action="/settings/delivery/city/<?= (int)$city['id'] ?>/delete" style="margin: 0;"
                              onsubmit="return confirm('Excluir cidade e todos os bairros vinculados?')">
                            <button type="submit" class="list-item-action delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- PAINEL 3: BAIRROS -->
<div class="section-panel" id="panel-bairros">
    
    <?php if (empty($cities)): ?>
        <div class="delivery-card">
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>Cadastre uma cidade primeiro antes de adicionar bairros.</p>
            </div>
        </div>
    <?php else: ?>
        <!-- Formulário Bairro -->
        <div class="delivery-card">
            <div class="delivery-card-header">
                <div class="delivery-card-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 6v12M6 12h12"/>
                    </svg>
                </div>
                <div class="delivery-card-title"><?= $editZone ? 'Editar Bairro' : 'Novo Bairro' ?></div>
            </div>
            
            <form method="POST" action="/settings/delivery/zone">
                <?php if ($editZone): ?>
                    <input type="hidden" name="id" value="<?= (int)$editZone['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Cidade *</label>
                    <select name="city_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= (int)$city['id'] ?>" <?= ($editZone && (int)$editZone['city_id'] == (int)$city['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nome do bairro *</label>
                    <input type="text" name="neighborhood" class="form-input" required
                           value="<?= htmlspecialchars($editZone['neighborhood'] ?? '') ?>"
                           placeholder="Ex: Centro">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Taxa de entrega (R$)</label>
                    <input type="number" step="0.01" min="0" name="fee" class="form-input"
                           value="<?= number_format((float)($editZone['fee'] ?? 0), 2, '.', '') ?>"
                           placeholder="0.00">
                    <div class="form-hint">Deixe 0 para entrega grátis neste bairro</div>
                </div>
                
                <button type="submit" class="btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M20 7L9 18l-5-5"/>
                    </svg>
                    <?= $editZone ? 'Atualizar Bairro' : 'Cadastrar Bairro' ?>
                </button>
                
                <?php if ($editZone): ?>
                    <a href="/settings/delivery" class="btn-secondary" style="display: block; text-align: center; text-decoration: none;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Lista de Bairros agrupados por cidade -->
        <div style="margin-top: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">
                    Bairros cadastrados
                </div>
                <div style="font-size: 12px; font-weight: 600; color: var(--primary);">Total: <?= count($zones) ?></div>
            </div>
            
            <div class="search-container">
                <div class="search-box">
                    <input type="text" id="searchBairro" placeholder="Buscar por bairro ou cidade..." class="search-input" oninput="filterBairros()">
                    <button type="button" class="search-btn" onclick="filterBairros()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m20 20-3.5-3.5"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <?php if (empty($zones)): ?>
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                        <circle cx="12" cy="11" r="3"/>
                    </svg>
                    <p>Nenhum bairro cadastrado</p>
                </div>
            <?php else: ?>
                <?php 
                // Agrupar por cidade
                $groupedZones = [];
                foreach ($zones as $zone) {
                    $cityName = $zone['city_name'] ?? 'Sem cidade';
                    if (!isset($groupedZones[$cityName])) {
                        $groupedZones[$cityName] = [];
                    }
                    $groupedZones[$cityName][] = $zone;
                }
                ?>
                
                <?php foreach ($groupedZones as $cityName => $cityZones): ?>
                    <div class="city-group">
                        <div class="city-group-header"><?= htmlspecialchars($cityName) ?></div>
                        
                        <?php foreach ($cityZones as $zone): ?>
                            <div class="list-item">
                                <div class="list-item-content">
                                    <div class="list-item-title"><?= htmlspecialchars($zone['neighborhood']) ?></div>
                                </div>
                                <span class="zone-fee <?= (float)$zone['fee'] == 0 ? 'free' : '' ?>">
                                    <?= (float)$zone['fee'] > 0 ? 'R$ ' . number_format((float)$zone['fee'], 2, ',', '.') : 'Grátis' ?>
                                </span>
                                <div class="list-item-actions">
                                    <a href="/settings/delivery?edit_zone=<?= (int)$zone['id'] ?>" class="list-item-action edit">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="/settings/delivery/zone/<?= (int)$zone['id'] ?>/delete" style="margin: 0;"
                                          onsubmit="return confirm('Excluir este bairro?')">
                                        <button type="submit" class="list-item-action delete">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function filterBairros() {
    const query = (document.getElementById('searchBairro')?.value || '').toLowerCase().trim();
    const panel = document.getElementById('panel-bairros');
    if (!panel) return;
    
    const groups = panel.querySelectorAll('.city-group');
    let totalVisible = 0;
    
    groups.forEach(group => {
        const cityHeader = group.querySelector('.city-group-header');
        const cityName = (cityHeader?.textContent || '').toLowerCase();
        const items = group.querySelectorAll('.list-item');
        let groupVisible = 0;
        
        items.forEach(item => {
            const bairroName = (item.querySelector('.list-item-title')?.textContent || '').toLowerCase();
            const matches = !query || bairroName.includes(query) || cityName.includes(query);
            item.style.display = matches ? '' : 'none';
            if (matches) groupVisible++;
        });
        
        group.style.display = groupVisible > 0 ? '' : 'none';
        totalVisible += groupVisible;
    });
    
    // Mostrar/esconder mensagem de nenhum resultado
    let noResults = panel.querySelector('.search-no-results');
    if (!noResults) {
        const listContainer = panel.querySelector('.city-group')?.parentElement;
        if (listContainer) {
            noResults = document.createElement('div');
            noResults.className = 'search-no-results';
            noResults.textContent = 'Nenhum bairro encontrado para esta busca.';
            listContainer.appendChild(noResults);
        }
    }
    if (noResults) {
        noResults.style.display = (query && totalVisible === 0) ? 'block' : 'none';
    }
}

function switchDeliveryTab(tabName) {
    // Remover active de todos os tabs
    document.querySelectorAll('.delivery-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Esconder todos os painéis
    document.querySelectorAll('.section-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    
    // Ativar tab selecionada
    document.querySelector('.delivery-tab[data-tab="' + tabName + '"]').classList.add('active');
    
    // Mostrar painel selecionado
    document.getElementById('panel-' + tabName).classList.add('active');
}

// Se estiver editando, abrir a aba correta
<?php if ($editCity): ?>
    switchDeliveryTab('cidades');
<?php elseif ($editZone): ?>
    switchDeliveryTab('bairros');
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
