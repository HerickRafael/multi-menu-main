<?php

require_once dirname(__DIR__, 2) . '/shared/helpers/svg_helper.php';

if (!function_exists('svg_product')) {
    function svg_product(string $name): string
    {
        switch ($name) {
            case 'back':
                return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"></path></svg>';
            case 'cart':
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
            case 'image-placeholder':
                return '<svg class="hero-placeholder-icon" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'check':
                return '<svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'minus':
                return '<svg viewBox="0 0 24 24"><path d="M5 12h14" stroke="#111" stroke-width="1.5" stroke-linecap="round"></path></svg>';
            case 'plus':
                return '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="#111" stroke-width="1.5" stroke-linecap="round"></path></svg>';
            case 'pause':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>';
            case 'clock':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
            case 'info':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>';
            default:
                return '';
        }
    }
}
