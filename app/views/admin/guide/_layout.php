<?php
/**
 * Guide Engine — motor de layout compartilhado
 *
 * Chamado por Guide::render(). Não incluir diretamente.
 *
 * Vars obrigatórias : $guideSections (nested map), $guideContent
 * Vars de page-header: $pageTitle, $pageDescription, $pageIcon, $breadcrumbs, $actions
 * Vars opcionais    : $guideCta
 *
 * @maxlines 150  — se crescer além disso, quebrar em partes menores
 */
require_once __DIR__ . '/GuideUI.php';
ob_start();
?>
<link rel="stylesheet" href="<?= base_url('assets/css/guide-layout.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/guide-base.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/guide-components.css') ?>">

<div class="mx-auto max-w-7xl p-4">
    <?php include __DIR__ . '/../components/page-header.php'; ?>

    <div class="gc-layout">
            <?php include __DIR__ . '/_sidebar.php'; ?>
        <div class="gc-main">
            <?= $guideContent ?>
        </div>
    </div>
    <div class="gc-spacer"></div>
</div>

<script src="<?= base_url('assets/js/guide.js') ?>"></script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
