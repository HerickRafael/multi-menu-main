<?php

declare(strict_types=1);

class ServiceContainer
{
    /** @var array<string, mixed> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $singletons = [];

    public function set(string $id, $concrete): void
    {
        $this->bindings[$id] = $concrete;
        unset($this->instances[$id], $this->singletons[$id]);
    }

    public function singleton(string $id, $concrete): void
    {
        $this->bindings[$id] = $concrete;
        $this->singletons[$id] = true;
        unset($this->instances[$id]);
    }

    public function get(string $id, ...$parameters)
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!array_key_exists($id, $this->bindings)) {
            if (class_exists($id)) {
                return new $id(...$parameters);
            }

            throw new RuntimeException("Service '{$id}' não registrado no container.");
        }

        $concrete = $this->bindings[$id];

        if ($concrete instanceof Closure) {
            $service = $concrete($this, ...$parameters);
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $service = new $concrete(...$parameters);
        } else {
            $service = $concrete;
        }

        if (!empty($this->singletons[$id])) {
            $this->instances[$id] = $service;
        }

        return $service;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->bindings) || class_exists($id);
    }
}