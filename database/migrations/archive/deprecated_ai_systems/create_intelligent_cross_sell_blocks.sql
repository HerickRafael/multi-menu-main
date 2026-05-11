-- ============================================================================
-- Migration: Blocos Inteligentes de Cross-Sell
-- ============================================================================
-- Permite criar múltiplos blocos de sugestão com mensagens e produtos
-- específicos, personalizados pelo comportamento do cliente
-- ============================================================================

-- Tabela de blocos de sugestão
CREATE TABLE IF NOT EXISTS cross_sell_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    block_key VARCHAR(50) NOT NULL COMMENT 'Identificador único: drink_upsell, side_upsell, dessert_upsell, etc',
    message VARCHAR(255) NOT NULL COMMENT 'Mensagem personalizada: "Vai uma bebida aí?", "Ou uma batatinha?"',
    target_category_type ENUM('hamburger', 'fries', 'drink', 'dessert', 'combo', 'other', 'any') NOT NULL,
    priority INT DEFAULT 0 COMMENT 'Ordem de exibição (menor = primeiro)',
    max_products INT DEFAULT 3 COMMENT 'Quantos produtos mostrar neste bloco',
    show_if_has_category_type VARCHAR(100) DEFAULT NULL COMMENT 'Mostrar bloco apenas se carrinho já tem este tipo (ex: hamburger)',
    show_if_missing_category_type VARCHAR(100) DEFAULT NULL COMMENT 'Mostrar bloco apenas se carrinho NÃO tem este tipo',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_block (company_id, block_key),
    INDEX idx_company_active (company_id, active, priority),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Blocos inteligentes de cross-sell com mensagens contextuais';

-- Tabela de mensagens variadas por contexto
CREATE TABLE IF NOT EXISTS cross_sell_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    block_key VARCHAR(50) NOT NULL,
    message_variant VARCHAR(255) NOT NULL COMMENT 'Variações de mensagem para A/B test',
    context VARCHAR(100) DEFAULT NULL COMMENT 'Contexto: new_customer, returning_customer, high_value, etc',
    weight INT DEFAULT 1 COMMENT 'Peso para seleção aleatória (maior = mais chance)',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_block (company_id, block_key, active),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Variações de mensagens para personalização contextual';

-- Inserir blocos padrão para empresas existentes
INSERT INTO cross_sell_blocks (company_id, block_key, message, target_category_type, priority, max_products, show_if_missing_category_type)
SELECT 
    id as company_id,
    'drink_primary' as block_key,
    'Vai uma bebida aí? 🥤' as message,
    'drink' as target_category_type,
    1 as priority,
    3 as max_products,
    'drink' as show_if_missing_category_type
FROM companies
WHERE active = 1
ON DUPLICATE KEY UPDATE message = VALUES(message);

INSERT INTO cross_sell_blocks (company_id, block_key, message, target_category_type, priority, max_products, show_if_missing_category_type)
SELECT 
    id as company_id,
    'side_primary' as block_key,
    'Ou uma batatinha? 🍟' as message,
    'fries' as target_category_type,
    2 as priority,
    3 as max_products,
    'fries' as show_if_missing_category_type
FROM companies
WHERE active = 1
ON DUPLICATE KEY UPDATE message = VALUES(message);

INSERT INTO cross_sell_blocks (company_id, block_key, message, target_category_type, priority, max_products, show_if_missing_category_type)
SELECT 
    id as company_id,
    'dessert_primary' as block_key,
    'Que tal uma sobremesa? 🍰' as message,
    'dessert' as target_category_type,
    3 as priority,
    3 as max_products,
    'dessert' as show_if_missing_category_type
FROM companies
WHERE active = 1
ON DUPLICATE KEY UPDATE message = VALUES(message);

-- Mensagens variadas para drinks (baseadas no comportamento)
INSERT INTO cross_sell_messages (company_id, block_key, message_variant, context, weight)
SELECT 
    id as company_id,
    'drink_primary' as block_key,
    'Vai uma bebida aí? 🥤' as message_variant,
    'default' as context,
    3 as weight
FROM companies WHERE active = 1;

INSERT INTO cross_sell_messages (company_id, block_key, message_variant, context, weight)
SELECT 
    id as company_id,
    'drink_primary' as block_key,
    'Sede? Temos bebidas geladinhas! ❄️' as message_variant,
    'returning_customer' as context,
    2 as weight
FROM companies WHERE active = 1;

INSERT INTO cross_sell_messages (company_id, block_key, message_variant, context, weight)
SELECT 
    id as company_id,
    'drink_primary' as block_key,
    'Completa com uma bebida? 🥤' as message_variant,
    'high_value' as context,
    2 as weight
FROM companies WHERE active = 1;

-- Mensagens variadas para sides
INSERT INTO cross_sell_messages (company_id, block_key, message_variant, context, weight)
SELECT 
    id as company_id,
    'side_primary' as block_key,
    'Ou uma batatinha? 🍟' as message_variant,
    'default' as context,
    3 as weight
FROM companies WHERE active = 1;

INSERT INTO cross_sell_messages (company_id, block_key, message_variant, context, weight)
SELECT 
    id as company_id,
    'side_primary' as block_key,
    'Batata crocante pra acompanhar? 😋' as message_variant,
    'returning_customer' as context,
    2 as weight
FROM companies WHERE active = 1;

INSERT INTO cross_sell_messages (company_id, block_key, message_variant, context, weight)
SELECT 
    id as company_id,
    'side_primary' as block_key,
    'Adiciona uma porção? 🍟' as message_variant,
    'new_customer' as context,
    1 as weight
FROM companies WHERE active = 1;

-- Mensagens para sobremesa
INSERT INTO cross_sell_messages (company_id, block_key, message_variant, context, weight)
SELECT 
    id as company_id,
    'dessert_primary' as block_key,
    'Que tal uma sobremesa? 🍰' as message_variant,
    'default' as context,
    2 as weight
FROM companies WHERE active = 1;

INSERT INTO cross_sell_messages (company_id, block_key, message_variant, context, weight)
SELECT 
    id as company_id,
    'dessert_primary' as block_key,
    'Finaliza com um doce? 😍' as message_variant,
    'high_value' as context,
    3 as weight
FROM companies WHERE active = 1;

COMMIT;
