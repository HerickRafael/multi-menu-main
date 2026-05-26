<?php
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
$router->get('/admin/{slug}/loyalty-discount',   'AdminLoyaltyDiscountController@index')->name('loyalty_discount.index');
$router->post('/admin/{slug}/loyalty-discount',  'AdminLoyaltyDiscountController@save')->name('loyalty_discount.save');
$router->post('/admin/{slug}/loyalty-discount/create-coupon', 'AdminLoyaltyDiscountController@createCoupon')->name('loyalty_discount.create_coupon');
$router->get('/admin/{slug}/loyalty-discount/coupons', 'AdminLoyaltyDiscountController@listCoupons')->name('loyalty_discount.coupons');

// Programa de Fidelidade Progressiva
$router->get('/admin/{slug}/loyalty-program',                'AdminLoyaltyProgramController@index')->name('loyalty_program.index');
$router->post('/admin/{slug}/loyalty-program',               'AdminLoyaltyProgramController@save')->name('loyalty_program.save');
$router->post('/admin/{slug}/loyalty-program/{id}/toggle',   'AdminLoyaltyProgramController@toggle')->name('loyalty_program.toggle');
$router->get('/admin/{slug}/loyalty-program/stats',          'AdminLoyaltyProgramController@stats')->name('loyalty_program.stats');

// Cupons (API e CRUD - integrado na página de Desconto Fidelidade)
$router->get('/admin/{slug}/coupons/api',        'AdminCouponsController@api')->name('coupons.api');
$router->get('/admin/{slug}/coupons/history',    'AdminCouponsController@history')->name('coupons.history');
$router->get('/admin/{slug}/coupons/create',     'AdminCouponsController@create')->name('coupons.create');
$router->post('/admin/{slug}/coupons/store',     'AdminCouponsController@store')->name('coupons.store');
$router->get('/admin/{slug}/coupons/{id}/edit',  'AdminCouponsController@edit')->name('coupons.edit');
$router->post('/admin/{slug}/coupons/{id}/update', 'AdminCouponsController@update')->name('coupons.update');
$router->post('/admin/{slug}/coupons/{id}/delete', 'AdminCouponsController@delete')->name('coupons.destroy');
$router->post('/admin/{slug}/coupons/{id}/toggle', 'AdminCouponsController@toggle')->name('coupons.toggle');

// API Management
$router->get('/admin/{slug}/api',                'AdminApiController@index')->name('api.index');
$router->post('/admin/{slug}/api/generate-token', 'AdminApiController@generateToken')->name('api.generate_token');
$router->post('/admin/{slug}/api/revoke-token',  'AdminApiController@revokeToken')->name('api.revoke_token');
$router->post('/admin/{slug}/api/generate-key',  'AdminApiController@generateApiKey')->name('api.generate_key');
$router->post('/admin/{slug}/api/revoke-key',    'AdminApiController@revokeApiKey')->name('api.revoke_key');

// Web Push Notifications
$router->get('/admin/{slug}/push/vapid-key',     'AdminPushController@getVapidKey')->name('push.vapid_key');
$router->post('/admin/{slug}/push/subscribe',    'AdminPushController@subscribe')->name('push.subscribe');
$router->post('/admin/{slug}/push/unsubscribe',  'AdminPushController@unsubscribe')->name('push.unsubscribe');
$router->post('/admin/{slug}/push/test',         'AdminPushController@test')->name('push.test');
$router->get('/admin/{slug}/push/status',        'AdminPushController@status')->name('push.status');

// Pausa Programada (Desktop)
$router->get('/admin/{slug}/pause/status',       'AdminPauseController@status')->name('pause.status');
$router->post('/admin/{slug}/pause/enable',      'AdminPauseController@enable')->name('pause.enable');
$router->post('/admin/{slug}/pause/disable',     'AdminPauseController@disable')->name('pause.disable');
$router->post('/admin/{slug}/pause/extend',      'AdminPauseController@extend')->name('pause.extend');

