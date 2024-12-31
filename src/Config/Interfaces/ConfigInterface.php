<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Interfaces;

/**
 * Interface ConfigInterface.
 *
 * Defines the contract for configuration management across multiple layers
 * such as environment variables, deployment configurations, scope-specific
 * settings, and caching.
 *
 * @method mixed get(string $path, $default = null)
 * @method static scope(?string $scope = 'default', ?int $id = null) Sets the scope and ID for the configuration and returns a new instance for method chaining.
 * @method ?int getScopeId() Retrieves the store ID the configuration should be scoped to.
 * @method $this setScopeId(?int $scopeId) Sets the store ID for the configuration scope.
 * @method string getScope() Retrieves the scope (e.g., 'store', 'website') for the configuration.
 * @method $this setScope(?string $scope) Sets the scope for the configuration.
 * @method void resetScope() Resets the scope and scope ID to their default values.
 */
interface ConfigInterface
{
    /**
     * Get the value of a configuration variable or throw an exception if not defined.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(string|null))|string|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If the configuration variable is not defined.
     *
     * @return mixed The value of the configuration variable.
     */
    public function getOrThrow(string $path, mixed $default = null): mixed;

    /**
     * Get the value of a configuration variable as a string.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(string|null))|string|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return string|null The value of the configuration variable as a string.
     */
    public function getString(string $path, mixed $default = null): ?string;

    /**
     * Get the value of a configuration variable as an integer.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(int|null))|int|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return int|null The value of the configuration variable as an integer.
     */
    public function getInt(string $path, mixed $default = null): ?int;

    /**
     * Get the value of a configuration variable as a float.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(float|null))|float|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return float|null The value of the configuration variable as a float.
     */
    public function getFloat(string $path, mixed $default = null): ?float;

    /**
     * Get the value of a configuration variable as a boolean.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(bool|null))|bool|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return bool|null The value of the configuration variable as a boolean.
     */
    public function getBool(string $path, mixed $default = null): ?bool;

    /**
     * Get the value of a configuration variable as an array.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(array<array-key, mixed>|null))|array<array-key, mixed>|null  $default  The default value to return if no configuration is found.
     * @param  string|null  $separator  The delimiter used to split the string into an array. Defaults to ','.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return array|null The value of the configuration variable as an array.
     */
    public function getArray(string $path, mixed $default = null, ?string $separator = ','): ?array;

    /**
     * Get the value of a configuration variable as an object.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  Closure(): object|null|object  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return mixed The value of the configuration variable as an object.
     */
    public function getObject(string $path, mixed $default = null): mixed;
}
