<?php

declare(strict_types=1);

require_once __DIR__ . '/../events/BaseEvent.php';
require_once __DIR__ . '/../listeners/LogEventListener.php';
require_once __DIR__ . '/../listeners/AlertEventListener.php';

class EventDispatcher
{
    /** @var array<string, array<int, callable|string>> */
    private static array $listeners = [];

    public static function bootstrap(): void
    {
        if (!empty(self::$listeners)) {
            return;
        }

        // Listener global de log.
        self::listen('*', [LogEventListener::class, 'handle']);

        // Listener de alerta para eventos criticos.
        self::listen('whatsapp.disconnected', [AlertEventListener::class, 'handle']);
    }

    public static function listen(string $eventName, callable|array $listener): void
    {
        self::$listeners[$eventName] ??= [];
        self::$listeners[$eventName][] = $listener;
    }

    public static function dispatch(BaseEvent $event): void
    {
        self::bootstrap();

        foreach (self::$listeners['*'] ?? [] as $listener) {
            call_user_func($listener, $event);
        }

        foreach (self::$listeners[$event->name] ?? [] as $listener) {
            call_user_func($listener, $event);
        }
    }
}
