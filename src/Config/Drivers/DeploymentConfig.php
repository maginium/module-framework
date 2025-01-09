<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Drivers;

use Magento\Framework\App\DeploymentConfig as BaseDeploymentConfig;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Config\Config;
use Maginium\Framework\Config\Interfaces\DriverInterface;
use Maginium\Framework\Log\Interfaces\LoggerInterface;
use Maginium\Framework\Support\Str;

/**
 * Class ConfigManager.
 *
 * This class manages configuration settings in the application.
 * It interfaces with various configuration layers including deployment,
 * scope configuration, environment variables, and a caching mechanism.
 *
 * The ConfigManager provides a unified and robust approach to retrieving
 * configuration values while ensuring performance by caching results.
 */
class DeploymentConfig extends Config implements DriverInterface
{
    /**
     * Logger instance for logging messages and debugging.
     */
    private LoggerInterface $logger;

    /**
     * @var BaseDeploymentConfig Handles deployment-level configuration.
     */
    private BaseDeploymentConfig $deploymentConfig;

    /**
     * ConfigManager constructor.
     *
     * Initializes the configuration manager with the necessary services and sets up the logging context.
     *
     * @param  LoggerInterface  $logger  Logs system events and errors.
     * @param  DeploymentConfig  $deploymentConfig  Deployment configuration service for global settings.
     */
    public function __construct(
        LoggerInterface $logger,
        BaseDeploymentConfig $deploymentConfig,
    ) {
        $this->logger = $logger;
        $this->deploymentConfig = $deploymentConfig;

        // Set Log class name
        $logger->setClassName(static::class);
    }

    /**
     * Retrieves the value of a configuration variable, falling back to a default if not found.
     *
     * The method checks multiple sources in the following order:
     * 1. Environment variables (using `Env::get()`).
     * 2. Cache (using `CacheManager`).
     * 3. Deployment configuration (using `DeploymentConfig`).
     * 4. Store-specific scope configuration (using `ScopeConfigInterface`).
     *
     * @param  string  $path  The key representing the configuration variable (e.g., 'web/secure/base_url').
     * @param  string  $default  The value to return if the configuration is not found.
     *
     * @return mixed The configuration value or the default value if no configuration is found.
     */
    public function get(string $path, $default = null): mixed
    {
        try {
            // Format the configuration path for use in Env and cache retrieval.
            $formattedPath = $this->formatConfigPath($path);

            // Third check: Deployment configuration
            $value = $this->deploymentConfig->get($formattedPath, null);

            // Check if the value is empty, and return null if it is
            if (empty($value)) {
                return $default ?? null;
            }

            // Return the found value or default if nothing was found
            return $value ?? $default;
        } catch (Exception $e) {
            // Log any error and return the default value to avoid breaking functionality.
            $this->logger->error('Error retrieving configuration for path: ' . $path, ['exception' => $e]);

            return $default;  // Return default value on failure
        }
    }

    /**
     * Formats the configuration path for internal usage.
     *
     * Ensures consistent formatting of configuration keys by replacing dots with underscores
     * for compatibility with certain systems that do not support dot notation.
     *
     * @param  string  $path  The configuration key.
     *
     * @return string The formatted configuration key.
     */
    private function formatConfigPath(string $path): string
    {
        // Replace dots with underscores if the path contains a dot; otherwise, return the original path.
        return Str::contains($path, '.') ? Str::replace('.', '/', $path) : $path;
    }
}
