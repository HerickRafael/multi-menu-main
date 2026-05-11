CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('debug', 'info', 'warning', 'error', 'critical') DEFAULT 'info',
    module VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    context_json JSON NULL,
    company_id INT NULL,
    order_id INT NULL,
    source VARCHAR(80) DEFAULT 'app',
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_system_logs_level_time (level, logged_at),
    INDEX idx_system_logs_module_time (module, logged_at),
    INDEX idx_system_logs_company_time (company_id, logged_at),
    INDEX idx_system_logs_order_time (order_id, logged_at),
    CONSTRAINT fk_system_logs_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    CONSTRAINT fk_system_logs_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
