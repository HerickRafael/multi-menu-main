-- Tabela para persistir sacolas por sessão
CREATE TABLE IF NOT EXISTS cart_sessions (
  session_id VARCHAR(128) NOT NULL,
  cart_json LONGTEXT NULL,
  customizations_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
