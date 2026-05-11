<?php

namespace App\Middleware;

/**
 * XSS (Cross-Site Scripting) Protection Middleware
 * 
 * Protege contra ataques XSS através de:
 * - Output Escaping (htmlspecialchars)
 * - Input Sanitization
 * - HTML Purifier
 * - Context-aware escaping
 * 
 * Implementação enterprise-level seguindo OWASP Top 10.
 * 
 * Uso:
 * 
 * // Output escaping (método principal)
 * echo XssProtection::escape($userInput);
 * 
 * // HTML com tags permitidas
 * echo XssProtection::purify($userHtml);
 * 
 * // Context-aware escaping
 * echo "<div data-value='" . XssProtection::escapeAttr($value) . "'>";
 * echo "<script>var data = " . XssProtection::escapeJs($value) . ";</script>";
 * 
 * @link https://owasp.org/www-community/attacks/xss/
 */
class XssProtection
{
    /** @var array Configuração padrão */
    private const DEFAULT_CONFIG = [
        'encoding' => 'UTF-8',
        'flags' => ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE,
        'allowed_tags' => '<p><br><strong><em><u><a><ul><ol><li>',
        'allowed_protocols' => ['http', 'https', 'mailto'],
    ];
    
    /** @var array Estatísticas */
    private static array $stats = [
        'escaped_outputs' => 0,
        'sanitized_inputs' => 0,
        'blocked_xss' => 0,
    ];
    
    /** @var array Log de tentativas de XSS */
    private static array $xssAttempts = [];
    
    /** @var array Padrões de XSS conhecidos */
    private const XSS_PATTERNS = [
        '/<script[^>]*>.*?<\/script>/is',           // <script> tags
        '/<iframe[^>]*>.*?<\/iframe>/is',           // <iframe> tags
        '/javascript:/i',                            // javascript: protocol
        '/on\w+\s*=/i',                             // event handlers (onclick, onerror, etc)
        '/<object[^>]*>.*?<\/object>/is',          // <object> tags
        '/<embed[^>]*>/i',                          // <embed> tags
        '/vbscript:/i',                             // vbscript: protocol
        '/data:text\/html/i',                       // data:text/html
        '/<meta[^>]*http-equiv/i',                  // meta refresh
        '/<link[^>]*>/i',                           // <link> tags (CSS injection)
        '/<style[^>]*>.*?<\/style>/is',            // <style> tags
        '/expression\s*\(/i',                       // CSS expression()
        '/import\s+/i',                             // @import (CSS)
        '/<base[^>]*>/i',                           // <base> tag
        '/<form[^>]*>/i',                           // <form> tags
    ];
    
    /**
     * Escape output para HTML (método principal)
     * 
     * @param mixed $value Valor a escapar
     * @return string Valor escapado
     */
    public static function escape($value): string
    {
        if ($value === null) {
            return '';
        }
        
        if (is_array($value) || is_object($value)) {
            return htmlspecialchars(json_encode($value), self::DEFAULT_CONFIG['flags'], self::DEFAULT_CONFIG['encoding']);
        }
        
        self::$stats['escaped_outputs']++;
        
        return htmlspecialchars(
            (string)$value,
            self::DEFAULT_CONFIG['flags'],
            self::DEFAULT_CONFIG['encoding']
        );
    }
    
