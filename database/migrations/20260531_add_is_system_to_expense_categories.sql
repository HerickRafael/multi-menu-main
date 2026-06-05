-- Migration: Alinha expense_categories com o model (coluna is_system)
-- Date: 2026-05-31
-- Mesma situação das colunas de recorrência: a tabela existia com schema antigo
-- e o CREATE TABLE IF NOT EXISTS não adicionou is_system. O model
-- ExpenseCategory::create depende dela.

ALTER TABLE `expense_categories`
  ADD COLUMN `is_system` TINYINT(1) NOT NULL DEFAULT 0 AFTER `icon`;
