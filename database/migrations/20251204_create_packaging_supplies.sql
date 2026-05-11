-- Migration: Criar tabela de insumos (embalagens) e vínculo com produtos
-- Data: 2024-12-04

-- Tabela de insumos/embalagens
CREATE TABLE IF NOT EXISTS packaging_supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    unit VARCHAR(20) DEFAULT 'un' COMMENT 'un, cx, kg, etc.',
    cost_per_unit DECIMAL(10,4) NOT NULL DEFAULT 0,
    stock_quantity DECIMAL(10,2) DEFAULT 0,
    min_stock_alert DECIMAL(10,2) DEFAULT 0,
    supplier VARCHAR(255) NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_packaging_company (company_id),
    INDEX idx_packaging_active (active),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de vínculo: embalagens do produto
CREATE TABLE IF NOT EXISTS product_packaging (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    supply_id INT NOT NULL,
    quantity DECIMAL(10,4) NOT NULL DEFAULT 1 COMMENT 'Quantidade usada por unidade do produto',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_product_supply (product_id, supply_id),
    INDEX idx_pp_product (product_id),
    INDEX idx_pp_supply (supply_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (supply_id) REFERENCES packaging_supplies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alguns exemplos de unidades comuns
-- un = unidade
-- cx = caixa
-- pct = pacote
-- rolo = rolo
-- kg = quilograma
-- g = grama
-- ml = mililitro
-- l = litro
