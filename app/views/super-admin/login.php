<?php
declare(strict_types=1);
/** @var string $title */
/** @var array|null $flash */
/** @var bool $rateLimited */
$hideTopbar = true;
include __DIR__ . '/layout.php';
?>
    <div class="card" style="max-width:430px;margin:0 auto;">
      <div style="text-align:center;margin-bottom:1.25rem">
        <div style="font-size:1.8rem;margin-bottom:.35rem">MM</div>
        <div style="font-size:1.05rem;font-weight:700;color:#0f172a;letter-spacing:-.02em">MultiMenu</div>
        <div style="font-size:.73rem;color:#64748b;text-transform:uppercase;letter-spacing:.07em;margin-top:.1rem">Super Admin</div>
      </div>

      <?php if (!empty($flash)): ?>
        <div class="flash <?= ($flash['type'] ?? '') === 'success' ? 'ok' : 'bad' ?>">
          <?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <h1 style="font-size:1rem;font-weight:700;margin:0 0 .2rem">Entrar no painel</h1>
      <p class="sub" style="margin:0 0 1.25rem">Use suas credenciais de super administrador.</p>

      <form method="post" action="<?= htmlspecialchars(base_url('superadmin/login'), ENT_QUOTES, 'UTF-8') ?>">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <div class="row">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required autocomplete="username" <?= !empty($rateLimited) ? 'disabled' : '' ?>>
        </div>
        <div class="row">
          <label for="password">Senha</label>
          <input type="password" id="password" name="password" required autocomplete="current-password" <?= !empty($rateLimited) ? 'disabled' : '' ?>>
        </div>
        <button type="submit" class="btn" style="width:100%" <?= !empty($rateLimited) ? 'disabled' : '' ?>>Entrar</button>
      </form>
    </div>
<?php include __DIR__ . '/layout_end.php'; ?>
