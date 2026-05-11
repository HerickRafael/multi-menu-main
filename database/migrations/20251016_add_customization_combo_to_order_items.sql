-- Adicionar campos para armazenar combo e personalização nos itens do pedido
-- Migration: 20251016_add_customization_combo_to_order_items.sql

ALTER TABLE `order_items`
ADD COLUMN `combo_data` TEXT NULL COMMENT 'Dados de combo selecionado (JSON)' AFTER `line_total`,
ADD COLUMN `customization_data` TEXT NULL COMMENT 'Dados de personalização (JSON)' AFTER `combo_data`,
ADD COLUMN `notes` TEXT NULL COMMENT 'Observações do item' AFTER `customization_data`;
