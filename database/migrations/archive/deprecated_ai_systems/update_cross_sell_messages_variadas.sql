-- ============================================================================
-- Atualização: Mensagens Variadas e sem Emojis
-- ============================================================================
-- Remove emojis e adiciona múltiplas variações de mensagens contextuais

-- Limpar mensagens antigas
DELETE FROM cross_sell_messages;

-- ============================================================================
-- BEBIDAS (drinks)
-- ============================================================================

-- Mensagens para clientes novos
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'drinks', 'new_customer', 'Que tal uma bebida?', 10, 1),
(1, 'drinks', 'new_customer', 'Vai uma bebida para acompanhar?', 10, 1),
(1, 'drinks', 'new_customer', 'Escolha sua bebida', 8, 1),
(1, 'drinks', 'new_customer', 'Experimente nossas bebidas', 7, 1);

-- Mensagens para clientes recorrentes
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'drinks', 'returning_customer', 'Sua bebida preferida?', 10, 1),
(1, 'drinks', 'returning_customer', 'Vai querer sua bebida de sempre?', 10, 1),
(1, 'drinks', 'returning_customer', 'Bebida para acompanhar?', 9, 1),
(1, 'drinks', 'returning_customer', 'Não esqueça da bebida', 8, 1);

-- Mensagens padrão (default)
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'drinks', 'default', 'Vai uma bebida aí?', 10, 1),
(1, 'drinks', 'default', 'Completa com uma bebida?', 9, 1),
(1, 'drinks', 'default', 'E uma bebida geladinha?', 9, 1),
(1, 'drinks', 'default', 'Adicione uma bebida', 8, 1),
(1, 'drinks', 'default', 'Que tal algo para beber?', 8, 1);

-- ============================================================================
-- ACOMPANHAMENTOS (sides)
-- ============================================================================

-- Mensagens para clientes novos
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'sides', 'new_customer', 'Vai um acompanhamento?', 10, 1),
(1, 'sides', 'new_customer', 'Que tal uma batatinha?', 10, 1),
(1, 'sides', 'new_customer', 'Escolha seu acompanhamento', 8, 1),
(1, 'sides', 'new_customer', 'Experimente nossos acompanhamentos', 7, 1);

-- Mensagens para clientes recorrentes
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'sides', 'returning_customer', 'Seu acompanhamento favorito?', 10, 1),
(1, 'sides', 'returning_customer', 'A batatinha de sempre?', 10, 1),
(1, 'sides', 'returning_customer', 'Acompanhamento para completar?', 9, 1),
(1, 'sides', 'returning_customer', 'Não vai querer um acompanhamento?', 8, 1);

-- Mensagens padrão
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'sides', 'default', 'Ou uma batatinha?', 10, 1),
(1, 'sides', 'default', 'Completa com um acompanhamento?', 9, 1),
(1, 'sides', 'default', 'Que tal uma porção?', 9, 1),
(1, 'sides', 'default', 'Adicione um acompanhamento', 8, 1),
(1, 'sides', 'default', 'Vai um lado?', 8, 1);

-- ============================================================================
-- SOBREMESAS (desserts)
-- ============================================================================

-- Mensagens para clientes novos
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'desserts', 'new_customer', 'Que tal uma sobremesa?', 10, 1),
(1, 'desserts', 'new_customer', 'Vai uma sobremesa?', 10, 1),
(1, 'desserts', 'new_customer', 'Escolha sua sobremesa', 8, 1),
(1, 'desserts', 'new_customer', 'Experimente nossas sobremesas', 7, 1);

-- Mensagens para clientes recorrentes
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'desserts', 'returning_customer', 'Sua sobremesa preferida?', 10, 1),
(1, 'desserts', 'returning_customer', 'Aquela sobremesa de sempre?', 10, 1),
(1, 'desserts', 'returning_customer', 'Sobremesa para fechar?', 9, 1),
(1, 'desserts', 'returning_customer', 'Vai querer uma sobremesa hoje?', 8, 1);

-- Mensagens padrão
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'desserts', 'default', 'E de sobremesa?', 10, 1),
(1, 'desserts', 'default', 'Que tal algo doce?', 9, 1),
(1, 'desserts', 'default', 'Finaliza com uma sobremesa?', 9, 1),
(1, 'desserts', 'default', 'Adicione uma sobremesa', 8, 1),
(1, 'desserts', 'default', 'Para fechar com chave de ouro', 8, 1);

