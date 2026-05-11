<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Endereços de cliente (delega a CustomerAddress).
 */
final class AddressRepository
{
    public function forCustomer(int $customerId, int $companyId): array
    {
        return \CustomerAddress::getByCustomer($customerId, $companyId);
    }
}
