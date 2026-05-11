-- Migration: Substituir coluna gerada por abordagem explícita + DLQ + heartbeat
-- Data: 2026-04-19
-- Fixes: UNIQUE simplificado, Dead Letter Queue, Heartbeat do cron
--
-- Substitui active_dedup (GENERATED) por is_active (TINYINT):
-- - Mais simples, sem dependência de coluna gerada
-- - Regra: pending/processing = 1, outros = NULL
-- - NULLs não conflitam em UNIQUE → permite múltiplos finalizados

-- 1. Remover constraint anterior com coluna gerada
ALTER TABLE customer_engagement_queue DROP INDEX uniq_active_customer_scenario;
ALTER TABLE customer_engagement_queue DROP COLUMN active_dedup;

-- 2. Adicionar coluna explícita is_active
ALTER TABLE customer_engagement_queue 
ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT '1=ativo(pending/processing), NULL=finalizado. Usado para UNIQUE parcial';

-- 3. Marcar itens já finalizados como NULL
UPDATE customer_engagement_queue 
SET is_active = NULL 
WHERE status NOT IN ('pending', 'processing');

-- 4. Criar UNIQUE com colunas explícitas (sem coluna gerada)
ALTER TABLE customer_engagement_queue 
ADD UNIQUE KEY uniq_active_customer_scenario (company_id, customer_id, scenario_type, is_active);

-- 5. Coluna para Dead Letter Queue
ALTER TABLE customer_engagement_queue 
ADD COLUMN dead_letter_at DATETIME NULL DEFAULT NULL COMMENT 'Quando item entrou na DLQ (failed 3x)';

-- 6. Índice para consultas DLQ
ALTER TABLE customer_engagement_queue 
ADD INDEX idx_dlq (company_id, status, dead_letter_at);

-- 7. Tabela de heartbeat para monitoramento de serviços (cron, workers)
CREATE TABLE IF NOT EXISTS system_heartbeat (
    service_name VARCHAR(50) PRIMARY KEY,
    last_run_at DATETIME NOT NULL,
    duration_seconds DECIMAL(8,2) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ok',
    metadata JSON DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
