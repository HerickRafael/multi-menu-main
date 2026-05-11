<?php

$customizationName = isset($customizationName) ? (string)$customizationName : '';
$customizationMeta = isset($customizationMeta) ? (string)$customizationMeta : '';
?>
<div class="ing">
  <div class="ing-name"><?= e($customizationName) ?></div>
  <div class="ing-meta"><?= e($customizationMeta) ?></div>
</div>
