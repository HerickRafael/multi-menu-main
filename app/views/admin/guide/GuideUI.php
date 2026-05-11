<?php
/**
 * Guide Engine — UI facade
 *
 * Thin loader. All methods live in the three sub-traits below.
 * Callers use GuideUI::method() as before — no API change.
 *
 * @maxlines 20
 */

require_once __DIR__ . '/GuideUI/SectionTrait.php';
require_once __DIR__ . '/GuideUI/FormTrait.php';
require_once __DIR__ . '/GuideUI/ElementsTrait.php';

class GuideUI
{
    use GuideSectionTrait;
    use GuideFormTrait;
    use GuideElementsTrait;
}
