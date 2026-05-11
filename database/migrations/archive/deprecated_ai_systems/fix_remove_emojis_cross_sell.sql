-- ============================================================================
-- CORREÇÃO URGENTE: Remover Emojis das Mensagens de Cross-Sell
-- ============================================================================
-- Este script atualiza todas as mensagens no banco de dados removendo emojis

-- Atualizar tabela cross_sell_blocks (mensagens principais)
UPDATE cross_sell_blocks 
SET message = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    message,
    '🥤', ''),
    '🍟', ''),
    '🍰', ''),
    '📦', ''),
    '➕', ''),
    '❄️', '')
WHERE message LIKE '%🥤%' 
   OR message LIKE '%🍟%' 
   OR message LIKE '%🍰%' 
   OR message LIKE '%📦%' 
   OR message LIKE '%➕%'
   OR message LIKE '%❄️%';

-- Atualizar tabela cross_sell_messages (variações)
UPDATE cross_sell_messages 
SET message_variant = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    message_variant,
    '🥤', ''),
    '🍟', ''),
    '🍰', ''),
    '📦', ''),
    '➕', ''),
    '❄️', '')
WHERE message_variant LIKE '%🥤%' 
   OR message_variant LIKE '%🍟%' 
   OR message_variant LIKE '%🍰%' 
   OR message_variant LIKE '%📦%' 
   OR message_variant LIKE '%➕%'
   OR message_variant LIKE '%❄️%';

-- Limpar espaços duplicados que podem ter sobrado
UPDATE cross_sell_blocks 
SET message = TRIM(REGEXP_REPLACE(message, ' +', ' '));

UPDATE cross_sell_messages 
SET message_variant = TRIM(REGEXP_REPLACE(message_variant, ' +', ' '));

-- Verificar resultado
SELECT 
    'cross_sell_blocks' as tabela,
    block_key,
    message
FROM cross_sell_blocks
WHERE company_id = 1

UNION ALL

SELECT 
    'cross_sell_messages' as tabela,
    block_key,
    message_variant as message
FROM cross_sell_messages
WHERE company_id = 1
LIMIT 20;

COMMIT;
