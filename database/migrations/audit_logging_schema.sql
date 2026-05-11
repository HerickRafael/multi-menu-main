-- ============================================================================
-- Audit Logging Schema
-- ============================================================================
-- Creates comprehensive audit logging infrastructure for security and compliance
-- 
-- Author: Multi-Menu Security Team
-- Version: 1.0.0
-- ============================================================================

-- ============================================================================
-- TABLE: audit_logs
-- ============================================================================
-- Main audit log table - stores all system events

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- Event identification
    event_name TEXT NOT NULL,               -- Dotted event name (e.g., auth.login)
    category TEXT NOT NULL,                 -- Event category
    severity TEXT NOT NULL,                 -- Severity level
    
    -- User context
    user_id INTEGER DEFAULT NULL,           -- User who triggered event
    session_id TEXT DEFAULT NULL,           -- Session identifier
    
    -- Request context
    ip_address TEXT NOT NULL,               -- Client IP address
    user_agent TEXT DEFAULT NULL,           -- User agent string
    request_method TEXT DEFAULT NULL,       -- HTTP method
    request_uri TEXT DEFAULT NULL,          -- Request URI
    
    -- Event data
    event_data TEXT DEFAULT NULL,           -- Event data (JSON)
    
    -- Security
    integrity_hash TEXT DEFAULT NULL,       -- Event integrity hash
    stack_trace TEXT DEFAULT NULL,          -- Stack trace (debug)
    
    -- Timestamps
    timestamp DATETIME NOT NULL,            -- Event timestamp
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_logs_event ON audit_logs(event_name);
CREATE INDEX idx_audit_logs_category ON audit_logs(category);
CREATE INDEX idx_audit_logs_severity ON audit_logs(severity);
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_ip ON audit_logs(ip_address);
CREATE INDEX idx_audit_logs_timestamp ON audit_logs(timestamp);
CREATE INDEX idx_audit_logs_category_severity ON audit_logs(category, severity);

-- ============================================================================
-- TABLE: audit_log_archive
-- ============================================================================
-- Archive for old audit logs (retention policy)

