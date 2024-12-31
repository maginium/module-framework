<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Config\Interfaces\FactoryInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Config service.
 *
 * This class acts as a simplified interface to access the ConfigInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * @method static \Maginium\Framework\Config\Interfaces\ConfigInterface driver(?string $name = null) Get a driver instance by name. Retrieves a driver instance based on the provided driver name. If no name is provided, it returns the default driver.
 * @method static \Maginium\Framework\Config\Interfaces\ConfigInterface scope(?string $scope = null, ?int $id = null) Sets the scope and id for the configuration and returns a new instance for method chaining.
 * @method static mixed get(string $path, ?string $default = null) Retrieves a configuration value or a default.
 * @method static ?int getScopeId() Retrieves the current store ID or null for the default store.
 * @method static \Maginium\Framework\Config\Interfaces\ConfigInterface setScopeId(int $scopeId) Sets the store ID for configuration scope.
 * @method static string getScope() Retrieves the current configuration scope (e.g., 'store', 'website').
 * @method static \Maginium\Framework\Config\Interfaces\ConfigInterface setScope(string $scope) Sets the scope for configuration retrieval.
 * @method static mixed getEncrypted(string $path, (callable():(string|null))|string|null $default = null) Retrieves and decrypts an encrypted configuration value.
 * @method static mixed getOrThrow(string $path, string $default) Retrieves a configuration value or throws an exception.
 * @method static ?string getString(string $path, (callable():(string|null))|string|null $default = null) Retrieves a configuration value as a string.
 * @method static ?int getInt(string $path, (callable():(int|null))|int|null $default = null) Retrieves a configuration value as an integer.
 * @method static ?float getFloat(string $path, (callable():(float|null))|float|null $default = null) Retrieves a configuration value as a float.
 * @method static ?bool getBool(string $path, (callable():(bool|null))|bool|null $default = null) Retrieves a configuration value as a boolean.
 * @method static ?array getArray(string $path, (callable():(array|null))|array|null $default = null, ?string $separator) Retrieves a configuration value as an array.
 * @method static mixed getObject(string $path, mixed $default = null) Retrieves a configuration value as an object.
 * @method static scope(?string $scope = 'default', ?int $id = null) Sets the scope and ID for the configuration and returns a new instance for method chaining.
 * @method ?int getScopeId() Retrieves the store ID the configuration should be scoped to.
 * @method $this setScopeId(?int $scopeId) Sets the store ID for the configuration scope.
 * @method string getScope() Retrieves the scope (e.g., 'store', 'website') for the configuration.
 * @method $this setScope(?string $scope) Sets the scope for the configuration.
 * @method void resetScope() Resets the scope and scope ID to their default values.
 *
 * @see FactoryInterface
 */
class Config extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return FactoryInterface::class;
    }
}
