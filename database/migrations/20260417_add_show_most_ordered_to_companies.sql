-- Permite habilitar/desabilitar a seção "Mais Pedidos" na home pública do cardápio
ALTER TABLE `companies`
  ADD COLUMN `show_most_ordered` TINYINT(1) NOT NULL DEFAULT 1
  AFTER `ga_measurement_id`;
