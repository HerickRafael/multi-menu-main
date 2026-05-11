<?php
/**
 * Guide Engine — Fluent builder
 *
 * Usage in a guide file:
 *
 *   $guide = Guide::make([
 *       'Group' => [['id' => 'sec', 'label' => 'Section', 'icon' => '<svg...>'], ...],
 *   ])->cta(['label' => '...', 'url' => '...', 'icon' => '<svg...>'])
 *     ->context(get_defined_vars());
 *
 *   ob_start();
 *   // ... section HTML using GuideUI::sectionOpen/Close ...
 *   $guide->render(ob_get_clean());
 *
 * @maxlines 60
 */

require_once __DIR__ . '/GuideUI.php';

class Guide
{
    private array $sections;
    private array $cta  = [];
    private array $ctx  = [];

    private function __construct(array $sections)
    {
        $this->sections = $sections;
    }

    /** Entry point. $sections is a nested map: ['Group label' => [['id','label','icon'], ...]] */
    public static function make(array $sections): self
    {
        return new self($sections);
    }

    /** Optional CTA button in the sidebar. */
    public function cta(array $cta): self
    {
        $this->cta = $cta;
        return $this;
    }

    /**
     * Captures page variables from the guide file scope.
     * Call as ->context(get_defined_vars()) before ob_start().
     */
    public function context(array $vars): self
    {
        $this->ctx = $vars;
        return $this;
    }

    /**
     * Renders the full guide page.
     *
     * Extracts page vars (EXTR_SKIP so nothing already set is overwritten),
     * then forces engine vars ($guideSections, $guideCta, $guideContent),
     * and delegates to _layout.php.
     */
    public function render(string $guideContent): void
    {
        extract($this->ctx, EXTR_SKIP);   // page vars: $pageTitle, $breadcrumbs, $company, etc.
        $guideSections = $this->sections; // engine vars always win
        $guideCta      = $this->cta;
        include __DIR__ . '/_layout.php';
    }
}
