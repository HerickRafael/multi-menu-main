<?php

declare(strict_types=1);

namespace App\Services;

class SidebarService
{
    /**
     * Build sidebar data.
     * 
     * @param array $company Company data
     * @param string $currentPath Current URL path
     * @param string|null $routeName Route name
     * @param string $activeSlug Active company slug
     * @param array|null $userPermissions User permissions for filtering (null = show all)
     * @return array
     */
    public static function build(array $company, string $currentPath, ?string $routeName = null, string $activeSlug = '', ?array $userPermissions = null): array
    {
        $currentPath = parse_url($currentPath, PHP_URL_PATH) ?: '/';

        $companyId = (int)($company['id'] ?? 0);
        $companySlug = trim((string)($activeSlug !== '' ? $activeSlug : ($company['slug'] ?? '')), '/');
        $companyName = trim((string)($company['name'] ?? '')) ?: 'Admin';

        $baseData = self::loadBaseData($companyId);
        $hours = $baseData['hours'] ?? [];
        $bhStatus = StoreStatusService::get($hours);
        $hoursJson = json_encode($hours, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($hoursJson === false) {
            $hoursJson = '{}';
        }

        $resolvedRoute = self::resolveRouteName($routeName, $companySlug, $currentPath);
        if ($resolvedRoute === null) {
            return self::buildSafeSidebarFallback($companySlug, $companyName, $bhStatus, $hoursJson, $currentPath);
        }

        $routeNameSource = strtolower(trim((string)(function_exists('current_route_name_source') ? current_route_name_source() : 'auto')));
        $routeNeedsManualName = $routeNameSource !== 'manual' && self::requiresExplicitRouteName($resolvedRoute);

        // Severidade: warning = auto-name em rota de negócio, critical = nunca usado aqui (critical fica no fallback)
        $debugWarningLevel = $routeNeedsManualName ? 'warning' : null;
        $routeWarningMessage = $routeNeedsManualName
            ? 'Rota "' . $resolvedRoute . '" sem ->name() explicito. Use ->name("{modulo}.{acao}") em routes/web.php.'
            : null;

        if ($routeNeedsManualName) {
            $context = [
                'route_name' => $resolvedRoute,
                'route_name_source' => $routeNameSource,
                'company_slug' => $companySlug,
                'current_path' => $currentPath,
                'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            ];

            if (class_exists('Logger') && method_exists('Logger', 'warning')) {
                \Logger::warning('SidebarService detected business route without explicit route name', $context);
            } else {
                error_log('SidebarService business route naming warning: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        $context = self::resolveContext($resolvedRoute);

        $globalItems = self::filterByPermissions(self::globalItems(), $userPermissions);
        $contextualMap = self::contextualItems();
        $contextualRaw = self::filterByPermissions($contextualMap[$context] ?? [], $userPermissions);

        $globalNav = [];
        foreach ($globalItems as $item) {
            $url = self::sidebarUrl($companySlug, $item['url']);
            $isActive = self::isGlobalItemActive($item, $context);
            $globalNav[] = [
                'id' => $item['id'],
                'label' => $item['label'],
                'icon' => $item['icon'],
                'badge' => $item['badge'] ?? null,
                'url' => $url,
                'is_active' => $isActive,
                'is_current_context' => ($item['context'] ?? '') === $context,
            ];
        }

        $contextualNav = [];
        foreach ($contextualRaw as $item) {
            $url = self::sidebarUrl($companySlug, $item['url']);
            $contextualNav[] = [
                'label' => $item['label'],
                'icon' => $item['icon'],
                'url' => $url,
                'is_active' => self::isContextualItemActive($resolvedRoute, $item),
            ];
        }

        return [
            'companySlug' => $companySlug,
            'companyName' => $companyName,
            'currentPath' => $currentPath,
            'currentRoute' => $resolvedRoute,
            'context' => $context,
            'contextLabel' => self::contextLabels()[$context] ?? 'Opções',
            'globalItems' => $globalNav,
            'contextualItems' => $contextualNav,
            // Dinamico: status calculado por request (nao entra em cache de menu/estrutura)
            'sidebarBhStatus' => $bhStatus,
            'sidebarHoursJson' => $hoursJson,
            'dashboardUrl' => self::sidebarUrl($companySlug, '/dashboard'),
            'settingsUrl' => self::sidebarUrl($companySlug, '/settings'),
            'logoutUrl' => self::sidebarUrl($companySlug, '/logout'),
            'menuUrl' => base_url($companySlug),
            'debug_warning' => $routeNeedsManualName,
            'debug_warning_level' => $debugWarningLevel,
            'debug_warning_message' => $routeWarningMessage,
        ];
    }

    /**
     * Fallback de erro para o catch do layout — evita bloco hardcoded no PHP da view.
     *
     * @param string $companySlug Slug da empresa (usado para URLs de fallback)
     * @param string $companyName Nome da empresa
     * @param string $errorMessage Mensagem de erro resumida para exibir na sidebar
     * @return array $sidebarData pronto para renderização
     */
    public static function buildErrorFallback(string $companySlug, string $companyName, string $errorMessage): array
    {
        $bhStatus  = ['is_open' => false, 'current_time' => '--:--', 'today_hours' => 'Fechado hoje'];
        $fallback  = self::buildSafeSidebarFallback($companySlug, $companyName, $bhStatus, '{}', '/');
        $fallback['currentRoute']          = 'fallback.error';
        $fallback['debug_warning']         = true;
        $fallback['debug_warning_level']   = 'critical';
        $fallback['debug_warning_message'] = $errorMessage;
        return $fallback;
    }

    private static function buildSafeSidebarFallback(string $companySlug, string $companyName, array $bhStatus, string $hoursJson, string $currentPath): array
    {
        $globalNav = [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
                'url' => self::sidebarUrl($companySlug, '/dashboard'),
                'is_active' => true,
                'is_current_context' => true,
            ],
            [
                'id' => 'settings',
                'label' => 'Configurações',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
                'url' => self::sidebarUrl($companySlug, '/settings'),
                'is_active' => false,
                'is_current_context' => false,
            ],
        ];

        return [
            'companySlug' => $companySlug,
            'companyName' => $companyName,
            'currentPath' => $currentPath,
            'currentRoute' => 'fallback.dashboard',
            'context' => 'dashboard',
            'contextLabel' => 'Opções',
            'globalItems' => $globalNav,
            'contextualItems' => [],
            'sidebarBhStatus' => $bhStatus,
            'sidebarHoursJson' => $hoursJson,
            'dashboardUrl' => self::sidebarUrl($companySlug, '/dashboard'),
            'settingsUrl' => self::sidebarUrl($companySlug, '/settings'),
            'logoutUrl' => self::sidebarUrl($companySlug, '/logout'),
            'menuUrl' => base_url($companySlug),
            'debug_warning' => true,
            'debug_warning_level' => 'critical',
            'debug_warning_message' => 'CRITICAL: Sidebar em fallback — routeName canonico ausente. Corrija a rota com ->name("{modulo}.{acao}") em routes/web.php.',
        ];
    }

    private static function requiresExplicitRouteName(string $routeName): bool
    {
        $parts = explode('.', strtolower(trim($routeName)), 2);
        $action = $parts[1] ?? 'index';
        $crudActions = ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'];
        $safeAutoActions = ['toggle', 'login', 'logout', 'manifest'];

        if (in_array($action, $crudActions, true)) {
            return false;
        }

        return !in_array($action, $safeAutoActions, true);
    }

    /**
     * Filter menu items by user permissions.
     * 
     * Items without 'permission' key are always visible.
     * Items with 'permission' key are visible only if user has that permission.
     * 
     * @param array $items Menu items
     * @param array|null $userPermissions User permissions (null = show all, empty array = show only items without permission requirement)
     * @return array Filtered items
     */
    private static function filterByPermissions(array $items, ?array $userPermissions): array
    {
        // null = no filtering, show everything (backward compatible)
        if ($userPermissions === null) {
            return $items;
        }

        return array_values(array_filter($items, static function (array $item) use ($userPermissions): bool {
            // Items without permission requirement are always visible
            if (!isset($item['permission']) || $item['permission'] === null) {
                return true;
            }

            $required = $item['permission'];

            // Support array of permissions (any match = visible)
            if (is_array($required)) {
                foreach ($required as $perm) {
                    if (in_array($perm, $userPermissions, true)) {
                        return true;
                    }
                }
                return false;
            }

            // Single permission string
            return in_array($required, $userPermissions, true);
        }));
    }

    private static function loadBaseData(int $companyId): array
    {
        if ($companyId <= 0 || !class_exists('SmartCache') || !function_exists('db')) {
            return ['hours' => []];
        }

        $cacheKey = 'sidebar:hours:' . $companyId;

        $loader = static function () use ($companyId): array {
            return ['hours' => self::loadHoursFromDb($companyId)];
        };

        try {
            // Estrutura de horario muda pouco, pode cachear por 1h com seguranca
            return \SmartCache::remember($cacheKey, $loader, 3600);
        } catch (\Throwable $e) {
            error_log('SidebarService cache fallback: ' . $e->getMessage());
            return $loader();
        }
    }

    private static function loadHoursFromDb(int $companyId): array
    {
        $hours = [];

        try {
            $stmt = db()->prepare('SELECT weekday, is_open, open1, close1, open2, close2 FROM company_hours WHERE company_id = ? ORDER BY weekday');
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as $row) {
                $weekday = (int)($row['weekday'] ?? 0);
                if ($weekday >= 1 && $weekday <= 7) {
                    $hours[$weekday] = $row;
                }
            }
        } catch (\Throwable $e) {
            error_log('SidebarService loadHoursFromDb error: ' . $e->getMessage());
        }

        return $hours;
    }

    private static function resolveRouteName(?string $routeName, string $companySlug, string $currentPath): ?string
    {
        $routeName = trim((string)$routeName);
        if ($routeName !== '') {
            return strtolower($routeName);
        }

        $context = [
            'company_slug' => $companySlug,
            'current_path' => $currentPath,
            'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'current_route_pattern' => function_exists('current_route_pattern') ? current_route_pattern() : null,
        ];

        if (class_exists('Logger') && method_exists('Logger', 'error')) {
            \Logger::error('SidebarService missing canonical routeName', null, $context);
        } else {
            error_log('SidebarService missing canonical routeName: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return null;
    }

    private static function isProductionEnvironment(): bool
    {
        $env = strtolower((string)config('env', 'local'));
        return $env === 'production';
    }

    private static function resolveContext(string $routeName): string
    {
        $group = explode('.', $routeName, 2)[0] ?? 'dashboard';
        $aliases = [
            'dashboard' => 'dashboard',
            'orders' => 'orders',
            'kds' => 'orders',

            'financial' => 'financial',
            'expenses' => 'financial',
            'product_costs' => 'financial',
            'packaging' => 'financial',

            'products' => 'products',
            'categories' => 'products',
            'ingredients' => 'products',
            'cross_sell' => 'products',
            'cross_sell_groups' => 'products',
            'customization_templates' => 'products',

            'customers' => 'customers',

            'loyalty' => 'loyalty',
            'coupons' => 'loyalty',
            'loyalty_program' => 'loyalty',
            'loyalty_discount' => 'loyalty',

            'whatsapp' => 'whatsapp',
            'evolution' => 'whatsapp',

            'settings' => 'settings',
            'payment_methods' => 'settings',
            'delivery_fees' => 'settings',
            'api' => 'settings',
            'ifood' => 'settings',

            'analytics' => 'analytics',
        ];

        return $aliases[$group] ?? 'dashboard';
    }

    private static function routePrefixFromItem(array $item): string
    {
        $rawUrl = (string)($item['url'] ?? '/');
        $path = parse_url($rawUrl, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : $rawUrl;

        $segments = array_values(array_filter(explode('/', trim($path, '/')), static function (string $seg): bool {
            return $seg !== '';
        }));

        if (empty($segments)) {
            return 'dashboard';
        }

        $normalized = array_map(static function (string $seg): string {
            $seg = strtolower($seg);
            $seg = str_replace('-', '_', $seg);
            $seg = preg_replace('/[^a-z0-9_]+/', '_', $seg);
            return trim((string)$seg, '_');
        }, $segments);

        $normalized = array_values(array_filter($normalized, static function (string $seg): bool {
            return $seg !== '';
        }));

        if (empty($normalized)) {
            return 'dashboard';
        }

        return implode('.', $normalized);
    }

    private static function routeMatches(string $routeName, string $prefix): bool
    {
        if ($prefix === '') {
            return false;
        }

        return $routeName === $prefix || strpos($routeName, $prefix . '.') === 0;
    }

    private static function isGlobalItemActive(array $item, string $context): bool
    {
        if (!isset($item['context']) || !is_string($item['context'])) {
            return false;
        }

        return $item['context'] === $context;
    }

    private static function isContextualItemActive(string $routeName, array $item): bool
    {
        $prefix = self::routePrefixFromItem($item);

        // Rotas raiz de modulo (ex.: settings) devem ficar ativas em subrotas do modulo.
        if (substr_count($prefix, '.') === 0) {
            return self::routeMatches($routeName, $prefix);
        }

        return self::routeMatches($routeName, $prefix);
    }

    private static function sidebarUrl(string $slug, string $path): string
    {
        $slug = trim($slug, '/');
        $path = ltrim($path, '/');

        if ($slug === '') {
            return base_url('admin/' . $path);
        }

        return base_url('admin/' . rawurlencode($slug) . '/' . $path);
    }

    private static function contextLabels(): array
    {
        return [
            'orders' => 'Pedidos',
            'products' => 'Produtos',
            'financial' => 'Financeiro',
            'customers' => 'Clientes',
            'loyalty' => 'Fidelidade',
            'whatsapp' => 'WhatsApp',
            'settings' => 'Configurações',
            'analytics' => 'Analytics',
            'dashboard' => 'Opções',
        ];
    }

    private static function globalItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
                'url' => '/dashboard',
                'context' => 'dashboard',
                'permission' => null, // Always visible
            ],
            [
                'id' => 'orders',
                'label' => 'Pedidos',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />',
                'url' => '/orders',
                'badge' => null,
                'context' => 'orders',
                'permission' => 'view_orders',
            ],
            [
                'id' => 'products',
                'label' => 'Produtos',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />',
                'url' => '/products',
                'context' => 'products',
                'permission' => 'view_products',
            ],
            [
                'id' => 'analytics',
                'label' => 'Analytics',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
                'url' => '/analytics',
                'context' => 'analytics',
                'permission' => 'view_analytics',
            ],
            [
                'id' => 'financial',
                'label' => 'Financeiro',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />',
                'url' => '/financial',
                'context' => 'financial',
                'permission' => 'view_financial',
            ],
            [
                'id' => 'customers',
                'label' => 'Clientes',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
                'url' => '/customers',
                'context' => 'customers',
                'permission' => 'view_customers',
            ],
            [
                'id' => 'loyalty',
                'label' => 'Fidelidade',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />',
                'url' => '/loyalty-discount',
                'context' => 'loyalty',
                'permission' => 'view_loyalty',
            ],
            [
                'id' => 'settings',
                'label' => 'Configurações',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
                'url' => '/settings',
                'context' => 'settings',
                'permission' => 'view_settings',
            ],
        ];
    }

    private static function contextualItems(): array
    {
        return [
            'dashboard' => [],
            'orders' => [
                ['label' => 'Lista de Pedidos', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />', 'url' => '/orders'],
                ['label' => 'KDS (Cozinha)', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />', 'url' => '/kds'],
                ['label' => 'Novo Pedido', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />', 'url' => '/orders/create'],
            ],
            'products' => [
                ['label' => 'Todos os Produtos', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />', 'url' => '/products'],
                ['label' => 'Categorias', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />', 'url' => '/categories'],
                ['label' => 'Ingredientes', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />', 'url' => '/ingredients'],
                ['label' => 'Grupos Personalizações', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9z" />', 'url' => '/customization-templates'],
                ['label' => 'Cross-Sell', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />', 'url' => '/cross-sell-groups'],
                ['label' => 'Novo Produto', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />', 'url' => '/products/create'],
            ],
            'financial' => [
                ['label' => 'Visão Geral', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />', 'url' => '/financial'],
                ['label' => 'Mensal', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />', 'url' => '/financial/monthly'],
                ['label' => 'Anual', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />', 'url' => '/financial/yearly'],
                ['label' => 'Despesas', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />', 'url' => '/expenses'],
                ['label' => 'Cat. Despesas', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />', 'url' => '/expenses/categories'],
                ['label' => 'Custos Produtos', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z" />', 'url' => '/product-costs'],
                ['label' => 'Embalagens', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />', 'url' => '/packaging'],
                ['label' => 'Configurações', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />', 'url' => '/financial/settings'],
            ],
            'customers' => [
                ['label' => 'Lista de Clientes', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />', 'url' => '/customers'],
                ['label' => 'Novo Cliente', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766z" />', 'url' => '/customers/create'],
            ],
            'loyalty' => [
                ['label' => 'Fidelidade', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />', 'url' => '/loyalty-discount'],
                ['label' => 'Programa Fidelidade', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />', 'url' => '/loyalty-program'],
                ['label' => 'Cupons', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />', 'url' => '/coupons/create'],
            ],
            'whatsapp' => [
                ['label' => 'Instâncias', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />', 'url' => '/evolution'],
            ],
            'settings' => [
                ['label' => 'Geral', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />', 'url' => '/settings'],
                ['label' => 'Pagamentos', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />', 'url' => '/payment-methods'],
                ['label' => 'Taxas Entrega', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />', 'url' => '/delivery-fees'],
                ['label' => 'iFood', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />', 'url' => '/ifood/config'],
                ['label' => 'WhatsApp', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />', 'url' => '/evolution'],
                ['label' => 'API', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />', 'url' => '/api'],
            ],
            'analytics' => [
                ['label' => 'Visão Geral', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />', 'url' => '/analytics'],
            ],
        ];
    }
}
