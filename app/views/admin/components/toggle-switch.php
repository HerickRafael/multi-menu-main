<?php
/**
 * Componente: Toggle Switch
 * 
 * @param string $name Nome do campo
 * @param string $label Label do toggle
 * @param bool $checked Estado inicial
 * @param string $id ID do campo
 * @param bool $disabled Desabilitado
 * @param string $size Tamanho: 'sm', 'md', 'lg'
 * @param string $color Cor quando ativo: 'blue', 'green', 'red', 'yellow', 'purple'
 * @param string $onchange Evento onchange
 * @param string $helpText Texto de ajuda
 */

$name = $name ?? 'toggle';
$label = $label ?? '';
$checked = $checked ?? false;
$id = $id ?? 'toggle-' . $name;
$disabled = $disabled ?? false;
$size = $size ?? 'md';
$color = $color ?? 'blue';
$onchange = $onchange ?? '';
$helpText = $helpText ?? '';

// Tamanhos
$sizes = [
    'sm' => ['track' => 'w-8 h-4', 'dot' => 'w-3 h-3', 'translate' => 'translate-x-4'],
    'md' => ['track' => 'w-11 h-6', 'dot' => 'w-5 h-5', 'translate' => 'translate-x-5'],
    'lg' => ['track' => 'w-14 h-7', 'dot' => 'w-6 h-6', 'translate' => 'translate-x-7'],
];
$s = $sizes[$size] ?? $sizes['md'];

// Cores
$colors = [
    'blue' => 'peer-checked:bg-blue-600',
    'green' => 'peer-checked:bg-green-600',
    'red' => 'peer-checked:bg-red-600',
    'yellow' => 'peer-checked:bg-yellow-500',
    'purple' => 'peer-checked:bg-purple-600',
];
$colorClass = $colors[$color] ?? $colors['blue'];

$disabledClass = $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer';
?>

<div class="flex items-center <?= $disabledClass ?>">
    <label class="relative inline-flex items-center cursor-pointer">
        <input 
            type="checkbox"
            id="<?= e($id) ?>"
            name="<?= e($name) ?>"
            value="1"
            class="sr-only peer"
            <?= $checked ? 'checked' : '' ?>
            <?= $disabled ? 'disabled' : '' ?>
            <?= $onchange ? "onchange=\"{$onchange}\"" : '' ?>
        >
        <div class="<?= $s['track'] ?> bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer <?= $colorClass ?> peer-checked:after:<?= $s['translate'] ?> peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:<?= $s['dot'] ?> after:transition-all"></div>
        <?php if ($label): ?>
        <span class="ml-3 text-sm font-medium text-gray-700"><?= e($label) ?></span>
        <?php endif; ?>
    </label>
    
    <?php if ($helpText): ?>
    <span class="ml-2 text-xs text-gray-500"><?= e($helpText) ?></span>
    <?php endif; ?>
</div>
