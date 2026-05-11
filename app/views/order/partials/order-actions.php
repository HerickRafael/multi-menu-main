<?php

declare(strict_types=1);
?>
<div class="buttons">
  <a href="<?= e($profileUrl) ?>" class="cta">Voltar ao perfil</a>

  <?php if ($status === 'pending' || $status === 'paid'): ?>
    <form method="post" action="<?= e(base_url($slugClean . '/order/' . $orderId . '/cancel')) ?>" data-confirm="Tem certeza que deseja cancelar este pedido?">
      <?php if (function_exists('csrf_field')): echo csrf_field(); endif; ?>
      <button class="ghost-btn danger" type="submit">
        <?= orderSvg('cancel', 'order-cancel-svg') ?>
        Cancelar pedido
      </button>
    </form>
  <?php endif; ?>
</div>
