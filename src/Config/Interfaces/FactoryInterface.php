<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Interfaces;

/**
 * Interface FactoryInterface.
 *
 * Defines the contract for configuration management across multiple layers
 * such as environment variables, deployment configurations, scope-specific
 * settings, and caching.
 */
interface FactoryInterface
{
    /**
     * Retrieve a driver instance by name.
     *
     * This method retrieves a driver instance based on the provided driver name.
     * If no name is provided, it returns the default driver instance.
     *
     * @param  string|null  $name  The name of the configuration driver.
     *
     * @return mixed The driver instance corresponding to the provided name or the default driver.
     */
    public function driver(?string $name = null): mixed;
}
