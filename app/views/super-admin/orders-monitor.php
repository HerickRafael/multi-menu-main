<?php
declare(strict_types=1);
/** @var array $summary */
/** @var array $listing */
/** @var array $companies */
/** @var int $selectedCompanyId */
/** @var string $selectedStatus */
include __DIR__ . '/layout.php';
$rows = $listing['rows'] ?? [];
$page = (int)($listing['page'] ?? 1);
$totalPages = (int)($listing['total_pages'] ?? 1);
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Monitoramento Global de Pedidos</h1>
    <p class="sub">Visão consolidada com timeline por pedido.</p>
  </div>
  <div class="toolbar-right">
    <form method="get" action="<?= htmlspecialchars(base_url('superadmin/orders-monitor'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <select name="company_id">
        <option value="0">Todas as lojas</option>
        <?php foreach ($companies as $company): ?>
          <?php $cid = (int)($company['id'] ?? 0); ?>
          <option value="<?= $cid ?>" <?= $selectedCompanyId === $cid ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)($company['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="status">
        <option value="">Todos os status</option>
        <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Pendente</option>
        <option value="paid" <?= $selectedStatus === 'paid' ? 'selected' : '' ?>>Pago</option>
        <option value="completed" <?= $selectedStatus === 'completed' ? 'selected' : '' ?>>Concluído</option>
        <option value="canceled" <?= $selectedStatus === 'canceled' ? 'selected' : '' ?>>Cancelado</option>
      </select>
      <button type="submit" class="btn secondary sm">Aplicar</button>
    </form>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= (int)($summary['total'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Pendentes</div><div class="stat-value" style="color:#b45309"><?= (int)($summary['pending'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Concluídos</div><div class="stat-value" style="color:#166534"><?= (int)($summary['completed'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Receita</div><div class="stat-value">R$ <?= number_format((float)($summary['total_value'] ?? 0), 2, ',', '.') ?></div></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Pedido</th>
          <th>Loja</th>
          <th>Status</th>
          <th>Total</th>
          <th>Criado em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:#64748b;">Sem pedidos para o filtro atual.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>#<?= (int)($r['id'] ?? 0) ?></td>
            <td><?= htmlspecialchars((string)($r['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>R$ <?= number_format((float)($r['total'] ?? 0), 2, ',', '.') ?></td>
            <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/orders/' . (int)$r['id'] . '/timeline'), ENT_QUOTES, 'UTF-8') ?>">Timeline</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:flex;justify-content:center;gap:.5rem;margin-top:1rem;">
  <?php if ($page > 1): ?>
    <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/orders-monitor?page=' . ($page - 1)), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
  <?php endif; ?>
  <span class="btn secondary sm" style="pointer-events:none;">Página <?= $page ?> de <?= $totalPages ?></span>
  <?php if ($page < $totalPages): ?>
    <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/orders-monitor?page=' . ($page + 1)), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php';
