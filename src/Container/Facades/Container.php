<?php

declare(strict_types=1);

namespace Maginium\Framework\Container\Facades;

use Maginium\Framework\Container\Interfaces\ContainerInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Dependency Injection service.
 *
 * This class acts as a simplified interface to access the ContainerInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * @method static mixed get(string $className) Retrieve a singleton instance of a specified class.
 * @method static bool has(string $className) Check if a class can be resolved.
 * @method static ?object resolve(?string $className, ?array $arguments = []) Check if a module is active and resolve an instance of a specified class.
 * @method static mixed make(string $className, ...$arguments) Create a new instance of a specified class.
 * @method static bool isEnabled(string $moduleName) Check if a module is installed and enabled.
 * @method static array<string, mixed> getBindings() Retrieve all bindings in the container.
 *
 * @see ContainerInterface
 */
class Container extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return ContainerInterface::class;
    }
}
