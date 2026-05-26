<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Camada fina sobre o model Product (evolução gradual do acesso a dados).
 */
final class ProductRepository
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

    public function listActiveByCompany(int $companyId): array
    {
        return \Product::listByCompany($companyId, null, true);
    }

    public function listByCompany(int $companyId): array
    {
        return \Product::listByCompany($companyId, null, false, false);
    }

    public function listByFiltersForMobile(int $companyId, string $search = '', ?int $categoryId = null, string $status = 'all'): array
    {
        $pdo = $this->conn();
        $sql = 'SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.company_id = ?';
        $params = [$companyId];

        if ($search !== '') {
            $sql .= ' AND (p.name LIKE ? OR p.sku LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }

        if ($status === 'active') {
            $sql .= ' AND p.active = 1';
        } elseif ($status === 'inactive') {
            $sql .= ' AND p.active = 0';
        }

        $sql .= ' ORDER BY c.sort_order ASC, p.sort_order ASC, p.name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function statsByCompany(int $companyId): array
    {
        $stmt = $this->conn()->prepare('SELECT COUNT(*) as total, SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active, SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) as inactive FROM products WHERE company_id = ?');
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'active' => 0, 'inactive' => 0];
    }

    public function findByCompanyAndId(int $companyId, int $productId): ?array
    {
        return \Product::findByCompanyAndId($companyId, $productId);
    }

    public function create(array $data): int
    {
        return \Product::create($data);
    }

    public function update(int $productId, array $data): bool
    {
        return \Product::update($productId, $data);
    }

    public function delete(int $productId): bool
    {
        return \Product::delete($productId);
    }

    public function toggleStatus(int $companyId, int $productId): ?int
    {
        $product = $this->findByCompanyAndId($companyId, $productId);
        if (!$product) {
            return null;
        }

        $newStatus = !empty($product['active']) ? 0 : 1;
        $this->conn()->prepare('UPDATE products SET active = ?, updated_at = NOW() WHERE id = ? AND company_id = ?')
            ->execute([$newStatus, $productId, $companyId]);

        return $newStatus;
    }

    public function searchSimpleByCompany(int $companyId, string $search, int $limit): array
    {
        $search = trim($search);
        $limit = min(max($limit, 1), 50);

        $query = 'SELECT p.id, p.name, p.sku, p.price, p.allow_customize FROM products p JOIN categories c ON p.category_id = c.id WHERE c.company_id = ? AND p.type = ? AND p.active = 1';
        $params = [$companyId, 'simple'];

        if ($search !== '') {
            $query .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR p.id = ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = is_numeric($search) ? (int)$search : 0;
        }

        $query .= ' ORDER BY p.name ASC LIMIT ' . $limit;
        $stmt = $this->conn()->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
