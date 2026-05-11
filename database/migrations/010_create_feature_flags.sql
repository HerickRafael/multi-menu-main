CREATE TABLE IF NOT EXISTS feature_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flag_key VARCHAR(120) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    default_enabled TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feature_flags_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO feature_flags (flag_key, name, description, default_enabled, is_active) VALUES
('whatsapp.monitoring', 'WhatsApp Monitoring', 'Monitora status de instancias WhatsApp por loja', 1, 1),
('orders.timeline_v2', 'Order Timeline V2', 'Timeline detalhada de pedidos para operacao', 1, 1),
('admin.bulk_actions', 'Bulk Actions Admin', 'Acoes em lote no painel super admin', 0, 1),
('security.strict_tenant', 'Strict Tenant Isolation', 'Bloqueio rigoroso de escopo multi-tenant', 1, 1);
