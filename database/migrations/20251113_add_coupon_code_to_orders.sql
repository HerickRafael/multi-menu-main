-- Migration: Add coupon_code column to orders table
-- Date: 2025-11-13
-- Description: Adds a column to store the coupon code used in the order

ALTER TABLE `orders` 
ADD COLUMN `coupon_code` VARCHAR(50) NULL DEFAULT NULL 
AFTER `loyalty_discount`;

-- Add index for performance
CREATE INDEX idx_orders_coupon_code ON `orders` (`coupon_code`);
