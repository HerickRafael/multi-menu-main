CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(140) NOT NULL UNIQUE,
    module VARCHAR(80) NOT NULL,
    action VARCHAR(80) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permissions_module_action (module, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (permission_key, module, action, description) VALUES
('stores.read', 'stores', 'read', 'Visualizar lojas'),
('stores.update', 'stores', 'update', 'Editar lojas'),
('stores.status', 'stores', 'status', 'Suspender/ativar/manutencao de lojas'),
('audit.read', 'audit', 'read', 'Visualizar auditoria'),
('orders.monitor', 'orders', 'monitor', 'Monitorar pedidos globais'),
('webhooks.retry', 'webhooks', 'retry', 'Reprocessar webhooks'),
('queues.retry', 'queues', 'retry', 'Reprocessar jobs de fila'),
('flags.manage', 'feature_flags', 'manage', 'Gerenciar feature flags'),
('rbac.manage', 'rbac', 'manage', 'Gerenciar papeis e permissoes'),
('whatsapp.monitor', 'whatsapp', 'monitor', 'Visualizar monitoramento de WhatsApp');
