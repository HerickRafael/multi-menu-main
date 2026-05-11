ALTER TABLE `companies`
  ADD COLUMN `delivery_after_hours_fee` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `min_order`,
  ADD COLUMN `delivery_free_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `delivery_after_hours_fee`;
