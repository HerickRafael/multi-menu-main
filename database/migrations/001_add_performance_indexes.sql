-- =====================================================
-- MIGRATION: Performance Indexes
-- DATA: 2025-11-12
-- DESCRIÇÃO: Adiciona índices para otimizar queries principais
-- GANHO ESPERADO: 2-5x velocidade em queries principais
-- =====================================================

-- Verificar e criar índices apenas se não existirem
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_company_active');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_products_company_active ON products (company_id, active)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_category_active');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_products_category_active ON products (category_id, active)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_created_at');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_products_created_at ON products (created_at)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'combo_groups' AND index_name = 'idx_combo_groups_product');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_combo_groups_product ON combo_groups (product_id, sort)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'combo_group_items' AND index_name = 'idx_combo_items_group');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_combo_items_group ON combo_group_items (group_id, sort)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'combo_group_items' AND index_name = 'idx_combo_items_simple_product');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_combo_items_simple_product ON combo_group_items (simple_product_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'order_items' AND index_name = 'idx_order_items_product');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_order_items_product ON order_items (product_id, quantity)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'order_items' AND index_name = 'idx_order_items_order');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_order_items_order ON order_items (order_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_company_status');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_orders_company_status ON orders (company_id, status)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_created_at');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_orders_created_at ON orders (created_at)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'customers' AND index_name = 'idx_customers_whatsapp');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_customers_whatsapp ON customers (whatsapp)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'customers' AND index_name = 'idx_customers_company');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_customers_company ON customers (company_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'customer_addresses' AND index_name = 'idx_addresses_customer');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_addresses_customer ON customer_addresses (customer_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'delivery_zones' AND index_name = 'idx_zones_city');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_zones_city ON delivery_zones (city_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- =====================================================
-- VERIFICAÇÃO: Execute para ver os índices criados
-- =====================================================
-- SHOW INDEX FROM products;
-- SHOW INDEX FROM combo_groups;
-- SHOW INDEX FROM combo_group_items;
-- SHOW INDEX FROM order_items;
-- SHOW INDEX FROM orders;
