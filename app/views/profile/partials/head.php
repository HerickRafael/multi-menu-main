<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title><?= $title ?></title>
<?php if (!empty($company['logo'])): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
<link rel="apple-touch-icon" href="<?= e(base_url($company['logo'])) ?>">
<?php endif; ?>
<?php
$profileCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/profile.css';
$profileCssVersion = is_file($profileCssPath) ? (string) filemtime($profileCssPath) : (string) time();
?>
<link rel="stylesheet" href="<?= e(base_url('assets/profile.css')) ?>?v=<?= e($profileCssVersion) ?>">