// iFood Integration
$router->get('/admin/{slug}/ifood/config',                  'AdminIFoodController@config')->name('ifood.config');
$router->post('/admin/{slug}/ifood/config/save',            'AdminIFoodController@saveConfig')->name('ifood.config.save');
$router->get('/admin/{slug}/ifood/orders',                  'AdminIFoodController@orders')->name('ifood.orders');
$router->get('/admin/{slug}/ifood/orders/{id}',             'AdminIFoodController@viewOrder')->name('ifood.orders.show');
$router->post('/admin/{slug}/ifood/orders/{id}/confirm',    'AdminIFoodController@confirmOrder')->name('ifood.orders.confirm');
$router->post('/admin/{slug}/ifood/orders/{id}/ready',      'AdminIFoodController@readyOrder')->name('ifood.orders.ready');
$router->post('/admin/{slug}/ifood/orders/{id}/dispatch',   'AdminIFoodController@dispatchOrder')->name('ifood.orders.dispatch');
$router->post('/admin/{slug}/ifood/orders/{id}/cancel',     'AdminIFoodController@cancelOrder')->name('ifood.orders.cancel');
$router->get('/admin/{slug}/ifood/orders/{id}/cancel-reasons', 'AdminIFoodController@getCancellationReasons')->name('ifood.orders.cancel-reasons');
$router->post('/admin/{slug}/ifood/poll',                   'AdminIFoodController@poll')->name('ifood.poll');
$router->post('/admin/{slug}/ifood/test-connection',        'AdminIFoodController@testConnection')->name('ifood.test-connection');
$router->post('/admin/{slug}/ifood/clear-error',            'AdminIFoodController@clearError')->name('ifood.clear-error');
$router->get('/admin/{slug}/ifood/status',                  'AdminIFoodController@status')->name('ifood.status');
$router->get('/admin/{slug}/ifood/logs',                    'AdminIFoodController@logs')->name('ifood.logs');
$router->get('/admin/{slug}/ifood/reviews',                 'AdminIFoodController@reviews')->name('ifood.reviews');
$router->post('/admin/{slug}/ifood/reviews/fetch',          'AdminIFoodController@fetchReviews')->name('ifood.reviews.fetch');
$router->post('/admin/{slug}/ifood/stock/sync',             'AdminIFoodController@syncStock')->name('ifood.stock.sync');
$router->get('/admin/{slug}/ifood/stock/state',             'AdminIFoodController@stockState')->name('ifood.stock.state');
$router->post('/admin/{slug}/ifood/orders/{id}/request-driver', 'AdminIFoodController@requestDriver')->name('ifood.orders.request-driver');
$router->post('/admin/{slug}/ifood/orders/{id}/cancel-driver',  'AdminIFoodController@cancelDriver')->name('ifood.orders.cancel-driver');
$router->get('/admin/{slug}/ifood/orders/{id}/driver-state',    'AdminIFoodController@driverState')->name('ifood.orders.driver-state');
$router->post('/admin/{slug}/ifood/shipping/quote',                   'AdminIFoodController@quoteShipping')->name('ifood.shipping.quote');
$router->post('/admin/{slug}/ifood/shipping/orders',                  'AdminIFoodController@createShipping')->name('ifood.shipping.create');
$router->get('/admin/{slug}/ifood/shipping/orders',                   'AdminIFoodController@listShipping')->name('ifood.shipping.list');
$router->get('/admin/{slug}/ifood/shipping/orders/{ext}',             'AdminIFoodController@shippingState')->name('ifood.shipping.state');
$router->post('/admin/{slug}/ifood/shipping/orders/{ext}/cancel',     'AdminIFoodController@cancelShipping')->name('ifood.shipping.cancel');
$router->get('/admin/{slug}/ifood/logistics/dashboard',               'AdminIFoodController@logisticsDashboard')->name('ifood.logistics.dashboard');
$router->get('/admin/{slug}/ifood/logistics/summary',                 'AdminIFoodController@logisticsSummary')->name('ifood.logistics.summary');
$router->get('/admin/{slug}/ifood/logistics/active',                  'AdminIFoodController@logisticsActive')->name('ifood.logistics.active');
$router->get('/admin/{slug}/ifood/logistics/metrics',                 'AdminIFoodController@logisticsMetrics')->name('ifood.logistics.metrics');
$router->get('/admin/{slug}/ifood/logistics/alerts',                  'AdminIFoodController@logisticsAlerts')->name('ifood.logistics.alerts');
$router->get('/admin/{slug}/ifood/observability/health',                       'AdminIFoodController@observabilityHealth')->name('ifood.observability.health');
$router->get('/admin/{slug}/ifood/observability/api-health',                   'AdminIFoodController@observabilityApiHealth')->name('ifood.observability.api');
$router->get('/admin/{slug}/ifood/observability/dead-jobs',                    'AdminIFoodController@observabilityDeadJobs')->name('ifood.observability.deadjobs');
$router->post('/admin/{slug}/ifood/observability/dead-jobs/{id}/retry',        'AdminIFoodController@observabilityRetryDeadJob')->name('ifood.observability.retry');
$router->post('/admin/{slug}/ifood/observability/dead-jobs/retry-all',         'AdminIFoodController@observabilityRetryAllDeadJobs')->name('ifood.observability.retry-all');
$router->get('/admin/{slug}/ifood/widget/config',                              'AdminIFoodController@widgetConfig')->name('ifood.widget.config');
$router->post('/admin/{slug}/ifood/widget/config',                             'AdminIFoodController@widgetSaveConfig')->name('ifood.widget.save');
$router->get('/admin/{slug}/ifood/widget/preview',                             'AdminIFoodController@widgetPreview')->name('ifood.widget.preview');
// Páginas React (renderizam o shell SPA com payload inicial)
$router->get('/admin/{slug}/ifood/logistics',                                  'AdminIFoodController@logisticsPage')->name('ifood.logistics.page');
$router->get('/admin/{slug}/ifood/observability',                              'AdminIFoodController@observabilityPage')->name('ifood.observability.page');
$router->get('/admin/{slug}/ifood/widget',                                     'AdminIFoodController@widgetPage')->name('ifood.widget.page');
// Shipping (HUB logístico) — páginas React
$router->get('/admin/{slug}/ifood/shipping',                                   'AdminIFoodController@shippingListPage')->name('ifood.shipping.list.page');
$router->get('/admin/{slug}/ifood/shipping/new',                               'AdminIFoodController@shippingNewPage')->name('ifood.shipping.new.page');
$router->get('/admin/{slug}/ifood/shipping/r/{ref}',                           'AdminIFoodController@shippingDetailPage')->name('ifood.shipping.detail.page');
$router->get('/admin/{slug}/ifood/stock',                                      'AdminIFoodController@stockSyncPage')->name('ifood.stock.page');

