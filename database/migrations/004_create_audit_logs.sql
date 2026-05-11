-- FASE 2: Tabela de auditoria global para rastreamento de ações do super admin
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    -- Quem fez a ação
    super_admin_id INT NOT NULL,
    
    -- O que foi feito
    action VARCHAR(100) NOT NULL,  -- 'create', 'update', 'delete', 'suspend', 'activate', 'impersonate', 'logout'
    module VARCHAR(100) NOT NULL,  -- 'stores', 'users', 'orders', 'impersonations', 'resources'
    
    -- Em qual entidade
    entity_type VARCHAR(100),       -- 'company', 'user', 'order', 'impersonation'
    entity_id INT,                  -- ID da entidade afetada
    company_id INT,                 -- Qual loja foi afetada (para isolamento multi-tenant)
    
    -- Dados de auditoria
    old_data LONGTEXT,              -- JSON: valores antigos antes da mudança
    new_data LONGTEXT,              -- JSON: novos valores após a mudança
    description TEXT,               -- Descrição legível da ação (ex: "Suspended store Wollburger")
    
    -- Contexto técnico
    ip_address VARCHAR(45),         -- IPv4 ou IPv6
    user_agent TEXT,                -- Browser/Client info
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes para queries rápidas
    INDEX (super_admin_id),
    INDEX (company_id),
    INDEX (action),
    INDEX (module),
    INDEX (entity_type, entity_id),
    INDEX (created_at),
    INDEX (super_admin_id, created_at DESC),  -- Histórico por admin
    INDEX (company_id, created_at DESC),      -- Histórico por loja
    
    -- Foreign key (soft delete safe — não use ON DELETE CASCADE, audit logs devem persistir)
    CONSTRAINT fk_audit_logs_super_admin FOREIGN KEY (super_admin_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: nenhum registro inicial necessário, será populado conforme ações ocorrem
