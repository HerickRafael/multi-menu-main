-- Migration: Reorganize order_numbers to be sequential (1, 2, 3...)
-- Date: 2025-12-22
-- Description: Renumbers all existing orders sequentially per company, 
--              clearing gaps from deleted orders

-- Step 1: Clear the available_order_numbers table (we'll recalculate everything)
TRUNCATE TABLE `available_order_numbers`;

-- Step 2: Temporarily allow NULL for order_number to avoid constraint issues
ALTER TABLE `orders` MODIFY COLUMN `order_number` INT NULL;

-- Step 3: Reset order_number to NULL temporarily
UPDATE `orders` SET `order_number` = NULL;

-- Step 4: Drop the unique index temporarily to allow reassignment
DROP INDEX `idx_orders_company_order_number` ON `orders`;

-- Step 5: Reassign order_numbers sequentially per company (1, 2, 3...)
SET @row_number = 0;
SET @current_company = 0;

UPDATE `orders` o
INNER JOIN (
    SELECT 
        id,
        company_id,
        @row_number := IF(@current_company = company_id, @row_number + 1, 1) AS new_order_number,
        @current_company := company_id AS dummy
    FROM `orders`
    ORDER BY company_id ASC, created_at ASC, id ASC
) AS numbered ON o.id = numbered.id
SET o.order_number = numbered.new_order_number;

-- Step 6: Recreate the unique index
CREATE UNIQUE INDEX `idx_orders_company_order_number` ON `orders` (`company_id`, `order_number`);

-- Step 7: Make order_number NOT NULL again
ALTER TABLE `orders` MODIFY COLUMN `order_number` INT NOT NULL;

-- Step 8: Update the sequence table with the correct next numbers
UPDATE `company_order_number_sequence` seq
SET seq.next_order_number = (
    SELECT COALESCE(MAX(o.order_number), 0) + 1
    FROM `orders` o
    WHERE o.company_id = seq.company_id
);

-- Step 9: Insert for companies that don't have a sequence record yet
INSERT INTO `company_order_number_sequence` (`company_id`, `next_order_number`)
SELECT c.id, 1
FROM `companies` c
WHERE NOT EXISTS (
    SELECT 1 FROM `company_order_number_sequence` s WHERE s.company_id = c.id
)
ON DUPLICATE KEY UPDATE next_order_number = next_order_number;