    /**
     * Escape para atributos HTML
     * 
     * @param mixed $value Valor a escapar
     * @return string Valor escapado
     */
    public static function escapeAttr($value): string
    {
        if ($value === null) {
            return '';
        }
        
        $value = (string)$value;
        
        // Remove event handlers perigosos (on*)
        $value = preg_replace('/\bon\w+\s*=/i', '', $value);
        
        // Remove caracteres perigosos
        $value = str_replace(['"', "'", '`'], '', $value);
        
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE,
            self::DEFAULT_CONFIG['encoding']
        );
    }
    
    /**
     * Escape para JavaScript
     * 
     * @param mixed $value Valor a escapar
     * @return string JSON encoded
     */
    public static function escapeJs($value): string
    {
        return json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
    }
    
    /**
     * Escape para URLs
     * 
     * @param string $value Valor a escapar
     * @return string URL escapada
     */
    public static function escapeUrl(string $value): string
    {
        // Validar protocolo
        $parsed = parse_url($value);
        if (isset($parsed['scheme'])) {
            if (!in_array(strtolower($parsed['scheme']), self::DEFAULT_CONFIG['allowed_protocols'])) {
                return '#';  // URL suspeita
            }
        }
        
        return htmlspecialchars(
            urlencode($value),
            ENT_QUOTES,
            self::DEFAULT_CONFIG['encoding']
        );
    }
    
    /**
     * Escape para CSS
     * 
     * @param string $value Valor a escapar
     * @return string Valor escapado
     */
    public static function escapeCss(string $value): string
    {
        // Remove caracteres perigosos para CSS
        $value = preg_replace('/[^a-zA-Z0-9\s\-_#,.]/', '', $value);
        
        return htmlspecialchars($value, ENT_QUOTES, self::DEFAULT_CONFIG['encoding']);
    }
    
    /**
     * Sanitiza input do usuário
     * 
     * @param string $input Input a sanitizar
     * @return string Input sanitizado
     */
    public static function sanitize(string $input): string
    {
        self::$stats['sanitized_inputs']++;
        
        // Remove tags HTML perigosas
        $input = strip_tags($input, self::DEFAULT_CONFIG['allowed_tags']);
        
        // Remove atributos perigosos
        $input = self::removeEventHandlers($input);
        
        // Sanitiza protocolos de URLs
        $input = self::sanitizeUrls($input);
        
        return $input;
    }
    
    /**
     * Purifica HTML permitindo tags seguras
     * 
     * @param string $html HTML a purificar
     * @param array|null $allowedTags Tags permitidas
     * @return string HTML purificado
     */
    public static function purify(string $html, ?array $allowedTags = null): string
    {
        $allowedTags = $allowedTags ?? explode('><', trim(self::DEFAULT_CONFIG['allowed_tags'], '<>'));
        
        // Remove tags não permitidas
        $html = strip_tags($html, self::DEFAULT_CONFIG['allowed_tags']);
        
        // Remove event handlers
        $html = self::removeEventHandlers($html);
        
        // Sanitiza URLs em links
        $html = preg_replace_callback(
            '/<a[^>]*href=["\']([^"\']*)["\'][^>]*>/i',
            function($matches) {
                $url = $matches[1];
                $parsed = parse_url($url);
                
                if (isset($parsed['scheme']) && !in_array(strtolower($parsed['scheme']), self::DEFAULT_CONFIG['allowed_protocols'])) {
                    return '<a href="#">';  // URL suspeita
                }
                
                return $matches[0];
            },
            $html
        );
        
        return $html;
    }
    
    /**
     * Detecta tentativas de XSS
     * 
     * @param string $input Input a verificar
     * @return bool True se XSS detectado
     */
    public static function detectXss(string $input): bool
    {
        foreach (self::XSS_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                self::$stats['blocked_xss']++;
                
                self::$xssAttempts[] = [
                    'input' => substr($input, 0, 200),
                    'pattern' => $pattern,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ];
                
                // Logger removed
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Valida input contra XSS
     * 
     * @param string $input Input a validar
     * @return bool True se seguro
     */
    public static function validateInput(string $input): bool
    {
        return !self::detectXss($input);
    }
    
    /**
     * Remove event handlers HTML
     * 
     * @param string $html HTML
     * @return string HTML sem event handlers
     */
    private static function removeEventHandlers(string $html): string
    {
        // Remove atributos on* (onclick, onerror, onload, etc)
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s*on\w+\s*=\s*[^>\s]*/i', '', $html);
        
        return $html;
    }
    
    /**
     * Sanitiza URLs em HTML
     * 
     * @param string $html HTML
     * @return string HTML com URLs sanitizadas
     */
    private static function sanitizeUrls(string $html): string
    {
        // Remove javascript: e data: protocols
        $html = preg_replace('/javascript:/i', '', $html);
        $html = preg_replace('/vbscript:/i', '', $html);
        $html = preg_replace('/data:text\/html/i', '', $html);
        
        return $html;
    }
    
    /**
     * Helper para escapar array de valores
     * 
     * @param array $data Array de dados
     * @return array Array com valores escapados
     */
    public static function escapeArray(array $data): array
    {
        $escaped = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $escaped[$key] = self::escapeArray($value);
            } else {
                $escaped[$key] = self::escape($value);
            }
        }
        
        return $escaped;
    }
    
    /**
     * Helper para template engines
     * 
     * @param string $template Template com {{ variable }}
     * @param array $data Dados a substituir
     * @return string Template renderizado com escape
     */
    public static function render(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $escaped = self::escape($value);
            $template = str_replace('{{' . $key . '}}', $escaped, $template);
        }
        
        return $template;
    }
    
    /**
     * Middleware para sanitizar $_GET, $_POST, $_REQUEST
     * 
     * @return void
     */
    public static function sanitizeGlobals(): void
    {
        $_GET = self::sanitizeArrayRecursive($_GET);
        $_POST = self::sanitizeArrayRecursive($_POST);
        $_REQUEST = self::sanitizeArrayRecursive($_REQUEST);
        
        // Logger removed
    }
    
    /**
     * Sanitiza array recursivamente
     * 
     * @param array $data Array a sanitizar
     * @return array Array sanitizado
     */
    private static function sanitizeArrayRecursive(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArrayRecursive($value);
            } else {
                // Detectar XSS
                if (is_string($value) && self::detectXss($value)) {
                    $sanitized[$key] = self::sanitize($value);
                } else {
                    $sanitized[$key] = $value;
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Obtém estatísticas
     * 
     * @return array
     */
    public static function getStats(): array
    {
        return self::$stats;
    }
    
    /**
     * Obtém tentativas de XSS bloqueadas
     * 
     * @return array
     */
    public static function getXssAttempts(): array
    {
        return self::$xssAttempts;
    }
    
    /**
     * Reset state (útil para testes)
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$stats = [
            'escaped_outputs' => 0,
            'sanitized_inputs' => 0,
            'blocked_xss' => 0,
        ];
        self::$xssAttempts = [];
    }
    
    /**
     * Helper: e() - Alias curto para escape()
     * 
     * @param mixed $value Valor a escapar
     * @return string Valor escapado
     */
    public static function e($value): string
    {
        return self::escape($value);
    }
}

// Nota: função e() global já definida em app/core/CommonHelpers.php

/**
 * Função global helper para sanitização
 * 
 * @param string $input Input a sanitizar
 * @return string Input sanitizado
 */
if (!function_exists('sanitize')) {
    function sanitize(string $input): string
    {
        return \App\Middleware\XssProtection::sanitize($input);
    }
}
