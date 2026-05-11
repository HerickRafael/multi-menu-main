-- Migration: Add internal_name column to ingredients table
-- Date: 2025-12-03
-- Description: Adiciona campo opcional "Nomenclatura interna" para identificação no painel admin

ALTER TABLE `ingredients` 
ADD COLUMN `internal_name` VARCHAR(100) DEFAULT NULL 
COMMENT 'Nomenclatura interna (visível apenas no admin)' 
AFTER `name`;
