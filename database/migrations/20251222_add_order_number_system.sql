-- Migration: Add order_number system with number reuse for deleted orders
-- Date: 2025-12-22
-- Description: Adds order_number column to orders table and creates a table 
--              to track available (reusable) order numbers from deleted orders

-- 1. Add order_number column to orders table
ALTER TABLE `orders` 
ADD COLUMN `order_number` INT NULL AFTER `id`;

-- 2. Create index for order_number (unique per company)
CREATE UNIQUE INDEX `idx_orders_company_order_number` ON `orders` (`company_id`, `order_number`);

-- 3. Create table to track available order numbers (from deleted orders)
CREATE TABLE IF NOT EXISTS `available_order_numbers` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `company_id` INT NOT NULL,
    `order_number` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_available_order_number_unique` (`company_id`, `order_number`),
    KEY `idx_available_order_number_company` (`company_id`),
    CONSTRAINT `fk_available_order_numbers_company` 
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Initialize order_number for existing orders (based on sequential order within each company)
-- This will assign sequential numbers 1, 2, 3... to existing orders per company
SET @row_number = 0;
SET @current_company = 0;

UPDATE `orders` o
INNER JOIN (
    SELECT 
        id,
        company_id,
        @row_number := IF(@current_company = company_id, @row_number + 1, 1) AS new_order_number,
        @current_company := company_id
    FROM `orders`
    ORDER BY company_id, created_at ASC, id ASC
) AS numbered ON o.id = numbered.id
SET o.order_number = numbered.new_order_number;

-- 5. Create table to track the next order number per company
CREATE TABLE IF NOT EXISTS `company_order_number_sequence` (
    `company_id` INT NOT NULL,
    `next_order_number` INT NOT NULL DEFAULT 1,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`company_id`),
    CONSTRAINT `fk_company_order_number_sequence_company` 
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Initialize next_order_number for each company based on max existing order_number
INSERT INTO `company_order_number_sequence` (`company_id`, `next_order_number`)
SELECT 
    company_id, 
    COALESCE(MAX(order_number), 0) + 1
FROM `orders`
GROUP BY company_id
ON DUPLICATE KEY UPDATE 
    next_order_number = VALUES(next_order_number);

-- 7. Insert companies that don't have orders yet
INSERT INTO `company_order_number_sequence` (`company_id`, `next_order_number`)
SELECT c.id, 1
FROM `companies` c
WHERE NOT EXISTS (
    SELECT 1 FROM `company_order_number_sequence` s WHERE s.company_id = c.id
);

-- 8. Make order_number NOT NULL after initialization
ALTER TABLE `orders` MODIFY COLUMN `order_number` INT NOT NULL;
