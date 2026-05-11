<?php

declare(strict_types=1);

class RouteDefinition
{
    public string $method;
    public string $pattern;
    public $handler;
    public string $name;
    public bool $isExplicitName;

    public function __construct(string $method, string $pattern, $handler, string $name, bool $isExplicitName = false)
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->name = $name;
        $this->isExplicitName = $isExplicitName;
    }

    public function name(string $routeName): self
    {
        $this->name = trim($routeName);
        if ($this->name === '') {
            return $this;
        }

        $this->isExplicitName = true;
        Router::renameRoute($this->method, $this->pattern, $this->name, true);
        return $this;
    }
}

class Router
{
    private array $routes = ['GET' => [], 'POST' => [], 'HEAD' => []];
    private static ?string $currentRouteName = null;
    private static ?string $currentRoutePattern = null;
    private static ?string $currentRouteNameSource = null;
    private const CRUD_ACTIONS = ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'];
    private const SAFE_AUTO_ACTIONS = ['toggle', 'login', 'logout', 'manifest', 'dashboard', 'spa'];

    public static function currentRouteName(): ?string
    {
        return self::$currentRouteName;
    }

    public static function currentRoutePattern(): ?string
    {
        return self::$currentRoutePattern;
    }

    public static function currentRouteNameSource(): ?string
    {
        return self::$currentRouteNameSource;
    }

    public static function renameRoute(string $method, string $pattern, string $routeName, bool $explicitName = true): void
    {
        $method = strtoupper($method);
        $routeName = trim($routeName);

        if ($routeName === '') {
            return;
        }

        if (isset($GLOBALS['router']) && $GLOBALS['router'] instanceof self) {
            $GLOBALS['router']->setRouteName($method, $pattern, $routeName, $explicitName);
        }
    }

    public function get(string $pattern, $handler)
    {
        $name = $this->buildDefaultRouteName('GET', $pattern);
        $definition = [
            'handler' => $handler,
            'name' => $name,
            'pattern' => $pattern,
            'explicit_name' => false,
        ];

        $this->routes['GET'][$pattern] = $definition;
        // HEAD usa mesma rota que GET (padrão HTTP)
        $this->routes['HEAD'][$pattern] = $definition;

        return new RouteDefinition('GET', $pattern, $handler, $name, false);
    }

    public function post(string $pattern, $handler)
    {
        $name = $this->buildDefaultRouteName('POST', $pattern);
        $this->routes['POST'][$pattern] = [
            'handler' => $handler,
            'name' => $name,
            'pattern' => $pattern,
            'explicit_name' => false,
        ];

        return new RouteDefinition('POST', $pattern, $handler, $name, false);
    }

    private function setRouteName(string $method, string $pattern, string $routeName, bool $explicitName): void
    {
        if (!isset($this->routes[$method][$pattern])) {
            return;
        }

        if (!is_array($this->routes[$method][$pattern])) {
            $this->routes[$method][$pattern] = [
                'handler' => $this->routes[$method][$pattern],
                'name' => $routeName,
                'pattern' => $pattern,
                'explicit_name' => $explicitName,
            ];
            return;
        }

        $this->routes[$method][$pattern]['name'] = $routeName;
        $this->routes[$method][$pattern]['explicit_name'] = $explicitName;
    }

    private function requiresExplicitRouteName(string $routeName): bool
    {
        $parts = explode('.', strtolower(trim($routeName)), 2);
        $action = $parts[1] ?? 'index';

        if (in_array($action, self::CRUD_ACTIONS, true)) {
            return false;
        }

        return !in_array($action, self::SAFE_AUTO_ACTIONS, true);
    }

    private function warnAutoNameForBusinessRoute(string $method, string $pattern, string $routeName, string $uri): void
    {
        $context = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'route_name' => $routeName,
            'request_uri' => $uri,
            'rule' => 'Business routes MUST use explicit ->name(). Auto-name is fallback only for CRUD.',
        ];

        $env = strtolower((string)(function_exists('config') ? config('env', 'local') : 'local'));
        $isProduction = $env === 'production';

