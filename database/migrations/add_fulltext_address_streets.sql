-- Migration: Add FULLTEXT index to address_streets for fast text search
-- Date: 2025-01-20
-- Description: Enables MySQL FULLTEXT search on street_normalized column
--              for the enterprise address autocomplete system

-- Add FULLTEXT index (InnoDB, min token size = 3)
ALTER TABLE address_streets ADD FULLTEXT INDEX ft_street_normalized (street_normalized);
