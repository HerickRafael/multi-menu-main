<?php
/**
 * Componente: Modal
 * 
 * @param string $id ID do modal (obrigatório)
 * @param string $title Título do modal
 * @param string $content Conteúdo HTML do modal
 * @param string $size Tamanho: 'sm', 'md', 'lg', 'xl', 'full'
 * @param bool $closable Pode ser fechado pelo X
 * @param bool $closeOnBackdrop Fecha ao clicar no backdrop
 * @param string $footerContent Conteúdo do footer (opcional)
 * @param array $buttons Botões do footer [['label' => 'Salvar', 'class' => 'btn-primary', 'onclick' => '', 'type' => 'submit'], ...]
 */

$id = $id ?? 'modal-' . uniqid();
$title = $title ?? '';
$content = $content ?? '';
$size = $size ?? 'md';
$closable = $closable ?? true;
$closeOnBackdrop = $closeOnBackdrop ?? true;
$footerContent = $footerContent ?? '';
$buttons = $buttons ?? [];

// Tamanhos
$sizes = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-lg',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
    'full' => 'max-w-full mx-4',
];
$sizeClass = $sizes[$size] ?? $sizes['md'];
?>

<!-- Modal -->
<div 
    id="<?= e($id) ?>" 
    class="fixed inset-0 z-50 hidden overflow-y-auto" 
    role="dialog" 
    aria-modal="true"
    aria-labelledby="<?= e($id) ?>-title"
>
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
        <?php if ($closeOnBackdrop): ?>
        onclick="closeModal('<?= e($id) ?>')"
        <?php endif; ?>
    ></div>
    
    <!-- Modal Container -->
    <div class="flex min-h-full items-center justify-center p-4">
        <!-- Modal Content -->
        <div class="relative w-full <?= $sizeClass ?> bg-white rounded-xl shadow-2xl transform transition-all">
            <!-- Header -->
            <?php if ($title || $closable): ?>
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <?php if ($title): ?>
                <h3 id="<?= e($id) ?>-title" class="text-lg font-semibold text-gray-900"><?= e($title) ?></h3>
                <?php else: ?>
                <div></div>
                <?php endif; ?>
                
                <?php if ($closable): ?>
                <button 
                    type="button" 
                    class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1.5 transition-colors"
                    onclick="closeModal('<?= e($id) ?>')"
                    aria-label="Fechar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Body -->
            <div class="p-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                <?= $content ?>
            </div>
            
            <!-- Footer -->
            <?php if ($footerContent || !empty($buttons)): ?>
            <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-200">
                <?php if ($footerContent): ?>
                    <?= $footerContent ?>
                <?php else: ?>
                    <?php foreach ($buttons as $btn): ?>
                    <button
                        type="<?= e($btn['type'] ?? 'button') ?>"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors <?= e($btn['class'] ?? 'bg-gray-100 text-gray-700 hover:bg-gray-200') ?>"
                        <?php if (!empty($btn['onclick'])): ?>onclick="<?= e($btn['onclick']) ?>"<?php endif; ?>
                        <?php if (!empty($btn['form'])): ?>form="<?= e($btn['form']) ?>"<?php endif; ?>
                    >
                        <?= e($btn['label'] ?? 'Botão') ?>
                    </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Funções globais para controle de modals
if (typeof openModal === 'undefined') {
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            modal.querySelector('[role="dialog"]')?.focus();
        }
    }
}

if (typeof closeModal === 'undefined') {
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }
}

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('[role="dialog"]:not(.hidden)');
        openModals.forEach(modal => {
            const id = modal.id;
            if (id) closeModal(id);
        });
    }
});
</script>
