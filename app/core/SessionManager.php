<?php

declare(strict_types=1);

/**
 * Session Manager - Enterprise Edition
 * 
 * Provides:
 * - Secure session configuration
 * - Redis-based session storage (scalable)
 * - Session fingerprinting (anti-hijacking)
 * - Automatic ID regeneration (anti-fixation)
 * - Session timeout management
 * - CSRF-ready session handling
 * 
 * Security Features:
 * - HttpOnly cookies (XSS protection)
 * - Secure cookies (HTTPS in production)
 * - SameSite cookies (CSRF protection)
 * - Strict mode (session fixation prevention)
 * - Fingerprint validation (hijacking prevention)
 * 
 * @package App\Core
 * @version 2.0.0
 */
class SessionManager
{
    private static bool $started = false;
    private static array $config = [];
    private static array $stats = [
        'regenerations' => 0,
        'hijack_attempts' => 0,
        'fingerprint_mismatches' => 0
    ];
    
    // Session lifetime configurations
    private const SESSION_LIFETIME = 2592000; // 30 dias
    private const REGENERATE_INTERVAL = 1800; // 30 minutos
    private const COOKIE_LIFETIME = 2592000; // 30 dias (manter login após fechar navegador)
    
    // Security thresholds
    private const MAX_FINGERPRINT_MISMATCHES = 3;
    
    /**
     * Initialize and start session with enterprise security
     * 
     * @param array $options Custom configuration options
     * @throws RuntimeException If session fails to start
     */
    public static function start(array $options = []): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Get environment variables with fallbacks
        $redisHost = getenv('REDIS_HOST') ?: ($_ENV['REDIS_HOST'] ?? 'redis_redis');
        $redisPort = getenv('REDIS_PORT') ?: ($_ENV['REDIS_PORT'] ?? 6379);
        $redisPassword = getenv('REDIS_PASSWORD') ?: ($_ENV['REDIS_PASSWORD'] ?? '');
        
        if (!class_exists('SecurityRequirements', false)) {
            require_once dirname(__DIR__) . '/config/SecurityRequirements.php';
        }

        self::$config = array_merge([
            'name' => getenv('SESSION_NAME') ?: ($_ENV['SESSION_NAME'] ?? 'MULTIMENU_SESS'),
            'use_redis' => extension_loaded('redis') && !empty($redisHost),
            'redis_host' => $redisHost,
            'redis_port' => (int)$redisPort,
            'redis_password' => $redisPassword,
            'environment' => getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production'),
            'app_key' => SecurityRequirements::resolveAppKey(),
        ], $options);
        
        // Configure session security settings
        self::configureSecuritySettings();
        
        // Configure Redis if available
        if (self::$config['use_redis']) {
            self::configureRedisStorage();
        }
        
        // Set custom session name
        session_name(self::$config['name']);
        
        // Start session
        if (!session_start()) {
            throw new RuntimeException('Failed to start session');
        }
        
        self::$started = true;
        
