<?php

declare(strict_types=1);

namespace App\Services;

require_once __DIR__ . '/../helpers/business_hours_helper.php';

class StoreStatusService
{
    /**
     * Calcula status da loja em tempo real com base na grade de horários.
     *
     * @param array<int|string, array<string, mixed>> $hours
     */
    public static function get(array $hours): array
    {
        return check_business_hours_status($hours);
    }
}
