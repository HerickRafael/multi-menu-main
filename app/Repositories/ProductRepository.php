<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Camada fina sobre o model Product (evolução gradual do acesso a dados).
 */
final class ProductRepository
{
    public function listActiveByCompany(int $companyId): array
    {
        return \Product::listByCompany($companyId, null, true);
    }

    public function findByCompanyAndId(int $companyId, int $productId): ?array
    {
        return \Product::findByCompanyAndId($companyId, $productId);
    }
}
