<?php
/**
 * Tabs de navegação dos cupons - estilo grid card
 * Requer: $activeCouponTab = 'list' | 'create' | 'history'
 */
$activeCouponTab = $activeCouponTab ?? 'list';

$tabs = [
    'list' => ['href' => '/coupons', 'label' => 'Cupons', 'icon' => '<path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6"/><path d="M2 8h20v4H2z"/><path d="M12 2v6"/>'],
    'create' => ['href' => '/coupons/create', 'label' => 'Criar', 'icon' => '<path d="M12 5v14m-7-7h14"/>'],
    'history' => ['href' => '/coupons/history', 'label' => 'Histórico', 'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
];
?>
<div style="background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; margin-bottom:16px;">
    <div style="display:grid; grid-template-columns:repeat(3,1fr);">
        <?php foreach ($tabs as $key => $tab):
            $isActive = ($activeCouponTab === $key);
            $bg = $isActive ? 'background:var(--admin-primary-color,#4361ee); border-bottom:2px solid var(--admin-primary-color,#4361ee);' : 'border-bottom:2px solid transparent;';
            $strokeColor = $isActive ? '#fff' : '#6b7280';
            $textColor = $isActive ? '#fff' : '#6b7280';
            $fontWeight = $isActive ? '600' : '500';
        ?>
        <a href="<?= $tab['href'] ?>" style="display:flex; flex-direction:column; align-items:center; gap:4px; padding:12px 4px; text-decoration:none; <?= $bg ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $strokeColor ?>" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $tab['icon'] ?></svg>
            <span style="font-size:10px; font-weight:<?= $fontWeight ?>; color:<?= $textColor ?>; text-align:center; line-height:1.1;"><?= $tab['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
