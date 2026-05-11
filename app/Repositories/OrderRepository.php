<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Camada fina sobre o model Order.
 */
final class OrderRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function findWithItems(int $orderId, int $companyId): ?array
    {
        return \Order::findWithItems($this->pdo, $orderId, $companyId);
    }
}
