<?php

declare(strict_types=1);

namespace App\Services\IFood;

/**
 * Contrato para handlers de jobs iFood.
 *
 * Cada job_type (ex: 'ifood.stock.sync', 'ifood.driver.request', 'ifood.reviews.fetch')
 * registra uma classe que implementa esta interface.
 *
 * Convenções:
 *  - Sucesso → simplesmente retornar.
 *  - Falha transitória (5xx, rede) → lançar IFoodRetryableException; worker reagenda.
 *  - Falha permanente (4xx, validação) → lançar RuntimeException; worker manda pra dead.
 */
interface IFoodJobHandler
{
    /**
     * @param array{id:int, company_id:?int, job_type:string, attempts:int, max_attempts:int} $job
     * @param array<string,mixed> $payload  Conteúdo decodificado de queue_jobs.payload_json
     */
    public function handle(array $job, array $payload): void;
}
