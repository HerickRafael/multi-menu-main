<?php
/**
 * Lista de Categorias Mobile
 */
require_once __DIR__ . '/../components/icons.php';
ob_start();
?>

<?php $activeProductNav = 'categories'; require __DIR__ . '/../components/products-nav.php'; ?>

<?php require __DIR__ . '/../components/page-alerts.php'; ?>

<!-- Lista de Categorias -->
<div class="categories-list">
    <?php if (empty($categories)): ?>
        <div style="text-align:center; padding:50px 20px; color:#94a3b8;">
            <div style="margin-bottom:12px;">
                <?= productIcon('grid', 48, '1.5') ?>
            </div>
            <div style="font-size:15px; font-weight:600; margin-bottom:4px;">Nenhuma categoria</div>
            <div style="font-size:13px; margin-bottom:16px;">Crie categorias para organizar seus produtos</div>
            <a href="/categories/create"
               style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px; border:none; border-radius:10px; font-size:13px; font-weight:600; color:#fff; background:var(--primary); text-decoration:none;">
                <?= productIcon('plus', 15, '2.5') ?>
                Nova Categoria
            </a>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($categories as $category): ?>
            <div style="background:var(--card-bg,#fff); border-radius:14px; padding:14px; box-shadow:0 1px 4px rgba(0,0,0,.06); display:flex; gap:12px; align-items:center;" data-id="<?= $category['id'] ?>">
                <!-- Imagem -->
                <?php if (!empty($category['image'])): ?>
                <div style="width:56px; height:56px; border-radius:12px; overflow:hidden; flex-shrink:0; background:#f1f5f9;">
                    <img src="/<?= htmlspecialchars($category['image']) ?>" alt="<?= htmlspecialchars($category['name']) ?>"
                         style="width:100%; height:100%; object-fit:cover;">
                </div>
                <?php else: ?>
                <div style="width:56px; height:56px; border-radius:12px; background:linear-gradient(135deg,#e0e7ff,#c7d2fe); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <?= productIcon('grid', 24, '1.5') ?>
                </div>
                <?php endif; ?>

                <!-- Info -->
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                        <span style="font-size:15px; font-weight:600; color:var(--text-primary,#1e293b); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <?= htmlspecialchars($category['name']) ?>
                        </span>
                        <?php if (!$category['active']): ?>
                        <span class="badge-inactive-sm">Inativo</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:12px; color:#64748b;">
                        <?= (int)$category['product_count'] ?> produtos
                        <?php if ($category['active_products'] < $category['product_count']): ?>
                            <span style="color:#94a3b8;">(<?= (int)$category['active_products'] ?> ativos)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ações -->
                <div style="display:flex; gap:6px; flex-shrink:0;">
                    <button type="button" onclick="toggleCategory(<?= $category['id'] ?>)"
                            title="<?= $category['active'] ? 'Desativar' : 'Ativar' ?>"
                            class="btn-icon-sm">
                        <?php if ($category['active']): ?>
                        <?= productIcon('eye', 18) ?>
                        <?php else: ?>
                        <?= productIcon('eye-off', 18) ?>
                        <?php endif; ?>
                    </button>
                    <a href="/categories/<?= $category['id'] ?>/edit" title="Editar"
                       class="btn-icon-sm">
                        <?= productIcon('edit', 18) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- FAB Nova Categoria -->
<a href="/categories/create" class="fab">
    <?= productIcon('plus', 24, '1.5') ?>
</a>

<script>
function toggleCategory(id) {
    fetch('/categories/' + id + '/toggle', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            MobileApp.toast(data.message, 'success');
            setTimeout(function() { location.reload(); }, 500);
        }
    })
    .catch(function() { MobileApp.toast('Erro ao atualizar', 'error'); });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
