<?php

declare(strict_types=1);

namespace App\Services;


/**
 * AuditService
 * 
 * Camada de serviço para auditoria — abstrai a lógica de registro de ações
 * do controller. Responsável por:
 * - Registrar ações críticas do super admin
 * - Formatar dados antigos/novos em JSON
 * - Enriquecer logs com contexto
 * - Gerar descrições legíveis
 */
class AuditService
{
    /**
     * Registrar ação de suspensão de loja
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $company_id ID da loja suspensa
     * @param string $reason Motivo da suspensão
     * @param array $old_data Dados antigos
     * @param array $new_data Novos dados
     * @return bool True se registrado com sucesso
     */
    public static function logStoreSuspend(int $super_admin_id, int $company_id, string $reason, array $old_data, array $new_data): bool
    {
        return \AuditLog::create(
            super_admin_id: $super_admin_id,
            action: 'suspend',
            module: 'stores',
            entity_type: 'company',
            entity_id: $company_id,
            company_id: $company_id,
            old_data: $old_data,
            new_data: $new_data,
            description: "Suspended store (ID: {$company_id}) - Reason: {$reason}"
        );
    }

    /**
     * Registrar ação de ativação de loja
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $company_id ID da loja
     * @param array $old_data Dados antigos
     * @param array $new_data Novos dados
     * @return bool True se registrado com sucesso
     */
    public static function logStoreActivate(int $super_admin_id, int $company_id, array $old_data, array $new_data): bool
    {
        return \AuditLog::create(
            super_admin_id: $super_admin_id,
            action: 'activate',
            module: 'stores',
            entity_type: 'company',
            entity_id: $company_id,
            company_id: $company_id,
            old_data: $old_data,
            new_data: $new_data,
            description: "Activated store (ID: {$company_id})"
        );
    }

    /**
     * Registrar ação de manutenção de loja
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $company_id ID da loja
     * @param string $reason Motivo da manutenção
     * @param array $old_data Dados antigos
     * @param array $new_data Novos dados
     * @return bool True se registrado com sucesso
     */
    public static function logStoreMaintenance(int $super_admin_id, int $company_id, string $reason, array $old_data, array $new_data): bool
    {
        return \AuditLog::create(
            super_admin_id: $super_admin_id,
            action: 'maintenance',
            module: 'stores',
            entity_type: 'company',
            entity_id: $company_id,
            company_id: $company_id,
            old_data: $old_data,
            new_data: $new_data,
            description: "Put store (ID: {$company_id}) in maintenance - Reason: {$reason}"
        );
    }

    /**
     * Registrar ação de bloqueio de loja
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $company_id ID da loja
     * @param string $reason Motivo do bloqueio
     * @param array $old_data Dados antigos
     * @param array $new_data Novos dados
     * @return bool True se registrado com sucesso
     */
    public static function logStoreBlock(int $super_admin_id, int $company_id, string $reason, array $old_data, array $new_data): bool
    {
        return \AuditLog::create(
            super_admin_id: $super_admin_id,
            action: 'block',
            module: 'stores',
            entity_type: 'company',
            entity_id: $company_id,
            company_id: $company_id,
            old_data: $old_data,
            new_data: $new_data,
            description: "Blocked store (ID: {$company_id}) - Reason: {$reason}"
        );
    }

    /**
     * Registrar ação de edição de loja
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $company_id ID da loja
     * @param array $old_data Dados antigos
     * @param array $new_data Novos dados
     * @return bool True se registrado com sucesso
     */
    public static function logStoreUpdate(int $super_admin_id, int $company_id, array $old_data, array $new_data): bool
    {
        return \AuditLog::create(
            super_admin_id: $super_admin_id,
            action: 'update',
            module: 'stores',
            entity_type: 'company',
            entity_id: $company_id,
            company_id: $company_id,
            old_data: $old_data,
            new_data: $new_data,
            description: "Updated store (ID: {$company_id})"
        );
    }

