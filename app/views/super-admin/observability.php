<?php
declare(strict_types=1);
/** @var array $data */
include __DIR__ . '/layout.php';
$summary = $data['summary'] ?? [];
$rows = $data['latest'] ?? [];
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Observabilidade</h1>
    <p class="sub">Health checks, alertas e sinais operacionais do sistema.</p>
  </div>
  <div class="toolbar-right">
    <form method="post" action="<?= htmlspecialchars(base_url('superadmin/observability/run-checks'), ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn secondary sm" type="submit">Rodar Health Checks</button>
    </form>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Checks (1h)</div><div class="stat-value"><?= (int)($summary['total'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">OK</div><div class="stat-value" style="color:#166534;"><?= (int)($summary['ok'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Warning</div><div class="stat-value" style="color:#b45309;"><?= (int)($summary['warning'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Critical</div><div class="stat-value" style="color:#b91c1c;"><?= (int)($summary['critical'] ?? 0) ?></div></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>Quando</th>
        <th>Componente</th>
        <th>Status</th>
        <th>Mensagem</th>
      </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="4" style="text-align:center;padding:2rem;color:#64748b;">Sem health checks registrados.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= htmlspecialchars((string)($row['checked_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['component'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php';
