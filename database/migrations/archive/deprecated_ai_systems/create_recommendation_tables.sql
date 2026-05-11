-- ============================================================================
-- Sistema de Recomendação Inteligente de Produtos
-- ============================================================================

-- Tabela para tracking de interações cliente-produto
CREATE TABLE IF NOT EXISTS customer_product_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    customer_id INT DEFAULT NULL,           -- NULL = cliente anônimo
    session_id VARCHAR(100) DEFAULT NULL,   -- Para tracking de anônimos
    product_id INT NOT NULL,
    event_type ENUM('view', 'add_to_cart', 'purchase') NOT NULL,
    event_weight DECIMAL(3,1) NOT NULL DEFAULT 1.0,  -- view=1.0, cart=3.0, purchase=5.0
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_company_customer (company_id, customer_id),
    INDEX idx_company_session (company_id, session_id),
    INDEX idx_product (product_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de scores de afinidade entre produtos (aprendizado colaborativo)
CREATE TABLE IF NOT EXISTS product_affinity_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    product_a_id INT NOT NULL,              -- Produto principal
    product_b_id INT NOT NULL,              -- Produto relacionado
    affinity_score DECIMAL(10,4) NOT NULL DEFAULT 0.0000,  -- Score calculado
    co_occurrence_count INT DEFAULT 0,      -- Quantas vezes foram comprados juntos
    last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_pair (company_id, product_a_id, product_b_id),
    INDEX idx_product_a (product_a_id, affinity_score DESC),
    INDEX idx_company (company_id),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_a_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_b_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de scores de popularidade global por produto
CREATE TABLE IF NOT EXISTS product_popularity_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    product_id INT NOT NULL,
    view_count INT DEFAULT 0,
    cart_count INT DEFAULT 0,
    purchase_count INT DEFAULT 0,
    popularity_score DECIMAL(10,4) NOT NULL DEFAULT 0.0000,  -- Score calculado
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_product (company_id, product_id),
    INDEX idx_company_score (company_id, popularity_score DESC),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de preferências pessoais calculadas (cache)
CREATE TABLE IF NOT EXISTS customer_product_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    preference_score DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    total_views INT DEFAULT 0,
    total_cart_adds INT DEFAULT 0,
    total_purchases INT DEFAULT 0,
    last_interaction_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_customer_product (company_id, customer_id, product_id),
    INDEX idx_customer_score (customer_id, preference_score DESC),
    INDEX idx_company (company_id),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Triggers para atualização automática de scores
-- ============================================================================

-- Trigger para atualizar popularidade quando houver interação
DELIMITER $$

CREATE TRIGGER after_interaction_insert
AFTER INSERT ON customer_product_interactions
FOR EACH ROW
BEGIN
    -- Atualizar ou criar registro de popularidade
    INSERT INTO product_popularity_scores (company_id, product_id, view_count, cart_count, purchase_count, popularity_score)
    VALUES (
        NEW.company_id,
        NEW.product_id,
        IF(NEW.event_type = 'view', 1, 0),
        IF(NEW.event_type = 'add_to_cart', 1, 0),
        IF(NEW.event_type = 'purchase', 1, 0),
        NEW.event_weight
    )
    ON DUPLICATE KEY UPDATE
        view_count = view_count + IF(NEW.event_type = 'view', 1, 0),
        cart_count = cart_count + IF(NEW.event_type = 'add_to_cart', 1, 0),
        purchase_count = purchase_count + IF(NEW.event_type = 'purchase', 1, 0),
        popularity_score = popularity_score + NEW.event_weight;
    
    -- Se tem customer_id, atualizar preferências pessoais
    IF NEW.customer_id IS NOT NULL THEN
        INSERT INTO customer_product_preferences (company_id, customer_id, product_id, preference_score, total_views, total_cart_adds, total_purchases)
        VALUES (
            NEW.company_id,
            NEW.customer_id,
            NEW.product_id,
            NEW.event_weight,
            IF(NEW.event_type = 'view', 1, 0),
            IF(NEW.event_type = 'add_to_cart', 1, 0),
            IF(NEW.event_type = 'purchase', 1, 0)
        )
        ON DUPLICATE KEY UPDATE
            preference_score = preference_score + NEW.event_weight,
            total_views = total_views + IF(NEW.event_type = 'view', 1, 0),
            total_cart_adds = total_cart_adds + IF(NEW.event_type = 'add_to_cart', 1, 0),
            total_purchases = total_purchases + IF(NEW.event_type = 'purchase', 1, 0);
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- Índices adicionais para performance
-- ============================================================================

-- Para queries de "produtos frequentemente comprados juntos"
ALTER TABLE customer_product_interactions 
ADD INDEX idx_purchase_lookup (company_id, customer_id, event_type, product_id);

-- Para queries de "top produtos por popularidade"
ALTER TABLE product_popularity_scores
ADD INDEX idx_top_products (company_id, popularity_score DESC, product_id);

-- ============================================================================
-- Comentários nas tabelas
-- ============================================================================

ALTER TABLE customer_product_interactions 
COMMENT = 'Tracking de todas as interações cliente-produto para machine learning';

ALTER TABLE product_affinity_scores 
COMMENT = 'Scores de afinidade entre produtos (filtro colaborativo item-based)';

ALTER TABLE product_popularity_scores 
COMMENT = 'Scores de popularidade global por produto';

ALTER TABLE customer_product_preferences 
COMMENT = 'Cache de preferências pessoais calculadas por cliente';
