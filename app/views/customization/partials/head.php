<head>
<meta charset="utf-8">
<meta name="csrf-token" content="<?= \App\Middleware\CsrfProtection::generateToken() ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title>Personalizar — <?= e($pName) ?> | <?= e($company['name'] ?? '') ?></title>
<?php if (!empty($company['logo'])): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
<link rel="apple-touch-icon" href="<?= e(base_url($company['logo'])) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<?php
$customizationCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/customization.css';
$customizationCssVersion = is_file($customizationCssPath) ? (string) filemtime($customizationCssPath) : (string) time();
?>
<link rel="stylesheet" href="<?= base_url('assets/customization.css') ?>?v=<?= e($customizationCssVersion) ?>">
<script id="customization-page-config" type="application/json">
<?= json_encode([
  'pId'            => $pId,
  'slug'           => $slug,
  'encodedSlug'    => $encodedSlug,
  'saveUrl'        => $saveUrl,
  'unitIndex'      => $unitIndex,
  'totalUnits'     => $totalUnits,
  'editCartItemUid'=> $editCartItemUid,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
</head>
