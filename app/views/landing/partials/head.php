<?php
// --- Sanitização e defaults de template ---
$whatsappNumber   = preg_replace('/[^0-9]/', '', (string)($whatsappNumber ?? ''));
$stats            = $stats            ?? [];
$features         = $features         ?? [];
$modules          = $modules          ?? [];
$pricing          = $pricing          ?? [];
$faq              = $faq              ?? [];
$featureTabs      = $featureTabs      ?? [];
$integrations     = $integrations     ?? [];
$cases            = $cases            ?? [];
$timeline         = $timeline         ?? [];
$steps            = $steps            ?? [];
$testimonials     = $testimonials     ?? [];
$securityFeatures = $securityFeatures ?? [];
$adminHighlights  = $adminHighlights  ?? [];
$competitors      = $competitors      ?? ['headers' => [], 'rows' => [], 'labels' => [], 'costs' => []];
$techStack        = $techStack        ?? [];
$totalFeatures    = $totalFeatures    ?? 0;
$totalModules     = $totalModules     ?? 0;
$totalControllers = $totalControllers ?? 0;
$totalMiddlewares  = $totalMiddlewares  ?? 0;
$roiDefaults      = $roiDefaults      ?? ['faturamentoMensal' => 30000, 'taxaMarketplace' => 27, 'planoMultiMenu' => 197];
?>
<!doctype html>
<html lang="pt-br" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <title><?= e($pageTitle ?? 'MultiMenu — Plataforma Completa para Restaurantes Digitais') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <meta name="description" content="<?= e($pageDescription ?? '') ?>">
  <meta name="theme-color" content="#4f46e5">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($pageTitle ?? '') ?>">
  <meta property="og:description" content="<?= e($pageDescription ?? '') ?>">
  <meta property="og:image" content="<?= base_url('assets/icons/icon-512x512.png') ?>">
  <meta property="og:locale" content="pt_BR">

  <?php
  $landingCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/css/landing.css';
  $tailwindCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/css/tailwind.min.css';
  $landingCssVersion = is_file($landingCssPath) ? (string) filemtime($landingCssPath) : (string) time();
  $tailwindCssVersion = is_file($tailwindCssPath) ? (string) filemtime($tailwindCssPath) : (string) time();
  ?>

  <!-- CSS -->
  <link rel="preload" href="<?= base_url('assets/css/landing.css') ?>?v=<?= e($landingCssVersion) ?>" as="style">
  <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>?v=<?= e($tailwindCssVersion) ?>">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap">

  <!-- Landing CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/landing.css') ?>?v=<?= e($landingCssVersion) ?>">
</head>
<body class="bg-white text-gray-900 antialiased overflow-x-hidden">

