<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Traits;

use Maginium\Framework\Container\Facades\Container;

/**
 * Trait Scopeable.
 *
 * Provides methods to manage configuration scope and scope ID for store-specific settings.
 * This trait enables dynamic scoping of configuration, allowing different parts
 * of the application to retrieve settings tailored to a specific store, website, or other scopes.
 *
 * @method static scope(?string $scope = 'default', ?int $id = null) Sets the scope and ID for the configuration and returns a new instance for method chaining.
 * @method ?int getScopeId() Retrieves the store ID the configuration should be scoped to.
 * @method $this setScopeId(?int $scopeId) Sets the store ID for the configuration scope.
 * @method string getScope() Retrieves the scope (e.g., 'store', 'website') for the configuration.
 * @method $this setScope(?string $scope) Sets the scope for the configuration.
 * @method void resetScope() Resets the scope and scope ID to their default values.
 */
trait Scopeable
{
    /**
     * @var string Configuration scope (e.g., 'store', 'website').
     *             The default value is 'default', representing global or application-wide settings.
     */
    private string $scope = 'default';

    /**
     * @var ?int Store ID, used to specify which store's configuration to retrieve.
     *           If null, the default store's configuration is used.
     */
    private ?int $scopeId = null;

    /**
     * Sets the scope and ID for the configuration and returns a new instance for method chaining.
     *
     * @param  string|null  $scope  The configuration scope (e.g., 'store', 'website').
     *                              If null, defaults to 'default'.
     * @param  int|null  $id  Optional configuration ID (e.g., store ID or resource ID).
     *                        If null, no specific store or resource is targeted.
     *
     * @return static A new instance with the applied scope and ID.
     */
    public function scope(?string $scope = 'default', ?int $id = null): static
    {
        // Create a new instance of the calling class using the service container.
        $instance = Container::make(static::class);

        // Set the provided scope and scope ID on the new instance.
        $instance->setScope($scope)->setScopeId($id);

        // Return the new instance for method chaining or further use.
        return $instance;
    }

    /**
     * Retrieves the store ID the configuration should be scoped to.
     *
     * @return int|null The store ID if set, or null for the default store configuration.
     */
    public function getScopeId(): ?int
    {
        // Return the currently set store ID.
        return $this->scopeId;
    }

    /**
     * Sets the store ID for the configuration scope.
     *
     * @param  int|null  $scopeId  The store ID to set, or null to reset to the default store.
     *
     * @return $this The current instance for method chaining.
     */
    public function setScopeId(?int $scopeId): static
    {
        // Assign the provided store ID to the internal property.
        $this->scopeId = $scopeId;

        // Return the current instance to allow chaining of methods.
        return $this;
    }

    /**
     * Retrieves the scope (e.g., 'store', 'website') for the configuration.
     *
     * @return string The currently set scope. Defaults to 'default' if not explicitly set.
     */
    public function getScope(): string
    {
        // Return the current configuration scope.
        return $this->scope;
    }

    /**
     * Sets the scope for the configuration.
     *
     * @param  string|null  $scope  The scope to set (e.g., 'store', 'website').
     *                              If null, retains the current scope.
     *
     * @return $this The current instance for method chaining.
     */
    public function setScope(?string $scope): static
    {
        // Assign the provided scope, defaulting to the existing scope if null.
        $this->scope = $scope ?? $this->scope;

        // Return the current instance to allow chaining of methods.
        return $this;
    }

    /**
     * Resets the scope and scope ID to their default values.
     *
     * This method ensures that any temporary scoping set during a request or operation
     * is cleared, restoring the configuration manager to a global/default state.
     */
    private function resetScope(): void
    {
        // Reset the scope ID to null, indicating no specific store or resource.
        $this->scopeId = null;

        // Reset the scope to 'default', representing global settings.
        $this->scope = 'default';
    }
}
