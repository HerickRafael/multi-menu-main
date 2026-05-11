<?php
declare(strict_types=1);
/** @var array $companies */
/** @var array|null $selectedCompany */
/** @var int $selectedCompanyId */
/** @var string $selectedStatus */
/** @var int $statsTotalOrders */
/** @var int $statsPendingOrders */
/** @var int $statsPaidOrders */
/** @var int $statsCompletedOrders */
/** @var int $statsCanceledOrders */
/** @var float $statsTotalValue */
/** @var array $rows */
/** @var string $superAdminName */
$hideTopbar = false;
include __DIR__ . '/layout.php';
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Pedidos em Tempo Real</h1>
    <p class="sub">Supervisão global com recorte por loja e status.</p>
  </div>
  <div class="toolbar-right">
    <form method="get" action="<?= htmlspecialchars(base_url('superadmin/orders-live'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
      <select name="company_id" style="max-width:220px;">
        <option value="0">Todas as lojas</option>
        <?php foreach ($companies as $company): ?>
          <?php $cid = (int)($company['id'] ?? 0); ?>
          <option value="<?= $cid ?>" <?= $selectedCompanyId === $cid ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)($company['name'] ?? 'Loja'), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="status" style="max-width:180px;">
        <option value="">Todos os status</option>
        <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Pendente</option>
        <option value="paid" <?= $selectedStatus === 'paid' ? 'selected' : '' ?>>Pago</option>
        <option value="completed" <?= $selectedStatus === 'completed' ? 'selected' : '' ?>>Concluído</option>
        <option value="canceled" <?= $selectedStatus === 'canceled' ? 'selected' : '' ?>>Cancelado</option>
      </select>
      <button type="submit" class="btn secondary sm">Aplicar</button>
      <?php if ($selectedCompanyId > 0 || $selectedStatus !== ''): ?>
        <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/orders-live'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Pedidos</div>
    <div class="stat-value"><?= (int)$statsTotalOrders ?></div>
    <div class="stat-sub">total no filtro</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Pendentes</div>
    <div class="stat-value" style="color:#b45309"><?= (int)$statsPendingOrders ?></div>
    <div class="stat-sub">exigem atenção</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Concluídos</div>
    <div class="stat-value" style="color:#166534"><?= (int)$statsCompletedOrders ?></div>
    <div class="stat-sub">finalizados</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Faturamento</div>
    <div class="stat-value">R$ <?= number_format((float)$statsTotalValue, 2, ',', '.') ?></div>
    <div class="stat-sub">soma dos pedidos</div>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Pedido</th>
          <th>Loja</th>
          <th>Cliente</th>
          <th>Total</th>
          <th>Status</th>
          <th>Criado em</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:#64748b">Sem pedidos para o filtro selecionado.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td style="font-weight:700">#<?= (int)($r['id'] ?? 0) ?></td>
            <td>
              <div style="font-weight:600"><?= htmlspecialchars((string)($r['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              <div style="font-size:.78rem;color:#64748b">/<?= htmlspecialchars((string)($r['company_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td>
              <div><?= htmlspecialchars((string)($r['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              <div style="font-size:.78rem;color:#64748b"><?= htmlspecialchars((string)($r['customer_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td style="font-weight:600">R$ <?= number_format((float)($r['total'] ?? 0), 2, ',', '.') ?></td>
            <td>
              <?php $status = (string)($r['status'] ?? 'pending'); ?>
              <?php if ($status === 'completed'): ?>
                <span class="badge on">Concluído</span>
              <?php elseif ($status === 'canceled'): ?>
                <span class="badge off">Cancelado</span>
              <?php elseif ($status === 'paid'): ?>
                <span class="badge" style="background:#dbeafe;color:#1d4ed8;">Pago</span>
              <?php else: ?>
                <span class="badge" style="background:#fef3c7;color:#92400e;">Pendente</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
