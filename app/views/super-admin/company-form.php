<?php
declare(strict_types=1);
/** @var bool $editMode */
/** @var array|null $company */
/** @var array $errors */
/** @var array $old */
/** @var array|null $flash */
/** @var string $superAdminName */
/** @var string $adminEmailDisplay */

$errors = $errors ?? [];
$old = $old ?? [];

function sa_val(array $old, ?array $company, string $key, string $default = ''): string {
    if (array_key_exists($key, $old)) {
        return (string)$old[$key];
    }
    if ($company && array_key_exists($key, $company)) {
        return (string)$company[$key];
    }

    return $default;
}

$c = $company ?? null;
$nameVal = sa_val($old, $c, 'company_name', $c ? (string)$c['name'] : '');
$slugVal = sa_val($old, $c, 'slug', $c ? (string)$c['slug'] : '');
$waVal = sa_val($old, $c, 'whatsapp', $c ? (string)($c['whatsapp'] ?? '') : '');
$addrVal = sa_val($old, $c, 'address', $c ? (string)($c['address'] ?? '') : '');
$adminNameVal = (string)($old['admin_name'] ?? '');
$adminEmailVal = (string)($old['admin_email'] ?? '');
$activeChecked = isset($old['company_active'])
    ? !empty($old['company_active'])
    : (($c && !empty($c['active'])) || empty($editMode));

$hideTopbar = false;
$adminEmailDisplay = $adminEmailDisplay ?? '';

include __DIR__ . '/layout.php';
?>
<style>
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
  @media (max-width: 700px) { .form-grid { grid-template-columns: 1fr; } }
  .form-grid .row-full { grid-column: 1 / -1; }
  .form-actions { margin-top: 1.25rem; display: flex; gap: .6rem; }
</style>
  <?php if (!empty($flash)): ?>
    <div class="flash <?= ($flash['type'] ?? '') === 'success' ? 'ok' : 'bad' ?>">
      <?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors['_global'])): ?>
    <div class="flash bad"><?= htmlspecialchars((string)$errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <div class="toolbar-left">
      <h1><?= !empty($editMode) ? 'Editar loja' : 'Nova loja' ?></h1>
      <p class="sub"><?= !empty($editMode) ? 'Atualize os dados da loja e do administrador' : 'Crie uma nova loja e defina o administrador inicial' ?></p>
    </div>
    <a class="btn secondary" href="<?= htmlspecialchars(base_url('superadmin'), ENT_QUOTES, 'UTF-8') ?>">← Voltar</a>
  </div>

  <div class="card">

      <form method="post" action="<?= htmlspecialchars(
          !empty($editMode) && !empty($company['id'])
              ? base_url('superadmin/companies/' . (int)$company['id'])
              : base_url('superadmin/companies'),
          ENT_QUOTES,
          'UTF-8'
      ) ?>">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>

        <div class="section-title">Dados da loja</div>
        <div class="form-grid">
          <div class="row">
            <label for="company_name">Nome da loja</label>
            <input type="text" id="company_name" name="company_name" required maxlength="150" value="<?= htmlspecialchars($nameVal, ENT_QUOTES, 'UTF-8') ?>" style="max-width:100%">
            <?php if (!empty($errors['company_name'])): ?><div class="err"><?= htmlspecialchars($errors['company_name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
          <div class="row">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" required maxlength="100" pattern="[a-z0-9\-]+" value="<?= htmlspecialchars($slugVal, ENT_QUOTES, 'UTF-8') ?>" style="max-width:100%">
            <p class="hint">URL: <?= htmlspecialchars(base_url(''), ENT_QUOTES, 'UTF-8') ?><strong>{slug}</strong></p>
            <?php if (!empty($errors['slug'])): ?><div class="err"><?= htmlspecialchars($errors['slug'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
          <div class="row">
            <label for="whatsapp">WhatsApp</label>
            <input type="text" id="whatsapp" name="whatsapp" placeholder="Somente números com DDD" value="<?= htmlspecialchars($waVal, ENT_QUOTES, 'UTF-8') ?>" style="max-width:100%">
            <?php if (!empty($errors['whatsapp'])): ?><div class="err"><?= htmlspecialchars($errors['whatsapp'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
          <div class="row">
            <label for="address">Endereço</label>
            <textarea id="address" name="address" style="max-width:100%"><?= htmlspecialchars($addrVal, ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>

        <?php if (!empty($editMode)): ?>
          <div class="row">
            <label class="chk">
              <input type="checkbox" name="company_active" value="1" <?= $activeChecked ? 'checked' : '' ?>>
              Loja ativa
            </label>
          </div>
        <?php endif; ?>

        <?php if (empty($editMode)): ?>
          <div class="section-title" style="margin-top:1.25rem">Admin da loja</div>
          <div class="form-grid">
            <div class="row">
              <label for="admin_name">Nome do admin</label>
              <input type="text" id="admin_name" name="admin_name" required maxlength="150" value="<?= htmlspecialchars($adminNameVal, ENT_QUOTES, 'UTF-8') ?>" style="max-width:100%">
              <?php if (!empty($errors['admin_name'])): ?><div class="err"><?= htmlspecialchars($errors['admin_name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
            <div class="row">
              <label for="admin_email">Email do admin</label>
              <input type="email" id="admin_email" name="admin_email" required value="<?= htmlspecialchars($adminEmailVal, ENT_QUOTES, 'UTF-8') ?>" style="max-width:100%">
              <?php if (!empty($errors['admin_email'])): ?><div class="err"><?= htmlspecialchars($errors['admin_email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
            <div class="row">
              <label for="admin_password">Senha</label>
              <input type="password" id="admin_password" name="admin_password" required minlength="8" autocomplete="new-password" style="max-width:100%">
              <?php if (!empty($errors['admin_password'])): ?><div class="err"><?= htmlspecialchars($errors['admin_password'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="section-title" style="margin-top:1.25rem">Admin da loja</div>
          <div class="form-grid">
            <div class="row">
              <label>Email atual</label>
              <input type="text" class="readonly" readonly value="<?= htmlspecialchars($adminEmailDisplay, ENT_QUOTES, 'UTF-8') ?>" style="max-width:100%">
            </div>
            <div class="row">
              <label for="admin_password_new">Nova senha</label>
              <input type="password" id="admin_password_new" name="admin_password_new" minlength="8" autocomplete="new-password" placeholder="Deixe em branco para não alterar" style="max-width:100%">
              <?php if (!empty($errors['admin_password_new'])): ?><div class="err"><?= htmlspecialchars($errors['admin_password_new'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="form-actions">
          <button type="submit" class="btn">Salvar</button>
          <a class="btn secondary" href="<?= htmlspecialchars(base_url('superadmin'), ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
        </div>
      </form>
    </div><!-- .card -->
<?php include __DIR__ . '/layout_end.php'; ?>
