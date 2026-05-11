<?php

namespace App\Middleware;

/**
 * Security Headers Middleware
 * 
 * Configura headers de segurança HTTP para proteger contra diversos ataques.
 * Implementação enterprise-level seguindo as melhores práticas OWASP.
 * 
 * Proteções implementadas:
 * - XSS (Cross-Site Scripting)
 * - Clickjacking
 * - MIME Sniffing
 * - Drive-by Downloads
 * - Protocol Downgrade
 * - Cookie Hijacking
 * 
 * Uso:
 * 
 * // Aplicar headers padrão
 * SecurityHeaders::apply();
 * 
 * // Aplicar com configuração customizada
 * SecurityHeaders::apply([
 *     'csp' => "default-src 'self'",
 *     'hsts' => true
 * ]);
 * 
 * @link https://owasp.org/www-project-secure-headers/
 */
class SecurityHeaders
{
    /** @var array Configuração padrão de headers */
    private const DEFAULT_CONFIG = [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
        'hsts' => true,
        'hsts_max_age' => 31536000,  // 1 ano
        'hsts_include_subdomains' => true,
        'hsts_preload' => false,
        'csp' => null,  // Configurar por aplicação
        'expect_ct' => true,
        'expect_ct_max_age' => 86400,  // 24 horas
    ];
    
    /** @var array Headers já aplicados (previne duplicação) */
    private static array $appliedHeaders = [];
    
    /** @var bool Se headers já foram enviados */
    private static bool $headersSent = false;
    
    /**
     * Aplica todos os security headers
     * 
     * @param array $config Configuração customizada
     * @return bool True se aplicado com sucesso
     */
    public static function apply(array $config = []): bool
    {
        // Prevenir aplicação duplicada
        if (self::$headersSent) {
            // Logger removed
            return false;
        }
        
        // Merge com configuração padrão
        $config = array_merge(self::DEFAULT_CONFIG, $config);
        
        // Aplicar cada header
        self::applyXFrameOptions($config['x_frame_options']);
        self::applyXContentTypeOptions($config['x_content_type_options']);
        self::applyXXssProtection($config['x_xss_protection']);
        self::applyReferrerPolicy($config['referrer_policy']);
        self::applyPermissionsPolicy($config['permissions_policy']);
        
        // HSTS (apenas em HTTPS)
        if ($config['hsts'] && self::isHttps()) {
            self::applyHsts(
                $config['hsts_max_age'],
                $config['hsts_include_subdomains'],
                $config['hsts_preload']
            );
        }
        
        // CSP (se configurado)
        if (!empty($config['csp'])) {
            self::applyContentSecurityPolicy($config['csp']);
        }
        
        // Expect-CT (apenas em HTTPS)
        if ($config['expect_ct'] && self::isHttps()) {
            self::applyExpectCt($config['expect_ct_max_age']);
        }
        
        self::$headersSent = true;
        
        // Logger removed
        
        return true;
    }
    
    /**
     * X-Frame-Options - Previne Clickjacking
     * 
     * @param string $value DENY | SAMEORIGIN | ALLOW-FROM uri
     */
    public static function applyXFrameOptions(string $value = 'DENY'): void
    {
        self::setHeader('X-Frame-Options', $value);
    }
    
    /**
     * X-Content-Type-Options - Previne MIME Sniffing
     * 
     * @param string $value nosniff
     */
    public static function applyXContentTypeOptions(string $value = 'nosniff'): void
    {
        self::setHeader('X-Content-Type-Options', $value);
    }
    
    /**
     * X-XSS-Protection - Proteção XSS (legacy, mas ainda útil)
     * 
     * @param string $value 0 | 1 | 1; mode=block
     */
    public static function applyXXssProtection(string $value = '1; mode=block'): void
    {
        self::setHeader('X-XSS-Protection', $value);
    }
    
    /**
     * Referrer-Policy - Controla informações de referrer
     * 
     * @param string $value no-referrer | strict-origin-when-cross-origin | etc
     */
    public static function applyReferrerPolicy(string $value = 'strict-origin-when-cross-origin'): void
    {
        self::setHeader('Referrer-Policy', $value);
    }
    
