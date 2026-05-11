-- ============================================================================
-- Sales Intelligence System - Sistema Completo de IA de Vendas
-- ============================================================================
-- Sistema que aprende comportamento de cada cliente e otimiza toda experiência
-- para maximizar vendas através de psicologia comportamental e neuromarketing
-- ============================================================================

USE multi_menu;

-- 1. PERFIS COMPORTAMENTAIS DE CLIENTES
-- Segmentação automática baseada em padrões de comportamento
CREATE TABLE IF NOT EXISTS customer_behavior_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    customer_id INT NULL, -- NULL para sessões anônimas
    session_id VARCHAR(100) NULL,
    
    -- Segmentação Automática (calculada por IA)
    profile_type ENUM(
        'impulsive',      -- Compra rápido, sem pesquisar muito
        'planner',        -- Pesquisa muito, adiciona ao carrinho aos poucos
        'economical',     -- Sensível a preço, busca promoções
        'premium',        -- Prefere produtos caros, qualidade
        'explorer',       -- Navega muito, experimenta novos produtos
        'loyal',          -- Sempre compra os mesmos produtos
        'undefined'       -- Ainda coletando dados
    ) DEFAULT 'undefined',
    
    profile_confidence DECIMAL(3,2) DEFAULT 0.00, -- 0.00 a 1.00
    
    -- Padrões de Navegação
    avg_session_duration INT DEFAULT 0, -- segundos
    avg_products_viewed INT DEFAULT 0,
    avg_categories_explored INT DEFAULT 0,
    scroll_depth_avg DECIMAL(5,2) DEFAULT 0.00, -- % da página
    
    -- Padrões de Compra
    total_orders INT DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0.00,
    avg_order_value DECIMAL(10,2) DEFAULT 0.00,
    avg_items_per_order DECIMAL(5,2) DEFAULT 0.00,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    
    -- Comportamento de Decisão
    avg_time_to_decision INT DEFAULT 0, -- segundos até adicionar ao carrinho
    cart_abandonment_rate DECIMAL(5,2) DEFAULT 0.00,
    price_sensitivity_score DECIMAL(3,2) DEFAULT 0.50, -- 0=não liga, 1=muito sensível
    
    -- Preferências
    favorite_categories JSON NULL, -- ['id' => count]
    favorite_products JSON NULL,
    preferred_time_of_day ENUM('morning', 'afternoon', 'evening', 'night') NULL,
    preferred_day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NULL,
    
    -- Triggers de Venda Efetivos (o que converte este cliente)
    responds_to_urgency BOOLEAN DEFAULT FALSE, -- "Só hoje!"
    responds_to_scarcity BOOLEAN DEFAULT FALSE, -- "Últimas unidades!"
    responds_to_social_proof BOOLEAN DEFAULT FALSE, -- "Mais vendido!"
    responds_to_discount BOOLEAN DEFAULT FALSE, -- "50% OFF"
    responds_to_premium BOOLEAN DEFAULT FALSE, -- "Premium", "Exclusivo"
    responds_to_combo BOOLEAN DEFAULT FALSE, -- Ofertas de combo
    
    -- Metadados
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_order_at TIMESTAMP NULL,
    first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_customer (company_id, customer_id),
    INDEX idx_session (company_id, session_id),
    INDEX idx_profile (company_id, profile_type),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. EVENTOS COMPORTAMENTAIS (tracking granular)
