-- Migration: Add confidence scoring and pending validation system
-- Date: 2026-04-09
-- Description: Adds confidence_score, status (active/pending/suspect), 
--              distinct_customers tracking, and address_street_customers table

-- 1) Add confidence_score column (0.0 to 1.0)
ALTER TABLE address_streets 
  ADD COLUMN confidence_score DECIMAL(3,2) NOT NULL DEFAULT 0.50 
  AFTER popularity_score;

-- 2) Add status column (active = appears in autocomplete, pending = needs validation, suspect = inconsistent)
ALTER TABLE address_streets 
  ADD COLUMN status ENUM('active','pending','suspect') NOT NULL DEFAULT 'active' 
  AFTER confidence_score;

-- 3) Add distinct_customers counter
ALTER TABLE address_streets 
  ADD COLUMN distinct_customers INT UNSIGNED NOT NULL DEFAULT 0 
  AFTER status;

-- 4) Index for status filtering during search
ALTER TABLE address_streets 
  ADD INDEX idx_status (company_id, city_normalized, status);

-- 5) Table to track which customers used each street (for distinct count)
CREATE TABLE IF NOT EXISTS address_street_customers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  street_id INT UNSIGNED NOT NULL,
  customer_hash VARCHAR(64) NOT NULL COMMENT 'SHA-256 of phone for privacy',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_street_customer (street_id, customer_hash),
  KEY idx_street_id (street_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) Set initial confidence scores based on source
UPDATE address_streets SET confidence_score = 0.90 WHERE source = 'osm';
UPDATE address_streets SET confidence_score = 0.85 WHERE source = 'manual';
UPDATE address_streets SET confidence_score = 0.70 WHERE source = 'customer';
UPDATE address_streets SET confidence_score = 0.30 WHERE source = 'learned';
UPDATE address_streets SET confidence_score = 0.50 WHERE source = 'nominatim';

-- 7) Set status for existing data
UPDATE address_streets SET status = 'active' WHERE source IN ('osm', 'manual', 'customer');
UPDATE address_streets SET status = 'pending' WHERE source = 'learned' AND popularity_score < 10;
UPDATE address_streets SET status = 'active' WHERE source = 'learned' AND popularity_score >= 10;
UPDATE address_streets SET status = 'active' WHERE source = 'nominatim';
