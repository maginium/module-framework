<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Closure;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\RuntimeException;

/**
 * Class MultipleInstanceManager.
 *
 * This abstract class provides a framework for managing multiple instances
 * of a particular type. It allows for the resolution and caching of instances,
 * registration of custom instance creators, and dynamic method calling on the
 * resolved instances. This class is designed to facilitate the creation and
 * management of shared resources in an application, such as database connections,
 * service classes, or other components that may need to be instantiated with
 * varying configurations.
 */
abstract class MultipleInstanceManager
{
    /**
     * The array of resolved instances.
     *
     * This array holds instances that have been resolved and are available for use.
     *
     * @var array
     */
    protected array $instances = [];

    /**
     * The registered custom instance creators.
     *
     * This array stores custom creators for instances, allowing for flexible instantiation
     * of instances based on specific needs.
     *
     * @var array
     */
    protected array $customCreators = [];

    /**
     * Attempt to get an instance from the local cache.
     *
     * This method checks if the requested instance is already resolved and cached.
     * If it is not cached, it will attempt to resolve it.
     *
     * @param  string  $name The name of the instance to retrieve.
     *
     * @return mixed The resolved instance.
     */
    protected function get(string $name): mixed
    {
        // Return the instance from the cache or resolve it if not present.
        return $this->instances[$name] ?? $this->resolve($name);
    }

    /**
     * Get all resolved instances.
     *
     * This method retrieves all the instances that have been resolved and cached.
     *
     * @return array The array of resolved instances.
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Get an instance by name.
     *
     * This method retrieves an instance by its name. If the name is not provided,
     * it defaults to the instance returned by `getDefaultInstance()`.
     *
     * @param string|null  $name The name of the instance to retrieve.
     *
     * @return mixed The resolved instance.
     */
    public function instance(?string $name = null)
    {
        // Determine the instance name; if null, use the default instance.
        $name = $name ?: $this->getDefaultInstance();

        // Resolve and cache the instance in the instances array.
        return $this->instances[$name] = $this->get($name);
    }

    /**
     * Unset the given instances.
     *
     * This method removes specified instances from the local cache. If no name is provided,
     * it defaults to the default instance.
     *
     * @param  array|string|null  $name The name(s) of the instance(s) to unset.
     *
     * @return $this The current instance of MultipleInstanceManager for method chaining.
     */
    public function forgetInstance(?string $name = null)
    {
        // Use the default instance if no name is specified.
        $name ??= $this->getDefaultInstance();

        // Loop through the provided names and unset them from the instances array.
        foreach ((array)$name as $instanceName) {
            if (isset($this->instances[$instanceName])) {
                unset($this->instances[$instanceName]);
            }
        }

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Disconnect the given instance and remove it from local cache.
     *
     * This method purges the specified instance from the local cache, ensuring that
     * it will be re-resolved the next time it is requested.
     *
     * @param  string|null  $name The name of the instance to purge.
     *
     * @return void
     */
    public function purge(?string $name = null): void
    {
        // Default to the instance specified by getDefaultInstance() if no name is given.
        $name ??= $this->getDefaultInstance();

        // Unset the specified instance from the instances array.
        unset($this->instances[$name]);
    }

    /**
     * Register a custom instance creator Closure.
     *
     * This method allows for the registration of a custom creator for a named instance.
     * The registered closure will be used to create instances of that name.
     *
     * @param  string  $name The name of the instance to extend.
     * @param  Closure  $callback The Closure that will be used to create the instance.
     *
     * @return $this The current instance of MultipleInstanceManager for method chaining.
     */
    public function extend(string $name, Closure $callback)
    {
        // Bind the Closure to this instance, allowing access to protected members.
        $this->customCreators[$name] = $callback->bindTo($this, $this);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Resolve the given instance.
     *
     * This method retrieves the configuration for the specified instance and attempts
     * to resolve it. If the instance is not defined or lacks a driver, exceptions are thrown.
     *
     * @param string $name The name of the instance to resolve.
     *
     * @throws InvalidArgumentException If the instance is not defined or has an invalid configuration.
     * @throws RuntimeException If the instance does not specify a driver.
     *
     * @return mixed The resolved instance.
     */
    protected function resolve(string $name): mixed
    {
        // Retrieve the configuration for the specified instance.
        $config = $this->getInstanceConfig($name);

        // Validate the configuration.
        if ($config === null) {
            throw InvalidArgumentException::make(__('Instance [] is not defined.', $name));
        }

        if (! Arr::keyExists('driver', $config)) {
            throw RuntimeException::make(__('Instance [] does not specify a driver.', $name));
        }

        // Check for a custom creator; if found, call it to create the instance.
        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        // Construct the driver method name and attempt to call it.
        $driverMethod = 'create' . Str::capital($config['driver']) . 'Driver';

        // Check if the method exists and call it to resolve the instance.
        if (Reflection::methodExists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        // If the driver is not supported, throw an exception.
        throw InvalidArgumentException::make(__('Instance driver [%1] is not supported.', $config['driver']));
    }

    /**
     * Dynamically call the default instance.
     *
     * This magic method allows for method calls on the default instance directly.
     * If a method is not found in the MultipleInstanceManager, it will be passed
     * to the resolved instance.
     *
     * @param  string  $method The name of the method to call.
     * @param  array  $parameters The parameters to pass to the method.
     *
     * @return mixed The result of the method call.
     */
    public function __call($method, $parameters)
    {
        // Delegate the method call to the default instance.
        return $this->instance()->{$method}(...$parameters);
    }
}
