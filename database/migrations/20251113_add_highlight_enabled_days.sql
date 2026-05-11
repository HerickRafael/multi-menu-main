-- Adiciona coluna para armazenar quais dias estão habilitados/desabilitados
-- Independente do texto estar preenchido ou não

ALTER TABLE `companies`
ADD COLUMN `highlight_texts_enabled_days` JSON DEFAULT NULL COMMENT 'Array de dias habilitados para exibição: ["monday", "tuesday", ...]'
AFTER `highlight_texts_by_day`;
