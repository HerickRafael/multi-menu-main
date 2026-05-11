-- ===================================================================
-- SESSION MANAGEMENT SYSTEM - DATABASE SCHEMA
-- ===================================================================
-- Sistema avançado de gerenciamento de sessões
-- ===================================================================

-- Extensão da tabela sessions (já existente no authentication_schema)
-- Se já existir, apenas adicionar campos faltantes

CREATE TABLE IF NOT EXISTS sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions(last_activity);
CREATE INDEX IF NOT EXISTS idx_sessions_ip ON sessions(ip_address);

-- ===================================================================
-- Tabela de logs de sessão (atividades)
-- ===================================================================
CREATE TABLE IF NOT EXISTS session_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    user_id INTEGER,
    action TEXT NOT NULL,
    data TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_session_logs_session ON session_logs(session_id);
CREATE INDEX IF NOT EXISTS idx_session_logs_user ON session_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_session_logs_action ON session_logs(action);
CREATE INDEX IF NOT EXISTS idx_session_logs_created ON session_logs(created_at);

-- ===================================================================
-- VIEWS ÚTEIS
-- ===================================================================

-- View de sessões ativas
CREATE VIEW IF NOT EXISTS v_active_sessions AS
SELECT 
    s.id as session_id,
    s.user_id,
    u.email,
    u.name,
    s.ip_address,
    s.user_agent,
    datetime(s.last_activity, 'unixepoch') as last_activity,
    datetime(s.created_at) as created_at,
    (strftime('%s', 'now') - s.last_activity) as idle_seconds
FROM sessions s
LEFT JOIN users u ON s.user_id = u.id
WHERE s.last_activity > (strftime('%s', 'now') - 7200) -- 2 horas
ORDER BY s.last_activity DESC;

-- View de usuários online
CREATE VIEW IF NOT EXISTS v_users_online AS
SELECT DISTINCT
    u.id,
    u.email,
    u.name,
    COUNT(DISTINCT s.id) as active_sessions,
    MAX(datetime(s.last_activity, 'unixepoch')) as last_seen
FROM users u
JOIN sessions s ON u.id = s.user_id
WHERE s.last_activity > (strftime('%s', 'now') - 7200)
GROUP BY u.id, u.email, u.name
ORDER BY last_seen DESC;

-- View de tentativas de ataque
CREATE VIEW IF NOT EXISTS v_session_attacks AS
SELECT 
    sl.*,
    u.email,
    u.name
FROM session_logs sl
LEFT JOIN users u ON sl.user_id = u.id
WHERE sl.action IN ('fixation_attempt', 'hijacking_attempt')
ORDER BY sl.created_at DESC;

-- View de atividades de sessão (últimas 24h)
CREATE VIEW IF NOT EXISTS v_session_activity AS
SELECT 
    sl.action,
    COUNT(*) as count,
    COUNT(DISTINCT sl.session_id) as unique_sessions,
    COUNT(DISTINCT sl.user_id) as unique_users,
    MIN(sl.created_at) as first_occurrence,
    MAX(sl.created_at) as last_occurrence
FROM session_logs sl
WHERE sl.created_at > datetime('now', '-1 day')
GROUP BY sl.action
ORDER BY count DESC;

-- View de sessões por usuário
CREATE VIEW IF NOT EXISTS v_user_session_stats AS
SELECT 
    u.id as user_id,
    u.email,
    u.name,
    COUNT(s.id) as total_sessions,
    MAX(datetime(s.last_activity, 'unixepoch')) as last_activity,
    COUNT(DISTINCT s.ip_address) as unique_ips
FROM users u
LEFT JOIN sessions s ON u.id = s.user_id
WHERE s.last_activity > (strftime('%s', 'now') - 86400) -- 24 horas
GROUP BY u.id, u.email, u.name
HAVING total_sessions > 0
ORDER BY total_sessions DESC;

-- ===================================================================
-- TRIGGERS
-- ===================================================================

-- Trigger para cleanup automático de sessões antigas
CREATE TRIGGER IF NOT EXISTS cleanup_old_sessions
AFTER INSERT ON sessions
BEGIN
    DELETE FROM sessions 
    WHERE last_activity < (strftime('%s', 'now') - 7200); -- 2 horas
END;

-- Trigger para cleanup automático de logs antigos
CREATE TRIGGER IF NOT EXISTS cleanup_old_session_logs
AFTER INSERT ON session_logs
BEGIN
    DELETE FROM session_logs 
    WHERE created_at < datetime('now', '-30 days'); -- 30 dias
END;

-- ===================================================================
-- DADOS DE TESTE (opcional)
-- ===================================================================

-- Exemplo de inserção de sessão (não executar em produção)
-- INSERT INTO sessions (id, user_id, payload, last_activity, ip_address, user_agent, created_at)
-- VALUES (
--     'test_session_' || hex(randomblob(16)),
--     1,
--     'YToyOntzOjU6InRlc3QiO3M6NDoidmFsdWUiO30=', -- base64 encoded
--     strftime('%s', 'now'),
--     '127.0.0.1',
--     'Mozilla/5.0',
--     datetime('now')
-- );

