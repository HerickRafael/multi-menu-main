<?php
$lastOrder = $lastOrder ?? null;
$lastOrderItems = $lastOrderItems ?? [];
if ($lastOrder && $customer):
  $reorderUrl = base_url(rawurlencode((string)$company['slug']) . '/reorder/' . (int)$lastOrder['id']);
  $summarizeItem = static function ($it) {
      $parts = [];
      $combo = $it['combo_data'] ?? ($it['combo'] ?? null);
      if ($combo) {
          if (is_string($combo)) {
              $combo = json_decode($combo, true);
          }
          if (is_array($combo)) {
              if (!empty($combo['groups']) && is_array($combo['groups'])) {
                  foreach ($combo['groups'] as $g) {
                      if (!empty($g['items']) && is_array($g['items'])) {
                          foreach ($g['items'] as $si) {
                              $n = $si['name'] ?? $si['simple_name'] ?? '';
                              if ($n) $parts[] = $n;
                          }
                      }
                  }
              } elseif (!empty($combo['selected_items']) && is_array($combo['selected_items'])) {
                  foreach ($combo['selected_items'] as $si) {
                      $n = $si['name'] ?? $si['simple_name'] ?? '';
                      if ($n) $parts[] = $n;
                  }
              }
          }
      }

      $cust = $it['customization_data'] ?? null;
      if ($cust) {
          if (is_string($cust)) {
              $cust = json_decode($cust, true);
          }
          if (is_array($cust) && !empty($cust['groups']) && is_array($cust['groups'])) {
              foreach ($cust['groups'] as $g) {
                  if (!empty($g['items']) && is_array($g['items'])) {
                      foreach ($g['items'] as $ci) {
                          $name = $ci['name'] ?? '';
                          if (!$name) continue;
                          $qty = isset($ci['qty']) ? (int)$ci['qty'] : null;
                          $default = isset($ci['default_qty']) ? (int)$ci['default_qty'] : null;
                          if ($qty !== null && $qty <= 0 && $default !== null && $default > 0) {
                              continue;
                          }
                          $parts[] = $name;
                      }
                  }
              }
          }
      }

      $parts = array_values(array_unique(array_filter($parts, static fn($x) => trim((string)$x) !== '')));
      if (!$parts) return '';
      if (count($parts) > 3) {
          return implode(', ', array_slice($parts, 0, 3)) . ', ...';
      }
      return implode(', ', $parts);
  };

  $totalItems = 0;
  foreach ($lastOrderItems as $_li) { $totalItems += max(1, (int)($_li['quantity'] ?? 1)); }
?>
<div class="pedido-container">
  <div class="ultimo-pedido-header">
    <div class="info-grupo">
      <div class="icone-historico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 3"/>
        </svg>
      </div>
      <div class="texto-info">
        <h2>Seu ultimo pedido</h2>
        <div class="data-pedido"><?= !empty($lastOrder['created_at']) ? date('d/m/Y H:i', strtotime($lastOrder['created_at'])) : 'Data desconhecida' ?></div>
        <div class="total-pedido">R$ <?= number_format((float)$lastOrder['total'], 2, ',', '.') ?></div>
      </div>
    </div>
    <div class="acao-grupo">
      <form method="POST" action="<?= e($reorderUrl) ?>" style="margin:0;">
        <?php if (function_exists('csrf_field')): echo csrf_field(); endif; ?>
        <button type="submit" class="btn-repetir">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
          Pedir de novo
        </button>
      </form>
      <div class="texto-pratico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M16 10l-5 5-2-2"></path></svg>
        Rapido e pratico
      </div>
    </div>
  </div>

  <div class="divisor-linha"></div>

  <?php if (!empty($lastOrderItems)): ?>
  <div class="pedido-interno">
    <div class="pedido-header">
      <h3>Itens do pedido</h3>
      <span class="badge-itens"><?= (int)$totalItems ?> <?= $totalItems == 1 ? 'item' : 'itens' ?></span>
    </div>

    <div class="pedido-lista">
      <?php foreach ($lastOrderItems as $it):
        $img = !empty($it['product_image']) ? base_url($it['product_image']) : '';
        $summary = $summarizeItem($it);
      ?>
      <div class="item-card">
        <?php if ($img): ?>
          <img class="item-imagem" src="<?= e($img) ?>" alt="<?= e($it['product_name'] ?? '') ?>">
        <?php else: ?>
          <div class="item-imagem" style="display:flex;align-items:center;justify-content:center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          </div>
        <?php endif; ?>
        <div class="item-info">
          <div>
            <h4><?= e($it['product_name'] ?? 'Produto') ?></h4>
            <?php if ($summary): ?><p class="item-desc"><?= e($summary) ?></p><?php endif; ?>
          </div>
          <div><p class="item-qtd">Qtd: <?= (int)($it['quantity'] ?? 1) ?></p></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <form method="POST" action="<?= e($reorderUrl) ?>" style="margin:0;">
    <?php if (function_exists('csrf_field')): echo csrf_field(); endif; ?>
    <button type="submit" class="pedido-banner-bottom">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
      <span>Repetir seu pedido com <span class="destaque">1 clique</span></span>
    </button>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>
