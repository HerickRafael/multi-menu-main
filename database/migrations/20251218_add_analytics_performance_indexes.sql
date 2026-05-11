-- =====================================================
-- MIGRATION: Analytics Performance Indexes
-- DATA: 2025-12-18
-- DESCRIÇÃO: Adiciona índices compostos otimizados para Analytics
-- PROBLEMA: getSummaryMetrics e queries de relatório estão lentas
-- GANHO ESPERADO: 3-10x velocidade em consultas de analytics
-- =====================================================

-- Índice composto para queries de analytics (company + status + created_at)
-- Crítico para getSummaryMetrics, getSalesByDay, getSalesByHour
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_analytics');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_orders_analytics ON orders (company_id, status, created_at)', 'SELECT "Index idx_orders_analytics already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Índice para busca por telefone do cliente (usado em contagem de clientes únicos)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_customer_phone');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_orders_customer_phone ON orders (company_id, customer_phone, created_at)', 'SELECT "Index idx_orders_customer_phone already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Índice para page_views (usado em getPageViews)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'page_views' AND index_name = 'idx_page_views_analytics');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_page_views_analytics ON page_views (company_id, viewed_at, ip_address)', 'SELECT "Index idx_page_views_analytics already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Índice para order_items (usado em getTopProducts)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'order_items' AND index_name = 'idx_order_items_analytics');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX idx_order_items_analytics ON order_items (product_id, quantity, line_total)', 'SELECT "Index idx_order_items_analytics already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- =====================================================
-- VERIFICAÇÃO: Execute para ver os índices criados
-- =====================================================
-- SHOW INDEX FROM orders WHERE Key_name LIKE 'idx_orders%';
-- SHOW INDEX FROM page_views;
-- SHOW INDEX FROM order_items;
