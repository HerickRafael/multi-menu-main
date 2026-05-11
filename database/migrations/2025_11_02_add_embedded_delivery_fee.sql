-- Migration: Add embedded_delivery_fee column to companies table
-- Date: 2025-11-02
-- Description: Adds a new column to store the embedded delivery fee for loyalty discount feature

-- Check if column exists before adding (MySQL 8.0+)
-- For older versions, remove this check and handle errors manually

ALTER TABLE `companies` 
ADD COLUMN `embedded_delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 
AFTER `delivery_free_enabled`;

-- Update existing companies to have default value
UPDATE `companies` SET `embedded_delivery_fee` = 0.00 WHERE `embedded_delivery_fee` IS NULL;
