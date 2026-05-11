-- Migration: Add loyalty_discount column to orders table
-- Date: 2025-11-13
-- Description: Adds a new column to store the loyalty discount amount applied to orders

ALTER TABLE `orders` 
ADD COLUMN `loyalty_discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 
AFTER `discount`;

-- Update existing orders to have default value
UPDATE `orders` SET `loyalty_discount` = 0.00 WHERE `loyalty_discount` IS NULL;
