-- Migration: Programa de Fidelidade Progressiva
-- Cria tabelas para programa de fidelidade gamificado

CREATE TABLE IF NOT EXISTS loyalty_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    required_orders INT NOT NULL DEFAULT 10,
    reward_type ENUM('free_item', 'discount_percentage', 'discount_fixed', 'free_delivery') NOT NULL DEFAULT 'discount_percentage',
    reward_value DECIMAL(10,2) NULL COMMENT 'Valor do desconto (% ou R$)',
    reward_product_id INT NULL COMMENT 'Produto grátis (se reward_type = free_item)',
    reward_description VARCHAR(255) NOT NULL DEFAULT 'Recompensa de fidelidade',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_company_active (company_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_loyalty_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    program_id INT NOT NULL,
    current_count INT NOT NULL DEFAULT 0,
    last_rewarded_at DATETIME NULL,
    times_completed INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_customer_program (customer_id, program_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES loyalty_programs(id) ON DELETE CASCADE,
    INDEX idx_program_count (program_id, current_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
