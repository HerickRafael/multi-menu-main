<?php
/**
 * Componente: Mensagem Flash / Alerta
 * 
 * @param string $type Tipo: 'success', 'error', 'warning', 'info'
 * @param string $message Mensagem
 * @param string $title Título (opcional)
 * @param bool $dismissible Pode ser fechado
 * @param bool $autoClose Fecha automaticamente
 * @param int $autoCloseDelay Tempo para fechar (ms)
 * @param string $id ID do alerta
 */

$type = $type ?? 'info';
$message = $message ?? '';
$title = $title ?? '';
$dismissible = $dismissible ?? true;
$autoClose = $autoClose ?? false;
$autoCloseDelay = $autoCloseDelay ?? 5000;
$id = $id ?? 'alert-' . uniqid();

if (empty($message)) {
    return;
}

// Configurações por tipo
$configs = [
    'success' => [
        'bg' => 'bg-green-50',
        'border' => 'border-green-500',
        'text' => 'text-green-800',
        'iconColor' => 'text-green-400',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
    ],
    'error' => [
        'bg' => 'bg-red-50',
        'border' => 'border-red-500',
        'text' => 'text-red-800',
        'iconColor' => 'text-red-400',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>'
    ],
    'warning' => [
        'bg' => 'bg-yellow-50',
        'border' => 'border-yellow-500',
        'text' => 'text-yellow-800',
        'iconColor' => 'text-yellow-400',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>'
    ],
    'info' => [
        'bg' => 'bg-blue-50',
        'border' => 'border-blue-500',
        'text' => 'text-blue-800',
        'iconColor' => 'text-blue-400',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>'
    ],
];

$c = $configs[$type] ?? $configs['info'];
?>

<div 
    id="<?= e($id) ?>" 
    class="<?= $c['bg'] ?> border-l-4 <?= $c['border'] ?> p-4 rounded-r-lg mb-4 flex items-start alert-message"
    role="alert"
    <?php if ($autoClose): ?>
    data-auto-close="<?= $autoCloseDelay ?>"
    <?php endif; ?>
>
    <div class="flex-shrink-0 <?= $c['iconColor'] ?>">
        <?= $c['icon'] ?>
    </div>
    
    <div class="ml-3 flex-1">
        <?php if ($title): ?>
        <p class="text-sm font-medium <?= $c['text'] ?>"><?= e($title) ?></p>
        <?php endif; ?>
        <p class="text-sm <?= $c['text'] ?> <?= $title ? 'mt-1' : '' ?>"><?= e($message) ?></p>
    </div>
    
    <?php if ($dismissible): ?>
    <button 
        type="button" 
        class="ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 inline-flex h-8 w-8 <?= $c['text'] ?> hover:bg-white/50"
        onclick="this.closest('.alert-message').remove()"
        aria-label="Fechar"
    >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
    </button>
    <?php endif; ?>
</div>

<?php if ($autoClose): ?>
<script>
(function() {
    const alert = document.getElementById('<?= e($id) ?>');
    if (alert) {
        setTimeout(() => {
            alert.classList.add('opacity-0', 'transition-opacity', 'duration-500');
            setTimeout(() => alert.remove(), 500);
        }, <?= $autoCloseDelay ?>);
    }
})();
</script>
<?php endif; ?>
