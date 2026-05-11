<head>
<meta charset="utf-8">
<meta name="csrf-token" content="<?= \App\Middleware\CsrfProtection::generateToken() ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title><?= e($product['name'] ?? 'Produto') ?> — <?= e($company['name'] ?? '') ?></title>
<?php if (!empty($company['logo'])): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
<link rel="apple-touch-icon" href="<?= e(base_url($company['logo'])) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<?php
$tailwindCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/css/tailwind.min.css';
$productCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/product.css';
$tailwindCssVersion = is_file($tailwindCssPath) ? (string) filemtime($tailwindCssPath) : (string) time();
$productCssVersion = is_file($productCssPath) ? (string) filemtime($productCssPath) : (string) time();
?>
<link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>?v=<?= e($tailwindCssVersion) ?>">
<link rel="stylesheet" href="<?= base_url('assets/product.css') ?>?v=<?= e($productCssVersion) ?>">
<script id="page-data" type="application/json">
<?= json_encode([
  'requiresLogin'         => (bool)$requireLogin,
  'isLogged'              => (bool)$isLogged,
  'productId'             => $pId,
  'customerId'            => isset($_SESSION['customer']['id']) ? (int)$_SESSION['customer']['id'] : null,
  'slug'                  => $slug,
  'checkCustomizationUrl' => base_url($slug . '/check-customization'),
  'trackInteractionUrl'   => base_url('api/' . $slug . '/track-interaction'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
</head>
