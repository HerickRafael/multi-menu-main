-- Migration: Create Financial Management Tables
-- Date: 2025-12-03
-- Description: Tabelas para o painel completo de gestão financeira

-- ============================================
-- 1. Categorias de Despesas
-- ============================================
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('fixed', 'variable') NOT NULL DEFAULT 'fixed',
  `description` VARCHAR(255) NULL,
  `color` VARCHAR(7) DEFAULT '#3B82F6',
  `icon` VARCHAR(50) DEFAULT 'currency-dollar',
  `is_system` TINYINT(1) DEFAULT 0 COMMENT 'Categorias padrão do sistema',
  `active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_company_type` (`company_id`, `type`),
  INDEX `idx_active` (`company_id`, `active`),
  CONSTRAINT `fk_expense_categories_company` 
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================
-- 2. Despesas (Fixas e Variáveis)
-- ============================================
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `category_id` INT NULL,
  `description` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `reference_month` CHAR(7) NOT NULL COMMENT 'YYYY-MM para agrupamento mensal',
  `is_recurring` TINYINT(1) DEFAULT 0,
  `recurrence_type` ENUM('monthly', 'yearly') NULL,
  `payment_method` VARCHAR(50) NULL,
  `notes` TEXT NULL,
  `attachment_path` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_company_date` (`company_id`, `expense_date`),
  INDEX `idx_company_month` (`company_id`, `reference_month`),
  INDEX `idx_category` (`category_id`),
  CONSTRAINT `fk_expenses_company` 
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_expenses_category` 
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================
-- 3. Custos Adicionais por Produto
-- ============================================
CREATE TABLE IF NOT EXISTS `product_additional_costs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `product_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  
  -- Custos de Embalagem
  `packaging_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Custo de embalagem (saco kraft, caixa, etc)',
  `packaging_description` VARCHAR(100) NULL,
  
  -- Custos de Mão de Obra
  `labor_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Custo de mão de obra por unidade',
  `labor_minutes` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Tempo de preparo em minutos',
  
  -- Custos de Desperdício
  `waste_percentage` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Percentual de desperdício estimado',
  
  -- Impostos
  `tax_percentage` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Percentual de impostos',
  
  -- Taxas de Plataforma
  `platform_fee_percentage` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Taxa média de plataforma (iFood, etc)',
  
  -- Outros Custos
  `other_costs` DECIMAL(10,2) DEFAULT 0.00,
  `other_costs_description` VARCHAR(255) NULL,
  
  -- Custo Total Calculado (cache)
  `total_additional_cost` DECIMAL(10,2) GENERATED ALWAYS AS (
    packaging_cost + labor_cost + other_costs
  ) STORED,
  
  `notes` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_product` (`product_id`),
  INDEX `idx_company` (`company_id`),
  CONSTRAINT `fk_product_costs_product` 
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_costs_company` 
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================
-- 4. Snapshot de Custo por Produto (Cache)
-- ============================================
CREATE TABLE IF NOT EXISTS `product_cost_snapshots` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `product_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  
  -- Custos Detalhados
  `ingredient_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Custo total dos ingredientes base',
  `packaging_cost` DECIMAL(10,2) DEFAULT 0.00,
  `labor_cost` DECIMAL(10,2) DEFAULT 0.00,
  `waste_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Custo do desperdício',
  `other_costs` DECIMAL(10,2) DEFAULT 0.00,
  
  -- Totais
  `production_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Custo de produção (ingredientes + embalagem + mão de obra + desperdício)',
  `tax_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Valor dos impostos',
  `platform_fee_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Valor da taxa de plataforma',
  `total_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Custo total (produção + impostos + taxas)',
  
  -- Preço e Margem
  `sale_price` DECIMAL(10,2) DEFAULT 0.00,
  `profit` DECIMAL(10,2) DEFAULT 0.00,
  `profit_margin` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Margem de lucro em %',
  
  -- Metadados
  `ingredients_detail` JSON NULL COMMENT 'Detalhamento dos ingredientes',
  `calculated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_product` (`product_id`),
  INDEX `idx_company` (`company_id`),
  INDEX `idx_profit_margin` (`company_id`, `profit_margin`),
  CONSTRAINT `fk_cost_snapshots_product` 
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cost_snapshots_company` 
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================
-- 5. Custo Registrado por Item de Venda
-- ============================================
CREATE TABLE IF NOT EXISTS `order_item_costs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_item_id` INT NOT NULL,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  
  -- Custos no momento da venda
  `base_ingredient_cost` DECIMAL(10,2) DEFAULT 0.00,
  `customization_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Custo dos adicionais/personalizações',
  `packaging_cost` DECIMAL(10,2) DEFAULT 0.00,
  `labor_cost` DECIMAL(10,2) DEFAULT 0.00,
  `waste_cost` DECIMAL(10,2) DEFAULT 0.00,
  `other_costs` DECIMAL(10,2) DEFAULT 0.00,
  
  -- Totais
  `production_cost` DECIMAL(10,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
  `platform_fee_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_cost` DECIMAL(10,2) DEFAULT 0.00,
  
  -- Venda e Lucro
  `quantity` INT DEFAULT 1,
  `unit_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Preço unitário vendido',
  `line_total` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total da linha (qtd x preço)',
  `unit_profit` DECIMAL(10,2) DEFAULT 0.00,
  `total_profit` DECIMAL(10,2) DEFAULT 0.00,
  `profit_margin` DECIMAL(5,2) DEFAULT 0.00,
  
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_item` (`order_item_id`),
  INDEX `idx_order` (`order_id`),
  INDEX `idx_product` (`product_id`),
  INDEX `idx_company_created` (`company_id`, `created_at`),
  CONSTRAINT `fk_order_item_costs_item` 
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_item_costs_order` 
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_item_costs_product` 
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_item_costs_company` 
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================
-- 6. Resumo Financeiro Diário (Cache)
-- ============================================
CREATE TABLE IF NOT EXISTS `financial_daily_summary` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `summary_date` DATE NOT NULL,
  `reference_month` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  
  -- Vendas
  `total_orders` INT DEFAULT 0,
  `completed_orders` INT DEFAULT 0,
  `cancelled_orders` INT DEFAULT 0,
  `total_items_sold` INT DEFAULT 0,
  
  -- Receitas
  `gross_revenue` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Faturamento bruto',
  `net_revenue` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Faturamento líquido (- descontos)',
  `delivery_fees` DECIMAL(10,2) DEFAULT 0.00,
  `discounts_given` DECIMAL(10,2) DEFAULT 0.00,
  `cancelled_value` DECIMAL(10,2) DEFAULT 0.00,
  
  -- Custos
  `total_production_cost` DECIMAL(12,2) DEFAULT 0.00,
  `total_ingredient_cost` DECIMAL(12,2) DEFAULT 0.00,
  `total_packaging_cost` DECIMAL(10,2) DEFAULT 0.00,
  `total_labor_cost` DECIMAL(10,2) DEFAULT 0.00,
  `total_tax_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_platform_fees` DECIMAL(10,2) DEFAULT 0.00,
  
  -- Lucros
  `gross_profit` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Lucro bruto (receita - custo produção)',
  `operating_profit` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Lucro operacional (- impostos - taxas)',
  
  -- Métricas
  `avg_ticket` DECIMAL(10,2) DEFAULT 0.00,
  `avg_profit_margin` DECIMAL(5,2) DEFAULT 0.00,
  
  -- Metadados
  `last_calculated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_date` (`company_id`, `summary_date`),
  INDEX `idx_month` (`company_id`, `reference_month`),
  CONSTRAINT `fk_daily_summary_company` 
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================
-- 7. Configurações Financeiras da Empresa
-- ============================================
CREATE TABLE IF NOT EXISTS `financial_settings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  
  -- Impostos Padrão
  `default_tax_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `tax_regime` VARCHAR(50) NULL COMMENT 'Simples Nacional, Lucro Presumido, etc',
  
  -- Taxas de Plataforma
  `ifood_fee_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `rappi_fee_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `ubereats_fee_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `own_delivery_fee_percentage` DECIMAL(5,2) DEFAULT 0.00,
  
  -- Mão de Obra
  `hourly_labor_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Custo hora de mão de obra',
  
  -- Margem Desejada
  `target_profit_margin` DECIMAL(5,2) DEFAULT 30.00 COMMENT 'Margem de lucro alvo',
  
  -- Meta Mensal
  `monthly_revenue_goal` DECIMAL(12,2) DEFAULT 0.00,
  `monthly_profit_goal` DECIMAL(12,2) DEFAULT 0.00,
  
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company` (`company_id`),
  CONSTRAINT `fk_financial_settings_company` 
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================
-- Dados Iniciais: Categorias de Despesas Padrão
-- ============================================
-- Serão inseridas quando a empresa for criada ou na primeira vez que acessar o módulo
