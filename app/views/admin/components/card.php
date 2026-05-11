<?php
/**
 * Componente: Card Genérico
 * 
 * @param string $title Título do card (opcional)
 * @param string $subtitle Subtítulo (opcional)
 * @param string $content Conteúdo HTML do card
 * @param string $footer Conteúdo do footer (opcional)
 * @param string $headerActions HTML de ações no header (opcional)
 * @param string $class Classes adicionais para o card
 * @param string $bodyClass Classes adicionais para o body
 * @param bool $noPadding Sem padding no body
 * @param string $id ID do card
 * @param bool $collapsible Card pode ser colapsado
 * @param bool $collapsed Inicia colapsado
 */

$title = $title ?? '';
$subtitle = $subtitle ?? '';
$content = $content ?? '';
$footer = $footer ?? '';
$headerActions = $headerActions ?? '';
$class = $class ?? '';
$bodyClass = $bodyClass ?? '';
$noPadding = $noPadding ?? false;
$id = $id ?? 'card-' . uniqid();
$collapsible = $collapsible ?? false;
$collapsed = $collapsed ?? false;

$paddingClass = $noPadding ? '' : 'p-4';
$hasHeader = $title || $subtitle || $headerActions;
?>

<div id="<?= e($id) ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden <?= e($class) ?>">
    <?php if ($hasHeader): ?>
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
        <div class="flex-1">
            <?php if ($title): ?>
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <?= e($title) ?>
                <?php if ($collapsible): ?>
                <button 
                    type="button"
                    class="card-toggle text-gray-400 hover:text-gray-600 p-1 rounded transition-colors"
                    onclick="toggleCard('<?= e($id) ?>')"
                    aria-expanded="<?= $collapsed ? 'false' : 'true' ?>"
                >
                    <svg class="w-5 h-5 transform transition-transform <?= $collapsed ? '' : 'rotate-180' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <?php endif; ?>
            </h3>
            <?php endif; ?>
            
            <?php if ($subtitle): ?>
            <p class="text-sm text-gray-500 mt-0.5"><?= e($subtitle) ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($headerActions): ?>
        <div class="flex items-center gap-2">
            <?= $headerActions ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="card-body <?= $paddingClass ?> <?= e($bodyClass) ?>" <?= $collapsed ? 'style="display:none"' : '' ?>>
        <?= $content ?>
    </div>
    
    <?php if ($footer): ?>
    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
        <?= $footer ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($collapsible): ?>
<script>
if (typeof toggleCard === 'undefined') {
    function toggleCard(cardId) {
        const card = document.getElementById(cardId);
        if (!card) return;
        
        const body = card.querySelector('.card-body');
        const icon = card.querySelector('.card-toggle svg');
        
        if (body.style.display === 'none') {
            body.style.display = '';
            icon?.classList.add('rotate-180');
            card.querySelector('.card-toggle')?.setAttribute('aria-expanded', 'true');
        } else {
            body.style.display = 'none';
            icon?.classList.remove('rotate-180');
            card.querySelector('.card-toggle')?.setAttribute('aria-expanded', 'false');
        }
    }
}
</script>
<?php endif; ?>
