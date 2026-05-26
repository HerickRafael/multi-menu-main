<?php
/* ========= Imagens otimizadas (cache profissional) ========= */
$router->get('/img/{path:.*}', 'ImageController@serve');

/* ========= Rotas públicas (cardápio) ========= */
/* IMPORTANT: {slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}
   impede capture de palavras-chave reservadas */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}', 'App\\Controllers\\Public\\HomeController@index');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/buscar', 'App\\Controllers\\Public\\HomeController@buscar')->name('home.buscar');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/politica-privacidade', 'App\\Controllers\\Public\\HomeController@privacyPolicy')->name('home.privacy_policy');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/produto/{id}', 'App\\Controllers\\Public\\ProductController@show');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/product/{id}', 'App\\Controllers\\Public\\ProductController@show');

/* Personalização de produto */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/produto/{id}/customizar', 'App\\Controllers\\Public\\ProductController@customize')->name('produto.customizar');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/produto/{id}/customizar', 'App\\Controllers\\Public\\ProductController@saveCustomization')->name('produto.customizar.save');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/produto/{id}/customizar/cancelar', 'App\\Controllers\\Public\\ProductController@cancelCustomization')->name('produto.customizar.cancelar');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/check-customization', 'App\\Controllers\\Public\\ProductController@checkCustomization')->name('produto.check_customization');

/* Carrinho */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/cart', 'App\\Controllers\\Public\\CartController@index');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/cart/add', 'App\\Controllers\\Public\\CartController@add')->name('cart.add');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/cart/update', 'App\\Controllers\\Public\\CartController@update');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/validate-coupon', 'App\\Controllers\\Public\\CartController@validateCoupon')->name('cart.validate_coupon');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/sync-coupon', 'App\\Controllers\\Public\\CartController@syncCoupon')->name('cart.sync_coupon');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout', 'App\\Controllers\\Public\\CartController@checkout')->name('checkout.show');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout/processing', 'App\\Controllers\\Public\\CartController@processing')->name('checkout.processing');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout/processing', 'App\\Controllers\\Public\\CartController@confirmProcessing')->name('checkout.confirm_processing');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout/success', 'App\\Controllers\\Public\\CartController@checkoutSuccess')->name('checkout.success');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout', 'App\\Controllers\\Public\\CartController@submitCheckout')->name('checkout.submit');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/checkout/calculate', 'App\\Controllers\\Public\\CartController@calculateCheckout')->name('checkout.calculate');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/reorder/{orderId}', 'App\\Controllers\\Public\\CartController@reorder')->name('cart.reorder');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/profile', 'App\\Controllers\\Public\\ProfileController@index');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/profile/update', 'App\\Controllers\\Public\\ProfileController@update');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/profile/export-data', 'App\\Controllers\\Public\\ProfileController@exportData')->name('profile.export_data');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/profile/request-deletion', 'App\\Controllers\\Public\\ProfileController@requestDeletion')->name('profile.request_deletion');

/* Pedidos */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/order/{id}', 'App\\Controllers\\Public\\OrderController@show');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/order/{id}/cancel', 'App\\Controllers\\Public\\OrderController@cancel')->name('order.cancel');

/* Autocomplete de ruas - Enterprise (DB local + Redis + Overpass fallback) */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/street-autocomplete', 'App\\Controllers\\Public\\StreetAutocompleteController@search')->name('street_autocomplete.search');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/street-autocomplete/popularity', 'App\\Controllers\\Public\\StreetAutocompleteController@popularity')->name('street_autocomplete.popularity');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/street-autocomplete/learn', 'App\\Controllers\\Public\\StreetAutocompleteController@learn')->name('street_autocomplete.learn');

/* Endereços */
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses', 'App\\Controllers\\Public\\AddressController@index');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/create', 'App\\Controllers\\Public\\AddressController@create');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/create', 'App\\Controllers\\Public\\AddressController@store');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/edit/{id}', 'App\\Controllers\\Public\\AddressController@edit');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/update', 'App\\Controllers\\Public\\AddressController@update');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/delete', 'App\\Controllers\\Public\\AddressController@delete')->name('addresses.delete');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/set-default', 'App\\Controllers\\Public\\AddressController@setDefault')->name('addresses.set_default');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/addresses/update-label', 'App\\Controllers\\Public\\AddressController@updateLabel')->name('addresses.update_label');

/* ========= Rotas cliente ========= */
// Rota de login (redireciona para home com modal de login)
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/login', function($params) {
    $slug = $params['slug'] ?? '';
    $expired = isset($_GET['expired']) ? '?expired=1' : '';
    $sessionExpired = isset($_GET['session_expired']) ? '?session_expired=1' : '';
    $query = $expired ?: $sessionExpired;
    header('Location: /' . rawurlencode($slug) . $query);
    exit;
})->name('customer.login_redirect');

$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/customer-login', 'App\\Controllers\\Public\\CustomerAuthController@login')->name('customer.login');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/customer-logout', 'App\\Controllers\\Public\\CustomerAuthController@logout')->name('customer.logout');
$router->get('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/customer-me', 'App\\Controllers\\Public\\CustomerAuthController@me')->name('customer.me');
$router->post('/{slug:(?!admin|superadmin|api|webhook|vendas|robots|sitemap|img|push|cross-sell|login)[A-Za-z0-9_-]+}/customer-lookup', 'App\\Controllers\\Public\\CustomerAuthController@lookup')->name('customer.lookup');
