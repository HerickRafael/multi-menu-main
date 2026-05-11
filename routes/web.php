<?php
// routes/web.php

/* ========= MOBILE ADMIN ROUTES (m.wollburger.online) ========= */
if (defined('IS_MOBILE_SUBDOMAIN') && IS_MOBILE_SUBDOMAIN) {
    $mobileSlug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

    // Rota raiz → Dashboard
    $router->get('/', function() {
        header('Location: /dashboard');
        exit;
    });

    // Auth
    $router->get('/login',                           'MobileAdminAuthController@loginForm');
    $router->post('/login',                          'MobileAdminAuthController@login');
    $router->get('/logout',                          'MobileAdminAuthController@logout');

    // Dashboard
    $router->get('/dashboard',                       'MobileAdminDashboardController@index');

    // PWA Manifest
    $router->get('/manifest.webmanifest',            'MobileAdminDashboardController@manifest');

    // Pedidos (core do mobile)
    $router->get('/orders',                          'MobileAdminOrdersController@index');
    $router->get('/orders/show',                     'MobileAdminOrdersController@show');
    $router->get('/orders/create',                   'MobileAdminOrdersController@create');
    $router->post('/orders',                         'MobileAdminOrdersController@store');
    $router->get('/orders/{id:\\d+}/edit',                'MobileAdminOrdersController@edit');
    $router->post('/orders/{id:\\d+}',                    'MobileAdminOrdersController@update');
    $router->post('/orders/setStatus',               'MobileAdminOrdersController@setStatus');
    $router->get('/orders/print',                        'MobileAdminOrdersController@printPdf');
    $router->post('/orders/{id:\\d+}/del',                'MobileAdminOrdersController@destroy');

    // Produtos CRUD completo
    $router->get('/products',                        'MobileAdminProductController@index');
    $router->get('/products/create',                 'MobileAdminProductController@create');
    $router->post('/products',                       'MobileAdminProductController@store');
    $router->get('/products/{id}',                   'MobileAdminProductController@show');
    $router->get('/products/{id}/edit',              'MobileAdminProductController@edit');
    $router->post('/products/{id}',                  'MobileAdminProductController@update');
    $router->post('/products/{id}/toggle',           'MobileAdminProductController@toggle');
    $router->post('/products/{id}/delete',           'MobileAdminProductController@destroy');

    // Categorias CRUD completo
    $router->get('/categories',                      'MobileAdminCategoryController@index');
    $router->get('/categories/create',               'MobileAdminCategoryController@create');
    $router->post('/categories',                     'MobileAdminCategoryController@store');
    $router->get('/categories/{id}/edit',            'MobileAdminCategoryController@edit');
    $router->post('/categories/{id}',                'MobileAdminCategoryController@update');
    $router->post('/categories/{id}/toggle',         'MobileAdminCategoryController@toggle');
    $router->post('/categories/{id}/delete',         'MobileAdminCategoryController@destroy');

    // Clientes CRUD completo
    $router->get('/customers',                       'MobileAdminCustomerController@index');
    $router->get('/customers/create',                'MobileAdminCustomerController@create');
    $router->post('/customers',                      'MobileAdminCustomerController@store');
    $router->get('/customers/{id}',                  'MobileAdminCustomerController@show');
    $router->get('/customers/{id}/edit',             'MobileAdminCustomerController@edit');
    $router->post('/customers/{id}',                 'MobileAdminCustomerController@update');
    $router->post('/customers/{id}/delete',          'MobileAdminCustomerController@destroy');

    // Configurações
    $router->get('/settings',                        'MobileAdminSettingsController@index');
    $router->get('/settings/store',                  'MobileAdminSettingsController@store');
    $router->post('/settings/store',                 'MobileAdminSettingsController@updateStore');
    $router->get('/settings/hours',                  'MobileAdminSettingsController@hours');
    $router->post('/settings/hours',                 'MobileAdminSettingsController@updateHours');
    $router->get('/settings/delivery',               'MobileAdminSettingsController@delivery');
    $router->post('/settings/delivery/city',         'MobileAdminSettingsController@saveCity');
    $router->post('/settings/delivery/city/{id}/delete', 'MobileAdminSettingsController@deleteCity');
    $router->post('/settings/delivery/zone',         'MobileAdminSettingsController@saveZone');
    $router->post('/settings/delivery/zone/{id}/delete', 'MobileAdminSettingsController@deleteZone');
    $router->post('/settings/delivery/adjust',       'MobileAdminSettingsController@adjustFees');
    $router->post('/settings/delivery/options',       'MobileAdminSettingsController@updateDeliveryOptions');
    $router->post('/settings/delivery/free-shipping', 'MobileAdminSettingsController@updateFreeShipping');
    $router->get('/settings/payments',               'MobileAdminSettingsController@payments');
    $router->post('/settings/payments/save',         'MobileAdminSettingsController@savePayment');
    $router->post('/settings/payments/{id}/toggle',  'MobileAdminSettingsController@togglePayment');
    $router->post('/settings/payments/{id}/delete',  'MobileAdminSettingsController@deletePayment');
    $router->get('/settings/whatsapp',               'MobileAdminSettingsController@whatsapp');
    $router->get('/settings/whatsapp/instances',     'MobileAdminSettingsController@whatsappInstances');
    $router->get('/settings/whatsapp/instance/{name}', 'MobileAdminSettingsController@whatsappInstance');
    $router->post('/settings/whatsapp/instance/{name}/settings', 'MobileAdminSettingsController@whatsappInstanceSettings');
    $router->post('/settings/whatsapp/sync',         'MobileAdminSettingsController@whatsappSync');
    $router->post('/settings/whatsapp/create',       'MobileAdminSettingsController@whatsappCreate');
    $router->post('/settings/whatsapp/{name}/qrcode','MobileAdminSettingsController@whatsappQrcode');
    $router->post('/settings/whatsapp/{name}/disconnect','MobileAdminSettingsController@whatsappDisconnect');
    $router->post('/settings/whatsapp/{name}/delete','MobileAdminSettingsController@whatsappDelete');
    $router->post('/settings/whatsapp/{name}/restart','MobileAdminSettingsController@whatsappRestart');
    // Configurações avançadas da instância (Evolution API Settings)
    $router->get('/settings/whatsapp/{name}/api-settings', 'MobileAdminSettingsController@whatsappApiSettings');
    $router->post('/settings/whatsapp/{name}/api-settings', 'MobileAdminSettingsController@whatsappSaveApiSettings');
    // Notificação de pedido
    $router->get('/settings/whatsapp/{name}/order-notification', 'MobileAdminSettingsController@whatsappOrderNotification');
    $router->post('/settings/whatsapp/{name}/order-notification', 'MobileAdminSettingsController@whatsappSaveOrderNotification');
    $router->post('/settings/whatsapp/{name}/validate-number', 'MobileAdminSettingsController@whatsappValidateNumber');
    // Engajamento de clientes
    $router->get('/settings/whatsapp/{name}/engagement', 'MobileAdminSettingsController@whatsappEngagement');
    $router->post('/settings/whatsapp/{name}/engagement', 'MobileAdminSettingsController@whatsappSaveEngagement');
    // Fora do expediente (independente)
    $router->get('/settings/whatsapp/{name}/out-of-hours', 'MobileAdminSettingsController@whatsappOutOfHours');
    $router->post('/settings/whatsapp/{name}/out-of-hours', 'MobileAdminSettingsController@whatsappSaveOutOfHours');
    // Pausa programada
    $router->get('/settings/whatsapp/{name}/scheduled-pause', 'MobileAdminSettingsController@whatsappScheduledPause');
    $router->post('/settings/whatsapp/{name}/scheduled-pause', 'MobileAdminSettingsController@whatsappSaveScheduledPause');
    $router->get('/settings/profile',                'MobileAdminSettingsController@profile');
    $router->post('/settings/profile',               'MobileAdminSettingsController@updateProfile');

    // Fidelidade (Mobile)
    $router->get('/settings/loyalty',                'MobileAdminSettingsController@loyalty');
    $router->post('/settings/loyalty',               'MobileAdminSettingsController@saveLoyalty');
    $router->post('/settings/loyalty/create-coupon', 'MobileAdminSettingsController@createLoyaltyCoupon');
    $router->post('/settings/loyalty/coupon/{id}/delete', 'MobileAdminSettingsController@deleteLoyaltyCoupon');

    // API Management (Mobile)
    $router->get('/settings/api',                    'MobileAdminSettingsController@apiManagement');
    $router->post('/settings/api/generate-token',    'MobileAdminSettingsController@apiGenerateToken');
    $router->post('/settings/api/revoke-token',      'MobileAdminSettingsController@apiRevokeToken');
    $router->post('/settings/api/generate-key',      'MobileAdminSettingsController@apiGenerateKey');
    $router->post('/settings/api/revoke-key',        'MobileAdminSettingsController@apiRevokeKey');

    // API endpoints mobile (para AJAX)
    $router->get('/api/orders',                      'MobileApiController@getOrders');
    $router->get('/api/orders/{id}',                 'MobileApiController@getOrder');
    $router->post('/api/orders/{id}/status',         'MobileApiController@updateOrderStatus');
    $router->get('/api/stats',                       'MobileApiController@getStats');
    $router->get('/api/customers/search',            'MobileApiController@searchCustomerByPhone');

    // Street Autocomplete (Mobile)
    $router->get('/api/street-autocomplete',         'MobileApiController@streetSearch');
    $router->post('/api/street-autocomplete/popularity', 'MobileApiController@streetPopularity');
    $router->post('/api/street-autocomplete/learn',  'MobileApiController@streetLearn');

    // Pausa Programada (Mobile)
    $router->get('/pause/status',                    'MobileAdminPauseController@status');
    $router->post('/pause/enable',                   'MobileAdminPauseController@enable');
    $router->post('/pause/disable',                  'MobileAdminPauseController@disable');
    $router->post('/pause/extend',                   'MobileAdminPauseController@extend');

    // Cupons (Mobile)
    $router->get('/coupons',                         'MobileAdminCouponsController@index');
    $router->get('/coupons/history',                 'MobileAdminCouponsController@history');
    $router->get('/coupons/create',                  'MobileAdminCouponsController@create');
    $router->post('/coupons',                        'MobileAdminCouponsController@store');
    $router->get('/coupons/{id}/edit',               'MobileAdminCouponsController@edit');
    $router->post('/coupons/{id}',                   'MobileAdminCouponsController@update');
    $router->post('/coupons/{id}/delete',            'MobileAdminCouponsController@delete');
    $router->post('/coupons/{id}/toggle',            'MobileAdminCouponsController@toggle');

    // KDS (Mobile)
    $router->get('/kds',                             'MobileAdminKdsController@index');
    $router->get('/kds/data',                        'MobileAdminKdsController@data');
    $router->post('/kds/status',                     'MobileAdminKdsController@status');

    // iFood (Mobile)
    $router->get('/ifood/config',                    'MobileAdminIFoodController@config');
    $router->post('/ifood/config/save',              'MobileAdminIFoodController@saveConfig');
    $router->get('/ifood/orders',                    'MobileAdminIFoodController@orders');
    $router->get('/ifood/orders/{id}',               'MobileAdminIFoodController@viewOrder');
    $router->post('/ifood/orders/{id}/confirm',      'MobileAdminIFoodController@confirmOrder');
    $router->post('/ifood/orders/{id}/ready',        'MobileAdminIFoodController@readyOrder');
    $router->post('/ifood/orders/{id}/dispatch',     'MobileAdminIFoodController@dispatchOrder');
    $router->post('/ifood/orders/{id}/cancel',       'MobileAdminIFoodController@cancelOrder');
    $router->get('/ifood/orders/{id}/cancel-reasons','MobileAdminIFoodController@getCancellationReasons');
    $router->post('/ifood/poll',                     'MobileAdminIFoodController@poll');
    $router->post('/ifood/test-connection',          'MobileAdminIFoodController@testConnection');
    $router->post('/ifood/clear-error',              'MobileAdminIFoodController@clearError');
    $router->get('/ifood/status',                    'MobileAdminIFoodController@status');

    // Custos de Produtos (Mobile)
    $router->get('/product-costs',                        'MobileAdminProductCostController@index');
    $router->get('/product-costs/{id}/edit',              'MobileAdminProductCostController@edit');
    $router->post('/product-costs/{id}/update',           'MobileAdminProductCostController@update');
    $router->post('/product-costs/{id}/update-packaging', 'MobileAdminProductCostController@updatePackaging');
    $router->post('/product-costs/bulk-update',           'MobileAdminProductCostController@bulkUpdate');
    $router->get('/product-costs/{id}/calculate',         'MobileAdminProductCostController@calculate');

    // Insumos & Embalagens (Mobile)
    $router->get('/packaging',              'MobileAdminPackagingController@index');
    $router->get('/packaging/create',       'MobileAdminPackagingController@create');
    $router->get('/packaging/{id}/edit',    'MobileAdminPackagingController@edit');
    $router->post('/packaging/store',       'MobileAdminPackagingController@store');
    $router->post('/packaging/{id}/delete', 'MobileAdminPackagingController@delete');

    // Analytics (Mobile)
    $router->get('/analytics',                       'MobileAdminAnalyticsController@index');

    // Financeiro (Mobile)
    $router->get('/financial',                       'MobileAdminFinancialController@dashboard');
    $router->get('/financial/monthly',               'MobileAdminFinancialController@monthly');
    $router->get('/financial/yearly',                'MobileAdminFinancialController@yearly');
    $router->get('/financial/settings',              'MobileAdminFinancialController@settings');
    $router->post('/financial/settings',             'MobileAdminFinancialController@saveSettings');
    $router->post('/financial/recalculate',          'MobileAdminFinancialController@recalculateCosts');

    // Despesas (Mobile)
    $router->get('/expenses',                        'MobileAdminFinancialController@expenses');
    $router->get('/expenses/create',                 'MobileAdminFinancialController@expenseCreate');
    $router->post('/expenses',                       'MobileAdminFinancialController@expenseStore');
    $router->get('/expenses/{id}/edit',              'MobileAdminFinancialController@expenseEdit');
    $router->post('/expenses/{id}',                  'MobileAdminFinancialController@expenseUpdate');
    $router->post('/expenses/{id}/delete',           'MobileAdminFinancialController@expenseDelete');
    $router->get('/expenses/categories',             'MobileAdminFinancialController@expenseCategories');
    $router->post('/expenses/categories',            'MobileAdminFinancialController@expenseCategoryStore');
    $router->post('/expenses/categories/{id}/delete','MobileAdminFinancialController@expenseCategoryDelete');
    $router->get('/expenses/categories/seed',        'MobileAdminFinancialController@expenseCategorySeed');

    // Guia (Mobile)
    $router->get('/guide/products',                  'MobileAdminGuideController@products');
    $router->get('/guide/ingredients',               'MobileAdminGuideController@ingredients');
    $router->get('/guide/coupons',                   'MobileAdminGuideController@coupons');
    $router->get('/guide/cross-sell',                 'MobileAdminGuideController@crossSell');
    $router->get('/guide/payment-methods',            'MobileAdminGuideController@paymentMethods');
    $router->get('/guide/delivery-fees',              'MobileAdminGuideController@deliveryFees');
    $router->get('/guide/loyalty-discount',            'MobileAdminGuideController@loyaltyDiscount');
    $router->get('/guide/financial',                   'MobileAdminGuideController@financial');
    $router->get('/guide/customization-templates',     'MobileAdminGuideController@customizationTemplates');
    $router->get('/guide/company-settings',             'MobileAdminGuideController@companySettings');
    $router->get('/guide/manual-order',                  'MobileAdminGuideController@manualOrder');
    $router->get('/guide/whatsapp',                      'MobileAdminGuideController@whatsapp');
    $router->get('/guide/ifood',                          'MobileAdminGuideController@ifood');

    // Ingredientes (Mobile)
    $router->get('/ingredients',                     'MobileAdminIngredientController@index');
    $router->get('/ingredients/create',              'MobileAdminIngredientController@create');
    $router->post('/ingredients',                    'MobileAdminIngredientController@store');
    $router->get('/ingredients/{id}/edit',           'MobileAdminIngredientController@edit');
    $router->post('/ingredients/{id}',               'MobileAdminIngredientController@update');
    $router->post('/ingredients/{id}/toggle',        'MobileAdminIngredientController@toggle');
    $router->post('/ingredients/{id}/delete',        'MobileAdminIngredientController@delete');

    // Grupos de Personalização (Mobile)
    $router->get('/customization-templates',                        'MobileAdminCustomizationTemplateController@index');
    $router->get('/customization-templates/create',                 'MobileAdminCustomizationTemplateController@create');
    $router->post('/customization-templates',                       'MobileAdminCustomizationTemplateController@store');
    $router->get('/customization-templates/{id}/edit',              'MobileAdminCustomizationTemplateController@edit');
    $router->post('/customization-templates/{id}',                  'MobileAdminCustomizationTemplateController@store');
    $router->post('/customization-templates/{id}/delete',           'MobileAdminCustomizationTemplateController@delete');
    $router->post('/customization-templates/{id}/toggle',           'MobileAdminCustomizationTemplateController@toggle');
    $router->get('/customization-templates/api/list',               'MobileAdminCustomizationTemplateController@apiList');
    $router->get('/customization-templates/api/{id}',               'MobileAdminCustomizationTemplateController@apiGet');
    $router->post('/customization-templates/api/create-from-group', 'MobileAdminCustomizationTemplateController@apiCreateFromGroup');
    $router->post('/customization-templates/api/copy-to-product',   'MobileAdminCustomizationTemplateController@apiCopyToProduct');

    // Cross-Sell (Mobile)
    $router->get('/cross-sell',                      'MobileAdminCrossSellController@index');
    $router->post('/cross-sell/save',                'MobileAdminCrossSellController@save');
    $router->get('/cross-sell/{id}/edit',            'MobileAdminCrossSellController@edit');
    $router->post('/cross-sell/{id}/toggle',         'MobileAdminCrossSellController@toggle');
    $router->post('/cross-sell/{id}/delete',         'MobileAdminCrossSellController@delete');

    // Web Push Notifications (Mobile) - usar rota simplificada para mobile
    $router->get('/push/vapid-key',                  'AdminPushController@getVapidKey');
    $router->post('/push/subscribe',                 'AdminPushController@subscribe');
    $router->post('/push/unsubscribe',               'AdminPushController@unsubscribe');
    $router->post('/push/test',                      'AdminPushController@test');
    $router->get('/push/status',                     'AdminPushController@status');

    // Fallback no subdomínio mobile: qualquer rota fora do painel mobile
    // deve ir para o domínio desktop (ex.: links públicos de produto/cardápio).
    $router->get('/{path:.*}', function() {
        $desktopUrl = SubdomainDetector::getDesktopUrl();
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . rtrim($desktopUrl, '/') . $requestUri, true, 302);
        exit;
    });

    // Fim das rotas mobile - não processa rotas desktop
    goto end_routes;
}

