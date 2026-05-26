<?php

declare(strict_types=1);

class OrderStatusService
{
    /**
     * Status aceitos na camada de entrada (UI/API).
     *
     * @return string[]
     */
    public static function acceptedInputStatuses(): array
    {
        return ['pending', 'paid', 'completed', 'canceled', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
    }

    /**
     * Converte status de UI/API para status persistido no sistema.
     */
    public static function mapToInternal(string $status): string
    {
        $normalized = strtolower(trim($status));

        $map = [
            'confirmed' => 'paid',
            'preparing' => 'paid',
            'ready' => 'paid',
            'delivered' => 'completed',
            'cancelled' => 'canceled',
        ];

        return $map[$normalized] ?? $normalized;
    }

    /**
     * Atualiza status de pedido com validacao unificada.
     *
     * @return array{ok: bool, error?: string, requested_status?: string, internal_status?: string}
     */
    public static function updateForCompany(PDO $db, int $companyId, int $orderId, string $requestedStatus): array
    {
        if ($orderId < 1) {
            return ['ok' => false, 'error' => 'ID do pedido inválido'];
        }

        $requestedStatus = strtolower(trim($requestedStatus));
        if (!in_array($requestedStatus, self::acceptedInputStatuses(), true)) {
            return ['ok' => false, 'error' => 'Status inválido'];
        }

        $order = Order::findBasic($db, $orderId, $companyId);
        if (!$order) {
            return ['ok' => false, 'error' => 'Pedido não encontrado'];
        }

        $internalStatus = self::mapToInternal($requestedStatus);
        $updated = Order::updateStatus($db, $orderId, $companyId, $internalStatus);

        if (!$updated) {
            return ['ok' => false, 'error' => 'Falha ao atualizar status'];
        }

        return [
            'ok' => true,
            'requested_status' => $requestedStatus,
            'internal_status' => $internalStatus,
        ];
    }
}
