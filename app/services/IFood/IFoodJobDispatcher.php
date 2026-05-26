<?php

declare(strict_types=1);

namespace App\Services\IFood;

use RuntimeException;

/**
 * Registry de handlers por job_type.
 *
 * Uso típico (no bootstrap do worker):
 *   $dispatcher = new IFoodJobDispatcher();
 *   $dispatcher->register('ifood.stock.sync',     StockSyncHandler::class);
 *   $dispatcher->register('ifood.driver.request', DriverRequestHandler::class);
 *   ...
 *
 * Handlers podem ser:
 *  - FQCN string (instanciado on-demand, sem deps no construtor)
 *  - Instância já criada
 *  - Callable (function($job, $payload))
 *
 * Para handlers com dependências (DB, IFoodClient, etc.), instancie antes:
 *   $dispatcher->register('ifood.x', new MyHandler($db, $logger));
 */
final class IFoodJobDispatcher
{
    /** @var array<string, IFoodJobHandler|callable|string> */
    private array $handlers = [];

    public function register(string $jobType, IFoodJobHandler|callable|string $handler): void
    {
        $this->handlers[$jobType] = $handler;
    }

    public function isRegistered(string $jobType): bool
    {
        return isset($this->handlers[$jobType]);
    }

    /**
     * Executa o handler associado ao job. Propaga exceções para o worker classificar.
     *
     * @param array{id:int, company_id:?int, job_type:string, attempts:int, max_attempts:int} $job
     * @param array<string,mixed> $payload
     */
    public function dispatch(array $job, array $payload): void
    {
        $type = (string) ($job['job_type'] ?? '');
        if (!isset($this->handlers[$type])) {
            throw new RuntimeException("No handler registered for job_type: {$type}");
        }

        $handler = $this->handlers[$type];

        if ($handler instanceof IFoodJobHandler) {
            $handler->handle($job, $payload);
            return;
        }

        if (is_callable($handler)) {
            $handler($job, $payload);
            return;
        }

        if (is_string($handler) && class_exists($handler)) {
            $instance = new $handler();
            if (!$instance instanceof IFoodJobHandler) {
                throw new RuntimeException("Handler {$handler} must implement IFoodJobHandler");
            }
            $instance->handle($job, $payload);
            return;
        }

        throw new RuntimeException("Invalid handler for {$type}");
    }
}
