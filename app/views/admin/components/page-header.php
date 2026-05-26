<?php
/**
 * Componente de Header Padrão para páginas admin
 * 
 * Variáveis esperadas:
 * @var string $pageTitle - Título da página
 * @var string $pageDescription - Descrição/subtítulo
 * @var string $pageIcon - SVG do ícone (HTML)
 * @var array $breadcrumbs - Array de breadcrumbs [['label' => 'Nome', 'url' => '/path'], ...]
 * @var array $actions - Array de botões de ação [['label' => 'Nome', 'url' => '/path', 'icon' => 'svg', 'primary' => true/false], ...]
 * @var string $activeSlug - Slug da empresa atual
 * @var string $extraHeaderContent - Conteúdo HTML extra para adicionar antes das actions (ex: selects)
 */

$pageTitle = $pageTitle ?? 'Página';
$pageDescription = $pageDescription ?? '';
$pageIcon = $pageIcon ?? '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = $breadcrumbs ?? [];
$actions = $actions ?? [];
$extraHeaderContent = $extraHeaderContent ?? '';

// Buscar activeSlug de múltiplas fontes possíveis (fallback)
$activeSlug = $activeSlug ?? $slug ?? $company['slug'] ?? '';
$activeSlug = trim($activeSlug, '/');

// Construir URL base do dashboard (evitar barras duplas)
// IMPORTANTE: $activeSlug deve conter o slug da empresa (ex: 'wollburger')
$dashboardUrl = $activeSlug !== '' ? 'admin/' . $activeSlug . '/dashboard' : 'admin/dashboard';

// Função helper para limpar URLs com barras duplas
$cleanUrl = function($url) {
    // Remove barras duplas, exceto após o protocolo (http:// ou https://)
    return preg_replace('#(?<!:)//+#', '/', $url);
};
?>

<!-- BREADCRUMB -->
<?php if (!empty($breadcrumbs)): ?>
<nav class="mb-4 flex items-center gap-2 text-sm text-slate-500 flex-wrap">
  <a href="<?= e($cleanUrl(base_url($dashboardUrl))) ?>" class="hover:text-slate-700 transition flex items-center gap-1">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Dashboard
  </a>
  <?php foreach ($breadcrumbs as $crumb): ?>
    <svg class="h-4 w-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <?php if (!empty($crumb['url'])): ?>
      <a href="<?= e($cleanUrl($crumb['url'])) ?>" class="hover:text-slate-700 transition"><?= e($crumb['label']) ?></a>
    <?php else: ?>
      <span class="text-slate-900 font-medium"><?= e($crumb['label']) ?></span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<!-- HEADER -->
<header class="mb-6 flex flex-wrap items-center gap-3">
  <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 flex-shrink-0">
    <?= $pageIcon ?>
  </span>
  <div class="flex-1 min-w-0">
    <h1 class="text-2xl font-semibold text-zinc-800 truncate"><?= e($pageTitle) ?></h1>
    <?php if ($pageDescription): ?>
      <p class="text-sm text-zinc-500"><?= $pageDescription ?></p>
    <?php endif; ?>
  </div>
  
  <?php if (!empty($extraHeaderContent) || !empty($actions)): ?>
  <div class="ml-auto flex flex-wrap items-center gap-2">
    <?php if (!empty($extraHeaderContent)): ?>
      <?= $extraHeaderContent ?>
    <?php endif; ?>
    <?php foreach ($actions as $actionItem): ?>
      <?php if (!empty($actionItem['onclick'])): ?>
        <button onclick="<?= e($actionItem['onclick']) ?>" class="inline-flex items-center gap-2 rounded-lg <?= !empty($actionItem['primary']) ? 'bg-zinc-900 text-white font-medium hover:bg-zinc-800' : 'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' ?> px-4 py-2 text-sm transition">
          <?php if (!empty($actionItem['icon'])): ?><?= $actionItem['icon'] ?><?php endif; ?>
          <?= e($actionItem['label']) ?>
        </button>
      <?php else: ?>
        <a href="<?= e($actionItem['url'] ?? '#') ?>" class="inline-flex items-center gap-2 rounded-lg <?= !empty($actionItem['primary']) ? 'bg-zinc-900 text-white font-medium hover:bg-zinc-800' : 'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' ?> px-4 py-2 text-sm transition">
          <?php if (!empty($actionItem['icon'])): ?><?= $actionItem['icon'] ?><?php endif; ?>
          <?= e($actionItem['label']) ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</header>
