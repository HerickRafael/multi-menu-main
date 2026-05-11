<?php
$title = 'Login - ' . ($company['name'] ?? 'Empresa');
ob_start(); ?>
<div class="flex flex-col items-center mb-6">
    <img src="/assets/icons/admin/logo-multimenu.png" alt="Multi Menu" class="w-16 h-16 mb-3 rounded-2xl">
    <h1 class="text-2xl font-bold">Login - <?= e($company['name'] ?? '') ?></h1>
</div>
<?php if (($_GET['msg'] ?? '') === 'sessao_expirada'): ?>
  <div class="mb-3 p-3 bg-yellow-100 text-yellow-800 rounded-xl text-sm">Sua sessão expirou. Por favor, entre novamente.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="mb-3 p-3 bg-red-100 text-red-800 rounded-xl"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" class="grid gap-3 max-w-md">
  <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfProtection::generateToken() ?>">
  <label class="grid gap-1">
    <span class="text-sm">E-mail</span>
    <input name="email" type="email" class="border rounded-xl p-2">
  </label>
  <label class="grid gap-1">
    <span class="text-sm">Senha</span>
    <input name="password" type="password" class="border rounded-xl p-2">
  </label>
  <button class="mt-2 rounded-xl bg-slate-900 px-4 py-2 text-white hover:bg-slate-800">Entrar</button>
</form>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
