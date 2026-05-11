CREATE TABLE IF NOT EXISTS order_timeline (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    company_id INT NOT NULL,
    status_from VARCHAR(40) NULL,
    status_to VARCHAR(40) NOT NULL,
    changed_by_type ENUM('system', 'super_admin', 'store_admin', 'customer') DEFAULT 'system',
    changed_by_id INT NULL,
    source VARCHAR(60) DEFAULT 'system',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_timeline_order (order_id, created_at),
    INDEX idx_order_timeline_company (company_id, created_at),
    INDEX idx_order_timeline_status_to (status_to),
    CONSTRAINT fk_order_timeline_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_timeline_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
