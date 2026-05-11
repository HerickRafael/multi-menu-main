<?php
/**
 * Guide Engine — Atomic form elements
 * Methods: input(), select(), textarea(), tag()
 */
trait GuideElementsTrait
{
    /** Renders a (disabled) text input. */
    public static function input(string $value = '', bool $disabled = true, string $type = 'text'): string
    {
        $safeType  = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        $valueAttr = $value !== '' ? ' value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"' : '';
        $disAttr   = $disabled ? ' disabled' : '';

        return "<input type=\"{$safeType}\" class=\"gc-input\"{$valueAttr}{$disAttr}>";
    }

    /**
     * Renders a (disabled) select element.
     *
     * $options accepts two formats:
     *   - string[]                              → label is used as value
     *   - ['value' => string, 'label' => string][] → value/label pairs (preferred)
     *
     * @param string $selected Value of the pre-selected option (empty = first option)
     */
    public static function select(array $options, string $selected = '', bool $disabled = true): string
    {
        $disAttr     = $disabled ? ' disabled' : '';
        $optionsHtml = '';

        foreach ($options as $i => $opt) {
            if (is_array($opt)) {
                $value = $opt['value'] ?? '';
                $label = $opt['label'] ?? $value;
                $isSel = ($selected !== '') ? ($value === $selected) : ($i === 0);
            } else {
                $value = $opt;
                $label = $opt;
                $isSel = ($selected !== '') ? ($opt === $selected) : ($i === 0);
            }

            $selAttr      = $isSel ? ' selected' : '';
            $safeValue    = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $safeLabel    = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $optionsHtml .= "<option value=\"{$safeValue}\"{$selAttr}>{$safeLabel}</option>";
        }

        return <<<HTML
            <select class="gc-select"{$disAttr}>{$optionsHtml}</select>
            HTML;
    }

    /**
     * Renders a (disabled) textarea.
     * Disabled color handled via CSS: .gc-textarea:disabled { color: #94a3b8 }
     */
    public static function textarea(string $placeholder = '', int $rows = 2, bool $disabled = true): string
    {
        $safePlaceholder = htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8');
        $disAttr         = $disabled ? ' disabled' : '';

        return <<<HTML
            <textarea class="gc-textarea" rows="{$rows}"{$disAttr}>{$safePlaceholder}</textarea>
            HTML;
    }

    /**
     * Renders an inline tag badge.
     *
     * @param string $variant 'r' = required/red | 'o' = optional/green (default)
     *                        Any unknown variant falls back to 'o' (safe whitelist).
     */
    public static function tag(string $label, string $variant = 'o'): string
    {
        if (!in_array($variant, ['r', 'o'], true)) {
            $variant = 'o';
        }

        return '<span class="gc-tag gc-tag-' . $variant . '">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</span>';
    }
}
