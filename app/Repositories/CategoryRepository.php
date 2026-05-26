<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Acesso a dados de categorias com escopo de empresa.
 */
final class CategoryRepository
{
    public function __construct(
        private ?PDO $pdo = null
    ) {
    }

    private function conn(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        /** @var PDO $pdo */
        $pdo = \db();
        return $pdo;
    }

    public function allByCompany(int $companyId): array
    {
        return \Category::allByCompany($companyId);
    }

    public function listWithProductStatsByCompany(int $companyId): array
    {
        $sql = "
            SELECT c.*,
                COUNT(p.id) as product_count,
                SUM(CASE WHEN p.active = 1 THEN 1 ELSE 0 END) as active_products
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            WHERE c.company_id = ?
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ";
        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([$companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByCompanyAndId(int $companyId, int $categoryId): ?array
    {
        $category = \Category::find($categoryId);
        if (!$category || (int)($category['company_id'] ?? 0) !== $companyId) {
            return null;
        }

        return $category;
    }

    public function create(int $companyId, array $data): int
    {
        $stmt = $this->conn()->prepare('INSERT INTO categories (company_id, name, description, image, active, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $companyId,
            $data['name'],
            $data['description'] !== '' ? $data['description'] : null,
            $data['image'] ?? null,
            (int)$data['active'],
            (int)$data['sort_order'],
        ]);

        return (int)$this->conn()->lastInsertId();
    }

    public function update(int $companyId, int $categoryId, array $data): bool
    {
        $stmt = $this->conn()->prepare('UPDATE categories SET name = ?, description = ?, image = ?, active = ?, sort_order = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([
            $data['name'],
            $data['description'] !== '' ? $data['description'] : null,
            $data['image'] ?? null,
            (int)$data['active'],
            (int)$data['sort_order'],
            $categoryId,
            $companyId,
        ]);

        return $stmt->rowCount() >= 0;
    }

    public function delete(int $companyId, int $categoryId): bool
    {
        $stmt = $this->conn()->prepare('DELETE FROM categories WHERE id = ? AND company_id = ?');
        $stmt->execute([$categoryId, $companyId]);

        return $stmt->rowCount() > 0;
    }

    public function countProducts(int $categoryId): int
    {
        $stmt = $this->conn()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->execute([$categoryId]);

        return (int)$stmt->fetchColumn();
    }

    public function toggleStatus(int $companyId, int $categoryId): ?int
    {
        $category = $this->findByCompanyAndId($companyId, $categoryId);
        if (!$category) {
            return null;
        }

        $newStatus = !empty($category['active']) ? 0 : 1;
        $this->conn()->prepare('UPDATE categories SET active = ?, updated_at = NOW() WHERE id = ? AND company_id = ?')
            ->execute([$newStatus, $categoryId, $companyId]);

        return $newStatus;
    }
}