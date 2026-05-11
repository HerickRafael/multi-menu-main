-- Migration: company_operational_status
-- Rastreia mudanças de status operacional de lojas

CREATE TABLE IF NOT EXISTS `company_operational_status` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `company_id` int NOT NULL,
  `status` enum('active', 'suspended', 'maintenance', 'blocked') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `reason` text COLLATE utf8mb4_general_ci,
  `admin_responsible_id` int,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  KEY `idx_company_id` (`company_id`),
  KEY `idx_status_company` (`status`, `company_id`),
  KEY `idx_changed_at` (`changed_at`),
  
  CONSTRAINT `fk_company_operational_status_company_id` 
    FOREIGN KEY (`company_id`) 
    REFERENCES `companies` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Índice compostos para queries comuns
CREATE INDEX `idx_company_changed_desc` ON `company_operational_status` (`company_id`, `changed_at` DESC);
