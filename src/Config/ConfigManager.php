<?php

declare(strict_types=1);

namespace Maginium\Framework\Config;

use Maginium\Framework\Config\Drivers\DeploymentConfigFactory;
use Maginium\Framework\Config\Drivers\EnvConfigFactory;
use Maginium\Framework\Config\Drivers\ScopeConfigFactory;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Config\Interfaces\ConfigInterface;
use Maginium\Framework\Config\Interfaces\DriverInterface;
use Maginium\Framework\Config\Interfaces\FactoryInterface;
use Maginium\Framework\Log\Interfaces\LoggerInterface;
use Maginium\Framework\Support\MultipleInstanceManager;

/**
 * Class ConfigManager.
 *
 * This class is responsible for managing config across multiple instances.
 * It provides mechanisms to handle config control and limits the execution
 * of specific operations to prevent race conditions.
 *
 * It extends `MultipleInstanceManager` to manage different config drivers
 * and utilizes a `Driver` mixin to provide access to various config driver methods.
 *
 * @mixin ConfigInterface This mixin adds methods to handle config drivers.
 * @mixin DriverInterface This mixin adds methods to handle config drivers.
 */
class ConfigManager extends MultipleInstanceManager implements FactoryInterface
{
    /**
     * Factory for creating environment-based configuration instances.
     */
    private EnvConfigFactory $envConfigFactory;

    /**
     * Factory for creating scope-specific configuration instances.
     */
    private ScopeConfigFactory $scopeConfigFactory;

    /**
     * Factory for handling deployment-level configuration.
     */
    private DeploymentConfigFactory $deploymentConfigFactory;

    /**
     * Logger instance for logging messages and debugging.
     */
    private LoggerInterface $logger;

    /**
     * ConfigManager constructor.
     *
     * Initializes the configuration manager with dependencies and sets the logging context for better debugging.
     *
     * @param  LoggerInterface  $logger  Logs system events and errors.
     * @param  EnvConfigFactory  $envConfigFactory  Factory for environment configurations.
     * @param  ScopeConfigFactory  $scopeConfigFactory  Factory for store-specific configurations.
     * @param  DeploymentConfigFactory  $deploymentConfigFactory  Factory for deployment configurations.
     */
    public function __construct(
        LoggerInterface $logger,
        EnvConfigFactory $envConfigFactory,
        ScopeConfigFactory $scopeConfigFactory,
        DeploymentConfigFactory $deploymentConfigFactory,
    ) {
        $this->logger = $logger;
        $this->envConfigFactory = $envConfigFactory;
        $this->scopeConfigFactory = $scopeConfigFactory;
        $this->deploymentConfigFactory = $deploymentConfigFactory;

        // Set Log class name
        $logger->setClassName(static::class);
    }

    /**
     * Get a driver instance by name.
     *
     * This method retrieves a driver instance based on the provided driver name.
     * It will return the instance of the specified driver, or the default driver if no name is provided.
     *
     * @param  string|null  $name  The name of the config driver.
     *
     * @return mixed The driver instance corresponding to the provided name.
     */
    public function driver(?string $name = null): mixed
    {
        // Fetch the driver instance from the instance manager.
        return $this->instance($name);
    }

    /**
     * Creates and returns an instance of the ScopeConfig driver.
     *
     * This driver is responsible for handling store-specific configuration settings.
     *
     * @return DriverInterface An instance of the ScopeConfig driver.
     */
    public function createScopeDriver(): DriverInterface
    {
        return $this->scopeConfigFactory->create();
    }

    /**
     * Creates and returns an instance of the environment variable driver.
     *
     * This driver is responsible for interacting with environment variables.
     *
     * @return DriverInterface An instance of the environment variable driver.
     */
    public function createEnvDriver(): DriverInterface
    {
        return $this->envConfigFactory->create();
    }

    /**
     * Creates and returns an instance of the DeploymentConfig driver.
     *
     * This driver is responsible for handling deployment-level configuration settings.
     *
     * @return DriverInterface An instance of the DeploymentConfig driver.
     */
    public function createDeploymentDriver(): DriverInterface
    {
        return $this->deploymentConfigFactory->create();
    }

    /**
     * Get the default instance name.
     *
     * This method retrieves the default driver instance name. The default instance is used
     * when no specific driver name is provided. It fetches this information from the configuration.
     *
     * @return string The default instance name, which defaults to "process" if not configured.
     */
    public function getDefaultInstance(): ?string
    {
        // Fetch the default driver name from configuration, fallback to 'process'
        return ConfigDrivers::SCOPE;
    }

    /**
     * Get the instance-specific configuration.
     *
     * This method retrieves configuration settings specific to the given config driver instance.
     * It fetches the driver configuration from the global configuration settings.
     *
     * @param  string  $name  The name of the config driver instance.
     *
     * @return array An array of configuration settings for the given driver instance.
     */
    public function getInstanceConfig($name): array
    {
        // Fetch the driver configuration from the global configuration
        return ['driver' => $name ?: $this->getDefaultInstance()];
    }
}
