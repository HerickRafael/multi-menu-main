<?php
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

