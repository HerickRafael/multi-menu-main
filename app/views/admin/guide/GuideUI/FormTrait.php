<?php
/**
 * Guide Engine — Form grouping components
 * Methods: fieldset(), field()
 */
trait GuideFormTrait
{
    /**
     * Wraps one or more field strings in a .gc-fieldset container.
     * Usage: GuideUI::fieldset(GuideUI::field(...), GuideUI::field(...))
     */
    public static function fieldset(string ...$fields): string
    {
        $inner = implode('', $fields);

        return <<<HTML
            <div class="gc-fieldset">
                {$inner}
            </div>
            HTML;
    }

    /**
     * A single labeled form field.
     *
     * @param string $label    Plain text (will be escaped)
     * @param string $inputHtml Already-rendered element HTML (trusted — from GuideUI::input/select/textarea)
     * @param string $tagHtml  Optional badge (use GuideUI::tag())
     * @param string $help     Optional help text (plain text — will be escaped)
     */
    public static function field(string $label, string $inputHtml, string $tagHtml = '', string $help = ''): string
    {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $helpHtml  = $help !== ''
            ? '<p class="gc-help">' . htmlspecialchars($help, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';

        return <<<HTML
            <div class="gc-field">
                <span class="gc-label">{$safeLabel}{$tagHtml}</span>
                {$inputHtml}
                {$helpHtml}
            </div>
            HTML;
    }
}
