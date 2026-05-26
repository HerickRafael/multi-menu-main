-- ===================================================================
-- AUTHORIZATION SYSTEM (RBAC) - DATABASE SCHEMA
-- ===================================================================
-- Sistema completo de Role-Based Access Control
-- ===================================================================

-- Tabela de roles (funções)
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_roles_name ON roles(name);

-- Tabela de permissões
CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_permissions_name ON permissions(name);

-- Tabela de associação usuário-role (many-to-many)
CREATE TABLE IF NOT EXISTS user_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE(user_id, role_id)
);

CREATE INDEX IF NOT EXISTS idx_user_roles_user ON user_roles(user_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_role ON user_roles(role_id);

-- Tabela de associação role-permission (many-to-many)
CREATE TABLE IF NOT EXISTS role_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE(role_id, permission_id)
);

CREATE INDEX IF NOT EXISTS idx_role_permissions_role ON role_permissions(role_id);
CREATE INDEX IF NOT EXISTS idx_role_permissions_permission ON role_permissions(permission_id);

-- Tabela de logs de acesso (auditoria)
CREATE TABLE IF NOT EXISTS access_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    resource TEXT,
    granted INTEGER NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_access_logs_user ON access_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_access_logs_action ON access_logs(action);
CREATE INDEX IF NOT EXISTS idx_access_logs_created ON access_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_access_logs_granted ON access_logs(granted);

-- ===================================================================
-- ROLES PADRÃO
-- ===================================================================

INSERT OR IGNORE INTO roles (name, description, created_at) VALUES
('super_admin', 'Super Administrador - Acesso total ao sistema', datetime('now')),
('admin', 'Administrador - Gerencia usuários e configurações', datetime('now')),
('manager', 'Gerente - Gerencia pedidos e produtos', datetime('now')),
('user', 'Usuário - Acesso básico', datetime('now')),
('guest', 'Visitante - Acesso limitado', datetime('now'));

-- ===================================================================
-- PERMISSÕES PADRÃO (CRUD para cada recurso)
-- ===================================================================

-- Usuários
INSERT OR IGNORE INTO permissions (name, description, created_at) VALUES
('users.view', 'Visualizar usuários', datetime('now')),
('users.create', 'Criar usuários', datetime('now')),
('users.update', 'Atualizar usuários', datetime('now')),
('users.delete', 'Deletar usuários', datetime('now'));

-- Roles e Permissões
INSERT OR IGNORE INTO permissions (name, description, created_at) VALUES
('roles.view', 'Visualizar roles', datetime('now')),
('roles.create', 'Criar roles', datetime('now')),
('roles.update', 'Atualizar roles', datetime('now')),
('roles.delete', 'Deletar roles', datetime('now')),
('permissions.view', 'Visualizar permissões', datetime('now')),
('permissions.create', 'Criar permissões', datetime('now')),
('permissions.update', 'Atualizar permissões', datetime('now')),
('permissions.delete', 'Deletar permissões', datetime('now'));

-- Produtos
INSERT OR IGNORE INTO permissions (name, description, created_at) VALUES
('products.view', 'Visualizar produtos', datetime('now')),
('products.create', 'Criar produtos', datetime('now')),
('products.update', 'Atualizar produtos', datetime('now')),
('products.delete', 'Deletar produtos', datetime('now'));

-- Pedidos
INSERT OR IGNORE INTO permissions (name, description, created_at) VALUES
('orders.view', 'Visualizar pedidos', datetime('now')),
('orders.create', 'Criar pedidos', datetime('now')),
('orders.update', 'Atualizar pedidos', datetime('now')),
('orders.delete', 'Deletar pedidos', datetime('now')),
('orders.cancel', 'Cancelar pedidos', datetime('now')),
('orders.approve', 'Aprovar pedidos', datetime('now'));

-- Categorias
INSERT OR IGNORE INTO permissions (name, description, created_at) VALUES
('categories.view', 'Visualizar categorias', datetime('now')),
('categories.create', 'Criar categorias', datetime('now')),
('categories.update', 'Atualizar categorias', datetime('now')),
('categories.delete', 'Deletar categorias', datetime('now'));

-- Relatórios
INSERT OR IGNORE INTO permissions (name, description, created_at) VALUES
('reports.view', 'Visualizar relatórios', datetime('now')),
('reports.export', 'Exportar relatórios', datetime('now'));

-- Configurações
INSERT OR IGNORE INTO permissions (name, description, created_at) VALUES
('settings.view', 'Visualizar configurações', datetime('now')),
('settings.update', 'Atualizar configurações', datetime('now'));

-- ===================================================================
-- ATRIBUIR PERMISSÕES AOS ROLES
-- ===================================================================

-- Super Admin: TODAS as permissões
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT 
    (SELECT id FROM roles WHERE name = 'super_admin'),
    id,
    datetime('now')
FROM permissions;

-- Admin: Todas exceto gerenciar super admins
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    p.id,
    datetime('now')
FROM permissions p
WHERE p.name NOT LIKE '%super%';

-- Manager: Gerenciar pedidos, produtos, categorias
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT 
    (SELECT id FROM roles WHERE name = 'manager'),
    p.id,
    datetime('now')
FROM permissions p
WHERE p.name LIKE 'orders.%'
   OR p.name LIKE 'products.%'
   OR p.name LIKE 'categories.%'
   OR p.name IN ('reports.view', 'reports.export');

-- User: Visualizar e criar pedidos próprios
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT 
    (SELECT id FROM roles WHERE name = 'user'),
    p.id,
    datetime('now')