// Pedidos
$router->get('/admin/{slug}/orders',             'AdminOrdersController@index')->name('orders.index');
$router->get('/admin/{slug}/orders/poll',        'AdminOrdersController@poll')->name('orders.poll');
$router->get('/admin/{slug}/orders/show',        'AdminOrdersController@show')->name('orders.show');
$router->get('/admin/{slug}/orders/print',       'AdminOrdersController@printPdf')->name('orders.print');
$router->get('/admin/{slug}/orders/create',      'AdminOrdersController@create')->name('orders.create');
$router->post('/admin/{slug}/orders',            'AdminOrdersController@store')->name('orders.store');
$router->get('/admin/{slug}/orders/{id:\\d+}/edit',   'AdminOrdersController@edit')->name('orders.edit');
$router->post('/admin/{slug}/orders/{id:\\d+}',       'AdminOrdersController@update')->name('orders.update');
$router->post('/admin/{slug}/orders/setStatus',  'AdminOrdersController@setStatus')->name('orders.status');
$router->post('/admin/{slug}/orders/{id:\\d+}/del',   'AdminOrdersController@destroy')->name('orders.destroy');

// API para Admin Desktop
$router->get('/admin/{slug}/api/customers/search', 'AdminApiController@searchCustomerByPhone')->name('api.customers.search');

// KDS
$router->get('/admin/{slug}/kds',                'AdminKdsController@index')->name('kds.index');
$router->get('/admin/{slug}/kds/data',           'AdminKdsController@data')->name('kds.data');
$router->post('/admin/{slug}/kds/status',        'AdminKdsController@status')->name('kds.status');

// Analytics
$router->get('/admin/{slug}/analytics',          'AdminAnalyticsController@index')->name('analytics.index');
$router->get('/admin/{slug}/analytics/data',     'AdminAnalyticsController@apiData')->name('analytics.data');

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
$router->get('/admin/{slug}/categories',             'AdminCategoryController@index')->name('categories.index');
$router->get('/admin/{slug}/categories/create',      'AdminCategoryController@create')->name('categories.create');
$router->get('/admin/{slug}/categories/most-ordered', 'AdminCategoryController@mostOrderedRedirect')->name('categories.most_ordered');
$router->post('/admin/{slug}/categories',            'AdminCategoryController@store')->name('categories.store');
$router->post('/admin/{slug}/categories/most-ordered', 'AdminCategoryController@updateMostOrdered')->name('categories.most_ordered.update');
$router->get('/admin/{slug}/categories/{id}/edit',   'AdminCategoryController@edit')->name('categories.edit');
$router->post('/admin/{slug}/categories/{id}',       'AdminCategoryController@update')->name('categories.update');
$router->post('/admin/{slug}/categories/{id}/del',   'AdminCategoryController@destroy')->name('categories.destroy');

