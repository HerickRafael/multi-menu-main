<div class="footer">
  <?php
  $isLastUnit = ($unitIndex <= 0) || ($unitIndex >= $totalUnits);
  $nextUnit = $unitIndex + 1;
  $prevUnit = $unitIndex - 1;

  if ($unitIndex > 1 && $totalUnits > 1):
    $parentQuery = $parentProductId ? 'parent_id=' . $parentProductId . '&' : '';
    $backToUnitUrl = base_url($encodedSlug . '/produto/' . $pId . '/customizar?' . $parentQuery . 'unit=' . $prevUnit . '&total_units=' . $totalUnits);
  ?>
    <a href="<?= e($backToUnitUrl) ?>" class="btn-cancel" style="display:flex;align-items:center;justify-content:center;text-decoration:none">← Voltar</a>
  <?php else: ?>
    <a href="<?= e($cancelUrl) ?>" class="btn-cancel" style="display:flex;align-items:center;justify-content:center;text-decoration:none">Cancelar</a>
  <?php endif; ?>

  <?php if ($isLastUnit): ?>
    <button type="submit" class="btn-confirm">Confirmar</button>
  <?php else: ?>
    <button type="submit" class="btn-confirm">Ir para <?= $nextUnit ?>º →</button>
  <?php endif; ?>
</div>
