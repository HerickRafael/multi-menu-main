INSERT IGNORE INTO permissions (permission_key, module, action, description) VALUES
('billing.read', 'billing', 'read', 'Visualizar dados de billing SaaS'),
('billing.manage', 'billing', 'manage', 'Criar assinatura/fatura e confirmar pagamentos no billing SaaS');

-- Super Admin: acesso total ao billing.
INSERT IGNORE INTO role_permissions (role_id, permission_id, allowed)
SELECT r.id, p.id, 1
FROM roles r
JOIN permissions p ON p.permission_key IN ('billing.read', 'billing.manage')
WHERE r.slug = 'super_admin';

-- Admin e Support: acesso somente leitura.
INSERT IGNORE INTO role_permissions (role_id, permission_id, allowed)
SELECT r.id, p.id, 1
FROM roles r
JOIN permissions p ON p.permission_key IN ('billing.read')
WHERE r.slug IN ('admin', 'support');
