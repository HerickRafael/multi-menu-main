<?php
declare(strict_types=1);
/** @var array $order */
/** @var array $events */
include __DIR__ . '/layout.php';
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Timeline do Pedido #<?= (int)($order['id'] ?? 0) ?></h1>
    <p class="sub">Loja #<?= (int)($order['company_id'] ?? 0) ?> | Status atual: <?= htmlspecialchars((string)($order['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  </div>
  <div class="toolbar-right">
    <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/orders-monitor'), ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
  </div>
</div>

<div class="card" style="padding:1rem;">
  <?php if (!$events): ?>
    <p style="color:#64748b;">Sem eventos de timeline para este pedido.</p>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:.75rem;">
      <?php foreach ($events as $event): ?>
        <div style="border-left:3px solid #4f46e5;padding:.6rem .8rem;background:#f8fafc;">
          <div style="font-weight:700;">
            <?= htmlspecialchars((string)($event['status_from'] ?? 'novo'), ENT_QUOTES, 'UTF-8') ?>
            →
            <?= htmlspecialchars((string)($event['status_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </div>
          <div style="font-size:.85rem;color:#475569;">
            <?= htmlspecialchars((string)($event['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?> |
            <?= htmlspecialchars((string)($event['changed_by_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?> |
            <?= htmlspecialchars((string)($event['source'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </div>
          <?php if (!empty($event['notes'])): ?>
            <div style="margin-top:.35rem;color:#334155;"><?= htmlspecialchars((string)$event['notes'], ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php';
