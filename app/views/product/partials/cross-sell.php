<?php if (!$isCombo && !empty($crossSellSections) && is_array($crossSellSections)): ?>
  <?php foreach ($crossSellSections as $section): ?>
    <?php if (!empty($section['title']) && !empty($section['products']) && is_array($section['products'])): ?>
      <section class="combo cross-sell-section" aria-label="<?= e($section['title']) ?>">
        <div class="group cross-sell-group">
          <h2><?= e($section['title']) ?></h2>
          <div class="choice-row cross-sell-row">
            <?php foreach ($section['products'] as $index => $cs): ?>
              <?php
                $csId = (int)($cs['id'] ?? 0);
                if ($csId <= 0) {
                    continue;
                }

                $csName = (string)($cs['name'] ?? '');
                $csImage = local_upload_src($cs['image'] ?? null);
                $csPrice = (float)($cs['price'] ?? 0);
                $csPromo = isset($cs['promo_price']) && $cs['promo_price'] !== null && $cs['promo_price'] !== '' ? (float)$cs['promo_price'] : null;
                $csHasIngredients = isset($cs['has_ingredients']) && (int)$cs['has_ingredients'] > 0;

                $displayPrice = $csPrice;
                if ($csPromo !== null && $csPromo > 0 && $csPromo < $csPrice) {
                    $displayPrice = $csPromo;
                }
                $priceLabel = price_br($displayPrice);
                $fallbackId = 'crosssell-fallback-' . $csId . '-' . $index;
              ?>
              <div class="choice cross-sell-item"
                   data-product-id="<?= $csId ?>"
                   data-price="<?= e(number_format($displayPrice, 2, '.', '')) ?>"
                   <?php if ($csHasIngredients): ?>
                   data-customizable="1"
                   data-custom-url="<?= base_url("{$slug}/produto/{$csId}") ?>"
                   <?php endif; ?>>
                <button type="button" class="ring" aria-pressed="false">
                  <img <?= lazyImageAttrs($csImage, $csName, ['class' => '', 'sizes' => 'card', 'eager' => true]) ?> data-fallback-target="<?= e($fallbackId) ?>">
                  <div id="<?= e($fallbackId) ?>" class="hero-product-placeholder" style="display:none;">
                    <?= svg_product('image-placeholder') ?>
                  </div>
                </button>
                <span class="mark" aria-hidden="true">
                  <?= svg_product('check') ?>
                </span>
                <div class="choice-name"><?= e($csName) ?></div>
                <div class="choice-price"><?= e($priceLabel) ?></div>
                <?php if ($csHasIngredients): ?>
                  <a class="choice-customize cross-sell-customize hidden"
                     data-base-url="<?= base_url("{$slug}/produto/{$csId}") ?>"
                     data-cross-sell-id="<?= $csId ?>"
                     href="<?= base_url("{$slug}/produto/{$csId}") ?>">Personalizar</a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>
