<?php
/**
 * Componente: Tabela de Dados
 * 
 * @param array $columns Colunas da tabela [['key' => 'nome', 'label' => 'Nome', 'class' => 'text-left'], ...]
 * @param array $rows Dados das linhas
 * @param string $id ID da tabela (opcional)
 * @param bool $striped Listras nas linhas
 * @param bool $hoverable Hover nas linhas
 * @param string $emptyMessage Mensagem quando não há dados
 * @param callable $rowRenderer Função para renderizar linhas customizadas
 * @param string $tableClass Classes adicionais para a tabela
 */

$columns = $columns ?? [];
$rows = $rows ?? [];
$id = $id ?? 'data-table-' . uniqid();
$striped = $striped ?? true;
$hoverable = $hoverable ?? true;
$emptyMessage = $emptyMessage ?? 'Nenhum registro encontrado';
$rowRenderer = $rowRenderer ?? null;
$tableClass = $tableClass ?? '';

$stripeClass = $striped ? 'even:bg-gray-50' : '';
$hoverClass = $hoverable ? 'hover:bg-gray-100' : '';
?>

<div class="overflow-x-auto">
    <table id="<?= e($id) ?>" class="min-w-full divide-y divide-gray-200 <?= e($tableClass) ?>">
        <thead class="bg-gray-50">
            <tr>
                <?php foreach ($columns as $col): ?>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider <?= e($col['class'] ?? '') ?>">
                    <?= e($col['label'] ?? $col['key'] ?? '') ?>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?= count($columns) ?>" class="px-4 py-8 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <?= e($emptyMessage) ?>
                    </div>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php if ($rowRenderer && is_callable($rowRenderer)): ?>
                        <?= $rowRenderer($row, $index) ?>
                    <?php else: ?>
                        <tr class="<?= $stripeClass ?> <?= $hoverClass ?> transition-colors">
                            <?php foreach ($columns as $col): ?>
                            <td class="px-4 py-3 text-sm text-gray-700 <?= e($col['cellClass'] ?? '') ?>">
                                <?php
                                $key = $col['key'] ?? '';
                                $value = is_array($row) ? ($row[$key] ?? '') : (isset($row->$key) ? $row->$key : '');
                                
                                if (isset($col['render']) && is_callable($col['render'])) {
                                    echo $col['render']($value, $row, $index);
                                } else {
                                    echo e($value);
                                }
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
