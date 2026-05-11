<?php $poolFree = isset($g['pool_free']) ? (int)$g['pool_free'] : (int)$gMax; ?>
<?php $poolMinEffective = max((int)$gMin, $poolFree); ?>
<div class="pool-counter" data-group="g<?= (int)$gi ?>">
  <span class="pool-counter-current" data-role="pool-sum">0</span>
  <span class="pool-counter-sep">/</span>
  <span class="pool-counter-max"><?= $poolFree ?></span>
  <span class="pool-counter-label">inclusos</span>
  <span class="pool-extras-badge" data-role="pool-extras"></span>
</div>
<div class="pool-group" data-group="g<?= (int)$gi ?>" data-pool-min="<?= $poolMinEffective ?>" data-pool-max="99" data-pool-free="<?= $poolFree ?>">
<?php foreach ($items as $ii => $it):
    $img = $it['image_path'] ?? $it['img'] ?? null;
    $max = isset($it['max']) ? (int)$it['max'] : 99;
    $qty = isset($it['qty']) ? (int)$it['qty'] : 0;
    $salePrice = isset($it['sale_price']) ? (float)$it['sale_price'] : 0.0;
    $fallbackId = 'pool-fallback-' . $gi . '-' . $ii;
?>
  <div class="row pool-row" data-id="<?= (int)$ii ?>" data-min="0" data-max="<?= $max ?>" data-price="<?= $salePrice ?>">
    <div class="thumb">
      <?php if (!empty($img) && trim($img) !== ''): ?>
        <img <?= lazyImageAttrs(base_url($img), $it['name'] ?? $it['label'] ?? '', ['class' => '', 'sizes' => 'thumb', 'eager' => true]) ?> data-fallback-target="<?= e($fallbackId) ?>">
        <div id="<?= e($fallbackId) ?>" class="ingredient-placeholder" style="display: none;">
          <?= svg_customization('ingredient-placeholder') ?>
        </div>
      <?php else: ?>
        <div class="ingredient-placeholder">
          <?= svg_customization('ingredient-placeholder') ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="info">
      <?php $itemName = $it['name'] ?? $it['label'] ?? ('Item '.($ii + 1)); ?>
      <div class="name"><?= e($itemName) ?></div>
      <div class="pool-item-price" data-role="pool-price" style="display:none"></div>
    </div>
    <div class="stepper">
      <button class="st-btn" type="button" data-act="dec" aria-label="Diminuir">−</button>
      <div class="st-val" data-role="val"><?= $qty ?></div>
      <button class="st-btn" type="button" data-act="inc" aria-label="Aumentar">+</button>
    </div>
    <input type="hidden" name="custom_qty[<?= (int)$gi ?>][<?= (int)$ii ?>]" value="<?= $qty ?>">
  </div>
<?php endforeach; ?>
</div>
