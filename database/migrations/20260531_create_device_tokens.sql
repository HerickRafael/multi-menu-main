-- Migration: Create device_tokens table for native push (FCM / APNs)
-- Date: 2026-05-31
-- Contexto: app Flutter nativo. Substitui o Web Push (push_subscriptions / VAPID),
-- que não funciona em app nativo. Aqui guardamos o token de device do Firebase
-- Cloud Messaging (Android) e APNs-via-FCM (iOS).

CREATE TABLE IF NOT EXISTS `device_tokens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `user_id` INT NULL COMMENT 'Usuário admin dono do device (logout = desativa)',
  `fcm_token` VARCHAR(255) NOT NULL COMMENT 'Registration token do FCM/APNs',
  `platform` ENUM('android','ios') NOT NULL,
  `app_version` VARCHAR(20) NULL COMMENT 'Versão do app no momento do registro',
  `device_name` VARCHAR(100) NULL COMMENT 'Nome amigável (ex.: Galaxy S23)',
  `device_id` VARCHAR(128) NULL COMMENT 'Identificador estável do aparelho (opcional)',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_used_at` TIMESTAMP NULL COMMENT 'Último envio de push bem-sucedido',
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Falhas consecutivas (token inválido => desativar)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique_token` (`fcm_token`),
  INDEX `idx_company_active` (`company_id`, `is_active`),
  INDEX `idx_user` (`user_id`),
  CONSTRAINT `fk_device_tokens_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_device_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notas de uso:
-- * Registro (POST /push/devices): UPSERT por `fcm_token`
--     INSERT ... ON DUPLICATE KEY UPDATE company_id=VALUES(company_id), user_id=VALUES(user_id),
--       platform=VALUES(platform), app_version=VALUES(app_version), is_active=1, failed_count=0;
-- * Desregistro (DELETE /push/devices ou logout): UPDATE is_active=0 WHERE fcm_token=?
-- * Envio: ao receber resposta UNREGISTERED/INVALID do FCM, incrementar failed_count;
--   ao passar de um limite (ex.: 5) setar is_active=0.
