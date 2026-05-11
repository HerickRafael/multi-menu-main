<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/svg_helper.php';

$company = is_array($company ?? null) ? $company : [];
$customer = is_array($customer ?? null) ? $customer : [];
$addresses = is_array($addresses ?? null) ? $addresses : [];
$slug = isset($slug) ? (string) $slug : (string) ($company['slug'] ?? '');
$slugClean = trim($slug, '/');

$profileUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'profile') : '#';
$createUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'addresses/create') : '#';
$title = 'Meus Enderecos - ' . e($company['name'] ?? 'Cardapio');

$pageConfig = [
    'page' => 'addresses-list',
    'deleteConfirmMessage' => 'Excluir este endereco?',
];

$addressesCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/addresses.css';
$addressesJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/addresses.js';
$addressesCssVersion = is_file($addressesCssPath) ? (string) filemtime($addressesCssPath) : (string) time();
$addressesJsVersion = is_file($addressesJsPath) ? (string) filemtime($addressesJsPath) : (string) time();
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="csrf-token" content="<?= \App\Middleware\CsrfProtection::generateToken() ?>">
<title><?= $title ?></title>
<?php if (!empty($company['logo'])): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/addresses.css')) ?>?v=<?= e($addressesCssVersion) ?>">
</head>
<body>
<div class="app">
  <div class="topbar">
    <div class="topwrap">
      <a class="back" href="<?= e($profileUrl) ?>" aria-label="Voltar">
        <?= addressesSvg('back') ?>
      </a>
      <span class="title">Meus Enderecos</span>
    </div>
  </div>

  <div class="content">
    <?php include __DIR__ . '/partials/address-list.php'; ?>
    <?php include __DIR__ . '/partials/delete-modal.php'; ?>
  </div>
</div>

<script id="page-config" type="application/json"><?= json_encode($pageConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= e(base_url('assets/addresses.js')) ?>?v=<?= e($addressesJsVersion) ?>" defer></script>
</body>
</html>
