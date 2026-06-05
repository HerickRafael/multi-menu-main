-- Migration: Create mobile_refresh_tokens table for app JWT auth (refresh + revoke)
-- Date: 2026-05-31
-- Contexto: o JWT atual (ApiController/ApiSecurity) expira em 3600s e NÃO tem
-- mecanismo de renovação nem de invalidação (logout). Esta tabela suporta:
--   * refresh token de longa duração (rotacionado a cada uso)
--   * revogação explícita (logout) e blacklist
--   * vínculo opcional ao device que fez login
--
-- OBS: já existe `oauth_tokens` (api_security_schema.sql) com colunas access_token/
-- refresh_token/scopes/expires_at. Optou-se por tabela dedicada ao fluxo mobile
-- (rotação + device binding + company_id) para não acoplar à máquina OAuth/client_id.
-- Se preferir reutilizar `oauth_tokens`, ver o ALTER alternativo no fim deste arquivo.

CREATE TABLE IF NOT EXISTS `mobile_refresh_tokens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL COMMENT 'SHA-256 do refresh token (nunca guardar em texto)',
  `device_token_id` INT NULL COMMENT 'FK opcional para device_tokens (login por aparelho)',
  `user_agent` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL COMMENT 'IPv4/IPv6 do login',
  `expires_at` DATETIME NOT NULL COMMENT 'Expiração do refresh (ex.: +30 dias)',
  `revoked_at` DATETIME NULL COMMENT 'Preenchido no logout/rotação => token inválido',
  `rotated_to` INT NULL COMMENT 'id do refresh token que substituiu este (auditoria de rotação)',
  `last_used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique_token_hash` (`token_hash`),
  INDEX `idx_user_company` (`user_id`, `company_id`),
  INDEX `idx_active` (`revoked_at`, `expires_at`),
  CONSTRAINT `fk_refresh_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_refresh_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_refresh_device` FOREIGN KEY (`device_token_id`) REFERENCES `device_tokens`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fluxo de uso:
-- * Login (POST /auth/login):
--     - gera JWT de acesso (3600s, como hoje)
--     - gera refresh aleatório (ex.: 64 bytes base64url), guarda SHA-256 em token_hash,
--       expires_at = NOW() + 30 dias
--     - devolve token + refresh_token (texto puro) ao app
-- * Refresh (POST /auth/refresh):
--     - localiza por token_hash, valida revoked_at IS NULL AND expires_at > NOW()
--     - ROTAÇÃO: marca revoked_at=NOW(), cria nova linha, grava rotated_to=novo_id
--     - devolve novo JWT + novo refresh
-- * Logout (POST /auth/logout): UPDATE revoked_at=NOW() WHERE token_hash=?
-- * Logout global: UPDATE revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL
-- * Limpeza: cron apaga revogados/expirados antigos.

-- ──────────────────────────────────────────────────────────────────────────
-- ALTERNATIVA (se for reaproveitar a tabela oauth_tokens existente em vez desta):
--   ALTER TABLE `oauth_tokens`
--     ADD COLUMN `company_id` INT NULL AFTER `user_id`,
--     ADD COLUMN `refresh_expires_at` DATETIME NULL AFTER `expires_at`,
--     ADD COLUMN `revoked_at` DATETIME NULL AFTER `refresh_expires_at`,
--     ADD COLUMN `device_token_id` INT NULL,
--     ADD INDEX `idx_oauth_refresh` (`refresh_token`),
--     ADD INDEX `idx_oauth_company` (`company_id`);
-- ──────────────────────────────────────────────────────────────────────────
