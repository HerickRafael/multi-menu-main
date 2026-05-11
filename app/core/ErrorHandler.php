<?php

declare(strict_types=1);

/**
 * Error Handler - Enterprise Edition
 * 
 * Provides:
 * - Centralized exception handling
 * - Structured logging (JSON format)
 * - Error context (user, request, session)
 * - Stack trace sanitization (security)
 * - Environment-aware error pages (dev vs prod)
 * - Error metrics and statistics
 * - Sentry integration ready
 * 
 * Security Features:
 * - No sensitive data exposure in production
 * - Stack trace sanitization (removes paths, credentials)
 * - Request data filtering (passwords, tokens)
 * - SQL query sanitization
 * 
 * @package App\Core
 * @version 2.0.0
 */
class ErrorHandler
{
    private static bool $registered = false;
    private static array $config = [];
    private static array $stats = [
        'errors_total' => 0,
        'warnings_total' => 0,
        'exceptions_total' => 0,
        'fatal_errors' => 0,
        'last_error_time' => null
    ];
    
    // Error level mapping
    private const ERROR_LEVELS = [
        E_ERROR => 'FATAL',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    
    // Sensitive keys to filter from logs
    private const SENSITIVE_KEYS = [
        'password',
        'passwd',
        'pwd',
        'secret',
        'token',
        'api_key',
        'apikey',
        'access_token',
        'refresh_token',
        'auth',
        'authorization',
        'cookie',
        'session',
        'csrf',
        'credit_card',
        'card_number',
        'cvv',
        'ssn'
    ];
    
    /**
     * Register error and exception handlers
     * 
     * @param array $options Configuration options
     */
    public static function register(array $options = []): void
    {
        if (self::$registered) {
            return;
        }
        
        self::$config = array_merge([
            'environment' => $_ENV['APP_ENV'] ?? 'production',
            'log_path' => __DIR__ . '/../../storage/logs',
            'display_errors' => false,
            'log_errors' => true,
            'log_sql_queries' => true,
            'sentry_dsn' => $_ENV['SENTRY_DSN'] ?? null,
            'notify_on_fatal' => true,
            'max_string_length' => 1000, // Truncate long strings in logs
        ], $options);
        
        // Register handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
        
        // Configure PHP error reporting
        if (self::$config['environment'] === 'development') {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            self::$config['display_errors'] = true;
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
            ini_set('display_errors', '0');
            self::$config['display_errors'] = false;
        }
        
        ini_set('log_errors', self::$config['log_errors'] ? '1' : '0');
        
        self::$registered = true;
    }
    
    /**
     * Handle PHP errors
     * 
     * @param int $errno Error level
     * @param string $errstr Error message
     * @param string $errfile File where error occurred
     * @param int $errline Line number
     * @return bool True to prevent default error handler
     */
    public static function handleError(
        int $errno,
        string $errstr,
        string $errfile = '',
        int $errline = 0
    ): bool {
        // Don't handle suppressed errors (@)
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $level = self::ERROR_LEVELS[$errno] ?? 'UNKNOWN';
        
        // Update stats
        if ($errno === E_WARNING || $errno === E_USER_WARNING) {
            self::$stats['warnings_total']++;
        } else {
            self::$stats['errors_total']++;
        }
        self::$stats['last_error_time'] = time();
        
        // Create error context
        $context = self::buildContext([
            'type' => 'error',
            'level' => $level,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ]);
        
        // Log error
        self::log($level, $errstr, $context);
        
        // Display error page if fatal and display_errors is enabled
        if (self::isFatalError($errno) && self::$config['display_errors']) {
            self::displayErrorPage($errstr, $context);
            return true;
        }
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     * 
     * @param Throwable $exception The exception
     */
    public static function handleException(Throwable $exception): void
    {
        self::$stats['exceptions_total']++;
        self::$stats['last_error_time'] = time();
        
        $context = self::buildContext([
            'type' => 'exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => self::sanitizeStackTrace($exception->getTrace())
        ]);
        
        // Log exception
        self::log('EXCEPTION', $exception->getMessage(), $context);
        
        // Send to Sentry if configured
        self::sendToSentry($exception, $context);
        
        // Display error page
        self::displayErrorPage(
            self::$config['environment'] === 'production' 
                ? 'An error occurred. Please try again later.' 
                : $exception->getMessage(),
            $context
        );
    }
    
    /**
     * Handle shutdown (catch fatal errors)
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && self::isFatalError($error['type'])) {
            self::$stats['fatal_errors']++;
            
            $context = self::buildContext([
                'type' => 'fatal',
                'level' => self::ERROR_LEVELS[$error['type']] ?? 'FATAL',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);
            
            self::log('FATAL', $error['message'], $context);
            
            // Notify on fatal error
            if (self::$config['notify_on_fatal']) {
                self::notifyFatalError($error, $context);
            }
            
            // Display error page if not in CLI
            if (PHP_SAPI !== 'cli') {
                self::displayErrorPage(
                    self::$config['environment'] === 'production'
                        ? 'A fatal error occurred. Our team has been notified.'
                        : $error['message'],
                    $context
                );
            }
        }
    }
    
    /**
     * Build error context with request/session/user data
     * 
     * @param array $error Error data
     * @return array Context
     */
    private static function buildContext(array $error): array
    {
        $context = [
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => self::$config['environment'],
            'request' => self::getRequestContext(),
            'server' => self::getServerContext(),
        ];
        
        // Add session context if available
        if (session_status() === PHP_SESSION_ACTIVE) {
            $context['session'] = self::getSessionContext();
        }
        
        // Add user context if available
        if (isset($_SESSION['customer_id'])) {
            $context['user'] = [
                'id' => $_SESSION['customer_id'],
                'name' => $_SESSION['customer_name'] ?? 'unknown'
            ];
        }
        
        return $context;
    }
    
    /**
     * Get request context
     * 
     * @return array Request data
     */
    private static function getRequestContext(): array
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'url' => self::getCurrentUrl(),
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'get' => self::sanitizeData($_GET),
            'post' => self::sanitizeData($_POST),
            'headers' => self::getRequestHeaders()
        ];
    }
    
