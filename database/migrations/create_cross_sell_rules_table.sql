-- Criação da tabela de regras de cross-sell manuais
-- Permite configurar recomendações de produtos na seção "Adicione também"

CREATE TABLE IF NOT EXISTS cross_sell_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    product_id INT NOT NULL COMMENT 'Produto que está sendo visualizado',
    recommended_product_id INT NOT NULL COMMENT 'Produto que será recomendado',
    priority INT NOT NULL DEFAULT 50 COMMENT 'Prioridade da recomendação (maior = mais importante)',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se a regra está ativa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_company_product (company_id, product_id),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority),
    
    -- Foreign keys
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (recommended_product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    -- Constraint: não permitir que um produto recomende a si mesmo
    CONSTRAINT chk_different_products CHECK (product_id != recommended_product_id),
    
    -- Constraint: evitar duplicação (mesmo par de produtos)
    UNIQUE KEY unique_product_recommendation (company_id, product_id, recommended_product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Regras manuais de cross-sell para recomendações de produtos';
