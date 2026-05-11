-- Migration: adicionar coluna `icon` em payment_methods e backfill a partir de meta->icon
-- Data: 2025-10-09

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `payment_methods`
  ADD COLUMN `icon` VARCHAR(255) NULL AFTER `meta`;

-- Backfill utilizando JSON functions (MySQL 5.7+). Se a coluna meta for NULL ou não contiver $.icon, não altera.
UPDATE `payment_methods`
SET `icon` = JSON_UNQUOTE(JSON_EXTRACT(`meta`, '$.icon'))
WHERE `meta` IS NOT NULL
  AND JSON_EXTRACT(`meta`, '$.icon') IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(`meta`, '$.icon')) <> '';

SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

-- Nota: essa migração apenas adiciona a coluna `icon` e a preenche a partir do JSON `meta` quando possível.
-- Após validar a aplicação e o backfill, poderá ser criada uma constraint UNIQUE(company_id, type, icon) se desejar reforçar unicidade.
