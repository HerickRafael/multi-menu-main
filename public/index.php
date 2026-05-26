<?php
// public/index.php

// Carrega o autoloader do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Carrega variáveis de ambiente do arquivo .env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

require_once __DIR__ . '/../app/config/SecurityRequirements.php';
SecurityRequirements::configurePhpErrorReporting();

// 🚀 Bootstrap centralizado - carrega todos os requires do sistema
require_once __DIR__ . '/../app/bootstrap.php';

// 📱 Detecção de subdomínio mobile (m.wollburger.online)
require_once __DIR__ . '/../app/middleware/SubdomainDetector.php';
SubdomainDetector::initialize();

// Serviços específicos de carrinho
require_once __DIR__ . '/../app/services/CartStorage.php';
require_once __DIR__ . '/../app/helpers/responsive_image_helper.php';
require_once __DIR__ . '/../app/middleware/TenantContextMiddleware.php';

// Registrar Error Handler Enterprise
ErrorHandler::register();

// Iniciar sessão com SessionManager Enterprise
SessionManager::start();

// Isolamento de tenant por rota/subdominio (admin/api/public)
$tenantContext = TenantContextMiddleware::applyFromRequest($_SERVER['REQUEST_URI'] ?? '/');
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

$strictCsp = filter_var($_ENV['STRICT_CSP'] ?? getenv('STRICT_CSP') ?: '1', FILTER_VALIDATE_BOOLEAN);
$cspPolicy = $strictCsp ? SecurityRequirements::contentSecurityPolicy() : null;

// 🔐 Security Headers — proteção OWASP contra XSS, clickjacking, MIME sniffing
\App\Middleware\SecurityHeaders::apply([
    'x_frame_options' => 'SAMEORIGIN',
    'csp' => $cspPolicy,
]);

// 🔐 Rate Limiter — proteção contra abuso
$globalRateId = 'ip:' . get_client_ip();
if (!\App\Middleware\RateLimiter::check($globalRateId, 1000, 60)) {
    http_response_code(429);
    $info = \App\Middleware\RateLimiter::getInfo($globalRateId);
    header('Retry-After: ' . max(1, $info['reset'] - time()));
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Too many requests. Try again later.']);
    exit;
}

$tenantRateId = 'tenant:' . (int)($tenantContext['company_id'] ?? 0);
if (!\App\Middleware\RateLimiter::check($tenantRateId, 10000, 60)) {
    http_response_code(429);
    $info = \App\Middleware\RateLimiter::getInfo($tenantRateId);
    header('Retry-After: ' . max(1, $info['reset'] - time()));
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Too many requests. Try again later.']);
    exit;
}

$loginRoute = str_contains($requestUri, '/customer-login') || (str_contains($requestUri, '/admin/') && str_contains($requestUri, '/login'));
if ($loginRoute) {
    $loginRateId = 'login:' . $tenantRateId . '|ip:' . get_client_ip() . '|uri:' . sha1($requestUri);
    if (!\App\Middleware\RateLimiter::check($loginRateId, 100, 60)) {
        http_response_code(429);
        $info = \App\Middleware\RateLimiter::getInfo($loginRateId);
        header('Retry-After: ' . max(1, $info['reset'] - time()));
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Too many login attempts. Try again later.']);
        exit;
    }
}

// 🔐 CSRF Protection — validar token em POST/PUT/DELETE/PATCH
// Exclui: webhooks, mobile-admin API, push, e API pública (token Bearer no host principal).
// No subdomínio mobile, /api/* usa sessão: CSRF permanece obrigatório.
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($requestMethod, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
    $csrfExcluded = (
        str_starts_with($requestUri, '/webhook/') ||
        str_starts_with($requestUri, '/mobile-admin/api/') ||
        str_starts_with($requestUri, '/push/')
    );
    if (!$csrfExcluded && str_starts_with($requestUri, '/api/')) {
        $csrfExcluded = !defined('IS_MOBILE_SUBDOMAIN') || !IS_MOBILE_SUBDOMAIN;
    }
    if (!$csrfExcluded) {
        \App\Middleware\CsrfProtection::validate(true);
    }
}

$router = new Router();
$GLOBALS['router'] = $router;

// (Opcional) Handlers de erro/404 se o Router suportar
// Router class doesn't support setNotFoundHandler method yet
// if (method_exists($router, 'setNotFoundHandler')) {
//   $router->setNotFoundHandler(function($uri){
//     http_response_code(404);
//     echo "Página não encontrada: " . htmlspecialchars((string)$uri, ENT_QUOTES, 'UTF-8');
//   });
// }

// Carrega as rotas (arquivo dedicado)
require_once __DIR__ . '/../routes/web.php';

// --- Normalização robusta da URI/base path ---
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Normalizar URI
$uri = '/' . ltrim((string)$uri, '/');
if ($uri === '' || $uri === false) {
    $uri = '/';
}

// Despacha
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$router->dispatch($method, $uri);
