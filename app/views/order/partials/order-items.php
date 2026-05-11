<?php

declare(strict_types=1);

$subtotal = (float) ($order['subtotal'] ?? 0);
$deliveryFee = (float) ($order['delivery_fee'] ?? 0);
$discount = (float) ($order['discount'] ?? 0);
$loyaltyDiscount = (float) ($order['loyalty_discount'] ?? 0);
$total = (float) ($order['total'] ?? 0);
?>
<div class="card">
  <h2>Resumo do pedido</h2>

  <div class="summary-items">
    <?php if (!empty($order['items']) && is_array($order['items'])): ?>
      <?php foreach ($order['items'] as $item):
        $quantity = (int) ($item['quantity'] ?? 1);
        $productName = trim((string) ($item['product_name'] ?? 'Produto'));
        $lineTotal = (float) ($item['line_total'] ?? 0);
      ?>
        <div class="summary-item-wrapper">
          <div class="summary-item">
            <div class="product"><?= (int) $quantity ?>x <?= e($productName) ?></div>
            <div class="price">R$ <?= number_format($lineTotal, 2, ',', '.') ?></div>
          </div>

          <?php
          $comboData = null;
          $componentCustomizations = [];
          if (!empty($item['combo_data'])) {
              if (is_string($item['combo_data'])) {
                  $comboData = json_decode($item['combo_data'], true);
                  $comboData = json_last_error() === JSON_ERROR_NONE ? $comboData : null;
              } else {
                  $comboData = $item['combo_data'];
              }

              if (is_array($comboData) && !empty($comboData['component_customizations'])) {
                  $componentCustomizations = $comboData['component_customizations'];
              }
          }

          $customData = null;
          if (!empty($item['customization_data'])) {
              if (is_string($item['customization_data'])) {
                  $customData = json_decode($item['customization_data'], true);
                  $customData = json_last_error() === JSON_ERROR_NONE ? $customData : null;
              } else {
                  $customData = $item['customization_data'];
              }
          }
          ?>

          <?php if (($comboData && is_array($comboData)) || ($customData && is_array($customData))): ?>
            <div class="summary-item-details">
              <?php include __DIR__ . '/../../public/components/_order_item_details.php'; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="summary-divider"></div>

  <div class="summary-total">
    <div class="row subtotal">
      <span>Subtotal</span>
      <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
    </div>

    <?php if ($discount > 0): ?>
      <div class="row discount">
        <?php if (!empty($order['coupon_code'])): ?>
          <span>Cupom <?= e($order['coupon_code']) ?></span>
        <?php else: ?>
          <span>Desconto</span>
        <?php endif; ?>
        <span>- R$ <?= number_format($discount, 2, ',', '.') ?></span>
      </div>
    <?php endif; ?>

    <?php if ($loyaltyDiscount > 0): ?>
      <div class="row discount">
        <span>Desconto Fidelidade</span>
        <span>- R$ <?= number_format($loyaltyDiscount, 2, ',', '.') ?></span>
      </div>
    <?php endif; ?>

    <div class="row grand">
      <span>Total</span>
      <span>R$ <?= number_format($total, 2, ',', '.') ?></span>
    </div>
  </div>
</div>
