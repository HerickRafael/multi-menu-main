<?php if ($items): ?>
  <div class="fixed-bottom">
    <div class="coupon">
      <button class="coupon-btn" id="coupon-btn" type="button" aria-label="Aplicar cupom">
        <?= cartSvg('coupon', ['class' => 'poly-coupons__icon', 'width' => '13', 'height' => '11']) ?>
        <span>Você tem algum cupom de desconto?</span>
      </button>
    </div>

    <div class="totals">
      <div class="trow"><span class="label">Subtotal</span><span class="value"><?= e($formatBrl($totals['subtotal'] ?? 0)) ?></span></div>
      <?php
      $minOrder = !empty($company['min_order']) ? (float)$company['min_order'] : 0;
      $freeShippingMin = !empty($company['delivery_free_min_value']) ? (float)$company['delivery_free_min_value'] : 0;
      $freeDeliveryEnabled = !empty($company['delivery_free_enabled']) ? (int)$company['delivery_free_enabled'] : 0;
      $subtotal = (float)($totals['subtotal'] ?? 0);

      $renderFreeShippingBanner = static function ($title, $subtitle) {
          ?>
          <div class="free-shipping-achieved">
            <div class="free-shipping-achieved__glow"></div>
            <div class="free-shipping-achieved__content">
              <div class="free-shipping-achieved__header">
                <div class="free-shipping-achieved__icon-wrap">
                  <?= cartSvg('check', ['width' => '24', 'height' => '24', 'fill' => '#10b981']) ?>
                </div>
                <div class="free-shipping-achieved__copy">
                  <div class="free-shipping-achieved__title"><?= e($title) ?></div>
                  <div class="free-shipping-achieved__subtitle"><?= e($subtitle) ?></div>
                </div>
              </div>
              <div class="free-shipping-achieved__badge-wrap">
                <div class="free-shipping-achieved__badge">
                  <span>Benefício Aplicado</span>
                </div>
              </div>
            </div>
            <div class="free-shipping-achieved__confetti free-shipping-achieved__confetti--tl">✨</div>
            <div class="free-shipping-achieved__confetti free-shipping-achieved__confetti--tr">🎊</div>
            <div class="free-shipping-achieved__confetti free-shipping-achieved__confetti--bl">⭐</div>
            <div class="free-shipping-achieved__confetti free-shipping-achieved__confetti--br">🎉</div>
          </div>
          <?php
      };

      if ($freeDeliveryEnabled):
          $renderFreeShippingBanner('🎉 TELE-ENTREGA GRÁTIS!', 'Promoção especial ativa');
      elseif ($minOrder > 0 || $freeShippingMin > 0):
          $hasMinOrder = $minOrder > 0 && $subtotal < $minOrder;
          $hasFreeShipping = $freeShippingMin > 0;

          if ($hasMinOrder):
              $target = $minOrder;
              $percentage = min(($subtotal / $target) * 100, 100);
              $remaining = $target - $subtotal;
              $color = '#dc2626';
              $bgColor = '#fee2e2';
              $textColor = '#991b1b';
              $icon = '⚠️';
              $label = 'Pedido mínimo';
              $statusText = number_format($percentage, 0) . '% do pedido mínimo';
          elseif ($hasFreeShipping && $subtotal < $freeShippingMin):
              $target = $freeShippingMin;
              $percentage = min(($subtotal / $target) * 100, 100);
              $remaining = $target - $subtotal;
              $color = '#10b981';
              $bgColor = '#d1fae5';
              $textColor = '#065f46';
              $icon = '🚚';
              $label = 'Frete grátis';
              $statusText = number_format($percentage, 0) . '% para frete grátis';
          elseif ($hasFreeShipping && $subtotal >= $freeShippingMin):
              $renderFreeShippingBanner('🎉 FRETE GRÁTIS!', 'Você economizou na entrega');
          endif;

            if (isset($target)):
              $progressWidth = max(0.0, min(100.0, (float)$percentage));
              $progressWidthCss = sprintf('%.2F', $progressWidth);
              $progressStyle = '--progress-color:' . $color . ';--progress-bg-color:' . $bgColor . ';--progress-text-color:' . $textColor . ';--progress-width:' . $progressWidthCss . '%;';
              ?>
              <div class="unified-progress" style="<?= e($progressStyle) ?>">
                <div class="unified-progress__head">
                  <div>
                    <div class="unified-progress__label"><?= $icon ?> <?= e($label) ?></div>
                    <div class="unified-progress__target"><?= e($formatBrl($target)) ?></div>
                  </div>
                  <div class="unified-progress__remaining-wrap">
                    <div class="unified-progress__label">Faltam</div>
                    <div class="unified-progress__remaining"><?= e($formatBrl($remaining)) ?></div>
                  </div>
                </div>

                <div class="unified-progress__bar-wrap">
                  <div class="unified-progress__bar">
                    <div class="unified-progress__fill"></div>
                    <div class="unified-progress__shimmer"></div>
                  </div>
                </div>

                <div class="unified-progress__foot">
                  <div class="unified-progress__status"><?= e($statusText) ?></div>
                  <div class="unified-progress__percent"><?= number_format($percentage, 0) ?>%</div>
                </div>
              </div>
              <?php
          endif;
      endif;
      ?>
    </div>
  </div>
<?php endif; ?>
