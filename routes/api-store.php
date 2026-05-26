<?php
/* ========= Rotas da API ========= */
// Informações da empresa
$router->get('/api/{slug}',                      'ApiController@getCompany')->name('api.company');
$router->get('/api/{slug}/stats',                'ApiController@getStats')->name('api.stats');

// Categorias
$router->get('/api/{slug}/categories',           'ApiController@getCategories')->name('api.categories');

// Produtos
$router->get('/api/{slug}/products',             'ApiController@getProducts')->name('api.products.index');
$router->get('/api/{slug}/products/{id}',        'ApiController@getProduct')->name('api.products.show');
$router->get('/api/{slug}/simple-products',      'ApiController@getSimpleProducts')->name('api.products.simple');

// Pedidos
$router->get('/api/{slug}/orders',               'ApiController@getOrders')->name('api.orders.index');
$router->get('/api/{slug}/orders/{id}',          'ApiController@getOrder')->name('api.orders.show');
$router->post('/api/{slug}/orders',              'ApiController@createOrder')->name('api.orders.store');
$router->post('/api/{slug}/orders/{id}/status',  'ApiController@updateOrderStatus')->name('api.orders.status.update');

// Machine Learning - Tracking de interações
$router->post('/api/{slug}/track-interaction',   'ApiController@trackInteraction')->name('api.track_interaction');

// Token JWT
$router->post('/api/{slug}/token',               'ApiController@generateToken')->name('api.token.generate');

// iFood Widget público (sem auth)
$router->get('/api/{slug}/ifood-widget/config.json',  'PublicIFoodWidgetController@configJson')->name('api.ifood_widget.config');
$router->get('/api/{slug}/ifood-widget/ifood.js',     'PublicIFoodWidgetController@jsSnippet')->name('api.ifood_widget.js');
$router->get('/api/{slug}/ifood-widget/track/{ref}',  'PublicIFoodWidgetController@trackOrder')->name('api.ifood_widget.track');
