-- Remove a coluna antiga highlight_text (substituída por highlight_texts_by_day)
-- Executar APÓS aplicar 20251113_add_daily_highlight_texts.sql

ALTER TABLE `companies`
DROP COLUMN `highlight_text`;
