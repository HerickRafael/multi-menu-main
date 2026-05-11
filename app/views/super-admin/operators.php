<?php
declare(strict_types=1);
/** @var array $companies */
/** @var array|null $selectedCompany */
/** @var int $selectedCompanyId */
/** @var string $selectedRole */
/** @var int $statsTotalUsers */
/** @var int $statsActiveUsers */
/** @var int $statsInactiveUsers */
/** @var int $statsOwnerUsers */
/** @var int $statsStaffUsers */
/** @var int $statsRootUsers */
/** @var array $rows */
/** @var string $superAdminName */
$hideTopbar = false;
include __DIR__ . '/layout.php';
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Usuários e Operadores</h1>
    <p class="sub">Gestão de acesso por loja com visão global e por função.</p>
  </div>
  <div class="toolbar-right">
    <form method="get" action="<?= htmlspecialchars(base_url('superadmin/operators'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
      <select name="company_id" style="max-width:220px;">
        <option value="0">Todas as lojas</option>
        <?php foreach ($companies as $company): ?>
          <?php $cid = (int)($company['id'] ?? 0); ?>
          <option value="<?= $cid ?>" <?= $selectedCompanyId === $cid ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)($company['name'] ?? 'Loja'), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="role" style="max-width:180px;">
        <option value="">Todos os perfis</option>
        <option value="owner" <?= $selectedRole === 'owner' ? 'selected' : '' ?>>Owner</option>
        <option value="staff" <?= $selectedRole === 'staff' ? 'selected' : '' ?>>Staff</option>
        <option value="root" <?= $selectedRole === 'root' ? 'selected' : '' ?>>Root</option>
      </select>
      <button type="submit" class="btn secondary sm">Aplicar</button>
      <?php if ($selectedCompanyId > 0 || $selectedRole !== ''): ?>
        <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/operators'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total de usuários</div>
    <div class="stat-value"><?= (int)$statsTotalUsers ?></div>
    <div class="stat-sub">no escopo filtrado</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Ativos</div>
    <div class="stat-value" style="color:#166534"><?= (int)$statsActiveUsers ?></div>
    <div class="stat-sub">com acesso liberado</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Owners</div>
    <div class="stat-value"><?= (int)$statsOwnerUsers ?></div>
    <div class="stat-sub">responsáveis por loja</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Staff</div>
    <div class="stat-value"><?= (int)$statsStaffUsers ?></div>
    <div class="stat-sub">operadores da loja</div>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Loja</th>
          <th>Perfil</th>
          <th>Status</th>
          <th>Criado em</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:#64748b">Nenhum usuário encontrado.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td style="font-weight:600"><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($r['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if (!empty($r['company_id'])): ?>
                <div style="font-weight:600"><?= htmlspecialchars((string)($r['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div style="font-size:.78rem;color:#64748b">/<?= htmlspecialchars((string)($r['company_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              <?php else: ?>
                <span style="color:#64748b">Escopo global</span>
              <?php endif; ?>
            </td>
            <td>
              <?php $role = (string)($r['role'] ?? 'staff'); ?>
              <?php if ($role === 'root'): ?>
                <span class="badge" style="background:#dbeafe;color:#1d4ed8;">Root</span>
              <?php elseif ($role === 'owner'): ?>
                <span class="badge" style="background:#ede9fe;color:#5b21b6;">Owner</span>
              <?php else: ?>
                <span class="badge off">Staff</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($r['active'])): ?>
                <span class="badge on">Ativo</span>
              <?php else: ?>
                <span class="badge off">Inativo</span>
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
