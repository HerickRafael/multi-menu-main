<?php if ($isCombo): ?>
<section class="combo" aria-label="Montar combo">
  <?php foreach ($comboGroups as $gi => $group): ?>
    <?php
    $gname = (string)($group['name'] ?? ('Etapa '.($gi + 1)));
    $items = $group['items'] ?? [];
    $gType = $group['type'] ?? 'single';
    $gMin  = isset($group['min']) ? (int)$group['min'] : 0;
    $gMax  = isset($group['max']) ? (int)$group['max'] : 1;
    ?>
    <div class="group">
      <h2><?= e($gname) ?></h2>
      <div class="choice-row"
           data-group-index="<?= (int)$gi ?>"
           data-group-type="<?= e($gType) ?>"
           data-min="<?= $gMin ?>"
           data-max="<?= $gMax ?>">
        <?php
        $defaultItemPrice = null;
        foreach ($items as $tempOpt) {
            if (!empty($tempOpt['default'])) {
                if (isset($tempOpt['price_override']) && $tempOpt['price_override'] !== null) {
                    $defaultItemPrice = (float)$tempOpt['price_override'];
                } elseif (isset($tempOpt['base_price']) && $tempOpt['base_price'] !== null) {
                    $defaultItemPrice = (float)$tempOpt['base_price'];
                }
                break;
            }
        }

        foreach ($items as $ii => $opt):
            $isDefault = !empty($opt['default']);
            $basePrice = isset($opt['base_price']) && $opt['base_price'] !== null ? (float)$opt['base_price'] : null;
            $priceOverride = isset($opt['price_override']) && $opt['price_override'] !== null ? (float)$opt['price_override'] : null;
            $delta = isset($opt['delta']) ? (float)$opt['delta'] : 0.0;

            $effectivePrice = null;
            if ($priceOverride !== null) {
                $effectivePrice = $priceOverride;
            } elseif ($basePrice !== null && abs($delta) > 0.009) {
                $effectivePrice = $basePrice;
            } elseif ($basePrice !== null) {
                $effectivePrice = $basePrice;
            }

            if ($isDefault) {
                $priceLabel = 'Incluído';
                $priceDiff = 0;
            } elseif (abs($delta) > 0.009) {
                $priceDiff = $delta;
                if ($delta > 0) {
                    $priceLabel = '+ ' . price_br($delta);
                } else {
                    $priceLabel = '− ' . price_br(abs($delta));
                }
            } elseif ($defaultItemPrice !== null && $effectivePrice !== null) {
                $priceDiff = $effectivePrice - $defaultItemPrice;
                if (abs($priceDiff) <= 0.009) {
                    $priceLabel = 'Incluído';
                } elseif ($priceDiff > 0) {
                    $priceLabel = '+ ' . price_br($priceDiff);
                } else {
                    $priceLabel = '− ' . price_br(abs($priceDiff));
                }
            } elseif ($effectivePrice !== null && $effectivePrice > 0.009) {
                $priceLabel = price_br($effectivePrice);
                $priceDiff = $effectivePrice;
            } else {
                $priceLabel = 'Incluído';
                $priceDiff = 0;
            }

            $comboImg = local_upload_src($opt['image'] ?? null);
            $simpleId = (int)($opt['simple_id'] ?? 0);
            $canCustomizeChoice = !empty($opt['customizable']) && $simpleId > 0;
            $defaultQty = isset($opt['default_qty']) ? (int)$opt['default_qty'] : ($isDefault ? 1 : 0);
            $parentQuery = http_build_query(['parent_id' => $pId]);
            $choiceCustomUrl = $canCustomizeChoice
              ? base_url($slug . '/produto/' . $simpleId . '/customizar?' . $parentQuery)
              : null;
            $fallbackId = 'combo-fallback-' . $gi . '-' . $ii;
            ?>
          <div class="choice <?= $isDefault ? 'sel' : '' ?>"
               data-group="<?= (int)$gi ?>"
               data-id="<?= (int)($opt['id'] ?? 0) ?>"
               data-simple="<?= $simpleId ?>"
               data-product-id="<?= $simpleId ?>"
               data-delta="<?= e(number_format($priceDiff, 2, '.', '')) ?>"
               data-effective-price="<?= $effectivePrice !== null ? e(number_format($effectivePrice, 2, '.', '')) : '0' ?>"
               data-default="<?= $isDefault ? '1' : '0' ?>"
               data-default-qty="<?= $defaultQty ?>"
               <?php if ($basePrice !== null): ?>data-base-price="<?= e(number_format($basePrice, 2, '.', '')) ?>"<?php endif; ?>
               data-customizable="<?= $canCustomizeChoice ? '1' : '0' ?>"
               <?php if ($choiceCustomUrl): ?>data-custom-url="<?= e($choiceCustomUrl) ?>"<?php endif; ?>>
            <button type="button" class="ring" aria-pressed="<?= $isDefault ? 'true' : 'false' ?>">
              <img <?= lazyImageAttrs($comboImg, $opt['name'] ?? '', ['class' => '', 'sizes' => 'card']) ?> data-fallback-target="<?= e($fallbackId) ?>">
              <div id="<?= e($fallbackId) ?>" class="hero-product-placeholder" style="display:none;">
                <?= svg_product('image-placeholder') ?>
              </div>
            </button>
            <span class="mark" aria-hidden="true">
              <?= svg_product('check') ?>
            </span>
            <div class="choice-name"><?= e($opt['name'] ?? '') ?></div>
            <div class="choice-price"><?= e($priceLabel) ?></div>
            <?php if ($canCustomizeChoice): ?>
              <?php if ($defaultQty > 1 && $isDefault):
                $unitUrl = $choiceCustomUrl . '&unit=1&total_units=' . $defaultQty;
              ?>
                <a class="choice-customize combo-customize <?= $isDefault ? '' : 'hidden' ?>"
                   data-base-url="<?= e($choiceCustomUrl) ?>"
                   data-total-units="<?= $defaultQty ?>"
                   href="<?= e($unitUrl) ?>">Personalizar</a>
              <?php else: ?>
                <a class="choice-customize combo-customize <?= $isDefault ? '' : 'hidden' ?>" data-base-url="<?= e($choiceCustomUrl) ?>" href="<?= e($choiceCustomUrl) ?>">Personalizar</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>
