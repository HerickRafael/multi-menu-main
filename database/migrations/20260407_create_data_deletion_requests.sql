-- Migration: Create data deletion requests table for LGPD compliance
-- Date: 2026-04-06

CREATE TABLE IF NOT EXISTS data_deletion_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    company_id INT NOT NULL,
    status ENUM('pending','processing','completed','rejected') NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL,
    processed_at DATETIME NULL,
    INDEX idx_customer_company (customer_id, company_id),
    INDEX idx_status (status),
    CONSTRAINT fk_ddr_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_ddr_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
