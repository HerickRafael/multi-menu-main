CREATE TABLE IF NOT EXISTS events_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(150) NOT NULL,
    aggregate_type VARCHAR(100) NULL,
    aggregate_id INT NULL,
    company_id INT NULL,
    payload_json JSON NULL,
    dispatched_by INT NULL,
    source VARCHAR(80) NOT NULL DEFAULT 'system',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_log_name_time (event_name, created_at),
    INDEX idx_events_log_company_time (company_id, created_at),
    INDEX idx_events_log_aggregate (aggregate_type, aggregate_id),
    CONSTRAINT fk_events_log_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    CONSTRAINT fk_events_log_dispatched_by FOREIGN KEY (dispatched_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
