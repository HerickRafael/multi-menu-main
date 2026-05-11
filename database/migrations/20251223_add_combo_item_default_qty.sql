-- Migration: Add default_qty column to combo_group_items
-- Date: 2025-12-23
-- Description: Adds a column to store the default quantity of items in combo groups

-- Add default_qty column to combo_group_items table
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'combo_group_items' AND column_name = 'default_qty');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE combo_group_items ADD COLUMN default_qty INT NOT NULL DEFAULT 1 AFTER is_default', 'SELECT "Column default_qty already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
