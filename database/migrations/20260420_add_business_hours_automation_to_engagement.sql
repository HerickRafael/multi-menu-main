-- Migration: Adiciona controle da automacao por expediente
-- Permite ativar/desativar o ajuste automatico de alwaysOnline/rejectCall por horario da loja

ALTER TABLE customer_engagement_config
    ADD COLUMN IF NOT EXISTS business_hours_automation_enabled TINYINT(1) NOT NULL DEFAULT 0
    AFTER scheduled_pause_message;
