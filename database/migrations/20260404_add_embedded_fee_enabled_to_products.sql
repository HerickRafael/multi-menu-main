-- Adiciona coluna para controlar quais produtos participam da taxa embutida
-- Default 1 = todos os produtos participam (backward compatible)
ALTER TABLE `products`
ADD COLUMN `embedded_fee_enabled` TINYINT(1) NOT NULL DEFAULT 1
AFTER `sort_order`;