/* ========= Rota raiz ========= */
$router->get('/', function() {
    header('Location: /wollburger');
    exit;
});

/* ========= Landing page de vendas ========= */
$router->get('/vendas', 'App\\Controllers\\Public\\LandingController@index');

/* ========= SEO: robots.txt dinâmico (multi-tenant) ========= */
$router->get('/robots.txt', function() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $baseUrl = ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /superadmin\n";
    echo "Disallow: /api/\n";
    echo "Disallow: /*/cart\n";
    echo "Disallow: /*/checkout\n";
    echo "Disallow: /*/profile\n";
    echo "Disallow: /*/reorder/\n";
    echo "\n";
    echo "Sitemap: {$baseUrl}/sitemap.xml\n";
    exit;
});

/* ========= SEO: Sitemap XML dinâmico ========= */
$router->get('/sitemap.xml', function() {
    $db = Database::getInstance();
    $companies = $db->query("SELECT slug FROM companies ORDER BY slug")->fetchAll(\PDO::FETCH_ASSOC);

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $baseUrl = ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $today = date('Y-m-d');

    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($companies as $c) {
        $loc = htmlspecialchars($baseUrl . '/' . rawurlencode($c['slug']), ENT_XML1, 'UTF-8');
        echo "  <url><loc>{$loc}</loc><lastmod>{$today}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>\n";
    }
    echo '</urlset>';
    exit;
});

