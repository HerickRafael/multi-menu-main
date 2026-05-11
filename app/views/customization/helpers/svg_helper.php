<?php

require_once dirname(__DIR__, 2) . '/shared/helpers/svg_helper.php';

if (!function_exists('svg_customization')) {
    function svg_customization(string $name): string
    {
        switch ($name) {
            case 'back':
                return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"></path></svg>';
            case 'ingredient-placeholder':
                return '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'info':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4m0-4h.01" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
            case 'check':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'plus':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 6v12m6-6H6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'swap':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M8 9l4-4 4 4m0 6l-4 4-4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            default:
                return '';
        }
    }
}
