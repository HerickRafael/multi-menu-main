-- Migration: adiciona colunas de configuração ao companies e cria tabela evolution_instances
-- Gerado em 2025-10-12

-- Adiciona colunas se ainda não existem (MySQL 8+ suporta IF NOT EXISTS)
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `evolution_server_url` varchar(255) DEFAULT NULL;
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `evolution_api_key` varchar(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `evolution_instances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `number` varchar(50) DEFAULT NULL,
  `instance_identifier` varchar(255) DEFAULT NULL,
  `qr_code` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `connected_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `evolution_company_idx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
