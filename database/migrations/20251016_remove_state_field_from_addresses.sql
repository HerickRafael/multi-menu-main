-- 20251016_remove_state_field_from_addresses.sql
-- Remove campo 'state' (UF) de tabelas relacionadas a endereços

-- Se existir uma tabela customer_addresses (futura), remove o campo state
-- ALTER TABLE `customer_addresses` DROP COLUMN IF EXISTS `state`;

-- Se existir uma tabela addresses (futura), remove o campo state  
-- ALTER TABLE `addresses` DROP COLUMN IF EXISTS `state`;

-- Para referência: Esta migração remove o campo Estado (UF) do sistema
-- O campo foi removido das views de checkout e profile, e do processamento
-- no controlador PublicCartController.php

-- Campos relacionados a state também foram removidos de:
-- - app/views/public/checkout.php (campo de entrada Estado UF)
-- - app/Views/public/profile.php (exibição do estado nos endereços)
-- - app/controllers/PublicCartController.php (processamento e formatação)