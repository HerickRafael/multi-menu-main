<?php

declare(strict_types=1);

/**
 * Fonte única de verdade para totais do checkout (cupom, fidelidade x entrega).
 */
final class OrderCalculatorService
{
    /**
     * @param array{
     *   subtotal: float,
     *   delivery_fee: float,
     *   loyalty_discount: float,
     *   coupon_percentage: float,
     *   selected_zone_id: int,
     *   zones_present: bool
     * } $input
     * @return array{
     *   couponDiscount: float,
     *   deliveryDiscountApplied: float,
     *   remainingLoyaltyDiscount: float,
     *   finalDeliveryFee: float,
     *   total: float,
     *   deliveryLabel: string
     * }
     */
    public static function computeCheckoutTotals(array $input): array
    {
        $subtotal = (float)($input['subtotal'] ?? 0);
        $deliveryFee = (float)($input['delivery_fee'] ?? 0);
        $loyaltyDiscount = (float)($input['loyalty_discount'] ?? 0);
        $couponPercentage = (float)($input['coupon_percentage'] ?? 0);
        $selectedZoneId = (int)($input['selected_zone_id'] ?? 0);
        $zonesPresent = (bool)($input['zones_present'] ?? false);

        $couponDiscount = $couponPercentage > 0 ? ($subtotal * $couponPercentage) / 100 : 0.0;

        $deliveryDiscountApplied = 0.0;
        $remainingLoyaltyDiscount = 0.0;
        $finalDeliveryFee = $deliveryFee;

        if ($loyaltyDiscount > 0 && $deliveryFee > 0) {
            if ($loyaltyDiscount >= $deliveryFee) {
                $deliveryDiscountApplied = $deliveryFee;
                $remainingLoyaltyDiscount = $loyaltyDiscount - $deliveryFee;
                $finalDeliveryFee = 0.0;
            } else {
                $deliveryDiscountApplied = $loyaltyDiscount;
                $remainingLoyaltyDiscount = 0.0;
                $finalDeliveryFee = $deliveryFee - $loyaltyDiscount;
            }
        } elseif ($loyaltyDiscount > 0 && $deliveryFee <= 0) {
            $remainingLoyaltyDiscount = $loyaltyDiscount;
        }

        $total = $subtotal - $couponDiscount + $finalDeliveryFee - $remainingLoyaltyDiscount;

        $deliveryLabel = self::buildDeliveryLabel(
            $zonesPresent,
            $selectedZoneId,
            $deliveryFee,
            $deliveryDiscountApplied,
            $finalDeliveryFee
        );

        return [
            'couponDiscount' => $couponDiscount,
            'deliveryDiscountApplied' => $deliveryDiscountApplied,
            'remainingLoyaltyDiscount' => $remainingLoyaltyDiscount,
            'finalDeliveryFee' => $finalDeliveryFee,
            'total' => $total,
            'deliveryLabel' => $deliveryLabel,
        ];
    }

    private static function buildDeliveryLabel(
        bool $zonesPresent,
        int $selectedZoneId,
        float $deliveryFee,
        float $deliveryDiscountApplied,
        float $finalDeliveryFee
    ): string {
        if ($selectedZoneId) {
            if ($deliveryDiscountApplied > 0) {
                if ($finalDeliveryFee <= 0) {
                    return 'Grátis';
                }

                return function_exists('price_br') ? price_br($finalDeliveryFee) : (string)round($finalDeliveryFee, 2);
            }

            return $deliveryFee > 0
                ? (function_exists('price_br') ? price_br($deliveryFee) : (string)round($deliveryFee, 2))
                : 'Grátis';
        }
        if ($zonesPresent) {
            return 'Selecione a cidade';
        }
        if (!$zonesPresent && $deliveryFee <= 0) {
            return 'Indisponível';
        }

        return 'A calcular';
    }
}
