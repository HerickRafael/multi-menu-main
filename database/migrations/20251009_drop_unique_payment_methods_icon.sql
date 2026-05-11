-- Rollback: remover índice UNIQUE uk_payment_methods_company_type_icon
-- Data: 2025-10-09
-- Este script tenta remover o índice apenas se ele existir no esquema atual.

-- Nota: alguns clientes MySQL não aceitam DROP INDEX IF EXISTS diretamente. Aqui usamos um bloqueio
-- com INFORMATION_SCHEMA para executar o ALTER TABLE somente quando o índice existir.

SET @exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'payment_methods'
    AND INDEX_NAME = 'uk_payment_methods_company_type_icon'
);

SELECT CONCAT('Índice encontrado? ', @exists) AS info;

SET @stmt_sql = IF(@exists > 0,
  'ALTER TABLE `payment_methods` DROP INDEX `uk_payment_methods_company_type_icon`',
  'SELECT "INDEX_NOT_FOUND" as status'
);

PREPARE stmt FROM @stmt_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fim.
