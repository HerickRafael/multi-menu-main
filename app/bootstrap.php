<?php
/**
 * 🚀 BOOTSTRAP - Sistema Multi-Menu
 * 
 * Este arquivo centraliza todos os requires e inicializações do sistema,
 * eliminando duplicações de require_once nos controllers.
 * 
 * Uso: require_once __DIR__ . '/../bootstrap.php';
 * 
 * @version 1.0
 */

declare(strict_types=1);

// Prevenir inclusão múltipla
if (defined('MULTIMENU_BOOTSTRAP_LOADED')) {
    return;
}
define('MULTIMENU_BOOTSTRAP_LOADED', true);

// Definir diretório base da aplicação
if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__);
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// ============================================================================
// 📦 VENDOR AUTOLOAD - Composer autoloader
// ============================================================================

$vendorAutoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// ============================================================================
// ⏰ TIMEZONE - Configurar timezone global (Horário de Brasília)
// ============================================================================
// IMPORTANTE: Isso deve ser feito ANTES de qualquer operação com datas
// para garantir consistência entre PHP e MySQL
date_default_timezone_set('America/Sao_Paulo');

// ============================================================================
// 🔧 CORE - Arquivos fundamentais do sistema
// ============================================================================

require_once APP_PATH . '/config/SecurityRequirements.php';
require_once APP_PATH . '/config/app.php';
SecurityRequirements::assertProductionSecrets();

require_once APP_PATH . '/config/db.php';
require_once APP_PATH . '/config/FormatConstants.php';

require_once APP_PATH . '/core/Helpers.php';
require_once APP_PATH . '/core/CommonHelpers.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/SessionManager.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/AuthCustomer.php';
require_once APP_PATH . '/core/Router.php';
require_once APP_PATH . '/core/ErrorHandler.php';

// ============================================================================
// 📦 MODELS - Modelos de dados
// ============================================================================

require_once APP_PATH . '/models/Company.php';
require_once APP_PATH . '/models/SuperAdmin.php';
require_once APP_PATH . '/models/User.php';
require_once APP_PATH . '/models/Category.php';
require_once APP_PATH . '/models/Product.php';
require_once APP_PATH . '/models/Order.php';
require_once APP_PATH . '/models/Customer.php';
require_once APP_PATH . '/models/CustomerAddress.php';
require_once APP_PATH . '/models/Ingredient.php';
require_once APP_PATH . '/models/PaymentMethod.php';
require_once APP_PATH . '/models/LoyaltyProgram.php';

// ============================================================================
// 🛡️ HELPERS - Funções auxiliares
// ============================================================================

require_once APP_PATH . '/helpers/Logger.php';
require_once APP_PATH . '/helpers/MoneyFormatter.php';
require_once APP_PATH . '/helpers/JsonHelper.php';
require_once APP_PATH . '/helpers/DataValidator.php';
require_once APP_PATH . '/helpers/session_security.php';
require_once APP_PATH . '/helpers/payment_brand_helper.php';

// ============================================================================
// 🔐 MIDDLEWARE - Segurança e validações (opcional, carregar sob demanda)
// ============================================================================

/**
 * Carrega middleware específico sob demanda
 * 
 * @param string $name Nome do middleware (sem .php)
 * @return void
 */
function load_middleware(string $name): void
{
    $path = APP_PATH . '/middleware/' . $name . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
}

// ============================================================================
// 🎨 COMPONENTES UI - Funções para renderizar componentes HTML
// ============================================================================

/**
 * Renderiza um componente HTML
 * 
 * @param string $name Nome do componente
 * @param array $data Dados para o componente
 * @return string HTML renderizado
 */
function component(string $name, array $data = []): string
{
    $path = APP_PATH . '/views/admin/components/' . $name . '.php';
    
    if (!file_exists($path)) {
        return "<!-- Componente não encontrado: {$name} -->";
    }
    
    extract($data);
    ob_start();
    include $path;
    return ob_get_clean();
}

/**
 * Renderiza e exibe um componente HTML
 * 
 * @param string $name Nome do componente
 * @param array $data Dados para o componente
 * @return void
 */
function render_component(string $name, array $data = []): void
{
    echo component($name, $data);
}

// ============================================================================
// 🔧 FUNÇÕES UTILITÁRIAS GLOBAIS
// ============================================================================

