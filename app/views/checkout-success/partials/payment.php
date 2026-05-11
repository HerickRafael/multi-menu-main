<?php if ($successOrder->hasPayment()): ?>
  <div class="block">
    <span class="section-title">Pagamento</span>
    <p><strong><?= e($successOrder->payment) ?></strong></p>
    <?php if ($successOrder->instructions !== ''): ?>
      <div class="note"><?= nl2br(e($successOrder->instructions)) ?></div>
    <?php endif; ?>
  </div>
<?php endif; ?>
