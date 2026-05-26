<?php

declare(strict_types=1);

namespace App\Services\IFood;

/**
 * Resposta tipada de uma chamada via IFoodClient.
 * Imutável; criada pelo client e devolvida ao caller.
 */
final class IFoodResponse
{
    public function __construct(
        public readonly bool   $ok,
        public readonly ?int   $status,
        public readonly mixed  $body,
        public readonly ?string $rawBody,
        public readonly ?string $error,
        public readonly int    $latencyMs,
        public readonly int    $attempts,
        public readonly array  $responseHeaders = [],
    ) {}

    public static function success(?int $status, mixed $body, ?string $rawBody, int $latencyMs, int $attempts, array $headers = []): self
    {
        return new self(true, $status, $body, $rawBody, null, $latencyMs, $attempts, $headers);
    }

    public static function failure(?int $status, ?string $error, mixed $body, ?string $rawBody, int $latencyMs, int $attempts, array $headers = []): self
    {
        return new self(false, $status, $body, $rawBody, $error, $latencyMs, $attempts, $headers);
    }
}
