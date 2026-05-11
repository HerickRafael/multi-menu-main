<?php
/**
 * Authorization System - RBAC (Role-Based Access Control)
 * 
 * Sistema completo de autorização com:
 * - Role-Based Access Control (RBAC)
 * - Permissions granulares (create, read, update, delete)
 * - Hierarquia de roles (admin > manager > user)
 * - Gates e Policies
 * - Middleware de autorização
 * - Auditoria de acessos
 * - Cache de permissões
 * 
 * @package MultiMenu
 * @subpackage Middleware
 * @version 1.0.0
 */

namespace App\Middleware;

use PDO;
use Exception;

class Authorization
{
    /**
     * Instância PDO
     * @var PDO
     */
    private PDO $pdo;
    
    /**
     * Usuário autenticado atual
     * @var array|null
     */
    private ?array $currentUser = null;
    
    /**
     * Cache de permissões
     * @var array
     */
    private array $permissionsCache = [];
    
    /**
     * Gates customizados
     * @var array
     */
    private static array $gates = [];
    
    /**
     * Policies customizadas
     * @var array
     */
    private static array $policies = [];
    
    /**
     * Hierarquia de roles
     * @var array
     */
    private array $roleHierarchy = [
        'super_admin' => ['admin', 'manager', 'user', 'guest'],
        'admin' => ['manager', 'user', 'guest'],
        'manager' => ['user', 'guest'],
        'user' => ['guest'],
        'guest' => []
    ];
    
    /**
     * Configurações
     * @var array
     */
    private array $config = [
        'enable_cache' => true,
        'cache_ttl' => 3600, // 1 hora
        'enable_audit' => true,
        'strict_mode' => false, // Se true, exige permissão explícita
    ];
    
    /**
     * Estatísticas
     * @var array
     */
    private static array $stats = [
        'authorization_checks' => 0,
        'authorized_actions' => 0,
        'denied_actions' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
    ];
    
