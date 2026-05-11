-- Migration: Criar tabela de ingredientes nativos do produto
-- Esta tabela armazena os ingredientes que fazem parte da composição original do produto
-- Diferente da personalização, esses ingredientes são apenas informativos e não aparecem para o cliente

CREATE TABLE IF NOT EXISTS `product_native_ingredients` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `ingredient_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(10,2) DEFAULT 1.00 COMMENT 'Quantidade do ingrediente no produto',
    `notes` VARCHAR(255) DEFAULT NULL COMMENT 'Observações internas',
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_product_ingredient` (`product_id`, `ingredient_id`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_ingredient_id` (`ingredient_id`),
    
    CONSTRAINT `fk_pni_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pni_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
