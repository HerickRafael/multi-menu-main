<?php
/**
 * Navegação de sub-seções do módulo Configurações (mobile)
 * Variáveis: $activeSettingsNav = 'store'|'hours'|'delivery'|'payments'|'loyalty'
 */
$activeSettingsNav = $activeSettingsNav ?? 'store';

$navItems = [
    ['key' => 'store',    'url' => '/settings/store',    'label' => 'Loja',
     'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
    ['key' => 'hours',    'url' => '/settings/hours',    'label' => 'Horários',
     'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
    ['key' => 'delivery', 'url' => '/settings/delivery', 'label' => 'Entrega',
     'icon' => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
    ['key' => 'payments', 'url' => '/settings/payments', 'label' => 'Pagamentos',
     'icon' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
    ['key' => 'loyalty',  'url' => '/settings/loyalty',  'label' => 'Fidelidade',
     'icon' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>'],
];
?>
<div style="background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; margin-bottom:16px;">
    <div style="display:grid; grid-template-columns:repeat(5,1fr);">
        <?php foreach ($navItems as $item):
            $isActive = ($item['key'] === $activeSettingsNav);
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