-- ============================================================================
-- COMBOS (combos)
-- ============================================================================

-- Mensagens para clientes novos
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'combos', 'new_customer', 'Que tal um combo completo?', 10, 1),
(1, 'combos', 'new_customer', 'Veja nossos combos', 10, 1),
(1, 'combos', 'new_customer', 'Combos com desconto', 9, 1),
(1, 'combos', 'new_customer', 'Aproveite nossos combos', 8, 1);

-- Mensagens para clientes recorrentes
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'combos', 'returning_customer', 'Seu combo favorito?', 10, 1),
(1, 'combos', 'returning_customer', 'Prefere montar um combo?', 10, 1),
(1, 'combos', 'returning_customer', 'Combos promocionais', 9, 1),
(1, 'combos', 'returning_customer', 'Que tal o combo de sempre?', 8, 1);

-- Mensagens padrão
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'combos', 'default', 'Ou prefere um combo?', 10, 1),
(1, 'combos', 'default', 'Veja nossos combos', 9, 1),
(1, 'combos', 'default', 'Combos completos', 9, 1),
(1, 'combos', 'default', 'Monte seu combo', 8, 1),
(1, 'combos', 'default', 'Economize com um combo', 8, 1);

-- ============================================================================
-- ADICIONAIS (extras)
-- ============================================================================

-- Mensagens para clientes novos
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'extras', 'new_customer', 'Que tal uns adicionais?', 10, 1),
(1, 'extras', 'new_customer', 'Incremente seu pedido', 10, 1),
(1, 'extras', 'new_customer', 'Escolha seus extras', 8, 1),
(1, 'extras', 'new_customer', 'Adicione extras', 7, 1);

-- Mensagens para clientes recorrentes
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'extras', 'returning_customer', 'Seus extras favoritos?', 10, 1),
(1, 'extras', 'returning_customer', 'Vai querer extras hoje?', 10, 1),
(1, 'extras', 'returning_customer', 'Turbine seu pedido', 9, 1),
(1, 'extras', 'returning_customer', 'Adicionais especiais', 8, 1);

-- Mensagens padrão
INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active) VALUES
(1, 'extras', 'default', 'E os adicionais?', 10, 1),
(1, 'extras', 'default', 'Incremente seu lanche', 9, 1),
(1, 'extras', 'default', 'Que tal uns extras?', 9, 1),
(1, 'extras', 'default', 'Adicione mais sabor', 8, 1),
(1, 'extras', 'default', 'Personalize seu pedido', 8, 1);

-- ============================================================================
-- Atualizar mensagens principais dos blocos (sem emojis)
-- ============================================================================

UPDATE cross_sell_blocks 
SET message = 'Vai uma bebida aí?' 
WHERE block_key = 'drinks' AND company_id = 1;

UPDATE cross_sell_blocks 
SET message = 'Ou uma batatinha?' 
WHERE block_key = 'sides' AND company_id = 1;

UPDATE cross_sell_blocks 
SET message = 'Que tal uma sobremesa?' 
WHERE block_key = 'desserts' AND company_id = 1;

UPDATE cross_sell_blocks 
SET message = 'Ou prefere um combo?' 
WHERE block_key = 'combos' AND company_id = 1;

UPDATE cross_sell_blocks 
SET message = 'E os adicionais?' 
WHERE block_key = 'extras' AND company_id = 1;

-- ============================================================================
-- Replicar para outras empresas (ajuste company_id conforme necessário)
-- ============================================================================

-- Se você tiver mais empresas, replique as mensagens alterando company_id:
-- Exemplo para empresa 2:
-- INSERT INTO cross_sell_messages (company_id, block_key, context, message_variant, weight, active)
-- SELECT 2, block_key, context, message_variant, weight, active
-- FROM cross_sell_messages WHERE company_id = 1;

COMMIT;

-- ============================================================================
-- Verificar resultados
-- ============================================================================

SELECT 
    block_key,
    context,
    COUNT(*) as total_mensagens,
    GROUP_CONCAT(message_variant SEPARATOR ' | ') as exemplos
FROM cross_sell_messages
WHERE company_id = 1
GROUP BY block_key, context
ORDER BY block_key, context;
