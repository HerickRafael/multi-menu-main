-- Usar o banco de dados correto
USE menu;

-- Criar tabela para armazenar configurações das instâncias
-- Esta tabela permite armazenar configurações customizadas para cada instância

CREATE TABLE IF NOT EXISTS `instance_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `instance_name` varchar(100) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` TEXT NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_config` (`company_id`, `instance_name`, `config_key`),
  INDEX `idx_company_instance` (`company_id`, `instance_name`),
  INDEX `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentários sobre os campos:
-- company_id: ID da empresa proprietária da configuração
-- instance_name: Nome da instância Evolution
-- config_key: Chave da configuração (ex: 'order_notification', 'webhook_settings', etc.)
-- config_value: Valor da configuração em formato JSON
-- created_at/updated_at: Timestamps de criação e atualização