/* ========= Super Admin (painel global — sem slug de loja no path) ========= */
$router->get('/superadmin/login', 'SuperAdminAuthController@showLogin')->name('superadmin.auth.login.form');
$router->post('/superadmin/login', 'SuperAdminAuthController@login')->name('superadmin.auth.login.submit');
$router->post('/superadmin/logout', 'SuperAdminAuthController@logout')->name('superadmin.auth.logout');

$router->get('/superadmin', 'SuperAdminController@dashboard')->name('superadmin.dashboard');
$router->get('/superadmin/catalog', 'SuperAdminController@catalog')->name('superadmin.catalog');
$router->get('/superadmin/orders-live', 'SuperAdminController@ordersLive')->name('superadmin.orders_live');
$router->get('/superadmin/operators', 'SuperAdminController@operators')->name('superadmin.operators');
$router->get('/superadmin/companies/create', 'SuperAdminController@create')->name('superadmin.companies_create');
$router->post('/superadmin/companies', 'SuperAdminController@store')->name('superadmin.companies.store');
$router->post('/superadmin/companies/{id}/toggle', 'SuperAdminController@toggle')->name('superadmin.companies.toggle');
$router->get('/superadmin/companies/{id}', 'SuperAdminController@edit')->name('superadmin.companies');
$router->post('/superadmin/companies/{id}', 'SuperAdminController@update')->name('superadmin.companies.update');

/* ========= FASE 1: Dashboard Real-time + Gestão de Lojas ========= */
$router->get('/superadmin/dashboard-home', 'SuperAdminController@dashboardHome')->name('superadmin.dashboard_home');

