<?php
/* ========= Super Admin JSON API (React Dashboard) ========= */
$router->post('/api/superadmin/auth', 'SuperAdminDashboardApiController@auth')->name('api.superadmin.auth');
$router->post('/api/superadmin/logout', 'SuperAdminDashboardApiController@logout')->name('api.superadmin.logout');
$router->post('/api/superadmin/tenant-context/switch', 'SuperAdminDashboardApiController@switchTenantContext')->name('api.superadmin.tenant_context.switch');
$router->get('/api/superadmin/me', 'SuperAdminDashboardApiController@me')->name('api.superadmin.me');
$router->get('/api/superadmin/dashboard', 'SuperAdminDashboardApiController@dashboard')->name('api.superadmin.dashboard');

// User management routes moved here
$router->get('/api/superadmin/users', 'SuperAdminDashboardApiController@users')->name('api.superadmin.users');
$router->post('/api/superadmin/users/create', 'SuperAdminDashboardApiController@createUser')->name('api.superadmin.users.create');
$router->post('/api/superadmin/users/update', 'SuperAdminDashboardApiController@updateUser')->name('api.superadmin.users.update');
$router->post('/api/superadmin/users/change-password', 'SuperAdminDashboardApiController@changeUserPassword')->name('api.superadmin.users.change_password');
$router->delete('/api/superadmin/users', 'SuperAdminDashboardApiController@deleteUser')->name('api.superadmin.users.delete');

// Store management routes
$router->get('/api/superadmin/stores', 'SuperAdminDashboardApiController@stores')->name('api.superadmin.stores');
$router->post('/api/superadmin/stores/toggle-status', 'SuperAdminDashboardApiController@toggleStoreStatus')->name('api.superadmin.stores.toggle_status');
$router->post('/api/superadmin/stores/reset-cache', 'SuperAdminDashboardApiController@resetStoreCache')->name('api.superadmin.stores.reset_cache');
$router->delete('/api/superadmin/stores', 'SuperAdminDashboardApiController@deleteStore')->name('api.superadmin.stores.delete');

$router->get('/api/superadmin/orders', 'SuperAdminDashboardApiController@orders')->name('api.superadmin.orders');
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

/* ========= FASE 7: Billing SaaS + Assinaturas + Faturas (API) ========= */
$router->get('/api/superadmin/billing', 'SuperAdminDashboardApiController@billing')->name('api.superadmin.billing');
$router->get('/api/superadmin/billing/plans', 'SuperAdminDashboardApiController@billingPlans')->name('api.superadmin.billing.plans');
$router->post('/api/superadmin/billing/plans/save', 'SuperAdminDashboardApiController@billingSavePlan')->name('api.superadmin.billing.plans.save');
$router->post('/api/superadmin/billing/plans/{code:[A-Za-z0-9._-]+}/toggle', 'SuperAdminDashboardApiController@billingTogglePlanStatus')->name('api.superadmin.billing.plans.toggle');
$router->post('/api/superadmin/billing/subscriptions/create', 'SuperAdminDashboardApiController@billingCreateSubscription')->name('api.superadmin.billing.subscriptions.create');
$router->post('/api/superadmin/billing/subscriptions/status', 'SuperAdminDashboardApiController@billingUpdateSubscriptionStatus')->name('api.superadmin.billing.subscriptions.status');
$router->post('/api/superadmin/billing/invoices/create-draft', 'SuperAdminDashboardApiController@billingCreateInvoiceDraft')->name('api.superadmin.billing.invoices.create_draft');
$router->post('/api/superadmin/billing/invoices/mark-paid', 'SuperAdminDashboardApiController@billingMarkInvoicePaid')->name('api.superadmin.billing.invoices.mark_paid');

