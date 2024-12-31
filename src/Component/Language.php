<?php

declare(strict_types=1);

namespace Maginium\Framework\Component;

/**
 * Language Component Registrar.
 *
 * This class is responsible for managing the registration and retrieval of language-related components in Magento,
 * including language packs and locale data. It extends the base `ComponentRegistrar` class, which provides core
 * functionality for component registration.
 *
 * The `Language` registrar can be used to register and get paths for language components, allowing easier management
 * of translations and locale-specific resources within the system.
 */
class Language
{
    /**
     * Registers a language component with the provided path.
     *
     * This method is used to register a language pack or any locale-specific component.
     *
     * @param  string  $componentName  The name of the language component (e.g., 'en_US').
     * @param  string  $componentPath  The file path to the component (e.g., '/path/to/lang/en_US').
     */
    public static function register(string $componentName, string $componentPath): void
    {
        ComponentRegistrar::register(ComponentRegistrar::LANGUAGE, $componentName, $componentPath);
    }

    /**
     * Retrieves the path of the registered language component.
     *
     * This method returns the file path of the language component if it is registered.
     *
     * @param  string  $componentName  The name of the language component to retrieve.
     *
     * @return string|null The path of the registered language component or null if not found.
     */
    public static function getPath(string $componentName): ?string
    {
        return ComponentRegistrar::path($componentName, type: ComponentRegistrar::LANGUAGE);
    }
}
