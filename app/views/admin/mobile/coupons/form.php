<?php
/**
 * Formulário Criar/Editar Cupom - Mobile
 */
$isEdit = !empty($coupon['id']);
$title = $isEdit ? 'Editar Cupom' : 'Novo Cupom';
?>

<style>
.form-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.form-group {
    margin-bottom: 18px;
}
.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}
.form-label .required { color: #dc2626; }
.form-input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 15px;
    color: #1e293b;
    background: #f8fafc;
    outline: none;
    box-sizing: border-box;
    transition: border-color .2s;
}
.form-input:focus {
    border-color: var(--primary, #7c3aed);
    background: #fff;
    box-shadow: 0 0 0 3px var(--primary-light, rgba(124,58,237,.1));
}
.form-hint {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.toggle-group {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
}
.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute;
    inset: 0;
    background: #cbd5e1;
    border-radius: 24px;
    cursor: pointer;
    transition: .3s;
}
.toggle-slider::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    left: 2px;
    bottom: 2px;
    background: white;
    border-radius: 50%;
    transition: .3s;
}
.toggle-switch input:checked + .toggle-slider {
    background: var(--primary, #7c3aed);
}
.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(20px);
}
.toggle-label {
    font-size: 14px;
    color: #475569;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}
.btn-cancel {
    flex: 1;
    padding: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    color: #64748b;
    font-size: 15px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
}
.btn-save {
    flex: 2;
    padding: 14px;
    border: none;
    border-radius: 12px;
    background: var(--primary, #7c3aed);
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(124,58,237,.3);
}

.usage-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 18px;
}
.usage-stat {
    background: #f0f9ff;
    border-radius: 12px;
    padding: 12px;
    text-align: center;
}
.usage-stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
}
.usage-stat-label {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}

.status-info {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 18px;
    font-size: 13px;
    color: #0369a1;
}
.status-info strong { color: #0c4a6e; }

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    font-size: 14px;
}
</style>

<?php $activeCouponTab = $isEdit ? 'list' : 'create'; ?>
<?php require __DIR__ . '/coupons-nav.php'; ?>

<?php if (!empty($error)): ?>
<div class="alert-error" style="display:flex; align-items:center; gap:8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
<?php endif; ?>

<?php if ($isEdit && !empty($usage_stats)): ?>
<div class="usage-stats">
    <div class="usage-stat">
        <div class="usage-stat-value"><?= (int)($usage_stats['unique_customers'] ?? 0) ?></div>
        <div class="usage-stat-label">Clientes Únicos</div>
    </div>
    <div class="usage-stat">
        <div class="usage-stat-value"><?= (int)($usage_stats['total_uses'] ?? 0) ?></div>
        <div class="usage-stat-label">Total de Usos</div>
    </div>
</div>
<?php endif; ?>

<?php if ($isEdit): ?>
<div class="status-info">
    <strong>Uso:</strong> <?= (int)($coupon['times_used'] ?? 0) ?>/<?= (int)($coupon['usage_limit'] ?? 0) ?> ·
    <strong>Status:</strong> <?= (int)($coupon['is_used'] ?? 0) === 1 ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="#dc2626" stroke="none"><circle cx="12" cy="12" r="10"/></svg> Esgotado' : '<svg width="12" height="12" viewBox="0 0 24 24" fill="#16a34a" stroke="none"><circle cx="12" cy="12" r="10"/></svg> Disponível' ?>
    <?php if (!empty($coupon['used_at'])): ?>
    · <strong>Último uso:</strong> <?= date('d/m/Y H:i', strtotime($coupon['used_at'])) ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= $isEdit ? '/coupons/' . (int)$coupon['id'] : '/coupons' ?>" class="form-card">

    <div class="form-group">
        <label class="form-label">Código do Cupom <span class="required">*</span><a href="/guide/coupons#form" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
        <input type="text" name="code" class="form-input" value="<?= htmlspecialchars($coupon['coupon_code'] ?? '') ?>"
               placeholder="Ex: PRIMEIRACOMPRA10" maxlength="50" style="text-transform:uppercase" <?= $isEdit ? 'required' : '' ?>>
        <div class="form-hint">Deixe em branco para gerar automaticamente</div>
    </div>

    <div class="form-group">
        <label class="form-label">Telefone do Cliente (Opcional)<a href="/guide/coupons#types" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
        <input type="text" name="customer_phone" class="form-input" value="<?= htmlspecialchars($coupon['customer_phone'] ?? '') ?>"
               placeholder="Ex: 11999999999" maxlength="20">
        <div class="form-hint">Deixe em branco para cupom genérico</div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Desconto (%) <span class="required">*</span><a href="/guide/coupons#form" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
            <input type="number" name="discount_percentage" class="form-input"
                   value="<?= htmlspecialchars($coupon['discount_percentage'] ?? '') ?>"
                   min="1" max="100" step="0.01" required placeholder="10">
        </div>
        <div class="form-group">
            <label class="form-label">Limite de Usos <span class="required">*</span><a href="/guide/coupons#limits" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
            <input type="number" name="usage_limit" class="form-input"
                   value="<?= htmlspecialchars($coupon['usage_limit'] ?? '1') ?>"
                   min="1" max="1000" required placeholder="1">
        </div>
    </div>

    <div class="toggle-group">
        <label class="toggle-switch">
            <input type="checkbox" name="allow_multiple_uses_per_customer" value="1"
                   <?= !empty($coupon['allow_multiple_uses_per_customer']) && (int)$coupon['allow_multiple_uses_per_customer'] === 1 ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
        <span class="toggle-label">Permitir múltiplos usos por cliente</span>
    </div>

    <div class="form-actions">
        <a href="/coupons" class="btn-cancel">Cancelar</a>
        <button type="submit" class="btn-save"><?= $isEdit ? 'Atualizar' : 'Criar Cupom' ?></button>
    </div>
</form>
