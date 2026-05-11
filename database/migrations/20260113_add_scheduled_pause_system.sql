-- Migration: Sistema de Pausa Programada
-- Data: 2026-01-13
-- Descrição: Adiciona campos para controle de pausa programada similar ao iFood

-- Adiciona colunas na tabela companies para controlar pausa programada
ALTER TABLE companies
ADD COLUMN pause_enabled TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Se a loja está em pausa (1=sim, 0=não)',
ADD COLUMN pause_until DATETIME DEFAULT NULL COMMENT 'Data/hora até quando a pausa está ativa',
ADD COLUMN pause_reason VARCHAR(255) DEFAULT NULL COMMENT 'Motivo da pausa exibido ao cliente',
ADD COLUMN pause_created_at DATETIME DEFAULT NULL COMMENT 'Quando a pausa foi criada',
ADD COLUMN pause_type ENUM('indefinite', 'timed', 'scheduled') DEFAULT 'timed' COMMENT 'Tipo de pausa: indefinido, temporizado ou agendado';

-- Índice para consultas rápidas de empresas em pausa
CREATE INDEX idx_companies_pause_enabled ON companies(pause_enabled, pause_until);
