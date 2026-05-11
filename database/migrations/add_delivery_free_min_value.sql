-- Adicionar campo para valor mínimo de frete grátis
-- Data: 2025-11-07

ALTER TABLE companies 
ADD COLUMN delivery_free_min_value DECIMAL(10,2) DEFAULT 0.00 
AFTER delivery_free_enabled;

-- Comentário: Campo para definir o valor mínimo do pedido para ter frete grátis
