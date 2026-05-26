INSERT IGNORE INTO permissions (permission_key, module, action, description) VALUES
('observability.read', 'observability', 'read', 'Visualizar dashboard de observabilidade'),
('observability.run_checks', 'observability', 'run_checks', 'Executar health checks da plataforma'),
('events.read', 'events', 'read', 'Visualizar trilha de eventos do sistema'),
('events.dispatch', 'events', 'dispatch', 'Disparar eventos de teste');

-- Super Admin: acesso total aos novos recursos da fase 6.
INSERT IGNORE INTO role_permissions (role_id, permission_id, allowed)
SELECT r.id, p.id, 1
FROM roles r
JOIN permissions p ON p.permission_key IN (
    'observability.read',
    'observability.run_checks',
    'events.read',
    'events.dispatch'
)
WHERE r.slug = 'super_admin';

-- Admin: pode consultar observabilidade e eventos, sem disparar testes.
INSERT IGNORE INTO role_permissions (role_id, permission_id, allowed)
SELECT r.id, p.id, 1
FROM roles r
JOIN permissions p ON p.permission_key IN (
    'observability.read',
    'events.read'
)
WHERE r.slug = 'admin';

-- Support: somente leitura operacional.
INSERT IGNORE INTO role_permissions (role_id, permission_id, allowed)
SELECT r.id, p.id, 1
FROM roles r
JOIN permissions p ON p.permission_key IN (
    'observability.read',
    'events.read'
)
WHERE r.slug = 'support';
