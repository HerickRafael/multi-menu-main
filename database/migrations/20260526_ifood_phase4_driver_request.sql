-- iFood Integration — Phase 4 (Driver Request — pedidos iFood nativos)
-- Date: 2026-05-26
--
-- Tabela `ifood_order_drivers` rastreia o ciclo de vida da solicitação de
-- entregador para um pedido iFood (1:1 com ifood_orders por env).
--
-- Estados (request_status):
--   PENDING            — job enfileirado, ainda não chamou a API
--   REQUESTED          — chamada feita; iFood respondeu 200/202; aguardando assignment
--   NO_DRIVER          — iFood respondeu 422 / driver indisponível; reagendado
--   ASSIGNED           — entregador atribuído (via webhook DISPATCHED com metadata)
--   COMPLETED          — entrega concluída
--   CANCELLED          — cancelado (pelo admin ou pelo entregador)
--   FAILED             — falha permanente (4xx não-422); job foi pra dead

CREATE TABLE ifood_order_drivers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  environment ENUM('sandbox','production') NOT NULL DEFAULT 'production',
  ifood_order_id VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'FK lógica para ifood_orders.ifood_order_id — collation alinhada para JOINs',
  order_display_id VARCHAR(20) NULL,
  request_status ENUM('PENDING','REQUESTED','NO_DRIVER','ASSIGNED','COMPLETED','CANCELLED','FAILED') NOT NULL DEFAULT 'PENDING',
  driver_id VARCHAR(100) NULL COMMENT 'ID do entregador retornado pelo iFood',
  driver_name VARCHAR(150) NULL,
  driver_phone VARCHAR(50) NULL,
  vehicle_type VARCHAR(50) NULL,
  requested_at DATETIME NULL,
  assigned_at DATETIME NULL,
  picked_up_at DATETIME NULL,
  delivered_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  cancel_reason VARCHAR(255) NULL,
  retries INT UNSIGNED NOT NULL DEFAULT 0,
  last_error VARCHAR(500) NULL,
  last_response_status SMALLINT UNSIGNED NULL COMMENT 'Último HTTP status do iFood',
  raw_response JSON NULL COMMENT 'Último response body relevante (forward-compat)',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_company_env_order (company_id, environment, ifood_order_id),
  KEY idx_status (request_status, updated_at),
  KEY idx_driver (driver_id),
  CONSTRAINT fk_order_drivers_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
