<?php

declare(strict_types=1);

class TenantScopeMiddleware
{
    public static function enforceRequestScope(string $param = 'company_id'): int
    {
        $companyId = (int)($_GET[$param] ?? $_POST[$param] ?? 0);
        if ($companyId < 1) {
            http_response_code(422);
            exit('Escopo de tenant obrigatorio. Informe company_id.');
        }
        return $companyId;
    }

    public static function enforceCompanyExists(int $companyId): void
    {
        $st = db()->prepare('SELECT id FROM companies WHERE id = ? LIMIT 1');
        $st->execute([$companyId]);
        if (!$st->fetchColumn()) {
            http_response_code(404);
            exit('Tenant nao encontrado.');
        }
    }

    public static function assertSqlScoped(string $sql): void
    {
        $normalized = strtolower(preg_replace('/\s+/', ' ', $sql));
        if (str_contains($normalized, ' from ') && str_contains($normalized, ' where ')) {
            if (!str_contains($normalized, 'company_id')) {
                throw new RuntimeException('Query sem company_id detectada em operacao sensivel.');
            }
        }
    }
}
