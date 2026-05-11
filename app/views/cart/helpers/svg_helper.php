<?php

require_once dirname(__DIR__, 2) . '/shared/helpers/svg_helper.php';

if (!function_exists('cartSvg')) {
    function cartSvg($name, array $attributes = [])
    {
        static $icons = [
            'back' => '<path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"/>',
            'empty-cart' => '<path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
            'home' => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
            'image-placeholder' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
            'chevron-right' => '<path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
            'section-customization' => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
            'edit' => '<path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
            'add' => '<path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>',
            'coupon' => '<path fill="none" stroke="currentColor" d="M.5 2A1.5 1.5 0 0 1 2 .5h9A1.5 1.5 0 0 1 12.5 2v.992c0 .097-.074.18-.171.19-2.235.249-2.235 3.498 0 3.747.097.01.171.093.171.19v.992a1.5 1.5 0 0 1-1.5 1.5H2a1.5 1.5 0 0 1-1.5-1.5z"/>',
            'check' => '<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>',
        ];

        if (!isset($icons[$name])) {
            return '';
        }

        $defaultViewBox = '0 0 24 24';
        if ($name === 'coupon') {
            $defaultViewBox = '0 0 13 11';
        }
        if ($name === 'check') {
            $defaultViewBox = '0 0 16 16';
        }

        $attrs = array_merge([
            'viewBox' => $defaultViewBox,
            'fill' => 'none',
        ], $attributes);

        $attrPairs = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $attrPairs[] = $key . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<svg ' . implode(' ', $attrPairs) . '>' . $icons[$name] . '</svg>';
    }
}
