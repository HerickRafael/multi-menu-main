<?php
/* ========= Rotas da API ========= */
// Informações da empresa
$router->get('/api/{slug}',                      'ApiController@getCompany')->name('api.company');
$router->get('/api/{slug}/stats',                'ApiController@getStats')->name('api.stats');

// Categorias
$router->get('/api/{slug}/categories',           'ApiController@getCategories')->name('api.categories');
$router->post('/api/{slug}/categories/reorder',  'ApiController@reorderCategories')->name('api.categories.reorder');
$router->post('/api/{slug}/categories',          'ApiController@createCategory')->name('api.categories.create');
$router->get('/api/{slug}/categories/{id}',      'ApiController@getCategory')->name('api.categories.show');
$router->post('/api/{slug}/categories/{id}/toggle', 'ApiController@toggleCategory')->name('api.categories.toggle');
$router->post('/api/{slug}/categories/{id}',     'ApiController@updateCategory')->name('api.categories.update');
$router->delete('/api/{slug}/categories/{id}',   'ApiController@deleteCategory')->name('api.categories.delete');

// Ingredientes
$router->get('/api/{slug}/ingredients',          'ApiController@getIngredients')->name('api.ingredients.index');
$router->post('/api/{slug}/ingredients',         'ApiController@createIngredient')->name('api.ingredients.create');
$router->get('/api/{slug}/ingredients/{id}',     'ApiController@getIngredient')->name('api.ingredients.show');
$router->post('/api/{slug}/ingredients/{id}/toggle', 'ApiController@toggleIngredient')->name('api.ingredients.toggle');
$router->post('/api/{slug}/ingredients/{id}',    'ApiController@updateIngredient')->name('api.ingredients.update');
$router->delete('/api/{slug}/ingredients/{id}',  'ApiController@deleteIngredient')->name('api.ingredients.delete');

// Configurações da loja
$router->get('/api/{slug}/settings/store',       'ApiController@getStoreSettings')->name('api.settings.store.show');
$router->post('/api/{slug}/settings/store',      'ApiController@updateStoreSettings')->name('api.settings.store.update');
$router->post('/api/{slug}/settings/store/logo',   'ApiController@uploadStoreLogo')->name('api.settings.store.logo');
$router->post('/api/{slug}/settings/store/banner', 'ApiController@uploadStoreBanner')->name('api.settings.store.banner');
$router->get('/api/{slug}/settings/hours',       'ApiController@getHours')->name('api.settings.hours.show');
$router->post('/api/{slug}/settings/hours',      'ApiController@updateHours')->name('api.settings.hours.update');
$router->get('/api/{slug}/settings/payments',    'ApiController@getPayments')->name('api.settings.payments.index');
$router->post('/api/{slug}/settings/payments',   'ApiController@createPayment')->name('api.settings.payments.create');
$router->post('/api/{slug}/settings/payments/{id}/toggle', 'ApiController@togglePayment')->name('api.settings.payments.toggle');
$router->post('/api/{slug}/settings/payments/{id}', 'ApiController@updatePayment')->name('api.settings.payments.update');
$router->delete('/api/{slug}/settings/payments/{id}', 'ApiController@deletePayment')->name('api.settings.payments.delete');

// Entrega (cidades, zonas/bairros, ajuste de taxas, frete grátis)
$router->get('/api/{slug}/settings/delivery',    'ApiController@getDelivery')->name('api.settings.delivery.show');
$router->post('/api/{slug}/settings/delivery/adjust-fees',  'ApiController@adjustDeliveryFees')->name('api.settings.delivery.adjust');
$router->post('/api/{slug}/settings/delivery/free-shipping','ApiController@updateFreeShipping')->name('api.settings.delivery.free');
$router->post('/api/{slug}/settings/delivery/cities',       'ApiController@createDeliveryCity')->name('api.settings.delivery.cities.create');
$router->post('/api/{slug}/settings/delivery/cities/{id}',  'ApiController@updateDeliveryCity')->name('api.settings.delivery.cities.update');
$router->delete('/api/{slug}/settings/delivery/cities/{id}','ApiController@deleteDeliveryCity')->name('api.settings.delivery.cities.delete');
$router->post('/api/{slug}/settings/delivery/zones',        'ApiController@createDeliveryZone')->name('api.settings.delivery.zones.create');
$router->post('/api/{slug}/settings/delivery/zones/{id}',   'ApiController@updateDeliveryZone')->name('api.settings.delivery.zones.update');
$router->delete('/api/{slug}/settings/delivery/zones/{id}', 'ApiController@deleteDeliveryZone')->name('api.settings.delivery.zones.delete');

