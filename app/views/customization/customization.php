<?php

require_once __DIR__ . '/../../helpers/lazy_loading_helper.php';
require_once __DIR__ . '/helpers/svg_helper.php';

// price_br() definida globalmente em app/core/CommonHelpers.php

$slug    = $company['slug'] ?? '';
$pName   = $product['name'] ?? 'Produto';
$pId     = (int)($product['id'] ?? 0);
$parentProductId = isset($parentProductId) ? (int)$parentProductId : 0;

$unitIndex = isset($unitIndex) ? (int)$unitIndex : (isset($_GET['unit']) ? (int)$_GET['unit'] : 0);
$totalUnits = isset($totalUnits) ? (int)$totalUnits : (isset($_GET['total_units']) ? (int)$_GET['total_units'] : 1);
$unitLabel = '';
if ($unitIndex > 0 && $totalUnits > 1) {
    $unitLabel = " ({$unitIndex}º de {$totalUnits})";
}

$qtyGet = isset($_GET['qty']) ? max(1, min(99, (int)$_GET['qty'])) : null;

$groups = [];
foreach (($mods ?? []) as $gIndex => $g) {
    if (empty($g['items']) || !is_array($g['items'])) {
        continue;
    }

    $items = [];
    foreach ($g['items'] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $items[] = $item;
    }

    if (!$items) {
        continue;
    }

    $g['items'] = array_values($items);
    $groups[] = $g;
}

$raw = trim($_GET['edit_cart_item'] ?? '');
$editCartItemUid = preg_match('/^[0-9a-f]{12}$/', $raw) ? $raw : '';
$encodedSlug = rawurlencode((string)$slug);

if ($editCartItemUid) {
    $backUrl = base_url($encodedSlug . '/cart');
    $cancelUrl = base_url($encodedSlug . '/cart');
} else {
    $backUrl = !empty($parentBackUrl) ? $parentBackUrl : base_url($encodedSlug . '/produto/' . $pId);
    $parentQuery = $parentProductId ? '?parent_id=' . $parentProductId : '';
    $cancelUrl = base_url($encodedSlug . '/produto/' . $pId . '/customizar/cancelar' . $parentQuery);
}

$saveUrl = base_url($encodedSlug . '/produto/' . $pId . '/customizar');
$customizationJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/customization.js';
$customizationJsVersion = is_file($customizationJsPath) ? (string) filemtime($customizationJsPath) : (string) time();
?>
<!doctype html>
<html lang="pt-br">
<?php include __DIR__ . '/partials/head.php'; ?>
<body>
<?php include __DIR__ . '/partials/modal-info.php'; ?>

<form class="app" method="post" action="<?= e($saveUrl) ?>" id="customForm">
  <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfProtection::generateToken() ?>">
  <?php include __DIR__ . '/partials/header.php'; ?>

  <div class="container">
    <?php if (!empty($groups)): ?>
      <?php foreach ($groups as $gi => $g): ?>
        <?php
        $gName = (string)($g['name'] ?? ('Grupo '.($gi + 1)));
        $gType = (string)($g['type'] ?? 'extra');
        $gMin  = (int)($g['min'] ?? 0);
        $gMax  = (int)($g['max'] ?? 0);
        $items = $g['items'] ?? [];
        ?>
        <h2 class="group-title"><?= e($gName) ?></h2>

        <?php if ($gType === 'single'): ?>
          <?php include __DIR__ . '/partials/group-single.php'; ?>
        <?php elseif ($gType === 'pool'): ?>
          <?php include __DIR__ . '/partials/group-pool.php'; ?>
        <?php else: ?>
          <?php include __DIR__ . '/partials/group-extra.php'; ?>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>

    <input type="hidden" name="product_id" value="<?= $pId ?>">
    <?php if ($parentProductId): ?>
      <input type="hidden" name="parent_id" value="<?= $parentProductId ?>">
    <?php endif; ?>
    <?php if (isset($returnToParent) && $returnToParent): ?>
      <input type="hidden" name="return_to_parent" value="1">
    <?php endif; ?>
    <?php if (isset($_GET['edit_cart_item']) && $editCartItemUid): ?>
      <input type="hidden" name="edit_cart_item" value="<?= e($editCartItemUid) ?>">
    <?php endif; ?>
    <?php if ($qtyGet !== null): ?>
      <input type="hidden" name="qty" value="<?= (int)$qtyGet ?>">
    <?php endif; ?>
    <?php if ($unitIndex > 0): ?>
      <input type="hidden" name="unit" value="<?= $unitIndex ?>">
      <input type="hidden" name="total_units" value="<?= $totalUnits ?>">
    <?php endif; ?>
  </div>

  <?php include __DIR__ . '/partials/footer-actions.php'; ?>
</form>

  <script src="<?= base_url('assets/customization.js') ?>?v=<?= e($customizationJsVersion) ?>" defer></script>
</body>
</html>