FROM permissions p
WHERE p.name IN (
    'products.view',
    'categories.view',
    'orders.view',
    'orders.create'
);

-- Guest: Apenas visualizar produtos e categorias
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT 
    (SELECT id FROM roles WHERE name = 'guest'),
    p.id,
    datetime('now')
FROM permissions p
WHERE p.name IN ('products.view', 'categories.view');

-- ===================================================================
-- VIEWS ÚTEIS
-- ===================================================================

-- View de usuários com seus roles
CREATE VIEW IF NOT EXISTS v_user_roles AS
SELECT 
    u.id as user_id,
    u.email,
    u.name,
    r.id as role_id,
    r.name as role_name,
    r.description as role_description
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id;

-- View de usuários com suas permissões
CREATE VIEW IF NOT EXISTS v_user_permissions AS
SELECT DISTINCT
    u.id as user_id,
    u.email,
    u.name,
    p.id as permission_id,
    p.name as permission_name,
    p.description as permission_description
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN role_permissions rp ON ur.role_id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id;

-- View de roles com contagem de usuários
CREATE VIEW IF NOT EXISTS v_role_stats AS
SELECT 
    r.id,
    r.name,
    r.description,
    COUNT(DISTINCT ur.user_id) as user_count,
    COUNT(DISTINCT rp.permission_id) as permission_count
FROM roles r
LEFT JOIN user_roles ur ON r.id = ur.role_id
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.name, r.description;

-- View de acessos negados (últimas 24h)
CREATE VIEW IF NOT EXISTS v_access_denied AS
SELECT 
    al.*,
    u.email,
    u.name
FROM access_logs al
LEFT JOIN users u ON al.user_id = u.id
WHERE al.granted = 0
  AND al.created_at > datetime('now', '-1 day')
ORDER BY al.created_at DESC;

-- View de atividades por usuário (últimas 24h)
CREATE VIEW IF NOT EXISTS v_user_activity AS
SELECT 
    u.email,
    u.name,
    COUNT(*) as total_actions,
    SUM(CASE WHEN al.granted = 1 THEN 1 ELSE 0 END) as granted_actions,
    SUM(CASE WHEN al.granted = 0 THEN 1 ELSE 0 END) as denied_actions,
    MAX(al.created_at) as last_activity
FROM access_logs al
JOIN users u ON al.user_id = u.id
WHERE al.created_at > datetime('now', '-1 day')
GROUP BY u.id, u.email, u.name
ORDER BY total_actions DESC;

-- ===================================================================
-- TRIGGERS
-- ===================================================================

-- Trigger para atualizar updated_at em roles
CREATE TRIGGER IF NOT EXISTS update_roles_timestamp 
AFTER UPDATE ON roles
BEGIN
    UPDATE roles SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- Trigger para atualizar updated_at em permissions
CREATE TRIGGER IF NOT EXISTS update_permissions_timestamp 
AFTER UPDATE ON permissions
BEGIN
    UPDATE permissions SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- Trigger para cleanup automático de logs antigos
CREATE TRIGGER IF NOT EXISTS cleanup_old_access_logs
AFTER INSERT ON access_logs
BEGIN
    DELETE FROM access_logs 
    WHERE created_at < datetime('now', '-90 days');
END;

-- ===================================================================
-- COMENTÁRIOS E DOCUMENTAÇÃO
-- ===================================================================

-- roles: Define as funções/papéis no sistema (admin, manager, user, guest)
-- permissions: Define as permissões granulares (users.create, orders.view, etc.)
-- user_roles: Associação many-to-many entre usuários e roles
-- role_permissions: Associação many-to-many entre roles e permissões
-- access_logs: Auditoria de todas as verificações de acesso

-- Hierarquia de roles (implementada no código):
-- super_admin > admin > manager > user > guest

-- Convenção de nomes de permissões:
-- {recurso}.{ação}
-- Exemplo: users.create, products.update, orders.delete

-- ===================================================================
-- QUERIES ÚTEIS PARA ADMINISTRAÇÃO
-- ===================================================================

-- Listar usuários com seus roles:
-- SELECT * FROM v_user_roles;

-- Listar usuários com suas permissões:
-- SELECT * FROM v_user_permissions WHERE user_id = 1;

-- Ver estatísticas de roles:
-- SELECT * FROM v_role_stats;

-- Ver tentativas de acesso negadas:
-- SELECT * FROM v_access_denied;

-- Ver atividade de usuários:
-- SELECT * FROM v_user_activity;

-- Atribuir role a usuário:
-- INSERT INTO user_roles (user_id, role_id, created_at)
-- VALUES (1, (SELECT id FROM roles WHERE name = 'admin'), datetime('now'));

-- Remover role de usuário:
-- DELETE FROM user_roles WHERE user_id = 1 AND role_id = 2;

-- Listar todas as permissões de um role:
-- SELECT p.* FROM permissions p
-- JOIN role_permissions rp ON p.id = rp.permission_id
-- WHERE rp.role_id = (SELECT id FROM roles WHERE name = 'admin');

-- ===================================================================
-- MANUTENÇÃO
-- ===================================================================

-- Limpar logs antigos manualmente (90+ dias):
-- DELETE FROM access_logs WHERE created_at < datetime('now', '-90 days');

-- Reindexar (opcional, para performance):
-- REINDEX idx_access_logs_user;
-- REINDEX idx_access_logs_action;
-- REINDEX idx_access_logs_created;