CREATE TABLE IF NOT EXISTS audit_log_archive (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    original_id INTEGER NOT NULL,           -- Original audit_logs.id
    event_name TEXT NOT NULL,
    category TEXT NOT NULL,
    severity TEXT NOT NULL,
    user_id INTEGER DEFAULT NULL,
    event_data TEXT DEFAULT NULL,
    timestamp DATETIME NOT NULL,
    archived_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_archive_timestamp ON audit_log_archive(timestamp);
CREATE INDEX idx_audit_archive_user ON audit_log_archive(user_id);

-- ============================================================================
-- TABLE: compliance_events
-- ============================================================================
-- Special table for compliance-related events (GDPR, PCI-DSS, SOC 2)

CREATE TABLE IF NOT EXISTS compliance_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    regulation TEXT NOT NULL,               -- GDPR, PCI, SOC2
    event_type TEXT NOT NULL,               -- consent, data_access, data_deletion
    user_id INTEGER DEFAULT NULL,           -- Affected user
    data_subject_id INTEGER DEFAULT NULL,   -- Data subject (for GDPR)
    
    -- Event details
    description TEXT NOT NULL,              -- Human-readable description
    event_data TEXT DEFAULT NULL,           -- Detailed event data (JSON)
    
    -- Legal basis (GDPR)
    legal_basis TEXT DEFAULT NULL,          -- consent, contract, legal_obligation, etc
    
    -- Context
    performed_by INTEGER DEFAULT NULL,      -- Admin/user who performed action
    ip_address TEXT DEFAULT NULL,
    
    -- Timestamps
    event_timestamp DATETIME NOT NULL,
    retention_until DATETIME DEFAULT NULL,  -- Data retention date
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (data_subject_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_compliance_regulation ON compliance_events(regulation);
CREATE INDEX idx_compliance_type ON compliance_events(event_type);
CREATE INDEX idx_compliance_user ON compliance_events(user_id);
CREATE INDEX idx_compliance_timestamp ON compliance_events(event_timestamp);

-- ============================================================================
-- TABLE: user_activity_summary
-- ============================================================================
-- Aggregated user activity for performance

CREATE TABLE IF NOT EXISTS user_activity_summary (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    date DATE NOT NULL,                     -- Activity date
    
    -- Activity counts
    login_count INTEGER DEFAULT 0,
    api_request_count INTEGER DEFAULT 0,
    data_access_count INTEGER DEFAULT 0,
    data_modification_count INTEGER DEFAULT 0,
    security_event_count INTEGER DEFAULT 0,
    
    -- Session info
    unique_sessions INTEGER DEFAULT 0,
    unique_ips INTEGER DEFAULT 0,
    
    -- Last activity
    last_activity DATETIME DEFAULT NULL,
    last_ip TEXT DEFAULT NULL,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, date)
);

CREATE INDEX idx_activity_summary_user ON user_activity_summary(user_id);
CREATE INDEX idx_activity_summary_date ON user_activity_summary(date);

-- ============================================================================
-- VIEW: v_recent_audit_logs
-- ============================================================================
-- Recent audit logs with user info

CREATE VIEW IF NOT EXISTS v_recent_audit_logs AS
SELECT 
    a.id,
    a.event_name,
    a.category,
    a.severity,
    a.user_id,
    u.username,
    u.email,
    a.ip_address,
    a.request_method,
    a.request_uri,
    a.event_data,
    a.timestamp,
    CASE 
        WHEN a.integrity_hash IS NOT NULL THEN 'Verified'
        ELSE 'Not Verified'
    END as integrity_status
FROM audit_logs a
LEFT JOIN users u ON a.user_id = u.id
ORDER BY a.timestamp DESC
LIMIT 1000;

-- ============================================================================
-- VIEW: v_audit_statistics
-- ============================================================================
-- Overall audit statistics

CREATE VIEW IF NOT EXISTS v_audit_statistics AS
SELECT 
    COUNT(*) as total_events,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT ip_address) as unique_ips,
    COUNT(DISTINCT category) as categories,
    MIN(timestamp) as oldest_event,
    MAX(timestamp) as newest_event,
    
    -- By severity
    SUM(CASE WHEN severity = 'debug' THEN 1 ELSE 0 END) as debug_events,
    SUM(CASE WHEN severity = 'info' THEN 1 ELSE 0 END) as info_events,
    SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning_events,
    SUM(CASE WHEN severity = 'error' THEN 1 ELSE 0 END) as error_events,
    SUM(CASE WHEN severity IN ('critical', 'alert', 'emergency') THEN 1 ELSE 0 END) as critical_events,
    
    -- By category
    SUM(CASE WHEN category = 'authentication' THEN 1 ELSE 0 END) as auth_events,
    SUM(CASE WHEN category = 'authorization' THEN 1 ELSE 0 END) as authz_events,
    SUM(CASE WHEN category = 'security' THEN 1 ELSE 0 END) as security_events,
    SUM(CASE WHEN category = 'data_modification' THEN 1 ELSE 0 END) as data_mod_events,
    SUM(CASE WHEN category = 'compliance' THEN 1 ELSE 0 END) as compliance_events
FROM audit_logs;

-- ============================================================================
-- VIEW: v_security_events
-- ============================================================================
-- Security-related events requiring attention

CREATE VIEW IF NOT EXISTS v_security_events AS
SELECT 
    a.id,
    a.event_name,
    a.severity,
    a.user_id,
    u.username,
    a.ip_address,
    a.event_data,
    a.timestamp,
    CASE 
        WHEN a.severity IN ('emergency', 'alert') THEN 'Immediate Action Required'
        WHEN a.severity = 'critical' THEN 'Critical'
        WHEN a.severity = 'error' THEN 'High Priority'
        ELSE 'Medium Priority'
    END as priority
FROM audit_logs a
LEFT JOIN users u ON a.user_id = u.id
WHERE a.category = 'security'
OR a.severity IN ('error', 'critical', 'alert', 'emergency')
ORDER BY a.timestamp DESC
LIMIT 500;

-- ============================================================================
-- VIEW: v_failed_authentications
-- ============================================================================
-- Failed login attempts

CREATE VIEW IF NOT EXISTS v_failed_authentications AS
SELECT 
    a.user_id,
    u.username,
    a.ip_address,
    COUNT(*) as attempt_count,
    MIN(a.timestamp) as first_attempt,
    MAX(a.timestamp) as last_attempt,
    CAST((julianday('now') - julianday(MAX(a.timestamp))) * 24 * 60 AS INTEGER) as minutes_since_last
