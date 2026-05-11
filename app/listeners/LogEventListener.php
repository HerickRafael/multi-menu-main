<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/EventLog.php';

class LogEventListener
{
    public static function handle(BaseEvent $event): void
    {
        EventLog::create(
            $event->name,
            $event->aggregateType,
            $event->aggregateId,
            $event->companyId,
            $event->payload,
            $event->dispatchedBy,
            $event->source
        );
    }
}
