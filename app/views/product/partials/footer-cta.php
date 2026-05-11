<form class="footer <?= !$isOpenNow ? 'has-closed-info' : '' ?>" method="post" action="<?= e($addToCartUrl) ?>" data-requires-login="<?= $requireLogin ? '1' : '0' ?>" data-has-combo="<?= $isCombo ? '1' : '0' ?>" data-has-customization="<?= $hasCustomization ? '1' : '0' ?>" data-customize-url="<?= e($customizeBase) ?>">
  <input type="hidden" name="product_id" value="<?= $pId ?>">
  <input type="hidden" name="qty" id="qtyField" value="1">

  <?php if ($isCombo): ?>
    <?php foreach ($comboGroups as $gi => $group): ?>
      <?php
      $selId = null;
      foreach (($group['items'] ?? []) as $opt) {
          if (!empty($opt['default'])) {
              $selId = isset($opt['id']) ? (int)$opt['id'] : null;
              break;
          }
      }
      ?>
      <input type="hidden" name="combo[<?= (int)$gi ?>]" id="combo_field_<?= (int)$gi ?>" value="<?= $selId !== null ? (int)$selId : '' ?>">
    <?php endforeach; ?>
  <?php endif; ?>

  <?php $isProgrammedPause = isset($pauseStatus) && !empty($pauseStatus['is_paused']); ?>
  <button class="cta <?= !$isOpenNow ? 'closed' : '' ?>" type="submit" <?= !$isOpenNow ? 'disabled' : '' ?>>
    <?php if ($isOpenNow): ?>
      Adicionar à Sacola
    <?php elseif ($isProgrammedPause): ?>
      <span class="lock-icon">⏸️</span>
      <span>Pedidos Pausados</span>
    <?php else: ?>
      <span class="lock-icon">🔒</span>
      <span>Loja Fechada</span>
    <?php endif; ?>
  </button>

  <?php if (!$isOpenNow): ?>
    <div class="closed-info <?= $isProgrammedPause ? 'pause-mode' : '' ?>">
      <?php if ($isProgrammedPause): ?>
        <?= svg_product('pause') ?>
        <div class="pause-info-content">
          <?php if ($pauseStatus['pause_type'] === 'indefinite'): ?>
            <span class="pause-title">Estamos em pausa no momento</span>
            <?php if (!empty($pauseStatus['pause_reason'])): ?>
              <span class="pause-reason"><?= e($pauseStatus['pause_reason']) ?></span>
            <?php endif; ?>
          <?php else: ?>
            <span class="pause-title">Estamos em pausa temporária</span>
            <span class="pause-reason">
              <?php if (!empty($pauseStatus['pause_reason'])): ?>
                <?= e($pauseStatus['pause_reason']) ?>
              <?php endif; ?>
              <?php if (!empty($pauseStatus['remaining_text'])): ?>
                · Retornamos em <strong><?= e($pauseStatus['remaining_text']) ?></strong>
              <?php endif; ?>
            </span>
          <?php endif; ?>
        </div>
      <?php elseif ($nextOpening && isset($nextOpening['message']) && isset($nextOpening['time'])): ?>
        <?= svg_product('clock') ?>
        <span><?= e($nextOpening['message']) ?> <strong><?= e($nextOpening['time']) ?></strong></span>
      <?php else: ?>
        <?= svg_product('info') ?>
        <span>Voltaremos em breve no horário de funcionamento</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</form>
