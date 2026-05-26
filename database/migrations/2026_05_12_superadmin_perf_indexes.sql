-- Migration: Super Admin Performance Indexes
-- Created: 2026-05-12
-- Purpose: Add missing composite indexes to improve super admin dashboard query performance

-- orders: sorted filtered queries by company + time
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_company_created');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE orders ADD INDEX idx_orders_company_created (company_id, created_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- orders: time-range analytics
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_created');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE orders ADD INDEX idx_orders_created (created_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- orders: status filter + time
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_status_created');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE orders ADD INDEX idx_orders_status_created (status, created_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- audit_logs: company + time
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'audit_logs' AND index_name = 'idx_audit_logs_company_created');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE audit_logs ADD INDEX idx_audit_logs_company_created (company_id, created_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- audit_logs: created_at
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'audit_logs' AND index_name = 'idx_audit_logs_created');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE audit_logs ADD INDEX idx_audit_logs_created (created_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- users: company-scoped counts
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_users_company');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE users ADD INDEX idx_users_company (company_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- users: company + active filter
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_users_company_active');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE users ADD INDEX idx_users_company_active (company_id, active)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
