<?php
/**
 * Componente: Campo de Formulário
 * 
 * @param string $type Tipo: 'text', 'email', 'password', 'number', 'textarea', 'select', 'checkbox', 'radio', 'file', 'date', 'time', 'datetime', 'color', 'range'
 * @param string $name Nome do campo
 * @param string $label Label do campo
 * @param mixed $value Valor atual
 * @param array $options Opções para select/radio [['value' => '1', 'label' => 'Opção 1'], ...]
 * @param string $placeholder Placeholder
 * @param bool $required Obrigatório
 * @param bool $disabled Desabilitado
 * @param bool $readonly Somente leitura
 * @param string $id ID do campo
 * @param string $class Classes CSS adicionais
 * @param string $helpText Texto de ajuda
 * @param string $error Mensagem de erro
 * @param array $attrs Atributos adicionais
 * @param int $rows Linhas para textarea
 * @param string $accept Accept para file input
 */

$type = $type ?? 'text';
$name = $name ?? 'field';
$label = $label ?? '';
$value = $value ?? '';
$options = $options ?? [];
$placeholder = $placeholder ?? '';
$required = $required ?? false;
$disabled = $disabled ?? false;
$readonly = $readonly ?? false;
$id = $id ?? 'field-' . $name;
$class = $class ?? '';
$helpText = $helpText ?? '';
$error = $error ?? '';
$attrs = $attrs ?? [];
$rows = $rows ?? 4;
$accept = $accept ?? '';

// Construir atributos extras
$extraAttrs = '';
foreach ($attrs as $key => $val) {
    $extraAttrs .= ' ' . e($key) . '="' . e($val) . '"';
}

// Classes base para inputs
$baseInputClass = 'w-full px-3 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors';
$inputClass = $baseInputClass . ' ' . ($error ? 'border-red-500 bg-red-50' : 'border-gray-300') . ' ' . $class;

// Atributos comuns
$requiredAttr = $required ? 'required' : '';
$disabledAttr = $disabled ? 'disabled' : '';
$readonlyAttr = $readonly ? 'readonly' : '';
?>

<div class="mb-4">
    <?php if ($label && $type !== 'checkbox'): ?>
    <label for="<?= e($id) ?>" class="block text-sm font-medium text-gray-700 mb-1">
        <?= e($label) ?>
        <?php if ($required): ?>
        <span class="text-red-500">*</span>
        <?php endif; ?>
    </label>
    <?php endif; ?>

    <?php if ($type === 'textarea'): ?>
        <textarea
            id="<?= e($id) ?>"
            name="<?= e($name) ?>"
            rows="<?= $rows ?>"
            placeholder="<?= e($placeholder) ?>"
            class="<?= $inputClass ?>"
            <?= $requiredAttr ?>
            <?= $disabledAttr ?>
            <?= $readonlyAttr ?>
            <?= $extraAttrs ?>
        ><?= e($value) ?></textarea>

    <?php elseif ($type === 'select'): ?>
        <select
            id="<?= e($id) ?>"
            name="<?= e($name) ?>"
            class="<?= $inputClass ?>"
            <?= $requiredAttr ?>
            <?= $disabledAttr ?>
            <?= $extraAttrs ?>
        >
            <?php if ($placeholder): ?>
            <option value=""><?= e($placeholder) ?></option>
            <?php endif; ?>
            <?php foreach ($options as $opt): ?>
            <?php 
                $optValue = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                $optLabel = is_array($opt) ? ($opt['label'] ?? $optValue) : $opt;
                $selected = ($value == $optValue) ? 'selected' : '';
            ?>
            <option value="<?= e($optValue) ?>" <?= $selected ?>><?= e($optLabel) ?></option>
            <?php endforeach; ?>
        </select>

    <?php elseif ($type === 'checkbox'): ?>
        <label class="inline-flex items-center cursor-pointer">
            <input
                type="checkbox"
                id="<?= e($id) ?>"
                name="<?= e($name) ?>"
                value="1"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 <?= $class ?>"
                <?= $value ? 'checked' : '' ?>
                <?= $disabledAttr ?>
                <?= $extraAttrs ?>
            >
            <span class="ml-2 text-sm text-gray-700"><?= e($label) ?></span>
            <?php if ($required): ?>
            <span class="text-red-500 ml-1">*</span>
            <?php endif; ?>
        </label>

    <?php elseif ($type === 'radio'): ?>
        <div class="flex flex-wrap gap-4">
            <?php foreach ($options as $opt): ?>
            <?php 
                $optValue = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                $optLabel = is_array($opt) ? ($opt['label'] ?? $optValue) : $opt;
                $checked = ($value == $optValue) ? 'checked' : '';
            ?>
            <label class="inline-flex items-center cursor-pointer">
                <input
                    type="radio"
                    name="<?= e($name) ?>"
                    value="<?= e($optValue) ?>"
                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 <?= $class ?>"
                    <?= $checked ?>
                    <?= $disabledAttr ?>
                    <?= $extraAttrs ?>
                >
                <span class="ml-2 text-sm text-gray-700"><?= e($optLabel) ?></span>
            </label>
            <?php endforeach; ?>
        </div>

    <?php elseif ($type === 'file'): ?>
        <input
            type="file"
            id="<?= e($id) ?>"
            name="<?= e($name) ?>"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 <?= $class ?>"
            <?= $accept ? "accept=\"{$accept}\"" : '' ?>
            <?= $requiredAttr ?>
            <?= $disabledAttr ?>
            <?= $extraAttrs ?>
        >

    <?php else: ?>
        <input
            type="<?= e($type) ?>"
            id="<?= e($id) ?>"
            name="<?= e($name) ?>"
            value="<?= e($value) ?>"
            placeholder="<?= e($placeholder) ?>"
            class="<?= $inputClass ?>"
            <?= $requiredAttr ?>
            <?= $disabledAttr ?>
            <?= $readonlyAttr ?>
            <?= $extraAttrs ?>
        >
    <?php endif; ?>

    <?php if ($helpText && !$error): ?>
    <p class="mt-1 text-xs text-gray-500"><?= e($helpText) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
    <p class="mt-1 text-xs text-red-600"><?= e($error) ?></p>
    <?php endif; ?>
</div>
