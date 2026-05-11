<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/shared/helpers/svg_helper.php';

if (!function_exists('addressesSvg')) {
    /**
     * Retorna SVG inline reutilizavel para paginas de enderecos.
     */
    function addressesSvg(string $name, string $class = ''): string
    {
        $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

        switch ($name) {
            case 'back':
                return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="12" x2="5" y2="12" stroke-linecap="round" stroke-linejoin="round"></line><polyline points="12 19 5 12 12 5" stroke-linecap="round" stroke-linejoin="round"></polyline></svg>';

            case 'plus':
                return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"></path></svg>';

            case 'back-compact':
                return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"></path></svg>';

            default:
                return '';
        }
    }
}
