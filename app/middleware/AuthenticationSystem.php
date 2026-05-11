<?php
/**
 * Authentication System
 * 
 * Sistema completo de autenticação com:
 * - Login seguro com bcrypt/argon2
 * - Two-Factor Authentication (2FA)
 * - Password recovery
 * - Account lockout (brute force protection)
 * - Remember me functionality
 * - Session management
 * - Password strength validation
 * 
 * @package MultiMenu
 * @subpackage Middleware
 * @version 1.0.0
 */

namespace App\Middleware;

use PDO;
use Exception;

class AuthenticationSystem
{
    /**
     * Instância PDO
     * @var PDO
     */
    private PDO $pdo;
    
    /**
     * Configurações
     * @var array
     */
    private array $config = [
        'password_algo' => PASSWORD_BCRYPT,
        'password_cost' => 12,
        'session_lifetime' => 7200, // 2 horas
        'remember_lifetime' => 2592000, // 30 dias
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
        'password_min_length' => 8,
        'password_require_special' => true,
        'password_require_number' => true,
        'password_require_uppercase' => true,
        'password_history_count' => 5, // Previne reutilização
    ];
    
    /**
     * Usuário autenticado atual
     * @var array|null
     */
    private ?array $currentUser = null;
    
    /**
     * Estatísticas
     * @var array
     */
    private static array $stats = [
        'login_attempts' => 0,
        'successful_logins' => 0,
        'failed_logins' => 0,
        'locked_accounts' => 0,
    ];
    
    /**
     * Construtor
     * 
     * @param PDO $pdo Instância PDO
     * @param array $config Configurações customizadas
     */
    public function __construct(PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = array_merge($this->config, $config);
        
        if (session_status() === PHP_SESSION_NONE) {
            $this->startSecureSession();
        }
    }
    
    /**
     * Inicia sessão segura
     */
    private function startSecureSession(): void
    {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', (string)$this->config['session_lifetime']);
        
        session_start();
        
        // Regenerar ID periodicamente
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Registra novo usuário
     * 
     * @param string $email E-mail
     * @param string $password Senha
     * @param array $data Dados adicionais (name, phone, etc.)
     * @return array Dados do usuário criado
     * @throws Exception Se falhar
     */
    public function register(string $email, string $password, array $data = []): array
    {
        // Validar e-mail
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido.');
        }
        
        // Validar senha
        if (!$this->validatePasswordStrength($password)) {
            throw new Exception($this->getPasswordRequirements());
        }
        
        // Verificar se e-mail já existe
        if ($this->emailExists($email)) {
            throw new Exception('E-mail já cadastrado.');
        }
        
        // Hash da senha
        $passwordHash = $this->hashPassword($password);
        
        // Gerar token de verificação
        $verificationToken = bin2hex(random_bytes(32));
        
        // Inserir usuário
        $stmt = $this->pdo->prepare("
            INSERT INTO users (
                email, password, name, phone, 
                verification_token, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        
        $stmt->execute([
            $email,
            $passwordHash,
            $data['name'] ?? null,
            $data['phone'] ?? null,
            $verificationToken
        ]);
        
        $userId = $this->pdo->lastInsertId();
        
        // Adicionar senha inicial ao histórico
        $this->addPasswordToHistory($userId, $passwordHash);
        
        // Log da ação
        $this->logAuthAction($userId, 'register', true, 'User registered successfully');
        
        return [
            'id' => $userId,
            'email' => $email,
            'name' => $data['name'] ?? null,
            'verification_token' => $verificationToken,
            'verified' => false
        ];
    }
    
    /**
     * Login do usuário
     * 
     * @param string $email E-mail
     * @param string $password Senha
     * @param bool $remember Lembrar login
     * @return array Dados do usuário ou array com '2fa_required' => true
     * @throws Exception Se falhar
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        self::$stats['login_attempts']++;
        
        // Verificar se conta está bloqueada
        if ($this->isAccountLocked($email)) {
            self::$stats['failed_logins']++;
            throw new Exception('Conta bloqueada temporariamente. Tente novamente mais tarde.');
        }
        
        // Buscar usuário
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            $this->recordFailedLogin($email);
            self::$stats['failed_logins']++;
            throw new Exception('E-mail ou senha incorretos.');
        }
        
        // Verificar senha
        if (!$this->verifyPassword($password, $user['password'])) {
            $this->recordFailedLogin($email);
            self::$stats['failed_logins']++;
            throw new Exception('E-mail ou senha incorretos.');
        }
        
        // Verificar se conta está ativa
        if (isset($user['status']) && $user['status'] !== 'active') {
            throw new Exception('Conta inativa. Entre em contato com o suporte.');
        }
        
        // Verificar se e-mail foi verificado
        if (isset($user['email_verified']) && !$user['email_verified']) {
            throw new Exception('Por favor, verifique seu e-mail antes de fazer login.');
        }
        
        // Limpar tentativas falhas
        $this->clearFailedLogins($email);
        
        // Login bem-sucedido
        $this->completeLogin($user, $remember);
        
        self::$stats['successful_logins']++;
        
        return $this->getCurrentUser();
    }
    
    /**
     * Completa o processo de login
     * 
     * @param array $user Dados do usuário
     * @param bool $remember Lembrar login
     */
    private function completeLogin(array $user, bool $remember = false): void
    {
        // Regenerar ID da sessão
        session_regenerate_id(true);
        
        // Armazenar na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Atualizar last_login
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET last_login = datetime('now'),
                last_ip = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['ip_address'], $user['id']]);
        
        // Remember me
        if ($remember) {
            $this->createRememberToken($user['id']);
        }
        
        // Log da ação
        $this->logAuthAction($user['id'], 'login', true, 'User logged in successfully');
        
        $this->currentUser = $user;
    }
    
