<?php
/**
 * Componente: Badge/Tag
 * 
 * @param string $text Texto do badge
 * @param string $type Tipo: 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'gray'
 * @param string $size Tamanho: 'sm', 'md', 'lg'
 * @param bool $rounded Totalmente arredondado (pill)
 * @param string $icon Ícone SVG (opcional)
 * @param bool $dot Mostrar dot indicador
 * @param string $class Classes adicionais
 */

$text = $text ?? '';
$type = $type ?? 'primary';
$size = $size ?? 'sm';
$rounded = $rounded ?? true;
$icon = $icon ?? '';
$dot = $dot ?? false;
$class = $class ?? '';

// Cores por tipo
$colors = [
    'primary' => 'bg-blue-100 text-blue-800',
    'secondary' => 'bg-gray-100 text-gray-800',
    'success' => 'bg-green-100 text-green-800',
    'danger' => 'bg-red-100 text-red-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'info' => 'bg-cyan-100 text-cyan-800',
    'gray' => 'bg-gray-200 text-gray-600',
    'purple' => 'bg-purple-100 text-purple-800',
    'indigo' => 'bg-indigo-100 text-indigo-800',
    'pink' => 'bg-pink-100 text-pink-800',
];

$colorClass = $colors[$type] ?? $colors['primary'];

// Tamanhos
$sizes = [
    'sm' => 'text-xs px-2 py-0.5',
    'md' => 'text-sm px-2.5 py-0.5',
    'lg' => 'text-sm px-3 py-1',
];
$sizeClass = $sizes[$size] ?? $sizes['sm'];

// Arredondamento
$roundedClass = $rounded ? 'rounded-full' : 'rounded';

// Cores do dot
$dotColors = [
    'primary' => 'bg-blue-500',
    'secondary' => 'bg-gray-500',
    'success' => 'bg-green-500',
    'danger' => 'bg-red-500',
    'warning' => 'bg-yellow-500',
    'info' => 'bg-cyan-500',
    'gray' => 'bg-gray-400',
    'purple' => 'bg-purple-500',
    'indigo' => 'bg-indigo-500',
    'pink' => 'bg-pink-500',
];
$dotColorClass = $dotColors[$type] ?? $dotColors['primary'];
?>

<span class="inline-flex items-center gap-1 font-medium <?= $sizeClass ?> <?= $colorClass ?> <?= $roundedClass ?> <?= e($class) ?>">
    <?php if ($dot): ?>
    <span class="w-1.5 h-1.5 rounded-full <?= $dotColorClass ?>"></span>
    <?php endif; ?>
    
    <?php if ($icon): ?>
    <span class="w-3.5 h-3.5"><?= $icon ?></span>
    <?php endif; ?>
    
    <?= e($text) ?>
</span>
