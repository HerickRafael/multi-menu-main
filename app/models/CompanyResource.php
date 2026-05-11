<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/SmartCache.php';

/**
 * CompanyResource Model
 * 
 * Gerencia ativação/desativação de recursos por loja
 * Recursos: whatsapp, delivery, checkout_v2, etc
 */
class CompanyResource
{
    private const CACHE_TTL = 600; // 10 minutos

    /**
     * Verifica se um recurso está ativado para uma loja
     */
    public static function isEnabled(int $company_id, string $resource_name): bool
    {
        $key = "company:resource:{$company_id}:{$resource_name}";
        
        return SmartCache::remember($key, function() use ($company_id, $resource_name) {
            $stmt = db()->prepare('
                SELECT enabled FROM company_resources_flags 
                WHERE company_id = ? AND resource_name = ? 
                LIMIT 1
            ');
            $stmt->execute([$company_id, $resource_name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (bool)$result['enabled'] : true; // Default: habilitado
        }, self::CACHE_TTL);
    }

    /**
     * Ativa um recurso para uma loja
     */
    public static function enable(int $company_id, string $resource_name, int $admin_id): bool
    {
        try {
            $stmt = db()->prepare('
                INSERT INTO company_resources_flags 
                (company_id, resource_name, enabled, enabled_by_admin_id, enabled_at)
                VALUES (?, ?, 1, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    enabled = 1,
                    enabled_by_admin_id = VALUES(enabled_by_admin_id),
                    enabled_at = NOW()
            ');
            
            $result = $stmt->execute([$company_id, $resource_name, $admin_id]);
            
            // Limpar cache
            SmartCache::forget("company:resource:{$company_id}:{$resource_name}");
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao ativar recurso {$resource_name} para loja {$company_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desativa um recurso para uma loja
     */
    public static function disable(int $company_id, string $resource_name, int $admin_id): bool
    {
        try {
            $stmt = db()->prepare('
                UPDATE company_resources_flags 
                SET enabled = 0, enabled_by_admin_id = ? 
                WHERE company_id = ? AND resource_name = ?
            ');
            
            $result = $stmt->execute([$admin_id, $company_id, $resource_name]);
            
            // Limpar cache
            SmartCache::forget("company:resource:{$company_id}:{$resource_name}");
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao desativar recurso {$resource_name} para loja {$company_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna todos os recursos de uma loja
     */
    public static function getAllForCompany(int $company_id): array
    {
        $stmt = db()->prepare('
            SELECT resource_name, enabled, enabled_at, enabled_by_admin_id, metadata 
            FROM company_resources_flags 
            WHERE company_id = ? 
            ORDER BY resource_name ASC
        ');
        $stmt->execute([$company_id]);
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON metadata
        foreach ($resources as &$resource) {
            if ($resource['metadata']) {
                $resource['metadata'] = json_decode($resource['metadata'], true);
            }
        }

        return $resources;
    }

    /**
     * Retorna status de múltiplos recursos de uma loja
     */
    public static function getMultiple(int $company_id, array $resource_names): array
    {
        $placeholders = implode(',', array_fill(0, count($resource_names), '?'));
        $params = [$company_id, ...$resource_names];

        $stmt = db()->prepare("
            SELECT resource_name, enabled FROM company_resources_flags 
            WHERE company_id = ? AND resource_name IN ({$placeholders})
        ");
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Construir array com status (default: true se não existir)
        $status = [];
        foreach ($resource_names as $name) {
            $status[$name] = true; // Default habilitado
        }

        foreach ($results as $row) {
            $status[$row['resource_name']] = (bool)$row['enabled'];
        }

        return $status;
    }
}
