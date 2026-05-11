<?php

declare(strict_types=1);

/**
 * 🔐 AuthCustomer - Gerenciamento de autenticação de clientes
 * 
 * Atualizado com sistema de segurança aprimorado para prevenir
 * troca indevida de perfil entre usuários.
 */
class AuthCustomer
{
    /**
     * Inicia a sessão de forma segura
     */
    public static function start(): void
    {
        // 🔐 Usar sistema de segurança aprimorado se disponível
        if (function_exists('secure_session_start')) {
            secure_session_start();
            return;
        }
        
        if (class_exists('Auth') && method_exists('Auth', 'start')) {
            Auth::start();
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $name = function_exists('config') ? (config('session_name') ?? 'mm_session') : 'mm_session';

            // Configurar o nome da sessão ANTES de iniciar
            if ($name && session_status() === PHP_SESSION_NONE && session_name() !== $name) {
                session_name($name);
            }
            
            // 🔐 Configurações de segurança básicas
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', $isSecure ? '1' : '0');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            
            session_start();
        }
    }

    /**
     * Retorna o cliente atual da sessão
     * 
     * @param string|null $slug Slug da empresa para validar escopo
     * @return array|null Dados do cliente ou null
     */
    public static function current(?string $slug = null): ?array
    {
        self::start();
        $c = $_SESSION['customer'] ?? null;

        if (!$c) {
            return null;
        }

        // 🔐 Validar escopo da empresa
        if ($slug !== null && isset($c['company_slug']) && $c['company_slug'] !== $slug) {
            // Log de tentativa de acesso fora do escopo
            if (function_exists('log_security_event')) {
                log_security_event('customer_scope_mismatch', [
                    'customer_id' => $c['id'] ?? null,
                    'session_slug' => $c['company_slug'] ?? null,
                    'requested_slug' => $slug
                ]);
            }
            return null;
        }
        
        // 🔐 Validar propriedade da sessão se disponível
        if (function_exists('validate_session_ownership')) {
            if (!validate_session_ownership((int)($c['id'] ?? 0))) {
                if (function_exists('log_security_event')) {
                    log_security_event('session_ownership_failed', [
                        'customer_id' => $c['id'] ?? null
                    ]);
                }
                return null;
            }
        }

        return $c;
    }

    /**
     * Verifica se o cliente está autenticado
     * 
     * @param string|null $slug Slug da empresa para validar escopo
     * @return bool
     */
    public static function require(?string $slug = null): bool
    {
        return self::current($slug) !== null;
    }
    
    /**
     * 🔐 Obtém o ID do cliente atual
     * 
     * @return int|null
     */
    public static function getCurrentId(): ?int
    {
        self::start();
        $c = $_SESSION['customer'] ?? null;
        
        if (!$c) {
            return null;
        }
        
        return (int)($c['id'] ?? 0) ?: null;
    }
    
    /**
     * 🔐 Valida se a sessão está íntegra
     * 
     * @return bool
     */
    public static function validateSession(): bool
    {
        self::start();
        
        // Verificar se há dados de cliente
        if (empty($_SESSION['customer'])) {
            return false;
        }
        
        // 🔐 Usar validação avançada se disponível
        if (function_exists('validate_session_ownership')) {
            return validate_session_ownership();
        }
        
        return true;
    }
}
