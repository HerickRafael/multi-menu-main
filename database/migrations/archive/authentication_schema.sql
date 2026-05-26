-- ===================================================================
-- AUTHENTICATION SYSTEM - DATABASE SCHEMA
-- ===================================================================
-- Este schema cria todas as tabelas necessárias para o sistema de
-- autenticação completo com 2FA, remember me, password recovery, etc.
-- ===================================================================

-- Tabela de usuários (expandida)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    name TEXT,
    phone TEXT,
    
    -- Verificação de e-mail
    email_verified INTEGER DEFAULT 0,
    verification_token TEXT,
    
    -- Status da conta
    status TEXT DEFAULT 'active',
    
    -- Two-Factor Authentication
    two_factor_enabled INTEGER DEFAULT 0,
    two_factor_secret TEXT,
    
    -- Tracking
    last_login TEXT,
    last_ip TEXT,
    login_count INTEGER DEFAULT 0,
    
    -- Timestamps
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    deleted_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_verification_token ON users(verification_token);

-- ===================================================================
-- Tabela de tentativas de login (brute force protection)
-- ===================================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email);
CREATE INDEX IF NOT EXISTS idx_login_attempts_created ON login_attempts(created_at);

-- ===================================================================
-- Tabela de códigos 2FA
-- ===================================================================
CREATE TABLE IF NOT EXISTS two_factor_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    code TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    used INTEGER DEFAULT 0,
    used_at TEXT,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_2fa_user_id ON two_factor_codes(user_id);
CREATE INDEX IF NOT EXISTS idx_2fa_code ON two_factor_codes(code);
CREATE INDEX IF NOT EXISTS idx_2fa_expires ON two_factor_codes(expires_at);

-- ===================================================================
-- Tabela de reset de senha
-- ===================================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    used INTEGER DEFAULT 0,
    used_at TEXT,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_password_resets_token ON password_resets(token);
CREATE INDEX IF NOT EXISTS idx_password_resets_user ON password_resets(user_id);
CREATE INDEX IF NOT EXISTS idx_password_resets_expires ON password_resets(expires_at);

-- ===================================================================
-- Tabela de histórico de senhas (previne reutilização)
-- ===================================================================
CREATE TABLE IF NOT EXISTS password_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_password_history_user ON password_history(user_id);
CREATE INDEX IF NOT EXISTS idx_password_history_created ON password_history(created_at);

-- ===================================================================
-- Tabela de tokens remember me
-- ===================================================================
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    selector TEXT NOT NULL UNIQUE,
    token TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_remember_selector ON remember_tokens(selector);
CREATE INDEX IF NOT EXISTS idx_remember_user ON remember_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_remember_expires ON remember_tokens(expires_at);

-- ===================================================================
-- Tabela de logs de autenticação
-- ===================================================================
CREATE TABLE IF NOT EXISTS auth_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    success INTEGER NOT NULL,
    details TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_auth_logs_user ON auth_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_auth_logs_action ON auth_logs(action);
CREATE INDEX IF NOT EXISTS idx_auth_logs_created ON auth_logs(created_at);

-- ===================================================================
-- Tabela de sessões (opcional - para armazenar sessões no DB)
-- ===================================================================
CREATE TABLE IF NOT EXISTS sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions(last_activity);

-- ===================================================================
-- VIEWS ÚTEIS
-- ===================================================================

-- View de usuários ativos
CREATE VIEW IF NOT EXISTS v_active_users AS
SELECT 
    id,
    email,
    name,
    phone,
    email_verified,
    two_factor_enabled,
    last_login,
    login_count,
    created_at
FROM users
WHERE status = 'active' AND deleted_at IS NULL;

-- View de tentativas de login recentes
CREATE VIEW IF NOT EXISTS v_recent_login_attempts AS
SELECT 
    email,
    COUNT(*) as attempt_count,
    MAX(created_at) as last_attempt,
    ip_address
FROM login_attempts
WHERE created_at > datetime('now', '-1 hour')
GROUP BY email, ip_address
HAVING COUNT(*) >= 3;

-- View de logs de autenticação (últimas 24h)
CREATE VIEW IF NOT EXISTS v_auth_logs_recent AS
SELECT 
    al.*,
    u.email,
    u.name
