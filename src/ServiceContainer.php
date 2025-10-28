<?php

/**
 * Service Container for dependency injection
 *
 * @package FP\Privacy
 */

namespace FP\Privacy;

class ServiceContainer
{
    /**
     * @var array<string, callable> Registered services
     */
    private array $services = [];

    /**
     * @var array<string, mixed> Resolved instances
     */
    private array $resolved = [];

    /**
     * Registra un servizio nel container
     *
     * @param string $id Service identifier
     * @param callable $factory Factory function
     */
    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
        unset($this->resolved[$id]); // Clear cached instance if exists
    }

    /**
     * Ottiene un servizio dal container (singleton)
     *
     * @param string $id Service identifier
     * @return mixed
     * @throws \RuntimeException if service not found
     */
    public function get(string $id)
    {
        // Return cached instance if exists
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        // Service not registered
        if (!isset($this->services[$id])) {
            throw new \RuntimeException("Service not found: {$id}");
        }

        // Resolve and cache
        $factory = $this->services[$id];
        $instance = $factory($this);
        $this->resolved[$id] = $instance;

        return $instance;
    }

    /**
     * Verifica se un servizio è registrato
     *
     * @param string $id Service identifier
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}

