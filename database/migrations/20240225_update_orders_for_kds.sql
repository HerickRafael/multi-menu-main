-- 20240225_update_orders_for_kds.sql

ALTER TABLE `orders`
  ADD COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN `status_changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `sla_deadline` DATETIME DEFAULT NULL,
  ADD COLUMN `customer_address` TEXT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `order_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  `event_type` ENUM('order.created','order.updated','order.status_changed','order.canceled','keepalive') NOT NULL DEFAULT 'order.updated',
  `status` ENUM('pending','paid','completed','canceled') DEFAULT NULL,
  `payload` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_events_order_id_idx` (`order_id`),
  KEY `order_events_company_id_idx` (`company_id`),
  KEY `order_events_created_at_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
