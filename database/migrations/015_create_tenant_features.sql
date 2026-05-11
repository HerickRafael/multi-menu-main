CREATE TABLE IF NOT EXISTS tenant_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    feature_flag_id INT NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    updated_by INT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tenant_feature (company_id, feature_flag_id),
    INDEX idx_tenant_features_company (company_id),
    INDEX idx_tenant_features_flag (feature_flag_id),
    CONSTRAINT fk_tenant_features_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_tenant_features_flag FOREIGN KEY (feature_flag_id) REFERENCES feature_flags(id) ON DELETE CASCADE,
    CONSTRAINT fk_tenant_features_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tenant_features (company_id, feature_flag_id, enabled)
SELECT c.id, f.id, f.default_enabled
FROM companies c
JOIN feature_flags f ON 1=1;
