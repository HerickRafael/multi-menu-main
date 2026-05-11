<?php
/**
 * Formulário Despesa - Mobile
 */
$isEdit = !empty($expense['id']);
$title = $isEdit ? 'Editar Despesa' : 'Nova Despesa';
?>

<style>
.form-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
.form-label .req { color: #dc2626; }
.form-input, .form-select, .form-textarea {
    width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 12px;
    font-size: 15px; color: #1e293b; background: #f8fafc; outline: none; box-sizing: border-box;
}
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--primary, #7c3aed); background: #fff; }
.form-textarea { resize: vertical; min-height: 80px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-actions { display: flex; gap: 10px; margin-top: 20px; }
.btn-cancel { flex: 1; padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: white; color: #64748b; font-size: 15px; font-weight: 600; text-align: center; text-decoration: none; }
.btn-save { flex: 2; padding: 14px; border: none; border-radius: 12px; background: var(--primary, #7c3aed); color: white; font-size: 15px; font-weight: 600; cursor: pointer; }
.alert-error { background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 14px; }
</style>

<?php if (!empty($error)): ?>
<div class="alert-error" style="display:flex; align-items:center; gap:8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
<?php endif; ?>

<form method="post" action="<?= $isEdit ? '/expenses/' . (int)$expense['id'] : '/expenses' ?>" class="form-card">

    <div class="form-group">
        <label class="form-label">Descrição <span class="req">*</span></label>
        <input type="text" name="description" class="form-input" required
               value="<?= htmlspecialchars($expense['description'] ?? '') ?>" placeholder="Ex: Aluguel, Energia, Fornecedor...">
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Valor (R$) <span class="req">*</span></label>
            <input type="text" name="amount" class="form-input" required
                   value="<?= htmlspecialchars($expense['amount'] ?? '') ?>" placeholder="0,00"
                   inputmode="decimal">
        </div>
        <div class="form-group">
            <label class="form-label">Categoria</label>
            <select name="category_id" class="form-select">
                <option value="">Sem categoria</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= ($expense['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Mês de Referência</label>
            <input type="month" name="reference_month" class="form-input"
                   value="<?= htmlspecialchars(!empty($expense['expense_date']) ? substr($expense['expense_date'], 0, 7) : date('Y-m')) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Data Pagamento</label>
            <input type="date" name="payment_date" class="form-input"
                   value="<?= htmlspecialchars($expense['expense_date'] ?? '') ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Forma de Pagamento</label>
        <select name="payment_method" class="form-select">
            <option value="">-</option>
            <?php foreach (['pix' => 'PIX', 'cash' => 'Dinheiro', 'credit' => 'Crédito', 'debit' => 'Débito', 'transfer' => 'Transferência', 'boleto' => 'Boleto'] as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= ($expense['payment_method'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Observações</label>
        <textarea name="notes" class="form-textarea" placeholder="Notas adicionais..."><?= htmlspecialchars($expense['notes'] ?? '') ?></textarea>
    </div>

    <div class="form-actions">
        <a href="/expenses" class="btn-cancel">Cancelar</a>
        <button type="submit" class="btn-save"><?= $isEdit ? 'Atualizar' : 'Adicionar' ?></button>
    </div>
</form>
