-- ============================================================================
-- File Upload Security Schema
-- ============================================================================
-- Creates tables and views for file upload tracking and security monitoring
-- 
-- Author: Multi-Menu Security Team
-- Version: 1.0.0
-- ============================================================================

-- ============================================================================
-- TABLE: uploaded_files
-- ============================================================================
-- Tracks all uploaded files

CREATE TABLE IF NOT EXISTS uploaded_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER DEFAULT NULL,           -- User who uploaded (NULL if anonymous)
    original_name TEXT NOT NULL,            -- Original filename
    stored_name TEXT NOT NULL,              -- Stored filename (sanitized/randomized)
    file_path TEXT NOT NULL,                -- Relative path from upload root
    mime_type TEXT NOT NULL,                -- MIME type
    file_size INTEGER NOT NULL,             -- File size in bytes
    file_hash TEXT NOT NULL,                -- SHA-256 hash for deduplication
    category TEXT DEFAULT NULL,             -- File category (image, document, etc)
    
    -- Security checks
    scan_status TEXT DEFAULT 'pending',     -- pending, clean, infected, error
    scan_timestamp DATETIME DEFAULT NULL,   -- When scanned
    scan_details TEXT DEFAULT NULL,         -- Scan result details
    
    -- Metadata
    ip_address TEXT DEFAULT NULL,           -- Upload IP
    user_agent TEXT DEFAULT NULL,           -- User agent
    upload_context TEXT DEFAULT NULL,       -- Where uploaded (profile, product, etc)
    
    -- Lifecycle
    is_temporary INTEGER DEFAULT 0,         -- Temporary file (cleanup after X days)
    is_deleted INTEGER DEFAULT 0,           -- Soft delete flag
    deleted_at DATETIME DEFAULT NULL,       -- When deleted
    expires_at DATETIME DEFAULT NULL,       -- Expiration date (for temporary files)
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_uploaded_files_user ON uploaded_files(user_id);
CREATE INDEX idx_uploaded_files_hash ON uploaded_files(file_hash);
CREATE INDEX idx_uploaded_files_mime ON uploaded_files(mime_type);
CREATE INDEX idx_uploaded_files_scan ON uploaded_files(scan_status);
CREATE INDEX idx_uploaded_files_deleted ON uploaded_files(is_deleted);
CREATE INDEX idx_uploaded_files_created ON uploaded_files(created_at);

-- ============================================================================
-- TABLE: upload_violations
-- ============================================================================
-- Logs upload security violations

CREATE TABLE IF NOT EXISTS upload_violations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER DEFAULT NULL,           -- User who attempted upload
    filename TEXT NOT NULL,                 -- Original filename
    violation_type TEXT NOT NULL,           -- Type of violation
    violation_details TEXT DEFAULT NULL,    -- Detailed information
    file_size INTEGER DEFAULT NULL,         -- File size
    mime_type TEXT DEFAULT NULL,            -- Detected MIME type
    
    -- Context
    ip_address TEXT DEFAULT NULL,           -- Client IP
    user_agent TEXT DEFAULT NULL,           -- User agent
    request_data TEXT DEFAULT NULL,         -- Additional request data (JSON)
    
    -- Actions taken
    quarantined INTEGER DEFAULT 0,          -- Was file quarantined?
    quarantine_path TEXT DEFAULT NULL,      -- Quarantine location
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_upload_violations_user ON upload_violations(user_id);
CREATE INDEX idx_upload_violations_type ON upload_violations(violation_type);
CREATE INDEX idx_upload_violations_ip ON upload_violations(ip_address);
CREATE INDEX idx_upload_violations_created ON upload_violations(created_at);

-- ============================================================================
-- TABLE: quarantined_files
-- ============================================================================
-- Tracks quarantined suspicious files

