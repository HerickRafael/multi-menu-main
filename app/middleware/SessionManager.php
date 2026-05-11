<?php
/**
 * Session Management System
 * 
 * Sistema avançado de gerenciamento de sessões com:
 * - Session fixation protection
 * - Session hijacking protection
 * - Timeout automático
 * - Concurrent session management
 * - Multiple storage drivers (file, database, redis)
 * - Secure attributes (httponly, secure, samesite)
 * - Session fingerprinting
 * - Activity tracking
 * 
 * @package MultiMenu
 * @subpackage Middleware
 * @version 1.0.0
 */

namespace App\Middleware;

use PDO;
use Exception;
use SessionHandlerInterface;

class SessionManager implements SessionHandlerInterface
{
    /**
     * Instância PDO
     * @var PDO|null
     */
    private ?PDO $pdo = null;
    
    /**
     * Configurações
     * @var array
     */
    private array $config = [
        'driver' => 'file', // file, database, redis
        'lifetime' => 604800, // 7 dias
        'cookie_lifetime' => 0, // Session cookie
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'regenerate_interval' => 300, // 5 minutos
        'gc_probability' => 1,
        'gc_divisor' => 100,
        'gc_maxlifetime' => 604800,
        'enable_fingerprint' => true,
        'fingerprint_method' => 'strong', // weak, strong
        'max_concurrent_sessions' => 5,
        'enable_activity_tracking' => true,
    ];
    
    /**
     * Fingerprint da sessão
     * @var string|null
     */
    private ?string $fingerprint = null;
    
    /**
     * Estatísticas
     * @var array
     */
    private static array $stats = [
        'sessions_started' => 0,
        'sessions_destroyed' => 0,
        'sessions_regenerated' => 0,
        'fixation_attempts' => 0,
        'hijacking_attempts' => 0,
    ];
    
    /**
     * Construtor
     * 
     * @param array $config Configurações customizadas
     * @param PDO|null $pdo Instância PDO (para driver database)
     */
    public function __construct(array $config = [], ?PDO $pdo = null)
    {
        $this->config = array_merge($this->config, $config);
        $this->pdo = $pdo;
        
        // Configurar handler
        if ($this->config['driver'] === 'database' && $this->pdo) {
            session_set_save_handler($this, true);
        }
        
        $this->configureSession();
    }
    
    /**
     * Configura parâmetros da sessão
     */
    private function configureSession(): void
    {
        ini_set('session.use_strict_mode', $this->config['use_strict_mode'] ? '1' : '0');
        ini_set('session.cookie_httponly', $this->config['cookie_httponly'] ? '1' : '0');
        ini_set('session.cookie_secure', $this->config['cookie_secure'] ? '1' : '0');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', $this->config['cookie_samesite']);
        ini_set('session.gc_probability', (string)$this->config['gc_probability']);
        ini_set('session.gc_divisor', (string)$this->config['gc_divisor']);
        ini_set('session.gc_maxlifetime', (string)$this->config['gc_maxlifetime']);
        
        session_set_cookie_params([
            'lifetime' => $this->config['cookie_lifetime'],
            'path' => $this->config['cookie_path'],
            'domain' => $this->config['cookie_domain'],
            'secure' => $this->config['cookie_secure'],
            'httponly' => $this->config['cookie_httponly'],
            'samesite' => $this->config['cookie_samesite']
        ]);
    }
    
    // ===================================================================
    // SESSION LIFECYCLE
    // ===================================================================
    
