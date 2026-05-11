-- Ingredientes: coluna usada em ProductCustomization (ing.active) e Ingredient::toggleActive
ALTER TABLE `ingredients`
  ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=visível no cardápio' AFTER `max_qty`;
