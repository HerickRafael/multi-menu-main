-- Migration: Alinha a tabela expenses com o model (colunas de recorrência)
-- Date: 2026-05-31
-- Contexto: a tabela expenses existia com schema antigo, então o CREATE TABLE
-- IF NOT EXISTS da migration 20251203 não adicionou estas colunas. O model
-- Expense (create/generateRecurring) e o admin web dependem delas.
-- (MySQL 8 não suporta ADD COLUMN IF NOT EXISTS; colunas confirmadas ausentes.)

ALTER TABLE `expenses`
  ADD COLUMN `is_recurring` TINYINT(1) NOT NULL DEFAULT 0 AFTER `reference_month`,
  ADD COLUMN `recurrence_type` ENUM('monthly','yearly') NULL AFTER `is_recurring`,
  ADD COLUMN `attachment_path` VARCHAR(255) NULL AFTER `notes`;
