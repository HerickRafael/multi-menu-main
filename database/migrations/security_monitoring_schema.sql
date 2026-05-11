-- ============================================
-- SECURITY MONITORING DATABASE SCHEMA
-- ============================================
-- Versão: 1.0.0
-- Descrição: Sistema de monitoramento de segurança
-- Recursos: Threat detection, anomaly detection, incident management
-- ============================================

-- ---------------------------------------------
-- TABELA: security_events
-- Eventos de segurança monitorados
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS security_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Event Information
    event_type VARCHAR(100) NOT NULL,
    severity TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=info, 2=low, 3=medium, 4=high, 5=critical',
    
    -- Detection Flags
    threat_detected BOOLEAN NOT NULL DEFAULT FALSE,
    anomaly_detected BOOLEAN NOT NULL DEFAULT FALSE,
    
    -- User Context
    user_id INT UNSIGNED NULL,
    session_id VARCHAR(128) NULL,
    
    -- Request Context
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    request_method VARCHAR(10) NULL,
    request_uri TEXT NULL,
    
    -- Geographic
    country VARCHAR(2) NULL,
    city VARCHAR(100) NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    
    -- Event Data
    event_data JSON NULL,
    threat_data JSON NULL,
    anomaly_data JSON NULL,
    
    -- Timestamp
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_event_type (event_type),
    INDEX idx_severity (severity),
    INDEX idx_threat_detected (threat_detected),
    INDEX idx_anomaly_detected (anomaly_detected),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),
    INDEX idx_severity_created (severity, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- TABELA: security_incidents
-- Incidentes de segurança
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS security_incidents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Incident Information
    incident_id VARCHAR(64) NOT NULL UNIQUE,
    incident_type VARCHAR(100) NOT NULL,
    severity TINYINT UNSIGNED NOT NULL,
    
    -- Status
    status ENUM('open', 'investigating', 'contained', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    
    -- Details
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    impact_assessment TEXT NULL,
    
    -- Response
    response_actions JSON NULL,
    mitigation_steps JSON NULL,
    lessons_learned TEXT NULL,
    
    -- Assignment
    assigned_to INT UNSIGNED NULL,
    assigned_at TIMESTAMP NULL,
    
    -- Related Data
    alert_data JSON NULL,
    affected_systems JSON NULL,
    affected_users JSON NULL,
    
    -- Timeline
    detected_at TIMESTAMP NULL,
    acknowledged_at TIMESTAMP NULL,
    contained_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    
    -- Metadata
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_incident_id (incident_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_priority (priority),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- TABELA: threat_intelligence
-- Inteligência de ameaças
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS threat_intelligence (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Threat Information
    threat_id VARCHAR(64) NOT NULL UNIQUE,
    threat_type VARCHAR(100) NOT NULL,
    threat_name VARCHAR(255) NOT NULL,
    
    -- Classification
    category VARCHAR(100) NOT NULL,
    severity TINYINT UNSIGNED NOT NULL,
    confidence DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '0-100%',
    
    -- Indicators
    ip_addresses JSON NULL,
    domains JSON NULL,
    urls JSON NULL,
    file_hashes JSON NULL,
    patterns JSON NULL,
    
    -- Details
    description TEXT NULL,
    tactics JSON NULL COMMENT 'MITRE ATT&CK tactics',
    techniques JSON NULL COMMENT 'MITRE ATT&CK techniques',
    
    -- Source
    source VARCHAR(100) NULL,
    source_reference TEXT NULL,
    
    -- Status
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    first_seen TIMESTAMP NULL,
    last_seen TIMESTAMP NULL,
    
    -- Metadata
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_threat_id (threat_id),
    INDEX idx_threat_type (threat_type),
    INDEX idx_category (category),
    INDEX idx_severity (severity),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- TABELA: blocked_ips
-- IPs bloqueados
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- IP Information
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    ip_range VARCHAR(45) NULL,
    
    -- Block Details
    reason TEXT NOT NULL,
    threat_level ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    
    -- Duration
    blocked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL,
    is_permanent BOOLEAN NOT NULL DEFAULT FALSE,
    
    -- Context
    blocked_by INT UNSIGNED NULL,
    incident_id VARCHAR(64) NULL,
    event_count INT UNSIGNED NOT NULL DEFAULT 0,
    
    -- Status
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    unblocked_at TIMESTAMP NULL,
    unblocked_by INT UNSIGNED NULL,
    unblock_reason TEXT NULL,
    
    INDEX idx_ip_address (ip_address),
    INDEX idx_is_active (is_active),
    INDEX idx_blocked_until (blocked_until),
    INDEX idx_incident_id (incident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- TABELA: security_alerts
-- Alertas de segurança
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS security_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Alert Information
    alert_id VARCHAR(64) NOT NULL UNIQUE,
    alert_type VARCHAR(100) NOT NULL,
    severity TINYINT UNSIGNED NOT NULL,
    
    -- Status
    status ENUM('pending', 'sent', 'acknowledged', 'resolved', 'ignored') NOT NULL DEFAULT 'pending',
    
    -- Recipients
    sent_to JSON NULL COMMENT 'Email addresses, phone numbers, webhooks',
    channels JSON NULL COMMENT 'email, sms, webhook, slack',
    
    -- Content
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    alert_data JSON NULL,
    
    -- Response
    acknowledged_by INT UNSIGNED NULL,
    acknowledged_at TIMESTAMP NULL,
    response_actions JSON NULL,
    
    -- Related Events
    event_id BIGINT UNSIGNED NULL,
    incident_id VARCHAR(64) NULL,
    
    -- Delivery Status
    delivery_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_delivery_attempt TIMESTAMP NULL,
    delivery_errors JSON NULL,
    
    -- Timestamp
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    
    INDEX idx_alert_id (alert_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_event_id (event_id),
    INDEX idx_incident_id (incident_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (event_id) REFERENCES security_events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- TABELA: anomaly_baselines
-- Linhas de base para detecção de anomalias
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS anomaly_baselines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Baseline Information
    baseline_type VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL COMMENT 'user, ip, system, etc',
    entity_id VARCHAR(255) NOT NULL,
    
    -- Metrics
    metric_name VARCHAR(100) NOT NULL,
    baseline_value DECIMAL(15,4) NOT NULL,
    std_deviation DECIMAL(15,4) NULL,
    min_value DECIMAL(15,4) NULL,
    max_value DECIMAL(15,4) NULL,
    
    -- Thresholds
    lower_threshold DECIMAL(15,4) NULL,
    upper_threshold DECIMAL(15,4) NULL,
    
    -- Time Period
    period_start TIMESTAMP NOT NULL,
    period_end TIMESTAMP NOT NULL,
    sample_size INT UNSIGNED NOT NULL,
    
    -- Status
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    confidence DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    
    -- Metadata
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_baseline (baseline_type, entity_type, entity_id, metric_name),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- VIEW: v_security_dashboard
-- Dashboard de segurança
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_security_dashboard AS
SELECT 
    -- Events (Last 24h)
    (SELECT COUNT(*) FROM security_events WHERE created_at >= NOW() - INTERVAL 24 HOUR) AS events_24h,
    (SELECT COUNT(*) FROM security_events WHERE threat_detected = TRUE AND created_at >= NOW() - INTERVAL 24 HOUR) AS threats_24h,
    (SELECT COUNT(*) FROM security_events WHERE anomaly_detected = TRUE AND created_at >= NOW() - INTERVAL 24 HOUR) AS anomalies_24h,
    
    -- Incidents
    (SELECT COUNT(*) FROM security_incidents WHERE status = 'open') AS open_incidents,
    (SELECT COUNT(*) FROM security_incidents WHERE status = 'investigating') AS investigating_incidents,
    (SELECT COUNT(*) FROM security_incidents WHERE priority = 'critical' AND status IN ('open', 'investigating')) AS critical_incidents,
    
    -- Threats
    (SELECT COUNT(*) FROM security_events WHERE severity = 5 AND created_at >= NOW() - INTERVAL 24 HOUR) AS critical_events_24h,
    (SELECT COUNT(*) FROM blocked_ips WHERE is_active = TRUE) AS active_blocks,
    
    -- Alerts
    (SELECT COUNT(*) FROM security_alerts WHERE status = 'pending') AS pending_alerts,
    (SELECT COUNT(*) FROM security_alerts WHERE created_at >= NOW() - INTERVAL 1 HOUR) AS alerts_1h,
    
    -- System Health
    (SELECT AVG(severity) FROM security_events WHERE created_at >= NOW() - INTERVAL 24 HOUR) AS avg_severity_24h,
    (SELECT COUNT(DISTINCT ip_address) FROM security_events WHERE threat_detected = TRUE AND created_at >= NOW() - INTERVAL 24 HOUR) AS unique_threat_ips;

-- ---------------------------------------------
-- VIEW: v_threat_timeline
-- Linha do tempo de ameaças
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_threat_timeline AS
SELECT 
    DATE(created_at) AS date,
    HOUR(created_at) AS hour,
    event_type,
    severity,
    COUNT(*) AS event_count,
    SUM(threat_detected) AS threats,
    SUM(anomaly_detected) AS anomalies,
    COUNT(DISTINCT user_id) AS unique_users,
    COUNT(DISTINCT ip_address) AS unique_ips
FROM security_events
WHERE created_at >= NOW() - INTERVAL 7 DAY
GROUP BY DATE(created_at), HOUR(created_at), event_type, severity
ORDER BY date DESC, hour DESC;

-- ---------------------------------------------
-- VIEW: v_top_threats
-- Top ameaças
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_top_threats AS
SELECT 
    event_type,
    JSON_UNQUOTE(JSON_EXTRACT(threat_data, '$.type')) AS threat_type,
    severity,
    COUNT(*) AS occurrences,
    COUNT(DISTINCT ip_address) AS unique_sources,
    COUNT(DISTINCT user_id) AS affected_users,
    MIN(created_at) AS first_seen,
    MAX(created_at) AS last_seen
FROM security_events
WHERE threat_detected = TRUE
  AND created_at >= NOW() - INTERVAL 7 DAY
GROUP BY event_type, JSON_UNQUOTE(JSON_EXTRACT(threat_data, '$.type')), severity
ORDER BY occurrences DESC
LIMIT 20;

-- ---------------------------------------------
-- VIEW: v_anomaly_summary
-- Resumo de anomalias
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_anomaly_summary AS
SELECT 
    JSON_UNQUOTE(JSON_EXTRACT(anomaly_data, '$.type')) AS anomaly_type,
    DATE(created_at) AS date,
    COUNT(*) AS count,
    AVG(severity) AS avg_severity,
    COUNT(DISTINCT user_id) AS affected_users,
    COUNT(DISTINCT ip_address) AS affected_ips
FROM security_events
WHERE anomaly_detected = TRUE
  AND created_at >= NOW() - INTERVAL 30 DAY
GROUP BY JSON_UNQUOTE(JSON_EXTRACT(anomaly_data, '$.type')), DATE(created_at)
ORDER BY date DESC, count DESC;

-- ---------------------------------------------
-- VIEW: v_incident_status
-- Status de incidentes
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_incident_status AS
SELECT 
    i.incident_id,
    i.incident_type,
    i.severity,
    i.status,
    i.priority,
    i.title,
    u.username AS assigned_to_user,
    i.created_at,
    i.detected_at,
    TIMESTAMPDIFF(HOUR, i.detected_at, NOW()) AS hours_open,
    (SELECT COUNT(*) FROM security_alerts WHERE incident_id = i.incident_id) AS alert_count
FROM security_incidents i
LEFT JOIN users u ON i.assigned_to = u.id
WHERE i.status NOT IN ('closed', 'resolved')
ORDER BY i.severity DESC, i.created_at ASC;

-- ---------------------------------------------
-- VIEW: v_blocked_ip_summary
-- Resumo de IPs bloqueados
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_blocked_ip_summary AS
SELECT 
    ip_address,
    threat_level,
    reason,
    event_count,
    blocked_at,
    blocked_until,
    is_permanent,
    CASE 
        WHEN is_permanent THEN 'permanent'
        WHEN blocked_until < NOW() THEN 'expired'
        ELSE 'active'
    END AS block_status,
    (SELECT COUNT(*) FROM security_events WHERE ip_address = b.ip_address) AS total_events
FROM blocked_ips b
WHERE is_active = TRUE
ORDER BY blocked_at DESC;

-- ---------------------------------------------
-- VIEW: v_alert_effectiveness
-- Eficácia dos alertas
-- ---------------------------------------------
CREATE OR REPLACE VIEW v_alert_effectiveness AS
SELECT 
    alert_type,
    DATE(created_at) AS date,
    COUNT(*) AS total_alerts,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent_count,
    SUM(CASE WHEN status = 'acknowledged' THEN 1 ELSE 0 END) AS acknowledged_count,
    AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) AS avg_response_time_minutes,
    AVG(delivery_attempts) AS avg_delivery_attempts
FROM security_alerts
WHERE created_at >= NOW() - INTERVAL 30 DAY
GROUP BY alert_type, DATE(created_at)
ORDER BY date DESC;

-- ---------------------------------------------
-- TRIGGER: auto_create_incident_alert
-- Cria alerta ao criar incidente
-- ---------------------------------------------
DELIMITER $$

CREATE TRIGGER auto_create_incident_alert
AFTER INSERT ON security_incidents
FOR EACH ROW
BEGIN
    IF NEW.severity >= 4 THEN
        INSERT INTO security_alerts (
            alert_id,
            alert_type,
            severity,
            status,
            title,
            message,
            incident_id,
            created_at
        ) VALUES (
            CONCAT('ALT-', NEW.incident_id, '-', UNIX_TIMESTAMP()),
            NEW.incident_type,
            NEW.severity,
            'pending',
            NEW.title,
            NEW.description,
            NEW.incident_id,
            NOW()
        );
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------
-- TRIGGER: update_event_count_on_block
-- Atualiza contagem de eventos ao bloquear IP
-- ---------------------------------------------
DELIMITER $$

CREATE TRIGGER update_event_count_on_block
BEFORE INSERT ON blocked_ips
FOR EACH ROW
BEGIN
    SET NEW.event_count = (
        SELECT COUNT(*)
        FROM security_events
        WHERE ip_address = NEW.ip_address
          AND created_at >= NOW() - INTERVAL 24 HOUR
    );
END$$

DELIMITER ;

-- ---------------------------------------------
-- TRIGGER: cleanup_expired_blocks
-- Remove bloqueios expirados
-- ---------------------------------------------
DELIMITER $$

CREATE TRIGGER cleanup_expired_blocks
BEFORE UPDATE ON blocked_ips
FOR EACH ROW
BEGIN
    IF NEW.blocked_until IS NOT NULL AND NEW.blocked_until < NOW() AND NEW.is_permanent = FALSE THEN
        SET NEW.is_active = FALSE;
        SET NEW.unblocked_at = NOW();
        SET NEW.unblock_reason = 'Auto-unblocked: Block period expired';
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------
-- STORED PROCEDURE: calculate_security_score
-- Calcula score de segurança
-- ---------------------------------------------
DELIMITER $$

CREATE PROCEDURE calculate_security_score()
BEGIN
    DECLARE v_score INT DEFAULT 100;
    DECLARE v_critical_threats INT;
    DECLARE v_failed_logins INT;
    DECLARE v_active_incidents INT;
    
    -- Count critical threats (last 24h)
    SELECT COUNT(*) INTO v_critical_threats
    FROM security_events
    WHERE threat_detected = TRUE
      AND severity = 5
      AND created_at >= NOW() - INTERVAL 24 HOUR;
    
    -- Count failed logins (last 24h)
    SELECT COUNT(*) INTO v_failed_logins
    FROM security_events
    WHERE event_type = 'authentication_failure'
      AND created_at >= NOW() - INTERVAL 24 HOUR;
    
    -- Count active incidents
    SELECT COUNT(*) INTO v_active_incidents
    FROM security_incidents
    WHERE status = 'open';
    
    -- Calculate score
    SET v_score = v_score - (v_critical_threats * 20);
    SET v_score = v_score - (FLOOR(v_failed_logins / 10) * 5);
    SET v_score = v_score - (v_active_incidents * 15);
    SET v_score = GREATEST(0, LEAST(100, v_score));
    
    SELECT 
        v_score AS score,
        CASE 
            WHEN v_score >= 90 THEN 'A'
            WHEN v_score >= 80 THEN 'B'
            WHEN v_score >= 70 THEN 'C'
            WHEN v_score >= 60 THEN 'D'
            ELSE 'F'
        END AS grade,
        CASE 
            WHEN v_score >= 90 THEN 'excellent'
            WHEN v_score >= 75 THEN 'good'
            WHEN v_score >= 60 THEN 'fair'
            WHEN v_score >= 40 THEN 'poor'
            ELSE 'critical'
        END AS status,
        v_critical_threats AS critical_threats,
        v_failed_logins AS failed_logins,
        v_active_incidents AS active_incidents;
END$$

DELIMITER ;

-- ---------------------------------------------
-- STORED PROCEDURE: cleanup_old_events
-- Limpa eventos antigos
-- ---------------------------------------------
DELIMITER $$

CREATE PROCEDURE cleanup_old_events(
    IN p_retention_days INT
)
BEGIN
    DECLARE v_deleted_count INT;
    
    DELETE FROM security_events
    WHERE created_at < DATE_SUB(NOW(), INTERVAL p_retention_days DAY);
    
    SET v_deleted_count = ROW_COUNT();
    
    SELECT v_deleted_count AS deleted_events;
END$$

DELIMITER ;

-- ---------------------------------------------
-- EVENT: auto_cleanup_events
-- Limpeza automática de eventos (executar diariamente)
-- ---------------------------------------------
CREATE EVENT IF NOT EXISTS auto_cleanup_events
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    CALL cleanup_old_events(90);

-- ---------------------------------------------
-- EVENT: auto_unblock_expired_ips
-- Desbloqueia IPs expirados automaticamente
-- ---------------------------------------------
CREATE EVENT IF NOT EXISTS auto_unblock_expired_ips
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
    UPDATE blocked_ips
    SET is_active = FALSE,
        unblocked_at = NOW(),
        unblock_reason = 'Auto-unblocked: Block period expired'
    WHERE blocked_until < NOW()
      AND is_permanent = FALSE
      AND is_active = TRUE;

-- ---------------------------------------------
-- QUERIES DE EXEMPLO
-- ---------------------------------------------

-- 1. Dashboard de segurança em tempo real
-- SELECT * FROM v_security_dashboard;

-- 2. Top 10 ameaças recentes
-- SELECT * FROM v_top_threats LIMIT 10;

-- 3. Timeline de eventos (últimas 24h)
-- SELECT * FROM v_threat_timeline WHERE date >= CURDATE() - INTERVAL 1 DAY;

-- 4. Incidentes abertos prioritários
-- SELECT * FROM v_incident_status WHERE priority IN ('high', 'critical');

-- 5. IPs bloqueados ativos
-- SELECT * FROM v_blocked_ip_summary WHERE block_status = 'active';

-- 6. Calcular security score
-- CALL calculate_security_score();

-- 7. Anomalias por tipo (últimos 7 dias)
-- SELECT anomaly_type, SUM(count) as total
-- FROM v_anomaly_summary
-- WHERE date >= CURDATE() - INTERVAL 7 DAY
-- GROUP BY anomaly_type
-- ORDER BY total DESC;

-- 8. Alertas pendentes
-- SELECT * FROM security_alerts WHERE status = 'pending' ORDER BY severity DESC, created_at ASC;

-- ============================================
-- FIM DO SCHEMA
-- ============================================
