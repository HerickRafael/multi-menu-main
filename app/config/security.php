<?php

declare(strict_types=1);

/**
 * Configuração central de segurança (bridge com SessionManager / CSRF).
 * Valores podem ser sobrescritos por variáveis de ambiente.
 */
return [
    'session_name' => $_ENV['SESSION_NAME'] ?? getenv('SESSION_NAME') ?: 'mm_session',
    'csrf_ttl' => 3600,
    /** Tempo máximo de inatividade do cliente no checkout (segundos), alinhado a helpers de sessão */
    'session_lifetime_seconds' => (int)($_ENV['CUSTOMER_SESSION_LIFETIME'] ?? getenv('CUSTOMER_SESSION_LIFETIME') ?: 604800),
];