    /**
     * Permissions-Policy (Feature-Policy) - Controla features do browser
     * 
     * @param string $value Ex: geolocation=(), microphone=(), camera=()
     */
    public static function applyPermissionsPolicy(string $value): void
    {
        self::setHeader('Permissions-Policy', $value);
    }
    
    /**
     * Strict-Transport-Security (HSTS) - Força HTTPS
     * 
     * @param int $maxAge Tempo em segundos
     * @param bool $includeSubDomains Aplicar em subdomínios
     * @param bool $preload Incluir na preload list
     */
    public static function applyHsts(
        int $maxAge = 31536000,
        bool $includeSubDomains = true,
        bool $preload = false
    ): void {
        if (!self::isHttps()) {
            // Logger removed
            return;
        }
        
        $value = "max-age={$maxAge}";
        
        if ($includeSubDomains) {
            $value .= "; includeSubDomains";
        }
        
        if ($preload) {
            $value .= "; preload";
        }
        
        self::setHeader('Strict-Transport-Security', $value);
    }
    
    /**
     * Content-Security-Policy - Previne XSS e outros ataques
     * 
     * @param string $policy Política CSP
     */
    public static function applyContentSecurityPolicy(string $policy): void
    {
        self::setHeader('Content-Security-Policy', $policy);
    }
    
    /**
     * Expect-CT - Certificate Transparency
     * 
     * @param int $maxAge Tempo em segundos
     * @param bool $enforce Enforçar ou apenas reportar
     * @param string|null $reportUri URI para reportar violações
     */
    public static function applyExpectCt(
        int $maxAge = 86400,
        bool $enforce = true,
        ?string $reportUri = null
    ): void {
        if (!self::isHttps()) {
            return;
        }
        
        $value = "max-age={$maxAge}";
        
        if ($enforce) {
            $value .= ", enforce";
        }
        
        if ($reportUri) {
            $value .= ", report-uri=\"{$reportUri}\"";
        }
        
        self::setHeader('Expect-CT', $value);
    }
    
