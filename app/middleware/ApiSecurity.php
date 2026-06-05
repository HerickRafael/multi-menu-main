<?php

namespace App\Middleware;

use PDO;
use Exception;

/**
 * API Security Middleware
 * 
 * Provides comprehensive API security features:
 * - API Key authentication
 * - JWT token authentication
 * - OAuth2 basic support
 * - Rate limiting for APIs
 * - Request signing/validation
 * - CORS configuration
 * - API versioning
 * - Request/Response logging
 * 
 * OWASP Coverage:
 * - A01: Broken Access Control
 * - A02: Cryptographic Failures
 * - A07: Identification and Authentication Failures
 * 
 * CWE Coverage:
 * - CWE-287: Improper Authentication
 * - CWE-306: Missing Authentication
 * - CWE-798: Hard-coded Credentials
 * - CWE-863: Incorrect Authorization
 * 
 * @package App\Middleware
 * @author Multi-Menu Security Team
 * @version 1.0.0
 */
class ApiSecurity
{
    /**
     * PDO database connection
     */
    private ?PDO $pdo;

    /**
     * Configuration options
     */
    private array $config;

    /**
     * Supported authentication methods
     */
    private const AUTH_METHODS = [
        'api_key',
        'jwt',
        'oauth2',
        'basic',
        'bearer'
    ];

    /**
     * JWT algorithms
     */
    private const JWT_ALGORITHMS = [
        'HS256',
        'HS384',
        'HS512'
    ];

    /**
     * Statistics counters
     */
    private array $stats = [
        'requests_authenticated' => 0,
        'requests_denied' => 0,
        'api_keys_validated' => 0,
        'jwt_tokens_validated' => 0,
        'signatures_validated' => 0,
        'rate_limits_hit' => 0
    ];

    /**
     * Constructor
     * 
     * @param array $config Configuration options
     * @param PDO|null $pdo Database connection (optional)
     */
    public function __construct(array $config = [], ?PDO $pdo = null)
    {
        $this->pdo = $pdo;
        $defaultCorsOrigins = $this->resolveDefaultCorsOrigins();
        $this->config = array_merge([
            // Authentication
            'auth_methods' => ['api_key', 'jwt'], // Allowed methods
            'require_auth' => true, // Require authentication
            'auth_header' => 'Authorization', // Auth header name
            
            // API Keys
            'api_key_header' => 'X-API-Key', // API key header
            'api_key_param' => 'api_key', // Query param name
            'api_key_length' => 32, // Key length
            
            // JWT
            'jwt_secret' => null, // JWT secret (REQUIRED for JWT)
            'jwt_algorithm' => 'HS256', // Algorithm
            'jwt_expiration' => 3600, // Token expiration (1h)
            'jwt_issuer' => 'multi-menu', // Token issuer
            'jwt_audience' => 'multi-menu-api', // Token audience
            
            // Request Signing
            'require_signature' => false, // Require signed requests
            'signature_header' => 'X-Signature', // Signature header
            'signature_algorithm' => 'sha256', // Hash algorithm
            
            // Rate Limiting
            'rate_limit_enabled' => true, // Enable rate limiting
            'rate_limit_requests' => 100, // Requests per window
            'rate_limit_window' => 60, // Window in seconds (1 min)
            
            // CORS
            'cors_enabled' => true, // Enable CORS
            'cors_origins' => $defaultCorsOrigins, // Allowed origins
            'cors_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'cors_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
            'cors_credentials' => true, // Allow credentials
            'cors_max_age' => 86400, // Preflight cache (24h)
            
            // Versioning
            'versioning_enabled' => true, // Enable versioning
            'version_header' => 'X-API-Version', // Version header
            'default_version' => 'v1', // Default version
            'supported_versions' => ['v1', 'v2'], // Supported versions
            
            // Logging
            'log_requests' => true, // Log API requests
            'log_responses' => true, // Log API responses
            'log_errors_only' => false, // Log only errors
            
            // Security
            'enforce_https' => false, // Require HTTPS (disabled for development)
            'allowed_ips' => [], // IP whitelist (empty = all)
            'blocked_ips' => [], // IP blacklist
            'sanitize_input' => true, // Sanitize request data
        ], $config);
    }

