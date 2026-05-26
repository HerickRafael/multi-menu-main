-- iFood Integration — Phase 8 (Widget)
-- Date: 2026-05-26
--
-- Configuração por company para o widget iFood incorporado no site público.
-- Uma row por company; a ausência de row = widget desativado.
--
-- O widget gera dois recursos:
--   1) Link/iframe pro cardápio iFood (merchant_url)
--   2) Link de tracking para pedidos iFood já feitos
--
-- Não armazenamos credenciais aqui — usa o que já existe em ifood_integrations.

CREATE TABLE ifood_widget_config (
  company_id INT NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  widget_type ENUM('button','embedded','tracking_only') NOT NULL DEFAULT 'button'
    COMMENT 'button=botão flutuante; embedded=iframe inline; tracking_only=só tracking de pedidos existentes',
  merchant_slug VARCHAR(150) NULL COMMENT 'Slug do merchant no iFood (extraído da URL)',
  merchant_url VARCHAR(500) NULL COMMENT 'URL completa do merchant — usado quando admin colou direto',
  tracking_enabled TINYINT(1) NOT NULL DEFAULT 1,
  theme ENUM('light','dark','auto') NOT NULL DEFAULT 'light',
  position ENUM('bottom-right','bottom-left','top-right','top-left','inline') NOT NULL DEFAULT 'bottom-right',
  button_label VARCHAR(50) NOT NULL DEFAULT 'Peça pelo iFood',
  fallback_url VARCHAR(500) NULL COMMENT 'Onde mandar o usuário se o widget falhar (fallback)',
  allowed_origins TEXT NULL COMMENT 'Domínios autorizados a embedar (separados por vírgula; null = qualquer)',
  custom_css TEXT NULL,
  cache_version INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Bumpado a cada update — usado pra cache-bust do JS snippet',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (company_id),
  CONSTRAINT fk_widget_config_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
