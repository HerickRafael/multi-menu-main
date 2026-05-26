-- iFood Integration — Phase 0 Foundations
-- Date: 2026-05-24
--
-- 1) Adds environment toggle (sandbox|production) to ifood_integrations
-- 2) Adds optional sandbox_merchant_id (kept separate so user can switch envs without losing prod merchant id)
-- 3) Creates ifood_api_logs (audit trail of every API call: request/response/latency/retry)
--
-- queue_jobs already has the required indexes (job_type, status, available_at) — no change.

ALTER TABLE ifood_integrations
  ADD COLUMN environment ENUM('sandbox','production') NOT NULL DEFAULT 'production' AFTER is_active,
  ADD COLUMN sandbox_merchant_id VARCHAR(100) NULL DEFAULT NULL AFTER merchant_id;

CREATE TABLE ifood_api_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  environment ENUM('sandbox','production') NOT NULL DEFAULT 'production',
  module VARCHAR(50) NOT NULL COMMENT 'auth, order, catalog, review, shipping, merchant, events',
  request_method VARCHAR(10) NOT NULL,
  request_url VARCHAR(500) NOT NULL,
  http_status SMALLINT UNSIGNED NULL COMMENT 'null if cURL/network failure before response',
  latency_ms INT UNSIGNED NULL,
  attempt_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
  request_body MEDIUMTEXT NULL COMMENT 'Sanitized (tokens redacted)',
  response_body MEDIUMTEXT NULL COMMENT 'Truncated at 64KB',
  error_message VARCHAR(500) NULL,
  job_id BIGINT UNSIGNED NULL COMMENT 'queue_jobs.id if called from worker',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_company_created (company_id, created_at),
  KEY idx_status (http_status),
  KEY idx_module (module, created_at),
  KEY idx_job (job_id),
  CONSTRAINT fk_api_logs_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
