<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Container\Interfaces\ContainerInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Dependency Injection service.
 *
 * This class acts as a simplified interface to access the ContainerInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * @method static bool has(string $className)
 *     Check if a class can be resolved.
 *     Parameters:
 *     - $className: The name of the class to check.
 *     Returns:
 *     - bool: True if the class can be resolved; false otherwise.
 * @method static mixed get(string $className)
 *     Retrieve a singleton instance of a specified class.
 *     Parameters:
 *     - $className: The name of the class to retrieve a singleton instance of.
 *     Returns:
 *     - mixed: Singleton instance of the specified class.
 * @method static mixed resolve(?string $className, ?array $arguments = [])
 *     Resolve an instance of a specified class.
 *     Parameters:
 *     - $className: The name of the class to resolve.
 *     - $arguments: Optional arguments to pass to the class constructor.
 *     Returns:
 *     - mixed: Instance of the resolved class or null if not resolved.
 * @method static mixed make(string $className, mixed ...$arguments)
 *     Create a new instance of a specified class.
 *     Parameters:
 *     - $className: The name of the class to instantiate.
 *     - $arguments: Optional parameters to pass to the class constructor as key-value pairs.
 *         Each argument is expected to be provided in pairs: a parameter name (string)
 *         followed by its corresponding value.
 *         For example, you can use:
 *         - make(Note::class, $message, 'alert')
 *         Which will be converted to:
 *         ['message' => $message, 'type' => 'alert']
 *     Returns:
 *     - mixed: Instance of the specified class.
 *     Throws:
 *     - InvalidArgumentException: If $className is null or empty.
 * @method static bool isEnabled(string $moduleName)
 *     Check if a module is installed and enabled.
 *     Parameters:
 *     - $moduleName: The name of the module to check.
 *     Returns:
 *     - bool: True if the module is installed and enabled; false otherwise.
 * @method static array getBindings()
 *     Retrieve all bindings in the container.
 *     Returns:
 *     - array: An associative array of all class bindings.
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