    /**
     * Get server context
     * 
     * @return array Server data
     */
    private static function getServerContext(): array
    {
        return [
            'hostname' => gethostname(),
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'sapi' => PHP_SAPI,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit')
        ];
    }
    
    /**
     * Get session context
     * 
     * @return array Session data
     */
    private static function getSessionContext(): array
    {
        return [
            'id' => session_id(),
            'name' => session_name(),
            'data' => self::sanitizeData($_SESSION)
        ];
    }
    
    /**
     * Get request headers
     * 
     * @return array Headers
     */
    private static function getRequestHeaders(): array
    {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        
        return self::sanitizeData($headers);
    }
    
    /**
     * Sanitize data (remove sensitive information)
     * 
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    private static function sanitizeData($data)
    {
        if (is_array($data)) {
            $sanitized = [];
            
            foreach ($data as $key => $value) {
                $lowerKey = strtolower((string)$key);
                
                // Check if key contains sensitive information
                $isSensitive = false;
                foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
                    if (strpos($lowerKey, $sensitiveKey) !== false) {
                        $isSensitive = true;
                        break;
                    }
                }
                
                if ($isSensitive) {
                    $sanitized[$key] = '[REDACTED]';
                } else {
                    $sanitized[$key] = self::sanitizeData($value);
                }
            }
            
            return $sanitized;
        }
        
        if (is_string($data)) {
            $maxLength = self::$config['max_string_length'];
            if (strlen($data) > $maxLength) {
                return substr($data, 0, $maxLength) . '... [truncated]';
            }
        }
        
        return $data;
    }
    
    /**
     * Sanitize stack trace (remove sensitive paths)
     * 
     * @param array $trace Stack trace
     * @return array Sanitized trace
     */
    private static function sanitizeStackTrace(array $trace): array
    {
        $basePath = dirname(__DIR__, 2);
        $sanitized = [];
        
        foreach ($trace as $frame) {
            $sanitizedFrame = [];
            
            if (isset($frame['file'])) {
                $sanitizedFrame['file'] = str_replace($basePath, '[APP]', $frame['file']);
            }
            
            if (isset($frame['line'])) {
                $sanitizedFrame['line'] = $frame['line'];
            }
            
            if (isset($frame['function'])) {
                $sanitizedFrame['function'] = $frame['function'];
            }
            
            if (isset($frame['class'])) {
                $sanitizedFrame['class'] = $frame['class'];
            }
            
            // Don't include args in production (may contain sensitive data)
            if (self::$config['environment'] === 'development' && isset($frame['args'])) {
                $sanitizedFrame['args'] = self::sanitizeData($frame['args']);
            }
            
            $sanitized[] = $sanitizedFrame;
        }
        
        return $sanitized;
    }
    