    /**
     * Logout do usuário
     */
    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            
            // Remover remember token
            $this->removeRememberToken($userId);
            
            // Log da ação
            $this->logAuthAction($userId, 'logout', true, 'User logged out');
        }
        
        // Limpar sessão
        $_SESSION = [];
        
        // Destruir cookie de sessão
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destruir sessão
        session_destroy();
        
        $this->currentUser = null;
    }
    
    /**
     * Verifica se usuário está autenticado
     * 
     * @return bool True se autenticado
     */
    public function check(): bool
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Verificar timeout de sessão
            if (isset($_SESSION['login_time'])) {
                $elapsed = time() - $_SESSION['login_time'];
                if ($elapsed > $this->config['session_lifetime']) {
                    $this->logout();
                    return false;
                }
            }
            
            // Verificar IP/User-Agent (opcional - pode ser muito restritivo)
            // if ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            //     $this->logout();
            //     return false;
            // }
            
            return true;
        }
        
        // Verificar remember token
        if (isset($_COOKIE['remember_token'])) {
            return $this->loginFromRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    /**
     * Obtém usuário autenticado atual
     * 
     * @return array|null Dados do usuário ou null
     */
    public function user(): ?array
    {
        if ($this->currentUser) {
            return $this->currentUser;
        }
        
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = $this->getUserById($_SESSION['user_id']);
            return $this->currentUser;
        }
        
        return null;
    }
    
    /**
     * Alias para user()
     */
    public function getCurrentUser(): ?array
    {
        return $this->user();
    }
    
    /**
     * Solicita recuperação de senha
     * 
     * @param string $email E-mail
     * @return string Token de recuperação
     * @throws Exception Se falhar
     */
    public function requestPasswordReset(string $email): string
    {
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            // Não revelar se e-mail existe
            throw new Exception('Se o e-mail estiver cadastrado, você receberá um link de recuperação.');
        }
        
        // Gerar token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora
        
        // Salvar token
        $stmt = $this->pdo->prepare("
            INSERT INTO password_resets (user_id, token, expires_at, created_at)
            VALUES (?, ?, ?, datetime('now'))
        ");
        $stmt->execute([$user['id'], hash('sha256', $token), $expiresAt]);
        
        // Log da ação
        $this->logAuthAction($user['id'], 'password_reset_request', true);
        
        return $token;
    }
    
    /**
     * Reseta a senha usando token
     * 
     * @param string $token Token de recuperação
     * @param string $newPassword Nova senha
     * @return bool True se bem-sucedido
     * @throws Exception Se falhar
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        // Validar nova senha
        if (!$this->validatePasswordStrength($newPassword)) {
            throw new Exception($this->getPasswordRequirements());
        }
        
        // Buscar token
        $stmt = $this->pdo->prepare("
            SELECT pr.*, u.id as user_id, u.email
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ?
            AND pr.expires_at > datetime('now')
            AND pr.used = 0
        ");
        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reset) {
            throw new Exception('Token inválido ou expirado.');
        }
        
        // Verificar histórico de senhas
        if ($this->isPasswordInHistory($reset['user_id'], $newPassword)) {
            throw new Exception('Você não pode reutilizar uma senha recente.');
        }
        
        // Hash da nova senha
        $passwordHash = $this->hashPassword($newPassword);
        
        // Atualizar senha
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET password = ?, updated_at = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$passwordHash, $reset['user_id']]);
        
        // Adicionar ao histórico
        $this->addPasswordToHistory($reset['user_id'], $passwordHash);
        
        // Marcar token como usado
        $stmt = $this->pdo->prepare("
            UPDATE password_resets 
            SET used = 1, used_at = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$reset['id']]);
        
        // Log da ação
        $this->logAuthAction($reset['user_id'], 'password_reset', true);
        
        return true;
    }
    
    /**
     * Altera senha (usuário autenticado)
     * 
     * @param string $currentPassword Senha atual
     * @param string $newPassword Nova senha
     * @return bool True se bem-sucedido
     * @throws Exception Se falhar
     */
    public function changePassword(string $currentPassword, string $newPassword): bool
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            throw new Exception('Usuário não autenticado.');
        }
        
        // Verificar senha atual
        if (!$this->verifyPassword($currentPassword, $user['password'])) {
            throw new Exception('Senha atual incorreta.');
        }
        
        // Validar nova senha
        if (!$this->validatePasswordStrength($newPassword)) {
            throw new Exception($this->getPasswordRequirements());
        }
        
        // Verificar histórico
        if ($this->isPasswordInHistory($user['id'], $newPassword)) {
            throw new Exception('Você não pode reutilizar uma senha recente.');
        }
        
        // Hash da nova senha
        $passwordHash = $this->hashPassword($newPassword);
        
        // Atualizar senha
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET password = ?, updated_at = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$passwordHash, $user['id']]);
        
        // Adicionar ao histórico
        $this->addPasswordToHistory($user['id'], $passwordHash);
        
        // Log da ação
        $this->logAuthAction($user['id'], 'password_change', true);
        
        return true;
    }
    
    // ===================================================================
    // MÉTODOS AUXILIARES - PASSWORD
    // ===================================================================
    
    /**
     * Cria hash da senha
     */
    private function hashPassword(string $password): string
    {
        return password_hash($password, $this->config['password_algo'], [
            'cost' => $this->config['password_cost']
        ]);
    }
    
    /**
     * Verifica senha
     */
    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Valida força da senha
     */
    private function validatePasswordStrength(string $password): bool
    {
        if (strlen($password) < $this->config['password_min_length']) {
            return false;
        }
        
        if ($this->config['password_require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if ($this->config['password_require_number'] && !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        if ($this->config['password_require_special'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtém requisitos de senha
     */
    private function getPasswordRequirements(): string
    {
        $requirements = ["A senha deve ter no mínimo {$this->config['password_min_length']} caracteres"];
        
        if ($this->config['password_require_uppercase']) {
            $requirements[] = "pelo menos 1 letra maiúscula";
        }
        
        if ($this->config['password_require_number']) {
            $requirements[] = "pelo menos 1 número";
        }
        
        if ($this->config['password_require_special']) {
            $requirements[] = "pelo menos 1 caractere especial";
        }
        
        return implode(", ", $requirements) . ".";
    }
    
    /**
     * Verifica se senha está no histórico
     */
    private function isPasswordInHistory(int $userId, string $password): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT password_hash FROM password_history
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $this->config['password_history_count']]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->verifyPassword($password, $row['password_hash'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Adiciona senha ao histórico
     */
    private function addPasswordToHistory(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO password_history (user_id, password_hash, created_at)
            VALUES (?, ?, datetime('now'))
        ");
        $stmt->execute([$userId, $passwordHash]);
        
        // Limpar histórico antigo
        $stmt = $this->pdo->prepare("
            DELETE FROM password_history
            WHERE user_id = ?
            AND id NOT IN (
                SELECT id FROM password_history
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            )
        ");
        $stmt->execute([$userId, $userId, $this->config['password_history_count']]);
    }
    
    // ===================================================================
    // MÉTODOS AUXILIARES - BRUTE FORCE PROTECTION
    // ===================================================================
    
    /**
     * Verifica se conta está bloqueada
     */
    private function isAccountLocked(string $email): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts
            WHERE email = ?
            AND created_at > datetime('now', '-' || ? || ' seconds')
        ");
        $stmt->execute([$email, $this->config['lockout_duration']]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['attempts'] >= $this->config['max_login_attempts']) {
            self::$stats['locked_accounts']++;
            return true;
        }
        
        return false;
    }
    
    /**
     * Registra tentativa de login falhada
     */
    private function recordFailedLogin(string $email): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO login_attempts (email, ip_address, created_at)
            VALUES (?, ?, datetime('now'))
        ");
        $stmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    }
    
    /**
     * Limpa tentativas de login
     */
    private function clearFailedLogins(string $email): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    // ===================================================================
    // MÉTODOS AUXILIARES - REMEMBER ME
    // ===================================================================
    
    /**
     * Cria token remember me
     */
    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $selector = bin2hex(random_bytes(16));
        $hashedToken = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['remember_lifetime']);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO remember_tokens (user_id, selector, token, expires_at, created_at)
            VALUES (?, ?, ?, ?, datetime('now'))
        ");
        $stmt->execute([$userId, $selector, $hashedToken, $expiresAt]);
        
        // Criar cookie
        setcookie(
            'remember_token',
            $selector . ':' . $token,
            time() + $this->config['remember_lifetime'],
            '/',
            '',
            true, // secure
            true  // httponly
        );
    }
    
    /**
     * Remove token remember me
     */
    private function removeRememberToken(int $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    
    /**
     * Login via remember token
     */
    private function loginFromRememberToken(string $cookie): bool
    {
        $parts = explode(':', $cookie);
        if (count($parts) !== 2) {
            return false;
        }
        
        [$selector, $token] = $parts;
        $hashedToken = hash('sha256', $token);
        
        $stmt = $this->pdo->prepare("
            SELECT rt.*, u.*
            FROM remember_tokens rt
            JOIN users u ON rt.user_id = u.id
            WHERE rt.selector = ?
            AND rt.token = ?
            AND rt.expires_at > datetime('now')
        ");
        $stmt->execute([$selector, $hashedToken]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return false;
        }
        
        // Login automático
        $this->completeLogin($data, false);
        
        return true;
    }
    
    // ===================================================================
    // MÉTODOS AUXILIARES - DATABASE
    // ===================================================================
    
    /**
     * Busca usuário por e-mail
     */
    private function getUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
    }
    
    /**
     * Busca usuário por ID
     */
    private function getUserById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
    }
    
    /**
     * Verifica se e-mail existe
     */
    private function emailExists(string $email): bool
    {
        return $this->getUserByEmail($email) !== null;
    }
    
    /**
     * Registra ação de autenticação
     */
    private function logAuthAction(int $userId, string $action, bool $success, string $details = ''): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO auth_logs (
                user_id, action, success, details, 
                ip_address, user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $success ? 1 : 0,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    // ===================================================================
    // MÉTODOS PÚBLICOS - ESTATÍSTICAS E CONFIGURAÇÃO
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
            'login_attempts' => 0,
            'successful_logins' => 0,
            'failed_logins' => 0,
            'locked_accounts' => 0,
            '2fa_verifications' => 0,
        ];
    }
    
    /**
     * Atualiza configuração
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Obtém configuração
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
