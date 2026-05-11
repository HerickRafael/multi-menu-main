<?php
/**
 * Componente: Card de Estatística
 * 
 * @param string $title Título do card
 * @param string|int $value Valor principal
 * @param string $icon Ícone SVG (opcional)
 * @param string $color Cor: 'blue', 'green', 'red', 'yellow', 'purple', 'indigo'
 * @param string $subtitle Subtítulo/descrição (opcional)
 * @param string $trend Tendência: 'up', 'down', 'neutral' (opcional)
 * @param string $trendValue Valor da tendência (ex: '+5%')
 * @param string $href Link opcional
 */

$title = $title ?? 'Estatística';
$value = $value ?? '0';
$color = $color ?? 'blue';
$subtitle = $subtitle ?? '';
$trend = $trend ?? '';
$trendValue = $trendValue ?? '';
$href = $href ?? '';
$icon = $icon ?? '';

// Cores por tipo
$colors = [
    'blue' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'icon' => 'text-blue-500'],
    'green' => ['bg' => 'bg-green-50', 'text' => 'text-green-600', 'icon' => 'text-green-500'],
    'red' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'icon' => 'text-red-500'],
    'yellow' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-600', 'icon' => 'text-yellow-500'],
    'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'icon' => 'text-purple-500'],
    'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'icon' => 'text-indigo-500'],
    'gray' => ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'icon' => 'text-gray-500'],
];

$c = $colors[$color] ?? $colors['blue'];

// Ícone padrão se não fornecido
if (empty($icon)) {
    $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>';
}

// Ícones de tendência
$trendIcons = [
    'up' => '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
    'down' => '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>',
    'neutral' => '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12H4"/></svg>',
];

$wrapper = $href ? 'a' : 'div';
$hrefAttr = $href ? "href=\"{$href}\"" : '';
$hoverClass = $href ? 'hover:shadow-md cursor-pointer' : '';
?>
<<?= $wrapper ?> <?= $hrefAttr ?> class="<?= $c['bg'] ?> rounded-xl p-4 transition-shadow <?= $hoverClass ?>">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600"><?= e($title) ?></p>
            <p class="text-2xl font-bold <?= $c['text'] ?> mt-1"><?= e($value) ?></p>
            
            <?php if ($subtitle || ($trend && $trendValue)): ?>
            <div class="flex items-center gap-2 mt-2">
                <?php if ($trend && $trendValue): ?>
                <span class="inline-flex items-center gap-1 text-sm">
                    <?= $trendIcons[$trend] ?? '' ?>
                    <span class="<?= $trend === 'up' ? 'text-green-600' : ($trend === 'down' ? 'text-red-600' : 'text-gray-500') ?>"><?= e($trendValue) ?></span>
                </span>
                <?php endif; ?>
                
                <?php if ($subtitle): ?>
                <span class="text-xs text-gray-500"><?= e($subtitle) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="<?= $c['icon'] ?> p-2 rounded-lg <?= $c['bg'] ?>">
            <?= $icon ?>
        </div>
    </div>
</<?= $wrapper ?>>
