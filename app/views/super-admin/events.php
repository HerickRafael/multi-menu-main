<?php
declare(strict_types=1);
/** @var array $filters */
/** @var array $data */
include __DIR__ . '/layout.php';
$rows = $data['rows'] ?? [];
$page = (int)($data['page'] ?? 1);
$totalPages = (int)($data['total_pages'] ?? 1);

$queryBase = [];
if (!empty($filters['event_name'])) {
  $queryBase['event_name'] = (string)$filters['event_name'];
}
if (!empty($filters['source'])) {
  $queryBase['source'] = (string)$filters['source'];
}
if (!empty($filters['company_id'])) {
  $queryBase['company_id'] = (string)$filters['company_id'];
}

$buildEventsPageUrl = static function (int $targetPage) use ($queryBase): string {
  $query = array_merge($queryBase, ['page' => $targetPage]);
  return base_url('superadmin/events?' . http_build_query($query));
};
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Eventos do Sistema</h1>
    <p class="sub">Trilha de eventos e listeners do dominio.</p>
  </div>
  <div class="toolbar-right">
    <form method="post" action="<?= htmlspecialchars(base_url('superadmin/events/dispatch-test'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.4rem;align-items:center;">
      <?= function_exists('csrf_field') ? csrf_field() : '' ?>
      <input type="number" min="1" name="company_id" value="1" style="width:90px;" placeholder="Company">
      <input type="number" min="1" name="instance_id" value="1" style="width:90px;" placeholder="Instance">
      <button class="btn secondary sm" type="submit">Disparar Evento Teste</button>
    </form>
  </div>
</div>

<div class="card" style="padding:1rem;margin-bottom:1rem;">
  <form method="get" action="<?= htmlspecialchars(base_url('superadmin/events'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <input type="text" name="event_name" placeholder="event_name" value="<?= htmlspecialchars((string)($filters['event_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <input type="text" name="source" placeholder="source" value="<?= htmlspecialchars((string)($filters['source'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <input type="number" min="1" name="company_id" placeholder="company_id" value="<?= htmlspecialchars((string)($filters['company_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
        <th>Evento</th>
        <th>Aggregate</th>
        <th>Company</th>
        <th>Source</th>
      </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:#64748b;">Nenhum evento encontrado.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td>#<?= (int)($row['id'] ?? 0) ?></td>
            <td><?= htmlspecialchars((string)($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['event_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['aggregate_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?> #<?= htmlspecialchars((string)($row['aggregate_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['company_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['source'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:flex;justify-content:center;gap:.5rem;margin-top:1rem;">
  <?php if ($page > 1): ?>
    <a class="btn secondary sm" href="<?= htmlspecialchars($buildEventsPageUrl($page - 1), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
  <?php endif; ?>
  <span class="btn secondary sm" style="pointer-events:none;">Pagina <?= $page ?> de <?= $totalPages ?></span>
  <?php if ($page < $totalPages): ?>
    <a class="btn secondary sm" href="<?= htmlspecialchars($buildEventsPageUrl($page + 1), ENT_QUOTES, 'UTF-8') ?>">Proxima</a>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php';
