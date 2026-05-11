<?php
/**
 * Testes automatizados: Router naming + SidebarService context/active.
 *
 * Roda diretamente:
 *   php tests/test_router_sidebar.php
 *
 * Retorna exit code 0 se todos passarem, 1 se algum falhar.
 */

declare(strict_types=1);

// ── Bootstrap mínimo (sem DB/Redis) ─────────────────────────────────────────

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Config stub
$config = ['env' => 'testing'];
function config(string $key, $default = null)
{
    global $config;
    return $config[$key] ?? $default;
}

// Helpers stub
function base_url(string $path = ''): string
{
    return 'http://localhost/' . ltrim($path, '/');
}

if (!function_exists('e')) {
    function e(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// Logger stub (captura para assertions)
class Logger
{
    public static array $logs = [];

    public static function warning(string $msg, array $ctx = []): void
    {
        self::$logs[] = ['level' => 'warning', 'msg' => $msg, 'ctx' => $ctx];
    }

    public static function error(string $msg, $ex = null, array $ctx = []): void
    {
        self::$logs[] = ['level' => 'error', 'msg' => $msg, 'ctx' => $ctx];
    }

    public static function clear(): void
    {
        self::$logs = [];
    }
}

require_once APP_PATH . '/core/Router.php';

// Stubs de helpers do bootstrap (não incluímos bootstrap.php para evitar side-effects)
if (!function_exists('current_route_name_source')) {
    function current_route_name_source(): ?string
    {
        if (class_exists('Router') && method_exists('Router', 'currentRouteNameSource')) {
            return Router::currentRouteNameSource();
        }
        return isset($GLOBALS['current_route_name_source']) ? (string)$GLOBALS['current_route_name_source'] : null;
    }
}

// Stub de SmartCache e StoreStatusService para SidebarService
class SmartCache
{
    public static function remember(string $key, callable $loader, int $ttl = 300)
    {
        return $loader();
    }
}

// Precisa estar no namespace App\Services
require_once APP_PATH . '/services/StoreStatusService.php';
require_once APP_PATH . '/services/SidebarService.php';

// ── Test runner ─────────────────────────────────────────────────────────────

$passed = 0;
$failed = 0;
$failures = [];

function assert_eq($expected, $actual, string $label): void
{
    global $passed, $failed, $failures;
    if ($expected === $actual) {
        $passed++;
        echo "  ✓ {$label}\n";
    } else {
        $failed++;
        $detail = "    expected: " . var_export($expected, true) . "\n    actual:   " . var_export($actual, true);
        $failures[] = "{$label}\n{$detail}";
        echo "  ✗ {$label}\n{$detail}\n";
    }
}

function assert_true(bool $value, string $label): void
{
    assert_eq(true, $value, $label);
}

function assert_false(bool $value, string $label): void
{
    assert_eq(false, $value, $label);
}

function assert_throws(callable $fn, string $label): void
{
    global $passed, $failed, $failures;
    try {
        $fn();
        $failed++;
        $failures[] = "{$label}: expected exception, none thrown";
        echo "  ✗ {$label} (no exception)\n";
    } catch (\Throwable $e) {
        $passed++;
        echo "  ✓ {$label} (threw: " . get_class($e) . ")\n";
    }
}

// ── 1. Router: auto-name gerado corretamente ───────────────────────────────

echo "\n=== Router: Auto-name Generation ===\n";

$router = new Router();
$GLOBALS['router'] = $router;

// CRUD simples — auto-name deve funcionar
$router->get('/admin/{slug}/dashboard', 'C@index');
$router->get('/admin/{slug}/orders', 'C@index');
$router->get('/admin/{slug}/orders/show', 'C@show');   // real: query param, não wildcard
$router->get('/admin/{slug}/products/create', 'C@create');
$router->post('/admin/{slug}/products', 'C@store');
$router->get('/admin/{slug}/products/{id}/edit', 'C@edit');
$router->post('/admin/{slug}/products/{id}', 'C@update');
$router->post('/admin/{slug}/products/{id}/del', 'C@destroy');

// Business routes — auto-name com inferência
$router->get('/admin/{slug}/financial/monthly', 'C@monthly');
$router->get('/admin/{slug}/financial/chart-data', 'C@chartData');
$router->get('/admin/{slug}/kds/data', 'C@data');

// Com nome manual explícito
$router->get('/admin/{slug}/orders/print', 'C@printPdf')->name('orders.print');
$router->post('/admin/{slug}/orders/setStatus', 'C@setStatus')->name('orders.status');

// Dispatch e verificar routeName gerado
$testCases = [
    ['GET', '/admin/acme/dashboard', 'dashboard.index'],
    ['GET', '/admin/acme/orders', 'orders.index'],
    ['GET', '/admin/acme/products/create', 'products.create'],
    ['POST', '/admin/acme/products', 'products.store'],
    ['POST', '/admin/acme/products/42', 'products.update'],
    ['POST', '/admin/acme/products/42/del', 'products.destroy'],
    // Manual names
    ['GET', '/admin/acme/orders/print', 'orders.print'],
    ['POST', '/admin/acme/orders/setStatus', 'orders.status'],
];

foreach ($testCases as [$method, $uri, $expectedName]) {
    // Supress output from dispatch
    ob_start();
    $router->dispatch($method, $uri);
    ob_end_clean();

    assert_eq($expectedName, Router::currentRouteName(), "Route {$method} {$uri} → {$expectedName}");
}

// ── 2. Router: explicit_name flag ──────────────────────────────────────────

echo "\n=== Router: Explicit Name Flag ===\n";

ob_start();
$router->dispatch('GET', '/admin/acme/orders/print');
ob_end_clean();
assert_eq('manual', Router::currentRouteNameSource(), 'orders.print has source=manual');

ob_start();
$router->dispatch('GET', '/admin/acme/dashboard');
ob_end_clean();
assert_eq('auto', Router::currentRouteNameSource(), 'dashboard.index has source=auto');

// ── 3. Router: enforcement em dev para business route sem name ─────────────

echo "\n=== Router: Enforcement (throw in dev) ===\n";

// financial.monthly é business route (não CRUD) e não tem ->name() explícito
assert_throws(function () use ($router) {
    ob_start();
    try {
        $router->dispatch('GET', '/admin/acme/financial/monthly');
    } finally {
        ob_end_clean();
    }
}, 'Business route financial.monthly without ->name() throws in dev');

// chart_data é business route sem name
assert_throws(function () use ($router) {
    ob_start();
    try {
        $router->dispatch('GET', '/admin/acme/financial/chart-data');
    } finally {
        ob_end_clean();
    }
}, 'Business route financial.chart_data without ->name() throws in dev');

// CRUD route sem name NÃO deve dar throw
$noThrow = true;
try {
    ob_start();
    $router->dispatch('GET', '/admin/acme/orders');
    ob_end_clean();
} catch (\Throwable $e) {
    ob_end_clean();
    $noThrow = false;
}
assert_true($noThrow, 'CRUD route orders.index (auto) does NOT throw');

// ── 4. SidebarService: resolveContext ───────────────────────────────────────

echo "\n=== SidebarService: Context Resolution ===\n";

// Para testar métodos privados, usamos Reflection
$rc = new ReflectionClass(\App\Services\SidebarService::class);
$resolveContext = $rc->getMethod('resolveContext');
$resolveContext->setAccessible(true);

$contextTests = [
    ['dashboard.index', 'dashboard'],
    ['orders.index', 'orders'],
    ['orders.show', 'orders'],
    ['orders.print', 'orders'],
    ['orders.status', 'orders'],
    ['kds.index', 'orders'],
    ['kds.data', 'orders'],
    ['products.index', 'products'],
    ['products.create', 'products'],
    ['categories.index', 'products'],
    ['ingredients.edit', 'products'],
    ['cross_sell_groups.index', 'products'],
    ['customization_templates.index', 'products'],
    ['financial.index', 'financial'],
    ['financial.monthly', 'financial'],
    ['expenses.index', 'financial'],
    ['product_costs.index', 'financial'],
    ['customers.index', 'customers'],
    ['loyalty_discount.index', 'loyalty'],
    ['coupons.index', 'loyalty'],
    ['evolution.index', 'whatsapp'],
    ['settings.index', 'settings'],
    ['payment_methods.index', 'settings'],
    ['delivery_fees.index', 'settings'],
    ['ifood.index', 'settings'],
    ['analytics.index', 'analytics'],
    ['unknown_module.something', 'dashboard'],  // fallback
];

foreach ($contextTests as [$route, $expectedContext]) {
    $result = $resolveContext->invoke(null, $route);
    assert_eq($expectedContext, $result, "resolveContext('{$route}') → '{$expectedContext}'");
}

// ── 5. SidebarService: requiresExplicitRouteName ───────────────────────────

echo "\n=== SidebarService: requiresExplicitRouteName ===\n";

$requiresExplicit = $rc->getMethod('requiresExplicitRouteName');
$requiresExplicit->setAccessible(true);

$explicitTests = [
    // CRUD — NÃO precisa de name explícito
    ['orders.index', false],
    ['orders.show', false],
    ['orders.create', false],
    ['orders.store', false],
    ['orders.edit', false],
    ['orders.update', false],
    ['orders.destroy', false],
    // Safe auto — NÃO precisa
    ['products.toggle', false],
    ['auth.login', false],
    ['auth.logout', false],
    ['dashboard.manifest', false],
    // Business — PRECISA de name explícito
    ['orders.print', true],
    ['orders.status', true],
    ['financial.monthly', true],
    ['financial.yearly', true],
    ['financial.settings', true],
    ['financial.recalculate', true],
    ['financial.chart_data', true],
    ['kds.data', true],
    ['kds.status', true],
    ['products.simple_search', true],
    ['analytics.data', true],
];

foreach ($explicitTests as [$route, $expectedResult]) {
    $result = $requiresExplicit->invoke(null, $route);
    $label = $expectedResult ? 'requires explicit' : 'auto-name OK';
    assert_eq($expectedResult, $result, "requiresExplicitRouteName('{$route}') → {$label}");
}

// ── 6. SidebarService: isGlobalItemActive ──────────────────────────────────

echo "\n=== SidebarService: Global Item Active ===\n";

$isGlobalActive = $rc->getMethod('isGlobalItemActive');
$isGlobalActive->setAccessible(true);

$ordersItem = ['context' => 'orders'];
$dashItem = ['context' => 'dashboard'];

assert_true($isGlobalActive->invoke(null, $ordersItem, 'orders'), 'Orders item active when context=orders');
assert_false($isGlobalActive->invoke(null, $ordersItem, 'dashboard'), 'Orders item NOT active when context=dashboard');
assert_true($isGlobalActive->invoke(null, $dashItem, 'dashboard'), 'Dashboard item active when context=dashboard');

// ── 7. SidebarService: build() NUNCA lança exception ───────────────────────

echo "\n=== SidebarService: build() Never Throws ===\n";

// Helper: testar que SidebarService::build retorna array sem lançar exception
function assert_build_safe(array $company, string $uri, ?string $routeName, string $slug, string $label, ?string $expectedLevel = null): void
{
    global $passed, $failed, $failures;
    try {
        $result = \App\Services\SidebarService::build($company, $uri, $routeName, $slug);
        if (!is_array($result) || !isset($result['globalItems'])) {
            $failed++;
            $failures[] = "{$label}: returned non-array or missing globalItems";
            echo "  ✗ {$label} (unexpected return)\n";
            return;
        }
        $wl = $result['debug_warning_level'] ?? 'none';
        if ($expectedLevel !== null && $wl !== $expectedLevel) {
            $failed++;
            $failures[] = "{$label}: expected level={$expectedLevel}, got level={$wl}";
            echo "  ✗ {$label} (expected level={$expectedLevel}, got level={$wl})\n";
            return;
        }
        $passed++;
        echo "  ✓ {$label} (level={$wl})\n";
    } catch (\Throwable $e) {
        $failed++;
        $failures[] = "{$label}: threw " . get_class($e) . ": " . $e->getMessage();
        echo "  ✗ {$label} (threw: " . get_class($e) . ")\n";
    }
}

// Helper: setar Router::$currentRouteNameSource via Reflection (prioridade sobre $GLOBALS)
$routerRc = new ReflectionClass(Router::class);
$routerSourceProp = $routerRc->getProperty('currentRouteNameSource');
$routerSourceProp->setAccessible(true);

function setRouterNameSource(?string $value): void
{
    global $routerSourceProp;
    $routerSourceProp->setValue(null, $value);
    $GLOBALS['current_route_name_source'] = $value;
}

// Cenário 1: routeName vazio → fallback critical, SEM throw
setRouterNameSource(null);
assert_build_safe([], '/admin/acme/financial/monthly', null, 'acme', 'build() with null routeName → fallback, no throw', 'critical');
assert_build_safe([], '/admin/acme/dashboard', '', 'acme', 'build() with empty routeName → fallback, no throw', 'critical');

// Cenário 2: business route com auto-name → warning, SEM throw
setRouterNameSource('auto');
assert_build_safe([], '/admin/acme/financial/monthly', 'financial.monthly', 'acme', 'build() business route auto-name → warning, no throw', 'warning');

// Cenário 3: CRUD route com auto-name → sem warning, SEM throw
setRouterNameSource('auto');
assert_build_safe([], '/admin/acme/orders', 'orders.index', 'acme', 'build() CRUD route auto-name → no warning, no throw', 'none');

// Cenário 4: business route com manual name → sem warning, SEM throw
setRouterNameSource('manual');
assert_build_safe([], '/admin/acme/financial/monthly', 'financial.monthly', 'acme', 'build() business route manual name → no warning, no throw', 'none');

// Limpar
setRouterNameSource(null);

// ── Resultado ───────────────────────────────────────────────────────────────

echo "\n" . str_repeat('─', 50) . "\n";
echo "Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";

if ($failed > 0) {
    echo "\nFailed tests:\n";
    foreach ($failures as $f) {
        echo "  • {$f}\n";
    }
    exit(1);
}

echo "\nAll tests passed.\n";
exit(0);
