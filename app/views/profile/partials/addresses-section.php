<section class="card">
  <h2>Endereços de entrega</h2>
  <p class="description">Gerencie seus locais de entrega preferidos para agilizar os pedidos.</p>
  <div class="addresses">
    <?php if ($addresses): ?>
      <?php foreach ($addresses as $address):
        $addressId = (int)($address['id'] ?? 0);
        $isDefault = (int)($address['is_default'] ?? 0) === 1;
        $label = trim($address['label'] ?? '');
      ?>
        <article class="address-card">
          <div class="address-title">
            <span><?= $label ? e($label) : e($address['name'] ?? 'Endereço') ?></span>
            <?php if ($isDefault): ?><span class="tag">Padrão</span><?php endif; ?>
          </div>
          <div class="address-meta">
            <strong><?= e($address['name'] ?? '') ?></strong> · <?= e($address['phone'] ?? '') ?><br>
            <?= e($address['street'] ?? '') ?>, <?= e($address['number'] ?? '') ?><?= !empty($address['complement']) ? ' - ' . e($address['complement']) : '' ?><br>
            <?= e($address['neighborhood'] ?? '') ?> · <?= e($address['city'] ?? '') ?>
          </div>
          <?php if (!empty($address['reference'])): ?>
            <div class="address-meta">📍 <?= e($address['reference']) ?></div>
          <?php endif; ?>
          <div class="address-actions">
            <?php if (!$isDefault): ?>
              <form method="post" action="<?= e($addressesUrl . '/set-default') ?>" style="flex:1;margin:0;">
                <?php if (function_exists('csrf_field')): echo csrf_field(); endif; ?>
                <input type="hidden" name="address_id" value="<?= $addressId ?>">
                <button class="ghost-btn" type="submit" style="width:100%;">⭐ Padrão</button>
              </form>
            <?php endif; ?>
            <a href="<?= e($addressesUrl . '/edit/' . $addressId) ?>" class="ghost-btn" style="flex:1;text-decoration:none;display:flex;align-items:center;justify-content:center;">Editar</a>
            <form method="post" action="<?= e($addressesUrl . '/delete') ?>" style="flex:1;margin:0;" data-confirm="Tem certeza que deseja excluir este endereço?">
              <?php if (function_exists('csrf_field')): echo csrf_field(); endif; ?>
              <input type="hidden" name="address_id" value="<?= $addressId ?>">
              <button class="ghost-btn danger" type="submit" style="width:100%;">Excluir</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="address-card" style="text-align:center;">
        <div class="address-title" style="justify-content:center;">Nenhum endereço salvo</div>
        <div class="address-meta">Os endereços salvos durante o checkout aparecerão aqui.</div>
      </div>
    <?php endif; ?>
  </div>

  <a href="<?= e($addressesUrl . '/create') ?>" class="add-address-btn" style="text-decoration:none;">
    <?= svg_profile('plus') ?>
    Adicionar novo endereço
  </a>

  <p class="description" style="margin-top:12px;text-align:center;color:#6b7280;">
    💡 <strong>Dica:</strong> Os endereços também são salvos automaticamente no checkout
  </p>
</section>
