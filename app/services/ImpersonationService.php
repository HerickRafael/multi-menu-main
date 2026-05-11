<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminImpersonation;
use App\Models\Company;
use App\Models\User;

/**
 * ImpersonationService
 * 
 * Orquestra a funcionalidade de impersonação — quando um super admin
 * quer "entrar como" um dono de loja sem saber a senha.
 * 
 * Garantias:
 * - Sessão isolada: Super admin está de fato "em outro contexto"
 * - Sem bypass: Usa session token, não manipula IDs diretamente
 * - Auditoria: Toda impersonation é registrada e rastreada
 * - Saída segura: Limpa session corretamente ao encerrar
 */
class ImpersonationService
{
    /**
     * Iniciar impersonação
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $company_id ID da loja a impersonar
     * @param string $reason Razão (debugging, support, etc)
     * @param string $role Qual role: 'owner' ou 'staff'
     * @return array|false ['success' => bool, 'impersonation_id' => int, 'session_token' => string, 'message' => string]
     */
    public static function start(int $super_admin_id, int $company_id, string $reason = '', string $role = 'owner'): array|false
    {
        try {
            // Validar que super admin existe e é root
            $super_admin = User::find($super_admin_id);
            if (!$super_admin || $super_admin['role'] !== 'root') {
                return [
                    'success' => false,
                    'message' => 'Super admin inválido ou sem permissão'
                ];
            }

            // Validar que loja existe
            $company = Company::find($company_id);
            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Loja não encontrada'
                ];
            }

            // Validar que super admin não está já impersonando
            $already_impersonating = AdminImpersonation::getActiveByAdmin($super_admin_id);
            if ($already_impersonating) {
                return [
                    'success' => false,
                    'message' => 'Super admin já está em uma impersonação ativa. Finalize a anterior primeiro.'
                ];
            }

            // Criar registro de impersonação
            $impersonation = AdminImpersonation::start($super_admin_id, $company_id, $reason, $role);
            if (!$impersonation) {
                return [
                    'success' => false,
                    'message' => 'Erro ao iniciar impersonação'
                ];
            }

            // Registrar em auditoria
            AuditService::logImpersonationStart($super_admin_id, $company_id, $reason, $role);

            return [
                'success' => true,
                'impersonation_id' => $impersonation['id'],
                'session_token' => $impersonation['session_token'],
                'company_id' => $company_id,
                'company_name' => $company['name'],
                'role' => $role,
                'message' => "Impersonação iniciada para {$company['name']} como {$role}"
            ];
        } catch (\Exception $e) {
            error_log("ImpersonationService::start error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Encerrar impersonação
     * 
     * @param string $session_token Token da sessão de impersonação
     * @param string $final_note Observações ao encerrar
     * @return array ['success' => bool, 'message' => string]
     */
    public static function end(string $session_token, string $final_note = ''): array
    {
        try {
            // Encontrar impersonação ativa
            $impersonation = AdminImpersonation::getBySessionToken($session_token);
            if (!$impersonation) {
                return [
                    'success' => false,
                    'message' => 'Impersonação não encontrada ou já encerrada'
                ];
            }

            // Encerrar
            $success = AdminImpersonation::end($impersonation['id'], $final_note);
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Erro ao encerrar impersonação'
                ];
            }

            // Registrar em auditoria
            AuditService::logImpersonationEnd(
                $impersonation['super_admin_id'],
                $impersonation['company_id'],
                $impersonation['action_count']
            );

            return [
                'success' => true,
                'message' => "Impersonação encerrada. {$impersonation['action_count']} ações realizadas."
            ];
        } catch (\Exception $e) {
            error_log("ImpersonationService::end error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao encerrar impersonação'
            ];
        }
    }

    /**
     * Validar se um session token é válido e ativo
     * 
     * @param string $session_token Token da sessão
     * @return array|null Impersonation se válida, null se inválida
     */
    public static function validate(string $session_token): ?array
    {
        return AdminImpersonation::getBySessionToken($session_token);
    }

    /**
     * Obter informações da impersonação ativa de um super admin
     * 
     * @param int $super_admin_id ID do super admin
     * @return array|null Impersonation ativa ou null
     */
    public static function getActive(int $super_admin_id): ?array
    {
        $impersonation = AdminImpersonation::getActiveByAdmin($super_admin_id);
        
        if (!$impersonation) {
            return null;
        }

        // Enriquecer com dados da loja
        $company = Company::find($impersonation['company_id']);
        
        return [
            'id' => $impersonation['id'],
            'session_token' => $impersonation['session_token'],
            'super_admin_id' => $impersonation['super_admin_id'],
            'company_id' => $impersonation['company_id'],
            'company_name' => $company['name'] ?? 'Unknown',
            'role' => $impersonation['impersonated_as_role'],
            'started_at' => $impersonation['started_at'],
            'reason' => $impersonation['reason'],
            'action_count' => $impersonation['action_count']
        ];
    }

    /**
     * Registrar uma ação durante impersonação
     * 
     * @param int $impersonation_id ID da impersonation
     * @return bool True se incrementado
     */
    public static function recordAction(int $impersonation_id): bool
    {
        return AdminImpersonation::incrementActionCount($impersonation_id);
    }

    /**
     * Obter histórico de impersonações de um super admin
     * 
     * @param int $super_admin_id ID do super admin
     * @param int $page Página
     * @param int $per_page Registros por página
     * @return array Histórico com paginação
     */
    public static function getHistory(int $super_admin_id, int $page = 1, int $per_page = 20): array
    {
        $offset = ($page - 1) * $per_page;
        
        $impersonations = AdminImpersonation::getByAdmin($super_admin_id, $per_page, $offset);
        
        // Enriquecer com nomes de lojas
        foreach ($impersonations as &$imp) {
            $company = Company::find($imp['company_id']);
            $imp['company_name'] = $company['name'] ?? 'Unknown';
            $imp['is_active'] = $imp['ended_at'] === null;
        }
        
        return [
            'page' => $page,
            'per_page' => $per_page,
            'impersonations' => $impersonations
        ];
    }

    /**
     * Obter estatísticas de impersonação do sistema
     * 
     * @return array Estatísticas
     */
    public static function getStats(): array
    {
        return AdminImpersonation::getStats();
    }

    /**
     * Obter histórico de impersonações de uma loja
     * 
     * @param int $company_id ID da loja
     * @param int $limit Limite de registros
     * @return array Histórico
     */
    public static function getCompanyHistory(int $company_id, int $limit = 50): array
    {
        return AdminImpersonation::getByCompany($company_id, $limit);
    }

    /**
     * Verificar se um super admin está atualmente impersonando
     * 
     * @param int $super_admin_id ID do super admin
     * @return bool True se está impersonando
     */
    public static function isImpersonating(int $super_admin_id): bool
    {
        return AdminImpersonation::getActiveByAdmin($super_admin_id) !== null;
    }
}
