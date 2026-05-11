-- Migration: Create customer_addresses table
-- Date: 2025-11-02
-- Description: Stores multiple delivery addresses for customers

CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL,
  `company_id` INT(11) NOT NULL,
  `label` VARCHAR(100) DEFAULT NULL COMMENT 'Ex: Casa, Trabalho, etc',
  `name` VARCHAR(150) NOT NULL COMMENT 'Nome do destinatário',
  `phone` VARCHAR(20) NOT NULL,
  `city_id` INT(11) DEFAULT NULL,
  `zone_id` INT(11) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `neighborhood` VARCHAR(100) DEFAULT NULL,
  `street` VARCHAR(255) NOT NULL,
  `number` VARCHAR(20) NOT NULL,
  `complement` VARCHAR(100) DEFAULT NULL,
  `reference` TEXT DEFAULT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Endereço padrão do cliente',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_default` (`customer_id`, `is_default`),
  CONSTRAINT `fk_address_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_address_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Índice para buscar endereços de um cliente em uma empresa específica
CREATE INDEX `idx_customer_company` ON `customer_addresses` (`customer_id`, `company_id`);
