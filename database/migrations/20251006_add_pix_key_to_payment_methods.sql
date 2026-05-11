-- Adiciona coluna pix_key para armazenar a chave Pix exibida ao cliente
ALTER TABLE payment_methods
  ADD COLUMN pix_key VARCHAR(255) DEFAULT NULL AFTER `type`;