// Gestão de Lojas (Stores)
$router->get('/superadmin/stores', 'SuperAdminController@storesIndex')->name('superadmin.stores.index');
$router->get('/superadmin/stores/{id:\\d+}', 'SuperAdminController@storesShow')->name('superadmin.stores.show');
$router->get('/superadmin/stores/{id:\\d+}/edit', 'SuperAdminController@storesEditForm')->name('superadmin.stores.edit_form');
$router->post('/superadmin/stores/{id:\\d+}/update', 'SuperAdminController@storesUpdate')->name('superadmin.stores.update');
$router->post('/superadmin/stores/{id:\\d+}/suspend', 'SuperAdminController@storesSuspend')->name('superadmin.stores.suspend');
$router->post('/superadmin/stores/{id:\\d+}/activate', 'SuperAdminController@storesActivate')->name('superadmin.stores.activate');
$router->post('/superadmin/stores/{id:\\d+}/maintenance', 'SuperAdminController@storesMaintenance')->name('superadmin.stores.maintenance');

/* ========= FASE 2: Auditoria + Impersonate ========= */
// Audit Logs
$router->get('/superadmin/audit-logs', 'SuperAdminController@auditLogs')->name('superadmin.audit_logs');

// Impersonation (Entrar como Loja)
$router->get('/superadmin/impersonate/{id:\\d+}', 'SuperAdminController@impersonateForm')->name('superadmin.impersonate_form');
$router->post('/superadmin/impersonate/{id:\\d+}/start', 'SuperAdminController@impersonateStart')->name('superadmin.impersonate_start');
$router->post('/superadmin/impersonate/end', 'SuperAdminController@impersonateEnd')->name('superadmin.impersonate_end');
$router->get('/superadmin/impersonations', 'SuperAdminController@impersonationHistory')->name('superadmin.impersonation_history');

/* ========= FASE 3: Pedidos + Logs Centralizados ========= */
$router->get('/superadmin/orders-monitor', 'SuperAdminController@ordersMonitor')->name('superadmin.orders_monitor');
$router->get('/superadmin/orders/{id:\\d+}/timeline', 'SuperAdminController@orderTimeline')->name('superadmin.orders.timeline');
$router->get('/superadmin/system-logs', 'SuperAdminController@systemLogs')->name('superadmin.system_logs');
$router->post('/superadmin/system-logs/ingest', 'SuperAdminController@systemLogsIngest')->name('superadmin.system_logs.ingest');
$router->get('/superadmin/system-logs/export', 'SuperAdminController@systemLogsExport')->name('superadmin.system_logs.export');

/* ========= FASE 4: Webhooks + Filas ========= */
$router->get('/superadmin/webhooks', 'SuperAdminController@webhooks')->name('superadmin.webhooks.index');
$router->post('/superadmin/webhooks/{id:\\d+}/retry', 'SuperAdminController@webhooksRetry')->name('superadmin.webhooks.retry');
$router->get('/superadmin/queues', 'SuperAdminController@queues')->name('superadmin.queues.index');
$router->post('/superadmin/queues/{id:\\d+}/retry', 'SuperAdminController@queuesRetry')->name('superadmin.queues.retry');

/* ========= FASE 5: WhatsApp Monitoring + Feature Flags + RBAC ========= */
$router->get('/superadmin/whatsapp-monitor', 'SuperAdminController@whatsappMonitor')->name('superadmin.whatsapp.monitor');
$router->get('/superadmin/feature-flags', 'SuperAdminController@featureFlags')->name('superadmin.feature_flags.index');
$router->post('/superadmin/feature-flags/toggle', 'SuperAdminController@featureFlagsToggle')->name('superadmin.feature_flags.toggle');
$router->get('/superadmin/rbac', 'SuperAdminController@rbacIndex')->name('superadmin.rbac.index');
$router->post('/superadmin/rbac/assign-role', 'SuperAdminController@rbacAssignRole')->name('superadmin.rbac.assign_role');

/* ========= FASE 6: Observabilidade + Seguranca Multi-tenant + Eventos ========= */
$router->get('/superadmin/observability', 'SuperAdminController@observabilityDashboard')->name('superadmin.observability.index');
$router->post('/superadmin/observability/run-checks', 'SuperAdminController@observabilityRunChecks')->name('superadmin.observability.run_checks');
$router->get('/superadmin/events', 'SuperAdminController@eventsIndex')->name('superadmin.events.index');
$router->post('/superadmin/events/dispatch-test', 'SuperAdminController@eventsDispatchTest')->name('superadmin.events.dispatch_test');

$router->post('/superadmin/api/chat', 'SuperAdminApiController@chat')->name('superadmin.api.chat');

/* ========= Super Admin JSON API (React Dashboard) ========= */
$router->post('/api/superadmin/auth', 'SuperAdminDashboardApiController@auth')->name('api.superadmin.auth');
$router->post('/api/superadmin/logout', 'SuperAdminDashboardApiController@logout')->name('api.superadmin.logout');
$router->post('/api/superadmin/tenant-context/switch', 'SuperAdminDashboardApiController@switchTenantContext')->name('api.superadmin.tenant_context.switch');
$router->get('/api/superadmin/me', 'SuperAdminDashboardApiController@me')->name('api.superadmin.me');
$router->get('/api/superadmin/dashboard', 'SuperAdminDashboardApiController@dashboard')->name('api.superadmin.dashboard');
$router->get('/api/superadmin/stores', 'SuperAdminDashboardApiController@stores')->name('api.superadmin.stores');
$router->get('/api/superadmin/orders', 'SuperAdminDashboardApiController@orders')->name('api.superadmin.orders');
$router->get('/api/superadmin/users', 'SuperAdminDashboardApiController@users')->name('api.superadmin.users');
$router->get('/api/superadmin/queues', 'SuperAdminDashboardApiController@queues')->name('api.superadmin.queues');

$router->get('/api/superadmin/monitoring', 'SuperAdminDashboardApiController@monitoring')->name('api.superadmin.monitoring');
$router->get('/api/superadmin/whatsapp', 'SuperAdminDashboardApiController@whatsapp')->name('api.superadmin.whatsapp');
$router->get('/api/superadmin/webhooks', 'SuperAdminDashboardApiController@webhooks')->name('api.superadmin.webhooks');
$router->get('/api/superadmin/logs', 'SuperAdminDashboardApiController@logs')->name('api.superadmin.logs');
$router->get('/api/superadmin/audit', 'SuperAdminDashboardApiController@audit')->name('api.superadmin.audit');
$router->get('/api/superadmin/permissions', 'SuperAdminDashboardApiController@permissions')->name('api.superadmin.permissions');
$router->post('/api/superadmin/permissions/assign-role', 'SuperAdminDashboardApiController@assignRole')->name('api.superadmin.permissions.assign_role');
$router->get('/api/superadmin/feature-flags', 'SuperAdminDashboardApiController@featureFlags')->name('api.superadmin.feature_flags');
$router->post('/api/superadmin/feature-flags/toggle', 'SuperAdminDashboardApiController@toggleFeatureFlag')->name('api.superadmin.feature_flags.toggle');
$router->get('/api/superadmin/analytics', 'SuperAdminDashboardApiController@analytics')->name('api.superadmin.analytics');
$router->get('/api/superadmin/settings', 'SuperAdminDashboardApiController@settings')->name('api.superadmin.settings');
$router->get('/api/superadmin/system', 'SuperAdminDashboardApiController@system')->name('api.superadmin.system');
$router->post('/api/superadmin/system/run-checks', 'SuperAdminDashboardApiController@runSystemChecks')->name('api.superadmin.system.run_checks');

