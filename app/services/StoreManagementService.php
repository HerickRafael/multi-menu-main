<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/CompanyStatus.php';
require_once __DIR__ . '/../models/CompanyResource.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../services/MetricsService.php';

/**
 * StoreManagementService
 * 
 * Lógica de negócio para gestão de lojas
 * Suspensão, ativação, manutenção, bloqueio
 * Validações e regras de negócio
 */
class StoreManagementService
{
    /**
     * Suspende uma loja
     */
    public static function suspendStore(
        int $company_id,
        string $reason,
        int $admin_id
    ): array {
        // Validar loja existe
        $company = Company::find($company_id);
        if (!$company) {
            return ['success' => false, 'message' => 'Loja não encontrada'];
        }

        // Mudança de status
        $result = CompanyStatus::suspend($company_id, $reason, $admin_id);
        if (!$result) {
            return ['success' => false, 'message' => 'Erro ao suspender loja'];
        }

        // Log de auditoria (será implementado em Fase 2)
        error_log("Loja {$company_id} suspensa por admin {$admin_id}. Motivo: {$reason}");

        return [
            'success' => true,
            'message' => 'Loja suspensa com sucesso',
            'company_id' => $company_id,
            'status' => 'suspended'
        ];
    }

    /**
     * Ativa uma loja
     */
    public static function activateStore(
        int $company_id,
        int $admin_id
    ): array {
        $company = Company::find($company_id);
        if (!$company) {
            return ['success' => false, 'message' => 'Loja não encontrada'];
        }

        $result = CompanyStatus::activate($company_id, $admin_id);
        if (!$result) {
            return ['success' => false, 'message' => 'Erro ao ativar loja'];
        }

        error_log("Loja {$company_id} ativada por admin {$admin_id}");

        return [
            'success' => true,
            'message' => 'Loja ativada com sucesso',
            'company_id' => $company_id,
            'status' => 'active'
        ];
    }

    /**
     * Coloca loja em manutenção
     */
    public static function maintenanceStore(
        int $company_id,
        string $reason,
        int $admin_id
    ): array {
        $company = Company::find($company_id);
        if (!$company) {
            return ['success' => false, 'message' => 'Loja não encontrada'];
        }

        $result = CompanyStatus::maintenance($company_id, $reason, $admin_id);
        if (!$result) {
            return ['success' => false, 'message' => 'Erro ao colocar loja em manutenção'];
        }

        error_log("Loja {$company_id} colocada em manutenção por admin {$admin_id}. Motivo: {$reason}");

        return [
            'success' => true,
            'message' => 'Loja colocada em manutenção',
            'company_id' => $company_id,
            'status' => 'maintenance'
        ];
    }

    /**
     * Bloqueia uma loja
     */
    public static function blockStore(
        int $company_id,
        string $reason,
        int $admin_id
    ): array {
        $company = Company::find($company_id);
        if (!$company) {
            return ['success' => false, 'message' => 'Loja não encontrada'];
        }

        $result = CompanyStatus::block($company_id, $reason, $admin_id);
        if (!$result) {
            return ['success' => false, 'message' => 'Erro ao bloquear loja'];
        }

        error_log("Loja {$company_id} BLOQUEADA por admin {$admin_id}. Motivo: {$reason}");

        return [
            'success' => true,
            'message' => 'Loja bloqueada com sucesso',
            'company_id' => $company_id,
            'status' => 'blocked'
        ];
    }

    /**
     * Retorna detalhe completo de uma loja para operações
     */
    public static function getStoreDetails(int $company_id): ?array
    {
        $company = Company::find($company_id);
        if (!$company) {
            return null;
        }

        $status = CompanyStatus::getCurrentStatus($company_id);
        $metrics = MetricsService::getCompanyMetrics($company_id);
        $resources = CompanyResource::getAllForCompany($company_id);
        $history = CompanyStatus::getHistory($company_id, 10);

        return [
            'id' => $company['id'],
            'name' => $company['name'],
            'slug' => $company['slug'],
            'active' => (bool)$company['active'],
            'logo' => $company['logo'],
            'banner' => $company['banner'],
            'whatsapp' => $company['whatsapp'],
            'address' => $company['address'],
            'created_at' => $company['created_at'],
            'status' => $status['status'],
            'status_reason' => $status['reason'],
            'status_changed_at' => $status['changed_at'],
            'metrics' => $metrics['metrics'],
            'resources' => $resources,
            'history' => $history
        ];
    }

    /**
     * Atualiza informações básicas de uma loja
     */
    public static function updateStore(
        int $company_id,
        array $data,
        int $admin_id
    ): array {
        $company = Company::find($company_id);
        if (!$company) {
            return ['success' => false, 'message' => 'Loja não encontrada'];
        }

        $allowed_fields = ['name', 'whatsapp', 'address', 'min_order'];
        $updates = [];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
        }

        try {
            $set_clause = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($updates)));
            $values = array_values($updates);
            $values[] = $company_id;

            $stmt = db()->prepare("UPDATE companies SET {$set_clause} WHERE id = ?");
            $result = $stmt->execute($values);

            if (!$result) {
                return ['success' => false, 'message' => 'Erro ao atualizar loja'];
            }

            error_log("Loja {$company_id} atualizada por admin {$admin_id}. Campos: " . implode(', ', array_keys($updates)));

            return [
                'success' => true,
                'message' => 'Loja atualizada com sucesso',
                'company_id' => $company_id
            ];
        } catch (Exception $e) {
            error_log("Erro ao atualizar loja {$company_id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar loja'];
        }
    }

    /**
     * Toggle de recurso de uma loja
     */
    public static function toggleResource(
        int $company_id,
        string $resource_name,
        int $admin_id
    ): array {
        $is_enabled = CompanyResource::isEnabled($company_id, $resource_name);
        
        if ($is_enabled) {
            $result = CompanyResource::disable($company_id, $resource_name, $admin_id);
        } else {
            $result = CompanyResource::enable($company_id, $resource_name, $admin_id);
        }

        if (!$result) {
            return ['success' => false, 'message' => 'Erro ao alterar recurso'];
        }

        return [
            'success' => true,
            'message' => 'Recurso alterado com sucesso',
            'resource' => $resource_name,
            'now_enabled' => !$is_enabled
        ];
    }
}
