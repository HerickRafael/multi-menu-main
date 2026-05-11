<?php
$selectedItems = [];
foreach ($items as $ii => $it) {
    if (!empty($it['default']) || !empty($it['selected'])) {
        $qty = 1;
        if (isset($it['selected_qty'])) {
            $qty = max(1, (int)$it['selected_qty']);
        } elseif (isset($it['default_qty']) && (int)$it['default_qty'] > 0) {
            $qty = (int)$it['default_qty'];
        }
        $selectedItems[$ii] = $qty;
    }
}
?>
<div class="single-group" data-group="g<?= (int)$gi ?>" data-min="<?= (int)$gMin ?>" data-max="<?= (int)$gMax ?>">
<?php foreach ($items as $ii => $it):
    $isSel = isset($selectedItems[$ii]);
    $itemQty = $isSel ? $selectedItems[$ii] : 1;
    $defaultQty = isset($it['default_qty']) ? (int)$it['default_qty'] : 0;
    $img = $it['image_path'] ?? $it['img'] ?? null;
    $fallbackId = 'single-fallback-' . $gi . '-' . $ii;
?>
  <div class="row radio" data-radio="g<?= (int)$gi ?>" data-id="<?= (int)$ii ?>" data-default-qty="<?= $defaultQty ?>">
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
      <?php $optName = $it['name'] ?? $it['label'] ?? ('Opção '.($ii + 1)); ?>
      <div class="name"><?= e($optName) ?></div>
      <?php $sale = isset($it['sale_price']) ? (float)$it['sale_price'] : 0.0; ?>
      <?php if ($sale > 0): ?>
        <div class="price"><?= price_br($sale) ?></div>
      <?php endif; ?>
    </div>
    <div class="radio-wrap">
      <?php $displayQty = $isSel ? $itemQty : max(1, $defaultQty); ?>
      <div class="quantity-selector <?= $isSel ? 'active' : '' ?>" data-group="g<?= (int)$gi ?>" data-id="<?= (int)$ii ?>">
        <div class="qs-dot"></div>
        <div class="qs-controls">
          <button type="button" class="qs-btn qs-dec" aria-label="Diminuir">−</button>
          <span class="qs-count"><?= $displayQty ?></span>
          <button type="button" class="qs-btn qs-inc" aria-label="Aumentar">+</button>
        </div>
      </div>
    </div>
    <input type="hidden" class="single-item-input" name="custom_single_items[<?= (int)$gi ?>][<?= (int)$ii ?>]" value="<?= $isSel ? $itemQty : 0 ?>">
  </div>
<?php endforeach; ?>
</div>
