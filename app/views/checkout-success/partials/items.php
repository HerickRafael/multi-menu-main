<?php if ($successOrder->hasItems()): ?>
  <div class="block">
    <span class="section-title">Itens do pedido</span>
    <div class="items">
      <?php foreach ($successOrder->items as $item): ?>
        <div class="item-row">
          <span><?= e($item->quantity . 'x ' . $item->name) ?></span>
          <span><?= e($item->formatDisplayPrice()) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