CREATE TABLE IF NOT EXISTS behavior_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    customer_id INT NULL,
    session_id VARCHAR(100) NULL,
    
    event_type ENUM(
        'page_view',
        'category_view',
        'product_view',
        'product_hover',
        'scroll',
        'add_to_cart',
        'remove_from_cart',
        'cart_view',
        'checkout_start',
        'checkout_abandon',
        'order_complete',
        'search',
        'filter_use',
        'hesitation' -- mouse parado em produto por >3s
    ) NOT NULL,
    
    target_type VARCHAR(50) NULL, -- 'product', 'category', 'banner'
    target_id INT NULL,
    
    metadata JSON NULL, -- dados específicos do evento
    
    -- Contexto
    device_type ENUM('mobile', 'tablet', 'desktop') NULL,
    page_url VARCHAR(500) NULL,
    referrer VARCHAR(500) NULL,
    
    duration INT NULL, -- duração em segundos (para eventos que têm duração)
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_company_date (company_id, created_at),
    INDEX idx_customer (customer_id, created_at),
    INDEX idx_session (session_id, created_at),
    INDEX idx_event_type (event_type, created_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. LAYOUTS INTELIGENTES (configurações dinâmicas)
CREATE TABLE IF NOT EXISTS intelligent_layouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    
    -- Identificação
    layout_name VARCHAR(100) NOT NULL, -- 'home_default', 'home_impulsive', etc
    profile_type VARCHAR(50) NULL, -- NULL = default, ou 'impulsive', 'planner', etc
    
    -- Configuração de Categorias
    category_order JSON NOT NULL, -- [id1, id2, id3...] ordem de exibição
    featured_categories JSON NULL, -- IDs de categorias em destaque
    
    -- Configuração de Produtos
    featured_products JSON NULL, -- IDs de produtos em destaque no topo
    product_sort_strategy ENUM(
        'popularity',      -- Mais vendidos primeiro
        'newest',          -- Mais novos primeiro
        'price_asc',       -- Menor preço primeiro
        'price_desc',      -- Maior preço primeiro
        'personalized',    -- IA decide por cliente
        'conversion_rate'  -- Maior taxa de conversão primeiro
    ) DEFAULT 'personalized',
    
    -- Estratégias de Venda
    show_urgency_badges BOOLEAN DEFAULT FALSE, -- "Só hoje!"
    show_scarcity_badges BOOLEAN DEFAULT FALSE, -- "Últimas 3 unidades"
    show_social_proof BOOLEAN DEFAULT FALSE, -- "127 pessoas compraram hoje"
    show_discount_badges BOOLEAN DEFAULT TRUE,
    show_combo_suggestions BOOLEAN DEFAULT TRUE,
    
    -- Hierarquia Visual (tamanhos relativos)
    hero_product_size ENUM('small', 'medium', 'large', 'xlarge') DEFAULT 'large',
    category_card_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
    
    -- Ancoragem de Preços
    show_expensive_first BOOLEAN DEFAULT FALSE, -- Mostrar produto caro primeiro (ancoragem)
    price_comparison_enabled BOOLEAN DEFAULT TRUE, -- Mostrar "de R$ X por R$ Y"
    
    -- Performance (tracking A/B)
    impressions INT DEFAULT 0,
    conversions INT DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_order_value DECIMAL(10,2) DEFAULT 0.00,
    
    -- Metadados
    is_active BOOLEAN DEFAULT TRUE,
    created_by_ai BOOLEAN DEFAULT FALSE, -- criado por auto-otimização
    ai_confidence DECIMAL(3,2) DEFAULT 0.00,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_company_profile (company_id, profile_type, is_active),
    UNIQUE KEY unique_company_profile (company_id, profile_type),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ESTRATÉGIAS DE VENDA POR CATEGORIA
CREATE TABLE IF NOT EXISTS sales_strategies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    category_id INT NOT NULL,
    
    -- Estratégia Aplicada
    strategy_type ENUM(
        'upsell',         -- Oferecer produto mais caro
        'cross_sell',     -- Oferecer complementar
        'bundle',         -- Combo com desconto
        'scarcity',       -- Escassez artificial
        'urgency',        -- Urgência temporal
        'social_proof',   -- Prova social
        'premium'         -- Destacar qualidade premium
    ) NOT NULL,
    
    -- Configuração
    trigger_conditions JSON NULL, -- quando ativar esta estratégia
    message_template VARCHAR(500) NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Performance
    times_triggered INT DEFAULT 0,
    conversions INT DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    revenue_generated DECIMAL(10,2) DEFAULT 0.00,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_company_category (company_id, category_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. APRENDIZADO HÍBRIDO (IA externa ensina IA interna)
CREATE TABLE IF NOT EXISTS hybrid_learning_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    
    -- Sessão de Aprendizado
    session_type ENUM(
        'behavior_analysis',    -- Analisar comportamento de clientes
        'layout_optimization',  -- Otimizar layouts
        'strategy_tuning',      -- Ajustar estratégias
        'prediction_training',  -- Treinar modelo preditivo
        'full_optimization'     -- Otimização completa
    ) NOT NULL,
    
    -- Dados de Entrada
    input_data_summary JSON NULL, -- resumo dos dados analisados
    
    -- Resultado da IA Externa
    ai_provider VARCHAR(50) NULL, -- 'openai', 'gemini'
    ai_recommendations JSON NULL, -- recomendações da IA externa
    ai_confidence DECIMAL(3,2) DEFAULT 0.00,
    
    -- Aplicação na IA Interna
    changes_applied JSON NULL, -- mudanças aplicadas no sistema
    
    -- Performance Antes/Depois
    metrics_before JSON NULL,
    metrics_after JSON NULL,
    improvement_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Metadados
    duration_seconds INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_company_type (company_id, session_type, created_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. MÉTRICAS DE CONVERSÃO DETALHADAS
CREATE TABLE IF NOT EXISTS conversion_metrics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    metric_date DATE NOT NULL,
    
    -- Métricas Gerais
    total_visitors INT DEFAULT 0,
    total_sessions INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0.00,
    
    -- Conversão por Etapa
    visitors_with_view INT DEFAULT 0,
    visitors_with_cart INT DEFAULT 0,
    visitors_with_checkout INT DEFAULT 0,
    visitors_with_order INT DEFAULT 0,
    
    -- Taxas
    view_to_cart_rate DECIMAL(5,2) DEFAULT 0.00,
    cart_to_checkout_rate DECIMAL(5,2) DEFAULT 0.00,
    checkout_to_order_rate DECIMAL(5,2) DEFAULT 0.00,
    overall_conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    
    -- Comportamento
    avg_session_duration INT DEFAULT 0,
    avg_products_viewed DECIMAL(5,2) DEFAULT 0.00,
    avg_order_value DECIMAL(10,2) DEFAULT 0.00,
    
    -- Por Perfil
    metrics_by_profile JSON NULL, -- conversão separada por perfil
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_company_date (company_id, metric_date),
    INDEX idx_company (company_id, metric_date),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DADOS INICIAIS
-- ============================================================================

-- Layout padrão para empresas existentes
INSERT INTO intelligent_layouts (company_id, layout_name, profile_type, category_order, product_sort_strategy, is_active)
SELECT 
    id as company_id,
    'home_default' as layout_name,
    NULL as profile_type,
    JSON_ARRAY() as category_order,
    'personalized' as product_sort_strategy,
    TRUE as is_active
FROM companies
WHERE NOT EXISTS (
    SELECT 1 FROM intelligent_layouts WHERE intelligent_layouts.company_id = companies.id
);
