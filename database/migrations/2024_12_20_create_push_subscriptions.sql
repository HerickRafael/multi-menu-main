-- Migration: Create push_subscriptions table for Web Push Notifications
-- Date: 2024-12-20

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL COMMENT 'ID do usuário admin (opcional)',
  `endpoint` TEXT NOT NULL COMMENT 'URL do endpoint de push',
  `p256dh_key` VARCHAR(255) NOT NULL COMMENT 'Chave pública do cliente',
  `auth_key` VARCHAR(255) NOT NULL COMMENT 'Chave de autenticação',
  `user_agent` VARCHAR(500) NULL COMMENT 'User agent do navegador',
  `device_name` VARCHAR(100) NULL COMMENT 'Nome amigável do dispositivo',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_used_at` TIMESTAMP NULL COMMENT 'Último envio de notificação',
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Contagem de falhas consecutivas',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_company_active` (`company_id`, `is_active`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_endpoint` (`endpoint`(255)),
  CONSTRAINT `fk_push_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_push_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice único para evitar duplicatas (mesmo endpoint para mesma empresa)
ALTER TABLE `push_subscriptions` ADD UNIQUE INDEX `idx_unique_endpoint` (`company_id`, `endpoint`(255));
