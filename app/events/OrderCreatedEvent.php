<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseEvent.php';

class OrderCreatedEvent extends BaseEvent
{
    public function __construct(int $orderId, int $companyId, array $payload = [], ?int $dispatchedBy = null)
    {
        parent::__construct('order.created', 'order', $orderId, $companyId, $payload, $dispatchedBy, 'orders');
    }
}
