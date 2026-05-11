<?php
$title = ($company['name'] ?? 'Cardapio') . ' - Cardapio';
ob_start();

require_once __DIR__ . '/helpers/svg_helper.php';

// badgePromo() e normalize_color_hex() definidas em app/core/CommonHelpers.php

$headerTextColor   = normalize_color_hex($company['menu_header_text_color'] ?? '', '#FFFFFF');
$headerButtonColor = normalize_color_hex($company['menu_header_button_color'] ?? '', '#FACC15');
$welcomeBgColor    = normalize_color_hex($company['menu_welcome_bg_color'] ?? '', '#6B21A8');
$headerBgColor     = normalize_color_hex($company['menu_header_bg_color'] ?? $welcomeBgColor, $welcomeBgColor);
$logoBorderColor   = normalize_color_hex($company['menu_logo_border_color'] ?? ($company['menu_logo_bg_color'] ?? ''), '#7C3AED');
$groupBgColor      = normalize_color_hex($company['menu_group_title_bg_color'] ?? '', '#FACC15');
$groupTextColor    = normalize_color_hex($company['menu_group_title_text_color'] ?? '', '#000000');
$welcomeText       = normalize_color_hex($company['menu_welcome_text_color'] ?? '', '#FFFFFF');

$q              = $q ?? '';
$novidades      = $novidades ?? [];
$searchResults  = $searchResults ?? [];
$categories     = $categories ?? [];
$products       = $products ?? [];
$hours          = $hours ?? [];
$isOpenNow      = $isOpenNow ?? null;
$todayLabel     = $todayLabel ?? null;
$company        = $company ?? [];

$mostraNovidade    = isset($mostraNovidade) ? (bool)$mostraNovidade : (count($novidades) > 0);
$mostraMaisPedidos = isset($mostraMaisPedidos) ? (bool)$mostraMaisPedidos : false;
$maisPedidos       = $maisPedidos ?? [];

$bannerUrl = !empty($company['banner']) ? base_url($company['banner']) : null;
$customer = $_SESSION['customer'] ?? null;
$showFooterMenu = true;
$forceLoginHome = !empty($_GET['login']) && !$customer;

$_partialCard = dirname(__DIR__) . '/public/partials_card.php';
$_partialCardExists = file_exists($_partialCard);

$pageConfig = [
    'colors' => [
        'headerTextColor' => $headerTextColor,
        'headerButtonColor' => $headerButtonColor,
        'headerBgColor' => $headerBgColor,
        'logoBorderColor' => $logoBorderColor,
        'groupBgColor' => $groupBgColor,
        'groupTextColor' => $groupTextColor,
        'welcomeBgColor' => $welcomeBgColor,
        'welcomeTextColor' => $welcomeText,
    ],
];

  $homeCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/home.css';
  $homeJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/home.js';
  $homeCssVersion = is_file($homeCssPath) ? (string) filemtime($homeCssPath) : (string) time();
  $homeJsVersion = is_file($homeJsPath) ? (string) filemtime($homeJsPath) : (string) time();
?>
  <link rel="stylesheet" href="<?= e(base_url('assets/home.css')) ?>?v=<?= e($homeCssVersion) ?>">

<div class="menu-root">
  <?php include __DIR__ . '/partials/header.php'; ?>

  <div class="max-w-5xl mx-auto p-4 pb-12">
    <?php include __DIR__ . '/partials/last-order.php'; ?>
    <?php include __DIR__ . '/partials/category-tabs.php'; ?>
    <?php include __DIR__ . '/partials/search-bar.php'; ?>
    <?php include __DIR__ . '/partials/category-sections.php'; ?>
  </div>
</div>

<script id="home-page-config" type="application/json"><?= json_encode($pageConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= e(base_url('assets/home.js')) ?>?v=<?= e($homeJsVersion) ?>"></script>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/public/layout.php';
