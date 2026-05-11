-- Migration: add type and meta columns to payment_methods
ALTER TABLE `payment_methods`
  ADD COLUMN `type` varchar(50) NOT NULL DEFAULT 'others',
  ADD COLUMN `meta` json DEFAULT NULL;
