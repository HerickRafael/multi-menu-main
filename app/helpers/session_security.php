<?php
/**
 * Session Security Helper
 * 
 * Sistema robusto de segurança de sessão para prevenir:
 * - Hijacking de sessão
 * - Troca indevida de perfil entre usuários
 * - Fixação de sessão
 * - Vazamento de dados entre sessões
 * 
 * @author Multi-Menu Security Team
 * @version 1.0.0
 */

/**
 * Gera um token único para a sessão do usuário
 * 
 * @return string Token de 64 caracteres hexadecimais
 */
function generate_session_token(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Valida e inicializa a sessão de forma segura
 * Configura cookies HTTPOnly, Secure e SameSite
 */
function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Configurações de segurança da sessão
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $isSecure ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', '604800'); // 7 dias
        
        session_start();
    }
    
    // Regenerar ID de sessão periodicamente (a cada 30 minutos)
    if (!isset($_SESSION['_last_regeneration'])) {
        $_SESSION['_last_regeneration'] = time();
    } elseif (time() - $_SESSION['_last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
        log_security_event('session_regenerated', [
            'customer_id' => $_SESSION['customer_id'] ?? null
        ]);
    }
}

/**
 * Valida se a sessão pertence ao usuário correto
 * Verifica fingerprint do navegador e outras características
 * 
 * @param int|null $expectedCustomerId ID do cliente esperado (opcional)
 * @return bool True se a sessão é válida
 */
function validate_session_ownership(?int $expectedCustomerId = null): bool
{
    if (!isset($_SESSION['customer_id'])) {
        return false;
    }
    
    // Validar fingerprint do navegador
    $currentFingerprint = generate_browser_fingerprint();
    if (isset($_SESSION['_browser_fingerprint'])) {
        if ($_SESSION['_browser_fingerprint'] !== $currentFingerprint) {
            // Fingerprint mudou - possível hijacking
            log_security_event('fingerprint_mismatch', [
                'customer_id' => $_SESSION['customer_id'],
                'expected' => substr($_SESSION['_browser_fingerprint'], 0, 16) . '...',
                'received' => substr($currentFingerprint, 0, 16) . '...'
            ]);
            destroy_session_completely();
            return false;
        }
    } else {
        $_SESSION['_browser_fingerprint'] = $currentFingerprint;
    }
    
    // Validar IP (com tolerância para IPs móveis)
    $currentIp = get_client_ip();
    if (isset($_SESSION['_client_ip'])) {
        // Verificar se o IP mudou drasticamente (diferentes blocos)
        if (!is_ip_similar($_SESSION['_client_ip'], $currentIp)) {
            log_security_event('ip_change_detected', [
                'customer_id' => $_SESSION['customer_id'],
                'old_ip' => $_SESSION['_client_ip'],
                'new_ip' => $currentIp
            ]);
            // Não destruir sessão por IP móvel, apenas logar
            // Atualizar IP para o novo
            $_SESSION['_client_ip'] = $currentIp;
        }
    } else {
        $_SESSION['_client_ip'] = $currentIp;
    }
    
    // Validar customer ID específico se fornecido
    if ($expectedCustomerId !== null && (int)$_SESSION['customer_id'] !== $expectedCustomerId) {
        log_security_event('customer_id_mismatch', [
            'expected' => $expectedCustomerId,
            'session' => $_SESSION['customer_id']
        ]);
        return false;
    }
    
    return true;
}

/**
 * Gera fingerprint do navegador baseado em características únicas
 * 
 * @return string Hash SHA-256 do fingerprint
 */
function generate_browser_fingerprint(): string
{
    $data = [
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
    ];
    
    return hash('sha256', implode('|', $data));
}

/**
 * Obtém o IP real do cliente, considerando proxies e CDNs
 * 
 * @return string Endereço IP do cliente
 */
