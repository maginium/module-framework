<?php

declare(strict_types=1);

namespace Maginium\Framework\Component;

/**
 * Library Component Registrar.
 *
 * This class is responsible for managing the registration and retrieval of library-related components in Magento,
 * including external libraries, packages, and third-party integrations. It extends the base `ComponentRegistrar` class,
 * which provides core functionality for component registration.
 *
 * The `Library` registrar can be used to register and retrieve paths for library components, enabling seamless
 * integration of third-party libraries and managing their locations within the Magento system.
 */
class Library
{
    /**
     * Registers a library component with the provided path.
     *
     * This method registers an external library or third-party package by associating it with a component name and path.
     *
     * @param  string  $componentName  The name of the library component (e.g., 'jquery', 'someLibrary').
     * @param  string  $componentPath  The file path to the library component (e.g., '/path/to/library').
     */
    public static function register(string $componentName, string $componentPath): void
    {
        ComponentRegistrar::register(ComponentRegistrar::LIBRARY, $componentName, $componentPath);
    }

    /**
     * Retrieves the path of the registered library component.
     *
     * This method returns the file path of the registered library component, if it exists.
     *
     * @param  string  $componentName  The name of the library component to retrieve.
     *
     * @return string|null The path of the registered library component or null if not found.
     */
    public static function getPath(string $componentName): ?string
    {
        return ComponentRegistrar::path($componentName, type: ComponentRegistrar::LIBRARY);
    }
}