CREATE TABLE IF NOT EXISTS quarantined_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER DEFAULT NULL,           -- User who uploaded
    original_name TEXT NOT NULL,            -- Original filename
    quarantine_path TEXT NOT NULL,          -- Storage path in quarantine
    file_size INTEGER NOT NULL,             -- File size
    mime_type TEXT DEFAULT NULL,            -- MIME type
    file_hash TEXT NOT NULL,                -- SHA-256 hash
    
    -- Quarantine reason
    reason TEXT NOT NULL,                   -- Why quarantined
    details TEXT DEFAULT NULL,              -- Detailed information (JSON)
    threat_level TEXT DEFAULT 'medium',     -- low, medium, high, critical
    
    -- Context
    ip_address TEXT DEFAULT NULL,           -- Upload IP
    user_agent TEXT DEFAULT NULL,           -- User agent
    
    -- Review status
    reviewed INTEGER DEFAULT 0,             -- Has been reviewed?
    reviewed_by INTEGER DEFAULT NULL,       -- Admin who reviewed
    reviewed_at DATETIME DEFAULT NULL,      -- Review timestamp
    review_action TEXT DEFAULT NULL,        -- release, delete, escalate
    review_notes TEXT DEFAULT NULL,         -- Review notes
    
    -- Lifecycle
    auto_delete_at DATETIME DEFAULT NULL,   -- Auto-delete date
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_quarantined_files_user ON quarantined_files(user_id);
CREATE INDEX idx_quarantined_files_hash ON quarantined_files(file_hash);
CREATE INDEX idx_quarantined_files_threat ON quarantined_files(threat_level);
CREATE INDEX idx_quarantined_files_reviewed ON quarantined_files(reviewed);
CREATE INDEX idx_quarantined_files_created ON quarantined_files(created_at);

-- ============================================================================
-- VIEW: v_recent_uploads
-- ============================================================================
-- Shows recent file uploads with user info

CREATE VIEW IF NOT EXISTS v_recent_uploads AS
SELECT 
    f.id,
    f.user_id,
    u.username,
    f.original_name,
    f.stored_name,
    f.mime_type,
    ROUND(CAST(f.file_size AS FLOAT) / 1024 / 1024, 2) as size_mb,
    f.scan_status,
    f.upload_context,
    f.ip_address,
    f.created_at,
    CASE 
        WHEN f.is_deleted = 1 THEN 'Deleted'
        WHEN f.expires_at IS NOT NULL AND f.expires_at < datetime('now') THEN 'Expired'
        WHEN f.scan_status = 'infected' THEN 'Infected'
        WHEN f.scan_status = 'clean' THEN 'Active'
        ELSE 'Pending'
    END as status
FROM uploaded_files f
LEFT JOIN users u ON f.user_id = u.id
WHERE f.is_deleted = 0
ORDER BY f.created_at DESC
LIMIT 100;

-- ============================================================================
-- VIEW: v_upload_statistics
-- ============================================================================
-- Aggregate upload statistics

CREATE VIEW IF NOT EXISTS v_upload_statistics AS
SELECT 
    COUNT(*) as total_uploads,
    COUNT(DISTINCT user_id) as unique_users,
    SUM(file_size) as total_bytes,
    ROUND(CAST(SUM(file_size) AS FLOAT) / 1024 / 1024 / 1024, 2) as total_gb,
    AVG(file_size) as avg_file_size,
    MAX(file_size) as max_file_size,
    MIN(created_at) as first_upload,
    MAX(created_at) as last_upload,
    
    -- By scan status
    SUM(CASE WHEN scan_status = 'clean' THEN 1 ELSE 0 END) as clean_files,
    SUM(CASE WHEN scan_status = 'infected' THEN 1 ELSE 0 END) as infected_files,
    SUM(CASE WHEN scan_status = 'pending' THEN 1 ELSE 0 END) as pending_scans,
    
    -- By category
    SUM(CASE WHEN mime_type LIKE 'image/%' THEN 1 ELSE 0 END) as images,
    SUM(CASE WHEN mime_type LIKE 'video/%' THEN 1 ELSE 0 END) as videos,
    SUM(CASE WHEN mime_type LIKE 'application/pdf' THEN 1 ELSE 0 END) as pdfs,
    
    -- Lifecycle
    SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) as deleted_files,
    SUM(CASE WHEN is_temporary = 1 THEN 1 ELSE 0 END) as temporary_files
