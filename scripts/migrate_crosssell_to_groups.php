<?php
/**
 * Script de migração: CrossSellRule → CrossSellGroup
 * Converte regras antigas (1:1) para grupos otimizados (1:N)
 */

require_once __DIR__ . '/../app/config/db.php';

try {
    $db = db();
    
    echo "🚀 Iniciando migração de CrossSellRule para CrossSellGroup...\n\n";
    
    // Passo 1: Criar tabela category_cross_sell_groups se não existir
    echo "📋 Passo 1: Criando tabela category_cross_sell_groups...\n";
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS category_cross_sell_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            trigger_category_id INT NOT NULL COMMENT 'Categoria que dispara as recomendações',
            recommendations JSON NOT NULL COMMENT 'Array de objetos: [{category_id, section_title}, ...]',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_company_trigger (company_id, trigger_category_id),
            INDEX idx_active (active),
            
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (trigger_category_id) REFERENCES categories(id) ON DELETE CASCADE,
            
            UNIQUE KEY unique_trigger_category (company_id, trigger_category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Grupos de cross-sell otimizados (1 categoria → N recomendações)'
    ");
    
    echo "✅ Tabela criada/verificada com sucesso!\n\n";
    
    // Passo 2: Verificar se há regras antigas para migrar
    echo "📊 Passo 2: Verificando regras antigas...\n";
    
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM category_cross_sell_rules 
        WHERE active = 1
    ");
    $totalRules = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "   Encontradas {$totalRules} regras ativas para migrar\n\n";
    
    if ($totalRules == 0) {
        echo "ℹ️  Nenhuma regra para migrar. Finalizando...\n";
        exit(0);
    }
    
    // Passo 3: Agrupar regras por company_id + trigger_category_id
    echo "🔄 Passo 3: Agrupando e migrando regras...\n";
    
    $stmt = $db->query("
        SELECT 
            company_id,
            trigger_category_id,
            GROUP_CONCAT(
                JSON_OBJECT(
                    'category_id', recommended_category_id,
                    'section_title', section_title
                )
                ORDER BY priority DESC
                SEPARATOR ','
            ) as recommendations_json
        FROM category_cross_sell_rules
        WHERE active = 1
        GROUP BY company_id, trigger_category_id
    ");
    
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $migratedCount = 0;
    
    foreach ($groups as $group) {
        $companyId = $group['company_id'];
        $triggerCategoryId = $group['trigger_category_id'];
        $recommendations = '[' . $group['recommendations_json'] . ']';
        
        // Inserir ou atualizar grupo
        $insertStmt = $db->prepare("
            INSERT INTO category_cross_sell_groups 
            (company_id, trigger_category_id, recommendations, active, created_at)
            VALUES (?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                recommendations = VALUES(recommendations),
                updated_at = NOW()
        ");
        
        $insertStmt->execute([
            $companyId,
            $triggerCategoryId,
            $recommendations
        ]);
        
        $migratedCount++;
        echo "   ✓ Migrado grupo: Company #{$companyId}, Trigger Category #{$triggerCategoryId}\n";
    }
    
    echo "\n✅ {$migratedCount} grupos migrados com sucesso!\n\n";
    
    // Passo 4: Verificação
    echo "🔍 Passo 4: Verificando migração...\n";
    
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_grupos,
            SUM(JSON_LENGTH(recommendations)) as total_recomendacoes
        FROM category_cross_sell_groups
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Total de grupos: {$stats['total_grupos']}\n";
    echo "   Total de recomendações: {$stats['total_recomendacoes']}\n\n";
    
    // Passo 5: Marcar tabela antiga como descontinuada
    echo "📝 Passo 5: Marcando tabela antiga como descontinuada...\n";
    
    $db->exec("
        ALTER TABLE category_cross_sell_rules 
        COMMENT = 'DESCONTINUADA - Migrada para category_cross_sell_groups. Manter por 30 dias para rollback.'
    ");
    
    echo "✅ Tabela antiga marcada (será mantida por 30 dias para rollback)\n\n";
    
    // Mostrar exemplo
    echo "📄 Exemplo de grupo criado:\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $stmt = $db->query("
        SELECT 
            csg.id,
            csg.company_id,
            csg.trigger_category_id,
            c.name as categoria_disparadora,
            JSON_PRETTY(csg.recommendations) as recomendacoes,
            csg.created_at
        FROM category_cross_sell_groups csg
        JOIN categories c ON csg.trigger_category_id = c.id
        LIMIT 1
    ");
    
    $example = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($example) {
        echo "ID: {$example['id']}\n";
        echo "Categoria Disparadora: {$example['categoria_disparadora']} (#{$example['trigger_category_id']})\n";
        echo "Recomendações:\n{$example['recomendacoes']}\n";
    }
    
    echo "─────────────────────────────────────────────────────────────\n\n";
    echo "🎉 Migração concluída com sucesso!\n\n";
    echo "⚠️  IMPORTANTE:\n";
    echo "   - A tabela 'category_cross_sell_rules' foi mantida para rollback\n";
    echo "   - Você pode removê-la após 30 dias de testes\n";
    echo "   - Comando: DROP TABLE category_cross_sell_rules;\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO na migração:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}
