<?php

require_once __DIR__ . '/../../helpers/lazy_loading_helper.php';
require_once __DIR__ . '/helpers/svg_helper.php';

$items  = isset($items) && is_array($items) ? $items : [];
$totals = isset($totals) && is_array($totals) ? $totals : ['subtotal' => 0.0, 'total' => 0.0];
$company = $company ?? [];
$slug = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$customer = $customer ?? null;
$requireLogin = !empty($requireLogin);

$slugClean = trim($slug, '/');
$slugEncoded = $slugClean !== '' ? rawurlencode($slugClean) : '';
$basePath = $slugEncoded !== '' ? $slugEncoded : '';
$homeUrl = function_exists('base_url') ? base_url($basePath) : '#';
$updateUrl = isset($updateUrl) ? (string)$updateUrl : (function_exists('base_url') ? base_url(($basePath !== '' ? $basePath . '/' : '') . 'cart/update') : '#');
$checkoutUrl = function_exists('base_url') ? base_url(($basePath !== '' ? $basePath . '/' : '') . 'checkout') : '#';

if ($requireLogin && !$customer) {
    $checkoutTarget = '/' . ltrim(parse_url(base_url(($basePath !== '' ? $basePath . '/' : '') . 'checkout'), PHP_URL_PATH), '/');
    $checkoutUrl = $homeUrl . '?login=1&redirect_to=' . urlencode($checkoutTarget);
}
$backUrl = $homeUrl;
$formatBrl = static function ($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); };
$uploadSrc = static function (?string $value, string $fallback = 'assets/logo-placeholder.png') {
    $raw = trim((string)($value ?? ''));

    if ($raw === '') {
        return base_url($fallback);
    }
    $path = parse_url($raw, PHP_URL_PATH);

    if ($path && strpos($path, '/uploads/') !== false) {
        return base_url(ltrim($path, '/'));
    }

    if (preg_match('/^https?:\/\//i', $raw)) {
        return $raw;
    }

    if ($path) {
        $raw = $path;
    }
    $raw = ltrim($raw, '/');

    if (strpos($raw, 'uploads/') === 0) {
        return base_url($raw);
    }

    return base_url('uploads/' . basename($raw));
};
$companyName = $company['name'] ?? 'Meu Carrinho';
$cartCssPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/cart.css';
$cartJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/cart.js';
$cartCssVersion = is_file($cartCssPath) ? (string) filemtime($cartCssPath) : (string) time();
$cartJsVersion = is_file($cartJsPath) ? (string) filemtime($cartJsPath) : (string) time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="csrf-token" content="<?= \App\Middleware\CsrfProtection::generateToken() ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover" />
<title>Sacola — <?= e($companyName) ?></title>
<?php if (!empty($company['logo'])): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
<link rel="apple-touch-icon" href="<?= e(base_url($company['logo'])) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/cart.css')) ?>?v=<?= e($cartCssVersion) ?>">
</head>
<body>
<div class="container">
  <div class="topbar">
    <div class="topwrap">
  <a class="back" href="<?= e($backUrl) ?>" data-action="navigate">
        <?= cartSvg('back') ?>
      </a>
      <div class="title">Minha Sacola</div>
    </div>
  </div>

  <?php if (isset($_SESSION['error_message'])): ?>

    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>

  <?php if (isset($_GET['reorder'])): ?>
    <?php if ($_GET['reorder'] === 'partial'): ?>
      <div class="reorder-alert reorder-alert--warning">
        ⚠️ <?= (int)($_GET['added'] ?? 0) ?> item(ns) adicionado(s). <?= (int)($_GET['skipped'] ?? 0) ?> não está(ão) mais disponível(is).
      </div>
    <?php else: ?>
      <div class="reorder-alert reorder-alert--success">
        ✅ Itens do pedido anterior adicionados à sacola!
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if (!$items): ?>
    <div class="empty">
      <div class="empty-icon">
        <?= cartSvg('empty-cart') ?>
      </div>
      <h2 class="empty-title">Sua sacola está vazia</h2>
      <p class="empty-text">Explore nosso cardápio e descubra<br>opções deliciosas para você!</p>
      <a href="<?= e($homeUrl) ?>" class="empty-btn">
        <?= cartSvg('home') ?>
        Ver Cardápio
      </a>
    </div>
  <?php endif; ?>

  <div class="products-area">
  <?php foreach ($items as $index => $item): ?>
    <?php
      $uid = preg_replace('/[^a-z0-9]/i', '', (string)($item['uid'] ?? 'u' . $index)) ?: 'u' . $index;
      $hasCombo = !empty($item['combo']['groups']);
      $eps = 0.009;
    ?>
    <?php if ($hasCombo): ?>
      <?php include __DIR__ . '/partials/combo.php'; ?>
      <?php continue; ?>
    <?php endif; ?>
    <?php include __DIR__ . '/partials/item.php'; ?>
  <?php endforeach; ?>

  <?php if ($items): ?>
    <div class="add-more">
      <a href="<?= e($homeUrl) ?>" class="add-more-btn">
        <?= cartSvg('add') ?>
        Adicionar mais itens
      </a>
    </div>
  <?php endif; ?>
  </div><!-- .products-area -->

  <?php include __DIR__ . '/partials/totals.php'; ?>

</div>

<div class="footer">
    <?php if ($items): ?>
      <?php 
      $canCheckout = !($minOrder > 0 && $subtotal < $minOrder);
      if ($canCheckout): 
      ?>
        <a class="cta" href="<?= e($checkoutUrl) ?>">Ir para o checkout</a>
      <?php else: ?>
        <button class="cta cta--disabled" type="button" disabled title="Adicione mais R$ <?= number_format($minOrder - $subtotal, 2, ',', '.') ?> para atingir o pedido mínimo">
          Pedido mínimo: <?= e($formatBrl($minOrder)) ?>
        </button>
      <?php endif; ?>
    <?php else: ?>
      <button class="cta" type="button" disabled>Ir para o checkout</button>
    <?php endif; ?>
  </div>

<?php
  $cartConfig = [
    'currentUserPhone' => (string)($customer['whatsapp'] ?? ''),
    'couponPlaceholderPrefix' => strtoupper((string)($coupon_prefix ?? 'WOLL')),
    'validateCouponUrl' => base_url($slug . '/validate-coupon'),
  ];
?>
<script id="cart-page-config" type="application/json"><?= htmlspecialchars(json_encode($cartConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></script>
<script src="<?= e(base_url('assets/cart.js')) ?>?v=<?= e($cartJsVersion) ?>"></script>
</body>
</html>
