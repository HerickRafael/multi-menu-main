-- ============================================================================
-- Migration: Normalizar telefones existentes para formato canônico (55XXXXXXXXXXX)
-- 
-- Aplica a mesma lógica de normalizePhone() nos dados já armazenados:
--   1. Remove caracteres não-numéricos
--   2. Remove zeros à esquerda
--   3. Adiciona código do país 55 se ausente
--
-- Tabelas afetadas:
--   - customers (whatsapp_e164)
--   - whatsapp_received_messages (phone)
--   - whatsapp_send_log (phone)
--   - out_of_hours_responses (phone)
--   - whatsapp_human_takeover (phone)
--   - whatsapp_lid_mapping (phone)
--   - whatsapp_pushname_mapping (phone)
--   - customer_engagement_queue (customer_phone)
--   - customer_engagement_log (customer_phone)
--   - whatsapp_failed_queue (remote_jid → normalizar phone dentro)
--   - orders (customer_phone)
--
-- Data: 2026-03-19
-- ============================================================================

-- ============================================
-- 1. customers.whatsapp_e164
--    Remove '+' e garante formato 55XXXXXXXXXXX
-- ============================================
UPDATE customers 
SET whatsapp_e164 = REPLACE(whatsapp_e164, '+', '')
WHERE whatsapp_e164 LIKE '+%';

-- Adicionar 55 nos que não têm
UPDATE customers 
SET whatsapp_e164 = CONCAT('55', whatsapp_e164)
WHERE whatsapp_e164 IS NOT NULL 
  AND whatsapp_e164 != ''
  AND whatsapp_e164 NOT LIKE '55%'
  AND LENGTH(whatsapp_e164) <= 11;

-- ============================================
-- 2. whatsapp_received_messages.phone
-- ============================================
UPDATE whatsapp_received_messages 
SET phone = CONCAT('55', phone)
WHERE phone IS NOT NULL 
  AND phone != ''
  AND phone NOT LIKE '55%'
  AND phone REGEXP '^[0-9]+$'
  AND LENGTH(phone) <= 11;

-- ============================================
-- 3. whatsapp_send_log.phone
-- ============================================
UPDATE whatsapp_send_log
SET phone = CONCAT('55', phone)
WHERE phone IS NOT NULL 
  AND phone != ''
  AND phone NOT LIKE '55%'
  AND phone REGEXP '^[0-9]+$'
  AND LENGTH(phone) <= 11;

-- ============================================
-- 4. out_of_hours_responses.phone
-- ============================================
UPDATE out_of_hours_responses
SET phone = CONCAT('55', phone)
WHERE phone IS NOT NULL 
  AND phone != ''
  AND phone NOT LIKE '55%'
  AND phone REGEXP '^[0-9]+$'
  AND LENGTH(phone) <= 11;

-- ============================================
-- 5. whatsapp_human_takeover.phone
-- ============================================
UPDATE whatsapp_human_takeover
SET phone = CONCAT('55', phone)
WHERE phone IS NOT NULL 
  AND phone != ''
  AND phone NOT LIKE '55%'
  AND phone REGEXP '^[0-9]+$'
  AND LENGTH(phone) <= 11;

-- ============================================
-- 6. whatsapp_lid_mapping.phone
-- ============================================
UPDATE whatsapp_lid_mapping
SET phone = CONCAT('55', phone)
WHERE phone IS NOT NULL 
  AND phone != ''
  AND phone NOT LIKE '55%'
  AND phone REGEXP '^[0-9]+$'
  AND LENGTH(phone) <= 11;

-- ============================================
-- 7. whatsapp_pushname_mapping.phone
-- ============================================
UPDATE whatsapp_pushname_mapping
SET phone = CONCAT('55', phone)
WHERE phone IS NOT NULL 
  AND phone != ''
  AND phone NOT LIKE '55%'
  AND phone REGEXP '^[0-9]+$'
  AND LENGTH(phone) <= 11;

-- ============================================
-- 8. customer_engagement_queue.customer_phone
-- ============================================
UPDATE customer_engagement_queue
SET customer_phone = REPLACE(customer_phone, '+', '')
WHERE customer_phone LIKE '+%';

UPDATE customer_engagement_queue
SET customer_phone = CONCAT('55', customer_phone)
WHERE customer_phone IS NOT NULL 
  AND customer_phone != ''
  AND customer_phone NOT LIKE '55%'
  AND customer_phone REGEXP '^[0-9]+$'
  AND LENGTH(customer_phone) <= 11;

-- ============================================
-- 9. customer_engagement_log.customer_phone
-- ============================================
UPDATE customer_engagement_log
SET customer_phone = REPLACE(customer_phone, '+', '')
WHERE customer_phone LIKE '+%';

UPDATE customer_engagement_log
SET customer_phone = CONCAT('55', customer_phone)
WHERE customer_phone IS NOT NULL 
  AND customer_phone != ''
  AND customer_phone NOT LIKE '55%'
  AND customer_phone REGEXP '^[0-9]+$'
  AND LENGTH(customer_phone) <= 11;

-- ============================================
-- 10. orders.customer_phone
--     Limpar formatação e normalizar
-- ============================================
-- Primeiro: remover +
UPDATE orders
SET customer_phone = REPLACE(customer_phone, '+', '')
WHERE customer_phone LIKE '+%';

-- Remover parênteses, espaços, traços 
UPDATE orders
SET customer_phone = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(customer_phone, '(', ''), ')', ''), '-', ''), ' ', ''), '.', '')
WHERE customer_phone REGEXP '[^0-9]';

-- Remover zeros à esquerda
UPDATE orders
SET customer_phone = TRIM(LEADING '0' FROM customer_phone)
WHERE customer_phone LIKE '0%'
  AND customer_phone REGEXP '^[0-9]+$';

-- Adicionar 55 nos que não têm
UPDATE orders
SET customer_phone = CONCAT('55', customer_phone)
WHERE customer_phone IS NOT NULL 
  AND customer_phone != ''
  AND customer_phone NOT LIKE '55%'
  AND customer_phone REGEXP '^[0-9]+$'
  AND LENGTH(customer_phone) BETWEEN 10 AND 11;
