<?php
require_once __DIR__ . '/helpers/svg_helper.php';

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/navbar.php';
include __DIR__ . '/partials/hero.php';
include __DIR__ . '/partials/stats.php';
include __DIR__ . '/partials/problem-solution.php';
include __DIR__ . '/partials/comparison-table.php';
include __DIR__ . '/partials/roi-calculator.php';
include __DIR__ . '/partials/features.php';
include __DIR__ . '/partials/modules.php';
include __DIR__ . '/partials/integrations.php';
include __DIR__ . '/partials/cases.php';
include __DIR__ . '/partials/timeline.php';
include __DIR__ . '/partials/steps.php';
include __DIR__ . '/partials/admin-showcase.php';
include __DIR__ . '/partials/security.php';
include __DIR__ . '/partials/testimonials.php';
include __DIR__ . '/partials/pricing.php';
include __DIR__ . '/partials/faq.php';
include __DIR__ . '/partials/cta-final.php';
include __DIR__ . '/partials/footer.php';
include __DIR__ . '/partials/whatsapp-float.php';

$landingJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/landing.js';
$landingJsVersion = is_file($landingJsPath) ? (string) filemtime($landingJsPath) : (string) time();
?>
<script type="application/json" id="landing-page-config"><?= json_encode([
    'roiDefaults' => [
        'faturamentoMensal' => (int)($roiDefaults['faturamentoMensal'] ?? 30000),
        'taxaMarketplace' => (int)($roiDefaults['taxaMarketplace'] ?? 27),
        'planoMultiMenu' => (int)($roiDefaults['planoMultiMenu'] ?? 197),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= base_url('assets/landing.js') ?>?v=<?= e($landingJsVersion) ?>"></script>
</body>
</html>
