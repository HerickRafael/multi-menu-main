<?php
/**
 * SubdomainDetector - Detecção de subdomínio mobile
 * 
 * Arquitetura limpa: detecta se o acesso é via m.site.com
 * para redirecionar automaticamente para views mobile dedicadas.
 * 
 * @package MultiMenu\Middleware
 */

class SubdomainDetector
{
    /**
     * Verifica se o acesso é pelo subdomínio mobile (m.*)
     * 
     * @return bool
     */
    public static function isMobileSubdomain(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return str_starts_with($host, 'm.');
    }

    /**
     * Retorna o host base (sem prefixo mobile)
     * 
     * @return string
     */
    public static function getBaseHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (str_starts_with($host, 'm.')) {
            return substr($host, 2);
        }
        return $host;
    }

    /**
     * Retorna a URL base do site desktop
     * 
     * @return string
     */
    public static function getDesktopUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . self::getBaseHost();
    }

    /**
     * Retorna a URL base do site mobile
     * 
     * @return string
     */
    public static function getMobileUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        if (!str_starts_with($host, 'm.')) {
            $host = 'm.' . $host;
        }
        
        return $protocol . '://' . $host;
    }

    /**
     * Retorna o slug da empresa (para mobile, usa env ou extrai do domínio)
     * 
     * @return string
     */
    public static function getCompanySlug(): string
    {
        // Primeiro tenta pegar do .env
        $envSlug = getenv('DEFAULT_COMPANY_SLUG') ?: ($_ENV['DEFAULT_COMPANY_SLUG'] ?? '');
        if (!empty($envSlug)) {
            return $envSlug;
        }

        // Fallback: extrai do domínio (wollburger.online → wollburger)
        $baseHost = self::getBaseHost();
        $parts = explode('.', $baseHost);
        return $parts[0] ?? 'wollburger';
    }

    /**
     * Inicializa o contexto mobile se for subdomínio m.*
     * Define constantes e variáveis globais
     * 
     * @return void
     */
    public static function initialize(): void
    {
        $isMobile = self::isMobileSubdomain();
        
        // Define constante global
        if (!defined('IS_MOBILE_SUBDOMAIN')) {
            define('IS_MOBILE_SUBDOMAIN', $isMobile);
        }

        // Se mobile, define o slug no contexto global
        if ($isMobile) {
            $_SERVER['MOBILE_SLUG'] = self::getCompanySlug();
            $_SERVER['MOBILE_CONTEXT'] = true;
        }
    }
}
