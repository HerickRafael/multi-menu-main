-- Adicionar coluna para textos de destaque por dia da semana
ALTER TABLE `companies` 
ADD COLUMN `highlight_texts_by_day` JSON DEFAULT NULL COMMENT 'Textos de destaque para cada dia da semana: {"monday": "...", "tuesday": "...", ...}' 
AFTER `highlight_text`;

-- Criar exemplo de estrutura JSON
-- {
--   "monday": "Texto de segunda-feira",
--   "tuesday": "Texto de terça-feira",
--   "wednesday": "Texto de quarta-feira",
--   "thursday": "Texto de quinta-feira",
--   "friday": "Texto de sexta-feira",
--   "saturday": "Texto de sábado",
--   "sunday": "Texto de domingo"
-- }
