-- iFood Integration — Phase 2 (Reviews)
-- Date: 2026-05-24
--
-- Cache local das avaliações de cada merchant. Sincronizado via job
-- 'ifood.reviews.fetch'. Identidade externa: (company_id, environment, ifood_review_id).

CREATE TABLE ifood_reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  environment ENUM('sandbox','production') NOT NULL DEFAULT 'production',
  ifood_review_id VARCHAR(100) NOT NULL,
  merchant_id VARCHAR(100) NOT NULL,
  ifood_order_id VARCHAR(100) NULL COMMENT 'UUID do pedido no iFood (referencia ifood_orders.ifood_order_id, soft)',
  order_display_id VARCHAR(20) NULL COMMENT 'Display ID do pedido (ex: ABC1) para exibição rápida',
  rating TINYINT UNSIGNED NOT NULL COMMENT '1 a 5',
  comment TEXT NULL,
  customer_name VARCHAR(150) NULL,
  moderation_status VARCHAR(40) NULL COMMENT 'PUBLISHED, MODERATED, REMOVED, etc.',
  review_date DATETIME NULL COMMENT 'Quando a review foi feita no iFood',
  raw_data JSON NULL COMMENT 'Payload bruto do iFood (forward-compat)',
  fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_company_env_review (company_id, environment, ifood_review_id),
  KEY idx_company_date (company_id, environment, review_date),
  KEY idx_rating (rating),
  KEY idx_order (ifood_order_id),
  KEY idx_moderation (moderation_status),
  CONSTRAINT fk_reviews_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
