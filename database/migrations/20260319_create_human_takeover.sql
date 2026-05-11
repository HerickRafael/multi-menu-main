-- Migration: Sistema de Atendimento Humano (Human Takeover)
-- Data: 2026-03-19
-- Descrição: Quando um humano responde via WhatsApp, toda automação é pausada
--            para aquela conversa. Reativa após inatividade do humano.

CREATE TABLE IF NOT EXISTS `whatsapp_human_takeover` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `instance_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL COMMENT 'Número do cliente em atendimento',
    `remote_jid` VARCHAR(100) NOT NULL COMMENT 'JID do cliente (para reply)',
    `activated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Quando humano assumiu',
    `last_human_activity_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Última msg do humano',
    `expires_at` DATETIME NOT NULL COMMENT 'Quando expira automaticamente (inatividade)',
    `status` ENUM('active', 'expired', 'released') NOT NULL DEFAULT 'active',
    `released_at` DATETIME NULL COMMENT 'Quando foi liberado manualmente',
    
    UNIQUE KEY `unique_active_conversation` (`company_id`, `phone`, `status`),
    INDEX `idx_active_lookup` (`company_id`, `phone`, `status`, `expires_at`),
    INDEX `idx_expiration` (`status`, `expires_at`),
    INDEX `idx_instance` (`instance_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
