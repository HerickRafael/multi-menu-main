<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/svg_helper.php';

$company = is_array($company ?? null) ? $company : [];
$address = is_array($address ?? null) ? $address : [];
$cities = is_array($cities ?? null) ? $cities : [];
$zonesByCity = is_array($zonesByCity ?? null) ? $zonesByCity : [];

$slug = isset($slug) ? (string) $slug : (string) ($company['slug'] ?? '');
$slugClean = trim($slug, '/');
$isEdit = !empty($address['id']);

$profileUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'profile') : '#';
$saveUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'addresses/' . ($isEdit ? 'update' : 'create')) : '#';

$title = ($isEdit ? 'Editar' : 'Novo') . ' Endereco - ' . e($company['name'] ?? 'Cardapio');
$selectedCityId = (int) ($address['city_id'] ?? 0);
$selectedZoneId = (int) ($address['zone_id'] ?? 0);

$pageConfig = [
    'page' => 'address-form',
    'slug' => $slugClean,
    'selectedZoneId' => $selectedZoneId,
    'cities' => $cities,
    'zonesByCity' => $zonesByCity,
];
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title><?= $title ?></title>
<?php if (!empty($company['logo'])): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
<link rel="apple-touch-icon" href="<?= e(base_url($company['logo'])) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/addresses.css')) ?>">
</head>
<body>
<div class="app">
  <div class="topbar">
    <div class="topwrap">
      <a class="back" href="<?= e($profileUrl) ?>" aria-label="Voltar">
        <?= addressesSvg('back-compact') ?>
      </a>
      <div class="title"><?= $isEdit ? 'Editar' : 'Novo' ?> Endereco</div>
    </div>
  </div>

  <form id="address-form" class="content" method="post" action="<?= e($saveUrl) ?>">
    <?php if (function_exists('csrf_field')): ?>
      <?= csrf_field() ?>
    <?php endif; ?>

    <?php if ($isEdit): ?>
      <input type="hidden" name="address_id" value="<?= (int) ($address['id'] ?? 0) ?>">
    <?php endif; ?>

    <?php include __DIR__ . '/partials/form-fields.php'; ?>
  </form>

  <div class="footer">
    <button class="cta" type="submit" form="address-form"><?= $isEdit ? 'Salvar alteracoes' : 'Adicionar endereco' ?></button>
  </div>
</div>

<script id="page-config" type="application/json"><?= json_encode($pageConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= e(base_url('assets/addresses.js')) ?>" defer></script>
</body>
</html>