// Métodos de pagamento
$router->get('/admin/{slug}/payment-methods',        'AdminPaymentMethodController@index')->name('payment_methods.index');
$router->post('/admin/{slug}/payment-methods',       'AdminPaymentMethodController@store')->name('payment_methods.store');
// Batch update (ativar/desativar todos) — precisa vir antes da rota com {id}
$router->post('/admin/{slug}/payment-methods/batch', 'AdminPaymentMethodController@batchUpdate')->name('payment_methods.batch');
$router->post('/admin/{slug}/payment-methods/{id}',  'AdminPaymentMethodController@update')->name('payment_methods.update');
$router->post('/admin/{slug}/payment-methods/{id}/delete', 'AdminPaymentMethodController@destroy')->name('payment_methods.destroy');

// Produtos (CRUD)
$router->get('/admin/{slug}/products',               'AdminProductController@index')->name('products.index');
$router->get('/admin/{slug}/products/create',        'AdminProductController@create')->name('products.create');
$router->post('/admin/{slug}/products',              'AdminProductController@store')->name('products.store');
$router->get('/admin/{slug}/products/{id}/edit',     'AdminProductController@edit')->name('products.edit');
$router->post('/admin/{slug}/products/{id}',         'AdminProductController@update')->name('products.update');
$router->post('/admin/{slug}/products/{id}/del',     'AdminProductController@destroy')->name('products.destroy');
$router->get('/admin/{slug}/products/simple-search', 'AdminProductController@simpleProductsSearch')->name('products.simple_search');

// Guia de Cadastro
$router->get('/admin/{slug}/guide/products',         'AdminGuideController@products')->name('guide.products');
$router->get('/admin/{slug}/guide/ingredients',      'AdminGuideController@ingredients')->name('guide.ingredients');
$router->get('/admin/{slug}/guide/coupons',          'AdminGuideController@coupons')->name('guide.coupons');
$router->get('/admin/{slug}/guide/cross-sell',       'AdminGuideController@crossSell')->name('guide.cross_sell');
$router->get('/admin/{slug}/guide/payment-methods',  'AdminGuideController@paymentMethods')->name('guide.payment_methods');
$router->get('/admin/{slug}/guide/delivery-fees',    'AdminGuideController@deliveryFees')->name('guide.delivery_fees');
$router->get('/admin/{slug}/guide/loyalty-discount', 'AdminGuideController@loyaltyDiscount')->name('guide.loyalty_discount');
$router->get('/admin/{slug}/guide/financial',        'AdminGuideController@financial')->name('guide.financial');
$router->get('/admin/{slug}/guide/customization-templates', 'AdminGuideController@customizationTemplates')->name('guide.customization_templates');
$router->get('/admin/{slug}/guide/company-settings',        'AdminGuideController@companySettings')->name('guide.company_settings');
$router->get('/admin/{slug}/guide/manual-order',             'AdminGuideController@manualOrder')->name('guide.manual_order');
$router->get('/admin/{slug}/guide/whatsapp',                  'AdminGuideController@whatsapp')->name('guide.whatsapp');
$router->get('/admin/{slug}/guide/ifood',                     'AdminGuideController@ifood')->name('guide.ifood');

// Ingredientes (CRUD)
$router->get('/admin/{slug}/ingredients',            'AdminIngredientController@index')->name('ingredients.index');
$router->get('/admin/{slug}/ingredients/create',     'AdminIngredientController@create')->name('ingredients.create');
$router->post('/admin/{slug}/ingredients',           'AdminIngredientController@store')->name('ingredients.store');
$router->get('/admin/{slug}/ingredients/{id}/edit',  'AdminIngredientController@edit')->name('ingredients.edit');
$router->post('/admin/{slug}/ingredients/{id}',      'AdminIngredientController@update')->name('ingredients.update');
$router->post('/admin/{slug}/ingredients/{id}/toggle','AdminIngredientController@toggle')->name('ingredients.toggle');
$router->post('/admin/{slug}/ingredients/{id}/del',  'AdminIngredientController@destroy')->name('ingredients.destroy');

