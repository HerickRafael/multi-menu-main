<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

use PDO;

/**
 * AuditLog Model
 * 
 * Registra TODA ação crítica do super admin para auditoria completa.
 * Cada ação é imutável — nunca é deletada, apenas criada.
 * 
 * Padrão multi-tenant: Sempre filtra por super_admin_id e opcionalmente company_id.
 */
class AuditLog
{
    /**
     * Criar novo registro de auditoria
     * 
     * @param int $super_admin_id ID do super admin que realizou a ação
     * @param string $action Ação realizada: 'create', 'update', 'delete', 'suspend', 'activate', 'impersonate', etc
     * @param string $module Módulo afetado: 'stores', 'users', 'orders', 'impersonations', 'resources'
     * @param string $entity_type Tipo de entidade: 'company', 'user', 'order', 'impersonation'
     * @param int|null $entity_id ID da entidade afetada
     * @param int|null $company_id ID da loja afetada (isolamento multi-tenant)
     * @param array|null $old_data Valores antigos (JSON)
     * @param array|null $new_data Novos valores (JSON)
     * @param string|null $description Descrição legível da ação
     * @param string|null $ip_address IP do cliente
     * @param string|null $user_agent User agent do browser
     * @return bool True se criado com sucesso
     */
    public static function create(
        int $super_admin_id,
        string $action,
        string $module,
        string $entity_type,
        ?int $entity_id,
        ?int $company_id,
        ?array $old_data = null,
        ?array $new_data = null,
        ?string $description = null,
        ?string $ip_address = null,
        ?string $user_agent = null
    ): bool {
        try {
            $db = db();
            
            $sql = "
                INSERT INTO audit_logs 
                (super_admin_id, action, module, entity_type, entity_id, company_id, 
                 old_data, new_data, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $super_admin_id,
                $action,
                $module,
                $entity_type,
                $entity_id,
                $company_id,
                $old_data ? json_encode($old_data) : null,
                $new_data ? json_encode($new_data) : null,
                $description,
                $ip_address ?? self::getClientIp(),
                $user_agent ?? $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("AuditLog::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter histórico de auditoria de um super admin
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $limit Limite de registros
     * @param int $offset Deslocamento para paginação
     * @return array Lista de logs de auditoria
     */
    public static function getByAdmin(int $super_admin_id, int $limit = 50, int $offset = 0): array
    {
        try {
            $db = db();
            
            $sql = "
                SELECT * FROM audit_logs
                WHERE super_admin_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$super_admin_id, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("AuditLog::getByAdmin error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obter histórico de auditoria de uma loja (company)
     * 
     * @param int $company_id ID da loja
     * @param int $limit Limite de registros
     * @param int $offset Deslocamento para paginação
     * @return array Lista de logs da loja
     */
    public static function getByCompany(int $company_id, int $limit = 50, int $offset = 0): array
    {
        try {
            $db = db();
            
            $sql = "
                SELECT * FROM audit_logs
                WHERE company_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$company_id, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("AuditLog::getByCompany error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obter histórico de uma ação específica (ex: todas as suspensões)
     * 
     * @param string $action Ação a filtrar
     * @param int $limit Limite de registros
     * @return array Lista de logs filtrada por ação
     */
    public static function getByAction(string $action, int $limit = 50): array
    {
        try {
            $db = db();
            
            $sql = "
                SELECT * FROM audit_logs
                WHERE action = ?
                ORDER BY created_at DESC
                LIMIT ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$action, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("AuditLog::getByAction error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar logs de auditoria com filtros complexos
     * 
     * @param array $filters Filtros: super_admin_id, company_id, action, module, entity_type, date_from, date_to
     * @param int $limit Limite de registros
     * @param int $offset Deslocamento
     * @return array Lista filtrada
     */
    public static function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        try {
            $db = db();
            
            $sql = "SELECT * FROM audit_logs WHERE 1=1";
            $params = [];
            
            if (!empty($filters['super_admin_id'])) {
                $sql .= " AND super_admin_id = ?";
                $params[] = $filters['super_admin_id'];
            }
            
            if (!empty($filters['company_id'])) {
                $sql .= " AND company_id = ?";
                $params[] = $filters['company_id'];
            }
            
            if (!empty($filters['action'])) {
                $sql .= " AND action = ?";
                $params[] = $filters['action'];
            }
            
            if (!empty($filters['module'])) {
                $sql .= " AND module = ?";
                $params[] = $filters['module'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("AuditLog::search error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar total de logs para um filtro (para paginação)
     * 
     * @param array $filters Filtros iguais a search()
     * @return int Contagem total
     */
    public static function count(array $filters): int
    {
        try {
            $db = db();
            
            $sql = "SELECT COUNT(*) as total FROM audit_logs WHERE 1=1";
            $params = [];
            
            if (!empty($filters['super_admin_id'])) {
                $sql .= " AND super_admin_id = ?";
                $params[] = $filters['super_admin_id'];
            }
            
            if (!empty($filters['company_id'])) {
                $sql .= " AND company_id = ?";
                $params[] = $filters['company_id'];
            }
            
            if (!empty($filters['action'])) {
                $sql .= " AND action = ?";
                $params[] = $filters['action'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (\Exception $e) {
            error_log("AuditLog::count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obter um log específico por ID
     * 
     * @param int $id ID do log
     * @return array|null Log ou null se não encontrado
     */
    public static function getById(int $id): ?array
    {
        try {
            $db = db();
            
            $sql = "SELECT * FROM audit_logs WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("AuditLog::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obter o IP do cliente
     * 
     * @return string IP do cliente
     */
    private static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        return trim($ip);
    }
}
