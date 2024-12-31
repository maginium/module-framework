<?php

declare(strict_types=1);

namespace Maginium\Framework\Container;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Container\Interfaces\ContainerInterface;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Reflection;

/**
 * Class ContainerManager.
 *
 * This class serves as a dependency resolution and management tool, leveraging Magento's Object Manager
 * to handle object creation and dependency injection. It adheres to the `ContainerInterface`, ensuring
 * it provides the necessary contract for container-based operations.
 */
class ContainerManager implements ContainerInterface
{
    /**
     * @var ObjectManagerInterface|null Object Manager instance.
     */
    protected ?ObjectManagerInterface $objectManager = null;

    /**
     * Container constructor.
     *
     * @param  ObjectManagerInterface|null  $objectManager  Object Manager instance (optional).
     */
    public function __construct(?ObjectManagerInterface $objectManager = null)
    {
        $this->objectManager = $objectManager ?? ObjectManager::getInstance();
    }

    /**
     * Retrieve a singleton instance of a specified class.
     *
     * @param  string  $className  The class name to retrieve.
     *
     * @throws InvalidArgumentException If $className is null or empty.
     *
     * @return mixed The singleton instance of the specified class.
     */
    public function get(string $className): mixed
    {
        if ($className === null || $className === '') {
            throw InvalidArgumentException::make(__('The class name cannot be null or empty.'));
        }

        return $this->objectManager->get($className);
    }

    /**
     * Check if a class can be resolved.
     *
     * @param  string  $className  The class name to check.
     *
     * @return bool True if the class can be resolved, false otherwise.
     */
    public function has(string $className): bool
    {
        if ($className === null || $className === '') {
            return false;
        }

        // Extract module name from the class name for module-specific checks
        $moduleName = $this->extractModuleName($className);

        // Check if the module is active and the class is not an interface, facade, or trait
        if ($this->isEnabled($moduleName) &&
            ! Reflection::isFacade($className) &&
            ! Reflection::isTrait($className) &&
            ! interface_exists($className)) {
            return true;
        }

        // Check if the class itself is an interface
        return interface_exists($className);
    }

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
    public function resolve(?string $className, ?array $arguments = []): ?object
    {
        if ($className === null || $className === '') {
            throw InvalidArgumentException::make(__('$className cannot be null or empty'));
        }

        // Check if the class can be resolved
        if ($this->has($className)) {
            // If the class can be resolved, return a singleton instance
            return $this->get($className);
        }

        // If not, create a new instance
        return $this->make($className, $arguments);
    }

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
    public function make(string $className, ...$arguments): mixed
    {
        // Validate that the class name is not null or empty.
        if ($className === null || $className === '') {
            throw InvalidArgumentException::make(__('The class name cannot be null or empty.'));
        }

        // Call the object manager to create an instance of the specified class with the prepared parameters.
        return $this->objectManager->create($className, ...$arguments);
    }

    /**
     * Check if a module is installed and enabled.
     *
     * @param  string  $moduleName  The name of the module to check.
     *
     * @throws InvalidArgumentException If $moduleName is null or empty.
     *
     * @return bool True if the module is enabled, false otherwise.
     */
    public function isEnabled(string $moduleName): bool
    {
        if ($moduleName === null || $moduleName === '') {
            // Throw an exception if $moduleName is null or empty
            // Throw the exception
            throw InvalidArgumentException::make(__('$moduleName cannot be null or empty'));
        }

        // Get the Module Manager instance
        $moduleManager = $this->get(ModuleManager::class);

        // Check if the module is enabled using Module Manager
        return $moduleManager->isEnabled($moduleName);
    }

    /**
     * Retrieve all bindings in the container.
     *
     * This method returns an associative array of all class bindings
     * available in the object manager.
     *
     * @return array An associative array of class bindings.
     */
    public function getBindings(): array
    {
        // Get the configuration object that contains the preferences
        $config = $this->get(ConfigInterface::class);

        // Retrieve and return an associative array of class bindings from preferences
        return $config->getPreferences();
    }

    /**
     * Extract module name from the full class name.
     *
     * @param  string  $className  The full class name.
     *
     * @return string The module name extracted from the class name.
     */
    private function extractModuleName(string $className): string
    {
        // Split the class name by namespace separator and extract the first two parts
        $parts = Php::explode('\\', $className, 3);

        // Form the module name by joining the first two parts with underscore
        return Php::implode('_', Php::arraySlice($parts, 0, 2));
    }
}
