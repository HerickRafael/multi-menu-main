-- iFood Integration — Phase 5 (Shipping Orders — HUB logístico)
-- Date: 2026-05-26
--
-- Tabela `ifood_shipping_orders` rastreia o ciclo de vida completo de um
-- pedido criado no NOSSO sistema que usa a logística iFood (endpoint
-- POST /shipping/v1.0/orders).
--
-- Identidade: a chave única externa é (company_id, environment, external_reference).
-- `external_reference` é gerado pelo dispatcher (UUID-like) ou fornecido pelo
-- caller — esse é o vetor de idempotência: enviar 2x o mesmo external_reference
-- não cria 2 pedidos no iFood.
--
-- ifood_shipping_id é o ID que o iFood devolve no response da criação;
-- usado para GET/DELETE subsequentes.
--
-- Estados (status):
--   PENDING    — job enfileirado, ainda não chamou a API
--   SUBMITTED  — POST 2xx; iFood aceitou a requisição (não significa que
--                  o pedido foi "ACCEPTED" pelo sistema dele ainda)
--   ACCEPTED   — sincronizado via polling/webhook: iFood confirmou
--   CONFIRMED  — entregador atribuído (sinônimo do ASSIGN na Fase 4)
--   PICKED_UP  — entregador pegou
--   DELIVERED  — entrega concluída
--   CANCELLED  — cancelado (pelo nosso lado ou pelo iFood)
--   REJECTED   — iFood recusou o pedido (4xx específico na criação)
--   FAILED     — falha permanente que não dá pra recuperar

CREATE TABLE ifood_shipping_orders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  environment ENUM('sandbox','production') NOT NULL DEFAULT 'production',
  order_id INT UNSIGNED NULL COMMENT 'FK lógica para orders.id (opcional — caller pode passar)',
  external_reference VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'Chave de idempotência (UUID ou order_number)',
  ifood_shipping_id VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT 'ID que o iFood devolve no POST',
  status ENUM('PENDING','SUBMITTED','ACCEPTED','CONFIRMED','PICKED_UP','DELIVERED','CANCELLED','REJECTED','FAILED') NOT NULL DEFAULT 'PENDING',
  request_payload JSON NOT NULL COMMENT 'Payload submetido ao iFood (auditoria/replay)',
  response_payload JSON NULL COMMENT 'Último response body relevante',
  last_error VARCHAR(500) NULL,
  last_response_status SMALLINT UNSIGNED NULL,
  submitted_at DATETIME NULL,
  accepted_at DATETIME NULL,
  picked_up_at DATETIME NULL,
  delivered_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  cancel_reason VARCHAR(255) NULL,
  retries INT UNSIGNED NOT NULL DEFAULT 0,
  next_poll_at DATETIME NULL COMMENT 'Quando o cron de status deve tentar de novo (NULL = não polar)',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_company_env_extref (company_id, environment, external_reference),
  KEY idx_ifood_id (company_id, environment, ifood_shipping_id),
  KEY idx_status_poll (status, next_poll_at),
  KEY idx_order (order_id),
  CONSTRAINT fk_shipping_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
