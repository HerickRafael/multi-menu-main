-- Cadastro de ingredientes e tabelas de personalização de produtos

CREATE TABLE IF NOT EXISTS ingredients (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id  INT(11)      NOT NULL,
  name        VARCHAR(200) NOT NULL,
  min_qty     INT          NOT NULL DEFAULT 0,
  max_qty     INT          NOT NULL DEFAULT 1,
  image_path  VARCHAR(255) DEFAULT NULL,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ingredients_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_custom_groups (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT(11) NOT NULL,
  name       VARCHAR(200) NOT NULL,
  type       ENUM('single','extra','addon','component') NOT NULL DEFAULT 'extra',
  min_qty    INT NOT NULL DEFAULT 0,
  max_qty    INT NOT NULL DEFAULT 99,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_pcg_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_custom_items (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id      INT UNSIGNED NOT NULL,
  ingredient_id INT UNSIGNED DEFAULT NULL,
  label         VARCHAR(200) NOT NULL,
  delta         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_default    TINYINT(1) NOT NULL DEFAULT 0,
  default_qty   INT NOT NULL DEFAULT 1,
  min_qty       INT NOT NULL DEFAULT 0,
  max_qty       INT NOT NULL DEFAULT 1,
  sort_order    INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_pci_group      FOREIGN KEY (group_id) REFERENCES product_custom_groups(id) ON DELETE CASCADE,
  CONSTRAINT fk_pci_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