    /**
     * Construtor
     * 
     * @param PDO $pdo Instância PDO
     * @param array|null $user Usuário autenticado
     * @param array $config Configurações customizadas
     */
    public function __construct(PDO $pdo, ?array $user = null, array $config = [])
    {
        $this->pdo = $pdo;
        $this->currentUser = $user;
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Define usuário autenticado
     * 
     * @param array $user Dados do usuário
     */
    public function setUser(array $user): void
    {
        $this->currentUser = $user;
        $this->permissionsCache = []; // Limpar cache
    }
    
    /**
     * Obtém usuário atual
     * 
     * @return array|null
     */
    public function getUser(): ?array
    {
        return $this->currentUser;
    }
    
    // ===================================================================
    // ROLE MANAGEMENT
    // ===================================================================
    
    /**
     * Atribui role a um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $roleName Nome do role
     * @return bool True se bem-sucedido
     * @throws Exception Se falhar
     */
    public function assignRole(int $userId, string $roleName): bool
    {
        // Verificar se role existe
        $role = $this->getRoleByName($roleName);
        if (!$role) {
            throw new Exception("Role '{$roleName}' não existe.");
        }
        
        // Verificar se já possui o role
        if ($this->hasRole($userId, $roleName)) {
            return true; // Já possui
        }
        
        // Atribuir role
        $stmt = $this->pdo->prepare("
            INSERT INTO user_roles (user_id, role_id, created_at)
            VALUES (?, ?, datetime('now'))
        ");
        
        $result = $stmt->execute([$userId, $role['id']]);
        
        if ($result && $this->config['enable_audit']) {
            $this->logAccess($userId, 'role_assigned', $roleName, true);
        }
        
        // Limpar cache
        $this->clearUserCache($userId);
        
        return $result;
    }
    
    /**
     * Remove role de um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $roleName Nome do role
     * @return bool True se bem-sucedido
     */
    public function removeRole(int $userId, string $roleName): bool
    {
        $role = $this->getRoleByName($roleName);
        if (!$role) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("
            DELETE FROM user_roles 
            WHERE user_id = ? AND role_id = ?
        ");
        
        $result = $stmt->execute([$userId, $role['id']]);
        
        if ($result && $this->config['enable_audit']) {
            $this->logAccess($userId, 'role_removed', $roleName, true);
        }
        
        // Limpar cache
        $this->clearUserCache($userId);
        
        return $result;
    }
    
    /**
     * Verifica se usuário possui role
     * 
     * @param int|null $userId ID do usuário (null = usuário atual)
     * @param string|array $roles Nome(s) do(s) role(s)
     * @return bool True se possui pelo menos um dos roles
     */
    public function hasRole($userId = null, $roles = null): bool
    {
        if ($userId === null) {
            if (!$this->currentUser) {
                return false;
            }
            $userId = $this->currentUser['id'];
        }
        
        if ($roles === null) {
            return false;
        }
        
        $roles = is_array($roles) ? $roles : [$roles];
        $userRoles = $this->getUserRoles($userId);
        
        // Verificar roles diretos
        foreach ($roles as $role) {
            if (in_array($role, $userRoles)) {
                return true;
            }
            
            // Verificar hierarquia
            foreach ($userRoles as $userRole) {
                if ($this->roleInherits($userRole, $role)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Verifica se role herda de outro
     * 
     * @param string $role Role a verificar
     * @param string $inherits Role que deve herdar
     * @return bool
     */
    private function roleInherits(string $role, string $inherits): bool
    {
        if (!isset($this->roleHierarchy[$role])) {
            return false;
        }
        
        return in_array($inherits, $this->roleHierarchy[$role]);
    }
    
    /**
     * Obtém roles do usuário
     * 
     * @param int $userId ID do usuário
     * @return array Lista de nomes de roles
     */
    public function getUserRoles(int $userId): array
    {
        $cacheKey = "user_roles_{$userId}";
        
        if ($this->config['enable_cache'] && isset($this->permissionsCache[$cacheKey])) {
            self::$stats['cache_hits']++;
            return $this->permissionsCache[$cacheKey];
        }
        
        self::$stats['cache_misses']++;
        
        $stmt = $this->pdo->prepare("
            SELECT r.name
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ?
        ");
        
        $stmt->execute([$userId]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if ($this->config['enable_cache']) {
            $this->permissionsCache[$cacheKey] = $roles;
        }
        
        return $roles;
    }
    
    // ===================================================================
    // PERMISSION MANAGEMENT
    // ===================================================================
    
    /**
     * Atribui permissão a um role
     * 
     * @param string $roleName Nome do role
     * @param string $permissionName Nome da permissão
     * @return bool True se bem-sucedido
     * @throws Exception Se falhar
     */
    public function assignPermission(string $roleName, string $permissionName): bool
    {
        $role = $this->getRoleByName($roleName);
        $permission = $this->getPermissionByName($permissionName);
        
        if (!$role) {
            throw new Exception("Role '{$roleName}' não existe.");
        }
        
        if (!$permission) {
            throw new Exception("Permission '{$permissionName}' não existe.");
        }
        
        // Verificar se já possui
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM role_permissions 
            WHERE role_id = ? AND permission_id = ?
        ");
        $stmt->execute([$role['id'], $permission['id']]);
        
        if ($stmt->fetchColumn() > 0) {
            return true; // Já possui
        }
        
        // Atribuir permissão
        $stmt = $this->pdo->prepare("
            INSERT INTO role_permissions (role_id, permission_id, created_at)
            VALUES (?, ?, datetime('now'))
        ");
        
        return $stmt->execute([$role['id'], $permission['id']]);
    }
    
    /**
     * Remove permissão de um role
     * 
     * @param string $roleName Nome do role
     * @param string $permissionName Nome da permissão
     * @return bool True se bem-sucedido
     */
    public function removePermission(string $roleName, string $permissionName): bool
    {
        $role = $this->getRoleByName($roleName);
        $permission = $this->getPermissionByName($permissionName);
        
        if (!$role || !$permission) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("
            DELETE FROM role_permissions 
            WHERE role_id = ? AND permission_id = ?
        ");
        
        return $stmt->execute([$role['id'], $permission['id']]);
    }
    
    /**
     * Verifica se usuário tem permissão
     * 
     * @param string|array $permissions Nome(s) da(s) permissão(ões)
     * @param int|null $userId ID do usuário (null = usuário atual)
     * @return bool True se possui pelo menos uma das permissões
     */
    public function can($permissions, $userId = null): bool
    {
        self::$stats['authorization_checks']++;
        
        if ($userId === null) {
            if (!$this->currentUser) {
                self::$stats['denied_actions']++;
                return false;
            }
            $userId = $this->currentUser['id'];
        }
        
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $userPermissions = $this->getUserPermissions($userId);
        
        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                self::$stats['authorized_actions']++;
                
                if ($this->config['enable_audit']) {
                    $this->logAccess($userId, 'permission_check', $permission, true);
                }
                
                return true;
            }
        }
        
        self::$stats['denied_actions']++;
        
        if ($this->config['enable_audit']) {
            $this->logAccess($userId, 'permission_denied', implode(',', $permissions), false);
        }
        
        return false;
    }
    
    /**
     * Verifica se usuário NÃO tem permissão
     * 
     * @param string|array $permissions Nome(s) da(s) permissão(ões)
     * @param int|null $userId ID do usuário
     * @return bool True se NÃO possui nenhuma das permissões
     */
    public function cannot($permissions, $userId = null): bool
    {
        return !$this->can($permissions, $userId);
    }
    
    /**
     * Obtém permissões do usuário
     * 
     * @param int $userId ID do usuário
     * @return array Lista de nomes de permissões
     */
    public function getUserPermissions(int $userId): array
    {
        $cacheKey = "user_permissions_{$userId}";
        
        if ($this->config['enable_cache'] && isset($this->permissionsCache[$cacheKey])) {
            self::$stats['cache_hits']++;
            return $this->permissionsCache[$cacheKey];
        }
        
        self::$stats['cache_misses']++;
        
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.name
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ?
        ");
        
        $stmt->execute([$userId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if ($this->config['enable_cache']) {
            $this->permissionsCache[$cacheKey] = $permissions;
        }
        
        return $permissions;
    }
    
    // ===================================================================
    // GATES E POLICIES
    // ===================================================================
    
    /**
     * Define um gate
     * 
     * @param string $name Nome do gate
     * @param callable $callback Função de verificação
     */
    public static function defineGate(string $name, callable $callback): void
    {
        self::$gates[$name] = $callback;
    }
    
    /**
     * Verifica um gate
     * 
     * @param string $name Nome do gate
     * @param mixed ...$args Argumentos para o gate
     * @return bool True se autorizado
     */
    public function checkGate(string $name, ...$args): bool
    {
        if (!isset(self::$gates[$name])) {
            return false;
        }
        
        $callback = self::$gates[$name];
        $result = $callback($this->currentUser, ...$args);
        
        if ($this->config['enable_audit']) {
            $this->logAccess(
                $this->currentUser['id'] ?? null,
                'gate_check',
                $name,
                $result
            );
        }
        
        return $result;
    }
    
    /**
     * Define uma policy
     * 
     * @param string $model Nome do modelo
     * @param string $action Ação (view, create, update, delete)
     * @param callable $callback Função de verificação
     */
    public static function definePolicy(string $model, string $action, callable $callback): void
    {
        if (!isset(self::$policies[$model])) {
            self::$policies[$model] = [];
        }
        
        self::$policies[$model][$action] = $callback;
    }
    
    /**
     * Verifica uma policy
     * 
     * @param string $model Nome do modelo
     * @param string $action Ação
     * @param mixed $resource Recurso (opcional)
     * @return bool True se autorizado
     */
    public function checkPolicy(string $model, string $action, $resource = null): bool
    {
        if (!isset(self::$policies[$model][$action])) {
            return false;
        }
        
        $callback = self::$policies[$model][$action];
        $result = $callback($this->currentUser, $resource);
        
        if ($this->config['enable_audit']) {
            $this->logAccess(
                $this->currentUser['id'] ?? null,
                'policy_check',
                "{$model}.{$action}",
                $result
            );
        }
        
        return $result;
    }
    
    // ===================================================================
    // MIDDLEWARE
    // ===================================================================
    
    /**
     * Middleware para verificar role
     * 
     * @param string|array $roles Role(s) requerido(s)
     * @throws Exception Se não autorizado
     */
    public function requireRole($roles): void
    {
        if (!$this->hasRole(null, $roles)) {
            throw new Exception('Acesso negado. Role insuficiente.');
        }
    }
    
    /**
     * Middleware para verificar permissão
     * 
     * @param string|array $permissions Permissão(ões) requerida(s)
     * @throws Exception Se não autorizado
     */
    public function requirePermission($permissions): void
    {
        if (!$this->can($permissions)) {
            throw new Exception('Acesso negado. Permissão insuficiente.');
        }
    }
    
    /**
     * Middleware para verificar gate
     * 
     * @param string $gate Nome do gate
     * @param mixed ...$args Argumentos
     * @throws Exception Se não autorizado
     */
    public function requireGate(string $gate, ...$args): void
    {
        if (!$this->checkGate($gate, ...$args)) {
            throw new Exception('Acesso negado. Gate não autorizado.');
        }
    }
    
    /**
     * Middleware para verificar policy
     * 
     * @param string $model Nome do modelo
     * @param string $action Ação
     * @param mixed $resource Recurso
     * @throws Exception Se não autorizado
     */
    public function requirePolicy(string $model, string $action, $resource = null): void
    {
        if (!$this->checkPolicy($model, $action, $resource)) {
            throw new Exception('Acesso negado. Policy não autorizada.');
        }
    }
    
    // ===================================================================
    // RESOURCE OWNERSHIP
    // ===================================================================
    
    /**
     * Verifica se usuário é dono do recurso
     * 
     * @param int $userId ID do usuário
     * @param mixed $resource Recurso (array ou objeto com user_id)
     * @return bool
     */
    public function ownsResource(int $userId, $resource): bool
    {
        if (is_array($resource)) {
            return isset($resource['user_id']) && $resource['user_id'] == $userId;
        }
        
        if (is_object($resource)) {
            return isset($resource->user_id) && $resource->user_id == $userId;
        }
        
        return false;
    }
    
    /**
     * Verifica se usuário pode acessar recurso (dono ou admin)
     * 
     * @param mixed $resource Recurso
     * @param int|null $userId ID do usuário
     * @return bool
     */
    public function canAccess($resource, $userId = null): bool
    {
        if ($userId === null) {
            if (!$this->currentUser) {
                return false;
            }
            $userId = $this->currentUser['id'];
        }
        
        // Admin pode tudo
        if ($this->hasRole($userId, ['admin', 'super_admin'])) {
            return true;
        }
        
        // Verificar ownership
        return $this->ownsResource($userId, $resource);
    }
    
    // ===================================================================
    // HELPERS
    // ===================================================================
    
    /**
     * Cria um novo role
     * 
     * @param string $name Nome do role
     * @param string $description Descrição
     * @return int ID do role criado
     */
    public function createRole(string $name, string $description = ''): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO roles (name, description, created_at)
            VALUES (?, ?, datetime('now'))
        ");
        
        $stmt->execute([$name, $description]);
        
        return (int)$this->pdo->lastInsertId();
    }
    
    /**
     * Cria uma nova permissão
     * 
     * @param string $name Nome da permissão
     * @param string $description Descrição
     * @return int ID da permissão criada
     */
    public function createPermission(string $name, string $description = ''): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO permissions (name, description, created_at)
            VALUES (?, ?, datetime('now'))
        ");
        
        $stmt->execute([$name, $description]);
        
        return (int)$this->pdo->lastInsertId();
    }
    
    /**
     * Busca role por nome
     */
    private function getRoleByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE name = ?");
        $stmt->execute([$name]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $role ?: null;
    }
    
    /**
     * Busca permissão por nome
     */
    private function getPermissionByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM permissions WHERE name = ?");
        $stmt->execute([$name]);
        $permission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $permission ?: null;
    }
    
    /**
     * Limpa cache de usuário
     */
    private function clearUserCache(int $userId): void
    {
        unset($this->permissionsCache["user_roles_{$userId}"]);
        unset($this->permissionsCache["user_permissions_{$userId}"]);
    }
    
    /**
     * Registra acesso/tentativa
     */
    private function logAccess($userId, string $action, string $resource, bool $granted): void
    {
        if (!$this->config['enable_audit']) {
            return;
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO access_logs (
                user_id, action, resource, granted, 
                ip_address, user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $resource,
            $granted ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
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
            'authorization_checks' => 0,
            'authorized_actions' => 0,
            'denied_actions' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];
    }
}
