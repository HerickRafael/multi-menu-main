<?php
// routes/web.php

if (defined('IS_MOBILE_SUBDOMAIN') && IS_MOBILE_SUBDOMAIN) {
    require __DIR__ . '/mobile-admin.php';
} else {
    require __DIR__ . '/public-root.php';
    require __DIR__ . '/super-admin.php';
    require __DIR__ . '/api-superadmin.php';
    require __DIR__ . '/public.php';
    require __DIR__ . '/store-admin.php';
    require __DIR__ . '/api-store.php';
    require __DIR__ . '/webhooks.php';
}

if (method_exists($router, 'where')) {
    $router->where('slug', '[a-z0-9\-]+');
    $router->where('id', '\d+');
}
