-- Migration: Criar tabelas de templates de personalização
-- Permite reutilizar grupos de personalização entre produtos

-- Tabela principal de templates
CREATE TABLE IF NOT EXISTS `customization_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nome do template (ex: Queijos, Molhos)',
  `type` enum('single','extra','addon','component') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'extra',
  `min_qty` int NOT NULL DEFAULT 0,
  `max_qty` int NOT NULL DEFAULT 99,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_active` (`active`),
  CONSTRAINT `fk_cust_tpl_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de itens do template
CREATE TABLE IF NOT EXISTS `customization_template_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `ingredient_id` int DEFAULT NULL,
  `label` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `delta` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Preço adicional',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `default_qty` int NOT NULL DEFAULT 1,
  `min_qty` int NOT NULL DEFAULT 0,
  `max_qty` int NOT NULL DEFAULT 1,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_ingredient` (`ingredient_id`),
  CONSTRAINT `fk_cti_template` FOREIGN KEY (`template_id`) REFERENCES `customization_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cti_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de vínculo template <-> produto (para saber onde o template está sendo usado)
-- Quando um grupo é copiado de um template, registramos a referência
CREATE TABLE IF NOT EXISTS `product_custom_group_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL COMMENT 'ID do grupo em product_custom_groups',
  `template_id` int NOT NULL COMMENT 'ID do template de origem',
  `synced` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se está sincronizado com o template',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group` (`group_id`),
  KEY `idx_template` (`template_id`),
  CONSTRAINT `fk_pcgt_group` FOREIGN KEY (`group_id`) REFERENCES `product_custom_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcgt_template` FOREIGN KEY (`template_id`) REFERENCES `customization_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
