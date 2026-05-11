<main class="card" role="main">
  <div class="brand">
    <h1><?= e($product['name'] ?? '') ?></h1>
  </div>

  <div class="price-row">
    <div class="price">
      <?php
    $originalPrice = isset($product['original_price']) ? (float)$product['original_price'] : (isset($product['price']) ? (float)$product['price'] : 0);
    $price = (float)($product['price'] ?? 0);
    $embeddedFee = isset($product['embedded_delivery_fee']) ? (float)$product['embedded_delivery_fee'] : 0;

$rawPromo = $product['promo_price'] ?? null;

$promoEndTs = null;
$nowTs = time();
if (!empty($product['promo_start_at']) && strtotime($product['promo_start_at']) > $nowTs) {
    $rawPromo = null;
}
if (!empty($product['promo_end_at'])) {
    $promoEndTs = strtotime($product['promo_end_at']);
    if ($promoEndTs < $nowTs) {
        $rawPromo = null;
    }
}

$promo = null;

if ($rawPromo !== null && $rawPromo !== '') {
    $promoStr = is_array($rawPromo) ? reset($rawPromo) : $rawPromo;
    $promoStr = trim((string)$promoStr);

    if ($promoStr !== '') {
        $promoStr = str_replace(' ', '', $promoStr);

        if (strpos($promoStr, ',') !== false && strpos($promoStr, '.') !== false) {
            $promoStr = str_replace('.', '', $promoStr);
        }
        $promoStr = str_replace(',', '.', $promoStr);

        if (is_numeric($promoStr)) {
            $promo = (float)$promoStr;
        }
    }
}

$priceMode = $product['price_mode'] ?? 'fixed';
$isPercentPromo = ($priceMode === 'sum') && $promo !== null && $promo > 0 && $promo <= 100;

if ($isPercentPromo):
    $discountedPrice = $originalPrice * (1 - ($promo / 100.0));
    $discountedPrice += $embeddedFee;
    $discount = (int)round($promo);
    ?>
        <div class="price-original"><?= price_br($price) ?></div>
        <div class="price-current-row">
          <span class="price-current"><?= price_br($discountedPrice) ?></span>
          <span class="price-discount"><?= $discount ?>% OFF</span>
        </div>
        <?php if ($promoEndTs): ?>
          <div class="promo-countdown" data-end="<?= (int)$promoEndTs ?>" style="font-size:12px;color:#dc2626;font-weight:600;margin-top:4px;">
            ⏰ <span class="cd-text">Calculando...</span>
          </div>
        <?php endif; ?>
  <?php
else:
    $hasPromo = $originalPrice > 0 && $promo !== null && $promo > 0 && $promo < $originalPrice;

    if ($hasPromo):
        $promoWithFee = $promo + $embeddedFee;
        $discount = $originalPrice > 0 ? (int)floor((($originalPrice - $promo) / $originalPrice) * 100) : 0;
  ?>
        <div class="price-original"><?= price_br($price) ?></div>
        <div class="price-current-row">
          <span class="price-current"><?= price_br($promoWithFee) ?></span>
          <?php if ($discount > 0): ?>
            <span class="price-discount"><?= $discount ?>% OFF</span>
          <?php endif; ?>
        </div>
        <?php if ($promoEndTs): ?>
          <div class="promo-countdown" data-end="<?= (int)$promoEndTs ?>" style="font-size:12px;color:#dc2626;font-weight:600;margin-top:4px;">
            ⏰ <span class="cd-text">Calculando...</span>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="price-single"><?= price_br($price) ?></div>
      <?php endif; ?>
  <?php endif; ?>
    </div>

    <div class="stepper" aria-label="Selecionar quantidade">
      <button class="st-btn" type="button" data-act="dec" aria-label="Diminuir">
        <?= svg_product('minus') ?>
      </button>
      <div class="st-val" id="qval" data-role="val">1</div>
      <button class="st-btn" type="button" data-act="inc" aria-label="Aumentar">
        <?= svg_product('plus') ?>
      </button>
    </div>
  </div>

  <?php if (!empty($product['description'])): ?>
  <section class="section">
    <h3>Sobre</h3>
    <p class="body"><?= nl2br(e($product['description'])) ?></p>
  </section>
  <?php endif; ?>