/* ========= Imagens otimizadas (cache profissional) ========= */
$router->get('/img/{path:.*}', 'ImageController@serve');

/* ========= Rotas públicas (cardápio) ========= */
/* IMPORTANT: {slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}
   impede capture de palavras-chave reservadas */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}', 'App\\Controllers\\Public\\HomeController@index');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/buscar', 'App\\Controllers\\Public\\HomeController@buscar');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/politica-privacidade', 'App\\Controllers\\Public\\HomeController@privacyPolicy');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/produto/{id}', 'App\\Controllers\\Public\\ProductController@show');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/product/{id}', 'App\\Controllers\\Public\\ProductController@show');

/* Personalização de produto */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/produto/{id}/customizar', 'App\\Controllers\\Public\\ProductController@customize');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/produto/{id}/customizar', 'App\\Controllers\\Public\\ProductController@saveCustomization');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/produto/{id}/customizar/cancelar', 'App\\Controllers\\Public\\ProductController@cancelCustomization');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/check-customization', 'App\\Controllers\\Public\\ProductController@checkCustomization');

/* Carrinho */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/cart', 'App\\Controllers\\Public\\CartController@index');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/cart/add', 'App\\Controllers\\Public\\CartController@add');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/cart/update', 'App\\Controllers\\Public\\CartController@update');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/validate-coupon', 'App\\Controllers\\Public\\CartController@validateCoupon');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/sync-coupon', 'App\\Controllers\\Public\\CartController@syncCoupon');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout', 'App\\Controllers\\Public\\CartController@checkout');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout/processing', 'App\\Controllers\\Public\\CartController@processing')->name('checkout.processing');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout/processing', 'App\\Controllers\\Public\\CartController@confirmProcessing')->name('checkout.confirm_processing');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout/success', 'App\\Controllers\\Public\\CartController@checkoutSuccess');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout', 'App\\Controllers\\Public\\CartController@submitCheckout');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout/calculate', 'App\\Controllers\\Public\\CartController@calculateCheckout');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/reorder/{orderId}', 'App\\Controllers\\Public\\CartController@reorder');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/profile', 'App\\Controllers\\Public\\ProfileController@index');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/profile/update', 'App\\Controllers\\Public\\ProfileController@update');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/profile/export-data', 'App\\Controllers\\Public\\ProfileController@exportData');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/profile/request-deletion', 'App\\Controllers\\Public\\ProfileController@requestDeletion');

/* Pedidos */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/order/{id}', 'App\\Controllers\\Public\\OrderController@show');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/order/{id}/cancel', 'App\\Controllers\\Public\\OrderController@cancel');

/* Autocomplete de ruas - Enterprise (DB local + Redis + Overpass fallback) */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/street-autocomplete', 'App\\Controllers\\Public\\StreetAutocompleteController@search');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/street-autocomplete/popularity', 'App\\Controllers\\Public\\StreetAutocompleteController@popularity');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/street-autocomplete/learn', 'App\\Controllers\\Public\\StreetAutocompleteController@learn');

/* Endereços */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses', 'App\\Controllers\\Public\\AddressController@index');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/create', 'App\\Controllers\\Public\\AddressController@create');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/create', 'App\\Controllers\\Public\\AddressController@store');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/edit/{id}', 'App\\Controllers\\Public\\AddressController@edit');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/update', 'App\\Controllers\\Public\\AddressController@update');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/delete', 'App\\Controllers\\Public\\AddressController@delete');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/set-default', 'App\\Controllers\\Public\\AddressController@setDefault');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/update-label', 'App\\Controllers\\Public\\AddressController@updateLabel');

/* ========= Rotas cliente ========= */
// Rota de login (redireciona para home com modal de login)
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/login', function($params) {
    $slug = $params['slug'] ?? '';
    $expired = isset($_GET['expired']) ? '?expired=1' : '';
    $sessionExpired = isset($_GET['session_expired']) ? '?session_expired=1' : '';
    $query = $expired ?: $sessionExpired;
    header('Location: /' . rawurlencode($slug) . $query);
    exit;
});

$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/customer-login', 'App\\Controllers\\Public\\CustomerAuthController@login');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/customer-logout', 'App\\Controllers\\Public\\CustomerAuthController@logout');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/customer-me', 'App\\Controllers\\Public\\CustomerAuthController@me');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/customer-lookup', 'App\\Controllers\\Public\\CustomerAuthController@lookup');

/* ========= Rotas admin ========= */
// Auth + Dashboard
$router->get('/admin/{slug}/login',              'AdminAuthController@loginForm')->name('admin.auth.login');
$router->post('/admin/{slug}/login',             'AdminAuthController@login')->name('admin.auth.login');
$router->get('/admin/{slug}/logout',             'AdminAuthController@logout')->name('admin.auth.logout');
$router->get('/admin/{slug}/dashboard',          'AdminDashboardController@index')->name('dashboard.index');

// PWA Manifest dinâmico (cores da empresa)
$router->get('/admin/{slug}/manifest.webmanifest', 'AdminDashboardController@manifest');

// Configurações
$router->get('/admin/{slug}/settings',           'AdminSettingsController@index')->name('settings.index');
$router->post('/admin/{slug}/settings',          'AdminSettingsController@save')->name('settings.update');

// Desconto Fidelidade
$router->get('/admin/{slug}/loyalty-discount',   'AdminLoyaltyDiscountController@index');
$router->post('/admin/{slug}/loyalty-discount',  'AdminLoyaltyDiscountController@save');
$router->post('/admin/{slug}/loyalty-discount/create-coupon', 'AdminLoyaltyDiscountController@createCoupon');
$router->get('/admin/{slug}/loyalty-discount/coupons', 'AdminLoyaltyDiscountController@listCoupons');

// Programa de Fidelidade Progressiva
$router->get('/admin/{slug}/loyalty-program',                'AdminLoyaltyProgramController@index');
$router->post('/admin/{slug}/loyalty-program',               'AdminLoyaltyProgramController@save');
$router->post('/admin/{slug}/loyalty-program/{id}/toggle',   'AdminLoyaltyProgramController@toggle');
$router->get('/admin/{slug}/loyalty-program/stats',          'AdminLoyaltyProgramController@stats');

// Cupons (API e CRUD - integrado na página de Desconto Fidelidade)
$router->get('/admin/{slug}/coupons/api',        'AdminCouponsController@api');
$router->get('/admin/{slug}/coupons/history',    'AdminCouponsController@history');
$router->get('/admin/{slug}/coupons/create',     'AdminCouponsController@create');
$router->post('/admin/{slug}/coupons/store',     'AdminCouponsController@store');
$router->get('/admin/{slug}/coupons/{id}/edit',  'AdminCouponsController@edit');
$router->post('/admin/{slug}/coupons/{id}/update', 'AdminCouponsController@update');
$router->post('/admin/{slug}/coupons/{id}/delete', 'AdminCouponsController@delete');
$router->post('/admin/{slug}/coupons/{id}/toggle', 'AdminCouponsController@toggle');

