<?php
/**
 * Guide Engine — Section wrappers
 * Methods: sectionOpen(), sectionClose()
 */
trait GuideSectionTrait
{
    /**
     * Opens a guide section: <section><div class="gc-card"><h2>
     * Must be closed with sectionClose().
     *
     * @param string $id      HTML anchor id (plain text — will be escaped)
     * @param string $title   Section heading (plain text — will be escaped)
     * @param array  $options Supported keys:
     *                        'icon' — raw trusted SVG string (not escaped, comes from guide source)
     */
    public static function sectionOpen(string $id, string $title, array $options = []): string
    {
        $safeId    = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $iconHtml  = ($options['icon'] ?? '') !== '' ? $options['icon'] . ' ' : '';

        return <<<HTML
            <section id="{$safeId}" class="gc-sec"><div class="gc-card"><h2>{$iconHtml}{$safeTitle}</h2>
            HTML;
    }

    /** Closes the section opened by sectionOpen(). */
    public static function sectionClose(): string
    {
        return "</div></section>\n";
    }
}
