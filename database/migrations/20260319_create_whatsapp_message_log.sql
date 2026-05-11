-- Migration: Log de envio de mensagens WhatsApp + fila de retry
-- Data: 2026-03-19
-- Descrição: Registra toda tentativa de envio via Evolution API e mantém fila de falhas para reprocessamento

-- ============================================================================
-- Tabela de log de todas as tentativas de envio
-- ============================================================================
CREATE TABLE IF NOT EXISTS `whatsapp_send_log` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `instance_name` VARCHAR(100) NOT NULL,
    `remote_jid` VARCHAR(100) NOT NULL COMMENT 'JID do destinatário',
    `phone` VARCHAR(20) NULL COMMENT 'Número extraído do JID',
    `message_type` VARCHAR(30) NOT NULL DEFAULT 'auto_response' COMMENT 'Tipo: auto_response, engagement, notification, pending_lid',
    `message_preview` VARCHAR(200) NULL COMMENT 'Primeiros 200 chars da mensagem',
    `attempt` TINYINT NOT NULL DEFAULT 1 COMMENT 'Número da tentativa (1, 2, 3)',
    `status` ENUM('success', 'failed', 'pending_retry') NOT NULL,
    `http_code` SMALLINT NULL COMMENT 'HTTP status code da Evolution API',
    `curl_error` VARCHAR(255) NULL COMMENT 'Erro de cURL se houver',
    `api_response` TEXT NULL COMMENT 'Response body da API (truncado)',
    `sent_message_id` VARCHAR(100) NULL COMMENT 'Message ID retornado pela Evolution API (para correlação com echo)',
    `duration_ms` INT NULL COMMENT 'Tempo de execução em ms',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_company_status` (`company_id`, `status`),
    INDEX `idx_company_created` (`company_id`, `created_at`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_phone` (`phone`, `created_at`),
    INDEX `idx_status_retry` (`status`, `created_at`),
    INDEX `idx_sent_message_id` (`sent_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Tabela de mensagens que falharam em todas as tentativas (fila de fallback)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `whatsapp_failed_queue` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `instance_name` VARCHAR(100) NOT NULL,
    `remote_jid` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `message_type` VARCHAR(30) NOT NULL DEFAULT 'auto_response',
    `last_error` VARCHAR(500) NULL COMMENT 'Último erro registrado',
    `last_http_code` SMALLINT NULL,
    `attempts_total` TINYINT NOT NULL DEFAULT 3 COMMENT 'Total de tentativas feitas',
    `status` ENUM('pending', 'retrying', 'sent', 'abandoned') NOT NULL DEFAULT 'pending',
    `next_retry_at` DATETIME NULL COMMENT 'Próxima tentativa agendada',
    `retry_count` TINYINT NOT NULL DEFAULT 0 COMMENT 'Tentativas de reprocessamento',
    `max_retries` TINYINT NOT NULL DEFAULT 3 COMMENT 'Máximo de reprocessamentos',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_status_retry` (`status`, `next_retry_at`),
    INDEX `idx_company` (`company_id`, `status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
