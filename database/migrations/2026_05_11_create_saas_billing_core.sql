-- Core SaaS billing tables (multi-tenant)
-- Scope: plans, subscriptions, invoices, usage_limits

CREATE TABLE IF NOT EXISTS `plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  `price_monthly` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price_yearly` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` CHAR(3) NOT NULL DEFAULT 'BRL',
  `limits_json` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plans_code` (`code`),
  KEY `idx_plans_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `status` ENUM('trialing','active','past_due','canceled','incomplete','paused') NOT NULL DEFAULT 'incomplete',
  `starts_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trial_ends_at` DATETIME NULL,
  `current_period_start` DATETIME NULL,
  `current_period_end` DATETIME NULL,
  `canceled_at` DATETIME NULL,
  `external_provider` VARCHAR(40) NULL,
  `external_subscription_id` VARCHAR(120) NULL,
  `metadata_json` JSON NULL,
  `created_by_super_admin_id` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscriptions_external` (`external_provider`, `external_subscription_id`),
  KEY `idx_subscriptions_company_status` (`company_id`, `status`),
  KEY `idx_subscriptions_plan` (`plan_id`),
  CONSTRAINT `fk_subscriptions_company`
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subscriptions_plan`
    FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_subscriptions_created_by_super_admin`
    FOREIGN KEY (`created_by_super_admin_id`) REFERENCES `super_admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `subscription_id` BIGINT UNSIGNED NULL,
  `invoice_number` VARCHAR(40) NOT NULL,
  `status` ENUM('draft','open','paid','void','uncollectible') NOT NULL DEFAULT 'draft',
  `currency` CHAR(3) NOT NULL DEFAULT 'BRL',
  `amount_subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `due_date` DATETIME NULL,
  `paid_at` DATETIME NULL,
  `external_invoice_id` VARCHAR(120) NULL,
  `payload_json` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoices_invoice_number` (`invoice_number`),
  UNIQUE KEY `uq_invoices_external` (`external_invoice_id`),
  KEY `idx_invoices_company_status` (`company_id`, `status`),
  KEY `idx_invoices_subscription` (`subscription_id`),
  CONSTRAINT `fk_invoices_company`
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoices_subscription`
    FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `usage_limits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `subscription_id` BIGINT UNSIGNED NULL,
  `resource_key` VARCHAR(80) NOT NULL,
  `hard_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `soft_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `current_usage` INT UNSIGNED NOT NULL DEFAULT 0,
  `reset_period` ENUM('daily','weekly','monthly','never') NOT NULL DEFAULT 'monthly',
  `resets_at` DATETIME NULL,
  `is_blocking` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usage_limits_company_resource` (`company_id`, `resource_key`),
  KEY `idx_usage_limits_subscription` (`subscription_id`),
  CONSTRAINT `fk_usage_limits_company`
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_limits_subscription`
    FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