    /**
     * Registrar ação de início de impersonação
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $company_id ID da loja impersonada
     * @param string $reason Motivo da impersonação
     * @param string $role Qual role: 'owner' ou 'staff'
     * @return bool True se registrado com sucesso
     */
    public static function logImpersonationStart(int $super_admin_id, int $company_id, string $reason, string $role = 'owner'): bool
    {
        return \AuditLog::create(
            super_admin_id: $super_admin_id,
            action: 'impersonate_start',
            module: 'impersonations',
            entity_type: 'impersonation',
            entity_id: null,
            company_id: $company_id,
            description: "Started impersonation as {$role} for store (ID: {$company_id}) - Reason: {$reason}"
        );
    }

    /**
     * Registrar ação de fim de impersonação
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $company_id ID da loja impersonada
     * @param int $actions_performed Número de ações realizadas
     * @return bool True se registrado com sucesso
     */
    public static function logImpersonationEnd(int $super_admin_id, int $company_id, int $actions_performed): bool
    {
        return \AuditLog::create(
            super_admin_id: $super_admin_id,
            action: 'impersonate_end',
            module: 'impersonations',
            entity_type: 'impersonation',
            entity_id: null,
            company_id: $company_id,
            description: "Ended impersonation for store (ID: {$company_id}) - Actions performed: {$actions_performed}"
        );
    }

    /**
     * Registrar ação genérica
     * 
     * @param int $super_admin_id ID do super admin
     * @param string $action Ação
     * @param string $module Módulo
     * @param string $entity_type Tipo de entidade
     * @param int|null $entity_id ID da entidade
     * @param int|null $company_id ID da loja
     * @param string $description Descrição
     * @param array|null $old_data Dados antigos
     * @param array|null $new_data Novos dados
     * @return bool True se registrado com sucesso
     */
    public static function log(
        int $super_admin_id,
        string $action,
        string $module,
        string $entity_type,
        ?int $entity_id,
        ?int $company_id,
        string $description,
        ?array $old_data = null,
        ?array $new_data = null
    ): bool {
        return \AuditLog::create(
            super_admin_id: $super_admin_id,
            action: $action,
            module: $module,
            entity_type: $entity_type,
            entity_id: $entity_id,
            company_id: $company_id,
            old_data: $old_data,
            new_data: $new_data,
            description: $description
        );
    }

    /**
     * Obter relatório de ações de um super admin
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $days Últimos N dias
     * @return array Relatório com resumo e detalhes
     */
    public static function getAdminReport(int $super_admin_id, int $days = 30): array
    {
        $date_from = date('Y-m-d H:i:s', time() - ($days * 86400));
        
        $filters = [
            'super_admin_id' => $super_admin_id,
            'date_from' => $date_from
        ];
        
        $logs = \AuditLog::search($filters, limit: 1000);
        
        // Resumir por ação
        $actions = array_count_values(array_column($logs, 'action'));
        
        return [
            'super_admin_id' => $super_admin_id,
            'days' => $days,
            'total_actions' => count($logs),
            'action_summary' => $actions,
            'logs' => $logs
        ];
    }

    /**
     * Obter relatório de ações de uma loja
     * 
     * @param int $company_id ID da loja
     * @param int $days Últimos N dias
     * @return array Relatório
     */
    public static function getCompanyReport(int $company_id, int $days = 30): array
    {
        $date_from = date('Y-m-d H:i:s', time() - ($days * 86400));
        
        $filters = [
            'company_id' => $company_id,
            'date_from' => $date_from
        ];
        
        $logs = \AuditLog::search($filters, limit: 1000);
        
        // Resumir por ação
        $actions = array_count_values(array_column($logs, 'action'));
        
        return [
            'company_id' => $company_id,
            'days' => $days,
            'total_actions' => count($logs),
            'action_summary' => $actions,
            'logs' => $logs
        ];
    }
}
