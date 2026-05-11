-- Migration: Add LGPD consent fields, GA4 measurement ID, and engagement metadata
-- Date: 2026-04-06

-- LGPD consent fields on customers table
ALTER TABLE customers
  ADD COLUMN lgpd_consent_at DATETIME NULL DEFAULT NULL AFTER whatsapp_e164,
  ADD COLUMN lgpd_consent_ip VARCHAR(45) NULL DEFAULT NULL AFTER lgpd_consent_at;

-- GA4 Measurement ID on companies table
ALTER TABLE companies
  ADD COLUMN ga_measurement_id VARCHAR(20) NULL DEFAULT NULL AFTER evolution_api_key;

-- Metadata JSON column on engagement queue for last product info
ALTER TABLE customer_engagement_queue
  ADD COLUMN metadata JSON NULL DEFAULT NULL AFTER customer_name;
