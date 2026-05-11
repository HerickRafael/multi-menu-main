CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (slug, name, description, is_system) VALUES
('super_admin', 'Super Admin', 'Acesso total ao painel global', 1),
('admin', 'Admin', 'Gestao operacional ampla', 1),
('support', 'Support', 'Suporte com acesso controlado', 1),
('finance', 'Finance', 'Acesso a relatorios financeiros e conciliacao', 1),
('operator', 'Operator', 'Operacao diaria com permissoes reduzidas', 1);