FROM audit_logs a
LEFT JOIN users u ON a.user_id = u.id
WHERE a.event_name LIKE 'auth.%'
AND a.severity = 'warning'
AND a.timestamp > datetime('now', '-24 hours')
GROUP BY a.user_id, a.ip_address
HAVING COUNT(*) >= 3
ORDER BY attempt_count DESC;

-- ============================================================================
-- VIEW: v_user_activity_timeline
-- ============================================================================
-- User activity timeline (last 30 days)

CREATE VIEW IF NOT EXISTS v_user_activity_timeline AS
SELECT 
    u.id as user_id,
    u.username,
    DATE(a.timestamp) as activity_date,
    COUNT(*) as event_count,
    COUNT(DISTINCT a.category) as categories,
    COUNT(DISTINCT a.ip_address) as unique_ips,
    GROUP_CONCAT(DISTINCT a.category) as activity_categories
FROM users u
JOIN audit_logs a ON u.id = a.user_id
WHERE a.timestamp > datetime('now', '-30 days')
GROUP BY u.id, DATE(a.timestamp)
ORDER BY u.id, activity_date DESC;

-- ============================================================================
-- VIEW: v_compliance_audit_trail
-- ============================================================================
-- Compliance events for auditing

CREATE VIEW IF NOT EXISTS v_compliance_audit_trail AS
SELECT 
    c.id,
    c.regulation,
    c.event_type,
    c.user_id,
    u.username as affected_user,
    c.performed_by,
    p.username as performed_by_username,
    c.description,
    c.legal_basis,
    c.event_timestamp,
    c.retention_until,
    CASE 
        WHEN c.retention_until IS NOT NULL AND c.retention_until < datetime('now') 
        THEN 'Expired'
        ELSE 'Active'
    END as retention_status
FROM compliance_events c
LEFT JOIN users u ON c.user_id = u.id
LEFT JOIN users p ON c.performed_by = p.id
ORDER BY c.event_timestamp DESC;

-- ============================================================================
-- VIEW: v_data_access_patterns
-- ============================================================================
-- Identifies unusual data access patterns

CREATE VIEW IF NOT EXISTS v_data_access_patterns AS
SELECT 
    a.user_id,
    u.username,
    COUNT(*) as access_count,
    COUNT(DISTINCT DATE(a.timestamp)) as active_days,
    COUNT(DISTINCT a.ip_address) as unique_ips,
    MIN(a.timestamp) as first_access,
    MAX(a.timestamp) as last_access,
    CAST((MAX(julianday(a.timestamp)) - MIN(julianday(a.timestamp))) AS INTEGER) as day_span,
    CASE 
        WHEN COUNT(*) > 1000 THEN 'High Volume'
        WHEN COUNT(DISTINCT a.ip_address) > 10 THEN 'Multiple IPs'
        WHEN COUNT(DISTINCT DATE(a.timestamp)) = 1 AND COUNT(*) > 100 THEN 'Suspicious Burst'
        ELSE 'Normal'
    END as pattern_type
FROM audit_logs a
LEFT JOIN users u ON a.user_id = u.id
WHERE a.category IN ('data_access', 'data_modification')
AND a.timestamp > datetime('now', '-7 days')
GROUP BY a.user_id
HAVING COUNT(*) > 50
ORDER BY access_count DESC;

-- ============================================================================
-- TRIGGER: update_activity_summary
-- ============================================================================
-- Updates user activity summary on new events

CREATE TRIGGER IF NOT EXISTS update_activity_summary
    AFTER INSERT ON audit_logs
    WHEN NEW.user_id IS NOT NULL