// API Management
$router->get('/admin/{slug}/api',                'AdminApiController@index');
$router->post('/admin/{slug}/api/generate-token', 'AdminApiController@generateToken');
$router->post('/admin/{slug}/api/revoke-token',  'AdminApiController@revokeToken');
$router->post('/admin/{slug}/api/generate-key',  'AdminApiController@generateApiKey');
$router->post('/admin/{slug}/api/revoke-key',    'AdminApiController@revokeApiKey');

// Web Push Notifications
$router->get('/admin/{slug}/push/vapid-key',     'AdminPushController@getVapidKey');
$router->post('/admin/{slug}/push/subscribe',    'AdminPushController@subscribe');
$router->post('/admin/{slug}/push/unsubscribe',  'AdminPushController@unsubscribe');
$router->post('/admin/{slug}/push/test',         'AdminPushController@test');
$router->get('/admin/{slug}/push/status',        'AdminPushController@status');

// Pausa Programada (Desktop)
$router->get('/admin/{slug}/pause/status',       'AdminPauseController@status');
$router->post('/admin/{slug}/pause/enable',      'AdminPauseController@enable');
$router->post('/admin/{slug}/pause/disable',     'AdminPauseController@disable');
$router->post('/admin/{slug}/pause/extend',      'AdminPauseController@extend');

// iFood Integration
$router->get('/admin/{slug}/ifood/config',                  'AdminIFoodController@config');
$router->post('/admin/{slug}/ifood/config/save',            'AdminIFoodController@saveConfig');
$router->get('/admin/{slug}/ifood/orders',                  'AdminIFoodController@orders');
$router->get('/admin/{slug}/ifood/orders/{id}',             'AdminIFoodController@viewOrder');
$router->post('/admin/{slug}/ifood/orders/{id}/confirm',    'AdminIFoodController@confirmOrder');
$router->post('/admin/{slug}/ifood/orders/{id}/ready',      'AdminIFoodController@readyOrder');
$router->post('/admin/{slug}/ifood/orders/{id}/dispatch',   'AdminIFoodController@dispatchOrder');
$router->post('/admin/{slug}/ifood/orders/{id}/cancel',     'AdminIFoodController@cancelOrder');
$router->get('/admin/{slug}/ifood/orders/{id}/cancel-reasons', 'AdminIFoodController@getCancellationReasons');
$router->post('/admin/{slug}/ifood/poll',                   'AdminIFoodController@poll');
$router->post('/admin/{slug}/ifood/test-connection',        'AdminIFoodController@testConnection');
$router->post('/admin/{slug}/ifood/clear-error',            'AdminIFoodController@clearError');
$router->get('/admin/{slug}/ifood/status',                  'AdminIFoodController@status');

// Pedidos
$router->get('/admin/{slug}/orders',             'AdminOrdersController@index')->name('orders.index');
$router->get('/admin/{slug}/orders/show',        'AdminOrdersController@show')->name('orders.show');
$router->get('/admin/{slug}/orders/print',       'AdminOrdersController@printPdf')->name('orders.print');
$router->get('/admin/{slug}/orders/create',      'AdminOrdersController@create')->name('orders.create');
$router->post('/admin/{slug}/orders',            'AdminOrdersController@store')->name('orders.store');
$router->get('/admin/{slug}/orders/{id:\\d+}/edit',   'AdminOrdersController@edit')->name('orders.edit');
$router->post('/admin/{slug}/orders/{id:\\d+}',       'AdminOrdersController@update')->name('orders.update');
$router->post('/admin/{slug}/orders/setStatus',  'AdminOrdersController@setStatus')->name('orders.status');
$router->post('/admin/{slug}/orders/{id:\\d+}/del',   'AdminOrdersController@destroy')->name('orders.destroy');

// API para Admin Desktop
$router->get('/admin/{slug}/api/customers/search', 'AdminApiController@searchCustomerByPhone');

// KDS
$router->get('/admin/{slug}/kds',                'AdminKdsController@index')->name('kds.index');
$router->get('/admin/{slug}/kds/data',           'AdminKdsController@data')->name('kds.data');
$router->post('/admin/{slug}/kds/status',        'AdminKdsController@status')->name('kds.status');

// Analytics
$router->get('/admin/{slug}/analytics',          'AdminAnalyticsController@index');
$router->get('/admin/{slug}/analytics/data',     'AdminAnalyticsController@apiData');

// Evolution integration - main route now shows instances page
$router->get('/admin/{slug}/evolution',          'AdminEvolutionController@instances')->name('evolution.instance');
$router->get('/admin/{slug}/evolution/instances/data','AdminEvolutionController@instancesData')->name('evolution.instancesData');
$router->post('/admin/{slug}/evolution/create',  'AdminEvolutionController@create')->name('evolution.create');
$router->post('/admin/{slug}/evolution/refresh', 'AdminEvolutionController@refresh_qr')->name('evolution.refresh_qr');
$router->post('/admin/{slug}/evolution/delete',  'AdminEvolutionController@delete')->name('evolution.delete');
$router->post('/admin/{slug}/evolution/import',  'AdminEvolutionController@import_remote')->name('evolution.import_remote');
$router->post('/admin/{slug}/evolution/fetch',   'AdminEvolutionController@fetch_and_import')->name('evolution.fetch_and_import');
$router->post('/admin/{slug}/evolution/sync',    'AdminEvolutionController@sync')->name('evolution.sync');

// Evolution Webhooks
$router->post('/admin/{slug}/evolution/configure_webhook/{instanceName}', 'AdminEvolutionController@configure_webhook')->name('evolution.configure_webhook');
$router->post('/admin/{slug}/evolution/remove_webhook/{instanceName}',    'AdminEvolutionController@remove_webhook')->name('evolution.remove_webhook');
$router->get('/admin/{slug}/evolution/webhook_status/{instanceName}',     'AdminEvolutionController@webhook_status')->name('evolution.webhook_status');

// Evolution Instance Configuration
$router->get('/admin/{slug}/evolution/instance/{instanceName}',           'AdminEvolutionInstanceController@config')->name('evolution.instance.config');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/connection_state', 'AdminEvolutionInstanceController@connection_state')->name('evolution.instance.connection_state');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/connect',   'AdminEvolutionInstanceController@connect')->name('evolution.instance.connect');
$router->post('/admin/{slug}/evolution/instance/{instanceName}/restart',  'AdminEvolutionInstanceController@restart')->name('evolution.instance.restart');
$router->post('/admin/{slug}/evolution/instance/{instanceName}/disconnect', 'AdminEvolutionInstanceController@disconnect')->name('evolution.instance.disconnect');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/qr_code',   'AdminEvolutionInstanceController@qr_code')->name('evolution.instance.qr_code');
$router->post('/admin/{slug}/evolution/instance/{instanceName}/pairing_code', 'AdminEvolutionInstanceController@pairing_code')->name('evolution.instance.pairing_code');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/stats',     'AdminEvolutionInstanceController@stats')->name('evolution.instance.stats');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/groups',    'AdminEvolutionInstanceController@groups')->name('evolution.instance.groups');
$router->post('/admin/{slug}/evolution/instance/{instanceName}/order-notification', 'AdminEvolutionInstanceController@order_notification')->name('evolution.instance.order_notification.post');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/order-notification',  'AdminEvolutionInstanceController@order_notification')->name('evolution.instance.order_notification.get');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/check-notification-conflict', 'AdminEvolutionInstanceController@check_notification_conflict')->name('evolution.instance.check_notification_conflict');
$router->post('/admin/{slug}/evolution/instance/{instanceName}/validate-whatsapp', 'AdminEvolutionInstanceController@validate_whatsapp')->name('evolution.instance.validate_whatsapp');
$router->post('/admin/{slug}/evolution/instance/{instanceName}/settings', 'AdminEvolutionInstanceController@save_settings')->name('evolution.instance.save_settings');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/settings',  'AdminEvolutionInstanceController@get_settings')->name('evolution.instance.get_settings');

