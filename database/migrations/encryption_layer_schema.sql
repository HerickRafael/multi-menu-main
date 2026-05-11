-- ============================================
-- ENCRYPTION LAYER DATABASE SCHEMA
-- ============================================
-- Versão: 1.0.0
-- Descrição: Sistema de gerenciamento de criptografia
-- Recursos: Key management, field mapping, rotation tracking
-- ============================================

-- ---------------------------------------------
-- TABELA: encryption_keys
-- Gerenciamento de chaves de criptografia
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS encryption_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Key Information
    key_id VARCHAR(64) NOT NULL UNIQUE,
    key_version INT UNSIGNED NOT NULL DEFAULT 1,
    context VARCHAR(255) NOT NULL,
    
    -- Key Material (encrypted with master key)
    encrypted_key TEXT NOT NULL,
    key_hash VARCHAR(64) NOT NULL,
    
    -- Metadata
    algorithm VARCHAR(50) NOT NULL DEFAULT 'aes-256-gcm',
    key_size INT UNSIGNED NOT NULL DEFAULT 256,
    purpose ENUM('data', 'search', 'backup', 'file', 'session') NOT NULL DEFAULT 'data',
    
    -- Status
    status ENUM('active', 'rotating', 'deprecated', 'revoked') NOT NULL DEFAULT 'active',
    is_current BOOLEAN NOT NULL DEFAULT TRUE,
    
    -- Lifecycle
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    deprecated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    
    -- Rotation
    rotation_schedule INT UNSIGNED NULL COMMENT 'Days between rotations',
    next_rotation TIMESTAMP NULL,
    rotation_count INT UNSIGNED NOT NULL DEFAULT 0,
    
    -- Metadata
    created_by INT UNSIGNED NULL,
    metadata JSON NULL,
    
    INDEX idx_key_id (key_id),
    INDEX idx_context (context),
    INDEX idx_status (status),
    INDEX idx_is_current (is_current),
    INDEX idx_expires (expires_at),
    INDEX idx_next_rotation (next_rotation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- TABELA: encrypted_fields
-- Mapeamento de campos criptografados
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS encrypted_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Table/Field Information
    table_name VARCHAR(64) NOT NULL,
    field_name VARCHAR(64) NOT NULL,
    record_id VARCHAR(255) NOT NULL,
    
    -- Encryption Details
    key_id VARCHAR(64) NOT NULL,
    encryption_version INT UNSIGNED NOT NULL DEFAULT 1,
    context VARCHAR(255) NOT NULL,
    
    -- Searchable Encryption
    search_hash VARCHAR(64) NULL COMMENT 'HMAC for searchable encryption',
    is_searchable BOOLEAN NOT NULL DEFAULT FALSE,
    
    -- Metadata
    field_type VARCHAR(50) NULL COMMENT 'Original field type',
    encrypted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP NULL,
    access_count INT UNSIGNED NOT NULL DEFAULT 0,
    
    -- Rotation Tracking
    needs_rotation BOOLEAN NOT NULL DEFAULT FALSE,
    rotated_at TIMESTAMP NULL,
    previous_key_id VARCHAR(64) NULL,
    
    -- Additional Info
    metadata JSON NULL,
    
    UNIQUE KEY uk_table_field_record (table_name, field_name, record_id),
    INDEX idx_key_id (key_id),
    INDEX idx_search_hash (search_hash),
    INDEX idx_needs_rotation (needs_rotation),
    INDEX idx_table_name (table_name),
    INDEX idx_encrypted_at (encrypted_at),
    
    FOREIGN KEY (key_id) REFERENCES encryption_keys(key_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- TABELA: key_rotation_log
-- Histórico de rotação de chaves
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS key_rotation_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Rotation Information
    rotation_id VARCHAR(64) NOT NULL UNIQUE,
    key_id VARCHAR(64) NOT NULL,
    
    -- Version Change
    old_version INT UNSIGNED NOT NULL,
    new_version INT UNSIGNED NOT NULL,
    
    -- Status
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'rolled_back') NOT NULL DEFAULT 'pending',
    rotation_type ENUM('scheduled', 'manual', 'compromised', 'expired') NOT NULL,
    
    -- Progress
    total_records INT UNSIGNED NOT NULL DEFAULT 0,
    processed_records INT UNSIGNED NOT NULL DEFAULT 0,
    failed_records INT UNSIGNED NOT NULL DEFAULT 0,
    progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    
    -- Timing
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    duration_seconds INT UNSIGNED NULL,
    
    -- Details
    initiated_by INT UNSIGNED NULL,
    reason TEXT NULL,
    error_message TEXT NULL,
    
    -- Metadata
    affected_tables JSON NULL COMMENT 'List of tables affected',
    statistics JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_rotation_id (rotation_id),
    INDEX idx_key_id (key_id),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at),
    INDEX idx_rotation_type (rotation_type),
    
    FOREIGN KEY (key_id) REFERENCES encryption_keys(key_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- TABELA: encryption_operations
-- Log de operações de criptografia
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS encryption_operations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Operation Details
    operation_type ENUM('encrypt', 'decrypt', 'rotate', 'derive_key', 'generate_token') NOT NULL,
    context VARCHAR(255) NOT NULL,
    
    -- Target
    table_name VARCHAR(64) NULL,
    field_name VARCHAR(64) NULL,
    record_id VARCHAR(255) NULL,
    
    -- Result
    status ENUM('success', 'failure') NOT NULL,
    error_message TEXT NULL,
    
    -- Performance
    execution_time_ms INT UNSIGNED NOT NULL,
    
    -- Security
    key_id VARCHAR(64) NULL,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    
    -- Timestamp
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_operation_type (operation_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_table_field (table_name, field_name),
    INDEX idx_user_id (user_id),
    INDEX idx_key_id (key_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- VIEW: v_active_keys
-- Chaves ativas no sistema
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_active_keys AS
SELECT 
    key_id,
    key_version,
    context,
    algorithm,
    purpose,
    status,
    created_at,
    expires_at,
    rotation_count,
    next_rotation,
    DATEDIFF(next_rotation, NOW()) AS days_until_rotation,
    CASE 
        WHEN expires_at IS NOT NULL AND expires_at < NOW() THEN 'expired'
        WHEN next_rotation IS NOT NULL AND next_rotation < NOW() THEN 'rotation_due'
        WHEN status = 'active' THEN 'healthy'
        ELSE 'attention_required'
    END AS health_status
FROM encryption_keys
WHERE status IN ('active', 'rotating')
ORDER BY next_rotation ASC;

-- ---------------------------------------------
-- VIEW: v_encryption_statistics
-- Estatísticas de criptografia
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_encryption_statistics AS
SELECT 
    -- Key Statistics
    (SELECT COUNT(*) FROM encryption_keys WHERE status = 'active') AS active_keys,
    (SELECT COUNT(*) FROM encryption_keys WHERE status = 'rotating') AS rotating_keys,
    (SELECT COUNT(*) FROM encryption_keys WHERE status = 'deprecated') AS deprecated_keys,
    
    -- Field Statistics
    (SELECT COUNT(*) FROM encrypted_fields) AS total_encrypted_fields,
    (SELECT COUNT(DISTINCT table_name) FROM encrypted_fields) AS tables_with_encryption,
    (SELECT COUNT(*) FROM encrypted_fields WHERE is_searchable = TRUE) AS searchable_fields,
    (SELECT COUNT(*) FROM encrypted_fields WHERE needs_rotation = TRUE) AS fields_needing_rotation,
    
    -- Rotation Statistics
    (SELECT COUNT(*) FROM key_rotation_log WHERE status = 'completed') AS completed_rotations,
    (SELECT COUNT(*) FROM key_rotation_log WHERE status = 'in_progress') AS active_rotations,
    (SELECT COUNT(*) FROM key_rotation_log WHERE status = 'failed') AS failed_rotations,
    
    -- Recent Activity
    (SELECT COUNT(*) FROM encryption_operations WHERE created_at >= NOW() - INTERVAL 24 HOUR) AS operations_24h,
    (SELECT COUNT(*) FROM encryption_operations WHERE created_at >= NOW() - INTERVAL 1 HOUR AND status = 'failure') AS failures_1h,
    
    -- Performance
    (SELECT AVG(execution_time_ms) FROM encryption_operations WHERE operation_type = 'encrypt' AND created_at >= NOW() - INTERVAL 1 HOUR) AS avg_encrypt_time_ms,
    (SELECT AVG(execution_time_ms) FROM encryption_operations WHERE operation_type = 'decrypt' AND created_at >= NOW() - INTERVAL 1 HOUR) AS avg_decrypt_time_ms;

-- ---------------------------------------------
-- VIEW: v_key_rotation_status
-- Status de rotação de chaves
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_key_rotation_status AS
SELECT 
    k.key_id,
    k.context,
    k.key_version,
    k.rotation_count,
    k.next_rotation,
    DATEDIFF(k.next_rotation, NOW()) AS days_until_rotation,
    (SELECT COUNT(*) FROM encrypted_fields WHERE key_id = k.key_id) AS encrypted_records,
    (SELECT COUNT(*) FROM encrypted_fields WHERE key_id = k.key_id AND needs_rotation = TRUE) AS records_needing_rotation,
    lr.status AS last_rotation_status,
    lr.completed_at AS last_rotation_date,
    lr.duration_seconds AS last_rotation_duration,
    CASE 
        WHEN k.next_rotation < NOW() THEN 'overdue'
        WHEN DATEDIFF(k.next_rotation, NOW()) <= 7 THEN 'due_soon'
        WHEN DATEDIFF(k.next_rotation, NOW()) <= 30 THEN 'upcoming'
        ELSE 'scheduled'
    END AS rotation_urgency
FROM encryption_keys k
LEFT JOIN (
    SELECT key_id, status, completed_at, duration_seconds,
           ROW_NUMBER() OVER (PARTITION BY key_id ORDER BY created_at DESC) AS rn
    FROM key_rotation_log
) lr ON k.key_id = lr.key_id AND lr.rn = 1
WHERE k.status IN ('active', 'rotating')
ORDER BY 
    CASE rotation_urgency
        WHEN 'overdue' THEN 1
        WHEN 'due_soon' THEN 2
        WHEN 'upcoming' THEN 3
        ELSE 4
    END,
    k.next_rotation ASC;

-- ---------------------------------------------
-- VIEW: v_encryption_health
-- Saúde do sistema de criptografia
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_encryption_health AS
SELECT 
    -- Key Health
    COUNT(CASE WHEN status = 'active' AND expires_at > NOW() THEN 1 END) AS healthy_keys,
    COUNT(CASE WHEN expires_at IS NOT NULL AND expires_at < NOW() THEN 1 END) AS expired_keys,
    COUNT(CASE WHEN next_rotation < NOW() THEN 1 END) AS rotation_overdue,
    
    -- Field Health
    (SELECT COUNT(*) FROM encrypted_fields WHERE needs_rotation = TRUE) AS fields_needing_rotation,
    (SELECT COUNT(*) FROM encrypted_fields WHERE last_accessed < NOW() - INTERVAL 90 DAY) AS stale_fields,
    
    -- Operation Health
    (SELECT COUNT(*) FROM encryption_operations 
     WHERE created_at >= NOW() - INTERVAL 1 HOUR AND status = 'failure') AS failures_last_hour,
    (SELECT AVG(execution_time_ms) FROM encryption_operations 
     WHERE created_at >= NOW() - INTERVAL 1 HOUR) AS avg_operation_time_ms,
    
    -- Overall Status
    CASE 
        WHEN (SELECT COUNT(*) FROM encryption_operations 
              WHERE created_at >= NOW() - INTERVAL 1 HOUR AND status = 'failure') > 10 THEN 'critical'
        WHEN (SELECT COUNT(*) FROM encryption_keys 
              WHERE expires_at < NOW() OR next_rotation < NOW()) > 0 THEN 'warning'
        WHEN (SELECT AVG(execution_time_ms) FROM encryption_operations 
              WHERE created_at >= NOW() - INTERVAL 1 HOUR) > 100 THEN 'degraded'
        ELSE 'healthy'
    END AS system_health
FROM encryption_keys;

-- ---------------------------------------------
-- VIEW: v_searchable_fields
-- Campos com criptografia pesquisável
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_searchable_fields AS
SELECT 
    ef.table_name,
    ef.field_name,
    ef.context,
    COUNT(*) AS total_records,
    COUNT(ef.search_hash) AS records_with_hash,
    MIN(ef.encrypted_at) AS first_encrypted,
    MAX(ef.encrypted_at) AS last_encrypted,
    SUM(ef.access_count) AS total_accesses,
    AVG(ef.access_count) AS avg_accesses
FROM encrypted_fields ef
WHERE ef.is_searchable = TRUE
GROUP BY ef.table_name, ef.field_name, ef.context
ORDER BY total_records DESC;

-- ---------------------------------------------
-- TRIGGER: update_key_current_status
-- Atualiza status is_current quando nova chave é ativada
-- ---------------------------------------------
DELIMITER $$

CREATE TRIGGER update_key_current_status
AFTER UPDATE ON encryption_keys
FOR EACH ROW
BEGIN
    IF NEW.is_current = TRUE AND NEW.status = 'active' AND OLD.is_current = FALSE THEN
        -- Remove is_current de outras chaves do mesmo contexto
        UPDATE encryption_keys 
        SET is_current = FALSE 
        WHERE context = NEW.context 
          AND key_id != NEW.key_id 
          AND is_current = TRUE;
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------
-- TRIGGER: log_key_rotation_start
-- Log início de rotação de chave
-- ---------------------------------------------
DELIMITER $$

CREATE TRIGGER log_key_rotation_start
AFTER UPDATE ON encryption_keys
FOR EACH ROW
BEGIN
    IF NEW.status = 'rotating' AND OLD.status != 'rotating' THEN
        INSERT INTO key_rotation_log (
            rotation_id,
            key_id,
            old_version,
            new_version,
            status,
            rotation_type,
            total_records,
            started_at
        )
        SELECT 
            CONCAT('ROT-', NEW.key_id, '-', UNIX_TIMESTAMP()),
            NEW.key_id,
            OLD.key_version,
            NEW.key_version,
            'in_progress',
            'scheduled',
            COUNT(*),
            NOW()
        FROM encrypted_fields
        WHERE key_id = NEW.key_id;
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------
-- TRIGGER: mark_fields_for_rotation
-- Marca campos para rotação quando chave muda
-- ---------------------------------------------
DELIMITER $$

CREATE TRIGGER mark_fields_for_rotation
AFTER UPDATE ON encryption_keys
FOR EACH ROW
BEGIN
    IF NEW.status = 'rotating' AND OLD.status = 'active' THEN
        UPDATE encrypted_fields
        SET needs_rotation = TRUE
        WHERE key_id = NEW.key_id
          AND needs_rotation = FALSE;
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------
-- STORED PROCEDURE: rotate_encryption_key
-- Rotaciona chave de criptografia
-- ---------------------------------------------
DELIMITER $$

CREATE PROCEDURE rotate_encryption_key(
    IN p_key_id VARCHAR(64),
    IN p_reason TEXT
)
BEGIN
    DECLARE v_rotation_id VARCHAR(64);
    DECLARE v_old_version INT;
    DECLARE v_new_version INT;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Get current version
    SELECT key_version INTO v_old_version
    FROM encryption_keys
    WHERE key_id = p_key_id AND status = 'active';
    
    IF v_old_version IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Key not found or not active';
    END IF;
    
    -- Generate rotation ID
    SET v_rotation_id = CONCAT('ROT-', p_key_id, '-', UNIX_TIMESTAMP());
    SET v_new_version = v_old_version + 1;
    
    -- Update key status
    UPDATE encryption_keys
    SET status = 'rotating',
        key_version = v_new_version,
        rotation_count = rotation_count + 1
    WHERE key_id = p_key_id;
    
    -- Create rotation log entry
    INSERT INTO key_rotation_log (
        rotation_id,
        key_id,
        old_version,
        new_version,
        status,
        rotation_type,
        reason,
        total_records,
        started_at
    )
    SELECT 
        v_rotation_id,
        p_key_id,
        v_old_version,
        v_new_version,
        'in_progress',
        'manual',
        p_reason,
        COUNT(*),
        NOW()
    FROM encrypted_fields
    WHERE key_id = p_key_id;
    
    COMMIT;
    
    SELECT v_rotation_id AS rotation_id, v_new_version AS new_version;
END$$

DELIMITER ;

-- ---------------------------------------------
-- STORED PROCEDURE: complete_key_rotation
-- Completa rotação de chave
-- ---------------------------------------------
DELIMITER $$

CREATE PROCEDURE complete_key_rotation(
    IN p_rotation_id VARCHAR(64),
    IN p_success BOOLEAN
)
BEGIN
    DECLARE v_key_id VARCHAR(64);
    DECLARE v_started_at TIMESTAMP;
    
    -- Get rotation details
    SELECT key_id, started_at INTO v_key_id, v_started_at
    FROM key_rotation_log
    WHERE rotation_id = p_rotation_id;
    
    IF v_key_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Rotation not found';
    END IF;
    
    START TRANSACTION;
    
    IF p_success THEN
        -- Mark rotation as completed
        UPDATE key_rotation_log
        SET status = 'completed',
            completed_at = NOW(),
            duration_seconds = TIMESTAMPDIFF(SECOND, v_started_at, NOW()),
            progress_percent = 100.00,
            processed_records = total_records
        WHERE rotation_id = p_rotation_id;
        
        -- Update key status
        UPDATE encryption_keys
        SET status = 'active',
            activated_at = NOW(),
            next_rotation = DATE_ADD(NOW(), INTERVAL rotation_schedule DAY)
        WHERE key_id = v_key_id;
        
        -- Clear rotation flags
        UPDATE encrypted_fields
        SET needs_rotation = FALSE,
            rotated_at = NOW()
        WHERE key_id = v_key_id AND needs_rotation = TRUE;
    ELSE
        -- Mark as failed
        UPDATE key_rotation_log
        SET status = 'failed',
            completed_at = NOW()
        WHERE rotation_id = p_rotation_id;
        
        -- Revert key status
        UPDATE encryption_keys
        SET status = 'active',
            key_version = key_version - 1
        WHERE key_id = v_key_id;
    END IF;
    
    COMMIT;
END$$

DELIMITER ;

-- ---------------------------------------------
-- STORED PROCEDURE: cleanup_old_operations
-- Limpa operações antigas
-- ---------------------------------------------
DELIMITER $$

CREATE PROCEDURE cleanup_old_operations(
    IN p_retention_days INT
)
BEGIN
    DECLARE v_deleted_count INT;
    
    DELETE FROM encryption_operations
    WHERE created_at < DATE_SUB(NOW(), INTERVAL p_retention_days DAY);
    
    SET v_deleted_count = ROW_COUNT();
    
    SELECT v_deleted_count AS deleted_operations;
END$$

DELIMITER ;

-- ---------------------------------------------
-- EVENT: auto_cleanup_operations
-- Limpeza automática de operações (executar diariamente)
-- ---------------------------------------------
CREATE EVENT IF NOT EXISTS auto_cleanup_operations
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    CALL cleanup_old_operations(90);

-- ---------------------------------------------
-- QUERIES DE EXEMPLO
-- ---------------------------------------------

-- 1. Listar chaves que precisam de rotação
-- SELECT * FROM v_key_rotation_status WHERE rotation_urgency IN ('overdue', 'due_soon');

-- 2. Verificar saúde do sistema
-- SELECT * FROM v_encryption_health;

-- 3. Campos mais acessados
-- SELECT table_name, field_name, SUM(access_count) AS total_accesses
-- FROM encrypted_fields
-- GROUP BY table_name, field_name
-- ORDER BY total_accesses DESC
-- LIMIT 10;

-- 4. Performance de operações
-- SELECT operation_type, 
--        COUNT(*) AS total,
--        AVG(execution_time_ms) AS avg_time,
--        MAX(execution_time_ms) AS max_time
-- FROM encryption_operations
-- WHERE created_at >= NOW() - INTERVAL 24 HOUR
-- GROUP BY operation_type;

-- 5. Rotações recentes
-- SELECT * FROM key_rotation_log
-- ORDER BY created_at DESC
-- LIMIT 10;

-- 6. Campos pesquisáveis por tabela
-- SELECT * FROM v_searchable_fields;

-- 7. Chaves expiradas ou próximas de expirar
-- SELECT key_id, context, expires_at, 
--        DATEDIFF(expires_at, NOW()) AS days_remaining
-- FROM encryption_keys
-- WHERE expires_at IS NOT NULL
--   AND expires_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)
-- ORDER BY expires_at ASC;

-- 8. Taxa de sucesso de operações
-- SELECT DATE(created_at) AS date,
--        operation_type,
--        COUNT(*) AS total,
--        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successful,
--        ROUND(SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS success_rate
-- FROM encryption_operations
-- WHERE created_at >= NOW() - INTERVAL 7 DAY
-- GROUP BY DATE(created_at), operation_type
-- ORDER BY date DESC, operation_type;

-- ============================================
-- FIM DO SCHEMA
-- ============================================
