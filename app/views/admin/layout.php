<?php
// SPA Guide capture mode: emit only the inner content (no chrome) for SPA payload injection.
if (!empty($GLOBALS['__SPA_GUIDE_CAPTURE__'])) {
    echo $content ?? '';
    return;
}
?><!doctype html>
<html lang="pt-br">
<?php
$companyData = is_array($company ?? null) ? $company : null;
$activeSlugValue = $activeSlug ?? null;
$companySlug = $activeSlugValue ?? ($companyData['slug'] ?? null);
$adminPrimaryColor = admin_theme_primary_color($companyData);
$adminPrimarySoft = hex_to_rgba($adminPrimaryColor, 0.55, $adminPrimaryColor);
$adminPrimaryGradient = admin_theme_gradient($companyData);
$adminPrimaryUltrasoft = hex_to_rgba($adminPrimaryColor, 0.10, $adminPrimaryColor);
$adminPrimaryLight = hex_to_rgba($adminPrimaryColor, 0.18, $adminPrimaryColor);
$kdsDataUrl = $companySlug ? base_url('admin/' . rawurlencode($companySlug) . '/kds/data') : null;
$orderDetailBaseUrl = $companySlug ? base_url('admin/' . rawurlencode($companySlug) . '/orders/show?id=') : null;
$kdsPageUrl = $companySlug ? base_url('admin/' . rawurlencode($companySlug) . '/kds') : null;
$bellConfig = (string)(config('kds_bell_url') ?? '');
$resolvedBellUrl = '';
if ($bellConfig !== '') {
    if (preg_match('/^(data:|https?:\\/\\/|\\/\\/)/i', $bellConfig)) {
        $resolvedBellUrl = $bellConfig;
    } else {
        $resolvedBellUrl = base_url(ltrim($bellConfig, '/'));
    }
}

?>
<head>
  <meta charset="utf-8">
  <title><?= e($title ?? 'Admin') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1, user-scalable=no">
  <meta name="theme-color" content="<?= e($adminPrimaryColor) ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="<?= e($companyData['name'] ?? 'Admin') ?>">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="application-name" content="<?= e($companyData['name'] ?? 'Multi Menu Admin') ?>">
  <meta name="msapplication-TileColor" content="<?= e($adminPrimaryColor) ?>">
  <meta name="msapplication-navbutton-color" content="<?= e($adminPrimaryColor) ?>">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="msapplication-config" content="<?= base_url('assets/icons/admin/browserconfig.xml') ?>">
  
  <!-- PWA Manifest (dinâmico por empresa se disponível) -->
  <?php if ($companySlug): ?>
    <link rel="manifest" href="<?= base_url('admin/' . rawurlencode($companySlug) . '/manifest.webmanifest') ?>">
  <?php else: ?>
    <link rel="manifest" href="<?= base_url('admin-manifest.webmanifest') ?>">
  <?php endif; ?>
  
  <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/icons/admin/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('assets/icons/admin/favicon-16x16.png') ?>">
  
  <!-- Apple Touch Icons -->
  <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/icons/admin/apple-touch-icon.png') ?>">
  <link rel="apple-touch-icon" sizes="152x152" href="<?= base_url('assets/icons/admin/icon-152x152.png') ?>">
  <link rel="apple-touch-icon" sizes="144x144" href="<?= base_url('assets/icons/admin/icon-144x144.png') ?>">
  <link rel="apple-touch-icon" sizes="120x120" href="<?= base_url('assets/icons/admin/icon-120x120.png') ?>">
  
  <!-- Splash Screens para iOS -->
  <link rel="apple-touch-startup-image" href="<?= base_url('assets/icons/admin/splash-640x1136.png') ?>" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)">
  <link rel="apple-touch-startup-image" href="<?= base_url('assets/icons/admin/splash-750x1334.png') ?>" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">
  <link rel="apple-touch-startup-image" href="<?= base_url('assets/icons/admin/splash-1242x2208.png') ?>" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)">
  <link rel="apple-touch-startup-image" href="<?= base_url('assets/icons/admin/splash-1125x2436.png') ?>" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)">
  <link rel="apple-touch-startup-image" href="<?= base_url('assets/icons/admin/splash-1170x2532.png') ?>" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)">
  <link rel="apple-touch-startup-image" href="<?= base_url('assets/icons/admin/splash-1284x2778.png') ?>" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3)">
  
  <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tailwind.min.css') ?: time() ?>">
  <style>
    :root {
      --admin-primary-color: <?= e($adminPrimaryColor) ?>;
      --admin-primary-soft: <?= e($adminPrimarySoft) ?>;
      --admin-primary-gradient: <?= e($adminPrimaryGradient) ?>;
      --admin-primary-ultrasoft: <?= e($adminPrimaryUltrasoft) ?>;
      --admin-primary-light: <?= e($adminPrimaryLight) ?>;
    }
  </style>
  <link rel="stylesheet" href="<?= base_url('assets/css/admin-theme.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/admin-notifications.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/lazy-loading.css') ?>">
  <meta name="csrf-token" content="<?= \App\Middleware\CsrfProtection::generateToken(false) ?>">
  <!-- Ponto único de entrega de config backend → frontend -->
  <script>
  window.APP_CONFIG = {
    kdsUrl:      <?= json_encode($kdsDataUrl ?? null) ?>,
    orderUrl:    <?= json_encode($orderDetailBaseUrl ?? null) ?>,
    kdsPage:     <?= json_encode($kdsPageUrl ?? null) ?>,
    bellUrl:     <?= json_encode($resolvedBellUrl) ?>,
    companySlug: <?= json_encode($companySlug ?? null) ?>
  };
  </script>
