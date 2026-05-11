-- Migration: Sistema de Engajamento Automático de Clientes via WhatsApp
-- Data: 2026-01-01
-- Descrição: Cria tabelas para controlar mensagens automáticas de engajamento
-- Versão: 2.0 - Usa instance_name em vez de instance_id para compatibilidade

-- ============================================================================
-- Tabela de configuração do sistema de engajamento por empresa
-- ============================================================================
CREATE TABLE IF NOT EXISTS `customer_engagement_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `instance_name` VARCHAR(100) NOT NULL COMMENT 'Nome da instância Evolution (string)',
    `enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Sistema ativo ou não',
    
    -- Cenário 1: Cadastro sem pedido
    `scenario1_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Ativar notificação para cadastro sem pedido',
    `scenario1_delay_minutes` INT NOT NULL DEFAULT 10 COMMENT 'Minutos após cadastro para enviar mensagem',
    
    -- Cenário 2: Cliente inativo
    `scenario2_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Ativar notificação para cliente inativo',
    `scenario2_inactive_days` INT NOT NULL DEFAULT 15 COMMENT 'Dias sem pedido para considerar inativo',
    
    -- Controle de spam
    `min_hours_between_messages` INT NOT NULL DEFAULT 24 COMMENT 'Mínimo de horas entre mensagens para mesmo cliente',
    
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `unique_company` (`company_id`),
    INDEX `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Tabela de log de mensagens enviadas (controle de spam e histórico)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `customer_engagement_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `instance_name` VARCHAR(100) NOT NULL,
    `scenario_type` ENUM('signup_no_order', 'inactive_customer') NOT NULL COMMENT 'Tipo de cenário',
    `customer_phone` VARCHAR(20) NULL COMMENT 'Telefone do cliente no momento do envio',
    `messages_count` TINYINT NOT NULL DEFAULT 3 COMMENT 'Quantidade de mensagens enviadas (sempre 3)',
    `status` ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
    `error_message` TEXT NULL COMMENT 'Mensagem de erro se falhou',
    
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_company_customer` (`company_id`, `customer_id`),
    INDEX `idx_customer_type` (`customer_id`, `scenario_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_company_created` (`company_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Tabela de fila de mensagens pendentes
-- ============================================================================
CREATE TABLE IF NOT EXISTS `customer_engagement_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `instance_name` VARCHAR(100) NOT NULL,
    `scenario_type` ENUM('signup_no_order', 'inactive_customer') NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL COMMENT 'Telefone do cliente',
    `customer_name` VARCHAR(100) NULL COMMENT 'Nome do cliente',
    `scheduled_at` DATETIME NOT NULL COMMENT 'Quando a mensagem deve ser enviada',
    `status` ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `attempts` TINYINT NOT NULL DEFAULT 0 COMMENT 'Tentativas de envio',
    `last_attempt_at` DATETIME NULL,
    `error_message` TEXT NULL,
    
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_status_scheduled` (`status`, `scheduled_at`),
    INDEX `idx_company_status` (`company_id`, `status`),
    INDEX `idx_customer_type` (`customer_id`, `scenario_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
