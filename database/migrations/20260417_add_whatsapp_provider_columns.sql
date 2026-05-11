-- Migration: Suporte a múltiplos providers WhatsApp (Evolution v2 + EvoGo)
-- Data: 2026-04-17

ALTER TABLE companies
    ADD COLUMN IF NOT EXISTS whatsapp_provider VARCHAR(20) NULL DEFAULT 'evolution' AFTER evolution_api_key,
    ADD COLUMN IF NOT EXISTS evogo_server_url VARCHAR(255) NULL AFTER whatsapp_provider,
    ADD COLUMN IF NOT EXISTS evogo_api_key VARCHAR(255) NULL AFTER evogo_server_url;

UPDATE companies
SET whatsapp_provider = 'evolution'
WHERE whatsapp_provider IS NULL OR whatsapp_provider = '';