// Cross-Sell Otimizado (Grupos de recomendações)
$router->get('/admin/{slug}/cross-sell-groups',             'CrossSellGroupController@index')->name('cross_sell_groups.index');
$router->post('/admin/{slug}/cross-sell-groups/save',       'CrossSellGroupController@save')->name('cross_sell_groups.save');
$router->get('/admin/{slug}/cross-sell-groups/edit/{id}',   'CrossSellGroupController@edit')->name('cross_sell_groups.edit');
$router->post('/admin/{slug}/cross-sell-groups/toggle/{id}', 'CrossSellGroupController@toggle')->name('cross_sell_groups.toggle');
$router->post('/admin/{slug}/cross-sell-groups/delete/{id}', 'CrossSellGroupController@delete')->name('cross_sell_groups.destroy');

// Grupos de Personalização (Templates reutilizáveis)
$router->get('/admin/{slug}/customization-templates',            'AdminCustomizationTemplateController@index')->name('customization_templates.index');
$router->get('/admin/{slug}/customization-templates/create',     'AdminCustomizationTemplateController@create')->name('customization_templates.create');
$router->post('/admin/{slug}/customization-templates',           'AdminCustomizationTemplateController@store')->name('customization_templates.store');
$router->get('/admin/{slug}/customization-templates/{id}/edit',  'AdminCustomizationTemplateController@edit')->name('customization_templates.edit');
$router->post('/admin/{slug}/customization-templates/{id}',      'AdminCustomizationTemplateController@store')->name('customization_templates.update');
$router->post('/admin/{slug}/customization-templates/{id}/del',  'AdminCustomizationTemplateController@delete')->name('customization_templates.destroy');
$router->post('/admin/{slug}/customization-templates/{id}/toggle', 'AdminCustomizationTemplateController@toggle')->name('customization_templates.toggle');
$router->get('/admin/{slug}/customization-templates/api/list',   'AdminCustomizationTemplateController@apiList')->name('customization_templates.api_list');
$router->get('/admin/{slug}/customization-templates/api/{id}',   'AdminCustomizationTemplateController@apiGet')->name('customization_templates.api_get');
$router->post('/admin/{slug}/customization-templates/api/copy-to-product', 'AdminCustomizationTemplateController@apiCopyToProduct')->name('customization_templates.api_copy');
$router->post('/admin/{slug}/customization-templates/api/create-from-group', 'AdminCustomizationTemplateController@apiCreateFromGroup')->name('customization_templates.api_create_from_group');

// Taxas de entrega (cidades + bairros)
$router->get('/admin/{slug}/delivery-fees',                   'AdminDeliveryFeeController@index')->name('delivery_fees.index');
$router->post('/admin/{slug}/delivery-fees/cities',           'AdminDeliveryFeeController@storeCity')->name('delivery_fees.cities.store');
$router->post('/admin/{slug}/delivery-fees/cities/{id}',      'AdminDeliveryFeeController@updateCity')->name('delivery_fees.cities.update');
$router->post('/admin/{slug}/delivery-fees/cities/{id}/del',  'AdminDeliveryFeeController@destroyCity')->name('delivery_fees.cities.destroy');
$router->post('/admin/{slug}/delivery-fees/zones',            'AdminDeliveryFeeController@storeZone')->name('delivery_fees.zones.store');
$router->post('/admin/{slug}/delivery-fees/zones/adjust',     'AdminDeliveryFeeController@adjustZones')->name('delivery_fees.zones.adjust');
$router->post('/admin/{slug}/delivery-fees/zones/{id}',       'AdminDeliveryFeeController@updateZone')->name('delivery_fees.zones.update');
$router->post('/admin/{slug}/delivery-fees/zones/{id}/del',   'AdminDeliveryFeeController@destroyZone')->name('delivery_fees.zones.destroy');
$router->post('/admin/{slug}/delivery-fees/options',          'AdminDeliveryFeeController@updateOptions')->name('delivery_fees.options');
$router->post('/admin/{slug}/delivery-fees/free-shipping',    'AdminDeliveryFeeController@updateFreeShipping')->name('delivery_fees.free_shipping');

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
$router->get('/admin/{slug}/expenses',                     'AdminExpenseController@index')->name('expenses.index');
$router->get('/admin/{slug}/expenses/create',              'AdminExpenseController@create')->name('expenses.create');
$router->post('/admin/{slug}/expenses/store',              'AdminExpenseController@store')->name('expenses.store');
$router->get('/admin/{slug}/expenses/{id}/edit',           'AdminExpenseController@edit')->name('expenses.edit');
$router->post('/admin/{slug}/expenses/{id}/update',        'AdminExpenseController@update')->name('expenses.update');
$router->get('/admin/{slug}/expenses/{id}/delete',         'AdminExpenseController@destroy')->name('expenses.destroy');

