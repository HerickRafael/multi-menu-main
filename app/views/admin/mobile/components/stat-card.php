<?php
/**
 * Stat Card Mobile - Componente de estatística
 * 
 * @param string $title - Título do card
 * @param string $value - Valor principal
 * @param string $icon - SVG icon (opcional)
 * @param string $trend - Tendência: up, down, neutral (opcional)
 * @param string $trendValue - Valor da tendência (opcional)
 * @param string $color - Cor: primary, success, warning, danger (opcional)
 */

$title = $title ?? 'Estatística';
$value = $value ?? '0';
$icon = $icon ?? '';
$trend = $trend ?? '';
$trendValue = $trendValue ?? '';
$color = $color ?? 'primary';
?>

<div class="stat-card stat-card--<?= htmlspecialchars($color) ?>">
    <div class="stat-card__header">
        <?php if ($icon): ?>
            <div class="stat-card__icon">
                <?= $icon ?>
            </div>
        <?php endif; ?>
        <span class="stat-card__title"><?= htmlspecialchars($title) ?></span>
    </div>
    
    <div class="stat-card__value"><?= $value ?></div>
    
    <?php if ($trend && $trendValue): ?>
        <div class="stat-card__trend stat-card__trend--<?= htmlspecialchars($trend) ?>">
            <?php if ($trend === 'up'): ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                    <polyline points="17 6 23 6 23 12"/>
                </svg>
            <?php elseif ($trend === 'down'): ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                    <polyline points="17 18 23 18 23 12"/>
                </svg>
            <?php endif; ?>
            <span><?= htmlspecialchars($trendValue) ?></span>
        </div>
    <?php endif; ?>
</div>
