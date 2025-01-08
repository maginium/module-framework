<?php

declare(strict_types=1);

namespace Maginium\Framework\Application\ServiceProvider;

use Maginium\Foundation\Abstracts\DataSource\DataSourceRegistry;
use Maginium\Framework\Support\ServiceProvider;

/**
 * Registry.
 *
 * This class manages multiple service providers, allowing dynamic access to providers for app bootstrapping.
 */
class Registry extends DataSourceRegistry
{
    /**
     * Constructor to initialize the service providers.
     *
     * @param array $providers Associative array of entities and their data sources.
     */
    public function __construct(array $providers = [])
    {
        parent::__construct($providers);
    }

    /**
     * Retrieve all service providers.
     *
     * @param string $providerName The service provider name (e.g., 'cache', 'database').
     *
     * @return ServiceProvider[] The service provider configuration or instance.
     */
    public function getServiceProviders(): array
    {
        return $this->all();
    }

    /**
     * Retrieve the service provider by its name.
     *
     * @param string $providerName The service provider name (e.g., 'cache', 'database').
     *
     * @return ServiceProvider The service provider configuration or instance.
     */
    public function getServiceProvider(string $providerName): ServiceProvider
    {
        return $this->get($providerName);
    }

    /**
     * Add a service provider dynamically.
     *
     * @param string $providerName The name of the service provider (e.g., 'cache', 'database').
     * @param ServiceProvider $serviceProvider The service provider instance or configuration.
     *
     * @return void
     */
    public function addServiceProvider(string $providerName, ServiceProvider $serviceProvider): void
    {
        // Dynamically add the service provider to the registry
        $this->put($providerName, $serviceProvider);
    }
}
