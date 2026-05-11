<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

use PDO;

/**
 * AdminImpersonation Model
 * 
 * Gerencia o recurso de "Entrar como loja" — permite que super admin
 * acesse a plataforma como se fosse um dono de loja, com sessão isolada
 * e auditoria completa.
 * 
 * Garantias de segurança:
 * - Session token único e cryptograficamente seguro
 * - Isolamento total: Super admin não vê dados de impersonation
 * - Auditoria: Toda impersonation é registrada
 * - Rastreamento: Ações durante impersonation são rastreadas
 */
class AdminImpersonation
{
    /**
     * Iniciar uma impersonação (super admin entra como loja)
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $company_id ID da loja a ser impersonada
     * @param string $reason Razão da impersonação (debugging, support, etc)
     * @param string $role Qual role: 'owner' ou 'staff'
     * @return array|false ['id' => int, 'session_token' => string] ou false
     */
    public static function start(int $super_admin_id, int $company_id, string $reason = '', string $role = 'owner'): array|false
    {
        try {
            $db = db();
            
            // Gerar session token seguro
            $session_token = bin2hex(random_bytes(32));
            
            $sql = "
                INSERT INTO admin_impersonations 
                (super_admin_id, company_id, session_token, impersonated_as_role, reason, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $super_admin_id,
                $company_id,
                $session_token,
                $role,
                $reason,
                self::getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            $impersonation_id = (int)$db->lastInsertId();
            
            return [
                'id' => $impersonation_id,
                'session_token' => $session_token
            ];
        } catch (\Exception $e) {
            error_log("AdminImpersonation::start error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Encerrar uma impersonação
     * 
     * @param int $impersonation_id ID da impersonation
     * @param string $final_note Observações ao encerrar
     * @return bool True se encerrado com sucesso
     */
    public static function end(int $impersonation_id, string $final_note = ''): bool
    {
        try {
            $db = db();
            
            $sql = "
                UPDATE admin_impersonations
                SET ended_at = NOW(), final_note = ?
                WHERE id = ? AND ended_at IS NULL
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$final_note, $impersonation_id]);
            
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log("AdminImpersonation::end error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter uma impersonação ativa pelo session token
     * 
     * @param string $session_token Token da sessão
     * @return array|null Impersonation ou null
     */
    public static function getBySessionToken(string $session_token): ?array
    {
        try {
            $db = db();
            
            $sql = "
                SELECT * FROM admin_impersonations
                WHERE session_token = ? AND ended_at IS NULL
                LIMIT 1
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$session_token]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("AdminImpersonation::getBySessionToken error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obter uma impersonação pelo ID
     * 
     * @param int $id ID da impersonation
     * @return array|null Impersonation ou null
     */
    public static function getById(int $id): ?array
    {
        try {
            $db = db();
            
            $sql = "SELECT * FROM admin_impersonations WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("AdminImpersonation::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar se há uma impersonação ativa para um super admin
     * 
     * @param int $super_admin_id ID do super admin
     * @return array|null Impersonation ativa ou null
     */
    public static function getActiveByAdmin(int $super_admin_id): ?array
    {
        try {
            $db = db();
            
            $sql = "
                SELECT * FROM admin_impersonations
                WHERE super_admin_id = ? AND ended_at IS NULL
                ORDER BY started_at DESC
                LIMIT 1
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$super_admin_id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("AdminImpersonation::getActiveByAdmin error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Incrementar contador de ações durante impersonação
     * 
     * @param int $impersonation_id ID da impersonation
     * @return bool True se incrementado com sucesso
     */
    public static function incrementActionCount(int $impersonation_id): bool
    {
        try {
            $db = db();
            
            $sql = "
                UPDATE admin_impersonations
                SET action_count = action_count + 1
                WHERE id = ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$impersonation_id]);
            
            return true;
        } catch (\Exception $e) {
            error_log("AdminImpersonation::incrementActionCount error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter histórico de impersonações de um super admin
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $limit Limite de registros
     * @param int $offset Deslocamento para paginação
     * @return array Lista de impersonations
     */
    public static function getByAdmin(int $super_admin_id, int $limit = 50, int $offset = 0): array
    {
        try {
            $db = db();
            
            $sql = "
                SELECT * FROM admin_impersonations
                WHERE super_admin_id = ?
                ORDER BY started_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$super_admin_id, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("AdminImpersonation::getByAdmin error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obter histórico de impersonações de uma loja (company)
     * 
     * @param int $company_id ID da loja
     * @param int $limit Limite de registros
     * @param int $offset Deslocamento
     * @return array Lista de impersonations
     */
    public static function getByCompany(int $company_id, int $limit = 50, int $offset = 0): array
    {
        try {
            $db = db();
            
            $sql = "
                SELECT ai.*, u.email as super_admin_email, u.name as super_admin_name
                FROM admin_impersonations ai
                JOIN users u ON u.id = ai.super_admin_id
                WHERE ai.company_id = ?
                ORDER BY ai.started_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$company_id, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("AdminImpersonation::getByCompany error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obter estatísticas de impersonações
     * 
     * @return array Estatísticas: total_impersonations, active_now, most_impersonated_company
     */
    public static function getStats(): array
    {
        try {
            $db = db();
            
            $total = $db->query("SELECT COUNT(*) as count FROM admin_impersonations")->fetch(PDO::FETCH_ASSOC)['count'];
            $active = $db->query("SELECT COUNT(*) as count FROM admin_impersonations WHERE ended_at IS NULL")->fetch(PDO::FETCH_ASSOC)['count'];
            $most_impersonated = $db->query("
                SELECT company_id, COUNT(*) as count 
                FROM admin_impersonations 
                GROUP BY company_id 
                ORDER BY count DESC 
                LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_impersonations' => (int)$total,
                'active_now' => (int)$active,
                'most_impersonated_company_id' => $most_impersonated['company_id'] ?? null,
                'most_impersonated_count' => $most_impersonated['count'] ?? 0
            ];
        } catch (\Exception $e) {
            error_log("AdminImpersonation::getStats error: " . $e->getMessage());
            return [
                'total_impersonations' => 0,
                'active_now' => 0,
                'most_impersonated_company_id' => null,
                'most_impersonated_count' => 0
            ];
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
