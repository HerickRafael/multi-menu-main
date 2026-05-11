<?php

require_once __DIR__ . '/../../helpers/lazy_loading_helper.php';
require_once __DIR__ . '/helpers/svg_helper.php';

$company      = $company      ?? [];
$product      = $product      ?? [];
$comboGroups  = $comboGroups  ?? null;
$mods         = $mods         ?? [];
$hasCustomization = isset($hasCustomization) ? (bool)$hasCustomization : (!empty($mods));
$isOpenNow    = $isOpenNow    ?? true;
$nextOpening  = $nextOpening  ?? null;

$slug     = (string)($company['slug'] ?? '');
$pId      = (int)($product['id'] ?? 0);
$homeUrl  = base_url($slug !== '' ? $slug : '');
$cartUrl  = base_url($slug . '/cart');
$priceMode = $product['price_mode'] ?? 'fixed';

$customizeBase = base_url($slug . '/produto/' . $pId . '/customizar');
$addToCartUrl  = base_url($slug . '/cart/add');
$requireLogin  = (bool)(config('login_required') ?? false);
$isLogged      = isset($_SESSION['customer']) && (!isset($_SESSION['customer']['company_slug']) || $_SESSION['customer']['company_slug'] === $slug);
$forceLoginModal = !empty($forceLoginModal);

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += isset($item['qty']) ? (int)$item['qty'] : 1;
    }
}

$comboGroupsRaw = is_array($comboGroups) ? $comboGroups : [];
$comboGroups    = [];

foreach ($comboGroupsRaw as $gIndex => $group) {
    if (!is_array($group)) {
        continue;
    }

    $itemsRaw = $group['items'] ?? [];

    if (!is_array($itemsRaw) || !$itemsRaw) {
        continue;
    }

    $items = [];

    foreach ($itemsRaw as $item) {
        if (!is_array($item)) {
            continue;
        }

        $simpleId = isset($item['simple_id'])
          ? (int)$item['simple_id']
          : (int)($item['simple_product_id'] ?? $item['product_id'] ?? 0);

        if ($simpleId <= 0) {
            continue;
        }

        $comboItemId = isset($item['id']) ? (int)$item['id'] : $simpleId;

        $basePrice = null;
        if (isset($item['base_price'])) {
            $basePrice = (float)$item['base_price'];
        } elseif (isset($item['price'])) {
            $basePrice = (float)$item['price'];
        }

        $priceOverride = null;
        if (isset($item['price_override']) && $item['price_override'] !== null && $item['price_override'] !== '') {
            $priceOverride = (float)$item['price_override'];
        }

        $delta = 0.0;

        if (isset($item['delta'])) {
            $delta = (float)$item['delta'];
        } elseif (isset($item['delta_price'])) {
            $delta = (float)$item['delta_price'];
        }

        $isDefault      = !empty($item['default']) || !empty($item['is_default']);
        $allowCustomize = !empty($item['customizable']) || !empty($item['allow_customize']);
        $defaultQty     = isset($item['default_qty']) ? (int)$item['default_qty'] : ($isDefault ? 1 : 0);

        $items[] = [
          'id'             => $comboItemId,
          'simple_id'      => $simpleId,
          'name'           => (string)($item['name'] ?? ''),
          'image'          => $item['image'] ?? null,
          'base_price'     => $basePrice,
          'price_override' => $priceOverride,
          'delta'          => $delta,
          'default'        => $isDefault,
          'customizable'   => $allowCustomize,
          'default_qty'    => $defaultQty,
        ];
    }

    if (!$items) {
        continue;
    }

    $minQty = isset($group['min']) ? (int)$group['min'] : (int)($group['min_qty'] ?? 0);
    $maxQty = isset($group['max']) ? (int)$group['max'] : (int)($group['max_qty'] ?? 1);
    $type   = isset($group['type']) && $group['type'] !== '' ? (string)$group['type'] : 'single';
    $name   = trim((string)($group['name'] ?? ''));

    if ($name === '') {
        $name = 'Grupo ' . ((int)$gIndex + 1);
    }

    $comboGroups[] = [
      'id'        => isset($group['id']) ? (int)$group['id'] : null,
      'name'      => $name,
      'type'      => $type,
      'min'       => $minQty,
      'max'       => $maxQty,
      'items'     => array_values($items),
    ];
}

$isCombo = (isset($product['type']) && $product['type'] === 'combo' && !empty($comboGroups));
$promoCountdownJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/js/promo-countdown.min.js';
$productJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/product.js';
$promoCountdownJsVersion = is_file($promoCountdownJsPath) ? (string) filemtime($promoCountdownJsPath) : (string) time();
$productJsVersion = is_file($productJsPath) ? (string) filemtime($productJsPath) : (string) time();
?>
<!doctype html>
<html lang="pt-br">
<?php include __DIR__ . '/partials/head.php'; ?>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/hero.php'; ?>
  <?php include __DIR__ . '/partials/product-info.php'; ?>
  <?php include __DIR__ . '/partials/cross-sell.php'; ?>
  <?php include __DIR__ . '/partials/combo-groups.php'; ?>
  </main>
  <?php include __DIR__ . '/partials/footer-cta.php'; ?>
</div>

<?php include dirname(__DIR__) . '/public/partials/login_modal.php'; ?>
<script src="<?= base_url('js/promo-countdown.min.js') ?>?v=<?= e($promoCountdownJsVersion) ?>" defer></script>
<script src="<?= base_url('assets/product.js') ?>?v=<?= e($productJsVersion) ?>" defer></script>
</body>
</html>
