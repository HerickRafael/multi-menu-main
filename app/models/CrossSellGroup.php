<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

/**
 * Model: CrossSellGroup
 * Gerencia grupos de cross-sell otimizados (1 categoria disparadora → N categorias recomendadas)
 */
class CrossSellGroup
{
    /**
     * Obtém a conexão com o banco de dados
     */
    private static function getDb(): PDO
    {
        return db();
    }

    /**
     * Busca todos os grupos de cross-sell da empresa
     * @param bool $onlyActive Se true, retorna apenas grupos ativos
     */
    public static function getByCompany(int $companyId, bool $onlyActive = false): array
    {
        $sql = '
            SELECT 
                csg.*,
                c.name as trigger_category_name
            FROM category_cross_sell_groups csg
            JOIN categories c ON csg.trigger_category_id = c.id
            WHERE csg.company_id = ?
        ';
        
        if ($onlyActive) {
            $sql .= ' AND csg.active = 1';
        }
        
        $sql .= ' ORDER BY c.name ASC';
        
        $stmt = self::getDb()->prepare($sql);
        $stmt->execute([$companyId]);
        
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Decodificar JSON recommendations
        foreach ($groups as &$group) {
            $group['recommendations'] = json_decode($group['recommendations'], true) ?? [];
        }
        
        return $groups;
    }

    /**
     * Busca um grupo específico por ID
     */
    public static function findById(int $id): ?array
    {
        $stmt = self::getDb()->prepare('
            SELECT * FROM category_cross_sell_groups WHERE id = ?
        ');
        $stmt->execute([$id]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($group) {
            $group['recommendations'] = json_decode($group['recommendations'], true) ?? [];
        }
        
        return $group ?: null;
    }

    /**
     * Busca grupo pela categoria disparadora
     */
    public static function findByTriggerCategory(int $companyId, int $triggerCategoryId): ?array
    {
        $stmt = self::getDb()->prepare('
            SELECT * FROM category_cross_sell_groups 
            WHERE company_id = ? AND trigger_category_id = ? AND active = 1
        ');
        $stmt->execute([$companyId, $triggerCategoryId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($group) {
            $group['recommendations'] = json_decode($group['recommendations'], true) ?? [];
        }
        
        return $group ?: null;
    }

    /**
     * Cria ou atualiza um grupo de cross-sell
     */
    public static function createOrUpdate(int $companyId, int $triggerCategoryId, array $recommendations): int
    {
        // Validar que recommendations é um array não vazio
        if (empty($recommendations)) {
            throw new Exception('É necessário pelo menos uma recomendação');
        }

        // Validar estrutura de cada recomendação
        foreach ($recommendations as $rec) {
            if (!isset($rec['category_id']) || !isset($rec['section_title'])) {
                throw new Exception('Cada recomendação deve ter category_id e section_title');
            }
        }

        $recommendationsJson = json_encode($recommendations);

        // Verificar se já existe
        $existing = self::findByTriggerCategory($companyId, $triggerCategoryId);

        if ($existing) {
            // Atualizar existente
            $stmt = self::getDb()->prepare('
                UPDATE category_cross_sell_groups 
                SET recommendations = ?, updated_at = NOW()
                WHERE company_id = ? AND trigger_category_id = ?
            ');
            $stmt->execute([$recommendationsJson, $companyId, $triggerCategoryId]);
            return (int)$existing['id'];
        } else {
            // Criar novo
            $stmt = self::getDb()->prepare('
                INSERT INTO category_cross_sell_groups 
                (company_id, trigger_category_id, recommendations, created_at)
                VALUES (?, ?, ?, NOW())
            ');
            $stmt->execute([$companyId, $triggerCategoryId, $recommendationsJson]);
            return (int)self::getDb()->lastInsertId();
        }
    }

    /**
     * Atualiza apenas o status (ativo/inativo)
     */
    public static function toggleActive(int $id): bool
    {
        $stmt = self::getDb()->prepare('
            UPDATE category_cross_sell_groups 
            SET active = NOT active
            WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }

    /**
     * Deleta um grupo
     */
    public static function delete(int $id): bool
    {
        $stmt = self::getDb()->prepare('
            DELETE FROM category_cross_sell_groups WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }

    /**
     * Busca produtos recomendados para uma categoria (para exibição pública)
     * Retorna array de seções, cada uma com título e produtos
     */
    public static function getRecommendationsForCategory(int $companyId, int $categoryId, int $limit = 4): array
    {
        // Buscar o grupo de recomendações
        $group = self::findByTriggerCategory($companyId, $categoryId);
        
        if (!$group || empty($group['recommendations'])) {
            return [];
        }

        $sections = [];

        foreach ($group['recommendations'] as $recommendation) {
            $recommendedCategoryId = (int)$recommendation['category_id'];
            $sectionTitle = $recommendation['section_title'];

            // Buscar produtos da categoria recomendada (excluindo combos)
            // Nota: LIMIT não aceita prepared statement, usamos intval() para segurança
            $limitValue = (int)$limit;
            $stmt = self::getDb()->prepare("
                SELECT p.id, p.name, p.description, p.price, p.promo_price, p.image,
                       COUNT(DISTINCT pcg.id) as has_ingredients
                FROM products p
                LEFT JOIN product_custom_groups pcg ON p.id = pcg.product_id
                WHERE p.company_id = ? 
                  AND p.category_id = ?
                  AND p.active = 1
                  AND p.type = 'simple'
                GROUP BY p.id, p.name, p.description, p.price, p.promo_price, p.image
                ORDER BY RAND()
                LIMIT {$limitValue}
            ");
            $stmt->execute([$companyId, $recommendedCategoryId]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($products)) {
                $sections[] = [
                    'title' => $sectionTitle,
                    'products' => $products
                ];
            }
        }

        return $sections;
    }
}
