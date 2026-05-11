<?php
/** ============================================================================
 * app/views/public/product-not-found.php
 * Página exibida quando o produto não é encontrado
 * Mantém o design do cardápio e redireciona para a home
 * ============================================================================ */

$company = $company ?? [];
$title   = ($company['name'] ?? 'Cardápio') . ' - Produto não encontrado';

ob_start();

/* ===== Helpers ===== */

if (!function_exists('e')) {
    /**
     * Escapa saída HTML. Fallback caso o framework não defina e().
     */
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// normalize_color_hex() definida globalmente em app/core/CommonHelpers.php

/* ===== Defaults de cor ===== */
const COLOR_DEFAULT_TEXT   = '#FFFFFF';
const COLOR_DEFAULT_BUTTON = '#FACC15';
const COLOR_DEFAULT_BG     = '#5B21B6';
const COLOR_DEFAULT_BORDER = '#7C3AED';

/* ===== Cores do cardápio ===== */
$headerTextColor   = normalize_color_hex((string)($company['menu_header_text_color']   ?? ''), COLOR_DEFAULT_TEXT);
$headerButtonColor = normalize_color_hex((string)($company['menu_header_button_color'] ?? ''), COLOR_DEFAULT_BUTTON);
$headerBgColor     = normalize_color_hex(
    (string)($company['menu_header_bg_color'] ?? $company['menu_logo_bg_color'] ?? ''),
    COLOR_DEFAULT_BG
);
$logoBorderColor   = normalize_color_hex(
    (string)($company['menu_logo_border_color'] ?? $company['menu_logo_bg_color'] ?? ''),
    COLOR_DEFAULT_BORDER
);

/* ===== URLs ===== */
$slug    = (string)($company['slug'] ?? '');
$homeUrl = function_exists('base_url') ? base_url($slug !== '' ? $slug : '') : '/';

$bannerUrl = !empty($company['banner'])
    ? base_url($company['banner'])
    : null;

$logoUrl = !empty($company['logo'])
    ? base_url($company['logo'])
    : null;

/* ===== Sessão do cliente (iniciada no front controller) ===== */
$customer      = $_SESSION['customer'] ?? null;
$showFooterMenu = true;

/* ===== Contagem regressiva (segundos) ===== */
const REDIRECT_DELAY = 5;
?>

<!-- ================================================================
     HEADER — mantém identidade visual do cardápio
     ================================================================ -->
<header>
  <style>
    /* Remove foco visual desnecessário em elementos decorativos */
    .no-focus-ring:focus,
    .no-focus-ring:focus-visible,
    .no-focus-ring:focus-within,
    .no-focus-ring:target { outline: none !important; box-shadow: none !important; }
    .no-focus-ring { -webkit-tap-highlight-color: transparent; }

    /* CSS variables para reutilização de cores no componente */
    .menu-header {
      --menu-header-text:   <?= e($headerTextColor)   ?>;
      --menu-header-button: <?= e($headerButtonColor) ?>;
      --menu-header-bg:     <?= e($headerBgColor)     ?>;

      color:            var(--menu-header-text);
      background-color: var(--menu-header-bg);
    }
    .menu-header-title,
    .menu-header-text  { color: var(--menu-header-text);   }
    .menu-header-btn   { background-color: var(--menu-header-button); color: #fff; }
  </style>

  <div class="rounded-2xl overflow-hidden">

    <!-- Banner ou barra de cor sólida -->
    <?php if ($bannerUrl): ?>
      <div class="relative">
        <img src="<?= e($bannerUrl) ?>" class="w-full h-36 md:h-48 object-cover" alt="Banner">
        <div class="absolute inset-0 bg-black/30" aria-hidden="true"></div>
      </div>
    <?php else: ?>
      <div class="h-24" style="background-color: <?= e($headerBgColor) ?>;" aria-hidden="true"></div>
    <?php endif; ?>

    <!-- Identidade: logo + nome + endereço -->
    <div class="p-5 pr-28 relative -mt-10 rounded-2xl no-focus-ring menu-header">

      <?php
        /* Ícone de fallback reutilizável */
        $fallbackIcon = <<<'SVG'
          <svg class="w-12 h-12 text-white/40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3"
                  stroke="currentColor" stroke-width="1.6"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        SVG;

        $logoCircleStyle = sprintf(
            'background-color:%s; border-color:%s;',
            e($logoBorderColor),
            e($logoBorderColor)
        );
      ?>

      <?php if ($logoUrl): ?>
        <!-- Logo real com fallback automático via onerror -->
        <img src="<?= e($logoUrl) ?>"
             class="w-24 h-24 rounded-full object-cover border-4 absolute -top-10 right-6 pointer-events-none"
             style="<?= $logoCircleStyle ?>"
             alt="<?= e($company['name'] ?? 'Logo') ?>"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="w-24 h-24 rounded-full border-4 absolute -top-10 right-6 pointer-events-none hidden items-center justify-center"
             style="<?= $logoCircleStyle ?>"
             aria-hidden="true">
          <?= $fallbackIcon ?>
        </div>
      <?php else: ?>
        <div class="w-24 h-24 rounded-full border-4 absolute -top-10 right-6 pointer-events-none flex items-center justify-center"
             style="<?= $logoCircleStyle ?>"
             aria-hidden="true">
          <?= $fallbackIcon ?>
        </div>
      <?php endif; ?>

      <h1 class="text-xl font-bold menu-header-title"><?= e($company['name'] ?? 'Cardápio') ?></h1>

      <?php if (!empty($company['address'])): ?>
        <p class="menu-header-text text-xs mt-1"><?= e($company['address']) ?></p>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- ================================================================
     MAIN — mensagem de produto não encontrado
     ================================================================ -->
<main class="mt-6 mb-28 px-4">
  <div class="flex flex-col items-center justify-center py-16">

    <!-- Ícone de aviso com pulse -->
    <div class="relative mb-6" aria-hidden="true">
      <div class="w-20 h-20 rounded-full flex items-center justify-center animate-pulse"
           style="background-color: <?= e($headerBgColor) ?>15;">
        <div class="w-16 h-16 rounded-full flex items-center justify-center"
             style="background-color: <?= e($headerBgColor) ?>25;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.5" stroke="<?= e($headerBgColor) ?>" class="w-8 h-8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
          </svg>
        </div>
      </div>
    </div>

    <h2 class="text-xl font-bold text-gray-800 mb-2 text-center">
      Ops! Produto não encontrado
    </h2>
    <p class="text-gray-500 text-sm text-center mb-8 max-w-xs">
      Este produto não está mais disponível ou foi removido do cardápio.
    </p>

    <!-- CTA: volta ao cardápio -->
    <a href="<?= e($homeUrl) ?>"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold text-white shadow-md transition-all duration-200 hover:shadow-lg active:scale-95"
       style="background-color: <?= e($headerBgColor) ?>;">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
           stroke-width="1.5" stroke="currentColor" class="w-4 h-4" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
      </svg>
      Ver cardápio
    </a>

    <!-- Contador regressivo -->
    <p class="text-xs text-gray-400 mt-8">
      Redirecionando em
      <span id="countdown"
            class="font-medium"
            style="color: <?= e($headerBgColor) ?>;"
            aria-live="polite"
            aria-atomic="true"><?= REDIRECT_DELAY ?></span>s
    </p>

  </div>
</main>

<script>
(function () {
  'use strict';

  // json_encode garante URL segura dentro de JS (evita quebra por aspas simples)
  var homeUrl  = <?= json_encode($homeUrl) ?>;
  var total    = <?= REDIRECT_DELAY ?>;
  var seconds  = total;
  var el       = document.getElementById('countdown');
  var interval = null;

  function tick() {
    seconds -= 1;
    if (el) el.textContent = seconds;

    if (seconds <= 0) {
      stop();
      window.location.href = homeUrl;
    }
  }

  function start() {
    if (!interval) {
      interval = setInterval(tick, 1000);
    }
  }

  function stop() {
    clearInterval(interval);
    interval = null;
  }

  // Pausa o countdown se o usuário mudar de aba (evita redirect indesejado)
  document.addEventListener('visibilitychange', function () {
    document.hidden ? stop() : start();
  });

  start();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
