<?php
/**
 * Categorias de Despesas - Mobile
 */
?>

<style>
.fin-nav { display: flex; gap: 6px; margin-bottom: 16px; overflow-x: auto; }
.fin-nav a { flex-shrink: 0; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; background: white; color: #64748b; border: 1px solid #e2e8f0; }
.fin-nav a.active { background: var(--primary, #7c3aed); color: white; border-color: var(--primary); }

.cat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.cat-title { font-size: 16px; font-weight: 700; color: #1e293b; }
.btn-seed { font-size: 12px; padding: 8px 12px; border-radius: 10px; background: #f1f5f9; color: #475569; text-decoration: none; font-weight: 500; }

.new-cat-form {
    background: white; border-radius: 16px; padding: 16px; margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.new-cat-form h3 { font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 10px; }
.new-cat-row { display: flex; gap: 8px; margin-bottom: 8px; }
.new-cat-input { flex: 1; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; }
.new-cat-select { padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: white; }
.new-cat-btn { padding: 10px 18px; border: none; border-radius: 10px; background: var(--primary, #7c3aed); color: white; font-size: 14px; font-weight: 600; cursor: pointer; }

.cat-list { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden; }
.cat-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
.cat-item:last-child { border-bottom: none; }
.cat-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.cat-icon.fixed { background: #dbeafe; color: #2563eb; }
.cat-icon.variable { background: #fef3c7; color: #d97706; }
.cat-info { flex: 1; }
.cat-name { font-size: 14px; font-weight: 600; color: #1e293b; }
.cat-type { font-size: 12px; color: #94a3b8; }
.cat-delete { width: 32px; height: 32px; border-radius: 8px; border: none; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; cursor: pointer; }

.empty-state { text-align: center; padding: 40px 16px; color: #94a3b8; }
.empty-state h3 { font-size: 16px; color: #64748b; }
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
    <a href="/expenses">Despesas</a>
    <a href="/expenses/categories" class="active">Categorias</a>
    <a href="/financial/settings">Config.</a>
</div>

<div class="cat-header">
    <span class="cat-title"><?= count($categories) ?> categorias</span>
    <?php if (empty($categories)): ?>
    <a href="/expenses/categories/seed" class="btn-seed">+ Carregar padrão</a>
    <?php endif; ?>
</div>

<!-- Form nova categoria -->
<form method="post" action="/expenses/categories" class="new-cat-form">
    <h3>Nova Categoria</h3>
    <div class="new-cat-row">
        <input type="text" name="name" class="new-cat-input" placeholder="Nome da categoria" required>
        <select name="type" class="new-cat-select">
            <option value="fixed">Fixa</option>
            <option value="variable">Variável</option>
        </select>
    </div>
    <input type="text" name="description" class="new-cat-input" placeholder="Descrição (opcional)" style="width:100%; margin-bottom:10px; box-sizing:border-box;">
    <button type="submit" class="new-cat-btn">Criar</button>
</form>

<?php if (empty($categories)): ?>
    <div class="empty-state">
        <h3>Nenhuma categoria</h3>
        <p>Crie categorias para organizar suas despesas</p>
    </div>
<?php else: ?>
<div class="cat-list">
    <?php foreach ($categories as $cat): ?>
    <div class="cat-item">
        <div class="cat-icon <?= ($cat['type'] ?? 'fixed') ?>">
            <?php if (($cat['type'] ?? 'fixed') === 'fixed'): ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <?php else: ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <?php endif; ?>
        </div>
        <div class="cat-info">
            <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
            <div class="cat-type"><?= ($cat['type'] ?? 'fixed') === 'fixed' ? 'Fixa' : 'Variável' ?>
                <?= !empty($cat['description']) ? ' · ' . htmlspecialchars($cat['description']) : '' ?>
            </div>
        </div>
        <form method="post" action="/expenses/categories/<?= (int)$cat['id'] ?>/delete" onsubmit="return confirm('Excluir categoria?')">
            <button type="submit" class="cat-delete">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            </button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
