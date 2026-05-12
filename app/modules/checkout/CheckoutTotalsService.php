<?php

declare(strict_types=1);

class CheckoutTotalsService
{
    /**
     * Calcula totais do checkout a partir do OrderCalculatorService.
     *
     * @param array $input
     * @return array
     */
    public static function compute(array $input): array
    {
        require_once __DIR__ . '/../../services/OrderCalculatorService.php';
        return OrderCalculatorService::computeCheckoutTotals($input);
    }
}
