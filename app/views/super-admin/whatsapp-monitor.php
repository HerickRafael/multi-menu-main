<?php
declare(strict_types=1);
/** @var array $data */
/** @var array $companies */
/** @var int $selectedCompanyId */
include __DIR__ . '/layout.php';
$summary = $data['summary'] ?? [];
$rows = $data['rows'] ?? [];
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>WhatsApp Monitoring</h1>
    <p class="sub">Status global das instancias por loja.</p>
  </div>
  <div class="toolbar-right">
    <form method="get" action="<?= htmlspecialchars(base_url('superadmin/whatsapp-monitor'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.5rem;">
      <select name="company_id">
        <option value="0">Todas as lojas</option>
        <?php foreach ($companies as $company): ?>
          <?php $cid = (int)($company['id'] ?? 0); ?>
          <option value="<?= $cid ?>" <?= $selectedCompanyId === $cid ? 'selected' : '' ?>><?= htmlspecialchars((string)($company['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn secondary sm" type="submit">Aplicar</button>
    </form>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Instâncias</div><div class="stat-value"><?= (int)($summary['total'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Online</div><div class="stat-value" style="color:#166534;"><?= (int)($summary['online'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value" style="color:#b45309;"><?= (int)($summary['pending'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Offline</div><div class="stat-value" style="color:#b91c1c;"><?= (int)($summary['offline'] ?? 0) ?></div></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>ID</th>
        <th>Loja</th>
        <th>Label</th>
        <th>Número</th>
        <th>Status</th>
        <th>Conectado em</th>
      </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:#64748b;">Nenhuma instância encontrada.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td>#<?= (int)($row['id'] ?? 0) ?></td>
            <td><?= htmlspecialchars((string)($row['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['connected_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php';
