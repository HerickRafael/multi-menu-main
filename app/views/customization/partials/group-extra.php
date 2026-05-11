<div class="list" aria-label="<?= e($gName) ?>">
<?php foreach ($items as $ii => $it):
    $img   = $it['image_path'] ?? $it['img'] ?? null;
    $min   = isset($it['min']) ? (int)$it['min'] : 0;
    $max   = isset($it['max']) ? (int)$it['max'] : 5;
    $qty   = isset($it['qty']) ? (int)$it['qty'] : (!empty($it['default']) ? (int)($it['default_qty'] ?? $min) : $min);
    $sale  = isset($it['sale_price']) ? (float)$it['sale_price'] : (float)($it['delta'] ?? 0);
    $fallbackId = 'extra-fallback-' . $gi . '-' . $ii;
?>
  <div class="row" data-id="<?= (int)$ii ?>" data-min="<?= $min ?>" data-max="<?= $max ?>">
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
      <?php if ($sale > 0): ?>
        <div class="price"><?= price_br($sale) ?></div>
      <?php endif; ?>
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
