-- Migration: Round 3 - Final fixes
-- 1. daily_quota na config
-- 2. ab_holdout_pct na config
-- 3. is_holdout no log
-- 4. error_message no queue (para DLQ retry tracking)

-- Quota diária por empresa (padrão 100)
ALTER TABLE customer_engagement_config 
    ADD COLUMN IF NOT EXISTS daily_quota INT NOT NULL DEFAULT 100 AFTER min_hours_between_messages;

-- Percentual de holdout A/B (0 = desabilitado)
ALTER TABLE customer_engagement_config 
    ADD COLUMN IF NOT EXISTS ab_holdout_pct INT NOT NULL DEFAULT 0 AFTER daily_quota;

-- Flag de holdout no log para comparação A/B
ALTER TABLE customer_engagement_log 
    ADD COLUMN IF NOT EXISTS is_holdout TINYINT(1) NOT NULL DEFAULT 0 AFTER error_message;

-- Error message no queue para DLQ tracking
ALTER TABLE customer_engagement_queue 
    ADD COLUMN IF NOT EXISTS error_message VARCHAR(255) NULL AFTER attempts;

-- Metadata JSON para heartbeat (alerta DLQ, etc)
ALTER TABLE system_heartbeat 
    ADD COLUMN IF NOT EXISTS metadata JSON NULL AFTER status;
