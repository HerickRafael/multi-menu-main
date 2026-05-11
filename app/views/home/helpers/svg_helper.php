<?php

declare(strict_types=1);

if (!function_exists('home_svg_attrs')) {
    function home_svg_attrs(array $attrs = []): string
    {
        $pairs = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $pairs[] = e((string) $key) . '="' . e((string) $value) . '"';
        }

        return implode(' ', $pairs);
    }
}