FROM auth_logs al
LEFT JOIN users u ON al.user_id = u.id
WHERE al.created_at > datetime('now', '-1 day')
ORDER BY al.created_at DESC;

-- ===================================================================
-- DADOS DE TESTE (comentados - descomentar se necessário)
-- ===================================================================

-- Usuário admin de teste (senha: Admin@123)
-- INSERT INTO users (email, password, name, email_verified, status, created_at, updated_at)
-- VALUES (
--     'admin@example.com',
--     '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyqGsNzRxHGe',
--     'Administrator',
--     1,
--     'active',
--     datetime('now'),
--     datetime('now')
-- );

-- ===================================================================
-- TRIGGERS
-- ===================================================================

-- Trigger para atualizar updated_at automaticamente
CREATE TRIGGER IF NOT EXISTS update_users_timestamp 
AFTER UPDATE ON users
BEGIN
    UPDATE users SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- Trigger para limpar dados antigos (cleanup automático)
CREATE TRIGGER IF NOT EXISTS cleanup_old_login_attempts
AFTER INSERT ON login_attempts
BEGIN
    DELETE FROM login_attempts 
    WHERE created_at < datetime('now', '-1 day');
END;

CREATE TRIGGER IF NOT EXISTS cleanup_old_2fa_codes
AFTER INSERT ON two_factor_codes
BEGIN
    DELETE FROM two_factor_codes 
    WHERE expires_at < datetime('now', '-1 day');
END;

CREATE TRIGGER IF NOT EXISTS cleanup_old_password_resets
AFTER INSERT ON password_resets
BEGIN
    DELETE FROM password_resets 
    WHERE expires_at < datetime('now', '-1 day');
END;

CREATE TRIGGER IF NOT EXISTS cleanup_expired_remember_tokens
AFTER INSERT ON remember_tokens
BEGIN
    DELETE FROM remember_tokens 
    WHERE expires_at < datetime('now');
END;

-- ===================================================================
-- COMENTÁRIOS E DOCUMENTAÇÃO
-- ===================================================================

-- users: Armazena dados dos usuários com suporte para verificação de e-mail e 2FA
-- login_attempts: Registra tentativas de login para proteção contra brute force
-- two_factor_codes: Códigos temporários para autenticação de dois fatores
-- password_resets: Tokens para recuperação de senha
-- password_history: Histórico de senhas para prevenir reutilização
-- remember_tokens: Tokens para funcionalidade "lembrar-me"
-- auth_logs: Auditoria completa de todas as ações de autenticação
-- sessions: Armazenamento de sessões (opcional, pode usar file-based)

-- ===================================================================
-- MANUTENÇÃO
-- ===================================================================

-- Limpar tentativas antigas manualmente:
-- DELETE FROM login_attempts WHERE created_at < datetime('now', '-7 days');

-- Limpar códigos 2FA expirados:
-- DELETE FROM two_factor_codes WHERE expires_at < datetime('now');

-- Limpar tokens de reset expirados:
-- DELETE FROM password_resets WHERE expires_at < datetime('now');

-- Limpar remember tokens expirados:
-- DELETE FROM remember_tokens WHERE expires_at < datetime('now');

-- Limpar logs antigos (manter últimos 90 dias):
-- DELETE FROM auth_logs WHERE created_at < datetime('now', '-90 days');

-- ===================================================================
-- QUERIES ÚTEIS PARA MONITORAMENTO
-- ===================================================================

-- Contas com múltiplas tentativas de login:
-- SELECT * FROM v_recent_login_attempts;

-- Usuários logados nas últimas 24h:
-- SELECT DISTINCT user_id, email FROM auth_logs al
-- JOIN users u ON al.user_id = u.id
-- WHERE al.action = 'login' AND al.success = 1 
-- AND al.created_at > datetime('now', '-1 day');

-- Tentativas de login falhadas por IP:
-- SELECT ip_address, COUNT(*) as failures
-- FROM login_attempts
-- WHERE created_at > datetime('now', '-1 hour')
-- GROUP BY ip_address
-- ORDER BY failures DESC;

-- Tokens remember me ativos:
-- SELECT u.email, rt.created_at, rt.expires_at
-- FROM remember_tokens rt
-- JOIN users u ON rt.user_id = u.id
-- WHERE rt.expires_at > datetime('now')
-- ORDER BY rt.created_at DESC;
