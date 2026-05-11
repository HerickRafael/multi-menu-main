<?php

$quantityControlLabel = isset($quantityControlLabel) ? (string)$quantityControlLabel : 'Produto';
$quantityControlUid = isset($quantityControlUid) ? (string)$quantityControlUid : '';
$quantityControlQty = isset($quantityControlQty) ? (int)$quantityControlQty : 1;
?>
<div class="qty" role="group" aria-label="Quantidade de <?= e($quantityControlLabel) ?>">
  <form method="post" action="<?= e($updateUrl) ?>">
    <input type="hidden" name="uid" value="<?= e($quantityControlUid) ?>">
    <button class="btn" type="submit" name="action" value="dec" aria-label="Diminuir">&minus;</button>
    <span class="val"><?= $quantityControlQty ?></span>
    <button class="btn" type="submit" name="action" value="inc" aria-label="Aumentar">+</button>
  </form>
</div>
