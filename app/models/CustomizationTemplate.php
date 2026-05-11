<?php

declare(strict_types=1);

/**
 * Model para Templates de Personalização
 * Permite criar grupos de personalização reutilizáveis entre produtos
 */

require_once __DIR__ . '/../config/db.php';

class CustomizationTemplate
{
    /**
     * Lista todos os templates de uma empresa
     */
    public static function all(int $companyId, bool $onlyActive = true): array
    {
        $pdo = db();
        $sql = 'SELECT * FROM customization_templates WHERE company_id = ?';
        if ($onlyActive) {
            $sql .= ' AND active = 1';
        }
        $sql .= ' ORDER BY name ASC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista todos os templates de uma empresa com seus itens
     */
    public static function listWithItemsForCompany(int $companyId, bool $onlyActive = true): array
    {
        $templates = self::all($companyId, $onlyActive);
        
        foreach ($templates as &$template) {
            $template['items'] = self::getItems((int)$template['id']);
        }
        
        return $templates;
    }

    /**
     * Busca um template pelo ID
     */
    public static function find(int $id): ?array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM customization_templates WHERE id = ?');
        $stmt->execute([$id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        return $template ?: null;
    }

    /**
     * Busca um template com seus itens
     */
    public static function findWithItems(int $id): ?array
    {
        $template = self::find($id);
        if (!$template) {
            return null;
        }
        
        $template['items'] = self::getItems($id);
        return $template;
    }

    /**
     * Lista os itens de um template
     */
    public static function getItems(int $templateId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('
            SELECT cti.*, i.name as ingredient_name, i.internal_name, i.image_path
            FROM customization_template_items cti
            LEFT JOIN ingredients i ON i.id = cti.ingredient_id
            WHERE cti.template_id = ?
            ORDER BY cti.sort_order ASC
        ');
        $stmt->execute([$templateId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo template
     */
    public static function create(array $data): int
    {
        $pdo = db();
        $stmt = $pdo->prepare('
            INSERT INTO customization_templates (company_id, name, type, min_qty, max_qty, active, hide_duplicates)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['company_id'],
            $data['name'],
            $data['type'] ?? 'extra',
            $data['min_qty'] ?? 0,
            $data['max_qty'] ?? 99,
            $data['active'] ?? 1,
            $data['hide_duplicates'] ?? 0
        ]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Atualiza um template
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = db();
        $stmt = $pdo->prepare('
            UPDATE customization_templates 
            SET name = ?, type = ?, min_qty = ?, max_qty = ?, active = ?, hide_duplicates = ?, updated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([
            $data['name'],
            $data['type'] ?? 'extra',
            $data['min_qty'] ?? 0,
            $data['max_qty'] ?? 99,
            $data['active'] ?? 1,
            $data['hide_duplicates'] ?? 0,
            $id
        ]);
    }

    /**
     * Remove um template
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        $stmt = $pdo->prepare('DELETE FROM customization_templates WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Toggle ativo/inativo
     */
    public static function toggleActive(int $id): bool
    {
        $pdo = db();
        $stmt = $pdo->prepare('
            UPDATE customization_templates 
            SET active = NOT active, updated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }

    /**
     * Salva os itens de um template
     */
    public static function saveItems(int $templateId, array $items): void
    {
        $pdo = db();
        
        // Remove itens existentes
        $pdo->prepare('DELETE FROM customization_template_items WHERE template_id = ?')
            ->execute([$templateId]);
        
        if (empty($items)) {
            return;
        }
        
        $stmt = $pdo->prepare('
            INSERT INTO customization_template_items 
            (template_id, ingredient_id, label, delta, is_default, default_qty, min_qty, max_qty, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        foreach ($items as $index => $item) {
            $ingredientId = !empty($item['ingredient_id']) ? (int)$item['ingredient_id'] : null;
            $stmt->execute([
                $templateId,
                $ingredientId,
                $item['label'] ?? '',
                (float)($item['delta'] ?? 0),
                !empty($item['is_default']) ? 1 : 0,
                (int)($item['default_qty'] ?? 1),
                (int)($item['min_qty'] ?? 0),
                (int)($item['max_qty'] ?? 1),
                $item['sort_order'] ?? $index
            ]);
        }
    }

    /**
     * Lista os produtos que usam um template
     * Busca tanto pela tabela de vínculos quanto por nome similar
     */
    public static function getProductsUsingTemplate(int $templateId): array
    {
        $pdo = db();
        
        // Buscar nome do template
        $stmtName = $pdo->prepare('SELECT name, company_id FROM customization_templates WHERE id = ?');
        $stmtName->execute([$templateId]);
        $templateData = $stmtName->fetch(PDO::FETCH_ASSOC);
        
        if (!$templateData) {
            return [];
        }
        
        // Buscar produtos:
        // 1. Pela tabela de vínculos (product_custom_group_templates)
        // 2. Por grupos com nome igual ao template
        $stmt = $pdo->prepare('
            SELECT DISTINCT p.id, p.name, p.sku
            FROM products p
            INNER JOIN product_custom_groups pcg ON pcg.product_id = p.id
            LEFT JOIN product_custom_group_templates pcgt ON pcgt.group_id = pcg.id AND pcgt.template_id = ?
            WHERE p.company_id = ?
              AND (pcgt.template_id IS NOT NULL OR LOWER(TRIM(pcg.name)) = LOWER(TRIM(?)))
            ORDER BY p.name ASC
        ');
        $stmt->execute([$templateId, $templateData['company_id'], $templateData['name']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta quantos produtos usam um template
     * Busca tanto pela tabela de vínculos quanto por nome similar
     */
    public static function countProductsUsingTemplate(int $templateId): int
    {
        $pdo = db();
        
        // Buscar nome do template
        $stmtName = $pdo->prepare('SELECT name, company_id FROM customization_templates WHERE id = ?');
        $stmtName->execute([$templateId]);
        $templateData = $stmtName->fetch(PDO::FETCH_ASSOC);
        
        if (!$templateData) {
            return 0;
        }
        
        $stmt = $pdo->prepare('
            SELECT COUNT(DISTINCT p.id) as total
            FROM products p
            INNER JOIN product_custom_groups pcg ON pcg.product_id = p.id
            LEFT JOIN product_custom_group_templates pcgt ON pcgt.group_id = pcg.id AND pcgt.template_id = ?
            WHERE p.company_id = ?
              AND (pcgt.template_id IS NOT NULL OR LOWER(TRIM(pcg.name)) = LOWER(TRIM(?)))
        ');
        $stmt->execute([$templateId, $templateData['company_id'], $templateData['name']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Copia um template para um produto (cria um grupo de personalização)
     * Retorna o ID do novo grupo criado
     */
    public static function copyToProduct(int $templateId, int $productId, bool $keepSync = false): int
    {
        $template = self::findWithItems($templateId);
        if (!$template) {
            throw new Exception('Template não encontrado');
        }
        
        $pdo = db();
        $pdo->beginTransaction();
        
        try {
            // Determinar a próxima ordem
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 as next_order FROM product_custom_groups WHERE product_id = ?');
            $stmt->execute([$productId]);
            $nextOrder = (int)$stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
            
            // Criar o grupo
            $stmt = $pdo->prepare('
                INSERT INTO product_custom_groups (product_id, name, type, min_qty, max_qty, hide_duplicates, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $productId,
                $template['name'],
                $template['type'],
                $template['min_qty'],
                $template['max_qty'],
                $template['hide_duplicates'] ?? 0,
                $nextOrder
            ]);
            $groupId = (int)$pdo->lastInsertId();
            
            // Copiar os itens
            $stmtItem = $pdo->prepare('
                INSERT INTO product_custom_items 
                (group_id, ingredient_id, label, delta, is_default, default_qty, min_qty, max_qty, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            foreach ($template['items'] as $item) {
                $stmtItem->execute([
                    $groupId,
                    $item['ingredient_id'],
                    $item['label'],
                    $item['delta'],
                    $item['is_default'],
                    $item['default_qty'],
                    $item['min_qty'],
                    $item['max_qty'],
                    $item['sort_order']
                ]);
            }
            
            // Registrar vínculo com o template
            $stmt = $pdo->prepare('
                INSERT INTO product_custom_group_templates (group_id, template_id, synced)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$groupId, $templateId, $keepSync ? 1 : 0]);
            
            $pdo->commit();
            return $groupId;
            
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Cria um template a partir de um grupo existente de um produto
     */
    public static function createFromProductGroup(int $groupId, int $companyId, ?string $name = null): int
    {
        $pdo = db();
        
        // Buscar dados do grupo
        $stmt = $pdo->prepare('SELECT * FROM product_custom_groups WHERE id = ?');
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            throw new Exception('Grupo não encontrado');
        }
        
        // Buscar itens do grupo
        $stmt = $pdo->prepare('SELECT * FROM product_custom_items WHERE group_id = ? ORDER BY sort_order ASC');
        $stmt->execute([$groupId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pdo->beginTransaction();
        
        try {
            // Criar template
            $templateId = self::create([
                'company_id' => $companyId,
                'name' => $name ?? $group['name'],
                'type' => $group['type'],
                'min_qty' => $group['min_qty'],
                'max_qty' => $group['max_qty'],
                'active' => 1
            ]);
            
            // Copiar itens para o template
            $templateItems = array_map(function($item) {
                return [
                    'ingredient_id' => $item['ingredient_id'],
                    'label' => $item['label'],
                    'delta' => $item['delta'],
                    'is_default' => $item['is_default'],
                    'default_qty' => $item['default_qty'],
                    'min_qty' => $item['min_qty'],
                    'max_qty' => $item['max_qty'],
                    'sort_order' => $item['sort_order']
                ];
            }, $items);
            
            self::saveItems($templateId, $templateItems);
            
            $pdo->commit();
            return $templateId;
            
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Sincroniza as alterações de um template para todos os grupos de produtos vinculados
     * Atualiza nome, tipo, min/max e todos os itens
     * Busca tanto pela tabela de vínculos quanto por grupos com nome igual
     */
    public static function syncToLinkedProducts(int $templateId): int
    {
        $template = self::findWithItems($templateId);
        if (!$template) {
            throw new Exception('Template não encontrado');
        }
        
        $pdo = db();
        
        // Buscar todos os grupos:
        // 1. Vinculados explicitamente na tabela product_custom_group_templates
        // 2. OU que tenham o mesmo nome do template (para grupos criados antes do sistema de vínculos)
        $stmt = $pdo->prepare('
            SELECT DISTINCT pcg.id as group_id, pcg.product_id
            FROM product_custom_groups pcg
            INNER JOIN products p ON p.id = pcg.product_id
            LEFT JOIN product_custom_group_templates pcgt ON pcgt.group_id = pcg.id AND pcgt.template_id = ?
            WHERE p.company_id = ?
              AND (pcgt.template_id IS NOT NULL OR LOWER(TRIM(pcg.name)) = LOWER(TRIM(?)))
        ');
        $stmt->execute([$templateId, $template['company_id'], $template['name']]);
        $linkedGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($linkedGroups)) {
            return 0;
        }
        
        $pdo->beginTransaction();
        
        try {
            $updatedCount = 0;
            
            foreach ($linkedGroups as $linked) {
                $groupId = (int)$linked['group_id'];
                
                // Atualizar o grupo (nome, tipo, min/max, hide_duplicates)
                $stmtUpdate = $pdo->prepare('
                    UPDATE product_custom_groups 
                    SET name = ?, type = ?, min_qty = ?, max_qty = ?, hide_duplicates = ?
                    WHERE id = ?
                ');
                $stmtUpdate->execute([
                    $template['name'],
                    $template['type'],
                    $template['min_qty'],
                    $template['max_qty'],
                    $template['hide_duplicates'] ?? 0,
                    $groupId
                ]);
                
                // Remover itens antigos do grupo
                $stmtDelete = $pdo->prepare('DELETE FROM product_custom_items WHERE group_id = ?');
                $stmtDelete->execute([$groupId]);
                
                // Inserir os novos itens do template
                $stmtItem = $pdo->prepare('
                    INSERT INTO product_custom_items 
                    (group_id, ingredient_id, label, delta, is_default, default_qty, min_qty, max_qty, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                
                foreach ($template['items'] as $item) {
                    $stmtItem->execute([
                        $groupId,
                        $item['ingredient_id'],
                        $item['label'],
                        $item['delta'],
                        $item['is_default'],
                        $item['default_qty'],
                        $item['min_qty'],
                        $item['max_qty'],
                        $item['sort_order']
                    ]);
                }
                
                // Criar vínculo se não existir
                $stmtCheck = $pdo->prepare('SELECT id FROM product_custom_group_templates WHERE group_id = ?');
                $stmtCheck->execute([$groupId]);
                if (!$stmtCheck->fetch()) {
                    $stmtLink = $pdo->prepare('INSERT INTO product_custom_group_templates (group_id, template_id, synced) VALUES (?, ?, 1)');
                    $stmtLink->execute([$groupId, $templateId]);
                }
                
                $updatedCount++;
            }
            
            $pdo->commit();
            return $updatedCount;
            
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Busca templates com contagem de produtos usando cada um
     */
    public static function allWithUsageCount(int $companyId, bool $onlyActive = true): array
    {
        $pdo = db();
        $sql = '
            SELECT ct.*, 
                   COUNT(DISTINCT pcg.product_id) as products_count,
                   GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ", ") as products_names
            FROM customization_templates ct
            LEFT JOIN product_custom_group_templates pcgt ON pcgt.template_id = ct.id
            LEFT JOIN product_custom_groups pcg ON pcg.id = pcgt.group_id
            LEFT JOIN products p ON p.id = pcg.product_id
            WHERE ct.company_id = ?
        ';
        if ($onlyActive) {
            $sql .= ' AND ct.active = 1';
        }
        $sql .= ' GROUP BY ct.id ORDER BY ct.name ASC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca templates por termo de busca
     */
    public static function search(int $companyId, string $query, int $limit = 20): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('
            SELECT ct.*, 
                   COUNT(DISTINCT pcg.product_id) as products_count,
                   GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ", ") as products_names
            FROM customization_templates ct
            LEFT JOIN product_custom_group_templates pcgt ON pcgt.template_id = ct.id
            LEFT JOIN product_custom_groups pcg ON pcg.id = pcgt.group_id
            LEFT JOIN products p ON p.id = pcg.product_id
            WHERE ct.company_id = ? AND ct.active = 1 AND ct.name LIKE ?
            GROUP BY ct.id
            ORDER BY ct.name ASC
            LIMIT ?
        ');
        $stmt->execute([$companyId, "%{$query}%", $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
