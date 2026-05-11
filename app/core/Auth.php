<?php

declare(strict_types=1);
class Auth
{
    public static function start(): void
    {
        $cfg = config();
        date_default_timezone_set($cfg['timezone'] ?? 'America/Sao_Paulo');
        
        // Configurar o nome da sessão ANTES de iniciar (se ainda não foi iniciada)
        if (session_status() === PHP_SESSION_NONE) {
            session_name($cfg['session_name'] ?? 'mm_session');
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = [
          'id'         => $user['id'],
          'role'       => $user['role'],
          'company_id' => $user['company_id'] ?? null,
          'name'       => $user['name'] ?? '',
          'email'      => $user['email'] ?? '',
        ];
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /** Admin logado? */
    public static function checkAdmin(): bool
    {
        $u = self::user();

        return $u && in_array($u['role'], ['root','owner','staff'], true);
    }

    /** Exige admin logado (redireciona pro login) */
    public static function requireAdmin(): void
    {
        if (!self::checkAdmin()) {
            header('Location: ' . base_url('admin/login'));
            exit;
        }
    }

    /** company_id padrão do usuário (pode ser null para root) */
    public static function companyId(): ?int
    {
        $u = self::user();

        return $u['company_id'] ?? null;
    }

    /* ========= Contexto de Empresa Ativa (para root trocar de empresa) ========= */

    /** Define o contexto de empresa ativa (também útil para owner/staff) */
    public static function setActiveCompany(int $companyId, ?string $slug = null): void
    {
        $_SESSION['active_company_id'] = $companyId;

        if ($slug !== null) {
            $_SESSION['active_company_slug'] = $slug;
        }
    }

    /** company_id efetivo usado pelo painel admin (prioriza contexto ativo) */
    public static function activeCompanyId(): ?int
    {
        if (isset($_SESSION['active_company_id'])) {
            return (int)$_SESSION['active_company_id'];
        }

        return self::companyId(); // fallback: company do usuário
    }

    /** slug efetivo do contexto ativo (se disponível) */
    public static function activeCompanySlug(): ?string
    {
        if (!empty($_SESSION['active_company_slug'])) {
            return (string)$_SESSION['active_company_slug'];
        }

        return null; // opcional: você pode buscar pelo Company::findById(self::activeCompanyId())
    }

    /** Limpa o contexto ativo (ex.: no logout ou troca de empresa) */
    public static function clearActiveCompany(): void
    {
        unset($_SESSION['active_company_id'], $_SESSION['active_company_slug']);
    }
}
