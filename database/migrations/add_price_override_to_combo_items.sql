-- Migração: Adicionar campo price_override à tabela combo_group_items
-- Data: 2025-10-23
-- Descrição: Permite definir preços customizados para itens de combo, diferentes do preço original do produto

ALTER TABLE `combo_group_items` 
ADD COLUMN `price_override` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Preço customizado para o item no combo. NULL = usar preço original do produto' 
AFTER `delta_price`;