BEGIN
    INSERT INTO user_activity_summary (user_id, date, login_count, api_request_count, 
                                       data_access_count, data_modification_count, 
                                       security_event_count, last_activity, last_ip)
    VALUES (NEW.user_id, DATE(NEW.timestamp), 
            CASE WHEN NEW.event_name LIKE 'auth.login%' THEN 1 ELSE 0 END,
            CASE WHEN NEW.category = 'api' THEN 1 ELSE 0 END,
            CASE WHEN NEW.category = 'data_access' THEN 1 ELSE 0 END,
            CASE WHEN NEW.category = 'data_modification' THEN 1 ELSE 0 END,
            CASE WHEN NEW.category = 'security' THEN 1 ELSE 0 END,
            NEW.timestamp, NEW.ip_address)
    ON CONFLICT(user_id, date) DO UPDATE SET
        login_count = login_count + CASE WHEN NEW.event_name LIKE 'auth.login%' THEN 1 ELSE 0 END,
        api_request_count = api_request_count + CASE WHEN NEW.category = 'api' THEN 1 ELSE 0 END,
        data_access_count = data_access_count + CASE WHEN NEW.category = 'data_access' THEN 1 ELSE 0 END,
        data_modification_count = data_modification_count + CASE WHEN NEW.category = 'data_modification' THEN 1 ELSE 0 END,
        security_event_count = security_event_count + CASE WHEN NEW.category = 'security' THEN 1 ELSE 0 END,
        last_activity = NEW.timestamp,
        last_ip = NEW.ip_address,
        updated_at = CURRENT_TIMESTAMP;
END;

-- ============================================================================
-- TRIGGER: archive_old_logs
-- ============================================================================
-- Archives logs older than 90 days

CREATE TRIGGER IF NOT EXISTS archive_old_logs
    AFTER INSERT ON audit_logs
BEGIN
    -- Move old logs to archive
    INSERT INTO audit_log_archive (original_id, event_name, category, severity, 
                                   user_id, event_data, timestamp)
    SELECT id, event_name, category, severity, user_id, event_data, timestamp
    FROM audit_logs
    WHERE timestamp < datetime('now', '-90 days')
    AND id NOT IN (SELECT original_id FROM audit_log_archive);
    
    -- Delete archived logs from main table
    DELETE FROM audit_logs
    WHERE timestamp < datetime('now', '-90 days');
END;

-- ============================================================================
-- TRIGGER: cleanup_old_archives
-- ============================================================================
-- Deletes archived logs older than retention period (365 days)

CREATE TRIGGER IF NOT EXISTS cleanup_old_archives
    AFTER INSERT ON audit_log_archive
BEGIN
    DELETE FROM audit_log_archive
    WHERE timestamp < datetime('now', '-365 days');
END;

-- ============================================================================
-- UTILITY QUERIES
-- ============================================================================

-- Query 1: Get recent events for specific user
-- SELECT * FROM v_recent_audit_logs WHERE user_id = ? LIMIT 50;

-- Query 2: Find security incidents
-- SELECT * FROM v_security_events WHERE priority IN ('Critical', 'Immediate Action Required');

-- Query 3: Failed login attempts from IP
-- SELECT * FROM v_failed_authentications WHERE ip_address = ?;

-- Query 4: User activity for date range
-- SELECT * FROM audit_logs 
-- WHERE user_id = ? 
-- AND timestamp BETWEEN ? AND ?
-- ORDER BY timestamp DESC;

-- Query 5: Compliance events for GDPR
-- SELECT * FROM v_compliance_audit_trail WHERE regulation = 'GDPR';

-- Query 6: Data modifications by user
-- SELECT * FROM audit_logs 
-- WHERE category = 'data_modification' 
-- AND user_id = ?
-- ORDER BY timestamp DESC;

-- Query 7: Unusual access patterns
-- SELECT * FROM v_data_access_patterns 
-- WHERE pattern_type != 'Normal';

-- Query 8: Events by severity
-- SELECT severity, COUNT(*) as count
-- FROM audit_logs
-- WHERE timestamp > datetime('now', '-24 hours')
-- GROUP BY severity
-- ORDER BY count DESC;

-- Query 9: Most active users today
-- SELECT user_id, username, COUNT(*) as events
-- FROM v_recent_audit_logs
-- WHERE DATE(timestamp) = DATE('now')
-- GROUP BY user_id
-- ORDER BY events DESC
-- LIMIT 10;

-- Query 10: Event distribution by hour (last 24h)
-- SELECT strftime('%H', timestamp) as hour, COUNT(*) as events
-- FROM audit_logs
-- WHERE timestamp > datetime('now', '-24 hours')
-- GROUP BY hour
-- ORDER BY hour;