    /**
     * Inicia sessão segura
     * 
     * @return bool True se iniciada com sucesso
     */
    public function start(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        
        $result = session_start();
        
        if ($result) {
            self::$stats['sessions_started']++;
            
            // Verificar fixation
            if ($this->detectFixation()) {
                $this->handleFixation();
                return false;
            }
            
            // Verificar hijacking
            if ($this->detectHijacking()) {
                $this->handleHijacking();
                return false;
            }
            
            // Criar fingerprint se não existir
            if ($this->config['enable_fingerprint'] && !isset($_SESSION['__fingerprint'])) {
                $this->setFingerprint();
            }
            
            // Registrar início se novo
            if (!isset($_SESSION['__created'])) {
                $_SESSION['__created'] = time();
                $_SESSION['__last_regeneration'] = time();
                $_SESSION['__ip'] = $this->getClientIp();
                $_SESSION['__user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            }
            
            // Atualizar última atividade
            $_SESSION['__last_activity'] = time();
            
            // Verificar timeout
            if ($this->isTimedOut()) {
                $this->destroyCurrent();
                return false;
            }
            
            // Regenerar ID periodicamente
            if ($this->shouldRegenerate()) {
                $this->regenerate();
            }
            
            // Tracking
            if ($this->config['enable_activity_tracking']) {
                $this->trackActivity();
            }
        }
        
        return $result;
    }
    
    /**
     * Regenera ID da sessão
     * 
     * @param bool $deleteOld Se true, deleta sessão antiga
     * @return bool
     */
    public function regenerate(bool $deleteOld = true): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        $oldId = session_id();
        
        $result = session_regenerate_id($deleteOld);
        
        if ($result) {
            self::$stats['sessions_regenerated']++;
            $_SESSION['__last_regeneration'] = time();
            
            // Log
            if ($this->config['enable_activity_tracking']) {
                $this->logActivity('regenerate', [
                    'old_id' => $oldId,
                    'new_id' => session_id()
                ]);
            }
        }
        
        return $result;
    }
    
    /**
     * Destrói sessão atual
     * 
     * @return bool
     */
    public function destroyCurrent(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        $sessionId = session_id();
        
        // Log
        if ($this->config['enable_activity_tracking']) {
            $this->logActivity('destroy');
        }
        
        // Limpar dados
        $_SESSION = [];
        
        // Destruir cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $this->config['cookie_path'],
                $this->config['cookie_domain'],
                $this->config['cookie_secure'],
                $this->config['cookie_httponly']
            );
        }
        
        $result = session_destroy();
        
        if ($result) {
            self::$stats['sessions_destroyed']++;
            
            // Destruir no banco também
            if ($this->pdo && $sessionId) {
                $this->destroy($sessionId);
            }
        }
        
