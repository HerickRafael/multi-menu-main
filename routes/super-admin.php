<?php
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

/* ========= FASE 7: Billing SaaS + Assinaturas + Faturas ========= */
$router->get('/superadmin/billing', 'SuperAdminController@billingIndex')->name('superadmin.billing.index');
$router->post('/superadmin/billing/plans/save', 'SuperAdminController@billingSavePlan')->name('superadmin.billing.plans.save');
$router->post('/superadmin/billing/plans/{code}/toggle', 'SuperAdminController@billingTogglePlanStatus')->name('superadmin.billing.plans.toggle');
$router->post('/superadmin/billing/subscriptions/create', 'SuperAdminController@billingCreateSubscription')->name('superadmin.billing.subscriptions.create');
$router->post('/superadmin/billing/subscriptions/{id:\\d+}/status', 'SuperAdminController@billingUpdateSubscriptionStatus')->name('superadmin.billing.subscriptions.status');
$router->post('/superadmin/billing/invoices/create-draft', 'SuperAdminController@billingCreateInvoiceDraft')->name('superadmin.billing.invoices.create_draft');
$router->post('/superadmin/billing/invoices/{id:\\d+}/mark-paid', 'SuperAdminController@billingMarkInvoicePaid')->name('superadmin.billing.invoices.mark_paid');

$router->post('/superadmin/api/chat', 'SuperAdminApiController@chat')->name('superadmin.api.chat');

/* ========= Catch-all for React SPA (/superadmin/platform/*) ========= */
$router->get('/superadmin/platform/{path:.*}', 'SuperAdminController@dashboard')->name('superadmin.spa');