    /**
     * Remove header de servidor (oculta versão PHP/Apache)
     */
    public static function removeServerHeader(): void
    {
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
            @header_remove('Server');
        }
    }
    
    /**
     * Aplica headers para prevenir caching de dados sensíveis
     */
    public static function applyNoCacheHeaders(): void
    {
        self::setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        self::setHeader('Pragma', 'no-cache');
        self::setHeader('Expires', '0');
    }
    
    /**
     * Aplica headers de CORS
     * 
     * @param string|array $allowedOrigins Origens permitidas
     * @param array $allowedMethods Métodos permitidos
     * @param array $allowedHeaders Headers permitidos
     * @param bool $allowCredentials Permitir credenciais
     * @param int $maxAge Tempo de cache
     */
    public static function applyCors(
        $allowedOrigins = '*',
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-CSRF-TOKEN'],
        bool $allowCredentials = true,
        int $maxAge = 86400
    ): void {
        // Origin
        if (is_array($allowedOrigins)) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($origin, $allowedOrigins)) {
                self::setHeader('Access-Control-Allow-Origin', $origin);
            }
        } else {
            self::setHeader('Access-Control-Allow-Origin', $allowedOrigins);
        }
        
        // Métodos
        self::setHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        
        // Headers
        self::setHeader('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));
        
        // Credenciais
        if ($allowCredentials) {
            self::setHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        // Max Age
        self::setHeader('Access-Control-Max-Age', (string)$maxAge);
    }
    
    /**
     * Configuração de CSP para diferentes ambientes
     */
    public static function getRecommendedCsp(string $environment = 'production'): string
    {
        $policies = [
            'strict' => "default-src 'none'; " .
                       "script-src 'self'; " .
                       "style-src 'self'; " .
                       "img-src 'self' data:; " .
                       "font-src 'self'; " .
                       "connect-src 'self'; " .
                       "frame-ancestors 'none'; " .
                       "base-uri 'self'; " .
                       "form-action 'self'",
            
            'moderate' => "default-src 'self'; " .
                         "script-src 'self' 'unsafe-inline'; " .
                         "style-src 'self' 'unsafe-inline'; " .
                         "img-src 'self' data: https:; " .
                         "font-src 'self' data:; " .
                         "connect-src 'self'; " .
                         "frame-ancestors 'self'",
            
            'development' => "default-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
                            "img-src 'self' data: https:; " .
                            "font-src 'self' data:; " .
                            "connect-src 'self' ws: wss:",
        ];
        
        return $policies[$environment] ?? $policies['moderate'];
    }
    
    /**
     * Obtém todas as configurações de headers aplicados
     */
    public static function getAppliedHeaders(): array
    {
        return self::$appliedHeaders;
    }
    
    /**
     * Reseta o estado (útil para testes)
     */
    public static function reset(): void
    {
        self::$appliedHeaders = [];
        self::$headersSent = false;
    }
    
    /**
     * Verifica se a conexão é HTTPS
     */
    private static function isHttps(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );
    }
    
    /**
     * Define um header e registra
     */
    private static function setHeader(string $name, string $value): void
    {
        // Sempre registrar no array (útil para testes e rastreamento)
        self::$appliedHeaders[$name] = $value;
        
        // Tentar aplicar header HTTP se possível
        if (!headers_sent()) {
            header("{$name}: {$value}");
        }
    }
    
    /**
     * Avalia a segurança atual dos headers
     * 
     * @return array Avaliação com score, grade e recomendações
     */
    public static function evaluateSecurity(): array
    {
        $headers = self::$appliedHeaders;
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];
        
        // X-Frame-Options (15 pontos)
        if (isset($headers['X-Frame-Options'])) {
            $score += 15;
        } else {
            $issues[] = 'Missing X-Frame-Options header';
            $recommendations[] = 'Add X-Frame-Options to prevent clickjacking';
        }
        
        // X-Content-Type-Options (10 pontos)
        if (isset($headers['X-Content-Type-Options'])) {
            $score += 10;
        } else {
            $issues[] = 'Missing X-Content-Type-Options header';
            $recommendations[] = 'Add X-Content-Type-Options to prevent MIME sniffing';
        }
        
        // X-XSS-Protection (5 pontos - legacy)
        if (isset($headers['X-XSS-Protection'])) {
            $score += 5;
        }
        
        // Referrer-Policy (10 pontos)
        if (isset($headers['Referrer-Policy'])) {
            $score += 10;
        } else {
            $issues[] = 'Missing Referrer-Policy header';
            $recommendations[] = 'Add Referrer-Policy to control referrer information';
        }
        
        // Permissions-Policy (10 pontos)
        if (isset($headers['Permissions-Policy'])) {
            $score += 10;
        } else {
            $issues[] = 'Missing Permissions-Policy header';
            $recommendations[] = 'Add Permissions-Policy to restrict browser features';
        }
        
        // HSTS (20 pontos)
        if (isset($headers['Strict-Transport-Security'])) {
            $score += 20;
        } else {
            if (self::isHttps()) {
                $issues[] = 'Missing HSTS header (HTTPS detected)';
                $recommendations[] = 'Add HSTS to enforce HTTPS connections';
            }
        }
        
        // CSP (30 pontos)
        if (isset($headers['Content-Security-Policy'])) {
            $score += 30;
        } else {
            $issues[] = 'Missing Content-Security-Policy header';
            $recommendations[] = 'Add CSP to prevent XSS and other injection attacks';
        }
        
        // Expect-CT (5 pontos)
        if (isset($headers['Expect-CT'])) {
            $score += 5;
        }
        
        // Grade
        $grade = 'F';
        if ($score >= 90) $grade = 'A+';
        elseif ($score >= 80) $grade = 'A';
        elseif ($score >= 70) $grade = 'B';
        elseif ($score >= 60) $grade = 'C';
        elseif ($score >= 50) $grade = 'D';
        
        return [
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100, 2),
            'grade' => $grade,
            'issues' => $issues,
            'recommendations' => $recommendations,
            'headers_applied' => count($headers),
            'is_https' => self::isHttps()
        ];
    }
}
