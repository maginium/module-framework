<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Closure;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\RuntimeException;
use ReflectionException;

/**
 * Abstract Facade Class.
 *
 * This abstract class provides a foundation for creating facades,
 * which serve as simplified interfaces to access underlying services or classes.
 * It includes methods for resolving instances, managing dynamic method calls,
 * and caching resolved instances for efficiency.
 */
abstract class Facade
{
    /**
     * The resolved object instances.
     *
     * @var array<string, mixed>
     */
    protected static $resolvedInstance = [];

    /**
     * Indicates whether resolved instances should be cached.
     *
     * @var bool
     */
    protected static $cached = true;

    /**
     * Retrieve the root object behind the facade.
     *
     * This method resolves the facade instance based on the accessor provided
     * by the subclass implementing the `getAccessor()` method.
     *
     * @return mixed The resolved facade instance.
     */
    public static function getFacadeRoot()
    {
        return static::instance(static::getAccessor());
    }

    /**
     * Execute a callback when the facade has been resolved.
     *
     * This method runs a given Closure if the facade's dependency has already been resolved,
     * allowing for actions to be taken immediately after resolving the dependency.
     *
     * @param Closure $callback The callback to execute with the resolved facade instance.
     */
    public static function resolved(Closure $callback): void
    {
        // Get the accessor name for the facade
        $accessor = static::getAccessor();

        // Check if the facade's dependency has been resolved
        if (static::isResolved($accessor)) {
            // Execute the callback, passing the resolved facade root
            $callback(static::getFacadeRoot());
        }
    }

    /**
     * Clear a specific resolved facade instance from the cache.
     *
     * This method allows for the removal of a resolved instance from the internal cache,
     * useful when you need to refresh the instance.
     *
     * @param string $name The name of the dependency to clear from cache.
     */
    public static function clearResolvedInstance(string $name): void
    {
        // Remove the resolved instance for the specified dependency name
        unset(static::$resolvedInstance[$name]);
    }

    /**
     * Clear all resolved instances from the cache.
     *
     * This method resets the internal cache of resolved instances,
     * allowing for a fresh start in instance resolution.
     */
    public static function clearResolvedInstances(): void
    {
        // Reset the array holding resolved instances
        static::$resolvedInstance = [];
    }

    /**
     * Check if a class is abstract and has been successfully resolved.
     *
     * This method attempts to resolve the specified class using ObjectManager
     * and checks if the resolved instance is not null and not an abstract class.
     *
     * @param string $className The class name to verify.
     *
     * @return bool True if the class has been resolved and is not abstract; otherwise false.
     */
    protected static function isResolved(string $className): bool
    {
        try {
            // Attempt to resolve the class instance from ObjectManager
            $resolvedInstance = static::getInstance($className);

            // Check if the resolved instance is not null and is not an abstract class
            return $resolvedInstance !== null && ! Reflection::isAbstract($resolvedInstance);
        } catch (Exception $e) {
            // Return false if the class cannot be resolved or is invalid
            return false;
        }
    }

    /**
     * Resolve the facade root instance using the Object Manager.
     *
     * This method retrieves the instance from the Object Manager, caching it
     * if caching is enabled. If the instance cannot be resolved, a RuntimeException is thrown.
     *
     * @param string $name The name of the dependency to resolve.
     *
     * @throws RuntimeException If the dependency cannot be resolved.
     *
     * @return mixed The resolved instance.
     */
    protected static function instance(string $name)
    {
        // Check if caching is enabled and if the dependency has already been resolved
        // If the dependency is cached, return the cached instance
        if (static::$cached && isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        // Attempt to resolve the instance from the Object Manager using the provided name
        $resolvedInstance = static::getInstance($name);

        // If the resolved instance is not null and caching is enabled
        if ($resolvedInstance !== null && static::$cached) {
            // Cache the resolved instance for future use to improve performance
            static::$resolvedInstance[$name] = $resolvedInstance;
        }

        // If an instance has been successfully resolved, return it
        if ($resolvedInstance !== null) {
            return $resolvedInstance;
        }

        // If the instance could not be resolved, throw a RuntimeException with a descriptive message
        throw RuntimeException::make("Unable to resolve dependency: {$name}");
    }

    /**
     * Retrieve the Object Manager instance.
     *
     * This method attempts to fetch the ObjectManager instance. If the instance does not
     * exist or is not initialized, it will catch the exception and return false or null.
     *
     * @return ObjectManagerInterface|null The ObjectManager instance if available, null otherwise.
     */
    protected static function getObjectManager(): ?ObjectManagerInterface
    {
        try {
            // Attempt to get the ObjectManager instance
            return ObjectManager::getInstance();
        } catch (\RuntimeException $e) {
            // Return null or false if unable to get the instance
            return null;
        }
    }

    /**
     * Attempts to resolve an instance by creating it if necessary.
     *
     * This method checks the constructor of the class. If it has parameters, it tries to resolve the class
     * by calling the constructor with those parameters.
     *
     * @param string $className The class name to resolve.
     *
     * @throws RuntimeException If the class cannot be instantiated or resolved.
     *
     * @return mixed The newly created instance, or false if the class has no constructor.
     */
    protected static function resolve(string $className)
    {
        try {
            // If no constructor parameters, just instantiate the class normally
            return static::getObjectManager()->create($className);
        } catch (ReflectionException $e) {
            // Handle reflection errors (e.g., class not found, no constructor)
            throw RuntimeException::make("Failed to resolve class {$className}: " . $e->getMessage());
        }
    }

    /**
     * Attempt to get an instance from the local cache or create it if not found.
     *
     * This method checks if the requested instance is already resolved and cached.
     * If it is not cached, it will attempt to resolve and create the instance.
     *
     * @param string $className The name of the instance to retrieve.
     *
     * @return mixed The resolved or newly created instance, or null if unable to resolve.
     */
    protected static function getInstance(string $className)
    {
        // Retrieve the ObjectManager instance
        $objectManager = static::getObjectManager();

        // Attempt to get the instance from the cache; if not found, resolve it.
        return $objectManager?->get($className) ?? static::resolve($className);
    }

    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    abstract protected static function getAccessor(): string;

    /**
     * Handle dynamic static calls to the facade.
     *
     * This magic method manages calls to undefined static methods on the facade,
     * delegating the call to the resolved facade root instance.
     *
     * @param string $method The name of the method being called.
     * @param array $args The arguments passed to the method.
     *
     * @throws RuntimeException If no facade root has been resolved.
     *
     * @return mixed The result of the method call on the facade root.
     */
    public static function __callStatic($method, $args)
    {
        // Resolve the instance of the facade root (this should be a class with the instance method)
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw RuntimeException::make('A facade root has not been set.');
        }

        // Delegate the static call to the resolved instance's method
        return $instance->{$method}(...$args);
    }
}
