<div class="hero-wrap">
  <a class="nav-btn" href="<?= e($homeUrl) ?>" aria-label="Voltar">
    <?= svg_product('back') ?>
  </a>

  <a class="cart-btn" id="product-cart-btn" href="<?= e($cartUrl) ?>" aria-label="Ver sacola">
    <?= svg_product('cart') ?>
    <?php if ($isLogged && $cartCount > 0): ?>
      <span class="cart-badge"><?= $cartCount > 99 ? '99+' : $cartCount ?></span>
    <?php endif; ?>
  </a>

  <div class="hero" aria-hidden="true"></div>

  <?php
    $imgSrc = local_upload_src($product['image'] ?? null);
    $imgAlt = !empty($product['name']) ? $product['name'] : 'Imagem do produto';
  ?>
  <div class="hero-product-container">
    <?php if (!empty($imgSrc) && trim($imgSrc) !== ''): ?>
      <img <?= lazyImageAttrs($imgSrc, $imgAlt, ['class' => 'hero-product', 'sizes' => 'hero', 'eager' => true]) ?>
           data-fallback-target="hero-product-fallback">
      <div id="hero-product-fallback" class="hero-product-placeholder" style="display: none;">
        <?= svg_product('image-placeholder') ?>
      </div>
    <?php else: ?>
      <div class="hero-product-placeholder">
        <?= svg_product('image-placeholder') ?>
      </div>
    <?php endif; ?>
  </div>
</div>