FROM uploaded_files;

-- ============================================================================
-- VIEW: v_violation_summary
-- ============================================================================
-- Summarizes upload violations

CREATE VIEW IF NOT EXISTS v_violation_summary AS
SELECT 
    violation_type,
    COUNT(*) as violation_count,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT ip_address) as unique_ips,
    SUM(quarantined) as files_quarantined,
    MIN(created_at) as first_violation,
    MAX(created_at) as last_violation
FROM upload_violations
WHERE created_at > datetime('now', '-30 days')
GROUP BY violation_type
ORDER BY violation_count DESC;

-- ============================================================================
-- VIEW: v_suspicious_uploaders
-- ============================================================================
-- Identifies users/IPs with high violation rates

CREATE VIEW IF NOT EXISTS v_suspicious_uploaders AS
SELECT 
    COALESCE(u.username, 'Anonymous') as username,
    v.user_id,
    v.ip_address,
    COUNT(*) as total_violations,
    COUNT(DISTINCT v.violation_type) as violation_types,
    SUM(v.quarantined) as files_quarantined,
    MIN(v.created_at) as first_violation,
    MAX(v.created_at) as last_violation,
    CASE 
        WHEN COUNT(*) >= 10 THEN 'High Risk'
        WHEN COUNT(*) >= 5 THEN 'Medium Risk'
        ELSE 'Low Risk'
    END as threat_level
FROM upload_violations v
LEFT JOIN users u ON v.user_id = u.id
WHERE v.created_at > datetime('now', '-7 days')
GROUP BY v.user_id, v.ip_address
HAVING COUNT(*) >= 3
ORDER BY total_violations DESC;

-- ============================================================================
-- VIEW: v_quarantine_pending_review
-- ============================================================================
-- Shows quarantined files awaiting review

CREATE VIEW IF NOT EXISTS v_quarantine_pending_review AS
SELECT 
    q.id,
    q.user_id,
    u.username,
    q.original_name,
    q.reason,
    q.threat_level,
    ROUND(CAST(q.file_size AS FLOAT) / 1024 / 1024, 2) as size_mb,
    q.ip_address,
    q.created_at,
    CAST((julianday('now') - julianday(q.created_at)) * 24 AS INTEGER) as hours_in_quarantine
FROM quarantined_files q
LEFT JOIN users u ON q.user_id = u.id
WHERE q.reviewed = 0
ORDER BY q.threat_level DESC, q.created_at ASC;

-- ============================================================================
-- VIEW: v_storage_by_user
-- ============================================================================
-- Shows storage usage per user

CREATE VIEW IF NOT EXISTS v_storage_by_user AS
SELECT 
    u.id as user_id,
    u.username,
    u.email,
    COUNT(f.id) as file_count,
    SUM(f.file_size) as total_bytes,
    ROUND(CAST(SUM(f.file_size) AS FLOAT) / 1024 / 1024, 2) as total_mb,
    MAX(f.created_at) as last_upload,
    
    -- By category
    SUM(CASE WHEN f.mime_type LIKE 'image/%' THEN 1 ELSE 0 END) as images,
    SUM(CASE WHEN f.mime_type LIKE 'video/%' THEN 1 ELSE 0 END) as videos,
    SUM(CASE WHEN f.mime_type LIKE 'application/%' THEN 1 ELSE 0 END) as documents
FROM users u
LEFT JOIN uploaded_files f ON u.id = f.user_id AND f.is_deleted = 0
GROUP BY u.id
HAVING COUNT(f.id) > 0
ORDER BY total_bytes DESC;

-- ============================================================================
-- TRIGGER: update_uploaded_files_timestamp
-- ============================================================================
-- Updates updated_at on file modification

CREATE TRIGGER IF NOT EXISTS update_uploaded_files_timestamp
    AFTER UPDATE ON uploaded_files
BEGIN
    UPDATE uploaded_files
    SET updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.id;
END;

-- ============================================================================
-- TRIGGER: cleanup_expired_files
-- ============================================================================
-- Soft-deletes expired temporary files