// Categorias de Despesas
$router->get('/admin/{slug}/expenses/categories',          'AdminExpenseController@categories')->name('expenses.categories');
$router->post('/admin/{slug}/expenses/categories/store',   'AdminExpenseController@storeCategory')->name('expenses.categories.store');
$router->post('/admin/{slug}/expenses/categories/{id}/update', 'AdminExpenseController@updateCategory')->name('expenses.categories.update');
$router->get('/admin/{slug}/expenses/categories/{id}/delete',  'AdminExpenseController@destroyCategory')->name('expenses.categories.destroy');
$router->get('/admin/{slug}/expenses/categories/seed',     'AdminExpenseController@seedCategories')->name('expenses.categories.seed');

// Custos de Produtos
$router->get('/admin/{slug}/product-costs',                'AdminProductCostController@index')->name('product_costs.index');
$router->get('/admin/{slug}/product-costs/{id}/edit',      'AdminProductCostController@edit')->name('product_costs.edit');
$router->post('/admin/{slug}/product-costs/{id}/update',   'AdminProductCostController@update')->name('product_costs.update');
$router->post('/admin/{slug}/product-costs/{id}/update-packaging', 'AdminProductCostController@updatePackaging')->name('product_costs.update_packaging');
$router->post('/admin/{slug}/product-costs/bulk-update',   'AdminProductCostController@bulkUpdate')->name('product_costs.bulk_update');
$router->get('/admin/{slug}/product-costs/{id}/calculate', 'AdminProductCostController@calculate')->name('product_costs.calculate');
$router->get('/admin/{slug}/product-costs/{id}/suggest-price', 'AdminProductCostController@suggestPrice')->name('product_costs.suggest_price');

// Insumos / Embalagens
$router->get('/admin/{slug}/packaging',                    'AdminPackagingController@index')->name('packaging.index');
$router->get('/admin/{slug}/packaging/create',             'AdminPackagingController@create')->name('packaging.create');
$router->get('/admin/{slug}/packaging/{id}/edit',          'AdminPackagingController@edit')->name('packaging.edit');
$router->post('/admin/{slug}/packaging/store',             'AdminPackagingController@store')->name('packaging.store');
$router->post('/admin/{slug}/packaging/{id}/delete',       'AdminPackagingController@delete')->name('packaging.destroy');
$router->get('/admin/{slug}/packaging/api/list',           'AdminPackagingController@apiList')->name('packaging.api_list');
$router->post('/admin/{slug}/packaging/product/{id}/save', 'AdminPackagingController@apiSaveProductPackaging')->name('packaging.product_save');

// Clientes (CRUD)
$router->get('/admin/{slug}/customers',                    'AdminCustomerController@index')->name('customers.index');
$router->get('/admin/{slug}/customers/create',             'AdminCustomerController@create')->name('customers.create');
$router->get('/admin/{slug}/customers/api/search',         'AdminCustomerController@apiSearch')->name('customers.api_search');
$router->post('/admin/{slug}/customers/api/validate-whatsapp', 'AdminCustomerController@validateWhatsapp')->name('customers.api_validate_whatsapp');
$router->get('/admin/{slug}/customers/{id}/edit',          'AdminCustomerController@edit')->name('customers.edit');
$router->post('/admin/{slug}/customers/store',             'AdminCustomerController@store')->name('customers.store');
$router->post('/admin/{slug}/customers/{id}/store',        'AdminCustomerController@store')->name('customers.update');
$router->post('/admin/{slug}/customers/{id}/delete',       'AdminCustomerController@delete')->name('customers.destroy');

// Endereços de clientes (AJAX API)
$router->post('/admin/{slug}/customers/{id}/addresses',                    'AdminCustomerController@storeAddress')->name('customers.addresses.store');
$router->post('/admin/{slug}/customers/{id}/addresses/{addressId}',        'AdminCustomerController@updateAddress')->name('customers.addresses.update');
$router->post('/admin/{slug}/customers/{id}/addresses/{addressId}/delete', 'AdminCustomerController@deleteAddress')->name('customers.addresses.destroy');

