<?php

declare(strict_types=1);

class OrderDetailsService
{
    /**
     * Carrega detalhes do pedido para o painel admin.
     *
     * @return array|null
     */
    public static function loadAdminDetails(PDO $db, int $companyId, int $orderId): ?array
    {
        $order = Order::findWithItems($db, $orderId, $companyId);
        if (!$order) {
            return null;
        }

        $ifoodData = null;
        if (($order['source'] ?? '') === 'ifood' && !empty($order['ifood_order_id'])) {
            $stIfood = $db->prepare('SELECT * FROM ifood_orders WHERE ifood_order_id = ? AND company_id = ?');
            $stIfood->execute([$order['ifood_order_id'], $companyId]);
            $ifoodData = $stIfood->fetch(PDO::FETCH_ASSOC);
        }

        $paymentMethodName = null;
        $paymentMethodType = null;
        $paymentMethodMeta = null;
        $paymentMethodInstructions = null;
        if (!empty($order['payment_method_id'])) {
            $stPm = $db->prepare('SELECT name, type, meta, instructions FROM payment_methods WHERE id = ? AND company_id = ?');
            $stPm->execute([(int)$order['payment_method_id'], $companyId]);
            $pmRow = $stPm->fetch(PDO::FETCH_ASSOC);
            if ($pmRow) {
                $paymentMethodName = $pmRow['name'] ?? null;
                $paymentMethodType = $pmRow['type'] ?? null;
                $paymentMethodInstructions = $pmRow['instructions'] ?? null;
                $rawMeta = $pmRow['meta'] ?? null;
                if ($rawMeta) {
                    $paymentMethodMeta = is_string($rawMeta) ? json_decode($rawMeta, true) : $rawMeta;
                }
            }
        }

        $orderEvents = Order::eventsForOrder($db, $companyId, $orderId, 50);

        return [
            'order' => $order,
            'ifoodData' => $ifoodData,
            'paymentMethodName' => $paymentMethodName,
            'paymentMethodType' => $paymentMethodType,
            'paymentMethodMeta' => $paymentMethodMeta,
            'paymentMethodInstructions' => $paymentMethodInstructions,
            'orderEvents' => $orderEvents,
        ];
    }
}
