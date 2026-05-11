<?php
/**
 * Lista de Despesas - Mobile
 */
$fmt = function($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); };
$totalExpenses = (float)($summary['total'] ?? 0);
$fixedExpenses = (float)($summary['fixed'] ?? 0);
$variableExpenses = (float)($summary['variable'] ?? 0);
?>

<style>
.fin-nav { display: flex; gap: 6px; margin-bottom: 16px; overflow-x: auto; }
.fin-nav a { flex-shrink: 0; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; background: white; color: #64748b; border: 1px solid #e2e8f0; }
.fin-nav a.active { background: var(--primary, #7c3aed); color: white; border-color: var(--primary); }
.month-selector { margin-bottom: 16px; }
.month-select { width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 15px; background: white; color: #1e293b; }

.expense-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
.expense-stat { background: white; border-radius: 14px; padding: 14px 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.expense-stat-value { font-size: 18px; font-weight: 700; color: #1e293b; }
.expense-stat-value.total { color: #dc2626; }
.expense-stat-label { font-size: 10px; color: #94a3b8; margin-top: 2px; text-transform: uppercase; }

.expenses-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.expenses-title { font-size: 16px; font-weight: 700; color: #1e293b; }
.btn-new-expense { display: inline-flex; align-items: center; gap: 6px; background: var(--primary, #7c3aed); color: white; border: none; border-radius: 12px; padding: 10px 16px; font-size: 14px; font-weight: 600; text-decoration: none; }

.expense-card { background: white; border-radius: 14px; padding: 14px 16px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.06); display: flex; align-items: center; gap: 12px; }
.expense-icon { width: 40px; height: 40px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px; }
.expense-info { flex: 1; min-width: 0; }
.expense-desc { font-size: 14px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.expense-meta { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.expense-amount { font-size: 16px; font-weight: 700; color: #dc2626; flex-shrink: 0; }
.expense-actions { display: flex; gap: 8px; margin-left: 8px; flex-shrink: 0; }
.expense-action { width: 32px; height: 32px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.expense-action.edit { background: #f1f5f9; color: #475569; }
.expense-action.delete { background: #fee2e2; color: #dc2626; }

.empty-state { text-align: center; padding: 40px 16px; color: #94a3b8; }
.empty-state h3 { font-size: 16px; color: #64748b; margin-bottom: 4px; }
</style>

<?php if (!empty($success)): ?>
<div style="background:#dcfce7; color:#16a34a; padding:12px 16px; border-radius:12px; margin-bottom:12px; font-size:14px; display:flex; align-items:center; gap:8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
<?php endif; ?>
<?php if (!empty($error)): ?>
<div style="background:#fee2e2; color:#dc2626; padding:12px 16px; border-radius:12px; margin-bottom:12px; font-size:14px; display:flex; align-items:center; gap:8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
<?php endif; ?>

<div class="fin-nav">
    <a href="/financial">Visão Geral</a>
    <a href="/financial/monthly">Mensal</a>
    <a href="/financial/yearly">Anual</a>
    <a href="/expenses" class="active">Despesas</a>
    <a href="/expenses/categories">Categorias</a>
    <a href="/financial/settings">Config.</a>
</div>

<div class="month-selector">
    <select class="month-select" onchange="window.location='/expenses?month='+this.value">
        <?php foreach ($availableMonths as $m => $label): ?>
        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="expense-stats">
    <div class="expense-stat">
        <div class="expense-stat-value total"><?= $fmt($totalExpenses) ?></div>
        <div class="expense-stat-label">Total</div>
    </div>
    <div class="expense-stat">
        <div class="expense-stat-value"><?= $fmt($fixedExpenses) ?></div>
        <div class="expense-stat-label">Fixas</div>
    </div>
    <div class="expense-stat">
        <div class="expense-stat-value"><?= $fmt($variableExpenses) ?></div>
        <div class="expense-stat-label">Variáveis</div>
    </div>
</div>

<div class="expenses-header">
    <span class="expenses-title"><?= count($expenses) ?> despesas</span>
    <a href="/expenses/create" class="btn-new-expense">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        Nova
    </a>
</div>

<?php if (empty($expenses)): ?>
    <div class="empty-state">
        <h3>Nenhuma despesa</h3>
        <p>Adicione despesas para controlar seus gastos</p>
    </div>
<?php else: ?>
    <?php foreach ($expenses as $exp):
        $catName = $exp['category_name'] ?? 'Sem categoria';
        $payMethod = $exp['payment_method'] ?? '';
        $dateStr = date('d/m', strtotime($exp['expense_date'] ?? $exp['created_at'] ?? 'now'));
    ?>
    <div class="expense-card">
        <div class="expense-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <div class="expense-info">
            <div class="expense-desc"><?= htmlspecialchars($exp['description'] ?? '') ?></div>
            <div class="expense-meta"><?= htmlspecialchars($catName) ?> · <?= $dateStr ?><?= $payMethod ? ' · ' . htmlspecialchars($payMethod) : '' ?></div>
        </div>
        <div class="expense-amount"><?= $fmt($exp['amount'] ?? 0) ?></div>
        <div class="expense-actions">
            <a href="/expenses/<?= (int)$exp['id'] ?>/edit" class="expense-action edit">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            </a>
            <form method="post" action="/expenses/<?= (int)$exp['id'] ?>/delete?month=<?= $month ?>" style="display:inline" onsubmit="return confirm('Excluir?')">
                <button type="submit" class="expense-action delete">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