/**
 * Obtém configuração do sistema
 * 
 * @param string $key Chave da configuração
 * @param mixed $default Valor padrão
 * @return mixed
 */
if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        global $config;
        return $config[$key] ?? $default;
    }
}

/**
 * Formata bytes para unidade legível
 * 
 * @param int $bytes Tamanho em bytes
 * @param int $precision Casas decimais
 * @return string Tamanho formatado (ex: 1.5 MB)
 */
if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

/**
 * Gera resposta JSON padronizada
 * 
 * @param mixed $data Dados da resposta
 * @param int $statusCode Código HTTP
 * @param array $headers Headers adicionais
 * @return void
 */
if (!function_exists('json_response')) {
    function json_response($data, int $statusCode = 200, array $headers = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

/**
 * Gera resposta de sucesso JSON
 * 
 * @param mixed $data Dados
 * @param string $message Mensagem
 * @return void
 */
if (!function_exists('json_success')) {
    function json_success($data = null, string $message = 'Sucesso'): void
    {
        json_response([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
}

/**
 * Gera resposta de erro JSON
 * 
 * @param string $message Mensagem de erro
 * @param int $statusCode Código HTTP
 * @param array $errors Erros detalhados
 * @return void
 */
if (!function_exists('json_error')) {
    function json_error(string $message, int $statusCode = 400, array $errors = []): void
    {
        json_response([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
}

/**
 * Obtém IP do cliente
 * 
 * @return string
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Proxy padrão
            'HTTP_CLIENT_IP',            // Cliente direto
            'REMOTE_ADDR'                // Fallback
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For pode ter múltiplos IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
}

if (!function_exists('current_route_name')) {
    function current_route_name(): ?string
    {
        if (class_exists('Router') && method_exists('Router', 'currentRouteName')) {
            return Router::currentRouteName();
        }

        return isset($GLOBALS['current_route_name']) ? (string)$GLOBALS['current_route_name'] : null;
    }
}

if (!function_exists('current_route_pattern')) {
    function current_route_pattern(): ?string
    {
        if (class_exists('Router') && method_exists('Router', 'currentRoutePattern')) {
            return Router::currentRoutePattern();
        }

        return isset($GLOBALS['current_route_pattern']) ? (string)$GLOBALS['current_route_pattern'] : null;
    }
}

if (!function_exists('current_route_name_source')) {
    function current_route_name_source(): ?string
    {
        if (class_exists('Router') && method_exists('Router', 'currentRouteNameSource')) {
            return Router::currentRouteNameSource();
        }

        return isset($GLOBALS['current_route_name_source']) ? (string)$GLOBALS['current_route_name_source'] : null;
    }
}

/**
 * Verifica se a requisição é AJAX
 * 
 * @return bool
 */
if (!function_exists('is_ajax')) {
    function is_ajax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

/**
 * Redireciona com mensagem flash
 * 
 * @param string $url URL de destino
 * @param string $message Mensagem
 * @param string $type Tipo (success, error, warning, info)
 * @return void
 */
if (!function_exists('redirect_with_message')) {
    function redirect_with_message(string $url, string $message, string $type = 'success'): void
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        header("Location: $url");
        exit;
    }
}

/**
 * Obtém e limpa mensagem flash
 * 
 * @return array|null ['message' => string, 'type' => string]
 */
if (!function_exists('get_flash_message')) {
    function get_flash_message(): ?array
    {
        if (isset($_SESSION['flash_message'])) {
            $flash = [
                'message' => $_SESSION['flash_message'],
                'type' => $_SESSION['flash_type'] ?? 'info'
            ];
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return $flash;
        }
        return null;
    }
}

/**
 * Debug helper - dump and die
 * 
 * @param mixed ...$vars Variáveis para debug
 * @return void
 */
if (!function_exists('dd')) {
    function dd(...$vars): void
    {
        echo '<pre style="background:#1e293b;color:#e2e8f0;padding:1rem;border-radius:0.5rem;overflow:auto;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n---\n";
        }
        echo '</pre>';
        exit;
    }
}

/**
 * Debug helper - dump
 * 
 * @param mixed ...$vars Variáveis para debug
 * @return void
 */
if (!function_exists('dump')) {
    function dump(...$vars): void
    {
        echo '<pre style="background:#1e293b;color:#e2e8f0;padding:1rem;border-radius:0.5rem;overflow:auto;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n---\n";
        }
        echo '</pre>';
    }
}
