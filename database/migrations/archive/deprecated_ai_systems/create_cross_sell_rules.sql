-- ============================================================================
-- Sistema de Cross-Sell Inteligente com Regras Fixas + IA
-- ============================================================================

-- Tabela de configuração do cross-sell por empresa
CREATE TABLE IF NOT EXISTS cross_sell_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    ai_enabled TINYINT(1) DEFAULT 1 COMMENT 'Usar IA para reordenar sugestões',
    max_suggestions INT DEFAULT 3 COMMENT 'Quantidade máxima de sugestões',
    weight_view DECIMAL(3,1) DEFAULT 1.0,
    weight_cart DECIMAL(3,1) DEFAULT 3.0,
    weight_purchase DECIMAL(3,1) DEFAULT 5.0,
    priority_by_margin TINYINT(1) DEFAULT 0 COMMENT 'Priorizar produtos com maior margem',
    promotional_slot_enabled TINYINT(1) DEFAULT 0 COMMENT 'Habilitar slot promocional manual',
    promotional_product_id INT DEFAULT NULL COMMENT 'Produto fixo em destaque',
    ab_test_enabled TINYINT(1) DEFAULT 0 COMMENT 'A/B Test: com IA vs sem IA',
    custom_message VARCHAR(255) DEFAULT 'Quer completar sua refeição?' COMMENT 'Mensagem personalizada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_company (company_id),
    INDEX idx_company (company_id),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (promotional_product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de regras fixas de cross-sell por categoria
CREATE TABLE IF NOT EXISTS cross_sell_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    source_category_id INT NOT NULL COMMENT 'Categoria de origem (produto sendo visualizado)',
    target_category_id INT NOT NULL COMMENT 'Categoria sugerida',
    priority INT DEFAULT 0 COMMENT 'Ordem de prioridade (menor = maior prioridade)',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_source (company_id, source_category_id, active),
    INDEX idx_company (company_id),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (source_category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (target_category_id) REFERENCES categories(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_rule (company_id, source_category_id, target_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para mapear nomes de categorias (facilita configuração)
CREATE TABLE IF NOT EXISTS category_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    category_id INT NOT NULL,
    type_name ENUM('hamburger', 'fries', 'drink', 'dessert', 'combo', 'other') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_category (company_id, category_id),
    INDEX idx_company_type (company_id, type_name),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Inserir configuração padrão para empresas existentes
-- ============================================================================

INSERT INTO cross_sell_config (company_id, ai_enabled, max_suggestions, custom_message)
SELECT id, 1, 3, 'Quer completar sua refeição?'
FROM companies
WHERE NOT EXISTS (
    SELECT 1 FROM cross_sell_config WHERE cross_sell_config.company_id = companies.id
);

-- ============================================================================
-- Procedure para criar regras padrão baseadas em tipos de categoria
-- ============================================================================

DELIMITER $$

CREATE PROCEDURE create_default_cross_sell_rules(IN p_company_id INT)
BEGIN
    DECLARE v_hamburger_cat INT;
    DECLARE v_fries_cat INT;
    DECLARE v_drink_cat INT;
    DECLARE v_dessert_cat INT;
    
    -- Buscar IDs das categorias por tipo
    SELECT category_id INTO v_hamburger_cat FROM category_types WHERE company_id = p_company_id AND type_name = 'hamburger' LIMIT 1;
    SELECT category_id INTO v_fries_cat FROM category_types WHERE company_id = p_company_id AND type_name = 'fries' LIMIT 1;
    SELECT category_id INTO v_drink_cat FROM category_types WHERE company_id = p_company_id AND type_name = 'drink' LIMIT 1;
    SELECT category_id INTO v_dessert_cat FROM category_types WHERE company_id = p_company_id AND type_name = 'dessert' LIMIT 1;
    
    -- Regras: Hambúrguer → Batata + Bebida
    IF v_hamburger_cat IS NOT NULL THEN
        IF v_fries_cat IS NOT NULL THEN
            INSERT IGNORE INTO cross_sell_rules (company_id, source_category_id, target_category_id, priority)
            VALUES (p_company_id, v_hamburger_cat, v_fries_cat, 1);
        END IF;
        
        IF v_drink_cat IS NOT NULL THEN
            INSERT IGNORE INTO cross_sell_rules (company_id, source_category_id, target_category_id, priority)
            VALUES (p_company_id, v_hamburger_cat, v_drink_cat, 2);
        END IF;
    END IF;
    
    -- Regras: Batata → Hambúrguer + Bebida
    IF v_fries_cat IS NOT NULL THEN
        IF v_hamburger_cat IS NOT NULL THEN
            INSERT IGNORE INTO cross_sell_rules (company_id, source_category_id, target_category_id, priority)
            VALUES (p_company_id, v_fries_cat, v_hamburger_cat, 1);
        END IF;
        
        IF v_drink_cat IS NOT NULL THEN
            INSERT IGNORE INTO cross_sell_rules (company_id, source_category_id, target_category_id, priority)
            VALUES (p_company_id, v_fries_cat, v_drink_cat, 2);
        END IF;
    END IF;
    
    -- Regras: Bebida → Hambúrguer + Batata
    IF v_drink_cat IS NOT NULL THEN
        IF v_hamburger_cat IS NOT NULL THEN
            INSERT IGNORE INTO cross_sell_rules (company_id, source_category_id, target_category_id, priority)
            VALUES (p_company_id, v_drink_cat, v_hamburger_cat, 1);
        END IF;
        
        IF v_fries_cat IS NOT NULL THEN
            INSERT IGNORE INTO cross_sell_rules (company_id, source_category_id, target_category_id, priority)
            VALUES (p_company_id, v_drink_cat, v_fries_cat, 2);
        END IF;
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- Comentários
-- ============================================================================

ALTER TABLE cross_sell_config 
COMMENT = 'Configuração do sistema de cross-sell inteligente por empresa';

ALTER TABLE cross_sell_rules 
COMMENT = 'Regras fixas de cross-sell: qual categoria sugerir baseado na categoria visualizada';

ALTER TABLE category_types 
COMMENT = 'Mapeamento de categorias para tipos conhecidos (hamburger, fries, drink, etc)';