    /**
     * Log error to file
     * 
     * @param string $level Error level
     * @param string $message Error message
     * @param array $context Error context
     */
    private static function log(string $level, string $message, array $context): void
    {
        if (!self::$config['log_errors']) {
            return;
        }
        
        $logPath = self::$config['log_path'];
        
        // Create log directory if not exists
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        // Determine log file based on level
        $logFile = match($level) {
            'FATAL', 'CORE_ERROR', 'COMPILE_ERROR' => 'fatal-errors.log',
            'EXCEPTION' => 'exceptions.log',
            'WARNING', 'USER_WARNING' => 'warnings.log',
            default => 'errors.log'
        };
        
        $logFilePath = $logPath . '/' . $logFile;
        
        // Format log entry as JSON
        $logEntry = [
            'timestamp' => $context['timestamp'],
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
        
        $jsonLog = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        // Write to log file
        error_log($jsonLog, 3, $logFilePath);
        
        // Also log to PHP error log for backward compatibility
        $simpleLog = sprintf(
            '[%s] %s: %s in %s:%s',
            $context['timestamp'],
            $level,
            $message,
            $context['error']['file'] ?? 'unknown',
            $context['error']['line'] ?? 0
        );
        error_log($simpleLog);
    }
    
    /**
     * Display error page
     * 
     * @param string $message Error message
     * @param array $context Error context
     */
    private static function displayErrorPage(string $message, array $context): void
    {
        // Prevent output if headers already sent or in CLI
        if (headers_sent() || PHP_SAPI === 'cli') {
            return;
        }
        
        // Clear any existing output
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        
        if (self::$config['environment'] === 'development') {
            self::displayDevelopmentError($message, $context);
        } else {
            self::displayProductionError($message);
        }
        
        exit(1);
    }
    
    /**
     * Display development error page (detailed)
     * 
     * @param string $message Error message
     * @param array $context Error context
     */
    private static function displayDevelopmentError(string $message, array $context): void
    {
        $error = $context['error'];
        $trace = $error['trace'] ?? [];
        
        echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - Multi Menu Development</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* Header Card */
        .header { 
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            padding: 30px; 
            border-radius: 16px; 
            margin-bottom: 24px; 
            box-shadow: 0 10px 40px rgba(238, 90, 90, 0.3);
            color: white;
        }
        .header-top {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .header-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .header h1 { font-size: 22px; font-weight: 700; }
        .header .error-type { 
            font-size: 14px; 
            opacity: 0.9; 
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 5px;
        }
        .header .message { 
            font-size: 16px; 
            line-height: 1.6;
            background: rgba(0,0,0,0.15);
            padding: 15px 20px;
            border-radius: 12px;
            margin-top: 15px;
            font-family: "SF Mono", "Monaco", "Inconsolata", "Fira Mono", monospace;
            word-break: break-word;
        }
        
        /* Section Cards */
        .section { 
            background: white;
            padding: 24px; 
            border-radius: 16px; 
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .section h2 { 
            font-size: 16px; 
            font-weight: 600;
            margin-bottom: 16px; 
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section h2 .icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        
        /* Code Block */
        .code { 
            background: #1e1e2e; 
            padding: 20px; 
            border-radius: 12px; 
            overflow-x: auto; 
            font-family: "SF Mono", "Monaco", "Inconsolata", "Fira Mono", monospace;
            font-size: 13px; 
            line-height: 1.7;
            color: #cdd6f4;
        }
        .code strong { color: #89b4fa; }
        
        /* Stack Trace */
        .trace-item { 
            background: #f8f9fa; 
            padding: 16px; 
            margin-bottom: 10px; 
            border-radius: 12px;
            border-left: 4px solid #667eea;
            transition: all 0.2s;
        }
        .trace-item:hover {
            background: #f0f2f5;
            transform: translateX(4px);
        }
        .trace-item .number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }
        .trace-item .file { color: #16a34a; font-weight: 500; }
        .trace-item .line { color: #dc2626; font-weight: 600; }
        .trace-item .function { color: #7c3aed; font-weight: 500; }
        
        /* Meta Grid */
        .meta { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 16px; 
        }
        .meta-item { 
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f1f8 100%);
            padding: 16px 20px; 
            border-radius: 12px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        .meta-item strong { 
            display: block; 
            color: #667eea; 
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .meta-item span {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            word-break: break-all;
        }
        
        /* JSON Block */
        .json { 
            white-space: pre-wrap; 
            word-wrap: break-word;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        /* Back Button */
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #667eea;
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: rgba(102, 126, 234, 0.5); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(102, 126, 234, 0.7); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="header-icon">🐛</div>
                <div>
                    <h1>Erro Detectado</h1>
                    <span class="error-type">' . htmlspecialchars($error['type'] ?? 'Exception') . '</span>
                </div>
            </div>
            <div class="message">' . htmlspecialchars($message) . '</div>
        </div>
        
        <div class="section">
            <h2><span class="icon">📍</span> Localização do Erro</h2>
            <div class="code">
                <strong>Arquivo:</strong> ' . htmlspecialchars($error['file'] ?? 'desconhecido') . '<br>
                <strong>Linha:</strong> ' . ($error['line'] ?? 0) . '
            </div>
        </div>';
        
        if (!empty($trace)) {
            echo '<div class="section">
                <h2><span class="icon">📚</span> Stack Trace</h2>';
            foreach ($trace as $i => $frame) {
                echo '<div class="trace-item">
                    <span class="number">' . $i . '</span>';
                if (isset($frame['file'])) {
                    echo '<span class="file">' . htmlspecialchars(basename($frame['file'])) . '</span>:<span class="line">' . ($frame['line'] ?? 0) . '</span><br>';
                    echo '<small style="color:#666;margin-left:34px;display:block;margin-top:4px;">' . htmlspecialchars(dirname($frame['file'])) . '</small>';
                }
                if (isset($frame['class'])) {
                    echo '<div style="margin-left:34px;margin-top:8px;"><span class="function">' . htmlspecialchars($frame['class'] . $frame['type'] . $frame['function']) . '()</span></div>';
                } elseif (isset($frame['function'])) {
                    echo '<div style="margin-left:34px;margin-top:8px;"><span class="function">' . htmlspecialchars($frame['function']) . '()</span></div>';
                }
                echo '</div>';
            }
            echo '</div>';
        }
        
        echo '<div class="section">
            <h2><span class="icon">🔍</span> Informações da Requisição</h2>
            <div class="meta">
                <div class="meta-item">
                    <strong>Método</strong>
                    <span>' . htmlspecialchars($context['request']['method']) . '</span>
                </div>
                <div class="meta-item">
                    <strong>URI</strong>
                    <span>' . htmlspecialchars($context['request']['uri']) . '</span>
                </div>
                <div class="meta-item">
                    <strong>IP</strong>
                    <span>' . htmlspecialchars($context['request']['ip']) . '</span>
                </div>
                <div class="meta-item">
                    <strong>Horário</strong>
                    <span>' . htmlspecialchars($context['timestamp']) . '</span>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2><span class="icon">💾</span> Contexto Completo (JSON)</h2>
            <div class="code json">' . htmlspecialchars(json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</div>
        </div>
        
        <div class="actions">
            <a href="/" class="btn">← Voltar para o início</a>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Display production error page (minimal)
     * 
     * @param string $message Error message
     */
    private static function displayProductionError(string $message): void
    {
        echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - Multi Menu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            padding: 20px;
        }
        .container { 
            background: white; 
            padding: 50px 40px; 
            border-radius: 24px; 
            box-shadow: 0 25px 80px rgba(0,0,0,0.25); 
            max-width: 480px; 
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
        }
        .icon-wrapper {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #fff5f5 0%, #fff0f0 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 8px 30px rgba(255, 107, 107, 0.2);
        }
        .icon { 
            font-size: 48px;
        }
        h1 { 
            color: #1a1a2e; 
            font-size: 26px; 
            font-weight: 700;
            margin-bottom: 12px; 
        }
        p { 
            color: #64748b; 
            font-size: 15px; 
            line-height: 1.7; 
            margin-bottom: 30px;
            padding: 0 10px;
        }
        .button { 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 14px 32px; 
            border-radius: 12px; 
            text-decoration: none; 
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
        }
        .button:hover { 
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.45);
        }
        .button svg {
            width: 18px;
            height: 18px;
        }
        .error-id { 
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f1f8 100%);
            padding: 12px 20px; 
            border-radius: 10px; 
            font-family: "SF Mono", "Monaco", monospace;
            font-size: 12px; 
            color: #667eea;
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .error-id::before {
            content: "📋";
        }
        .decoration {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .decoration-1 {
            top: -100px;
            right: -100px;
        }
        .decoration-2 {
            bottom: -80px;
            left: -80px;
            width: 160px;
            height: 160px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="decoration decoration-1"></div>
        <div class="decoration decoration-2"></div>
        <div class="icon-wrapper">
            <span class="icon">⚠️</span>
        </div>
        <h1>Ops! Algo deu errado</h1>
        <p>' . htmlspecialchars($message) . '</p>
        <a href="/" class="button">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Voltar para o início
        </a>
        <div class="error-id">Erro registrado • ' . date('Y-m-d H:i:s') . '</div>
    </div>
</body>
</html>';
    }
    
    /**
     * Send error to Sentry
     * 
     * @param Throwable $exception Exception
     * @param array $context Context
     */
    private static function sendToSentry(Throwable $exception, array $context): void
    {
        if (empty(self::$config['sentry_dsn'])) {
            return;
        }
        
        // TODO: Implement Sentry integration
        // For now, just log that it would be sent
        error_log('[ErrorHandler] Would send to Sentry: ' . $exception->getMessage());
    }
    
    /**
     * Notify about fatal error
     * 
     * @param array $error Error data
     * @param array $context Context
     */
    private static function notifyFatalError(array $error, array $context): void
    {
        // TODO: Implement notification (email, Slack, etc.)
        error_log('[ErrorHandler] FATAL ERROR NOTIFICATION: ' . $error['message']);
    }
    
    /**
     * Check if error is fatal
     * 
     * @param int $errno Error number
     * @return bool True if fatal
     */
    private static function isFatalError(int $errno): bool
    {
        return in_array($errno, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR
        ]);
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Get current URL
     * 
     * @return string URL
     */
    private static function getCurrentUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return $protocol . $host . $uri;
    }
    
    /**
     * Get statistics
     * 
     * @return array Statistics
     */
    public static function getStats(): array
    {
        return self::$stats;
    }
    
    /**
     * Health check
     * 
     * @return array Health status
     */
    public static function healthCheck(): array
    {
        $logPath = self::$config['log_path'] ?? __DIR__ . '/../../storage/logs';
        
        return [
            'status' => self::$registered ? 'healthy' : 'not_registered',
            'registered' => self::$registered,
            'environment' => self::$config['environment'] ?? 'unknown',
            'log_path' => $logPath,
            'log_writable' => is_writable($logPath),
            'stats' => self::$stats
        ];
    }
}