// Engajamento de Clientes (Customer Engagement)
$router->post('/admin/{slug}/evolution/instance/{instanceName}/customer-engagement', 'AdminEvolutionInstanceController@customer_engagement')->name('evolution.instance.customer_engagement.post');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/customer-engagement',  'AdminEvolutionInstanceController@customer_engagement')->name('evolution.instance.customer_engagement.get');
$router->get('/admin/{slug}/evolution/instance/{instanceName}/engagement-stats',     'AdminEvolutionInstanceController@engagement_stats')->name('evolution.instance.engagement_stats');

// Categorias (CRUD)
$router->get('/admin/{slug}/categories',             'AdminCategoryController@index');
$router->get('/admin/{slug}/categories/create',      'AdminCategoryController@create');
$router->get('/admin/{slug}/categories/most-ordered', 'AdminCategoryController@mostOrderedRedirect');
$router->post('/admin/{slug}/categories',            'AdminCategoryController@store');
$router->post('/admin/{slug}/categories/most-ordered', 'AdminCategoryController@updateMostOrdered');
$router->get('/admin/{slug}/categories/{id}/edit',   'AdminCategoryController@edit');
$router->post('/admin/{slug}/categories/{id}',       'AdminCategoryController@update');
$router->post('/admin/{slug}/categories/{id}/del',   'AdminCategoryController@destroy');

// Métodos de pagamento
$router->get('/admin/{slug}/payment-methods',        'AdminPaymentMethodController@index');
$router->post('/admin/{slug}/payment-methods',       'AdminPaymentMethodController@store');
// Batch update (ativar/desativar todos) — precisa vir antes da rota com {id}
$router->post('/admin/{slug}/payment-methods/batch', 'AdminPaymentMethodController@batchUpdate');
$router->post('/admin/{slug}/payment-methods/{id}',  'AdminPaymentMethodController@update');
$router->post('/admin/{slug}/payment-methods/{id}/delete', 'AdminPaymentMethodController@destroy');

// Produtos (CRUD)
$router->get('/admin/{slug}/products',               'AdminProductController@index')->name('products.index');
$router->get('/admin/{slug}/products/create',        'AdminProductController@create')->name('products.create');
$router->post('/admin/{slug}/products',              'AdminProductController@store')->name('products.store');
$router->get('/admin/{slug}/products/{id}/edit',     'AdminProductController@edit')->name('products.edit');
$router->post('/admin/{slug}/products/{id}',         'AdminProductController@update')->name('products.update');
$router->post('/admin/{slug}/products/{id}/del',     'AdminProductController@destroy')->name('products.destroy');
$router->get('/admin/{slug}/products/simple-search', 'AdminProductController@simpleProductsSearch')->name('products.simple_search');

// Guia de Cadastro
$router->get('/admin/{slug}/guide/products',         'AdminGuideController@products');
$router->get('/admin/{slug}/guide/ingredients',      'AdminGuideController@ingredients');
$router->get('/admin/{slug}/guide/coupons',          'AdminGuideController@coupons');
$router->get('/admin/{slug}/guide/cross-sell',       'AdminGuideController@crossSell');
$router->get('/admin/{slug}/guide/payment-methods',  'AdminGuideController@paymentMethods');
$router->get('/admin/{slug}/guide/delivery-fees',    'AdminGuideController@deliveryFees');
$router->get('/admin/{slug}/guide/loyalty-discount', 'AdminGuideController@loyaltyDiscount');
$router->get('/admin/{slug}/guide/financial',        'AdminGuideController@financial');
$router->get('/admin/{slug}/guide/customization-templates', 'AdminGuideController@customizationTemplates');
$router->get('/admin/{slug}/guide/company-settings',        'AdminGuideController@companySettings');
$router->get('/admin/{slug}/guide/manual-order',             'AdminGuideController@manualOrder');
$router->get('/admin/{slug}/guide/whatsapp',                  'AdminGuideController@whatsapp');
$router->get('/admin/{slug}/guide/ifood',                     'AdminGuideController@ifood');

// Ingredientes (CRUD)
$router->get('/admin/{slug}/ingredients',            'AdminIngredientController@index');
$router->get('/admin/{slug}/ingredients/create',     'AdminIngredientController@create');
$router->post('/admin/{slug}/ingredients',           'AdminIngredientController@store');
$router->get('/admin/{slug}/ingredients/{id}/edit',  'AdminIngredientController@edit');
$router->post('/admin/{slug}/ingredients/{id}',      'AdminIngredientController@update');
$router->post('/admin/{slug}/ingredients/{id}/toggle','AdminIngredientController@toggle');
$router->post('/admin/{slug}/ingredients/{id}/del',  'AdminIngredientController@destroy');

// Cross-Sell Otimizado (Grupos de recomendações)
$router->get('/admin/{slug}/cross-sell-groups',             'CrossSellGroupController@index');
$router->post('/admin/{slug}/cross-sell-groups/save',       'CrossSellGroupController@save');
$router->get('/admin/{slug}/cross-sell-groups/edit/{id}',   'CrossSellGroupController@edit');
$router->post('/admin/{slug}/cross-sell-groups/toggle/{id}', 'CrossSellGroupController@toggle');
$router->post('/admin/{slug}/cross-sell-groups/delete/{id}', 'CrossSellGroupController@delete');

// Grupos de Personalização (Templates reutilizáveis)
$router->get('/admin/{slug}/customization-templates',            'AdminCustomizationTemplateController@index');
$router->get('/admin/{slug}/customization-templates/create',     'AdminCustomizationTemplateController@create');
$router->post('/admin/{slug}/customization-templates',           'AdminCustomizationTemplateController@store');
$router->get('/admin/{slug}/customization-templates/{id}/edit',  'AdminCustomizationTemplateController@edit');
$router->post('/admin/{slug}/customization-templates/{id}',      'AdminCustomizationTemplateController@store');
$router->post('/admin/{slug}/customization-templates/{id}/del',  'AdminCustomizationTemplateController@delete');
$router->post('/admin/{slug}/customization-templates/{id}/toggle', 'AdminCustomizationTemplateController@toggle');
$router->get('/admin/{slug}/customization-templates/api/list',   'AdminCustomizationTemplateController@apiList');
$router->get('/admin/{slug}/customization-templates/api/{id}',   'AdminCustomizationTemplateController@apiGet');
$router->post('/admin/{slug}/customization-templates/api/copy-to-product', 'AdminCustomizationTemplateController@apiCopyToProduct');
$router->post('/admin/{slug}/customization-templates/api/create-from-group', 'AdminCustomizationTemplateController@apiCreateFromGroup');

