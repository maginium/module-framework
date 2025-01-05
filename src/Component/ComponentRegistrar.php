<?php

declare(strict_types=1);

namespace Maginium\Framework\Component;

use const VENDOR_AUTOLOAD;

use Composer\Autoload\ClassLoader;
use Exception;
use Magento\Framework\Component\ComponentRegistrar as BaseComponentRegistrar;
use Maginium\Foundation\Exceptions\LogicException;
use Maginium\Framework\Component\Interfaces\ComponentRegistrarInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;

use function Laravel\Prompts\warning;

/**
 * Component Registrar provides the ability to statically register components.
 *
 * This class is responsible for managing the registration of Magento components such as modules, libraries,
 * themes, languages, and setup scripts. It offers methods to register components, retrieve their paths, and
 */
class ComponentRegistrar extends BaseComponentRegistrar implements ComponentRegistrarInterface
{
    /**
     * Retrieves the path to a registered component based on its name and type.
     *
     * This static method normalizes the component name and checks if the path for the
     * given component (of a specific type) exists. If the path is found, it returns
     * the path; otherwise, it returns null.
     *
     * @param  string|null  $componentName  The component name in format Vendor_Module (e.g., 'Vendor_Test').
     * @param  string  $type  The type of component (e.g., 'module', 'library').
     *
     * @return string|null The path to the component if found, or null if not.
     */
    public static function path(?string $componentName, string $type): ?string
    {
        // Normalize the module name to Vendor_Module format using the Str class
        $normalizedModuleName = Str::replace(['\\', '/'], '_', $componentName);

        // Use reflection to access the private $paths property in the parent class
        $pathsProperty = Reflection::getProperty(BaseComponentRegistrar::class, 'paths');
        $pathsProperty->setAccessible(true);

        // Get the value of $paths
        $paths = $pathsProperty->getValue();

        // Check if the module path exists for the given type and return it, or return null if not found
        return $paths[$type][$normalizedModuleName] ?? null;
    }

    /**
     * Registers a component with its type, name, and path.
     *
     * This method ensures that a component is registered only once. If a component with
     * the same type and name has already been registered, a \LogicException is thrown.
     * The method checks if the component is already autoloaded via PSR-4 before registering it.
     *
     * @param  string  $type  The type of the component (e.g., 'module', 'library').
     * @param  string  $componentName  The fully-qualified name of the component (e.g., 'Vendor_Module').
     * @param  string  $path  The absolute file system path to the component directory.
     *
     * @throws LogicException If the component is already registered.
     */
    public static function register($type, $componentName, $path): void
    {
        // Check if the provided path for the component exists
        if (! file_exists($path)) {
            // If the path does not exist, log a warning message and return early
            warning(__('⚠️ the following module %1 will not be installed', $componentName)->render());

            return;
        }

        // Check if the component is already autoloaded via PSR-4
        // if (static::isComponentAutoloadedPSR4($componentName)) {
        //     // Skip registration if the component is already autoloaded
        //     return;
        // }

        // Ensure that the component is not already registered by calling the parent register method
        parent::register($type, $componentName, $path);
    }

    /**
     * Retrieve the Composer autoloader based on the vendor path.
     *
     * This method loads the Composer autoloader from the autoload file found in the vendor directory.
     * It ensures that the autoloader is available for registering componentNames and managing autoloading.
     *
     * @return ClassLoader Returns the Composer autoloader instance.
     */
    public static function getAutoloader(): ClassLoader
    {
        // Include the Composer autoload file and return the ClassLoader instance.
        // This file is responsible for managing the automatic loading of classes and componentNames.
        return require VENDOR_AUTOLOAD;
    }

    /**
     * Check if a component namespace is autoloaded via PSR-4 in Composer.
     *
     * This method checks if the given component name (namespace) is registered in Composer's
     * PSR-4 autoload mappings. It considers the prefix for the namespace and determines
     * if the component is available through the autoloader.
     *
     * @param  string  $componentName  The fully qualified component name (e.g., Maginium_Test).
     *
     * @return bool True if the component is autoloaded via PSR-4, otherwise false.
     */
    private static function isComponentAutoloadedPSR4($componentName): bool
    {
        try {
            // Get the autoloader instance
            $autoloader = static::getAutoloader();

            // Convert component name to PSR-4 namespace format (e.g., Maginium_Test -> Maginium\_Test)
            $namespace = str_replace('_', '\\', $componentName) . '\\';

            // Retrieve all PSR-4 mappings from the autoloader
            $psr4Mappings = $autoloader->getPrefixesPsr4();

            // Check if the prefix exists in the PSR-4 mappings using the Arr class
            return Arr::has($psr4Mappings, $namespace);
        } catch (Exception $e) {
            // Handle the exception, logging or returning false
            // This prevents the application from breaking due to any issues with the autoloader
            error_log('Error checking PSR-4 autoload: ' . $e->getMessage());

            return false;
        }
    }
}
