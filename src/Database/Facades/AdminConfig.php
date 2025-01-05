<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Facades;

use Illuminate\Support\Traits\Macroable;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\App\DeploymentConfig\Writer as DeploymentConfigWriter;
use Maginium\Framework\Support\Validator;

/**
 * AdminConfig class for managing Magento's admin configuration settings.
 *
 * This class provides methods for retrieving, setting, and restoring configuration values
 * in Magento, as well as appending new values to existing configuration paths.
 *
 * @method mixed get(string $path, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, ?int $scopeId = null) Retrieve a configuration value from a given path.
 * @method void set(string|array $path, string|int|array|null $value = null, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, int $scopeId = 0) Set a configuration value at a given path.
 * @method void save(array $data, bool $override = false, array $comments = [], bool $lock = false) Saves configuration data in a specified file.
 * @method void restore(string|array $path, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, int $scopeId = 0) Restore configuration value(s) to their default state.
 * @method void reset(string|array $path, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, int $scopeId = 0) Reset configuration value(s) to original default value. Deprecated, use restore() instead.
 * @method void append(string $path, string|int|array $value, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, ?int $scopeId = null) Append a value to an existing configuration value.
 */
class AdminConfig
{
    use Macroable;

    /**
     * The deployment config writer for saving configuration values.
     *
     * @var DeploymentConfigWriter
     */
    protected DeploymentConfigWriter $deploymentConfigWriter;

    /**
     * The config writer for saving configuration values.
     *
     * @var ConfigWriter
     */
    protected ConfigWriter $configWriter;

    /**
     * The scope config for retrieving configuration values.
     *
     * @var ScopeConfig
     */
    protected ScopeConfig $scopeConfig;

    /**
     * AdminConfig constructor.
     *
     * Initializes the AdminConfig class with required dependencies.
     *
     * @param ScopeConfig $scopeConfig The scope config to retrieve configuration values.
     * @param ConfigWriter $configWriter The config writer to save configuration values.
     * @param DeploymentConfigWriter $deploymentConfigWriter The deployment config writer to save configuration values.
     */
    public function __construct(
        ScopeConfig $scopeConfig,
        ConfigWriter $configWriter,
        DeploymentConfigWriter $deploymentConfigWriter,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->deploymentConfigWriter = $deploymentConfigWriter;
    }

    /**
     * Retrieve a configuration value from a given path.
     *
     * This method fetches a configuration value for a specific scope (e.g., default, website, store)
     * and scope ID (e.g., store ID, website ID).
     *
     * @param string $path The configuration path to retrieve the value from.
     * @param string $scope The scope type (default, website, store).
     * @param int|null $scopeId The scope ID (optional; used when specific to a website/store).
     *
     * @return mixed The configuration value.
     */
    public function get(string $path, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, ?int $scopeId = null)
    {
        return $this->scopeConfig->getValue($path, $scope, $scopeId);
    }

    /**
     * Saves config in specified file.
     *
     * Usage:
     * ```php
     * saveConfig(
     *      [
     *          ConfigFilePool::APP_ENV => ['some' => 'value'],
     *      ],
     *      true,
     *      null,
     *      [],
     *      false
     * )
     * ```
     *
     * @param array $data The data to be saved
     * @param bool $override Whether values should be overridden
     * @param array $comments The array of comments
     * @param bool $lock Whether the file should be locked while writing
     *
     * @throws FileSystemException
     *
     * @return void
     */
    public function save(array $data, $override = false, array $comments = [], bool $lock = false): void
    {
        $this->deploymentConfigWriter->saveConfig($data, $override, null, $comments, $lock);
    }

    /**
     * Set a configuration value at a given path.
     *
     * This method saves a configuration value for a given path and scope.
     * It can also handle multiple configuration paths if an array is provided.
     *
     * @param string|array $path The configuration path(s) to save.
     * @param string|int|array $value The value(s) to be set for the given path(s).
     * @param string $scope The scope type (default, website, store).
     * @param int $scopeId The scope ID (optional; used when specific to a website/store).
     *
     * @return void
     */
    public function set($path, $value = null, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, int $scopeId = 0): void
    {
        // If path is a string, save the single configuration value
        if (! Validator::isArray($path)) {
            $this->configWriter->save($path, $value, $scope, $scopeId);

            return;
        }

        // If path is an array, iterate and save multiple configuration values
        $paths = $path;

        foreach ($paths as $path => $value) {
            $this->set(
                $path,
                $value['value'] ?? $value,
                $value['scope'] ?? $scope,
                $value['scope_id'] ?? $scopeId,
            );
        }
    }

    /**
     * Restore a configuration value to its default state in the database.
     *
     * This method deletes a given configuration path, causing it to revert to its default value as
     * defined in the config.xml file. It can handle multiple paths if an array is provided.
     *
     * @param string|array $path The configuration path(s) to restore.
     * @param string $scope The scope type (default, website, store).
     * @param int $scopeId The scope ID (optional; used when specific to a website/store).
     *
     * @return void
     */
    public function restore($path, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, int $scopeId = 0): void
    {
        $paths = Validator::isString($path) ? [$path] : $path;

        foreach ($paths as $path) {
            // Delete the configuration entry, restoring it to default.
            $this->configWriter->delete($path, $scope, $scopeId);
        }
    }

    /**
     * Reset a configuration value to its original default value.
     *
     * This method is deprecated and has been replaced with the `restore()` method.
     * It provides backward compatibility for resetting configurations.
     *
     * @deprecated 2.0.3 Replaced with "restore" to match the admin settings naming convention.
     * @see AdminConfig::restore()
     *
     * @param string|array $path The configuration path(s) to reset.
     * @param string $scope The scope type (default, website, store).
     * @param int $scopeId The scope ID (optional; used when specific to a website/store).
     *
     * @return void
     */
    public function reset($path, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, int $scopeId = 0): void
    {
        $this->restore($path, $scope, $scopeId);
    }

    /**
     * Append a value to an existing configuration value.
     *
     * This method appends the given value to the existing configuration value at a specified path.
     * It is useful when adding additional data to a configuration (e.g., appending values to an array or string).
     *
     * @param string $path The configuration path to append the value to.
     * @param string|int|array $value The value to append.
     * @param string $scope The scope type (default, website, store).
     * @param int|null $scopeId The scope ID (optional; used when specific to a website/store).
     *
     * @return void
     */
    public function append(string $path, $value, string $scope = ScopeConfig::SCOPE_TYPE_DEFAULT, ?int $scopeId = null): void
    {
        // Retrieve the current value of the configuration path.
        $oldValue = $this->scopeConfig->getValue($path, $scope, $scopeId);

        // Append the new value to the existing value.
        $newValue = $oldValue . $value;

        // Save the updated configuration value.
        $this->configWriter->save($path, $newValue, $scope, $scopeId ?: 0);
    }
}
