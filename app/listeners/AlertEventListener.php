<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/SystemLog.php';

class AlertEventListener
{
    public static function handle(BaseEvent $event): void
    {
        $criticalEvents = ['whatsapp.disconnected'];
        if (!in_array($event->name, $criticalEvents, true)) {
            return;
        }

        SystemLog::create(
            'warning',
            'events',
            'Evento critico detectado: ' . $event->name,
            [
                'aggregate_type' => $event->aggregateType,
                'aggregate_id' => $event->aggregateId,
                'payload' => $event->payload,
            ],
            $event->companyId,
            null,
            'event-listener'
        );
    }
}
