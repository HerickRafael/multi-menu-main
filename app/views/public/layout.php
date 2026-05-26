<?php /* Tailwind via CDN */ ?>
<?php
$company = $company ?? [];
$slug = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$slug = trim($slug, '/');

$homeUrl = function_exists('base_url') ? base_url($slug !== '' ? $slug : '') : '#';
$cartUrl = function_exists('base_url') ? base_url(($slug !== '' ? $slug . '/' : '') . 'cart') : '#';
$profileUrl = function_exists('base_url') ? base_url(($slug !== '' ? $slug . '/' : '') . 'profile') : '#';

$cartItemCount = 0;
try {
    if (class_exists('CartStorage')) {
        $cartItems = CartStorage::instance()->getCart();

        if (is_array($cartItems)) {
            foreach ($cartItems as $entry) {
                $cartItemCount += max(0, (int)($entry['qty'] ?? 0));
            }
        }
    } elseif (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $entry) {
            $cartItemCount += max(0, (int)($entry['qty'] ?? 0));
        }
    }
} catch (Throwable $layoutCartEx) {
    $cartItemCount = 0;
}
$layoutCustomer = $_SESSION['customer'] ?? null;
$layoutIsLogged = !empty($layoutCustomer);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title><?= e($title ?? 'Cardápio') ?></title>
  <meta name="csrf-token" content="<?= \App\Middleware\CsrfProtection::generateToken() ?>">
  <script>window.__DEBUG = <?= !empty(config('debug')) ? 'true' : 'false' ?>;</script>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
  <meta name="description" content="<?= e(($company['name'] ?? 'Cardápio') . ' - Cardápio digital. Faça seu pedido online!') ?>">
  
  <?php
  // Preparar dados para Open Graph
  $ogTitle = e(($company['name'] ?? 'Cardápio') . ' - Cardápio');
  $ogDescription = e(($company['name'] ?? 'Cardápio') . ' - Cardápio digital. Faça seu pedido online!');
  $ogUrl = rtrim(base_url(), '/') . ($_SERVER['REQUEST_URI'] ?? '/');
  $ogImage = !empty($company['logo']) ? base_url($company['logo']) : base_url('assets/icons/icon-512x512.png');
  $ogSiteName = e($company['name'] ?? 'Cardápio Digital');
  ?>
  
  <!-- Open Graph Meta Tags (WhatsApp, Facebook, etc.) -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($ogUrl) ?>">
  <meta property="og:title" content="<?= $ogTitle ?>">
  <meta property="og:description" content="<?= $ogDescription ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <meta property="og:site_name" content="<?= $ogSiteName ?>">
  <meta property="og:locale" content="pt_BR">
  
  <!-- Twitter Card Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= $ogTitle ?>">
  <meta name="twitter:description" content="<?= $ogDescription ?>">
  <meta name="twitter:image" content="<?= e($ogImage) ?>">
  
  <!-- PWA Meta Tags -->
  <meta name="theme-color" content="<?= e($company['menu_header_bg_color'] ?? '#4361ee') ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="<?= e($company['name'] ?? 'Cardápio') ?>">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="format-detection" content="telephone=no">
  <meta name="msapplication-TileColor" content="<?= e($company['menu_header_bg_color'] ?? '#4361ee') ?>">
  
  <!-- PWA Manifest -->
  <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
  
  <!-- Icons -->
  <?php if (!empty($company['logo'])): ?>
  <link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
  <link rel="apple-touch-icon" href="<?= e(base_url($company['logo'])) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= e(base_url($company['logo'])) ?>">
  <?php else: ?>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/icons/icon-32x32.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/icons/icon-180x180.png') ?>">
  <?php endif; ?>
  
  <!-- Google Analytics 4 -->
  <?php if (!empty($company['ga_measurement_id']) && preg_match('/^G-[A-Z0-9]{6,12}$/i', $company['ga_measurement_id'])): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($company['ga_measurement_id']) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($company['ga_measurement_id']) ?>');</script>
  <?php endif; ?>

  <!-- Canonical URL -->
  <link rel="canonical" href="<?= e($ogUrl) ?>">

  <!-- JSON-LD Structured Data (Schema.org) -->
  <?php
  $jsonLd = [
      '@context' => 'https://schema.org',
      '@type' => 'Restaurant',
      'name' => $company['name'] ?? 'Cardápio',
      'url' => $ogUrl,
      'image' => $ogImage,
      'hasMenu' => ['@type' => 'Menu', 'url' => $ogUrl],
  ];
  if (!empty($company['whatsapp'])) {
      $jsonLd['telephone'] = '+55' . preg_replace('/[^0-9]/', '', $company['whatsapp']);
  }
  if (!empty($company['logo'])) {
      $jsonLd['logo'] = base_url($company['logo']);
  }
  if (!empty($company['address'])) {
      $jsonLd['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $company['address']];
  }
  ?>
  <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

  <!-- Preload crítico -->
  <link rel="preload" href="<?= base_url('assets/css/ui.css') ?>" as="style">
  <link rel="preload" href="<?= base_url('assets/css/layout.css') ?>" as="style">
  <link rel="preload" href="<?= base_url('js/lazy-load.min.js') ?>" as="script">
  
  <!-- CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/ui.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/layout.css') ?>">
</head>
<body class="bg-gray-50 text-gray-900">
  <div class="max-w-5xl mx-auto<?= !empty($showFooterMenu) ? ' p-4' : '' ?>">
    <?= $content ?? '' ?>
  </div>
  <?php if (!empty($showFooterMenu)): ?>
  <nav class="fixed bottom-0 left-0 right-0 bg-white border-t">
    <div class="max-w-5xl mx-auto flex justify-around py-2">
      <a href="<?= e($homeUrl) ?>" class="flex flex-col items-center text-black">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="w-6 h-6"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke-linecap="round" stroke-linejoin="round"/><polyline points="9 22 9 12 15 12 15 22" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="text-xs">Home</span>
      </a>
      <a href="<?= e($cartUrl) ?>" id="btn-cart" class="relative flex flex-col items-center text-gray-500">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="w-6 h-6"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?php if ($layoutIsLogged && $cartItemCount > 0): ?>
          <span class="absolute -top-1.5 right-2 inline-flex min-w-[18px] min-h-[18px] px-1.5 items-center justify-center rounded-full bg-red-500 text-white text-[11px] font-semibold leading-none">
            <?= e($cartItemCount) ?>
          </span>
        <?php endif; ?>
        <span class="text-xs">Sacola</span>
      </a>
      <a href="<?= e($profileUrl) ?>" id="btn-profile" class="flex flex-col items-center text-gray-500">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="w-6 h-6"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="text-xs">Perfil</span>
      </a>
    </div>
    <div style="height: env(safe-area-inset-bottom, 0px);"></div>
  </nav>
  <?php endif; ?>
  
  <!-- Modal de Login (compartilhado via partial) -->
  <?php
  $requireLogin = function_exists('config') && (bool)config('login_required');
  $customer = $layoutCustomer;
  $isLogged = $layoutIsLogged;
  $forceLoginModal = $forceLoginModal ?? false;
  $loginModalPath = __DIR__ . '/partials/login_modal.php';
  if (file_exists($loginModalPath)) { include $loginModalPath; }
  ?>
  
  <script>
    // expose customer state for client-side handlers
    window.__IS_CUSTOMER = <?= !empty($_SESSION['customer']) ? 'true' : 'false' ?>;
    
    // Detectar troca de usuário e limpar sessionStorage
    (function() {
      const currentUserPhone = '<?= e($_SESSION['customer']['whatsapp'] ?? '') ?>';
      const lastUserPhone = sessionStorage.getItem('lastUserPhone');
      
      if (lastUserPhone && currentUserPhone && lastUserPhone !== currentUserPhone) {
        // Usuário mudou - limpar sessionStorage relacionado ao checkout
        sessionStorage.removeItem('couponCode');
        sessionStorage.removeItem('couponDiscount');
        sessionStorage.removeItem('couponSyncAttempted');
        sessionStorage.removeItem('checkoutFormData');
        sessionStorage.removeItem('orderSummary');
        console.log('SessionStorage limpo: usuário diferente detectado');
      }
      
      // Atualizar o telefone do usuário atual
      if (currentUserPhone) {
        sessionStorage.setItem('lastUserPhone', currentUserPhone);
      } else if (!currentUserPhone && lastUserPhone) {
        // Usuário fez logout - limpar tudo
        sessionStorage.clear();
      }
    })();
  </script>
  <script src="<?= base_url('assets/js/ui.min.js') ?>"></script>
  <script src="<?= base_url('js/lazy-load.min.js') ?>"></script>
  <script src="<?= base_url('js/mobile-optimizations.min.js') ?>" defer></script>
  <script src="<?= base_url('js/image-preload.min.js') ?>" defer></script>
  <script src="<?= base_url('js/promo-countdown.min.js') ?>" defer></script>
  
  <!-- Service Worker Registration -->
  <script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('<?= base_url('sw.js') ?>')
        .then(reg => {
          console.log('✓ Service Worker registered:', reg.scope);
        })
        .catch(err => console.error('✗ SW registration failed:', err));
    });
  }
  
  // Initialize Image Preloader
  document.addEventListener('DOMContentLoaded', () => {
    if (typeof ImagePreloader !== 'undefined') {
      const preloader = new ImagePreloader();
      preloader.preloadCriticalImages();
      preloader.setupHoverPrefetch();
      preloader.setupScrollPrefetch();
      preloader.setupLinkPrediction();
    }
    
    if (typeof PerformanceMonitor !== 'undefined') {
      window.performanceMonitor = new PerformanceMonitor();
      window.performanceMonitor.startMonitoring();
    }
  });
  </script>
  <script>
  (function() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) return;
    var token = meta.getAttribute('content');
    if (!token) return;
    function injectCsrf() {
      document.querySelectorAll('form').forEach(function(form) {
        if ((form.getAttribute('method') || '').toUpperCase() === 'POST' && !form.querySelector('input[name="csrf_token"]')) {
          var inp = document.createElement('input');
          inp.type = 'hidden'; inp.name = 'csrf_token'; inp.value = token;
          form.appendChild(inp);
        }
      });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', injectCsrf); } else { injectCsrf(); }
    // Expõe token para chamadas fetch/axios
    window._csrfToken = token;
    // Interceptar fetch para injetar CSRF em POSTs
    if (window.fetch) {
      var origFetch = window.fetch;
      window.fetch = function(url, opts) {
        opts = opts || {};
        if (opts.method && opts.method.toUpperCase() === 'POST') {
          if (opts.headers instanceof Headers) {
            if (!opts.headers.has('X-CSRF-TOKEN')) opts.headers.set('X-CSRF-TOKEN', token);
          } else {
            opts.headers = opts.headers || {};
            if (!opts.headers['X-CSRF-TOKEN']) opts.headers['X-CSRF-TOKEN'] = token;
          }
        }
        return origFetch.call(this, url, opts);
      };
    }
  })();
  </script>
</body>
</html>
