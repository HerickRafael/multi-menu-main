<?php

$addressData = is_array($addressData ?? null) ? $addressData : [];
$slugClean = isset($slugClean) ? (string) $slugClean : '';

$addressId = (int) ($addressData['id'] ?? 0);
$editUrl = function_exists('base_url') ? base_url($slugClean . '/addresses/edit/' . $addressId) : '#';
$setDefaultUrl = function_exists('base_url') ? base_url($slugClean . '/addresses/set-default') : '#';
$deleteUrl = function_exists('base_url') ? base_url($slugClean . '/addresses/delete') : '#';
?>
<div class="address-card">
  <div class="address-title">
    <span><?= e($addressData['label'] ?? 'Endereco') ?></span>
    <?php if (!empty($addressData['is_default'])): ?>
      <span class="badge-default">Padrao</span>
    <?php endif; ?>
  </div>

  <div class="address-meta">
    <?= e($addressData['street'] ?? '') ?><?php if (!empty($addressData['number'])): ?>, <?= e($addressData['number']) ?><?php endif; ?>
    <?php if (!empty($addressData['complement'])): ?> - <?= e($addressData['complement']) ?><?php endif; ?>
    <br>
    <?php if (!empty($addressData['neighborhood'])): ?><?= e($addressData['neighborhood']) ?> - <?php endif; ?>
    <?= e($addressData['city'] ?? '') ?><?php if (!empty($addressData['state'])): ?>/<?= e($addressData['state']) ?><?php endif; ?>
    <?php if (!empty($addressData['zipcode'])): ?><br>CEP: <?= e($addressData['zipcode']) ?><?php endif; ?>
  </div>

  <div class="address-actions">
    <a href="<?= e($editUrl) ?>">Editar</a>

    <?php if (empty($addressData['is_default'])): ?>
      <form method="POST" action="<?= e($setDefaultUrl) ?>" class="inline-form">
        <?= \App\Middleware\CsrfProtection::field() ?>
        <input type="hidden" name="address_id" value="<?= $addressId ?>">
        <button type="submit">Definir padrao</button>
      </form>
    <?php endif; ?>

    <form method="POST" action="<?= e($deleteUrl) ?>" class="inline-form js-confirm-delete-form" data-confirm-message="Excluir este endereco?">
      <?= \App\Middleware\CsrfProtection::field() ?>
      <input type="hidden" name="address_id" value="<?= $addressId ?>">
      <button type="submit" class="btn-delete">Excluir</button>
    </form>
  </div>
</div>
