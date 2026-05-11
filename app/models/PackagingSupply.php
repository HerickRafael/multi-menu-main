<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Model para Insumos/Embalagens
 */
class PackagingSupply
{
    /**
     * Lista todos os insumos de uma empresa
     */
    public static function listByCompany(int $companyId, bool $onlyActive = true): array
    {
        $pdo = db();
        $sql = 'SELECT * FROM packaging_supplies WHERE company_id = ?';
        
        if ($onlyActive) {
            $sql .= ' AND active = 1';
        }
        
        $sql .= ' ORDER BY name ASC';
        
        $st = $pdo->prepare($sql);
        $st->execute([$companyId]);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca insumo por ID
     */
    public static function find(int $id): ?array
    {
        $pdo = db();
        $st = $pdo->prepare('SELECT * FROM packaging_supplies WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

    /**
     * Busca insumo por ID e empresa
     */
    public static function findByCompany(int $id, int $companyId): ?array
    {
        $pdo = db();
        $st = $pdo->prepare('SELECT * FROM packaging_supplies WHERE id = ? AND company_id = ?');
        $st->execute([$id, $companyId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

    /**
     * Verifica se nome já existe
     */
    public static function existsByName(int $companyId, string $name, ?int $ignoreId = null): bool
    {
        $pdo = db();
        $sql = 'SELECT id FROM packaging_supplies WHERE company_id = ? AND LOWER(name) = LOWER(?)';
        $params = [$companyId, $name];
        
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        
        $st = $pdo->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        
        return (bool)$st->fetchColumn();
    }

    /**
     * Cria novo insumo
     */
    public static function create(array $data): int
    {
        $pdo = db();
        
        $st = $pdo->prepare('
            INSERT INTO packaging_supplies 
            (company_id, name, description, unit, cost_per_unit, stock_quantity, min_stock_alert, supplier, active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $st->execute([
            $data['company_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['unit'] ?? 'un',
            $data['cost_per_unit'] ?? 0,
            $data['stock_quantity'] ?? 0,
            $data['min_stock_alert'] ?? 0,
            $data['supplier'] ?? null,
            $data['active'] ?? 1,
        ]);
        
        return (int)$pdo->lastInsertId();
    }

    /**
     * Atualiza insumo
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = db();
        
        $st = $pdo->prepare('
            UPDATE packaging_supplies SET
                name = ?,
                description = ?,
                unit = ?,
                cost_per_unit = ?,
                stock_quantity = ?,
                min_stock_alert = ?,
                supplier = ?,
                active = ?
            WHERE id = ?
        ');
        
        return $st->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['unit'] ?? 'un',
            $data['cost_per_unit'] ?? 0,
            $data['stock_quantity'] ?? 0,
            $data['min_stock_alert'] ?? 0,
            $data['supplier'] ?? null,
            $data['active'] ?? 1,
            $id,
        ]);
    }

    /**
     * Remove insumo (soft delete ou hard delete)
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        
        // Verificar se está vinculado a algum produto
        $st = $pdo->prepare('SELECT COUNT(*) FROM product_packaging WHERE supply_id = ?');
        $st->execute([$id]);
        $count = (int)$st->fetchColumn();
        
        if ($count > 0) {
            // Soft delete - apenas desativa
            $st = $pdo->prepare('UPDATE packaging_supplies SET active = 0 WHERE id = ?');
            return $st->execute([$id]);
        }
        
        // Hard delete - remove completamente
        $st = $pdo->prepare('DELETE FROM packaging_supplies WHERE id = ?');
        return $st->execute([$id]);
    }

    /**
     * Lista embalagens vinculadas a um produto
     */
    public static function getByProduct(int $productId): array
    {
        $pdo = db();
        $st = $pdo->prepare('
            SELECT pp.*, ps.name, ps.unit, ps.cost_per_unit,
                   (pp.quantity * ps.cost_per_unit) as total_cost
            FROM product_packaging pp
            JOIN packaging_supplies ps ON ps.id = pp.supply_id
            WHERE pp.product_id = ?
            ORDER BY ps.name
        ');
        $st->execute([$productId]);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Calcula custo total de embalagens de um produto
     */
    public static function getProductPackagingCost(int $productId): float
    {
        $pdo = db();
        $st = $pdo->prepare('
            SELECT SUM(pp.quantity * ps.cost_per_unit) as total
            FROM product_packaging pp
            JOIN packaging_supplies ps ON ps.id = pp.supply_id
            WHERE pp.product_id = ?
        ');
        $st->execute([$productId]);
        
        return (float)($st->fetchColumn() ?: 0);
    }

    /**
     * Vincula embalagem a um produto
     */
    public static function linkToProduct(int $productId, int $supplyId, float $quantity, ?string $notes = null): bool
    {
        $pdo = db();
        
        $st = $pdo->prepare('
            INSERT INTO product_packaging (product_id, supply_id, quantity, notes)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = ?, notes = ?
        ');
        
        return $st->execute([$productId, $supplyId, $quantity, $notes, $quantity, $notes]);
    }

    /**
     * Remove vínculo de embalagem com produto
     */
    public static function unlinkFromProduct(int $productId, int $supplyId): bool
    {
        $pdo = db();
        $st = $pdo->prepare('DELETE FROM product_packaging WHERE product_id = ? AND supply_id = ?');
        return $st->execute([$productId, $supplyId]);
    }

    /**
     * Atualiza todos os vínculos de embalagem de um produto
     */
    public static function syncProductPackaging(int $productId, array $packaging): void
    {
        $pdo = db();
        
        // Remove todos os vínculos existentes
        $st = $pdo->prepare('DELETE FROM product_packaging WHERE product_id = ?');
        $st->execute([$productId]);
        
        // Adiciona os novos vínculos
        if (!empty($packaging)) {
            $st = $pdo->prepare('
                INSERT INTO product_packaging (product_id, supply_id, quantity, notes)
                VALUES (?, ?, ?, ?)
            ');
            
            foreach ($packaging as $item) {
                if (!empty($item['supply_id']) && (float)($item['quantity'] ?? 0) > 0) {
                    $st->execute([
                        $productId,
                        $item['supply_id'],
                        $item['quantity'],
                        $item['notes'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Conta produtos que usam determinado insumo
     */
    public static function countProductsUsing(int $supplyId): int
    {
        $pdo = db();
        $st = $pdo->prepare('SELECT COUNT(DISTINCT product_id) FROM product_packaging WHERE supply_id = ?');
        $st->execute([$supplyId]);
        
        return (int)$st->fetchColumn();
    }

    /**
     * Lista produtos que usam determinado insumo
     */
    public static function getProductsUsing(int $supplyId): array
    {
        $pdo = db();
        $st = $pdo->prepare('
            SELECT p.id, p.name, p.image, pp.quantity, ps.unit,
                   (pp.quantity * ps.cost_per_unit) as cost
            FROM product_packaging pp
            JOIN products p ON p.id = pp.product_id
            JOIN packaging_supplies ps ON ps.id = pp.supply_id
            WHERE pp.supply_id = ?
            ORDER BY p.name
        ');
        $st->execute([$supplyId]);
        
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Recalcula e atualiza os custos de embalagem de todos os produtos que usam um insumo específico
     * Chamado quando o preço de um insumo é alterado
     */
    public static function recalculateProductCostsForSupply(int $supplyId, int $companyId): int
    {
        $pdo = db();
        
        // Buscar todos os produtos que usam esse insumo
        $st = $pdo->prepare('
            SELECT DISTINCT pp.product_id 
            FROM product_packaging pp 
            JOIN products p ON p.id = pp.product_id
            WHERE pp.supply_id = ? AND p.company_id = ?
        ');
        $st->execute([$supplyId, $companyId]);
        $productIds = $st->fetchAll(PDO::FETCH_COLUMN);
        
        $updated = 0;
        
        foreach ($productIds as $productId) {
            // Recalcular custo total de embalagens do produto
            $newPackagingCost = self::getProductPackagingCost((int)$productId);
            
            // Atualizar na tabela product_additional_costs
            $stUpdate = $pdo->prepare('
                UPDATE product_additional_costs 
                SET packaging_cost = ?, updated_at = NOW() 
                WHERE product_id = ?
            ');
            $result = $stUpdate->execute([$newPackagingCost, $productId]);
            
            if ($result && $stUpdate->rowCount() > 0) {
                $updated++;
            }
        }
        
        return $updated;
    }
}
