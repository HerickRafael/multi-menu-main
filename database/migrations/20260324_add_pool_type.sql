-- Migration: Adicionar tipo 'pool' para modo montagem (açaí, poke)
-- Pool mode: stepper por item com limite total compartilhado, sem custo extra

ALTER TABLE product_custom_groups 
  MODIFY COLUMN type ENUM('single','extra','addon','component','pool') NOT NULL DEFAULT 'extra';

ALTER TABLE customization_templates 
  MODIFY COLUMN type ENUM('single','extra','addon','component','pool') NOT NULL DEFAULT 'extra';
