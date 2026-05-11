CREATE TABLE IF NOT EXISTS feature_flag_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    feature_flag_id INT NOT NULL,
    old_enabled TINYINT(1) NOT NULL,
    new_enabled TINYINT(1) NOT NULL,
    changed_by INT NULL,
    reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feature_flag_history_company_time (company_id, created_at),
    INDEX idx_feature_flag_history_flag_time (feature_flag_id, created_at),
    CONSTRAINT fk_feature_flag_history_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_feature_flag_history_flag FOREIGN KEY (feature_flag_id) REFERENCES feature_flags(id) ON DELETE CASCADE,
    CONSTRAINT fk_feature_flag_history_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