function get_client_ip(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Proxy genérico
        'HTTP_X_REAL_IP',            // Nginx
        'HTTP_CLIENT_IP',            // Proxy alternativo
        'REMOTE_ADDR'                // Direto
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
            // Se não passar no filtro rigoroso, usar sem filtro
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Verifica se dois IPs são similares (mesmo bloco /16 para IPv4)
 * Útil para tolerar mudanças de IP em redes móveis
 * 
 * @param string $ip1 Primeiro IP
 * @param string $ip2 Segundo IP
 * @return bool True se os IPs são considerados similares
 */
function is_ip_similar(string $ip1, string $ip2): bool
{
    // Para IPv4, comparar os dois primeiros octetos
    $parts1 = explode('.', $ip1);
    $parts2 = explode('.', $ip2);
    
    if (count($parts1) === 4 && count($parts2) === 4) {
        return $parts1[0] === $parts2[0] && $parts1[1] === $parts2[1];
    }
    
    // Para IPv6 ou IPs inválidos, considerar iguais apenas se forem idênticos
    return $ip1 === $ip2;
}

/**
 * Destrói a sessão completamente, incluindo cookies
 */
function destroy_session_completely(): void
{
    $_SESSION = [];
    
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
    
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    log_security_event('session_destroyed', []);
}

/**
 * Inicializa sessão de cliente de forma segura
 * Deve ser chamado após autenticação bem-sucedida
 * 
 * @param array $customer Dados do cliente
 * @param int $companyId ID da empresa
 */
function initialize_customer_session(array $customer, int $companyId): void
{
    // Regenerar ID de sessão ao fazer login (previne fixação de sessão)
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    
    // Limpar dados de sessão anterior para evitar vazamento
    $keysToPreserve = ['_last_regeneration'];
    $preservedData = [];
    foreach ($keysToPreserve as $key) {
        if (isset($_SESSION[$key])) {
            $preservedData[$key] = $_SESSION[$key];
        }
    }
    
    // Limpar sessão antiga
    $_SESSION = [];
    
    // Restaurar dados preservados
    foreach ($preservedData as $key => $value) {
        $_SESSION[$key] = $value;
    }
    
    // Definir novos dados de sessão
    $_SESSION['customer_id'] = (int)($customer['id'] ?? 0);
    $_SESSION['customer_phone'] = $customer['whatsapp'] ?? $customer['phone'] ?? '';
    $_SESSION['customer_name'] = $customer['name'] ?? '';
    $_SESSION['company_id'] = $companyId;
    $_SESSION['_session_token'] = generate_session_token();
    $_SESSION['_login_time'] = time();
    $_SESSION['_last_activity'] = time();
    $_SESSION['_browser_fingerprint'] = generate_browser_fingerprint();
    $_SESSION['_client_ip'] = get_client_ip();
    $_SESSION['_last_regeneration'] = time();
    
    // Garantir que dados de cupom de outros usuários são limpos
    unset($_SESSION['couponCode']);
    unset($_SESSION['couponDiscount']);
    unset($_SESSION['couponSyncAttempted']);
    
    log_security_event('customer_login', [
        'customer_id' => $_SESSION['customer_id'],
        'customer_name' => $_SESSION['customer_name'],
        'company_id' => $companyId
    ]);
}

/**
 * Valida atividade da sessão (timeout por inatividade)
 * 
 * @param int $maxIdleTime Tempo máximo de inatividade em segundos (padrão: 7 dias)
 * @return bool True se a sessão ainda é válida
 */
function validate_session_activity(int $maxIdleTime = 604800): bool
{
    if (!isset($_SESSION['_last_activity'])) {
        return false;
    }
    
    if (time() - $_SESSION['_last_activity'] > $maxIdleTime) {
        log_security_event('session_timeout', [
            'customer_id' => $_SESSION['customer_id'] ?? 'unknown',
            'idle_time' => time() - $_SESSION['_last_activity']
        ]);
        destroy_session_completely();
        return false;
    }
    
    $_SESSION['_last_activity'] = time();
    return true;
}

/**
 * Obtém token de sessão para uso em JavaScript
 * 
 * @return string Token de sessão ou string vazia
 */
function get_session_token_for_js(): string
{
    return $_SESSION['_session_token'] ?? '';
}

/**
 * Valida o token de sessão recebido do cliente
 * 
 * @param string $token Token recebido
 * @return bool True se o token é válido
 */
function validate_session_token(string $token): bool
{
    if (empty($_SESSION['_session_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['_session_token'], $token);
}

/**
 * Valida se o telefone pertence ao cliente atual da sessão
 * 
 * @param string $phone Telefone para validar
 * @return bool True se o telefone pertence ao cliente
 */
function validate_customer_phone(string $phone): bool
{
    if (!isset($_SESSION['customer_phone'])) {
        return false;
    }
    
    // Normalizar telefones para comparação (remover não-dígitos)
    $sessionPhone = preg_replace('/\D/', '', $_SESSION['customer_phone']);
    $checkPhone = preg_replace('/\D/', '', $phone);
    
    // Comparar últimos 9 dígitos (formato brasileiro sem DDD)
    if (strlen($sessionPhone) >= 9 && strlen($checkPhone) >= 9) {
        return substr($sessionPhone, -9) === substr($checkPhone, -9);
    }
    
    return $sessionPhone === $checkPhone;
}

/**
 * Obtém dados seguros do cliente para uso em views
 * 
 * @return array Dados do cliente sanitizados
 */
function get_secure_customer_data(): array
{
    return [
        'id' => (int)($_SESSION['customer_id'] ?? 0),
        'name' => $_SESSION['customer_name'] ?? '',
        'phone' => $_SESSION['customer_phone'] ?? '',
        'company_id' => (int)($_SESSION['company_id'] ?? 0),
        'session_token' => $_SESSION['_session_token'] ?? '',
        'is_authenticated' => isset($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0
    ];
}

/**
 * Log de eventos de segurança
 * 
 * @param string $event Nome do evento
 * @param array $context Contexto adicional
 */
function log_security_event(string $event, array $context = []): void
{
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'customer_id' => $_SESSION['customer_id'] ?? null,
        'ip' => get_client_ip(),
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'context' => $context
    ];
    
    $logMessage = "SECURITY_LOG: " . json_encode($logData, JSON_UNESCAPED_UNICODE);
    
    // Logar em arquivo específico de segurança se disponível
    $securityLogPath = dirname(__DIR__, 2) . '/storage/logs/security.log';
    $logDir = dirname($securityLogPath);
    
    if (is_dir($logDir) && is_writable($logDir)) {
        file_put_contents(
            $securityLogPath,
            date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    } else {
        // Fallback para error_log padrão
        error_log($logMessage);
    }
}

/**
 * Middleware para validar sessão em requisições AJAX
 * 
 * @return bool True se a requisição é válida
 */
function validate_ajax_request(): bool
{
    // Verificar se é requisição AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if (!$isAjax) {
        // Também aceitar Content-Type JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            return true; // Não é AJAX, deixar passar
        }
    }
    
    // Validar token de sessão do header
    $token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? '';
    if (!empty($token) && !validate_session_token($token)) {
        log_security_event('invalid_ajax_token', [
            'provided_token' => substr($token, 0, 16) . '...'
        ]);
        return false;
    }
    
    return true;
}

/**
 * Limpa dados sensíveis da sessão ao fazer logout
 */
function logout_customer(): void
{
    log_security_event('customer_logout', [
        'customer_id' => $_SESSION['customer_id'] ?? null,
        'customer_name' => $_SESSION['customer_name'] ?? null
    ]);
    
    // Lista de chaves a serem removidas
    $customerKeys = [
        'customer_id',
        'customer_phone', 
        'customer_name',
        'company_id',
        '_session_token',
        '_login_time',
        '_last_activity',
        '_browser_fingerprint',
        '_client_ip',
        'couponCode',
        'couponDiscount',
        'couponSyncAttempted',
        'customer',
        'cart'
    ];
    
    foreach ($customerKeys as $key) {
        unset($_SESSION[$key]);
    }
    
    // Regenerar ID de sessão
    session_regenerate_id(true);
    $_SESSION['_last_regeneration'] = time();
}

/**
 * Verifica se o cliente está autenticado
 * 
 * @return bool True se autenticado
 */
function is_customer_authenticated(): bool
{
    return isset($_SESSION['customer_id']) && 
           (int)$_SESSION['customer_id'] > 0 &&
           isset($_SESSION['_session_token']);
}

/**
 * Obtém o ID do cliente atual ou null
 * 
 * @return int|null
 */
function get_current_customer_id(): ?int
{
    if (!is_customer_authenticated()) {
        return null;
    }
    return (int)$_SESSION['customer_id'];
}