CREATE TRIGGER IF NOT EXISTS cleanup_expired_files
    AFTER INSERT ON uploaded_files
BEGIN
    UPDATE uploaded_files
    SET is_deleted = 1,
        deleted_at = datetime('now')
    WHERE is_temporary = 1
    AND expires_at IS NOT NULL
    AND expires_at < datetime('now')
    AND is_deleted = 0;
END;

-- ============================================================================
-- TRIGGER: cleanup_old_violations
-- ============================================================================
-- Removes old violation logs (90 days)

CREATE TRIGGER IF NOT EXISTS cleanup_old_violations
    AFTER INSERT ON upload_violations
BEGIN
    DELETE FROM upload_violations
    WHERE created_at < datetime('now', '-90 days');
END;

-- ============================================================================
-- TRIGGER: auto_delete_reviewed_quarantine
-- ============================================================================
-- Auto-deletes reviewed quarantine files after 30 days

CREATE TRIGGER IF NOT EXISTS auto_delete_reviewed_quarantine
    AFTER UPDATE ON quarantined_files
    WHEN NEW.reviewed = 1 AND OLD.reviewed = 0
BEGIN
    UPDATE quarantined_files
    SET auto_delete_at = datetime('now', '+30 days')
    WHERE id = NEW.id;
END;

-- ============================================================================
-- UTILITY QUERIES
-- ============================================================================

-- Query 1: Find duplicate files (by hash)
-- SELECT file_hash, COUNT(*) as count, GROUP_CONCAT(original_name) as files
-- FROM uploaded_files
-- WHERE is_deleted = 0
-- GROUP BY file_hash
-- HAVING COUNT(*) > 1;

-- Query 2: Get storage usage by MIME type
-- SELECT 
--     CASE 
--         WHEN mime_type LIKE 'image/%' THEN 'Images'
--         WHEN mime_type LIKE 'video/%' THEN 'Videos'
--         WHEN mime_type LIKE 'application/pdf' THEN 'PDFs'
--         ELSE 'Other'
--     END as category,
--     COUNT(*) as file_count,
--     ROUND(CAST(SUM(file_size) AS FLOAT) / 1024 / 1024, 2) as total_mb
-- FROM uploaded_files
-- WHERE is_deleted = 0
-- GROUP BY category
-- ORDER BY total_mb DESC;

-- Query 3: Find large files (> 10MB)
-- SELECT original_name, stored_name, 
--        ROUND(CAST(file_size AS FLOAT) / 1024 / 1024, 2) as size_mb,
--        created_at
-- FROM uploaded_files
-- WHERE file_size > 10485760  -- 10MB
-- AND is_deleted = 0
-- ORDER BY file_size DESC;

-- Query 4: Files pending antivirus scan
-- SELECT COUNT(*) as pending_scans
-- FROM uploaded_files
-- WHERE scan_status = 'pending'
-- AND is_deleted = 0;

-- Query 5: Recent infected files
-- SELECT f.*, u.username
-- FROM uploaded_files f
-- LEFT JOIN users u ON f.user_id = u.id
-- WHERE f.scan_status = 'infected'
-- ORDER BY f.created_at DESC
-- LIMIT 20;

-- Query 6: User upload activity (last 7 days)
-- SELECT u.username, COUNT(*) as uploads,
--        ROUND(CAST(SUM(f.file_size) AS FLOAT) / 1024 / 1024, 2) as total_mb
-- FROM users u
-- JOIN uploaded_files f ON u.id = f.user_id
-- WHERE f.created_at > datetime('now', '-7 days')
-- GROUP BY u.id
-- ORDER BY uploads DESC;

-- Query 7: Cleanup old soft-deleted files (30+ days)
-- DELETE FROM uploaded_files
-- WHERE is_deleted = 1
-- AND deleted_at < datetime('now', '-30 days');

-- Query 8: Mark file as scanned
-- UPDATE uploaded_files
-- SET scan_status = 'clean',
--     scan_timestamp = datetime('now'),
--     scan_details = 'No threats detected'
-- WHERE id = ?;
