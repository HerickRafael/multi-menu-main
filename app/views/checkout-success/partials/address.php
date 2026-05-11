<?php if ($successOrder->hasAddress()): ?>
  <div class="block">
    <span class="section-title">Endereço de entrega</span>
    <div class="note"><?= nl2br(e($successOrder->address)) ?></div>
  </div>
<?php endif; ?>