// Despesas + categorias de despesa (Financeiro)
$router->get('/api/{slug}/expenses',                       'ApiController@getExpenses')->name('api.expenses.index');
$router->post('/api/{slug}/expenses',                      'ApiController@createExpense')->name('api.expenses.create');
$router->get('/api/{slug}/expenses/categories',            'ApiController@getExpenseCategories')->name('api.expenses.categories.index');
$router->post('/api/{slug}/expenses/categories',           'ApiController@createExpenseCategory')->name('api.expenses.categories.create');
$router->post('/api/{slug}/expenses/categories/seed',      'ApiController@seedExpenseCategories')->name('api.expenses.categories.seed');
$router->post('/api/{slug}/expenses/categories/{id}',      'ApiController@updateExpenseCategory')->name('api.expenses.categories.update');
$router->delete('/api/{slug}/expenses/categories/{id}',    'ApiController@deleteExpenseCategory')->name('api.expenses.categories.delete');
$router->get('/api/{slug}/expenses/{id}',                  'ApiController@getExpense')->name('api.expenses.show');
$router->post('/api/{slug}/expenses/{id}',                 'ApiController@updateExpense')->name('api.expenses.update');
$router->delete('/api/{slug}/expenses/{id}',               'ApiController@deleteExpense')->name('api.expenses.delete');

// Pausa programada da loja
$router->get('/api/{slug}/pause/status',         'ApiController@pauseStatus')->name('api.pause.status');
$router->post('/api/{slug}/pause/enable',        'ApiController@pauseEnable')->name('api.pause.enable');
$router->post('/api/{slug}/pause/disable',       'ApiController@pauseDisable')->name('api.pause.disable');
$router->post('/api/{slug}/pause/extend',        'ApiController@pauseExtend')->name('api.pause.extend');

// Perfil do usuário
$router->get('/api/{slug}/settings/profile',     'ApiController@getProfile')->name('api.settings.profile.show');
$router->post('/api/{slug}/settings/profile',    'ApiController@updateProfile')->name('api.settings.profile.update');

// Cross-sell (categoria-gatilho → categorias recomendadas)
$router->get('/api/{slug}/cross-sell',           'ApiController@getCrossSell')->name('api.crosssell.index');
$router->post('/api/{slug}/cross-sell',          'ApiController@saveCrossSell')->name('api.crosssell.save');
$router->get('/api/{slug}/cross-sell/{id}',      'ApiController@getCrossSellGroup')->name('api.crosssell.show');
$router->post('/api/{slug}/cross-sell/{id}/toggle', 'ApiController@toggleCrossSell')->name('api.crosssell.toggle');
$router->delete('/api/{slug}/cross-sell/{id}',   'ApiController@deleteCrossSell')->name('api.crosssell.delete');

// Produtos
$router->get('/api/{slug}/products',             'ApiController@getProducts')->name('api.products.index');
$router->post('/api/{slug}/products',            'ApiController@createProduct')->name('api.products.create');
$router->get('/api/{slug}/simple-products',      'ApiController@getSimpleProducts')->name('api.products.simple');
$router->get('/api/{slug}/products/{id}',        'ApiController@getProduct')->name('api.products.show');
$router->post('/api/{slug}/products/{id}/toggle','ApiController@toggleProduct')->name('api.products.toggle');
$router->post('/api/{slug}/products/{id}/image',         'ApiController@uploadProductImage')->name('api.products.image');
$router->get('/api/{slug}/products/{id}/customization',  'ApiController@getProductCustomization')->name('api.products.customization.show');
$router->post('/api/{slug}/products/{id}/customization', 'ApiController@saveProductCustomization')->name('api.products.customization.save');
$router->post('/api/{slug}/products/{id}',       'ApiController@updateProduct')->name('api.products.update');
$router->delete('/api/{slug}/products/{id}',     'ApiController@deleteProduct')->name('api.products.delete');

