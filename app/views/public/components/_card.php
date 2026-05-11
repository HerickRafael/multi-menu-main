<?php
// Component: Card de produto
// Espera a variável $p (produto) e $company disponível no escopo
if (!isset($p) || !is_array($p)) {
    return;
}
?>
<a href="<?= base_url(rawurlencode((string)($company['slug'] ?? '')) . '/produto/' . (int)$p['id']) ?>" class="block h-full">
  <div class="ui-card rounded-2xl shadow p-4 bg-white border flex gap-3 hover:bg-gray-50 h-full">
    <div class="w-24 h-24 rounded-xl bg-gray-100 overflow-hidden relative flex items-center justify-center">
      <?php if (!empty($p['image'])): ?>
        <img src="<?= base_url($p['image']) ?>"
             alt="<?= e($p['name']) ?>"
             class="w-full h-full object-cover absolute inset-0">
      <?php else: ?>
        <svg class="w-12 h-12 text-gray-400" viewBox="0 0 24 24" fill="none">
          <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      <?php endif; ?>
    </div>

    <div class="flex-1 min-h-[88px] flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <?php if (badgePromo($p)): ?>
            <span class="ui-badge ui-badge--warning text-xs bg-yellow-300 text-black font-semibold px-2 py-0.5 rounded-lg">Promoção!</span>
          <?php endif; ?>
          <?php if (is_new_product($p)): ?>
            <span class="ui-badge ui-badge--new text-xs bg-blue-600 text-white font-semibold px-2 py-0.5 rounded-lg">Novidade!</span>
          <?php endif; ?>
        </div>

        <h3 class="font-semibold leading-5"><?= e($p['name']) ?></h3>

        <?php if (!empty($p['description'])): ?>
          <p class="text-sm text-gray-600 line-clamp-2">
            <?= e($p['description']) ?> <span class="underline">Ver mais</span>
          </p>
        <?php endif; ?>
      </div>

      <?php
        // Pega o preço original (sem taxa embutida) para cálculo de desconto
        $originalPrice = isset($p['original_price']) ? (float)$p['original_price'] : (isset($p['price']) ? (float)$p['price'] : 0);
        $priceVal = isset($p['price']) ? (float)$p['price'] : 0;
        $embeddedFee = isset($p['embedded_delivery_fee']) ? (float)$p['embedded_delivery_fee'] : 0;
        
        $promoRaw = $p['promo_price'] ?? null;
        $priceMode = $p['price_mode'] ?? 'fixed';
        $promoVal = null;
        $isPercentPromo = false;

        // Verificar prazo da promoção
        $promoExpired = false;
        $promoNotStarted = false;
        $promoEndTs = null;
        $nowTs = time();
        if (!empty($p['promo_start_at']) && strtotime($p['promo_start_at']) > $nowTs) {
            $promoNotStarted = true;
        }
        if (!empty($p['promo_end_at'])) {
            $promoEndTs = strtotime($p['promo_end_at']);
            if ($promoEndTs < $nowTs) {
                $promoExpired = true;
            }
        }

        if ($promoRaw !== null && $promoRaw !== '' && !$promoExpired && !$promoNotStarted) {
            $promoStr = is_array($promoRaw) ? reset($promoRaw) : $promoRaw;
            $promoStr = trim((string)$promoStr);

            if ($promoStr !== '') {
                $promoStr = str_replace(' ', '', $promoStr);

                if (strpos($promoStr, ',') !== false && strpos($promoStr, '.') !== false) {
                    $promoStr = str_replace('.', '', $promoStr);
                }
                $promoStr = str_replace(',', '.', $promoStr);

                if (is_numeric($promoStr)) {
                    $promoVal = (float)$promoStr;
                    
                    // Se está no modo 'sum' e valor é 0-100, é porcentagem
                    if ($priceMode === 'sum' && $promoVal > 0 && $promoVal <= 100) {
                        $isPercentPromo = true;
                    }
                }
            }
        }

        if ($isPercentPromo) {
            // Modo porcentagem: calcular desconto sobre o preço ORIGINAL (sem taxa)
            $discountedPrice = $originalPrice * (1 - ($promoVal / 100.0));
            // Depois adiciona a taxa embutida
            $discountedPrice += $embeddedFee;
            $hasPromo = true;
        } else {
            // Modo tradicional: promo é valor absoluto
            $hasPromo = $originalPrice > 0 && $promoVal !== null && $promoVal > 0 && $promoVal < $originalPrice;
            // Se tem promo em valor fixo, adiciona a taxa também
            $discountedPrice = $promoVal + $embeddedFee;
        }
      ?>
      <div class="mt-auto pt-1">
        <?php if ($hasPromo): ?>
          <span class="text-sm text-gray-400 line-through">
            R$ <?= number_format($priceVal, 2, ',', '.') ?>
          </span>
          <span class="ml-2 text-lg font-bold">
            R$ <?= number_format($discountedPrice, 2, ',', '.') ?>
          </span>
          <?php if ($isPercentPromo): ?>
            <span class="ml-1 text-xs bg-green-500 text-white px-1 rounded">
              <?= (int)round($promoVal) ?>% OFF
            </span>
          <?php endif; ?>
          <?php if ($promoEndTs && $hasPromo): ?>
            <div class="promo-countdown text-[11px] text-red-600 font-medium mt-0.5" data-end="<?= (int)$promoEndTs ?>">
              ⏰ <span class="cd-text">Calculando...</span>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <span class="text-lg font-bold">
            R$ <?= number_format($priceVal, 2, ',', '.') ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</a>
