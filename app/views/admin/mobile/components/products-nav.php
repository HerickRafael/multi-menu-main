<?php
/**
 * Navegação de sub-seções do módulo Produtos (mobile)
 * Variáveis: $activeProductNav = 'products'|'categories'|'ingredients'|'templates'|'crosssell'
 */
$activeProductNav = $activeProductNav ?? 'products';

$navItems = [
    ['key' => 'products',    'url' => '/products',    'label' => 'Produtos',
     'icon' => '<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>'],
    ['key' => 'categories',  'url' => '/categories',  'label' => 'Categorias',
     'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>'],
    ['key' => 'ingredients',  'url' => '/ingredients',  'label' => 'Ingredientes',
     'icon' => '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>'],
    ['key' => 'templates', 'url' => '/customization-templates', 'label' => 'Personaliz.',
     'icon' => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 14l2 2 4-4"/>'],
    ['key' => 'crosssell',  'url' => '/cross-sell',  'label' => 'Cross-Sell',
     'icon' => '<path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>'],
];
?>
<div style="background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; margin-bottom:16px;">
    <div style="display:grid; grid-template-columns:repeat(5,1fr);">
        <?php foreach ($navItems as $item):
            $isActive = ($item['key'] === $activeProductNav);
            $color = $isActive ? '#fff' : '#6b7280';
            $bg = $isActive ? 'background:var(--admin-primary-color,#4361ee);' : '';
            $border = $isActive ? 'border-bottom:2px solid var(--admin-primary-color,#4361ee);' : 'border-bottom:2px solid transparent;';
            $weight = $isActive ? '600' : '500';
        ?>
        <a href="<?= $item['url'] ?>" style="display:flex; flex-direction:column; align-items:center; gap:4px; padding:12px 4px; text-decoration:none; <?= $bg ?> <?= $border ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $color ?>" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $item['icon'] ?></svg>
            <span style="font-size:10px; font-weight:<?= $weight ?>; color:<?= $color ?>; text-align:center; line-height:1.1;"><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