        if ($isProduction) {
            // Produção: log critical + continua (enforcement via sidebar UI)
            if (class_exists('Logger') && method_exists('Logger', 'error')) {
                Logger::error('Router: business route without explicit name in PRODUCTION', null, $context);
            } else {
                error_log('CRITICAL Router auto-name on business route: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            return;
        }

        // Dev/local: throw para forçar correção imediata
        throw new \RuntimeException(
            'Route "' . $routeName . '" (pattern: ' . $pattern . ') is a business route without explicit ->name(). '
            . 'Add ->name("' . $routeName . '") in routes/web.php. '
            . 'Auto-name is only allowed for CRUD actions (index/show/create/store/edit/update/destroy).'
        );
    }

    private function buildDefaultRouteName(string $method, string $pattern): string
    {
        $parts = array_values(array_filter(explode('/', trim($pattern, '/')), static function (string $part): bool {
            return $part !== '';
        }));

        if (isset($parts[0]) && $parts[0] === 'admin') {
            array_shift($parts);
            if (isset($parts[0]) && $this->isParamSegment($parts[0])) {
                array_shift($parts);
            }
        }

        $hasParam = count(array_filter($parts, function (string $part): bool {
            return $this->isParamSegment($part);
        })) > 0;

        $literalParts = array_values(array_filter($parts, function (string $part): bool {
            return !$this->isParamSegment($part);
        }));

        if (empty($literalParts)) {
            return 'home.index';
        }

        $module = $this->normalizeSegment($literalParts[0]);
        $rest = array_slice($literalParts, 1);

        $action = $this->inferAction($method, $rest, $hasParam);

        return $module . '.' . $action;
    }

    private function inferAction(string $method, array $rest, bool $hasParam): string
    {
        $normalizedRest = array_values(array_filter(array_map([$this, 'normalizeSegment'], $rest), static function (string $part): bool {
            return $part !== '';
        }));

        if (empty($normalizedRest)) {
            if ($method === 'POST') {
                return $hasParam ? 'update' : 'store';
            }

            return $hasParam ? 'show' : 'index';
        }

        $first = $normalizedRest[0];
        $actionMap = [
            'list' => 'index',
            'index' => 'index',
            'create' => 'create',
            'new' => 'create',
            'store' => 'store',
            'edit' => 'edit',
            'update' => 'update',
            'delete' => 'destroy',
            'del' => 'destroy',
            'destroy' => 'destroy',
            'show' => 'show',
            'view' => 'show',
            'toggle' => 'toggle',
            'status' => 'status',
            'kds' => 'kds',
            'api' => 'api',
            'history' => 'history',
            'monthly' => 'monthly',
            'yearly' => 'yearly',
            'settings' => 'settings',
            'poll' => 'poll',
            'sync' => 'sync',
            'test' => 'test',
            'print' => 'print',
            'manifest' => 'manifest',
            'login' => 'login',
            'logout' => 'logout',
        ];

        if (isset($actionMap[$first])) {
            return $actionMap[$first];
        }

        return implode('_', $normalizedRest);
    }

    private function normalizeSegment(string $segment): string
    {
        $segment = trim($segment);
        $segment = preg_replace('/\{[^}]+\}/', '', $segment);
        $segment = strtolower($segment);
        $segment = str_replace('-', '_', $segment);
        $segment = preg_replace('/[^a-z0-9_]+/', '_', $segment);
        $segment = trim((string)$segment, '_');

        return $segment === '' ? 'index' : $segment;
    }

    private function isParamSegment(string $segment): bool
    {
        return strlen($segment) > 2 && $segment[0] === '{' && substr($segment, -1) === '}';
    }

    private function match(string $pattern, string $uri)
    {
        // Suporta padrões customizados: {param:regex} ou {param}
        $regex = preg_replace_callback('#\{([^}]+)\}#', function($matches) {
            $param = $matches[1];
            
            // Verifica se tem padrão customizado: {path:.*}
            if (strpos($param, ':') !== false) {
                list($name, $pattern) = explode(':', $param, 2);
                return '(?P<' . $name . '>' . $pattern . ')';
            }
            
            // Padrão padrão: qualquer coisa exceto /
            return '(?P<' . $param . '>[^/]+)';
        }, $pattern);
        
        $regex = '#^' . rtrim($regex, '/') . '/?$#';

        if (preg_match($regex, $uri, $m)) {
            return array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return false;
    }

    public function dispatch($method, $uri)
    {
        $uri = rtrim($uri, '/') ?: '/';
        $method = strtoupper((string)$method);

        self::$currentRouteName = null;
        self::$currentRoutePattern = null;
        self::$currentRouteNameSource = null;
        $GLOBALS['current_route_name'] = null;
        $GLOBALS['current_route_pattern'] = null;
        $GLOBALS['current_route_name_source'] = null;

        // Se não há rotas para este método, evita erro
        if (!isset($this->routes[$method])) {
            http_response_code(404);
            echo '404';
            return;
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            if (($params = $this->match($pattern, $uri)) !== false) {
                $handler = is_array($route) && array_key_exists('handler', $route) ? $route['handler'] : $route;
                $routeName = is_array($route) && isset($route['name']) ? (string)$route['name'] : $this->buildDefaultRouteName($method, $pattern);
                $isExplicitName = is_array($route) && !empty($route['explicit_name']);

                self::$currentRouteName = $routeName;
                self::$currentRoutePattern = $pattern;
                self::$currentRouteNameSource = $isExplicitName ? 'manual' : 'auto';
                $GLOBALS['current_route_name'] = $routeName;
                $GLOBALS['current_route_pattern'] = $pattern;
                $GLOBALS['current_route_name_source'] = self::$currentRouteNameSource;

                if (!$isExplicitName && $this->requiresExplicitRouteName($routeName)) {
                    $this->warnAutoNameForBusinessRoute($method, $pattern, $routeName, $uri);
                }

                // handler "Controller@metodo" ou "App\\Controllers\\Public\\Foo@metodo"
                if (is_string($handler) && strpos($handler, '@') !== false) {
                    [$class, $controllerMethod] = explode('@', $handler, 2);
                    $class = trim($class);

                    if (str_contains($class, '\\')) {
                        if (!class_exists($class)) {
                            http_response_code(500);
                            echo 'Controller class não encontrada: ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

                            return;
                        }
                        $obj = new $class();
                    } else {
                        $file = __DIR__ . '/../controllers/' . $class . '.php';

                        if (!file_exists($file)) {
                            http_response_code(500);
                            echo "Controller file não encontrado: app/controllers/{$class}.php";

                            return;
                        }
                        require_once $file;

                        if (!class_exists($class)) {
                            http_response_code(500);
                            echo "Classe do controller não encontrada: {$class}";

                            return;
                        }
                        $obj = new $class();
                    }

                    return $obj->$controllerMethod($params);
                }

                // handler callable
                if (is_callable($handler)) {
                    return call_user_func($handler, $params);
                }
            }
        }

        http_response_code(404);
        echo '404';
    }
}
