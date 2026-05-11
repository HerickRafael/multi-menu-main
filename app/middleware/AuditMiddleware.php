<?php

declare(strict_types=1);

/**
 * AuditMiddleware
 * 
 * Middleware que registra automaticamente ações críticas do super admin
 * durante a requisição, baseado em padrões de rota.
 * 
 * Executa APÓS a ação completar (ou falhar), capturando o resultado
 * e registrando em auditoria.
 * 
 * Padrão: Aplicar após SuperAdminMiddleware::enforce()
 */
class AuditMiddleware
{
    /**
     * Interceptar e registrar ação de super admin
     * 
     * @param string $route Rota da requisição (ex: /superadmin/stores/1/update)
     * @param string $method Método HTTP (GET, POST, etc)
     * @param array $data POST data ou parametros
     * @return void
     */
    public static function record(string $route, string $method, array $data = []): void
    {
        require_once __DIR__ . '/../models/AuditLog.php';
        require_once __DIR__ . '/../services/AuditService.php';

        $super_admin_id = (int)($_SESSION['super_admin_id'] ?? 0);
        if ($super_admin_id < 1) {
            return; // Não é super admin, não auditoria
        }

        // Determinar ação baseado em padrão de rota
        if (preg_match('#/superadmin/stores/(\d+)/update$#', $route)) {
            // POST /superadmin/stores/{id}/update
            preg_match('#/(\d+)/update#', $route, $m);
            $company_id = (int)($m[1] ?? 0);

            if ($company_id > 0) {
                $description = "Updated store {$company_id}";
                AuditService::log(
                    $super_admin_id,
                    'update',
                    'stores',
                    'company',
                    $company_id,
                    $company_id,
                    $description,
                    old_data: $data['old_data'] ?? null,
                    new_data: $data['new_data'] ?? null
                );
            }
        } elseif (preg_match('#/superadmin/stores/(\d+)/suspend$#', $route)) {
            preg_match('#/(\d+)/suspend#', $route, $m);
            $company_id = (int)($m[1] ?? 0);

            if ($company_id > 0) {
                $reason = $data['reason'] ?? '';
                $description = "Suspended store {$company_id}" . ($reason ? " - Reason: {$reason}" : '');
                AuditService::log(
                    $super_admin_id,
                    'suspend',
                    'stores',
                    'company',
                    $company_id,
                    $company_id,
                    $description
                );
            }
        } elseif (preg_match('#/superadmin/stores/(\d+)/activate$#', $route)) {
            preg_match('#/(\d+)/activate#', $route, $m);
            $company_id = (int)($m[1] ?? 0);

            if ($company_id > 0) {
                AuditService::log(
                    $super_admin_id,
                    'activate',
                    'stores',
                    'company',
                    $company_id,
                    $company_id,
                    "Activated store {$company_id}"
                );
            }
        } elseif (preg_match('#/superadmin/stores/(\d+)/maintenance$#', $route)) {
            preg_match('#/(\d+)/maintenance#', $route, $m);
            $company_id = (int)($m[1] ?? 0);

            if ($company_id > 0) {
                $reason = $data['reason'] ?? '';
                $description = "Put store {$company_id} in maintenance" . ($reason ? " - Reason: {$reason}" : '');
                AuditService::log(
                    $super_admin_id,
                    'maintenance',
                    'stores',
                    'company',
                    $company_id,
                    $company_id,
                    $description
                );
            }
        } elseif (preg_match('#/superadmin/impersonate/(\d+)/start$#', $route)) {
            preg_match('#/(\d+)/start#', $route, $m);
            $company_id = (int)($m[1] ?? 0);

            if ($company_id > 0) {
                $reason = $data['reason'] ?? '';
                $role = $data['role'] ?? 'owner';
                $description = "Started impersonation as {$role} for store {$company_id}" . ($reason ? " - Reason: {$reason}" : '');
                AuditService::log(
                    $super_admin_id,
                    'impersonate_start',
                    'impersonations',
                    'impersonation',
                    null,
                    $company_id,
                    $description
                );
            }
        } elseif (preg_match('#/superadmin/impersonate/end$#', $route)) {
            $impersonation_id = (int)($_SESSION['impersonation_id'] ?? 0);
            $company_id = (int)($_SESSION['impersonated_company_id'] ?? 0);
            $action_count = $data['action_count'] ?? 0;

            if ($company_id > 0) {
                $description = "Ended impersonation for store {$company_id} - Actions: {$action_count}";
                AuditService::log(
                    $super_admin_id,
                    'impersonate_end',
                    'impersonations',
                    'impersonation',
                    $impersonation_id ?: null,
                    $company_id,
                    $description
                );
            }
        }
    }

    /**
     * Middleware para registrar ação automaticamente após a requisição
     * (Aplicável como wrapper em rotas críticas)
     */
    public static function handle(): void
    {
        // Este método seria chamado após o controller completar a ação
        // Para uso futuro com wrapper de rotas
    }
}
