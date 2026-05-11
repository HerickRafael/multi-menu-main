<?php
declare(strict_types=1);
/** @var array $matrix */
/** @var array $admins */
include __DIR__ . '/layout.php';
$roles = $matrix['roles'] ?? [];
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>RBAC - Roles e Permissões</h1>
    <p class="sub">Matriz de acesso do super admin.</p>
  </div>
</div>

<div class="card" style="padding:1rem;margin-bottom:1rem;">
  <h3 style="margin-bottom:.7rem;">Atribuir Role</h3>
  <form method="post" action="<?= htmlspecialchars(base_url('superadmin/rbac/assign-role'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <select name="user_id" required>
      <option value="">Usuário</option>
      <?php foreach ($admins as $admin): ?>
        <option value="<?= (int)($admin['id'] ?? 0) ?>"><?= htmlspecialchars((string)($admin['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)($admin['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</option>
      <?php endforeach; ?>
    </select>
    <select name="role_id" required>
      <option value="">Role</option>
      <?php foreach ($roles as $role): ?>
        <option value="<?= (int)($role['id'] ?? 0) ?>"><?= htmlspecialchars((string)($role['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn secondary sm" type="submit">Atribuir</button>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>Role</th>
        <th>Slug</th>
        <th>Permissões</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($roles as $role): ?>
        <tr>
          <td><?= htmlspecialchars((string)($role['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($role['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php $perms = $role['permissions'] ?? []; ?>
            <?php if (empty($perms)): ?>
              <span style="color:#64748b;">Sem permissões</span>
            <?php else: ?>
              <div style="display:flex;flex-wrap:wrap;gap:.35rem;">
                <?php foreach ($perms as $perm): ?>
                  <span class="badge" style="background:#e2e8f0;color:#0f172a;"><?= htmlspecialchars((string)($perm['key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php';
