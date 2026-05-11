-- ============================================================================
-- Sales AI Admin - Tabelas para Painel Administrativo Completo
-- ============================================================================

-- Tabela de regras manuais de cross-sell (configurável pelo admin)
CREATE TABLE IF NOT EXISTS cross_sell_rules_manual (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    source_category_id INT NOT NULL COMMENT 'Categoria do produto visualizado',
    target_category_id INT NOT NULL COMMENT 'Categoria sugerida',
    max_items INT DEFAULT 2 COMMENT 'Máximo de itens desta categoria',
    fixed_order TINYINT(1) DEFAULT 0 COMMENT 'Ordem fixa ou aleatória',
    priority INT DEFAULT 0 COMMENT 'Prioridade admin (0-10)',
    ai_enabled TINYINT(1) DEFAULT 1 COMMENT 'Usar IA para ordenar',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_source_target (company_id, source_category_id, target_category_id),
    INDEX idx_company (company_id),
    INDEX idx_source (source_category_id),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (source_category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (target_category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações avançadas da IA (por empresa)
CREATE TABLE IF NOT EXISTS ai_configuration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    
    -- Modo da IA
    ai_mode ENUM('personalized', 'popularity', 'collaborative') DEFAULT 'personalized',
    
    -- Pesos dos componentes (0.0 a 1.0)
    weight_personal DECIMAL(3,2) DEFAULT 0.40 COMMENT 'Preferências pessoais',
    weight_collaborative DECIMAL(3,2) DEFAULT 0.40 COMMENT 'Filtro colaborativo',
    weight_popularity DECIMAL(3,2) DEFAULT 0.20 COMMENT 'Popularidade global',
    
    -- Pesos adicionais
    weight_margin DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Priorizar por margem de lucro',
    weight_promotion DECIMAL(3,2) DEFAULT 0.15 COMMENT 'Boost para promoções',
    weight_new_items DECIMAL(3,2) DEFAULT 0.05 COMMENT 'Boost para lançamentos',
    
    -- Configurações de comportamento
    time_based_recommendations TINYINT(1) DEFAULT 1 COMMENT 'Recomendações por horário',
    max_items_per_block INT DEFAULT 3 COMMENT 'Máximo de itens por bloco',
    exploration_rate DECIMAL(3,2) DEFAULT 0.10 COMMENT 'Taxa de exploração (novos produtos)',
    
    -- Filtros
    ignore_low_stock TINYINT(1) DEFAULT 1 COMMENT 'Ignorar produtos com estoque baixo',
    require_image TINYINT(1) DEFAULT 0 COMMENT 'Só recomendar produtos com imagem',
    min_margin_percent DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Margem mínima para recomendar',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Métricas de conversão agregadas (cache para dashboard)
CREATE TABLE IF NOT EXISTS cross_sell_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    metric_date DATE NOT NULL,
    
    -- Métricas gerais
    total_impressions INT DEFAULT 0 COMMENT 'Quantas vezes apareceu',
    total_clicks INT DEFAULT 0 COMMENT 'Quantas vezes clicaram',
    total_purchases INT DEFAULT 0 COMMENT 'Quantas vezes compraram',
    
    -- Taxas
    click_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'CTR (%)',
    conversion_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Conversão (%)',
    
    -- Financeiro
    total_revenue DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Receita gerada',
    avg_ticket DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Ticket médio',
    avg_ticket_with_ai DECIMAL(10,2) DEFAULT 0.00,
    avg_ticket_without_ai DECIMAL(10,2) DEFAULT 0.00,
    
    -- Influência da IA
    ai_influenced_purchases INT DEFAULT 0 COMMENT 'Compras influenciadas pela IA',
    ai_influence_percent DECIMAL(5,2) DEFAULT 0.00,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_company_date (company_id, metric_date),
    INDEX idx_date (metric_date),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Desempenho por produto (quais produtos funcionam melhor)
CREATE TABLE IF NOT EXISTS product_recommendation_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    product_id INT NOT NULL,
    
    -- Métricas acumuladas
    times_recommended INT DEFAULT 0 COMMENT 'Quantas vezes foi recomendado',
    times_clicked INT DEFAULT 0 COMMENT 'Quantas vezes clicaram',
    times_purchased INT DEFAULT 0 COMMENT 'Quantas vezes compraram',
    
    -- Taxas
    click_rate DECIMAL(5,2) DEFAULT 0.00,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    
    -- Receita
    total_revenue DECIMAL(10,2) DEFAULT 0.00,
    avg_price DECIMAL(10,2) DEFAULT 0.00,
    
    -- Contexto
    best_hour_of_day INT DEFAULT NULL COMMENT 'Horário de melhor performance',
    best_paired_with INT DEFAULT NULL COMMENT 'Produto com melhor sinergia',
    
    last_recommended_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_company_product (company_id, product_id),
    INDEX idx_conversion (conversion_rate DESC),
    INDEX idx_revenue (total_revenue DESC),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (best_paired_with) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Combinações mais populares (combos formados)
CREATE TABLE IF NOT EXISTS popular_combinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    product_a_id INT NOT NULL,
    product_b_id INT NOT NULL,
    product_c_id INT DEFAULT NULL COMMENT 'Terceiro produto (opcional)',
    
    -- Estatísticas
    times_ordered_together INT DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0.00,
    avg_combo_value DECIMAL(10,2) DEFAULT 0.00,
    
    -- Performance
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    recommendation_score DECIMAL(10,4) DEFAULT 0.0000,
    
    first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_combo (company_id, product_a_id, product_b_id, product_c_id),
    INDEX idx_company (company_id),
    INDEX idx_times_ordered (times_ordered_together DESC),
    INDEX idx_revenue (total_revenue DESC),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_a_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_b_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_c_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs detalhados para auditoria (mantém últimos 90 dias)
CREATE TABLE IF NOT EXISTS recommendation_audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    session_id VARCHAR(100) DEFAULT NULL,
    
    -- Contexto
    product_viewed_id INT NOT NULL COMMENT 'Produto que estava visualizando',
    product_recommended_id INT NOT NULL COMMENT 'Produto recomendado',
    block_key VARCHAR(50) DEFAULT NULL COMMENT 'Bloco de cross-sell',
    
    -- Ação
    action_type ENUM('impression', 'click', 'add_to_cart', 'purchase') NOT NULL,
    
    -- Metadados
    recommendation_score DECIMAL(10,4) DEFAULT NULL COMMENT 'Score da IA',
    position_in_list INT DEFAULT NULL COMMENT 'Posição na lista (1, 2, 3...)',
    ai_method VARCHAR(50) DEFAULT NULL COMMENT 'Método usado (personal/collab/popular)',
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_company (company_id),
    INDEX idx_customer (customer_id),
    INDEX idx_viewed (product_viewed_id),
    INDEX idx_recommended (product_recommended_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (product_viewed_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_recommended_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A/B Testing (experimentos)
CREATE TABLE IF NOT EXISTS ab_test_experiments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Configuração
    variant_a_config JSON COMMENT 'Configuração da variante A',
    variant_b_config JSON COMMENT 'Configuração da variante B',
    
    -- Divisão de tráfego
    traffic_split DECIMAL(3,2) DEFAULT 0.50 COMMENT '50% = metade A, metade B',
    
    -- Status
    status ENUM('draft', 'running', 'paused', 'completed') DEFAULT 'draft',
    
    -- Resultados
    variant_a_conversions INT DEFAULT 0,
    variant_a_revenue DECIMAL(10,2) DEFAULT 0.00,
    variant_b_conversions INT DEFAULT 0,
    variant_b_revenue DECIMAL(10,2) DEFAULT 0.00,
    
    winner VARCHAR(10) DEFAULT NULL COMMENT 'A ou B',
    confidence_level DECIMAL(5,2) DEFAULT NULL COMMENT 'Nível de confiança (%)',
    
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_company (company_id),
    INDEX idx_status (status),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Triggers para atualização automática de métricas
-- ============================================================================

DELIMITER $$

-- Atualizar performance quando houver nova interação
CREATE TRIGGER after_audit_log_insert
AFTER INSERT ON recommendation_audit_log
FOR EACH ROW
BEGIN
    -- Atualizar performance do produto
    INSERT INTO product_recommendation_performance 
        (company_id, product_id, times_recommended, times_clicked, times_purchased)
    VALUES (
        NEW.company_id,
        NEW.product_recommended_id,
        CASE WHEN NEW.action_type = 'impression' THEN 1 ELSE 0 END,
        CASE WHEN NEW.action_type = 'click' THEN 1 ELSE 0 END,
        CASE WHEN NEW.action_type = 'purchase' THEN 1 ELSE 0 END
    )
    ON DUPLICATE KEY UPDATE
        times_recommended = times_recommended + CASE WHEN NEW.action_type = 'impression' THEN 1 ELSE 0 END,
        times_clicked = times_clicked + CASE WHEN NEW.action_type = 'click' THEN 1 ELSE 0 END,
        times_purchased = times_purchased + CASE WHEN NEW.action_type = 'purchase' THEN 1 ELSE 0 END,
        click_rate = (times_clicked / NULLIF(times_recommended, 0)) * 100,
        conversion_rate = (times_purchased / NULLIF(times_recommended, 0)) * 100,
        last_recommended_at = NEW.created_at;
        
    -- Atualizar métricas diárias
    INSERT INTO cross_sell_metrics 
        (company_id, metric_date, total_impressions, total_clicks, total_purchases)
    VALUES (
        NEW.company_id,
        CURDATE(),
        CASE WHEN NEW.action_type = 'impression' THEN 1 ELSE 0 END,
        CASE WHEN NEW.action_type = 'click' THEN 1 ELSE 0 END,
        CASE WHEN NEW.action_type = 'purchase' THEN 1 ELSE 0 END
    )
    ON DUPLICATE KEY UPDATE
        total_impressions = total_impressions + CASE WHEN NEW.action_type = 'impression' THEN 1 ELSE 0 END,
        total_clicks = total_clicks + CASE WHEN NEW.action_type = 'click' THEN 1 ELSE 0 END,
        total_purchases = total_purchases + CASE WHEN NEW.action_type = 'purchase' THEN 1 ELSE 0 END,
        click_rate = (total_clicks / NULLIF(total_impressions, 0)) * 100,
        conversion_rate = (total_purchases / NULLIF(total_impressions, 0)) * 100;
END$$

DELIMITER ;

-- ============================================================================
-- Inserir configuração padrão para empresas existentes
-- ============================================================================

INSERT INTO ai_configuration (company_id)
SELECT id FROM companies
WHERE id NOT IN (SELECT company_id FROM ai_configuration);

-- ============================================================================
-- Índices adicionais para performance
-- ============================================================================

-- Para queries de dashboard
ALTER TABLE recommendation_audit_log
ADD INDEX idx_dashboard (company_id, created_at, action_type);

-- Para relatórios de horário
ALTER TABLE recommendation_audit_log
ADD INDEX idx_hourly (company_id, HOUR(created_at), action_type);

-- ============================================================================
-- Comentários nas tabelas
-- ============================================================================

ALTER TABLE cross_sell_rules_manual 
COMMENT = 'Regras manuais configuradas pelo admin para cross-sell';

ALTER TABLE ai_configuration 
COMMENT = 'Configurações avançadas da IA por empresa';

ALTER TABLE cross_sell_metrics 
COMMENT = 'Métricas agregadas diárias para dashboard';

ALTER TABLE product_recommendation_performance 
COMMENT = 'Performance individual de cada produto nas recomendações';

ALTER TABLE popular_combinations 
COMMENT = 'Combinações/combos mais populares formados pelos clientes';

ALTER TABLE recommendation_audit_log 
COMMENT = 'Log detalhado de todas as interações com recomendações';

ALTER TABLE ab_test_experiments 
COMMENT = 'Experimentos A/B para testar diferentes configurações';

COMMIT;
