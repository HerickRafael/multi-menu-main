<?php
$title    = 'Categoria - ' . ($company['name'] ?? '');
$editing  = !empty($cat['id']);
$slug     = rawurlencode((string)($company['slug'] ?? ''));
$action   = $editing
  ? "admin/{$slug}/categories/" . (int)$cat['id']
  : "admin/{$slug}/categories";

// Configuração do header padronizado
$pageTitle = ($editing ? 'Editar' : 'Nova') . ' Categoria';
$pageDescription = $editing ? 'Altere os dados da categoria' : 'Adicione uma nova categoria ao menu';
$pageIcon = $editing 
    ? '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    : '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Categorias', 'url' => base_url("admin/{$slug}/categories")],
    ['label' => $editing ? 'Editar' : 'Nova']
];
$actions = [
    ['label' => 'Salvar', 'onclick' => "document.getElementById('category-form').submit()", 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'primary' => true]
];
$activeSlug = $slug;

ob_start(); ?>

<div class="mx-auto max-w-4xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<form id="category-form"
      method="post"
      action="<?= e(base_url($action)) ?>"
      class="relative grid gap-6 rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">

  <!-- CSRF / METHOD -->
  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>
  <?php if ($editing): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

  <!-- CAMPOS -->
  <label class="grid gap-1">
    <span class="text-sm font-medium text-slate-700">Nome</span>
    <input name="name" value="<?= e($cat['name'] ?? '') ?>" required
           class="rounded-xl border border-slate-300 px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400"
           placeholder="Ex: Bebidas, Lanches">
  </label>

  <label class="grid gap-1">
    <span class="text-sm font-medium text-slate-700">Ordem</span>
    <input name="sort_order" type="number" min="0" step="1" value="<?= e($cat['sort_order'] ?? 0) ?>"
           class="rounded-xl border border-slate-300 px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400"
           placeholder="0">
    <span class="text-xs text-slate-500">Define a posição da categoria na lista.</span>
  </label>

  <label class="inline-flex items-center gap-2 pt-1">
    <input type="checkbox" name="active"
           class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
           <?= !isset($cat['active']) || $cat['active'] ? 'checked' : '' ?>>
    <span class="text-sm text-slate-700">Ativa</span>
  </label>

  <!-- rodapé interno só pra respiro -->
  <div class="pb-1"></div>

</form>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