    /**
     * Handle API request
     * 
     * Main entry point for API security middleware
     * 
     * @param array $request Request data
     * @return array Response with authenticated user data
     * @throws Exception On authentication failure
     */
    public function handle(array $request = []): array
    {
        try {
            // Merge with $_SERVER if request is empty
            if (empty($request)) {
                $request = [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                    'uri' => $_SERVER['REQUEST_URI'] ?? '/',
                    'headers' => $this->collectHeaders(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'body' => file_get_contents('php://input'),
                    'query' => $_GET ?? [],
                    'post' => $_POST ?? []
                ];
            }

            // Security checks
            $this->enforceHttps($request);
            $this->checkIpRestrictions($request);
            $this->handleCors($request);
            $this->handleVersioning($request);

            // Handle OPTIONS preflight
            if ($request['method'] === 'OPTIONS') {
                return [
                    'authenticated' => true,
                    'preflight' => true
                ];
            }

            // Rate limiting
            if ($this->config['rate_limit_enabled']) {
                $this->checkRateLimit($request);
            }

            // Authentication
            $authData = null;
            if ($this->config['require_auth']) {
                $authData = $this->authenticate($request);
            }

            // Request signature validation
            if ($this->config['require_signature']) {
                $this->validateSignature($request, $authData);
            }

            // Sanitize input
            if ($this->config['sanitize_input']) {
                $request = $this->sanitizeRequest($request);
            }

            // Log request
            if ($this->config['log_requests']) {
                $this->logRequest($request, $authData);
            }

            $this->stats['requests_authenticated']++;

            return [
                'authenticated' => true,
                'auth_data' => $authData,
                'request' => $request
            ];

        } catch (Exception $e) {
            $this->stats['requests_denied']++;
            
            if ($this->config['log_requests']) {
                $this->logError($request ?? [], $e);
            }

            throw $e;
        }
    }

    /**
     * Authenticate API request
     * 
     * Supports multiple authentication methods
     * 
     * @param array $request Request data
     * @return array Authentication data
     * @throws Exception On authentication failure
     */
    /**
     * Coleta os headers da requisição com acesso case-insensitive.
     *
     * Necessário porque clientes como Dart/Flutter enviam o nome do header em
     * minúsculas (ex.: "authorization"), e as checagens deste middleware usam
     * a forma canônica (ex.: "Authorization"); chaves de array em PHP são
     * case-sensitive. Aqui garantimos a forma canônica quando existir qualquer
     * variação de caixa.
     */
    private function collectHeaders(): array
    {
        $raw = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $headers = $raw;
        $lower = array_change_key_case($raw, CASE_LOWER);

        $canonical = [
            'Authorization' => 'authorization',
            'X-API-Key'     => 'x-api-key',
            'X-Signature'   => 'x-signature',
            'X-API-Version' => 'x-api-version',
            'Origin'        => 'origin',
            'Content-Type'  => 'content-type',
        ];
        foreach ($canonical as $proper => $low) {
            if (!isset($headers[$proper]) && isset($lower[$low])) {
                $headers[$proper] = $lower[$low];
            }
        }

        // Fallback extra: alguns setups expõem só em $_SERVER.
        if (!isset($headers['Authorization'])) {
            $fromServer = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? null;
            if ($fromServer) {
                $headers['Authorization'] = $fromServer;
            }
        }

        return $headers;
    }

    private function authenticate(array $request): array
    {
        $method = $this->detectAuthMethod($request);

        if (!$method) {
            throw new Exception('No authentication method detected', 401);
        }

        if (!in_array($method, $this->config['auth_methods'])) {
            throw new Exception("Authentication method '$method' not allowed", 401);
        }

        return match($method) {
            'api_key' => $this->authenticateApiKey($request),
            'jwt', 'bearer' => $this->authenticateJwt($request),
            'basic' => $this->authenticateBasic($request),
            'oauth2' => $this->authenticateOAuth2($request),
            default => throw new Exception("Unknown authentication method: $method", 401)
        };
    }

    /**
     * Detect authentication method from request
     * 
     * @param array $request Request data
     * @return string|null Authentication method
     */
    private function detectAuthMethod(array $request): ?string
    {
        $headers = $request['headers'] ?? [];

        // Check API key header
        if (isset($headers[$this->config['api_key_header']])) {
            return 'api_key';
        }

        // Check API key in query
        if (isset($request['query'][$this->config['api_key_param']])) {
            return 'api_key';
        }

        // Check Authorization header
        if (isset($headers[$this->config['auth_header']])) {
            $auth = $headers[$this->config['auth_header']];
            
            if (stripos($auth, 'Bearer ') === 0) {
                return 'bearer';
            }
            
            if (stripos($auth, 'Basic ') === 0) {
                return 'basic';
            }

            if (stripos($auth, 'OAuth ') === 0) {
                return 'oauth2';
            }
        }

        return null;
    }

    /**
     * Authenticate using API key
     * 
     * @param array $request Request data
     * @return array Authentication data
     * @throws Exception On authentication failure
     */
    private function authenticateApiKey(array $request): array
    {
        $headers = $request['headers'] ?? [];
        $query = $request['query'] ?? [];

        // Extract API key
        $apiKey = $headers[$this->config['api_key_header']] 
                  ?? $query[$this->config['api_key_param']] 
                  ?? null;

        if (!$apiKey) {
            throw new Exception('API key not provided', 401);
        }

        // Validate format
        if (strlen($apiKey) !== $this->config['api_key_length']) {
            throw new Exception('Invalid API key format', 401);
        }

        // Validate against database
        if ($this->pdo) {
            $stmt = $this->pdo->prepare("
                SELECT id, user_id, name, scopes, rate_limit, expires_at, last_used_at
                FROM api_keys
                WHERE key_hash = :key_hash
                AND is_active = 1
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute(['key_hash' => hash('sha256', $apiKey)]);
            $keyData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$keyData) {
                throw new Exception('Invalid API key', 401);
            }

            // Update last used
            $this->pdo->prepare("
                UPDATE api_keys 
                SET last_used_at = NOW(),
                    request_count = request_count + 1
                WHERE id = :id
            ")->execute(['id' => $keyData['id']]);

            $this->stats['api_keys_validated']++;

            $scopes = [];
            if ($keyData['scopes']) {
                if (is_string($keyData['scopes'])) {
                    $scopes = json_decode($keyData['scopes'], true) ?: [];
                } else {
                    // MySQL JSON column returns array directly
                    $scopes = $keyData['scopes'];
                }
            }

            return [
                'method' => 'api_key',
                'key_id' => $keyData['id'],
                'user_id' => $keyData['user_id'],
                'name' => $keyData['name'],
                'scopes' => $scopes,
                'rate_limit' => $keyData['rate_limit']
            ];
        }

        // No database - basic validation only
        $this->stats['api_keys_validated']++;
        
        return [
            'method' => 'api_key',
            'key' => $apiKey
        ];
    }

    /**
     * Authenticate using JWT token
     * 
     * @param array $request Request data
     * @return array Authentication data
     * @throws Exception On authentication failure
     */
    private function authenticateJwt(array $request): array
    {
        if (!$this->config['jwt_secret']) {
            throw new Exception('JWT authentication not configured', 500);
        }

        $headers = $request['headers'] ?? [];
        $auth = $headers[$this->config['auth_header']] ?? '';

        // Extract token
        if (!preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            throw new Exception('JWT token not provided', 401);
        }

        $token = $matches[1];

        // Validate and decode JWT
        $payload = $this->validateJwt($token);

        $this->stats['jwt_tokens_validated']++;

        return [
            'method' => 'jwt',
            'token' => $token,
            'payload' => $payload,
            'user_id' => $payload['sub'] ?? null,
            'scopes' => $payload['scopes'] ?? []
        ];
    }

    /**
     * Validate JWT token
     * 
     * @param string $token JWT token
     * @return array Decoded payload
     * @throws Exception On validation failure
     */
    private function validateJwt(string $token): array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format', 401);
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Decode header
        $header = json_decode($this->base64UrlDecode($headerB64), true);
        if (!$header) {
            throw new Exception('Invalid JWT header', 401);
        }

        // Check algorithm
        $algorithm = $header['alg'] ?? '';
        if ($algorithm !== $this->config['jwt_algorithm']) {
            throw new Exception('Unsupported JWT algorithm', 401);
        }

        // Verify signature
        $expectedSignature = $this->signJwt($headerB64 . '.' . $payloadB64, $algorithm);
        $actualSignature = $this->base64UrlDecode($signatureB64);

        if (!hash_equals($expectedSignature, $actualSignature)) {
            throw new Exception('Invalid JWT signature', 401);
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadB64), true);
        if (!$payload) {
            throw new Exception('Invalid JWT payload', 401);
        }

        // Validate claims
        $now = time();

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < $now) {
            throw new Exception('JWT token expired', 401);
        }

