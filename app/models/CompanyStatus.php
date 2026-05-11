<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/SmartCache.php';

/**
 * CompanyStatus Model
 * 
 * Rastreia mudanças de status operacional de lojas
 * Status: active, suspended, maintenance, blocked
 */
class CompanyStatus
{
    /**
     * Busca status atual da loja
     */
    public static function getCurrentStatus(int $company_id): array
    {
        $key = "company:status:{$company_id}";
        
        return SmartCache::remember($key, function() use ($company_id) {
            $stmt = db()->prepare('
                SELECT * FROM company_operational_status 
                WHERE company_id = ? 
                ORDER BY changed_at DESC 
                LIMIT 1
            ');
            $stmt->execute([$company_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return ['status' => 'active', 'reason' => null];
            }
            
            return $row;
        }, 300); // Cache 5 minutos
    }

    /**
     * Muda status da loja
     */
    public static function changeStatus(
        int $company_id,
        string $new_status,
        string $reason = '',
        ?int $admin_responsible_id = null
    ): bool {
        // Validar status
        $valid_statuses = ['active', 'suspended', 'maintenance', 'blocked'];
        if (!in_array($new_status, $valid_statuses, true)) {
            throw new RuntimeException("Invalid status: {$new_status}");
        }

        try {
            $stmt = db()->prepare('
                INSERT INTO company_operational_status 
                (company_id, status, reason, admin_responsible_id, changed_at)
                VALUES (?, ?, ?, ?, NOW())
            ');
            
            $result = $stmt->execute([
                $company_id,
                $new_status,
                $reason ?: null,
                $admin_responsible_id
            ]);

            // Limpar cache
            SmartCache::forget("company:status:{$company_id}");

            return $result;
        } catch (Exception $e) {
            error_log("Erro ao mudar status de loja {$company_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna histórico de mudanças de status
     */
    public static function getHistory(int $company_id, int $limit = 20): array
    {
        $stmt = db()->prepare('
            SELECT * FROM company_operational_status 
            WHERE company_id = ? 
            ORDER BY changed_at DESC 
            LIMIT ?
        ');
        $stmt->execute([$company_id, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Suspende loja
     */
    public static function suspend(int $company_id, string $reason, int $admin_id): bool
    {
        return self::changeStatus($company_id, 'suspended', $reason, $admin_id);
    }

    /**
     * Ativa loja
     */
    public static function activate(int $company_id, int $admin_id): bool
    {
        return self::changeStatus($company_id, 'active', 'Reativada', $admin_id);
    }

    /**
     * Coloca loja em manutenção
     */
    public static function maintenance(int $company_id, string $reason, int $admin_id): bool
    {
        return self::changeStatus($company_id, 'maintenance', $reason, $admin_id);
    }

    /**
     * Bloqueia loja
     */
    public static function block(int $company_id, string $reason, int $admin_id): bool
    {
        return self::changeStatus($company_id, 'blocked', $reason, $admin_id);
    }
}
