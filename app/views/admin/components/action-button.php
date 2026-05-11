<?php
/**
 * Componente: Botão de Ação
 * 
 * @param string $type Tipo: 'edit', 'delete', 'view', 'toggle', 'custom'
 * @param string $href URL do botão (para links)
 * @param string $onclick JavaScript onclick (para ações)
 * @param string $label Texto do botão (opcional)
 * @param string $icon Ícone SVG customizado (opcional)
 * @param string $class Classes CSS adicionais
 * @param bool $disabled Desabilitado
 * @param array $attrs Atributos HTML extras
 */

$type = $type ?? 'custom';
$href = $href ?? '#';
$onclick = $onclick ?? '';
$label = $label ?? '';
$class = $class ?? '';
$disabled = $disabled ?? false;
$attrs = $attrs ?? [];

// Configurações por tipo
$configs = [
    'edit' => [
        'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
        'label' => 'Editar',
        'classes' => 'text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50'
    ],
    'delete' => [
        'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
        'label' => 'Excluir',
        'classes' => 'text-red-600 hover:text-red-900 hover:bg-red-50'
    ],
    'view' => [
        'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
        'label' => 'Visualizar',
        'classes' => 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
    ],
    'toggle' => [
        'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>',
        'label' => 'Alternar',
        'classes' => 'text-yellow-600 hover:text-yellow-900 hover:bg-yellow-50'
    ],
    'custom' => [
        'icon' => '',
        'label' => 'Ação',
        'classes' => 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
    ]
];

$config = $configs[$type] ?? $configs['custom'];
$iconHtml = $icon ?? $config['icon'];
$labelText = $label ?: $config['label'];
$baseClasses = "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {$config['classes']}";

if ($disabled) {
    $baseClasses .= ' opacity-50 cursor-not-allowed pointer-events-none';
}

$allClasses = trim("$baseClasses $class");

// Construir atributos extras
$attrsHtml = '';
foreach ($attrs as $key => $value) {
    $attrsHtml .= ' ' . e($key) . '="' . e($value) . '"';
}

// Renderizar como link ou botão
if ($onclick || $type === 'delete') {
    $tag = 'button';
    $hrefAttr = '';
    $onclickAttr = $onclick ? "onclick=\"{$onclick}\"" : '';
    $typeAttr = 'type="button"';
} else {
    $tag = 'a';
    $hrefAttr = "href=\"{$href}\"";
    $onclickAttr = '';
    $typeAttr = '';
}
?>
<<?= $tag ?> <?= $hrefAttr ?> <?= $typeAttr ?> <?= $onclickAttr ?> class="<?= $allClasses ?>"<?= $attrsHtml ?>>
    <?= $iconHtml ?>
    <span><?= e($labelText) ?></span>
</<?= $tag ?>>
