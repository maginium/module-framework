<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Providers;

use Maginium\Framework\Config\EnvConfigLoader;
use Maginium\Framework\Support\ServiceProvider;

/**
 * Class ConfigServiceProvider.
 *
 * This service provider is responsible for registering and bootstrapping configuration-related
 * services. It ensures that environment-specific configurations are loaded into the application.
 */
class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This method is invoked during the service provider registration phase.
     * It is used to bind services, classes, or configurations into the application's container.
     * In this case, it ensures that environment configurations are loaded early in the application lifecycle.
     *
     * @return void
     */
    public function register(): void
    {
        // Load environment-specific configurations from the EnvConfigLoader.
        // This step ensures that configuration settings are accessible globally in the application.
        EnvConfigLoader::load();
    }
}
