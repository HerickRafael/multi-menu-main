<?php

declare(strict_types=1);
?>
<div class="topbar">
  <div class="topwrap">
    <a href="<?= e($profileUrl) ?>" class="back" aria-label="Voltar">
      <?= orderSvg('back') ?>
    </a>
    <div class="title">Pedido #<?= (int) $orderNumber ?></div>
  </div>
</div>