        // Security checks and maintenance
        self::initializeSession();
        self::regenerateIfNeeded();
        self::validateFingerprint();
        self::checkTimeout();
    }
    
    /**
     * Configure security settings for sessions
     */
    private static function configureSecuritySettings(): void
    {
        $isProduction = self::$config['environment'] === 'production';
        
        // Cookie security
        ini_set('session.cookie_httponly', '1'); // Previne XSS
        ini_set('session.cookie_secure', $isProduction ? '1' : '0'); // HTTPS only em produção
        ini_set('session.cookie_samesite', 'Lax'); // Previne CSRF
        ini_set('session.use_strict_mode', '1'); // Previne session fixation
        ini_set('session.use_only_cookies', '1'); // Não aceita session_id via URL
        
        // Session configuration
        ini_set('session.gc_maxlifetime', (string)self::SESSION_LIFETIME);
        ini_set('session.cookie_lifetime', (string)self::COOKIE_LIFETIME);
        
        // Garbage collection (performance)
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100'); // 1% de chance de GC
        
        // Use entropy for better session IDs (deprecated in PHP 7.1+, handled automatically)
        // PHP 7.1+ uses cryptographically secure random_bytes() automatically
        
        // Session ID configuration (PHP 7.1+ handles this automatically)
        // Removed deprecated ini settings: session.sid_length, session.sid_bits_per_character
    }
    
    /**
     * Configure Redis as session storage handler
     */
    private static function configureRedisStorage(): void
    {
        $host = self::$config['redis_host'];
        $port = self::$config['redis_port'];
        $password = self::$config['redis_password'];
        
        // Build Redis connection string
        $redisConfig = "tcp://{$host}:{$port}?database=1&prefix=sess:";
        
        // Only add auth parameter if password is not empty
        if (!empty($password)) {
            $redisConfig = "tcp://{$host}:{$port}?auth={$password}&database=1&prefix=sess:";
        }
        
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', $redisConfig);
        
        error_log('[SessionManager] Redis session storage configured: ' . $host . ':' . $port);
    }
    
    /**
     * Initialize session with required fields
     */
    private static function initializeSession(): void
    {
        if (!isset($_SESSION['CREATED_AT'])) {
            $_SESSION['CREATED_AT'] = time();
            $_SESSION['LAST_ACTIVITY'] = time();
        }
        
        if (!isset($_SESSION['USER_AGENT'])) {
            $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        if (!isset($_SESSION['REMOTE_ADDR'])) {
            $_SESSION['REMOTE_ADDR'] = self::getClientIP();
        }
    }
    
    /**
     * Regenerate session ID periodically to prevent fixation
     */
    private static function regenerateIfNeeded(): void
    {
        $now = time();
        $created = $_SESSION['CREATED_AT'] ?? $now;
        
        // Regenerar ID a cada 30 minutos
        if ($now - $created > self::REGENERATE_INTERVAL) {
            self::regenerate();
            $_SESSION['CREATED_AT'] = $now;
        }
    }
    
    /**
     * Validate session fingerprint to prevent hijacking
     */
    private static function validateFingerprint(): void
    {
        $currentFingerprint = self::generateFingerprint();
        
        if (!isset($_SESSION['FINGERPRINT'])) {
            // First time - set fingerprint
            $_SESSION['FINGERPRINT'] = $currentFingerprint;
            $_SESSION['FINGERPRINT_MISMATCHES'] = 0;
            return;
        }
        
        if ($_SESSION['FINGERPRINT'] !== $currentFingerprint) {
            // Possible session hijacking attempt
            self::$stats['hijack_attempts']++;
            self::$stats['fingerprint_mismatches']++;
            $_SESSION['FINGERPRINT_MISMATCHES'] = ($_SESSION['FINGERPRINT_MISMATCHES'] ?? 0) + 1;
            
            $context = [
                'session_id' => session_id(),
                'expected_fingerprint' => $_SESSION['FINGERPRINT'],
                'received_fingerprint' => $currentFingerprint,
                'ip_address' => self::getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'mismatches' => $_SESSION['FINGERPRINT_MISMATCHES']
            ];
            
            error_log('[SessionManager] SECURITY: Possible session hijacking attempt: ' . json_encode($context));
            
            // Se muitos mismatches, destruir sessão
            if ($_SESSION['FINGERPRINT_MISMATCHES'] >= self::MAX_FINGERPRINT_MISMATCHES) {
                error_log('[SessionManager] SECURITY: Session destroyed after ' . self::MAX_FINGERPRINT_MISMATCHES . ' fingerprint mismatches');
                self::destroy();
                self::start();
            } else {
                // Atualizar fingerprint (pode ser mudança legítima de rede)
                $_SESSION['FINGERPRINT'] = $currentFingerprint;
            }
        } else {
            // Reset mismatch counter em caso de sucesso
            $_SESSION['FINGERPRINT_MISMATCHES'] = 0;
        }
    }
    
    /**
     * Generate session fingerprint for security
     * 
     * @return string Fingerprint hash
     */
    private static function generateFingerprint(): string
    {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            // Não usar IP completo pois pode mudar legitimamente (mobile, VPN)
            // Usar apenas os primeiros 3 octetos para IPv4
            self::getClientIPPartial(),
            self::$config['app_key']
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Get partial client IP (first 3 octets for IPv4)
     * 
     * @return string Partial IP
     */
    private static function getClientIPPartial(): string
    {
        $ip = self::getClientIP();
        
        // Para IPv4, pegar apenas os primeiros 3 octetos
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return implode('.', array_slice($parts, 0, 3));
        }
        
        // Para IPv6, pegar prefixo
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4));
        }
        
        return $ip;
    }
    
    /**
     * Get real client IP address (even behind proxies)
     * 
     * @return string Client IP
     */
    private static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxies
            'HTTP_X_REAL_IP',        // Nginx
            'REMOTE_ADDR'            // Fallback
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Se for lista de IPs (X-Forwarded-For), pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validar IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Check session timeout and destroy if expired
     */
    private static function checkTimeout(): void
    {
        $lastActivity = $_SESSION['LAST_ACTIVITY'] ?? time();
        $now = time();
        
        if ($now - $lastActivity > self::SESSION_LIFETIME) {
            error_log('[SessionManager] Session timeout: ' . ($now - $lastActivity) . 's inactive');
            self::destroy();
            self::start();
        } else {
            $_SESSION['LAST_ACTIVITY'] = $now;
        }
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['CREATED_AT'] = time();
            self::$stats['regenerations']++;
        }
    }
    
    /**
     * Destroy current session
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            // Delete session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            session_destroy();
            self::$started = false;
        }
    }
    
    /**
     * Set session variable
     * 
     * @param string $key Variable name
     * @param mixed $value Variable value
     */
    public static function set(string $key, $value): void
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     * 
     * @param string $key Variable name
     * @param mixed $default Default value if not exists
     * @return mixed Variable value or default
     */
    public static function get(string $key, $default = null)
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session variable exists
     * 
     * @param string $key Variable name
     * @return bool True if exists
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     * 
     * @param string $key Variable name
     */
    public static function remove(string $key): void
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }
    
    /**
     * Flash message - set message that will be removed after next read
     * 
     * @param string $key Message key
     * @param mixed $value Message value
     */
    public static function flash(string $key, $value): void
    {
        self::ensureStarted();
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get flash message and remove it
     * 
     * @param string $key Message key
     * @param mixed $default Default value if not exists
     * @return mixed Message value or default
     */
    public static function getFlash(string $key, $default = null)
    {
        self::ensureStarted();
        
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }
        
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        
        return $value;
    }
    
    /**
     * Ensure session is started
     * 
     * @throws RuntimeException If session is not started
     */
    private static function ensureStarted(): void
    {
        if (!self::$started && session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
    }
    
    /**
     * Get session ID
     * 
     * @return string Session ID
     */
    public static function getId(): string
    {
        return session_id();
    }
    
    /**
     * Get session info
     * 
     * @return array Session information
     */
    public static function getInfo(): array
    {
        self::ensureStarted();
        
        return [
            'id' => session_id(),
            'name' => session_name(),
            'status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
            'created_at' => $_SESSION['CREATED_AT'] ?? null,
            'last_activity' => $_SESSION['LAST_ACTIVITY'] ?? null,
            'age_seconds' => isset($_SESSION['CREATED_AT']) ? time() - $_SESSION['CREATED_AT'] : 0,
            'idle_seconds' => isset($_SESSION['LAST_ACTIVITY']) ? time() - $_SESSION['LAST_ACTIVITY'] : 0,
            'fingerprint_mismatches' => $_SESSION['FINGERPRINT_MISMATCHES'] ?? 0,
            'storage_handler' => ini_get('session.save_handler'),
            'storage_path' => ini_get('session.save_path')
        ];
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
        try {
            self::ensureStarted();
            
            $info = self::getInfo();
            $isHealthy = $info['status'] === 'active' && $info['idle_seconds'] < self::SESSION_LIFETIME;
            
            return [
                'status' => $isHealthy ? 'healthy' : 'degraded',
                'session_active' => $info['status'] === 'active',
                'storage_handler' => $info['storage_handler'],
                'age_seconds' => $info['age_seconds'],
                'idle_seconds' => $info['idle_seconds'],
                'regenerations' => self::$stats['regenerations'],
                'hijack_attempts' => self::$stats['hijack_attempts']
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
}
