-- Migration: Remove status column from evolution_instances (no longer used)
-- Date: 2025-12-13
-- Reason: Status de conexão agora é SEMPRE buscado diretamente da API Evolution
--         usando o endpoint /instance/connectionState/{instanceName}
--         O campo status local estava causando inconsistências (mostrando "conectado" quando não estava)
--
-- ESTA MIGRATION JÁ FOI APLICADA EM 2025-12-13

-- 1. Remover a coluna status
ALTER TABLE `evolution_instances` DROP COLUMN `status`;

-- 2. Remover a coluna qr_code (não usada - QR code vem da API)
ALTER TABLE `evolution_instances` DROP COLUMN `qr_code`;

-- 3. Remover a coluna connected_at (não usada - conexão é verificada na API)
ALTER TABLE `evolution_instances` DROP COLUMN `connected_at`;

-- Estrutura final da tabela:
-- id, company_id, label, number, instance_identifier, created_at, updated_at, is_main
