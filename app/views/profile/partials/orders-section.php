<section class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
    <h2>Histórico de pedidos</h2>
    <span class="tag">Últimos 10</span>
  </div>
  <p class="description">Acompanhe seus pedidos recentes e status de entrega.</p>

  <div class="addresses">
    <?php if ($orders): ?>
      <?php foreach ($orders as $order):
        $orderId = (int)($order['id'] ?? 0);
        $status = $order['status'] ?? 'pendente';
        $statusLabels = [
          'pending' => '⏳ Pendente',
          'pendente' => '⏳ Pendente',
          'confirmado' => '✅ Confirmado',
          'preparando' => '🍳 Preparando',
          'pronto' => '📦 Pronto p/ entrega',
          'enviado' => '🛵 A caminho',
          'entregue' => '🎉 Entregue',
          'paid' => '🎉 Concluído',
          'completed' => '🎉 Concluído',
          'canceled' => '❌ Cancelado',
          'cancelado' => '❌ Cancelado',
        ];
        $statusColors = [
          'pending' => 'background:#fef3c7;color:#92400e;',
          'pendente' => 'background:#fef3c7;color:#92400e;',
          'confirmado' => 'background:#dbeafe;color:#1e40af;',
          'preparando' => 'background:#fef3c7;color:#92400e;',
          'pronto' => 'background:#d1fae5;color:#065f46;',
          'enviado' => 'background:#dbeafe;color:#1e40af;',
          'entregue' => 'background:#d1fae5;color:#065f46;',
          'paid' => 'background:#d1fae5;color:#065f46;',
          'completed' => 'background:#d1fae5;color:#065f46;',
          'canceled' => 'background:#fee2e2;color:#991b1b;',
          'cancelado' => 'background:#fee2e2;color:#991b1b;',
        ];
        $statusLabel = $statusLabels[$status] ?? ucfirst($status);
        $statusStyle = $statusColors[$status] ?? '';
        $canRepeat = in_array($status, ['completed', 'entregue', 'paid', 'canceled', 'cancelado'], true);
        $canCancel = in_array($status, ['pending', 'pendente', 'confirmado', 'preparando'], true);
      ?>
        <article class="address-card">
          <div class="address-title">
            <span>Pedido #<?= $orderId ?></span>
            <span class="tag" style="<?= $statusStyle ?>"><?= $statusLabel ?></span>
          </div>
          <div class="address-meta">
            <strong>R$ <?= number_format($order['total'], 2, ',', '.') ?></strong><br>
            📅 <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
          </div>
          <div class="address-actions" style="margin-top:12px;">
            <a href="<?= e(base_url($slugClean . '/order/' . $orderId)) ?>" class="ghost-btn" style="text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;">
              <?= svg_profile('eye') ?>
              Ver pedido
            </a>
            <?php if ($canRepeat): ?>
              <form method="post" action="<?= e(base_url($slugClean . '/reorder/' . $orderId)) ?>" style="flex:1;margin:0;">
                <?php if (function_exists('csrf_field')): echo csrf_field(); endif; ?>
                <button class="ghost-btn" type="submit" style="width:100%;display:flex;align-items:center;justify-content:center;gap:6px;color:#16a34a;border-color:#16a34a;">
                  <?= svg_profile('reorder') ?>
                  Pedir de novo
                </button>
              </form>
            <?php endif; ?>
            <?php if ($canCancel): ?>
              <form method="post" action="<?= e(base_url($slugClean . '/order/' . $orderId . '/cancel')) ?>" style="flex:1;margin:0;" data-confirm="Tem certeza que deseja cancelar este pedido?">
                <?php if (function_exists('csrf_field')): echo csrf_field(); endif; ?>
                <button class="ghost-btn danger" type="submit" style="width:100%;display:flex;align-items:center;justify-content:center;gap:6px;">
                  <?= svg_profile('cancel') ?>
                  Cancelar
                </button>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="address-card" style="text-align:center;">
        <div class="address-title" style="justify-content:center;">Nenhum pedido encontrado</div>
        <div class="address-meta">Seus pedidos aparecerão aqui após a primeira compra.</div>
      </div>
    <?php endif; ?>
  </div>
</section>
