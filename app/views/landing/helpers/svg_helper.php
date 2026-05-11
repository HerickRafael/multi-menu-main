<?php

declare(strict_types=1);

if (!function_exists('landing_svg_attrs')) {
    function landing_svg_attrs(array $attrs = []): string
    {
        $pairs = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $pairs[] = e((string)$key) . '="' . e((string)$value) . '"';
        }

        return implode(' ', $pairs);
    }
}