// Taxas de entrega (cidades + bairros)
$router->get('/admin/{slug}/delivery-fees',                   'AdminDeliveryFeeController@index');
$router->post('/admin/{slug}/delivery-fees/cities',           'AdminDeliveryFeeController@storeCity');
$router->post('/admin/{slug}/delivery-fees/cities/{id}',      'AdminDeliveryFeeController@updateCity');
$router->post('/admin/{slug}/delivery-fees/cities/{id}/del',  'AdminDeliveryFeeController@destroyCity');
$router->post('/admin/{slug}/delivery-fees/zones',            'AdminDeliveryFeeController@storeZone');
$router->post('/admin/{slug}/delivery-fees/zones/adjust',     'AdminDeliveryFeeController@adjustZones');
$router->post('/admin/{slug}/delivery-fees/zones/{id}',       'AdminDeliveryFeeController@updateZone');
$router->post('/admin/{slug}/delivery-fees/zones/{id}/del',   'AdminDeliveryFeeController@destroyZone');
$router->post('/admin/{slug}/delivery-fees/options',          'AdminDeliveryFeeController@updateOptions');
$router->post('/admin/{slug}/delivery-fees/free-shipping',    'AdminDeliveryFeeController@updateFreeShipping');

/* ========= Gestão Financeira ========= */
// Dashboard Financeiro
$router->get('/admin/{slug}/financial',                    'AdminFinancialController@dashboard')->name('financial.index');
$router->get('/admin/{slug}/financial/monthly',            'AdminFinancialController@monthly')->name('financial.monthly');
$router->get('/admin/{slug}/financial/yearly',             'AdminFinancialController@yearly')->name('financial.yearly');
$router->get('/admin/{slug}/financial/settings',           'AdminFinancialController@settings')->name('financial.settings');
$router->post('/admin/{slug}/financial/settings',          'AdminFinancialController@saveSettings')->name('financial.update');
$router->post('/admin/{slug}/financial/recalculate',       'AdminFinancialController@recalculateCosts')->name('financial.recalculate');
$router->get('/admin/{slug}/financial/chart-data',         'AdminFinancialController@chartData')->name('financial.chart_data');

// Despesas
$router->get('/admin/{slug}/expenses',                     'AdminExpenseController@index');
$router->get('/admin/{slug}/expenses/create',              'AdminExpenseController@create');
$router->post('/admin/{slug}/expenses/store',              'AdminExpenseController@store');
$router->get('/admin/{slug}/expenses/{id}/edit',           'AdminExpenseController@edit');
$router->post('/admin/{slug}/expenses/{id}/update',        'AdminExpenseController@update');
$router->get('/admin/{slug}/expenses/{id}/delete',         'AdminExpenseController@destroy');

// Categorias de Despesas
$router->get('/admin/{slug}/expenses/categories',          'AdminExpenseController@categories');
$router->post('/admin/{slug}/expenses/categories/store',   'AdminExpenseController@storeCategory');
$router->post('/admin/{slug}/expenses/categories/{id}/update', 'AdminExpenseController@updateCategory');
$router->get('/admin/{slug}/expenses/categories/{id}/delete',  'AdminExpenseController@destroyCategory');
$router->get('/admin/{slug}/expenses/categories/seed',     'AdminExpenseController@seedCategories');

// Custos de Produtos
$router->get('/admin/{slug}/product-costs',                'AdminProductCostController@index');
$router->get('/admin/{slug}/product-costs/{id}/edit',      'AdminProductCostController@edit');
$router->post('/admin/{slug}/product-costs/{id}/update',   'AdminProductCostController@update');
$router->post('/admin/{slug}/product-costs/{id}/update-packaging', 'AdminProductCostController@updatePackaging');
$router->post('/admin/{slug}/product-costs/bulk-update',   'AdminProductCostController@bulkUpdate');
$router->get('/admin/{slug}/product-costs/{id}/calculate', 'AdminProductCostController@calculate');
$router->get('/admin/{slug}/product-costs/{id}/suggest-price', 'AdminProductCostController@suggestPrice');

// Insumos / Embalagens
$router->get('/admin/{slug}/packaging',                    'AdminPackagingController@index');
$router->get('/admin/{slug}/packaging/create',             'AdminPackagingController@create');
$router->get('/admin/{slug}/packaging/{id}/edit',          'AdminPackagingController@edit');
$router->post('/admin/{slug}/packaging/store',             'AdminPackagingController@store');
$router->post('/admin/{slug}/packaging/{id}/delete',       'AdminPackagingController@delete');
$router->get('/admin/{slug}/packaging/api/list',           'AdminPackagingController@apiList');
$router->post('/admin/{slug}/packaging/product/{id}/save', 'AdminPackagingController@apiSaveProductPackaging');

// Clientes (CRUD)
$router->get('/admin/{slug}/customers',                    'AdminCustomerController@index');
$router->get('/admin/{slug}/customers/create',             'AdminCustomerController@create');
$router->get('/admin/{slug}/customers/api/search',         'AdminCustomerController@apiSearch');
$router->post('/admin/{slug}/customers/api/validate-whatsapp', 'AdminCustomerController@validateWhatsapp');
$router->get('/admin/{slug}/customers/{id}/edit',          'AdminCustomerController@edit');
$router->post('/admin/{slug}/customers/store',             'AdminCustomerController@store');
$router->post('/admin/{slug}/customers/{id}/store',        'AdminCustomerController@store');
$router->post('/admin/{slug}/customers/{id}/delete',       'AdminCustomerController@delete');

// Endereços de clientes (AJAX API)
$router->post('/admin/{slug}/customers/{id}/addresses',                    'AdminCustomerController@storeAddress');
$router->post('/admin/{slug}/customers/{id}/addresses/{addressId}',        'AdminCustomerController@updateAddress');
$router->post('/admin/{slug}/customers/{id}/addresses/{addressId}/delete', 'AdminCustomerController@deleteAddress');

/* ========= Rotas da API ========= */
// Informações da empresa
$router->get('/api/{slug}',                      'ApiController@getCompany');
$router->get('/api/{slug}/stats',                'ApiController@getStats');

// Categorias
$router->get('/api/{slug}/categories',           'ApiController@getCategories');

// Produtos
$router->get('/api/{slug}/products',             'ApiController@getProducts');
$router->get('/api/{slug}/products/{id}',        'ApiController@getProduct');
$router->get('/api/{slug}/simple-products',      'ApiController@getSimpleProducts');

// Pedidos
$router->get('/api/{slug}/orders',               'ApiController@getOrders');
$router->get('/api/{slug}/orders/{id}',          'ApiController@getOrder');
$router->post('/api/{slug}/orders',              'ApiController@createOrder');
$router->post('/api/{slug}/orders/{id}/status',  'ApiController@updateOrderStatus');

// Machine Learning - Tracking de interações
$router->post('/api/{slug}/track-interaction',   'ApiController@trackInteraction');

// Token JWT
$router->post('/api/{slug}/token',               'ApiController@generateToken');

/* ========= Webhooks Evolution API ========= */
$router->post('/webhook/evolution/{instanceName}', 'WebhookEvolutionController@messages');
$router->post('/webhook/evolution-worker',         'WebhookEvolutionController@processQueue');
$router->get('/webhook/evolution-queue-stats',      'WebhookEvolutionController@queueStats');
$router->post('/webhook/evolution-dlq-retry',       'WebhookEvolutionController@dlqRetry');

/* ========= Webhooks iFood ========= */
$router->post('/webhook/ifood', 'WebhookIFoodController@handle');

/* ========= Constraints globais ========= */
if (method_exists($router, 'where')) {
  $router->where('slug', '[a-z0-9\-]+');
  $router->where('id',   '\d+');
}

/* ========= Label para goto (rotas mobile) ========= */
end_routes:
