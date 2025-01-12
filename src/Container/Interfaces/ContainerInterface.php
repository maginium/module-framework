<?php

declare(strict_types=1);

namespace Maginium\Framework\Container\Interfaces;

use Maginium\Foundation\Exceptions\InvalidArgumentException;
use ReflectionAttribute;

/**
 * Interface ContainerInterface.
 *
 * Defines methods for resolving and managing dependencies.
 */
interface ContainerInterface
{
    /**
     * Retrieve a singleton instance of a specified class.
     *
     * @param  string  $className  The class name to retrieve.
     *
     * @throws InvalidArgumentException If $className is null or empty.
     *
     * @return mixed The singleton instance of the specified class.
     */
    public function get(string $className): mixed;

    /**
     * Returns the current instance of the ContainerManager.
     *
     * @return ContainerInterface The current instance of this class.
     */
    public function getInstance(): self;

    /**
     * Check if a class can be resolved.
     *
     * @param  string  $className  The class name to check.
     *
     * @return bool True if the class can be resolved, false otherwise.
     */
    public function has(string $className): bool;

    /**
     * Check if a module is active and resolve an instance of a specified class.
     *
     * @param  string|null  $className  The class name to resolve.
     * @param  array|null  $arguments  The class arguments to resolve.
     *
     * @throws InvalidArgumentException If $className is null or empty.
     *
     * @return object|null The resolved instance or null if not resolved.
     */
    public function resolve(?string $className, ?array $arguments = []): ?object;

    /**
     * Create a new instance of a specified class.
     *
     * @param  string  $className  The class name to instantiate.
     * @param  mixed  ...$arguments  Optional arguments to pass to the class constructor.
     *
     * @throws InvalidArgumentException If $className is null or empty.
     *
     * @return mixed The instance of the specified class.
     */
    public function make(string $className, ...$arguments): mixed;

    /**
     * Check if a module is installed and enabled.
     *
     * @param  string  $moduleName  The name of the module to check.
     *
     * @throws InvalidArgumentException If $moduleName is null or empty.
     *
     * @return bool True if the module is enabled, false otherwise.
     */
    public function isEnabled(string $moduleName): bool;

    /**
     * Get all bindings in the container.
     *
     * This method retrieves an associative array of all the registered bindings,
     * mapping class names to their corresponding resolved instances or definitions.
     *
     * @return array<string, mixed> An associative array of bindings.
     */
    public function getBindings(): array;

    /**
     * Determine if the container has a method binding.
     *
     * @param  string  $method The method name to check for binding.
     *
     * @return bool True if the method is bound, otherwise false.
     */
    public function hasMethodBinding($method): bool;

    /**
     * Bind a callback to resolve with Container::call.
     *
     * @param  array|string  $method The method to bind.
     * @param  Closure  $callback The callback to bind to the method.
     *
     * @return void
     */
    public function bindMethod($method, $callback): void;

    /**
     * Get the method binding for the given method.
     *
     * @param  string  $method The method name to call.
     * @param  mixed  $instance The instance to call the method on.
     *
     * @return mixed The result of the method call.
     */
    public function callMethodBinding($method, $instance): mixed;

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback The callback to call.
     * @param  array<string, mixed>  $parameters Parameters to inject into the callback.
     * @param  string|null  $defaultMethod The default method to call, if applicable.
     *
     * @throws \InvalidArgumentException If the callback is invalid or the method cannot be found.
     *
     * @return mixed The result of the callback execution.
     */
    public function call($callback, array $parameters = [], $defaultMethod = null): mixed;

    /**
     * Resolve a dependency based on an attribute.
     *
     * @param  ReflectionAttribute  $attribute The attribute to resolve from.
     *
     * @return mixed The resolved dependency.
     */
    public function resolveFromAttribute(ReflectionAttribute $attribute): mixed;

    /**
     * Fire all of the after resolving attribute callbacks.
     *
     * @param  ReflectionAttribute[]  $attributes List of attributes to fire callbacks for.
     * @param  mixed  $object The object being resolved.
     *
     * @return void
     */
    public function fireAfterResolvingAttributeCallbacks(array $attributes, $object): void;
}