</head>
<body class="bg-slate-50 text-slate-900">
  <?php
  // Verificar se deve exibir shell completo (topbar + sidebar)
  $showSidebar = !isset($hideSidebar) || !$hideSidebar;
  $rn = (string)(($routeName ?? null) ?: (function_exists('current_route_name') ? current_route_name() : null) ?: '');
  $isKdsPage  = in_array($rn, ['kds.index', 'kds.data', 'kds.status'], true);
  $isAuthPage = in_array($rn, ['admin.auth.login', 'admin.auth.logout'], true);
  $showSidebar = $showSidebar && !$isKdsPage && !$isAuthPage;
  ?>

  <?php if ($showSidebar): ?>
  <?php
  $topbarCompanyName = trim((string)($companyData['name'] ?? 'Loja'));
  $topbarLogoUrl = base_url('assets/icons/admin/logo-multimenu.png');
  ?>

  <header class="admin-topbar fixed inset-x-0 top-0 z-50 h-12 px-3" style="background:#efeff0;border-bottom:none;">
    <div class="mx-auto flex h-full items-center justify-between" style="max-width:1800px;">
      <div class="flex items-center gap-2">
        <button
          id="topbar-sidebar-toggle"
          type="button"
          class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-600 transition-colors hover:bg-zinc-200"
          aria-label="Alternar menu lateral"
          aria-controls="smart-sidebar"
          aria-expanded="true"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
          </svg>
        </button>

        <div class="inline-flex items-center gap-1.5 rounded-full bg-white px-2 py-1 ring-1 ring-zinc-200">
          <div class="h-4 w-4 overflow-hidden rounded-full flex items-center justify-center shrink-0">
            <img src="<?= e($topbarLogoUrl) ?>" alt="Logo da loja" class="h-4 w-4 object-contain" loading="lazy" />
          </div>
          <span class="text-sm font-semibold text-zinc-700 max-w-[140px] truncate"><?= e($topbarCompanyName) ?></span>
        </div>

        <div class="inline-flex items-center gap-1.5 rounded-full bg-white px-2 py-1 ring-1 ring-zinc-200">
          <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white">●</span>
          <span class="text-sm font-semibold text-zinc-700">iFood</span>
        </div>
      </div>

      <div class="flex items-center gap-2 text-zinc-600">
        <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md hover:bg-zinc-200" aria-label="Ajuda">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M9.75 9a2.25 2.25 0 114.15 1.2c-.33.6-.85 1.02-1.37 1.42-.5.38-.98.77-1.2 1.28M12 16h.01" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </button>
        <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md hover:bg-zinc-200" aria-label="Notificações">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M15 17h5l-1.4-1.4a2 2 0 01-.6-1.4V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </button>
      </div>
    </div>
  </header>
  <?php endif; ?>
  
  <?php
    if ($showSidebar):
      try {
        $resolvedRouteName = isset($routeName) && is_string($routeName) && trim($routeName) !== ''
          ? trim($routeName)
          : (function_exists('current_route_name') ? (current_route_name() ?? null) : null);

        $sidebarData = \App\Services\SidebarService::build(
          is_array($companyData) ? $companyData : [],
          '',
          $resolvedRouteName,
          (string)($companySlug ?? '')
        );

        include __DIR__ . '/components/sidebar.php';
      } catch (\Throwable $e) {
        $sidebarContext = [
          'message' => $e->getMessage(),
          'file' => $e->getFile(),
          'line' => $e->getLine(),
          'route_name' => function_exists('current_route_name') ? current_route_name() : null,
          'route_pattern' => function_exists('current_route_pattern') ? current_route_pattern() : null,
          'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
          'company_slug' => (string)($companySlug ?? ''),
        ];

        if (class_exists('Logger') && method_exists('Logger', 'error')) {
          Logger::error('Admin layout sidebar error', $e, $sidebarContext);
        } else {
          error_log('Admin layout sidebar error: ' . json_encode($sidebarContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $sidebarData = \App\Services\SidebarService::buildErrorFallback(
          trim((string)($companySlug ?? ''), '/'),
          trim((string)($company['name'] ?? '')) ?: 'Admin',
          'CRITICAL: Sidebar error — ' . mb_substr($e->getMessage(), 0, 120)
        );

        include __DIR__ . '/components/sidebar.php';
      }
  endif;
  ?>
  
  <?php if ($showSidebar): ?>
  <main id="admin-main-content" class="sidebar-main-content">
    <div id="admin-content-frame">
      <div id="admin-content-scroll">
        <div class="max-w-7xl mx-auto px-4 py-3 lg:px-6 lg:py-4 box-border">
          <?= $content ?? '' ?>
        </div>
      </div>
    </div>
  </main>
  <?php else: ?>
  <div class="admin-top-content">
    <div class="max-w-7xl mx-auto px-4 py-3 lg:px-6 lg:py-4 box-border">
      <?= $content ?? '' ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Sistemas centralizados -->
  <link rel="stylesheet" href="<?= base_url('assets/css/ui.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/skeleton.css') ?>">
  
  <!-- JavaScript comum do admin -->
  <script src="<?= base_url('assets/js/toast-system.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/skeleton-system.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/admin-csrf.min.js') ?>?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/admin-csrf.min.js') ?: time() ?>"></script>
  <script src="<?= base_url('assets/js/admin-common.min.js') ?>?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/admin-common.min.js') ?: time() ?>"></script>

  <?php if ($showNotifications ?? !$isKdsPage): ?>
    <?php include __DIR__ . '/components/order-notifications.php'; ?>
    <script src="<?= base_url('assets/js/kds-chime.min.js') ?>?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/kds-chime.min.js') ?: time() ?>"></script>
    <script src="<?= base_url('assets/js/order-notifications.min.js') ?>?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/order-notifications.min.js') ?: time() ?>"></script>
  <?php endif; ?>

  <script src="<?= base_url('assets/js/admin.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/lazy-loading.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/admin-pwa.min.js') ?>?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/admin-pwa.min.js') ?: time() ?>"></script>

  <style>
    .admin-top-content { padding-top: 3rem; }

    /* ── Fixed-frame layout (desktop) — matches StoreDashboardLayout (60 / 14 in Tailwind = 240px / 56px) ── */
    @media (min-width: 1024px) {
      #admin-main-content {
        position: fixed !important;
        top: 48px !important;
        bottom: 0 !important;
        right: 0 !important;
        left: 240px !important;
        overflow: hidden !important;
        background: #f5f5f5 !important;
        transition: left 0.3s ease-in-out;
        margin: 0 !important;
        padding: 0 !important;
      }
      #admin-main-content.collapsed { left: 56px !important; }
      .sidebar-preload-collapsed #admin-main-content {
        left: 56px !important;
        transition: none !important;
      }
      #admin-content-frame {
        position: absolute;
        inset: 0;
        border-radius: 28px 28px 0 0;
        background: #fff;
        border: 1px solid #e4e4e7;
        border-bottom: 0;
      }
      #admin-content-scroll {
        height: 100%;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
      }
    }

    /* ── Mobile: normal document flow ── */
    @media (max-width: 1023px) {
      #admin-main-content {
        display: block;
        margin-top: 48px;
      }
      #admin-content-frame {
        background: #fff;
        min-height: calc(100vh - 48px);
      }
    }
  </style>
</body>
</html>
