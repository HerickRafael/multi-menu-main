<?php
declare(strict_types=1);
/** @var array $data */
/** @var array $companies */
/** @var int $selectedCompanyId */
include __DIR__ . '/layout.php';
$allFlags = $data['all_flags'] ?? [];
$tenantFlags = $data['tenant_flags'] ?? [];
$tenantMap = [];
foreach ($tenantFlags as $tf) {
    $tenantMap[(int)$tf['feature_flag_id']] = $tf;
}
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Feature Flags por Loja</h1>
    <p class="sub">Controle fino de funcionalidades por tenant.</p>
  </div>
  <div class="toolbar-right">
    <form method="get" action="<?= htmlspecialchars(base_url('superadmin/feature-flags'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.5rem;">
      <select name="company_id">
        <?php foreach ($companies as $company): ?>
          <?php $cid = (int)($company['id'] ?? 0); ?>
          <option value="<?= $cid ?>" <?= $selectedCompanyId === $cid ? 'selected' : '' ?>><?= htmlspecialchars((string)($company['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn secondary sm" type="submit">Trocar loja</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>Flag</th>
        <th>Descrição</th>
        <th>Default</th>
        <th>Ativa na Loja</th>
        <th>Ação</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($allFlags as $flag): ?>
        <?php $fid = (int)($flag['id'] ?? 0); ?>
        <?php $tenant = $tenantMap[$fid] ?? null; ?>
        <?php $enabled = (int)($tenant['enabled'] ?? (int)($flag['default_enabled'] ?? 0)) === 1; ?>
        <tr>
          <td>
            <div style="font-weight:700;"><?= htmlspecialchars((string)($flag['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div style="font-size:.8rem;color:#64748b;"><?= htmlspecialchars((string)($flag['flag_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
          </td>
          <td><?= htmlspecialchars((string)($flag['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= (int)($flag['default_enabled'] ?? 0) === 1 ? 'ON' : 'OFF' ?></td>
          <td><?= $enabled ? 'ON' : 'OFF' ?></td>
          <td>
            <form method="post" action="<?= htmlspecialchars(base_url('superadmin/feature-flags/toggle'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.4rem;align-items:center;">
              <input type="hidden" name="company_id" value="<?= $selectedCompanyId ?>">
              <input type="hidden" name="feature_flag_id" value="<?= $fid ?>">
              <input type="hidden" name="enabled" value="<?= $enabled ? 0 : 1 ?>">
              <input type="text" name="reason" placeholder="Motivo (opcional)" style="max-width:200px;">
              <button class="btn secondary sm" type="submit"><?= $enabled ? 'Desativar' : 'Ativar' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php';
