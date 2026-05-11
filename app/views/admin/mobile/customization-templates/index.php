<?php
/**
 * Mobile View: Lista de Grupos de Personalização
 */
require_once __DIR__ . '/../components/icons.php';
$typeLabels = [
    'single' => 'Seleção única',
    'extra'  => 'Quantidade',
    'addon'  => 'Adicional',
    'component' => 'Componente'
];
$typeColors = [
    'single' => '#3b82f6',
    'extra'  => '#8b5cf6',
    'addon'  => '#10b981',
    'component' => '#f59e0b'
];
?>

<?php $activeProductNav = 'templates'; require __DIR__ . '/../components/products-nav.php'; ?>

<?php require __DIR__ . '/../components/page-alerts.php'; ?>

<?php if (empty($templates)): ?>
<div style="text-align:center; padding:50px 20px; color:#94a3b8;">
    <div style="margin-bottom:12px;">
        <?= productIcon('clipboard', 48, '1.5') ?>
    </div>
    <div style="font-size:15px; font-weight:600; margin-bottom:4px;">Nenhum grupo criado</div>
    <div style="font-size:13px; margin-bottom:16px;">Crie grupos reutilizáveis para adicionar rapidamente aos produtos.</div>
    <a href="/customization-templates/create"
       style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px; border:none; border-radius:10px; font-size:13px; font-weight:600; color:#fff; background:var(--primary); text-decoration:none;">
        <?= productIcon('plus', 15, '2.5') ?>
        Criar primeiro grupo
    </a>
</div>
<?php else: ?>

<div class="table-list">
    <?php foreach ($templates as $tpl):
        $isActive = !empty($tpl['active']);
        $productsCount = (int)($tpl['products_count'] ?? 0);
        $typeLabel = $typeLabels[$tpl['type']] ?? 'Extra';
        $typeColor = $typeColors[$tpl['type']] ?? '#8b5cf6';
    ?>
    <div class="table-list-item">
        <div class="table-list-header">
            <div class="table-list-title">
                <span class="table-list-icon"><?= productIcon('clipboard', 16) ?></span>
                <span class="table-list-name"><?= htmlspecialchars($tpl['name']) ?></span>
            </div>
            <span class="<?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                <?= $isActive ? 'Ativo' : 'Inativo' ?>
            </span>
        </div>

        <div class="table-list-tags">
            <span class="table-list-tag" style="background:<?= $typeColor ?>15; color:<?= $typeColor ?>; border:1px solid <?= $typeColor ?>30;">
                <?= $typeLabel ?>
            </span>
            <?php if ($productsCount > 0): ?>
            <span class="table-list-tag" style="background:#f0f4ff; color:#3b82f6; border:1px solid #bfdbfe;">
                <?= $productsCount ?> produto<?= $productsCount > 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="table-list-actions">
            <a href="/customization-templates/<?= (int)$tpl['id'] ?>/edit" class="action-btn">
                <?= productIcon('edit', 14) ?> Editar
            </a>
            <button type="button" onclick="toggleTemplate(<?= (int)$tpl['id'] ?>)" class="action-btn">
                <?php if ($isActive): ?>
                <?= productIcon('eye-off', 14) ?> Desativar
                <?php else: ?>
                <?= productIcon('eye', 14) ?> Ativar
                <?php endif; ?>
            </button>
            <?php if ($productsCount === 0): ?>
            <button type="button" onclick="deleteTemplate(<?= (int)$tpl['id'] ?>)" class="action-btn action-btn-danger">
                <?= productIcon('trash', 14) ?> Excluir
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function toggleTemplate(id) {
    fetch('/customization-templates/' + id + '/toggle', { method: 'POST' })
        .then(function(r) {
            if (r.ok) { location.reload(); }
            else { r.json().then(function(d) { alert(d.error || 'Erro'); }); }
        })
        .catch(function() { alert('Erro ao alterar status'); });
}

function deleteTemplate(id) {
    if (!confirm('Excluir este grupo?')) return;
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = '/customization-templates/' + id + '/delete';
    document.body.appendChild(f);
    f.submit();
}
</script>

<!-- FAB Novo Grupo -->
<a href="/customization-templates/create" class="fab">
    <?= productIcon('plus', 24, '1.5') ?>
</a>
