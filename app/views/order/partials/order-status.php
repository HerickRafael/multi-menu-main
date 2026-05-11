<?php

declare(strict_types=1);
?>
<div class="card card-status">
  <h2>
    <span class="card-status-heading">Acompanhamento</span>
    <span class="status-badge" style="<?= e($statusStyle) ?>"><?= e($statusLabel) ?></span>
  </h2>

  <div class="order-info-list">
    <div class="order-info-row">
      <div class="order-info-icon"><?= orderSvg('clock', 'order-info-svg') ?></div>
      <div class="order-info-body">
        <div class="order-info-label">DATA DO PEDIDO</div>
        <?php
          $createdTs = !empty($order['created_at']) ? strtotime((string) $order['created_at']) : false;
          $createdFormatted = ($createdTs !== false && $createdTs > 0) ? date('d/m/Y H:i', $createdTs) : '—';
        ?>
        <div class="order-info-value"><?= e($createdFormatted) ?></div>
      </div>
    </div>

    <div class="order-info-row">
      <div class="order-info-icon"><?= orderSvg('user', 'order-info-svg') ?></div>
      <div class="order-info-body">
        <div class="order-info-label">CLIENTE</div>
        <div class="order-info-value"><?= e($order['customer_name'] ?? '') ?> <span class="order-info-sub">(<?= e($order['customer_phone'] ?? '') ?>)</span></div>
      </div>
    </div>

    <?php if (!empty($order['customer_address'])): ?>
    <div class="order-info-row">
      <div class="order-info-icon"><?= orderSvg('map', 'order-info-svg') ?></div>
      <div class="order-info-body">
        <div class="order-info-label">ENDEREÇO</div>
        <div class="order-info-value order-info-value--wrap"><?= e($order['customer_address']) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($order['notes'])): ?>
    <div class="order-info-row">
      <div class="order-info-icon"><?= orderSvg('notes', 'order-info-svg') ?></div>
      <div class="order-info-body">
        <div class="order-info-label">OBSERVAÇÕES</div>
        <div class="order-info-value order-info-value--wrap"><?= e($order['notes']) ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
