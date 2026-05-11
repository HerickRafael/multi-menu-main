<?php

declare(strict_types=1);

/**
 * Helper de SVGs compartilhados entre múltiplos domínios de views.
 *
 * Cada ícone aqui aparece em 2 ou mais views (cart, product, customization,
 * order, profile, addresses, checkout-success). Os helpers locais de cada
 * view carregam este arquivo via require_once e continuam funcionando
 * normalmente; este helper fica disponível também para código novo.
 *
 * Uso:
 *   svg_shared('back')
 *   svg_shared('cancel', 'w-4 h-4 text-red-500')
 */
if (!function_exists('svg_shared')) {
    function svg_shared(string $name, string $class = ''): string
    {
        $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

        switch ($name) {
            // Aparece em: cart (back-compact), addresses (back-compact), product, customization, order, profile
            case 'back':
                return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"/></svg>';

            // Aparece em: order, profile (paths idênticos; profile adiciona width/height via classe)
            case 'cancel':
                return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';

            // Aparece em: product, customization (quase idênticos)
            case 'info':
                return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>';

            // Aparece em: product (image-placeholder), customization (ingredient-placeholder) — path idêntico
            case 'picture-placeholder':
                return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

            // Aparece em: addresses, product, customization, profile (stroke-width="1.5"; currentColor)
            case 'plus':
                return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

            // Aparece em: product (stroke-width 1.5), order (stroke-width 2)
            case 'clock':
                return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';

            default:
                return '';
        }
    }
}
