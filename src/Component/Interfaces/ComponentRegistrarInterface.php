<?php

declare(strict_types=1);

namespace Maginium\Framework\Component\Interfaces;

use Magento\Framework\Component\ComponentRegistrarInterface as BaseComponentRegistrarInterface;
use Maginium\Foundation\Exceptions\LogicException;

/**
 * Interface for Component Registrar.
 *
 * This interface defines methods for managing Magento component paths, ensuring that
 * components can be registered, retrieved, and validated based on their types.
 */
interface ComponentRegistrarInterface extends BaseComponentRegistrarInterface
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
    public static function path(?string $componentName, string $type): ?string;

    /**
     * Registers a component by type and name with its corresponding file path.
     *
     * This method ensures that a component is registered only once. If a component with the same type and
     * name has already been registered, a LogicException is thrown to prevent re-registration.
     *
     * The method first checks if the component is autoloaded via PSR-4. If it is, the registration is skipped,
     * as it is assumed to already be handled by the autoloader.
     *
     * @param  string  $type  The type of the component (e.g., 'module', 'library').
     * @param  string  $componentName  The fully-qualified name of the component (e.g., 'Vendor_Module').
     * @param  string  $path  The absolute file system path to the component, typically the directory where the component resides.
     *
     * @throws LogicException If the component is already registered (based on its type and name).
     */
    public static function register($type, $componentName, $path): void;
}
