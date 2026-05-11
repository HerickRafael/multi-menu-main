<?php

declare(strict_types=1);

class BaseEvent
{
    public string $name;
    public ?string $aggregateType;
    public ?int $aggregateId;
    public ?int $companyId;
    public array $payload;
    public ?int $dispatchedBy;
    public string $source;

    public function __construct(
        string $name,
        ?string $aggregateType = null,
        ?int $aggregateId = null,
        ?int $companyId = null,
        array $payload = [],
        ?int $dispatchedBy = null,
        string $source = 'system'
    ) {
        $this->name = $name;
        $this->aggregateType = $aggregateType;
        $this->aggregateId = $aggregateId;
        $this->companyId = $companyId;
        $this->payload = $payload;
        $this->dispatchedBy = $dispatchedBy;
        $this->source = $source;
    }
}
