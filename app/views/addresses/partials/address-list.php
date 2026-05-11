<?php

$addresses = is_array($addresses ?? null) ? $addresses : [];
$createUrl = isset($createUrl) ? (string) $createUrl : '#';
?>
<?php if (empty($addresses)): ?>
  <div class="empty">
    <?= addressesSvg('plus') ?>
    <p>Nenhum endereco cadastrado</p>
    <a href="<?= e($createUrl) ?>" class="add-btn">
      <?= addressesSvg('plus') ?>
      Adicionar endereco
    </a>
  </div>
<?php else: ?>
  <?php foreach ($addresses as $addressData): ?>
    <?php include __DIR__ . '/address-card.php'; ?>
  <?php endforeach; ?>

  <a href="<?= e($createUrl) ?>" class="add-btn">
    <?= addressesSvg('plus') ?>
    Adicionar novo endereco
  </a>
<?php endif; ?>
