-- ============================================================================
-- API Security Schema
-- ============================================================================
-- Creates tables and views for API security features:
-- - API Keys management
-- - OAuth tokens
-- - API request logging
-- - API error tracking
-- 
-- Author: Multi-Menu Security Team
-- Version: 1.0.0
-- ============================================================================

-- ============================================================================
-- TABLE: api_keys
-- ============================================================================
-- Stores API keys for authentication
-- Keys are hashed with SHA-256 before storage

CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,                     -- Friendly name for the key
    key_hash VARCHAR(64) NOT NULL UNIQUE,           -- SHA-256 hash of the key
    scopes JSON DEFAULT NULL,                       -- JSON array of allowed scopes
    rate_limit INT DEFAULT NULL,                    -- Custom rate limit (requests/min)
    is_active TINYINT DEFAULT 1,                    -- 1 = active, 0 = revoked
    expires_at DATETIME DEFAULT NULL,               -- Expiration date (NULL = never)
    last_used_at DATETIME DEFAULT NULL,             -- Last usage timestamp
    request_count INT DEFAULT 0,                    -- Total requests made with this key
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME DEFAULT NULL,               -- Revocation timestamp
    
    INDEX idx_api_keys_user (user_id),
    INDEX idx_api_keys_hash (key_hash),
    INDEX idx_api_keys_active (is_active),
    INDEX idx_api_keys_expires (expires_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: oauth_tokens
-- ============================================================================
-- Stores OAuth2 access tokens

CREATE TABLE IF NOT EXISTS oauth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    client_id VARCHAR(255) NOT NULL,                -- OAuth client identifier
    access_token VARCHAR(64) NOT NULL UNIQUE,       -- SHA-256 hash of access token
    jwt_raw TEXT DEFAULT NULL,                      -- JWT completo (legível)
    refresh_token VARCHAR(64) DEFAULT NULL,         -- SHA-256 hash of refresh token
    scopes JSON DEFAULT NULL,                       -- JSON array of granted scopes
    expires_at DATETIME NOT NULL,                   -- Token expiration
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_oauth_tokens_user (user_id),
    INDEX idx_oauth_tokens_access (access_token),
    INDEX idx_oauth_tokens_expires (expires_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: api_requests
-- ============================================================================
-- Logs all API requests for monitoring and analytics

CREATE TABLE IF NOT EXISTS api_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,                       -- User ID (if authenticated)
    auth_method VARCHAR(50) DEFAULT NULL,           -- Authentication method used
    endpoint VARCHAR(500) NOT NULL,                 -- API endpoint
    method VARCHAR(10) NOT NULL,                    -- HTTP method (GET, POST, etc)
    ip_address VARCHAR(45) NOT NULL,                -- Client IP address (supports IPv6)
    user_agent TEXT DEFAULT NULL,                   -- User agent string
    request_data JSON DEFAULT NULL,                 -- JSON request data
    response_status INT DEFAULT NULL,               -- HTTP response status
    response_time INT DEFAULT NULL,                 -- Response time in ms
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_api_requests_user (user_id),
    INDEX idx_api_requests_endpoint (endpoint),
    INDEX idx_api_requests_method (method),
    INDEX idx_api_requests_ip (ip_address),
    INDEX idx_api_requests_created (created_at),
    INDEX idx_api_requests_status (response_status),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TABLE: api_errors
-- ============================================================================
-- Logs API errors and security violations

CREATE TABLE IF NOT EXISTS api_errors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(500) NOT NULL,                 -- API endpoint
    method VARCHAR(10) NOT NULL,                    -- HTTP method
    ip_address VARCHAR(45) NOT NULL,                -- Client IP address (supports IPv6)
    user_agent TEXT DEFAULT NULL,                   -- User agent string
    error_message TEXT NOT NULL,                    -- Error message
    error_code INT DEFAULT NULL,                    -- HTTP error code
    request_data JSON DEFAULT NULL,                 -- JSON request data
    stack_trace TEXT DEFAULT NULL,                  -- Error stack trace
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_api_errors_endpoint (endpoint),
    INDEX idx_api_errors_ip (ip_address),
    INDEX idx_api_errors_created (created_at),
    INDEX idx_api_errors_code (error_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- VIEW: v_api_key_usage
-- ============================================================================
-- Shows API key usage statistics

CREATE OR REPLACE VIEW v_api_key_usage AS
SELECT 
    k.id,
    k.user_id,
    u.name as username,
    u.email,
    k.name as key_name,
    k.scopes,
    k.rate_limit,
    k.is_active,
    k.expires_at,
    k.last_used_at,
    k.request_count,
    k.created_at,
    CASE 
        WHEN k.is_active = 0 THEN 'Revoked'
        WHEN k.expires_at IS NOT NULL AND k.expires_at < NOW() THEN 'Expired'
        ELSE 'Active'
    END as status,
    CASE
        WHEN k.last_used_at IS NOT NULL 
        THEN TIMESTAMPDIFF(MINUTE, k.last_used_at, NOW())
        ELSE NULL
    END as minutes_since_last_use
FROM api_keys k
LEFT JOIN users u ON k.user_id = u.id
ORDER BY k.created_at DESC;

-- ============================================================================
-- DADOS DE EXEMPLO (opcional - para testes apenas)
-- ============================================================================

-- Após executar este schema, você pode:
-- 1. Executar setup_api.php para gerar dados de teste
-- 2. Ou inserir manualmente uma API key para teste:

-- INSERT INTO api_keys (user_id, name, key_hash, scopes, rate_limit) 
-- VALUES (1, 'Chave de Teste', SHA2('minha-api-key-secreta', 256), JSON_ARRAY('read', 'write'), 1000);

-- Para verificar se as tabelas foram criadas:
-- SHOW TABLES LIKE 'api_%';

-- Para ver a estrutura das tabelas:
-- DESCRIBE api_keys;
-- DESCRIBE api_requests;
-- DESCRIBE api_errors;
-- DESCRIBE oauth_tokens;


