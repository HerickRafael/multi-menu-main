<div class="summary">
  <div class="summary-row"><span>Subtotal</span><span><?= e($successOrder->formatSubtotal()) ?></span></div>
  <div class="summary-row"><span>Entrega</span><span><?= e($successOrder->formatDelivery()) ?></span></div>
  <div class="summary-row total"><span>Total</span><span><?= e($successOrder->formatTotal()) ?></span></div>
</div>
