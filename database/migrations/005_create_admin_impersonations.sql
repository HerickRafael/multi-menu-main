-- FASE 2: Tabela de impersonações (quando super admin "entra como" uma loja)
CREATE TABLE IF NOT EXISTS admin_impersonations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    -- Quem está impersonando
    super_admin_id INT NOT NULL,
    
    -- Qual loja está sendo impersonada
    company_id INT NOT NULL,
    
    -- Sessão isolada
    session_token VARCHAR(255) NOT NULL UNIQUE,  -- Token seguro para isolamento de sessão
    
    -- Contexto de quem está sendo impersonado
    impersonated_as_role ENUM('owner', 'staff') DEFAULT 'owner',  -- Qual role no contexto da loja
    
    -- Timeline da impersonação
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME NULL,  -- NULL = impersonação ativa
    reason TEXT,  -- Por que o super admin entrou (debugging, support, etc)
    
    -- Contexto técnico
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    -- Dados para auditoria
    action_count INT DEFAULT 0,  -- Quantas ações foram feitas durante a impersonação
    final_note TEXT,            -- Observações ao encerrar
    
    -- Indexes
    INDEX (super_admin_id),
    INDEX (company_id),
    INDEX (session_token),
    INDEX (started_at),
    INDEX (ended_at),
    INDEX (super_admin_id, started_at DESC),  -- Histórico de impersonações do admin
    INDEX (company_id, started_at DESC),      -- Histórico de impersonações da loja
    UNIQUE INDEX (session_token),
    
    -- Foreign keys
    CONSTRAINT fk_impersonations_super_admin FOREIGN KEY (super_admin_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_impersonations_company FOREIGN KEY (company_id) REFERENCES companies(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: nenhum registro inicial necessário, será criado quando super admin impersonate

-- Índice combinado para queries otimizadas
CREATE INDEX idx_impersonations_active ON admin_impersonations(ended_at, started_at DESC);
