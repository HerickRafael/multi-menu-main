-- Migration: company_metrics_cache
-- Cache de métricas operacionais de lojas (com TTL)

CREATE TABLE IF NOT EXISTS `company_metrics_cache` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `company_id` int NOT NULL UNIQUE,
  `total_orders_today` int NOT NULL DEFAULT 0,
  `total_revenue_today` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `active_orders_now` int NOT NULL DEFAULT 0,
  `pending_orders` int NOT NULL DEFAULT 0,
  `preparation_time_avg` int DEFAULT 0,
  `customer_satisfaction_avg` decimal(3, 2) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `cached_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  
  KEY `idx_company_id` (`company_id`),
  KEY `idx_expires_at` (`expires_at`),
  
  CONSTRAINT `fk_company_metrics_cache_company_id` 
    FOREIGN KEY (`company_id`) 
    REFERENCES `companies` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
