<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseEvent.php';

class WhatsAppDisconnectedEvent extends BaseEvent
{
    public function __construct(int $instanceId, int $companyId, array $payload = [], ?int $dispatchedBy = null)
    {
        parent::__construct('whatsapp.disconnected', 'evolution_instance', $instanceId, $companyId, $payload, $dispatchedBy, 'whatsapp');
    }
}
