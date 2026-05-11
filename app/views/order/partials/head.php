<?php

declare(strict_types=1);
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title><?= e($title) ?></title>
<?php if (!empty($company['logo'])): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
<link rel="apple-touch-icon" href="<?= e(base_url($company['logo'])) ?>">
<?php endif; ?>
<?php
$orderCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/order.css';
$orderCssVersion = is_file($orderCssPath) ? (string) filemtime($orderCssPath) : (string) time();
?>
<link rel="stylesheet" href="<?= e(base_url('assets/order.css')) ?>?v=<?= e($orderCssVersion) ?>">