-- ===================================================================
-- COMENTÁRIOS E DOCUMENTAÇÃO
-- ===================================================================

-- sessions: Armazena todas as sessões ativas e seus dados
--   - id: ID único da sessão (gerado pelo PHP)
--   - user_id: ID do usuário logado (null se não autenticado)
--   - payload: Dados serializados da sessão
--   - last_activity: Timestamp Unix da última atividade
--   - ip_address: IP do cliente
--   - user_agent: User agent do navegador
--   - created_at: Data de criação da sessão

-- session_logs: Auditoria de todas as ações de sessão
--   - session_id: ID da sessão
--   - user_id: ID do usuário
--   - action: Ação realizada (start, regenerate, destroy, fixation_attempt, hijacking_attempt)
--   - data: Dados adicionais em JSON
--   - ip_address: IP do cliente
--   - user_agent: User agent
--   - created_at: Data da ação

-- ===================================================================
-- QUERIES ÚTEIS PARA ADMINISTRAÇÃO
-- ===================================================================

-- Ver sessões ativas:
-- SELECT * FROM v_active_sessions;

-- Ver usuários online:
-- SELECT * FROM v_users_online;

-- Ver tentativas de ataque:
-- SELECT * FROM v_session_attacks;

-- Ver atividades de sessão:
-- SELECT * FROM v_session_activity;

-- Ver estatísticas por usuário:
-- SELECT * FROM v_user_session_stats;

-- Destruir sessão específica:
-- DELETE FROM sessions WHERE id = 'session_id_here';

-- Destruir todas as sessões de um usuário:
-- DELETE FROM sessions WHERE user_id = 1;

-- Ver sessões de um usuário específico:
-- SELECT * FROM sessions WHERE user_id = 1;

-- Ver histórico de sessões de um usuário:
-- SELECT * FROM session_logs WHERE user_id = 1 ORDER BY created_at DESC LIMIT 20;

-- Ver sessões inativas (mais de 2 horas):
-- SELECT * FROM sessions 
-- WHERE last_activity < (strftime('%s', 'now') - 7200);

-- Contar sessões ativas:
-- SELECT COUNT(*) FROM sessions 
-- WHERE last_activity > (strftime('%s', 'now') - 7200);

-- Contar usuários online:
-- SELECT COUNT(DISTINCT user_id) FROM sessions 
-- WHERE last_activity > (strftime('%s', 'now') - 7200)
-- AND user_id IS NOT NULL;

-- Ver sessões por IP:
-- SELECT ip_address, COUNT(*) as session_count
-- FROM sessions
-- WHERE last_activity > (strftime('%s', 'now') - 7200)
-- GROUP BY ip_address
-- ORDER BY session_count DESC;

-- ===================================================================
-- MANUTENÇÃO
-- ===================================================================

-- Limpar sessões antigas manualmente (2+ horas):
-- DELETE FROM sessions 
-- WHERE last_activity < (strftime('%s', 'now') - 7200);

-- Limpar logs antigos manualmente (30+ dias):
-- DELETE FROM session_logs 
-- WHERE created_at < datetime('now', '-30 days');

-- Limpar todas as sessões (forçar re-login):
-- DELETE FROM sessions;

-- Ver tamanho da tabela de sessões:
-- SELECT COUNT(*) as total_sessions FROM sessions;

-- Ver tamanho da tabela de logs:
-- SELECT COUNT(*) as total_logs FROM session_logs;

-- ===================================================================
-- SEGURANÇA
-- ===================================================================

-- As sessões são protegidas por:
-- 1. Session fixation protection (rejeita IDs via GET/POST)
-- 2. Session hijacking protection (fingerprinting de User-Agent + headers)
-- 3. Timeout automático (configurável, padrão 2h)
-- 4. Regeneração periódica de ID (configurável, padrão 5min)
-- 5. Secure cookies (httponly, secure, samesite)
-- 6. IP tracking (detecta mudança de IP)
-- 7. Concurrent session limiting (limite de sessões por usuário)
-- 8. Activity tracking (todas as ações são logadas)

-- Conformidade OWASP:
-- - A02:2021 - Cryptographic Failures (cookies seguros)
-- - A04:2021 - Insecure Design (proteções implementadas)
-- - A05:2021 - Security Misconfiguration (configuração segura)
-- - A07:2021 - Identification and Authentication Failures (proteção de sessão)

-- Conformidade CWE:
-- - CWE-384: Session Fixation
-- - CWE-472: External Control of Assumed-Immutable Web Parameter
-- - CWE-613: Insufficient Session Expiration
-- - CWE-614: Sensitive Cookie in HTTPS Session Without 'Secure' Attribute
-- - CWE-1004: Sensitive Cookie Without 'HttpOnly' Flag
