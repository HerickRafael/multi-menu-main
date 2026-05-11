<?php
declare(strict_types=1);
/** @var array $rows */
/** @var int $page */
/** @var int $totalPages */
/** @var int $total */
/** @var int $statsTotal */
/** @var int $statsActive */
/** @var int $statsInactive */
/** @var string $searchQuery */
/** @var array|null $flash */
/** @var string $superAdminName */
$hideTopbar = false;
$searchQuery = $searchQuery ?? '';
include __DIR__ . '/layout.php';
?>

  <?php if (!empty($flash)): ?>
    <div class="flash <?= ($flash['type'] ?? '') === 'success' ? 'ok' : 'bad' ?>">
      <?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <!-- Stat Cards -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-label">Total de Lojas</div>
      <div class="stat-value"><?= (int)$statsTotal ?></div>
      <div class="stat-sub">cadastradas</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Ativas</div>
      <div class="stat-value" style="color:#166534"><?= (int)$statsActive ?></div>
      <div class="stat-sub">em operação</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Inativas</div>
      <div class="stat-value" style="color:#dc2626"><?= (int)$statsInactive ?></div>
      <div class="stat-sub">desativadas</div>
    </div>
  </div>

  <div class="stat-grid" style="margin-top:-.2rem;">
    <div class="stat-card">
      <div class="stat-label">Lojas</div>
      <div class="stat-sub" style="margin-bottom:.6rem;">Cadastro, slug e status operacional.</div>
      <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
    </div>
    <div class="stat-card">
      <div class="stat-label">Cardápios e Produtos</div>
      <div class="stat-sub" style="margin-bottom:.6rem;">Visão por loja e diagnóstico rápido.</div>
      <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/catalog'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pedidos em Tempo Real</div>
      <div class="stat-sub" style="margin-bottom:.6rem;">Supervisão global de pedidos ativos.</div>
      <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/orders-live'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
    </div>
    <div class="stat-card">
      <div class="stat-label">Usuários e Operadores</div>
      <div class="stat-sub" style="margin-bottom:.6rem;">Acessos por loja e por perfil.</div>
      <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/operators'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
    </div>
  </div>

  <div class="card">
    <!-- Toolbar -->
    <div class="toolbar" style="margin-bottom:1rem;">
      <div class="toolbar-left">
        <h1>Lojas</h1>
        <p class="sub"><?= (int)$total ?> resultado(s)<?= $searchQuery !== '' ? ' para "' . htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') . '"' : '' ?></p>
      </div>
      <div class="toolbar-right">
        <form method="get" action="<?= htmlspecialchars(base_url('superadmin'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.4rem;align-items:center">
          <input type="search" name="q" placeholder="Buscar por nome ou slug..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="btn secondary sm">Buscar</button>
          <?php if ($searchQuery !== ''): ?>
            <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
          <?php endif; ?>
        </form>
        <a class="btn" href="<?= htmlspecialchars(base_url('superadmin/companies/create'), ENT_QUOTES, 'UTF-8') ?>">Nova loja</a>
      </div>
    </div>

    <!-- Tabela -->
    <div class="table-wrap">
      <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>Slug</th>
          <th>WhatsApp</th>
          <th>Admin</th>
          <th>Pedidos</th>
          <th>Status</th>
          <th>Criada em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" style="text-align:center;padding:2rem;color:#64748b">Nenhuma loja encontrada.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td style="color:#94a3b8;font-size:.8rem"><?= (int)($r['id'] ?? 0) ?></td>
              <td style="font-weight:600"><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><a href="<?= htmlspecialchars(base_url(rawurlencode((string)($r['slug'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="color:#0f172a;font-family:ui-monospace,monospace;font-size:.82rem;text-decoration:none"><?= htmlspecialchars((string)($r['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></td>
              <td style="color:#475569"><?= htmlspecialchars((string)($r['whatsapp'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size:.82rem;color:#475569"><?= htmlspecialchars((string)($r['admin_email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-weight:600"><?= (int)($r['order_count'] ?? 0) ?></td>
              <td>
                <?php if (!empty($r['active'])): ?>
                  <span class="badge on">Ativa</span>
                <?php else: ?>
                  <span class="badge off">Inativa</span>
                <?php endif; ?>
              </td>
              <td style="font-size:.8rem;color:#64748b"><?= htmlspecialchars(substr((string)($r['created_at'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <div class="actions">
                  <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/companies/' . (int)$r['id']), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                  <form method="post" action="<?= htmlspecialchars(base_url('superadmin/companies/' . (int)$r['id'] . '/toggle'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Alterar status desta loja?');">
                    <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                    <button type="submit" class="btn <?= !empty($r['active']) ? 'danger' : 'secondary' ?> sm">
                      <?= !empty($r['active']) ? 'Desativar' : 'Ativar' ?>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <?php $qParam = $searchQuery !== '' ? '&q=' . urlencode($searchQuery) : ''; ?>
      <div class="pager">
        <?php if ($page > 1): ?>
          <a href="<?= htmlspecialchars(base_url('superadmin?page=' . ($page - 1) . $qParam), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
        <?php endif; ?>
        <span>Página <?= (int)$page ?> / <?= (int)$totalPages ?></span>
        <?php if ($page < $totalPages): ?>
          <a href="<?= htmlspecialchars(base_url('superadmin?page=' . ($page + 1) . $qParam), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

<?php include __DIR__ . '/layout_end.php'; ?>