// Pedidos
$router->get('/api/{slug}/orders',               'ApiController@getOrders')->name('api.orders.index');
$router->get('/api/{slug}/orders/{id}',          'ApiController@getOrder')->name('api.orders.show');
$router->post('/api/{slug}/orders',              'ApiController@createOrder')->name('api.orders.store');
$router->post('/api/{slug}/orders/{id}/status',  'ApiController@updateOrderStatus')->name('api.orders.status.update');
$router->post('/api/{slug}/orders/{id}',         'ApiController@updateOrder')->name('api.orders.update');
$router->delete('/api/{slug}/orders/{id}',       'ApiController@deleteOrder')->name('api.orders.delete');
$router->get('/api/{slug}/orders/{id}/receipt',  'ApiController@getReceipt')->name('api.orders.receipt');

// Clientes (app mobile)
$router->get('/api/{slug}/customers',            'ApiController@getCustomers')->name('api.customers.index');
$router->get('/api/{slug}/customers/{id}',       'ApiController@getCustomer')->name('api.customers.show');
$router->post('/api/{slug}/customers',           'ApiController@createCustomer')->name('api.customers.store');
$router->post('/api/{slug}/customers/{id}',      'ApiController@updateCustomer')->name('api.customers.update');
$router->delete('/api/{slug}/customers/{id}',    'ApiController@deleteCustomer')->name('api.customers.delete');

// Machine Learning - Tracking de interações
$router->post('/api/{slug}/track-interaction',   'ApiController@trackInteraction')->name('api.track_interaction');

// Token JWT
$router->post('/api/{slug}/token',               'ApiController@generateToken')->name('api.token.generate');

// Auth do app mobile (Flutter)
// Entrada do app: SEM slug — o usuário só informa e-mail/senha e o sistema
// resolve a loja onde ele foi cadastrado (modelo admin loja).
$router->post('/api/auth/login',                 'MobileAuthController@login')->name('api.auth.login');
$router->post('/api/auth/refresh',               'MobileAuthController@refresh')->name('api.auth.refresh');
$router->post('/api/auth/logout',                'MobileAuthController@logout')->name('api.auth.logout');

// Variantes COM slug (ex.: root escolhendo a loja explicitamente).
$router->post('/api/{slug}/auth/login',          'MobileAuthController@login')->name('api.auth.login.slug');
$router->post('/api/{slug}/auth/refresh',        'MobileAuthController@refresh')->name('api.auth.refresh.slug');
$router->post('/api/{slug}/auth/logout',         'MobileAuthController@logout')->name('api.auth.logout.slug');
$router->get('/api/{slug}/auth/me',              'ApiController@me')->name('api.auth.me');

// Dashboard do lojista (tela inicial do app)
$router->get('/api/{slug}/dashboard',            'ApiController@dashboard')->name('api.dashboard');

// KDS — painel de cozinha (polling incremental por sync_token)
$router->get('/api/{slug}/kds/orders',            'ApiController@kdsOrders')->name('api.kds.orders');
$router->post('/api/{slug}/kds/orders/{id}/status', 'ApiController@kdsUpdateStatus')->name('api.kds.status');

// Push nativo (FCM/APNs) — registro de devices do app mobile
$router->post('/api/{slug}/push/devices',         'ApiController@registerDevice')->name('api.push.devices.register');
$router->delete('/api/{slug}/push/devices',       'ApiController@unregisterDevice')->name('api.push.devices.unregister');
$router->post('/api/{slug}/push/test',            'ApiController@pushTest')->name('api.push.test');

// iFood Widget público (sem auth)
$router->get('/api/{slug}/ifood-widget/config.json',  'PublicIFoodWidgetController@configJson')->name('api.ifood_widget.config');
$router->get('/api/{slug}/ifood-widget/ifood.js',     'PublicIFoodWidgetController@jsSnippet')->name('api.ifood_widget.js');
$router->get('/api/{slug}/ifood-widget/track/{ref}',  'PublicIFoodWidgetController@trackOrder')->name('api.ifood_widget.track');
