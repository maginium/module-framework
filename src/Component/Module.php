<?php

declare(strict_types=1);

namespace Maginium\Framework\Component;

use Maginium\Framework\Support\Str;

/**
 * Module Component Registrar.
 *
 * This class is responsible for managing the registration and retrieval of module components within Magento.
 * It extends the base `ComponentRegistrar` class, which provides the core functionality for component registration.
 *
 * The `Module` registrar allows for seamless integration and management of Magento modules, enabling the registration
 * of new modules, as well as retrieving their paths. This class helps to keep track of registered modules and their
 * locations within the Magento system.
 */
class Module
{
    /**
     * Registers a single module with its componentName and path.
     *
     * This method registers a single module by its componentName and corresponding file path.
     * It uses the `register` method to ensure the module is added to the correct component type.
     *
     * @param  string  $componentName  The fully-qualified componentName of the module (e.g., `Vendor_Module`).
     * @param  string  $path  The absolute file system path to the module's directory.
     */
    public static function register(string $componentName, string $path): void
    {
        // Register the module by passing the type 'MODULE', the componentName, and its file path.
        // The 'MODULE' constant is used to specify the component type for this registration.
        ComponentRegistrar::register(ComponentRegistrar::MODULE, $componentName, $path);
    }

    /**
     * Register each module in the provided extensions list.
     *
     * This method is used to register multiple modules in one call by iterating over the provided list of
     * module componentNames and their corresponding paths. It registers each module and sets the appropriate
     * PSR-4 autoloading for the modules.
     *
     * @param  array  $extensions  The array of module componentNames and paths, where the key is the module's componentName
     *                             and the value is the path to that module.
     */
    public static function registerModules(array $extensions): void
    {
        // Get the Composer autoloader using the predefined VENDOR_AUTOLOAD constant.
        $loader = ComponentRegistrar::getAutoloader();

        // Iterate through each extension in the provided list.
        // $componentName is the module's componentName, and $path is the file path to the module.
        foreach ($extensions as $componentName => $path) {
            // Register each module with its componentName and corresponding path.
            // The 'MODULE' constant indicates the type of component being registered (modules in this case).
            ComponentRegistrar::register(ComponentRegistrar::MODULE, $componentName, $path);

            // Set up PSR-4 autoloading for the module, which allows for proper componentName resolution.
            // The 'Str::replace' method replaces underscores in the componentName with backslashes to conform with PSR-4.
            // $loader->setPsr4() registers the componentName prefix and maps it to the corresponding directory path.
            // This enables the autoloader to find and load classes from this module based on the componentName.
            $loader->setPsr4(Str::replace('_', '\\', $componentName) . '\\', [$path]);
        }
    }

    /**
     * Static function to get the module path by its name.
     *
     * @param  string  $moduleName  The module name in either Vendor\Test or Vendor_Test format.
     *
     * @return string|null The path to the module, or null if not found.
     */
    public static function getPath(?string $moduleName): ?string
    {
        // Check if the module path exists
        return ComponentRegistrar::path($moduleName, ComponentRegistrar::MODULE);
    }
}
