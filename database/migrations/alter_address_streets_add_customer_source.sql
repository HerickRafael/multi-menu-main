-- Migration: Expand source ENUM to support customer-driven learning
-- Date: 2026-04-08
-- Description: Adds 'customer' and 'nominatim' source types to address_streets
--   'customer' = streets learned from real customer orders (highest priority)
--   'nominatim' = streets found via Nominatim API fallback

ALTER TABLE address_streets 
  MODIFY COLUMN source ENUM('osm','manual','learned','customer','nominatim') NOT NULL DEFAULT 'osm';
