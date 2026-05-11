<?php
/**
 * Empty State Mobile - Estado vazio amigável
 * 
 * @param string $icon - SVG icon
 * @param string $title - Título
 * @param string $message - Mensagem
 * @param string $actionUrl - URL do botão (opcional)
 * @param string $actionLabel - Label do botão (opcional)
 */

$icon = $icon ?? '';
$title = $title ?? 'Nada por aqui';
$message = $message ?? 'Não encontramos nenhum item.';
$actionUrl = $actionUrl ?? '';
$actionLabel = $actionLabel ?? '';
?>

<div class="empty-state">
    <?php if ($icon): ?>
        <div class="empty-state__icon">
            <?= $icon ?>
        </div>
    <?php else: ?>
        <div class="empty-state__icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M8 15s1.5 2 4 2 4-2 4-2"/>
                <line x1="9" y1="9" x2="9.01" y2="9"/>
                <line x1="15" y1="9" x2="15.01" y2="9"/>
            </svg>
        </div>
    <?php endif; ?>
    
    <h3 class="empty-state__title"><?= htmlspecialchars($title) ?></h3>
    <p class="empty-state__message"><?= htmlspecialchars($message) ?></p>
    
    <?php if ($actionUrl && $actionLabel): ?>
        <a href="<?= htmlspecialchars($actionUrl) ?>" class="btn-primary">
            <?= htmlspecialchars($actionLabel) ?>
        </a>
    <?php endif; ?>
</div>