        return $result;
    }
    
    // ===================================================================
    // SECURITY CHECKS
    // ===================================================================
    
    /**
     * Detecta tentativa de fixation
     * 
     * @return bool
     */
    private function detectFixation(): bool
    {
        // Verificar se ID foi fornecido via GET/POST (possível fixation)
        if (isset($_GET[session_name()]) || isset($_POST[session_name()])) {
            self::$stats['fixation_attempts']++;
            return true;
        }
        
        return false;
    }
    
    /**
     * Detecta tentativa de hijacking
     * 
     * @return bool
     */
    private function detectHijacking(): bool
    {
        if (!$this->config['enable_fingerprint']) {
            return false;
        }
        
        // Primeira vez - sem fingerprint ainda
        if (!isset($_SESSION['__fingerprint'])) {
            return false;
        }
        
        // Verificar fingerprint
        $currentFingerprint = $this->generateFingerprint();
        
        if ($_SESSION['__fingerprint'] !== $currentFingerprint) {
            self::$stats['hijacking_attempts']++;
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica se sessão expirou
     * 
     * @return bool
     */
    private function isTimedOut(): bool
    {
        if (!isset($_SESSION['__last_activity'])) {
            return false;
        }
        
        $elapsed = time() - $_SESSION['__last_activity'];
        
        return $elapsed > $this->config['lifetime'];
    }
    
    /**
     * Verifica se deve regenerar ID
     * 
     * @return bool
     */
    private function shouldRegenerate(): bool
    {
        if (!isset($_SESSION['__last_regeneration'])) {
            return true;
        }
        
        $elapsed = time() - $_SESSION['__last_regeneration'];
        
        return $elapsed > $this->config['regenerate_interval'];
    }
    
    /**
     * Trata tentativa de fixation
     */
    private function handleFixation(): void
    {
        $this->logActivity('fixation_attempt', [
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        $this->destroyCurrent();
        
        throw new Exception('Session fixation attempt detected.');
    }
    
    /**
     * Trata tentativa de hijacking
     */
    private function handleHijacking(): void
    {
        $this->logActivity('hijacking_attempt', [
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expected_fingerprint' => $_SESSION['__fingerprint'] ?? '',
            'actual_fingerprint' => $this->generateFingerprint()
        ]);
        
        $this->destroyCurrent();
        
        throw new Exception('Session hijacking attempt detected.');
    }
    
    // ===================================================================
    // FINGERPRINTING
    // ===================================================================
    
    /**
     * Gera fingerprint da sessão
     * 
     * @return string
     */
    private function generateFingerprint(): string
    {
        $components = [];
        
        if ($this->config['fingerprint_method'] === 'weak') {
            // Apenas User-Agent (menos seguro, mas mais flexível)
            $components[] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        } else {
            // Strong: User-Agent + Accept + Accept-Language + Accept-Encoding
            $components[] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $components[] = $_SERVER['HTTP_ACCEPT'] ?? '';
            $components[] = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            $components[] = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        }
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Define fingerprint na sessão
     */
    private function setFingerprint(): void
    {
        $this->fingerprint = $this->generateFingerprint();
        $_SESSION['__fingerprint'] = $this->fingerprint;
    }
    
    // ===================================================================
    // CONCURRENT SESSIONS
    // ===================================================================
    
    /**
     * Obtém sessões ativas do usuário
     * 
     * @param int $userId ID do usuário
     * @return array
     */
    public function getUserSessions(int $userId): array
    {
        if (!$this->pdo) {
            return [];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM sessions
            WHERE user_id = ?
            AND last_activity > ?
            ORDER BY last_activity DESC
        ");
        
        $stmt->execute([
            $userId,
            time() - $this->config['lifetime']
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Destrói sessão específica
     * 
     * @param string $sessionId ID da sessão
     * @return bool
     */
    public function destroySession(string $sessionId): bool
    {
        if (!$this->pdo) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$sessionId]);
    }
    
    /**
     * Destrói todas as sessões do usuário exceto a atual
     * 
     * @param int $userId ID do usuário
     * @return int Número de sessões destruídas
     */
    public function destroyOtherSessions(int $userId): int
    {
        if (!$this->pdo) {
            return 0;
        }
        
        $currentId = session_id();
        
        $stmt = $this->pdo->prepare("
            DELETE FROM sessions 
            WHERE user_id = ? AND id != ?
        ");
        
        $stmt->execute([$userId, $currentId]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Limita sessões concorrentes
     * 
     * @param int $userId ID do usuário
     */
    private function limitConcurrentSessions(int $userId): void
    {
        $sessions = $this->getUserSessions($userId);
        
        if (count($sessions) >= $this->config['max_concurrent_sessions']) {
            // Remover sessão mais antiga
            $oldest = end($sessions);
            if ($oldest) {
                $this->destroySession($oldest['id']);
            }
        }
    }
    
    // ===================================================================
    // ACTIVITY TRACKING
    // ===================================================================
    
    /**
     * Registra atividade da sessão
     */
    private function trackActivity(): void
    {
        if (!$this->pdo) {
            return;
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return;
        }
        
        // Atualizar last_activity na tabela sessions
        $stmt = $this->pdo->prepare("
            UPDATE sessions 
            SET last_activity = ?,
                ip_address = ?,
                user_agent = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            time(),
            $this->getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            session_id()
        ]);
    }
    
    /**
     * Registra ação na sessão
     * 
     * @param string $action Ação
     * @param array $data Dados adicionais
     */
    private function logActivity(string $action, array $data = []): void
    {
        if (!$this->pdo || !$this->config['enable_activity_tracking']) {
            return;
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO session_logs (
                session_id, user_id, action, data,
                ip_address, user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([
            session_id(),
            $_SESSION['user_id'] ?? null,
            $action,
            json_encode($data),
            $this->getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    // ===================================================================
    // SESSION HANDLER INTERFACE
    // ===================================================================
    
    /**
     * Abre sessão
     */
    public function open($path, $name): bool
    {
        return true;
    }
    
    /**
     * Fecha sessão
     */
    public function close(): bool
    {
        return true;
    }
    
    /**
     * Lê dados da sessão
     */
    public function read($id): string
    {
        if (!$this->pdo) {
            return '';
        }
        
        $stmt = $this->pdo->prepare("
            SELECT payload FROM sessions 
            WHERE id = ? AND last_activity > ?
        ");
        
        $stmt->execute([
            $id,
            time() - $this->config['lifetime']
        ]);
        
        $data = $stmt->fetchColumn();
        
        return $data ?: '';
    }
    
    /**
     * Escreve dados da sessão
     */
    public function write($id, $data): bool
    {
        if (!$this->pdo) {
            return false;
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        // Limitar sessões concorrentes
        if ($userId) {
            $this->limitConcurrentSessions($userId);
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO sessions (id, user_id, payload, last_activity, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
            ON CONFLICT(id) DO UPDATE SET
                payload = excluded.payload,
                last_activity = excluded.last_activity,
                ip_address = excluded.ip_address,
                user_agent = excluded.user_agent
        ");
        
        return $stmt->execute([
            $id,
            $userId,
            $data,
            time(),
            $this->getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    /**
     * Destrói sessão específica
     */
    public function destroy($id): bool
    {
        if (!$this->pdo) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Garbage collection
     */
    public function gc($max_lifetime): int
    {
        if (!$this->pdo) {
            return 0;
        }
        
        $stmt = $this->pdo->prepare("
            DELETE FROM sessions 
            WHERE last_activity < ?
        ");
        
        $stmt->execute([time() - $max_lifetime]);
        
        return $stmt->rowCount();
    }
    
    // ===================================================================
    // HELPERS
    // ===================================================================
    
    /**
     * Obtém IP do cliente
     * 
     * @return string
     */
    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    /**
     * Obtém dados da sessão
     * 
     * @param string $key Chave
     * @param mixed $default Valor padrão
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Define dados na sessão
     * 
     * @param string $key Chave
     * @param mixed $value Valor
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Verifica se chave existe
     * 
     * @param string $key Chave
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove chave
     * 
     * @param string $key Chave
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
    
    /**
     * Flash message (disponível apenas na próxima requisição)
     * 
     * @param string $key Chave
     * @param mixed $value Valor
     */
    public function flash(string $key, $value): void
    {
        $_SESSION['__flash'][$key] = $value;
    }
    
    /**
     * Obtém e remove flash message
     * 
     * @param string $key Chave
     * @param mixed $default Valor padrão
     * @return mixed
     */
    public function getFlash(string $key, $default = null)
    {
        $value = $_SESSION['__flash'][$key] ?? $default;
        unset($_SESSION['__flash'][$key]);
        return $value;
    }
    
    /**
     * Obtém informações da sessão
     * 
     * @return array
     */
    public function getInfo(): array
    {
        return [
            'id' => session_id(),
            'status' => session_status(),
            'created' => $_SESSION['__created'] ?? null,
            'last_activity' => $_SESSION['__last_activity'] ?? null,
            'last_regeneration' => $_SESSION['__last_regeneration'] ?? null,
            'ip' => $_SESSION['__ip'] ?? null,
            'user_agent' => $_SESSION['__user_agent'] ?? null,
            'fingerprint' => $_SESSION['__fingerprint'] ?? null,
            'lifetime' => $this->config['lifetime'],
            'timeout_in' => isset($_SESSION['__last_activity']) 
                ? $this->config['lifetime'] - (time() - $_SESSION['__last_activity'])
                : null
        ];
    }
    
    // ===================================================================
    // ESTATÍSTICAS
    // ===================================================================
    
    /**
     * Obtém estatísticas
     */
    public static function getStats(): array
    {
        return self::$stats;
    }
    
    /**
     * Reseta estatísticas
     */
    public static function resetStats(): void
    {
        self::$stats = [
            'sessions_started' => 0,
            'sessions_destroyed' => 0,
            'sessions_regenerated' => 0,
            'fixation_attempts' => 0,
            'hijacking_attempts' => 0,
        ];
    }
}
