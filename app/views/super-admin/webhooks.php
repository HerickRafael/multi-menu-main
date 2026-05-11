<?php
declare(strict_types=1);
/** @var array $filters */
/** @var array $data */
include __DIR__ . '/layout.php';
$rows = $data['rows'] ?? [];
$summary = $data['summary'] ?? [];
$page = (int)($data['page'] ?? 1);
$totalPages = (int)($data['total_pages'] ?? 1);
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Central de Webhooks</h1>
    <p class="sub">Monitoramento global com retry manual.</p>
  </div>
  <div class="toolbar-right">
    <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/queues'), ENT_QUOTES, 'UTF-8') ?>">Ver Filas</a>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= (int)($summary['total'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Processados</div><div class="stat-value" style="color:#166534"><?= (int)($summary['processed'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Falhas</div><div class="stat-value" style="color:#b91c1c"><?= (int)($summary['failed'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Retrying</div><div class="stat-value" style="color:#1d4ed8"><?= (int)($summary['retrying'] ?? 0) ?></div></div>
</div>

<div class="card" style="padding:1rem;margin-bottom:1rem;">
  <form method="get" action="<?= htmlspecialchars(base_url('superadmin/webhooks'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <select name="status">
      <option value="">Status</option>
      <?php foreach (['received','processed','failed','retrying','dropped'] as $status): ?>
        <option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= strtoupper($status) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="source" placeholder="Source" value="<?= htmlspecialchars((string)($filters['source'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <input type="number" min="1" name="company_id" placeholder="Company ID" value="<?= htmlspecialchars((string)($filters['company_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <button class="btn secondary sm" type="submit">Filtrar</button>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Data</th>
          <th>Source</th>
          <th>Event</th>
          <th>Status</th>
          <th>Retries</th>
          <th>Empresa</th>
          <th>Ação</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8" style="text-align:center;padding:2rem;color:#64748b;">Nenhum webhook encontrado.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td>#<?= (int)($row['id'] ?? 0) ?></td>
            <td><?= htmlspecialchars((string)($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['source'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['event_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)($row['retry_count'] ?? 0) ?></td>
            <td><?= isset($row['company_id']) ? (int)$row['company_id'] : '-' ?></td>
            <td>
              <form method="post" action="<?= htmlspecialchars(base_url('superadmin/webhooks/' . (int)($row['id'] ?? 0) . '/retry'), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn secondary sm" type="submit">Retry</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:flex;justify-content:center;gap:.5rem;margin-top:1rem;">
  <?php if ($page > 1): ?>
    <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/webhooks?page=' . ($page - 1)), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
  <?php endif; ?>
  <span class="btn secondary sm" style="pointer-events:none;">Página <?= $page ?> de <?= $totalPages ?></span>
  <?php if ($page < $totalPages): ?>
    <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/webhooks?page=' . ($page + 1)), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php';
