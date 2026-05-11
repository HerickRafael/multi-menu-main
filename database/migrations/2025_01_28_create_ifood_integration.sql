-- Migration: Create iFood integration tables
-- Date: 2025-01-28

-- Tabela de configuração da integração iFood por empresa
CREATE TABLE IF NOT EXISTS `ifood_integrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT NOT NULL,
    `merchant_id` VARCHAR(100) NULL COMMENT 'ID do merchant no iFood',
    `client_id` VARCHAR(100) NULL COMMENT 'Client ID da aplicação iFood',
    `client_secret` VARCHAR(255) NULL COMMENT 'Client Secret da aplicação iFood (encriptado)',
    `access_token` TEXT NULL COMMENT 'Access Token atual (encriptado)',
    `refresh_token` TEXT NULL COMMENT 'Refresh Token (encriptado)',
    `token_expires_at` TIMESTAMP NULL COMMENT 'Data de expiração do token',
    `webhook_secret` VARCHAR(255) NULL COMMENT 'Secret para validar webhooks',
    `is_active` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Se a integração está ativa',
    `auto_confirm` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Confirmar pedidos automaticamente',
    `sync_catalog` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Sincronizar catálogo com iFood',
    `last_poll_at` TIMESTAMP NULL COMMENT 'Última consulta de eventos (polling)',
    `last_error` TEXT NULL COMMENT 'Último erro ocorrido',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_company_ifood` (`company_id`),
    CONSTRAINT `fk_ifood_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar pedidos do iFood
CREATE TABLE IF NOT EXISTS `ifood_orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT NOT NULL,
    `order_id` INT UNSIGNED NULL COMMENT 'ID do pedido local (orders table)',
    `ifood_order_id` VARCHAR(100) NOT NULL COMMENT 'UUID do pedido no iFood',
    `ifood_display_id` VARCHAR(20) NULL COMMENT 'ID amigável do iFood (ex: XPTO)',
    `ifood_merchant_id` VARCHAR(100) NOT NULL,
    `order_type` ENUM('DELIVERY', 'TAKEOUT', 'DINE_IN', 'INDOOR') NOT NULL DEFAULT 'DELIVERY',
    `order_timing` ENUM('IMMEDIATE', 'SCHEDULED') NOT NULL DEFAULT 'IMMEDIATE',
    `sales_channel` VARCHAR(50) NOT NULL DEFAULT 'IFOOD',
    `status` VARCHAR(50) NOT NULL DEFAULT 'PLACED' COMMENT 'Status atual do pedido no iFood',
    `customer_name` VARCHAR(255) NULL,
    `customer_phone` VARCHAR(50) NULL,
    `customer_document` VARCHAR(20) NULL,
    `delivery_address` JSON NULL COMMENT 'Endereço de entrega completo',
    `items` JSON NOT NULL COMMENT 'Itens do pedido',
    `benefits` JSON NULL COMMENT 'Cupons/descontos aplicados',
    `payments` JSON NOT NULL COMMENT 'Informações de pagamento',
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `benefits_total` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `additional_fees` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `pickup_code` VARCHAR(10) NULL COMMENT 'Código de retirada',
    `delivered_by` ENUM('IFOOD', 'MERCHANT') NOT NULL DEFAULT 'IFOOD',
    `scheduled_datetime` TIMESTAMP NULL COMMENT 'Data/hora agendada para entrega',
    `ifood_created_at` TIMESTAMP NULL COMMENT 'Data de criação no iFood',
    `confirmed_at` TIMESTAMP NULL,
    `ready_at` TIMESTAMP NULL,
    `dispatched_at` TIMESTAMP NULL,
    `concluded_at` TIMESTAMP NULL,
    `cancelled_at` TIMESTAMP NULL,
    `cancellation_reason` VARCHAR(255) NULL,
    `raw_data` JSON NULL COMMENT 'Dados brutos do pedido iFood',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ifood_order` (`ifood_order_id`),
    KEY `idx_company_status` (`company_id`, `status`),
    KEY `idx_order_local` (`order_id`),
    KEY `idx_created` (`created_at`),
    CONSTRAINT `fk_ifood_orders_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de log de eventos do iFood
CREATE TABLE IF NOT EXISTS `ifood_events_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT NOT NULL,
    `ifood_order_id` VARCHAR(100) NOT NULL,
    `event_id` VARCHAR(100) NOT NULL COMMENT 'UUID do evento no iFood',
    `event_code` VARCHAR(20) NOT NULL COMMENT 'Código do evento (ex: PLC, CFM)',
    `event_full_code` VARCHAR(50) NOT NULL COMMENT 'Código completo (ex: PLACED, CONFIRMED)',
    `metadata` JSON NULL,
    `acknowledged` TINYINT(1) NOT NULL DEFAULT 0,
    `acknowledged_at` TIMESTAMP NULL,
    `ifood_created_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_event` (`event_id`),
    KEY `idx_order` (`ifood_order_id`),
    KEY `idx_company_ack` (`company_id`, `acknowledged`),
    CONSTRAINT `fk_ifood_events_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de mapeamento de produtos iFood <-> Local
CREATE TABLE IF NOT EXISTS `ifood_product_mapping` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT NOT NULL,
    `product_id` INT UNSIGNED NULL COMMENT 'ID do produto local',
    `ifood_product_id` VARCHAR(100) NOT NULL COMMENT 'ID do produto no iFood',
    `ifood_external_code` VARCHAR(100) NULL COMMENT 'Código externo configurado no iFood',
    `ifood_name` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_company_ifood_product` (`company_id`, `ifood_product_id`),
    KEY `idx_product` (`product_id`),
    CONSTRAINT `fk_ifood_mapping_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
