-- Migration: adicionar índice UNIQUE para (company_id, type, icon)
-- Data: 2025-10-09

-- Observação: usamos prefixo para a coluna icon (191) para compatibilidade com utf8mb4.
ALTER TABLE `payment_methods`
  ADD UNIQUE INDEX `uk_payment_methods_company_type_icon` (`company_id`, `type`, `icon`(191));
