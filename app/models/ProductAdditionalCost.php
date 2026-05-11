<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Model para Custos Adicionais de Produto
 */
class ProductAdditionalCost
{
    /**
     * Busca custos adicionais de um produto
     */
    public static function findByProduct(int $productId): ?array
    {
        $pdo = db();
        $st = $pdo->prepare('SELECT * FROM product_additional_costs WHERE product_id = ?');
        $st->execute([$productId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Alias para findByProduct (compatibilidade)
     */
    public static function getByProduct(int $productId): ?array
    {
        return self::findByProduct($productId);
    }

    /**
     * Busca ou cria registro de custos adicionais
     */
    public static function findOrCreate(int $productId, int $companyId): array
    {
        $existing = self::findByProduct($productId);
        
        if ($existing) {
            return $existing;
        }

        // Criar registro vazio
        $pdo = db();
        $st = $pdo->prepare('
            INSERT INTO product_additional_costs (product_id, company_id)
            VALUES (?, ?)
        ');
        $st->execute([$productId, $companyId]);

        return self::findByProduct($productId) ?? [
            'product_id' => $productId,
            'company_id' => $companyId,
            'packaging_cost' => 0,
            'labor_cost' => 0,
            'waste_percentage' => 0,
            'tax_percentage' => 0,
            'platform_fee_percentage' => 0,
            'other_costs' => 0,
        ];
    }

    /**
     * Lista custos adicionais de todos os produtos de uma empresa
     */
    public static function listByCompany(int $companyId): array
    {
        $pdo = db();
        $st = $pdo->prepare('
            SELECT pac.*, p.name as product_name, p.price as sale_price
            FROM product_additional_costs pac
            JOIN products p ON p.id = pac.product_id
            WHERE pac.company_id = ?
            ORDER BY p.name
        ');
        $st->execute([$companyId]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Atualiza custos adicionais de um produto
     */
    public static function save(int $productId, int $companyId, array $data): void
    {
        $pdo = db();
        
        // Verificar se já existe
        $existing = self::findByProduct($productId);

        if ($existing) {
            $st = $pdo->prepare('
                UPDATE product_additional_costs SET
                    packaging_cost = ?,
                    packaging_description = ?,
                    labor_cost = ?,
                    labor_minutes = ?,
                    waste_percentage = ?,
                    tax_percentage = ?,
                    platform_fee_percentage = ?,
                    other_costs = ?,
                    other_costs_description = ?,
                    notes = ?
                WHERE product_id = ?
            ');
            $st->execute([
                $data['packaging_cost'] ?? 0,
                $data['packaging_description'] ?? null,
                $data['labor_cost'] ?? 0,
                $data['labor_minutes'] ?? 0,
                $data['waste_percentage'] ?? 0,
                $data['tax_percentage'] ?? 0,
                $data['platform_fee_percentage'] ?? 0,
                $data['other_costs'] ?? 0,
                $data['other_costs_description'] ?? null,
                $data['notes'] ?? null,
                $productId,
            ]);
        } else {
            $st = $pdo->prepare('
                INSERT INTO product_additional_costs 
                (product_id, company_id, packaging_cost, packaging_description, labor_cost, labor_minutes,
                 waste_percentage, tax_percentage, platform_fee_percentage, other_costs, other_costs_description, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $st->execute([
                $productId,
                $companyId,
                $data['packaging_cost'] ?? 0,
                $data['packaging_description'] ?? null,
                $data['labor_cost'] ?? 0,
                $data['labor_minutes'] ?? 0,
                $data['waste_percentage'] ?? 0,
                $data['tax_percentage'] ?? 0,
                $data['platform_fee_percentage'] ?? 0,
                $data['other_costs'] ?? 0,
                $data['other_costs_description'] ?? null,
                $data['notes'] ?? null,
            ]);
        }
    }

    /**
     * Calcula total de custos adicionais de um produto
     */
    public static function getTotalAdditionalCost(int $productId): float
    {
        $costs = self::findByProduct($productId);
        
        if (!$costs) {
            return 0.0;
        }

        return (float)($costs['packaging_cost'] ?? 0) 
             + (float)($costs['labor_cost'] ?? 0)
             + (float)($costs['other_costs'] ?? 0);
    }

    /**
     * Deleta custos adicionais de um produto
     */
    public static function delete(int $productId): void
    {
        $pdo = db();
        $st = $pdo->prepare('DELETE FROM product_additional_costs WHERE product_id = ?');
        $st->execute([$productId]);
    }
}
