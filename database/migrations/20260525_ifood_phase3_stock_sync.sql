-- iFood Integration — Phase 3 (Stock Sync — Catalog Item Status)
-- Date: 2026-05-25
--
-- 1) `ifood_stock_sync_state`: snapshot do último estado sincronizado por
--    (company, environment, product). Permite detectar drift e evitar PUTs
--    desnecessários quando o estado local == último estado remoto conhecido.
--
-- 2) Adiciona `dedup_key` em `queue_jobs` para coalescing de jobs pendentes.
--    O worker limpa o dedup_key ao reservar o job; logo, a unicidade real
--    é "1 dedup_key ativo entre os jobs ainda não-reservados".

CREATE TABLE ifood_stock_sync_state (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  environment ENUM('sandbox','production') NOT NULL DEFAULT 'production',
  product_id INT UNSIGNED NOT NULL COMMENT 'FK lógica para products.id',
  ifood_product_id VARCHAR(100) NOT NULL COMMENT 'Snapshot de ifood_product_mapping.ifood_product_id',
  desired_status ENUM('AVAILABLE','UNAVAILABLE') NOT NULL,
  last_synced_status ENUM('AVAILABLE','UNAVAILABLE') NULL,
  last_synced_at DATETIME NULL,
  last_error VARCHAR(500) NULL,
  consecutive_failures INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_company_env_product (company_id, environment, product_id),
  KEY idx_drift (last_synced_status, desired_status),
  KEY idx_ifood_product (company_id, environment, ifood_product_id),
  CONSTRAINT fk_stock_sync_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- dedup_key para coalescing.
-- A regra "no máximo 1 job pendente por dedup_key" é mantida pelo dispatcher
-- via SELECT antes do INSERT. Não usamos UNIQUE KEY porque MySQL não suporta
-- índice parcial (e o mesmo dedup_key pode aparecer em jobs done/dead).
ALTER TABLE queue_jobs
  ADD COLUMN dedup_key VARCHAR(150) NULL AFTER job_type,
  ADD INDEX idx_queue_jobs_dedup (dedup_key, status);
