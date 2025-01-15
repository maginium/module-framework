<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Interfaces;

/**
 * Interface Driver.
 *
 * This interface defines a contract for config drivers that handle
 * the execution of tasks either concurrently or deferred. Implementing
 * classes must provide mechanisms to run tasks and to defer their
 * execution until a later time.
 */
interface DriverInterface
{
    /**
     * Cache tag.
     */
    public const CACHE_TAG = 'CONFIG';

    /**
     * Retrieves the value of a configuration variable, falling back to a default if not found.
     *
     * The method checks multiple sources in the following order:
     * 1. Environment variables (using `Env::get()`).
     * 2. Cache (using `CacheManager`).
     * 3. Deployment configuration (using `DeploymentConfig`).
     * 4. Store-specific scope configuration (using `DriverInterface`).
     *
     * @param  string  $path  The key representing the configuration variable (e.g., 'web/secure/base_url').
     * @param  ?mixed  $default  The value to return if the configuration is not found.
     *
     * @return mixed The configuration value or the default value if no configuration is found.
     */
    public function get(string $path, $default = null): mixed;
}
