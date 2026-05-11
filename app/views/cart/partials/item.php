    <?php
      /* ===================== PRODUTO SIMPLES (EXATAMENTE COMO ANTES) ===================== */
      $extId = 'ext-'.$uid;
      $hasDetails = (!empty($item['customization']['groups'])) || !empty($item['component_customizations']);
      $extraTotal = 0.0;
      $hasPaidExtra = false;

      if (!empty($item['customization']['groups'])) {
          foreach ($item['customization']['groups'] as $group) {
              foreach (($group['items'] ?? []) as $opt) {
                  $linePrice = 0.0;

                  if (isset($opt['price'])) {
                      $linePrice = (float)$opt['price'];
                  } elseif (isset($opt['unit_price'], $opt['qty'])) {
                      $linePrice = (float)$opt['unit_price'] * (int)$opt['qty'];
                  }
                  $extraTotal += $linePrice;

                  if (abs($linePrice) > $eps) {
                      $hasPaidExtra = true;
                  }
              }
          }
      }

      if (!$hasDetails) {
          $extraTotal = 0.0;
      }
      
      // Calcular preço base (sem extras)
      $basePrice = ($item['unit_price'] ?? 0) - $extraTotal;
      
      $headerNote = 'Incluso';

      if (!empty($item['customization']['groups'])) {
          if ($extraTotal > $eps) {
              $headerNote = '+ '.$formatBrl($extraTotal);
          } elseif ($extraTotal < -$eps) {
              $headerNote = '− '.$formatBrl(abs($extraTotal));
          }
      }
      $openSimple = $hasPaidExtra;
      $cardClasses = 'item' . ($openSimple ? ' open' : '');
      $buttonClasses = 'toggle-row' . ($openSimple ? ' open' : '');
      $extClasses = 'ext' . ($openSimple ? ' open' : '');
      $ariaExpanded = $openSimple ? 'true' : 'false';
      ?>
    <div class="<?= e($cardClasses) ?>" id="card-<?= e($uid) ?>" aria-controls="<?= e($extId) ?>" aria-expanded="<?= e($ariaExpanded) ?>">
      <div class="avatar">
        <?php if (!empty($item['product']['image'])): ?>
          <img <?= lazyImageAttrs($uploadSrc($item['product']['image']), $item['product']['name'] ?? '', ['class' => '', 'sizes' => 'thumb', 'eager' => true]) ?>>
        <?php else: ?>
          <?= cartSvg('image-placeholder', ['stroke' => 'currentColor', 'stroke-width' => '1.5', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) ?>
        <?php endif; ?>
      </div>
      <div class="info">
        <div class="name"><?= e($item['product']['name'] ?? 'Produto') ?></div>
        <div class="price"><?= e($formatBrl($basePrice)) ?></div>
      </div>
      <?php
        $quantityControlLabel = (string)($item['product']['name'] ?? 'Produto');
        $quantityControlUid = (string)($item['uid'] ?? '');
        $quantityControlQty = (int)($item['qty'] ?? 1);
        include __DIR__ . '/quantity-control.php';
      ?>
      <?php if ($hasDetails): ?>
        <button class="<?= e($buttonClasses) ?>" type="button" data-target="<?= e($extId) ?>" aria-expanded="<?= e($ariaExpanded) ?>">
          <span class="toggle-left">Ingredientes</span>
          <span class="toggle-right">
            <span class="note"><?= e($headerNote) ?></span>
            <?= cartSvg('chevron-right', ['class' => 'chev']) ?>
          </span>
        </button>
      <?php endif; ?>
    </div>

    <?php if ($hasDetails): ?>
      <div class="<?= e($extClasses) ?>" id="<?= e($extId) ?>">
        <?php 
        $isFirstGroup = true;
        foreach ($item['customization']['groups'] as $group):
            $groupItems = $group['items'] ?? [];

            if (!$groupItems) {
                continue;
            }
            $groupTitle = trim((string)($group['name'] ?? '')); 
            $productId = (int)($item['product']['id'] ?? 0);
            $itemUid = (string)($item['uid'] ?? '');
            $editUrl = $productId && $itemUid ? base_url($slug . '/produto/' . $productId . '/customizar?edit_cart_item=' . urlencode($itemUid)) : '';
            ?>
          <?php if ($groupTitle !== ''): ?>
            <div class="section-title<?= $editUrl ? ' editable' : '' ?>" <?= $editUrl ? 'data-edit-url="' . e($editUrl) . '"' : '' ?>>
              <?= cartSvg('section-customization', ['class' => 'icon-16']) ?>
              <span class="section-title-text"><?= e($groupTitle) ?></span>
              <?php if ($editUrl): ?>
                <?= cartSvg('edit', ['class' => 'edit-icon icon-16']) ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php 
          $isFirstGroup = false;
          $groupType = $group['type'] ?? 'qty';
          foreach ($groupItems as $opt):
              $name = (string)($opt['name'] ?? '');
              $qty  = isset($opt['qty']) ? (int)$opt['qty'] : null;
              $defaultQty = array_key_exists('default_qty', $opt) && $opt['default_qty'] !== null ? (int)$opt['default_qty'] : null;
              $deltaQty = array_key_exists('delta_qty', $opt) ? (int)$opt['delta_qty'] : null;

              if ($deltaQty === null && $qty !== null) {
                  $deltaQty = $defaultQty !== null ? $qty - $defaultQty : $qty;
              }

              if ($qty !== null && $qty > 1) {
                  $name = $qty.'x '.$name;
              }

              $linePrice = 0.0;

              if (isset($opt['price'])) {
                  $linePrice = (float)$opt['price'];
              } elseif ($deltaQty !== null && isset($opt['unit_price'])) {
                  $linePrice = (float)$opt['unit_price'] * $deltaQty;
              } elseif ($qty !== null && isset($opt['unit_price'])) {
                  $linePrice = (float)$opt['unit_price'] * $qty;
              }

              $meta = 'Incluso';

              // Pool: mostrar distinção grátis/pago
              $freeQty = isset($opt['free_qty']) ? (int)$opt['free_qty'] : 0;
              $paidQty = isset($opt['paid_qty']) ? (int)$opt['paid_qty'] : 0;

              if ($groupType === 'pool' && ($freeQty > 0 || $paidQty > 0)) {
                  if ($paidQty > 0) {
                      // Mostrar preço se disponível; fallback 'Extra' para itens salvos sem preço
                      $meta = $linePrice > 0.009 ? '+ '.$formatBrl($linePrice) : 'Extra';
                  } else {
                      $meta = 'Incluso';
                  }
              // Verificar se é remoção (flag do controller ou default>0 com qty=0)
              } elseif (!empty($opt['removed']) || ($defaultQty !== null && $defaultQty > 0 && $qty === 0)) {
                  $meta = 'Removido';
              } elseif ($deltaQty !== null) {
                  if ($deltaQty > 0 && $linePrice > 0.009) {
                      $meta = '+ '.$formatBrl($linePrice);
                  } elseif ($deltaQty > 0 && $linePrice <= 0.009) {
                      $meta = 'Extra';
                  }
              } else {
                  if ($linePrice > 0.009) {
                      $meta = '+ '.$formatBrl($linePrice);
                  }
              }
              ?>
            <?php
              $customizationName = $name;
              $customizationMeta = $meta;
              include __DIR__ . '/customization.php';
            ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
        
        <?php if ($extraTotal > $eps): ?>
          <div class="item-divider"></div>
          <div class="item-subtotal">
            <span class="label">Subtotal do item</span>
            <span class="value"><?= e($formatBrl($item['unit_price'] ?? 0)) ?></span>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
