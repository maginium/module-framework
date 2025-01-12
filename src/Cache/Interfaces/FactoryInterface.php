<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Interfaces;

use Illuminate\Contracts\Cache\Factory;
use Maginium\Framework\Cache\Repository;

/**
 * Interface FactoryInterface.
 *
 * Defines the contract for configuration management across multiple layers
 * such as environment variables, deployment configurations, scope-specific
 * settings, and caching.
 */
interface FactoryInterface extends Factory
{
    /**
     * Get a cache store instance by name.
     *
     * @param string|null $name
     *
     * @return Repository
     */
    public function store($name = null): Repository;
}
