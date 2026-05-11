<!doctype html>
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
  // Verificar se deve exibir sidebar (não exibir em páginas full-width como KDS ou login)
  $showSidebar = !isset($hideSidebar) || !$hideSidebar;
  $rn = (string)(($routeName ?? null) ?: (function_exists('current_route_name') ? current_route_name() : null) ?: '');
  $isKdsPage  = in_array($rn, ['kds.index', 'kds.data', 'kds.status'], true);
  $isAuthPage = in_array($rn, ['admin.auth.login', 'admin.auth.logout'], true);
  $showSidebar = $showSidebar && !$isKdsPage && !$isAuthPage;
  
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
  
  <div class="<?= $showSidebar ? 'sidebar-main-content' : '' ?>">
    <div class="max-w-7xl mx-auto px-4 py-4 lg:px-6 lg:py-6 box-border">
      <?= $content ?? '' ?>
    </div>
  </div>

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
</body>
</html>
