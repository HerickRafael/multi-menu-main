-- Add description, image, created_at, updated_at to categories table
ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS description TEXT NULL DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL DEFAULT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER active,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL AFTER created_at;
