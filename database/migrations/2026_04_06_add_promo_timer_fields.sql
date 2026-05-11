-- Migration: Add promo timer fields to products
-- Date: 2026-04-06
-- Item #12: Promoções com timer de urgência

ALTER TABLE products 
  ADD COLUMN promo_start_at DATETIME NULL DEFAULT NULL AFTER promo_price,
  ADD COLUMN promo_end_at DATETIME NULL DEFAULT NULL AFTER promo_start_at;