        // Check not before
        if (isset($payload['nbf']) && $payload['nbf'] > $now) {
            throw new Exception('JWT token not yet valid', 401);
        }

        // Check issuer
        if ($this->config['jwt_issuer'] && 
            isset($payload['iss']) && 
            $payload['iss'] !== $this->config['jwt_issuer']) {
            throw new Exception('Invalid JWT issuer', 401);
        }

        // Check audience
        if ($this->config['jwt_audience'] && 
            isset($payload['aud']) && 
            $payload['aud'] !== $this->config['jwt_audience']) {
            throw new Exception('Invalid JWT audience', 401);
        }

        return $payload;
    }

    /**
     * Generate JWT token
     * 
     * @param array $payload Token payload
     * @param int|null $expiration Expiration time (seconds from now)
     * @return string JWT token
     */
    public function generateJwt(array $payload, ?int $expiration = null): string
    {
        if (!$this->config['jwt_secret']) {
            throw new Exception('JWT secret not configured', 500);
        }

        $expiration = $expiration ?? $this->config['jwt_expiration'];
        $now = time();

        // Build payload
        $payload = array_merge([
            'iat' => $now, // Issued at
            'exp' => $now + $expiration, // Expiration
            'iss' => $this->config['jwt_issuer'], // Issuer
            'aud' => $this->config['jwt_audience'] // Audience
        ], $payload);

        // Build header
        $header = [
            'typ' => 'JWT',
            'alg' => $this->config['jwt_algorithm']
        ];

        // Encode
        $headerB64 = $this->base64UrlEncode(json_encode($header));
        $payloadB64 = $this->base64UrlEncode(json_encode($payload));

        // Sign
        $signature = $this->signJwt($headerB64 . '.' . $payloadB64, $this->config['jwt_algorithm']);
        $signatureB64 = $this->base64UrlEncode($signature);

        return $headerB64 . '.' . $payloadB64 . '.' . $signatureB64;
    }

    /**
     * Sign JWT data
     * 
     * @param string $data Data to sign
     * @param string $algorithm Algorithm
     * @return string Signature
     */
    private function signJwt(string $data, string $algorithm): string
    {
        $hashAlgorithm = match($algorithm) {
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
            default => throw new Exception("Unsupported algorithm: $algorithm", 500)
        };

        return hash_hmac($hashAlgorithm, $data, $this->config['jwt_secret'], true);
    }

    /**
     * Authenticate using HTTP Basic Auth
     * 
     * @param array $request Request data
     * @return array Authentication data
     * @throws Exception On authentication failure
     */
    private function authenticateBasic(array $request): array
    {
        $headers = $request['headers'] ?? [];
        $auth = $headers[$this->config['auth_header']] ?? '';

        if (!preg_match('/Basic\s+(.+)/i', $auth, $matches)) {
            throw new Exception('Basic auth credentials not provided', 401);
        }

        $credentials = base64_decode($matches[1]);
        if (!$credentials || !str_contains($credentials, ':')) {
            throw new Exception('Invalid Basic auth format', 401);
        }

        [$username, $password] = explode(':', $credentials, 2);

        // Validate against database
        if ($this->pdo) {
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, role, password_hash
                FROM users
                WHERE email = :email
                AND active = 1
            ");
            $stmt->execute([
                'email' => $username // Usa email como username
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                throw new Exception('Invalid credentials', 401);
            }

            return [
                'method' => 'basic',
                'user_id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
        }

        return [
            'method' => 'basic',
            'username' => $username
        ];
    }

    /**
     * Authenticate using OAuth2
     * 
     * Basic OAuth2 support (Bearer token)
     * 
     * @param array $request Request data
     * @return array Authentication data
     * @throws Exception On authentication failure
     */
    private function authenticateOAuth2(array $request): array
    {
        $headers = $request['headers'] ?? [];
        $auth = $headers[$this->config['auth_header']] ?? '';

        if (!preg_match('/OAuth\s+(.+)/i', $auth, $matches)) {
            throw new Exception('OAuth token not provided', 401);
        }

        $token = $matches[1];

        // Validate against database
        if ($this->pdo) {
            $stmt = $this->pdo->prepare("
                SELECT id, user_id, scopes, expires_at
                FROM oauth_tokens
                WHERE access_token = :token
                AND expires_at > datetime('now')
            ");
            $stmt->execute(['token' => hash('sha256', $token)]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                throw new Exception('Invalid OAuth token', 401);
            }

            return [
                'method' => 'oauth2',
                'token_id' => $tokenData['id'],
                'user_id' => $tokenData['user_id'],
                'scopes' => json_decode($tokenData['scopes'] ?? '[]', true)
            ];
        }

        return [
            'method' => 'oauth2',
            'token' => $token
        ];
    }

    /**
     * Validate request signature
     * 
     * @param array $request Request data
     * @param array|null $authData Authentication data
     * @throws Exception On validation failure
     */
    private function validateSignature(array $request, ?array $authData): void
    {
        $headers = $request['headers'] ?? [];
        $providedSignature = $headers[$this->config['signature_header']] ?? '';

        if (!$providedSignature) {
            throw new Exception('Request signature not provided', 401);
        }

        // Build signature data
        $signatureData = $this->buildSignatureData($request, $authData);

        // Get signing key
        $signingKey = $authData['signing_key'] ?? $this->config['jwt_secret'] ?? '';
        if (!$signingKey) {
            throw new Exception('Signing key not available', 500);
        }

        // Calculate expected signature
        $expectedSignature = hash_hmac(
            $this->config['signature_algorithm'],
            $signatureData,
            $signingKey
        );

        if (!hash_equals($expectedSignature, $providedSignature)) {
            throw new Exception('Invalid request signature', 401);
        }

        $this->stats['signatures_validated']++;
    }

    /**
     * Build signature data from request
     * 
     * @param array $request Request data
     * @param array|null $authData Authentication data
     * @return string Signature data
     */
    private function buildSignatureData(array $request, ?array $authData): string
    {
        $parts = [
            $request['method'] ?? '',
            $request['uri'] ?? '',
            $request['body'] ?? '',
            $authData['user_id'] ?? '',
            time() // Include timestamp for replay protection
        ];

        return implode('|', $parts);
    }

    /**
     * Check rate limiting
     * 
     * @param array $request Request data
     * @throws Exception If rate limit exceeded
     */
    private function checkRateLimit(array $request): void
    {
        if (!$this->pdo) {
            return; // Skip if no database
        }

        $ip = $request['ip'] ?? '127.0.0.1';
        $window = $this->config['rate_limit_window'];
        $limit = $this->config['rate_limit_requests'];

        // Get request count in window
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM api_requests
            WHERE ip_address = :ip
            AND created_at > DATE_SUB(NOW(), INTERVAL :window SECOND)
        ");
        $stmt->execute(['ip' => $ip, 'window' => $window]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] >= $limit) {
            $this->stats['rate_limits_hit']++;
            throw new Exception("Rate limit exceeded: {$limit} requests per {$window}s", 429);
        }
    }

    /**
     * Handle CORS
     * 
     * @param array $request Request data
     */
    private function handleCors(array $request): void
    {
        if (!$this->config['cors_enabled']) {
            return;
        }

        $origin = $request['headers']['Origin'] ?? '';
        if ($origin === '') {
            return;
        }
        
        // Check if origin is allowed
        $allowedOrigins = $this->config['cors_origins'];
        if (!in_array('*', $allowedOrigins, true) && !in_array($origin, $allowedOrigins, true)) {
            return; // Don't set CORS headers for disallowed origins
        }

        // Set CORS headers
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->config['cors_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->config['cors_headers']));
        
        if ($this->config['cors_credentials']) {
            header('Access-Control-Allow-Credentials: true');
        }
        
        header('Access-Control-Max-Age: ' . $this->config['cors_max_age']);
    }

    /**
     * Resolve safe default CORS origins from environment.
     *
     * Supports ALLOWED_ORIGINS as comma separated values.
     * Falls back to localhost development URLs.
     *
     * @return array<int, string>
     */
    private function resolveDefaultCorsOrigins(): array
    {
        $raw = (string)(getenv('ALLOWED_ORIGINS') ?: ($_ENV['ALLOWED_ORIGINS'] ?? ''));
        if ($raw !== '') {
            $origins = array_values(array_filter(array_map('trim', explode(',', $raw))));
            if (!empty($origins)) {
                return $origins;
            }
        }

        return [
            'http://localhost:8088',
            'http://127.0.0.1:8088',
        ];
    }

    /**
     * Handle API versioning
     * 
     * @param array $request Request data
     * @throws Exception If version not supported
     */
    private function handleVersioning(array &$request): void
    {
        if (!$this->config['versioning_enabled']) {
            return;
        }

        $headers = $request['headers'] ?? [];
        $version = $headers[$this->config['version_header']] ?? $this->config['default_version'];

        if (!in_array($version, $this->config['supported_versions'])) {
            throw new Exception("API version '$version' not supported", 400);
        }

        $request['api_version'] = $version;
    }

    /**
     * Enforce HTTPS
     * 
     * @param array $request Request data
     * @throws Exception If not HTTPS
     */
    private function enforceHttps(array $request): void
    {
        if (!$this->config['enforce_https']) {
            return;
        }

        $isHttps = ($request['headers']['X-Forwarded-Proto'] ?? '') === 'https'
                || ($_SERVER['HTTPS'] ?? '') === 'on'
                || ($_SERVER['SERVER_PORT'] ?? 0) == 443;

        if (!$isHttps) {
            throw new Exception('HTTPS required', 403);
        }
    }

    /**
     * Check IP restrictions
     * 
     * @param array $request Request data
     * @throws Exception If IP not allowed
     */
    private function checkIpRestrictions(array $request): void
    {
        $ip = $request['ip'] ?? '127.0.0.1';

        // Check blacklist
        if (in_array($ip, $this->config['blocked_ips'])) {
            throw new Exception('IP address blocked', 403);
        }

        // Check whitelist
        if (!empty($this->config['allowed_ips']) && !in_array($ip, $this->config['allowed_ips'])) {
            throw new Exception('IP address not allowed', 403);
        }
    }

    /**
     * Sanitize request data
     * 
     * @param array $request Request data
     * @return array Sanitized request
     */
    private function sanitizeRequest(array $request): array
    {
        if (isset($request['query'])) {
            $request['query'] = $this->sanitizeArray($request['query']);
        }

        if (isset($request['post'])) {
            $request['post'] = $this->sanitizeArray($request['post']);
        }

        return $request;
    }

    /**
     * Sanitize array recursively
     * 
     * @param array $data Data to sanitize
     * @return array Sanitized data
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } else {
                $data[$key] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            }
        }

        return $data;
    }

    /**
     * Log API request
     * 
     * @param array $request Request data
     * @param array|null $authData Authentication data
     */
    private function logRequest(array $request, ?array $authData): void
    {
        if (!$this->pdo) {
            return;
        }

        try {
            $this->pdo->prepare("
                INSERT INTO api_requests (
                    user_id, auth_method, endpoint, method,
                    ip_address, user_agent, request_data,
                    response_status, created_at
                ) VALUES (
                    :user_id, :auth_method, :endpoint, :method,
                    :ip, :user_agent, :request_data,
                    :status, NOW()
                )
            ")->execute([
                'user_id' => $authData['user_id'] ?? null,
                'auth_method' => $authData['method'] ?? null,
                'endpoint' => $request['uri'] ?? '',
                'method' => $request['method'] ?? '',
                'ip' => $request['ip'] ?? '',
                'user_agent' => $request['user_agent'] ?? '',
                'request_data' => json_encode($request['query'] ?? []),
                'status' => 200
            ]);
        } catch (Exception $e) {
            // Silent fail - don't break request if logging fails
            error_log("API request logging failed: " . $e->getMessage());
        }
    }

    /**
     * Log API error
     * 
     * @param array $request Request data
     * @param Exception $error Error
     */
    private function logError(array $request, Exception $error): void
    {
        if (!$this->pdo) {
            return;
        }

        try {
            $this->pdo->prepare("
                INSERT INTO api_errors (
                    endpoint, method, ip_address, user_agent,
                    error_message, error_code, request_data, created_at
                ) VALUES (
                    :endpoint, :method, :ip, :user_agent,
                    :message, :code, :request_data, NOW()
                )
            ")->execute([
                'endpoint' => $request['uri'] ?? '',
                'method' => $request['method'] ?? '',
                'ip' => $request['ip'] ?? '',
                'user_agent' => $request['user_agent'] ?? '',
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
                'request_data' => json_encode($request)
            ]);
        } catch (Exception $e) {
            error_log("API error logging failed: " . $e->getMessage());
        }
    }

    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Encoded data
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     * 
     * @param string $data Data to decode
     * @return string Decoded data
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Generate API key
     * 
     * @param int $userId User ID
     * @param string $name Key name
     * @param array $scopes Allowed scopes
     * @param int|null $expiresIn Expiration (seconds from now)
     * @return string API key
     */
    public function generateApiKey(int $userId, string $name, array $scopes = [], ?int $expiresIn = null): string
    {
        if (!$this->pdo) {
            throw new Exception('Database required for API key generation', 500);
        }

        // Generate random key
        $key = bin2hex(random_bytes($this->config['api_key_length'] / 2));
        $keyHash = hash('sha256', $key);

        // Calculate expiration
        $expiresAt = $expiresIn ? date('Y-m-d H:i:s', time() + $expiresIn) : null;

        // Store in database
        $this->pdo->prepare("
            INSERT INTO api_keys (
                user_id, name, key_hash, scopes,
                expires_at, created_at
            ) VALUES (
                :user_id, :name, :key_hash, :scopes,
                :expires_at, NOW()
            )
        ")->execute([
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => $keyHash,
            'scopes' => json_encode($scopes),
            'expires_at' => $expiresAt
        ]);

        return $key;
    }

    /**
     * Revoke API key
     * 
     * @param string $key API key
     * @return bool Success
     */
    public function revokeApiKey(string $key): bool
    {
        if (!$this->pdo) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE api_keys
            SET is_active = 0,
                revoked_at = NOW()
            WHERE key_hash = :key_hash
        ");

        return $stmt->execute(['key_hash' => hash('sha256', $key)]);
    }

    /**
     * Get statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
