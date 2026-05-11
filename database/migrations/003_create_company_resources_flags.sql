-- Migration: company_resources_flags
-- Controla ativação/desativação de recursos por loja

CREATE TABLE IF NOT EXISTS `company_resources_flags` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `company_id` int NOT NULL,
  `resource_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `enabled_by_admin_id` int,
  `enabled_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metadata` json DEFAULT NULL,
  
  KEY `idx_company_id` (`company_id`),
  KEY `idx_resource_name` (`resource_name`),
  UNIQUE KEY `uq_company_resource` (`company_id`, `resource_name`),
  
  CONSTRAINT `fk_company_resources_flags_company_id` 
    FOREIGN KEY (`company_id`) 
    REFERENCES `companies` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Recursos padrão (inicialmente todos ativados)
INSERT IGNORE INTO `company_resources_flags` 
  (`company_id`, `resource_name`, `enabled`, `enabled_by_admin_id`, `metadata`)
SELECT 
  id, 
  'whatsapp', 
  1, 
  NULL, 
  JSON_OBJECT('description', 'WhatsApp integration')
FROM companies
WHERE id NOT IN (SELECT company_id FROM company_resources_flags WHERE resource_name = 'whatsapp');

INSERT IGNORE INTO `company_resources_flags` 
  (`company_id`, `resource_name`, `enabled`, `enabled_by_admin_id`, `metadata`)
SELECT 
  id, 
  'delivery', 
  1, 
  NULL, 
  JSON_OBJECT('description', 'Delivery system')
FROM companies
WHERE id NOT IN (SELECT company_id FROM company_resources_flags WHERE resource_name = 'delivery');

INSERT IGNORE INTO `company_resources_flags` 
  (`company_id`, `resource_name`, `enabled`, `enabled_by_admin_id`, `metadata`)
SELECT 
  id, 
  'checkout_v2', 
  1, 
  NULL, 
  JSON_OBJECT('description', 'New checkout system')
FROM companies
WHERE id NOT IN (SELECT company_id FROM company_resources_flags WHERE resource_name = 'checkout_v2');
