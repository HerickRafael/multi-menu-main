<?php
declare(strict_types=1);
/** @var array $filters */
/** @var array $logs */
include __DIR__ . '/layout.php';
$rows = $logs['rows'] ?? [];
$page = (int)($logs['page'] ?? 1);
$totalPages = (int)($logs['total_pages'] ?? 1);
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Logs Centralizados</h1>
    <p class="sub">Busca por nível, módulo e conteúdo.</p>
  </div>
  <div class="toolbar-right" style="display:flex;gap:.5rem;">
    <form method="post" action="<?= htmlspecialchars(base_url('superadmin/system-logs/ingest'), ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn secondary sm" type="submit">Ingerir exceptions.log</button>
    </form>
    <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/system-logs/export'), ENT_QUOTES, 'UTF-8') ?>">Exportar CSV</a>
  </div>
</div>

<div class="card" style="padding:1rem;margin-bottom:1rem;">
  <form method="get" action="<?= htmlspecialchars(base_url('superadmin/system-logs'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <select name="level">
      <option value="">Nível</option>
      <?php foreach (['debug','info','warning','error','critical'] as $lvl): ?>
        <option value="<?= $lvl ?>" <?= (($filters['level'] ?? '') === $lvl) ? 'selected' : '' ?>><?= strtoupper($lvl) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="module" placeholder="Módulo" value="<?= htmlspecialchars((string)($filters['module'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <input type="text" name="search" placeholder="Buscar na mensagem" value="<?= htmlspecialchars((string)($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <button class="btn secondary sm" type="submit">Filtrar</button>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Data</th>
          <th>Nível</th>
          <th>Módulo</th>
          <th>Mensagem</th>
          <th>Fonte</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5" style="text-align:center;padding:2rem;color:#64748b;">Nenhum log encontrado.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars((string)($r['logged_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= strtoupper(htmlspecialchars((string)($r['level'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></td>
            <td><?= htmlspecialchars((string)($r['module'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td style="max-width:560px;white-space:normal;"><?= htmlspecialchars((string)($r['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($r['source'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:flex;justify-content:center;gap:.5rem;margin-top:1rem;">
  <?php if ($page > 1): ?>
    <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/system-logs?page=' . ($page - 1)), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
  <?php endif; ?>
  <span class="btn secondary sm" style="pointer-events:none;">Página <?= $page ?> de <?= $totalPages ?></span>
  <?php if ($page < $totalPages): ?>
    <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/system-logs?page=' . ($page + 1)), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php';
