<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Registry as RegistryManager;
use Maginium\Framework\Support\Facade;

/**
 * Class Registry.
 *
 * Facade for interacting with the Registry service, which manages values in the registry.
 *
 * @method static mixed registry(string $key)
 *     Retrieve a value from the registry by the specified key.
 *     Parameters:
 *     - string $key: The key to look up in the registry.
 *     Returns:
 *     - mixed: The value associated with the key or null if not found.
 * @method static void register(string $key, mixed $value, bool $graceful = false)
 *     Register a new variable in the registry.
 *     Parameters:
 *     - string $key: The key to register.
 *     - mixed $value: The value to associate with the key.
 *     - bool $graceful: Optional. If true, suppresses errors if the key already exists.
 * @method static void unregister(string $key)
 *     Unregister a variable from the registry by its key.
 *     Parameters:
 *     - string $key: The key to unregister.
 * @method static void _resetState()
 *     Reset the registry state, clearing all stored values.
 *
 * @see RegistryManager
 */
class Registry extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return RegistryManager::class;
    }
}
