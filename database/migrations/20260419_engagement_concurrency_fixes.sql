-- Migration: Melhorias de concorrência e prioridade no sistema de engajamento
-- Data: 2026-04-19
-- Fixes: Race condition na fila, prioridade por cenário
--
-- Coluna gerada (STORED) para UNIQUE parcial:
-- - Itens ativos (pending/processing): valor = scenario_type → UNIQUE impede duplicatas
-- - Itens finalizados (sent/failed/cancelled): valor = NULL → NULLs não conflitam em UNIQUE
--
-- Isso impede que dois ciclos de cron criem entradas duplicadas para o mesmo cliente+cenário

ALTER TABLE customer_engagement_queue 
ADD COLUMN active_dedup VARCHAR(30) GENERATED ALWAYS AS (
    CASE WHEN status IN ('pending', 'processing') THEN scenario_type ELSE NULL END
) STORED;

ALTER TABLE customer_engagement_queue 
ADD UNIQUE KEY uniq_active_customer_scenario (company_id, customer_id, active_dedup);
