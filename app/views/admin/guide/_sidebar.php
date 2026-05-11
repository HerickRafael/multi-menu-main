<?php
/**
 * Guide Engine — navegação lateral dinâmica
 *
 * @var array       $guideSections  Nested map: ['Grupo' => [['id'=>'...','label'=>'...','icon'=>'...'], ...]]
 * @var array|null  $guideCta       CTA do rodapé: ['label' => '...', 'url' => '...', 'icon' => '<svg...>']
 */
$_gcFirst = null;
foreach ($guideSections as $_gcGroup) {
    foreach ($_gcGroup as $_gcItem) {
        if (!empty($_gcItem['id'])) { $_gcFirst = $_gcItem['id']; break 2; }
    }
}
?>
<nav class="gc-nav" style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:16px 10px;">
    <?php foreach ($guideSections as $_gcGroupLabel => $_gcItems): ?>
        <div class="gc-nav-group"><?= e($_gcGroupLabel) ?></div>
        <?php foreach ($_gcItems as $_gcItem): ?>
            <a href="#<?= e($_gcItem['id']) ?>"
               class="<?= $_gcItem['id'] === $_gcFirst ? 'active' : '' ?>"
               data-section="<?= e($_gcItem['id']) ?>">
                <?php if (!empty($_gcItem['icon'])): ?><?= $_gcItem['icon'] ?><?php endif; ?>
                <?= e($_gcItem['label']) ?>
            </a>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <?php if (!empty($guideCta)): ?>
        <a href="<?= e($guideCta['url']) ?>" class="gc-cta">
            <?php if (!empty($guideCta['icon'])): ?><?= $guideCta['icon'] ?><?php endif; ?>
            <?= e($guideCta['label']) ?>
        </a>
    <?php endif; ?>
</nav>
