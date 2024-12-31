<?php

declare(strict_types=1);

namespace Maginium\Framework\Container\Interfaces;

use Maginium\Foundation\Exceptions\InvalidArgumentException;

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
}
