-- ================================================================
-- Migração: CrossSellRule → CrossSellGroup
-- Converte regras antigas (1:1) para grupos otimizados (1:N)
-- ================================================================

-- Passo 1: Criar tabela category_cross_sell_groups se não existir
CREATE TABLE IF NOT EXISTS category_cross_sell_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    trigger_category_id INT NOT NULL COMMENT 'Categoria que dispara as recomendações',
    recommendations JSON NOT NULL COMMENT 'Array de objetos: [{category_id, section_title}, ...]',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_company_trigger (company_id, trigger_category_id),
    INDEX idx_active (active),
    
    -- Foreign keys
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (trigger_category_id) REFERENCES categories(id) ON DELETE CASCADE,
    
    -- Uma categoria disparadora só pode ter um grupo de recomendações
    UNIQUE KEY unique_trigger_category (company_id, trigger_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Grupos de cross-sell otimizados (1 categoria → N recomendações)';

-- Passo 2: Migrar dados de category_cross_sell_rules para category_cross_sell_groups
-- Agrupa múltiplas regras da mesma trigger_category em um único grupo JSON

INSERT INTO category_cross_sell_groups (company_id, trigger_category_id, recommendations, active, created_at)
SELECT 
    company_id,
    trigger_category_id,
    CONCAT('[',
        GROUP_CONCAT(
            JSON_OBJECT(
                'category_id', recommended_category_id,
                'section_title', section_title
            )
            ORDER BY priority DESC
            SEPARATOR ','
        ),
    ']') as recommendations,
    1 as active,
    NOW() as created_at
FROM category_cross_sell_rules
WHERE active = 1
GROUP BY company_id, trigger_category_id
ON DUPLICATE KEY UPDATE
    recommendations = VALUES(recommendations),
    updated_at = NOW();

-- Passo 3: (OPCIONAL) Desativar tabela antiga - NÃO DELETAR ainda para permitir rollback
-- Renomear tabela antiga para backup
-- RENAME TABLE category_cross_sell_rules TO category_cross_sell_rules_backup;

-- OU Adicionar comentário de descontinuação
ALTER TABLE category_cross_sell_rules 
COMMENT = 'DESCONTINUADA - Migrada para category_cross_sell_groups. Manter por 30 dias para rollback.';

-- ================================================================
-- Verificação pós-migração
-- ================================================================

-- Ver quantos grupos foram criados
SELECT 
    COUNT(*) as total_grupos,
    SUM(JSON_LENGTH(recommendations)) as total_recomendacoes
FROM category_cross_sell_groups;

-- Comparar com tabela antiga
SELECT 
    COUNT(*) as total_regras_antigas
FROM category_cross_sell_rules
WHERE active = 1;

-- Ver exemplo de grupo criado
SELECT 
    csg.id,
    csg.trigger_category_id,
    c.name as categoria_disparadora,
    JSON_PRETTY(csg.recommendations) as recomendacoes,
    csg.created_at
FROM category_cross_sell_groups csg
JOIN categories c ON csg.trigger_category_id = c.id
LIMIT 1;
