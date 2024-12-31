<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Drivers;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Config\Config;
use Maginium\Framework\Config\Helpers\Cache as CacheManager;
use Maginium\Framework\Config\Interfaces\DriverInterface;
use Maginium\Framework\Config\Traits\Scopeable;
use Maginium\Framework\Log\Interfaces\LoggerInterface;
use Maginium\Framework\Support\Facades\Crypt;
use Maginium\Framework\Support\Str;

/**
 * Class ScopeConfig.
 *
 * Manages configuration settings across different scopes with caching support.
 */
class ScopeConfig extends Config implements DriverInterface
{
    use Scopeable;

    /**
     * Logger instance for logging messages and debugging.
     */
    private LoggerInterface $logger;

    /**
     * @var CacheManager Cache management for saving and retrieving configuration data.
     */
    private CacheManager $cacheManager;

    /**
     * @var ScopeConfigInterface Interface for retrieving configuration values scoped to a store.
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * ScopeConfig constructor.
     *
     * @param  LoggerInterface  $logger  Logs system events and errors.
     * @param  CacheManager  $cacheManager  Cache manager for configuration values.
     * @param  ScopeConfigInterface  $scopeConfig  Scope-specific configuration service.
     */
    public function __construct(
        LoggerInterface $logger,
        CacheManager $cacheManager,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->cacheManager = $cacheManager;

        // Set Log class name
        $logger->setClassName(static::class);
    }

    /**
     * Retrieves the value of a configuration variable, with fallback to a default.
     *
     * @param  string  $path  Configuration key (e.g., 'web/secure/base_url').
     * @param  mixed|null  $default  Default value to return if the configuration key is not found.
     *
     * @return mixed The configuration value or the provided default.
     */
    public function get(string $path, $default = null): mixed
    {
        try {
            // Format the configuration path to ensure consistent key structure.
            $formattedPath = $this->formatConfigPath($path);

            // Generate a unique cache key based on the configuration path and scope ID.
            $cacheKey = $this->cacheManager->generateCacheKey($formattedPath, $this->getScopeId());

            // Check if the configuration value is already cached and return it if found.
            if ($this->cacheManager->has($cacheKey)) {
                return $this->cacheManager->load($cacheKey);
            }

            // Retrieve the configuration value using the scoped configuration service.
            $value = $this->scopeConfig->getValue($formattedPath, $this->getScope(), $this->getScopeId());

            // Reset the scope to its default values after retrieving the configuration.
            $this->resetScope();

            // Check if the value is empty, and return null if it is
            if (empty($value)) {
                return $default ?? null;
            }

            // Return the configuration value or the default if the value is null.
            return $value ?? $default;
        } catch (Exception $e) {
            // Log the exception with the configuration path for debugging.
            $this->logger->error('Error retrieving configuration for path: ' . $path, ['exception' => $e]);

            // Return the default value if an exception occurs.
            return $default;
        }
    }

    /**
     * Retrieves and decrypts an encrypted configuration value.
     *
     * @param  string  $path  Configuration key for the encrypted value.
     * @param  mixed|null  $default  Default value to return if the key is not found.
     *
     * @throws LocalizedException If an error occurs during decryption.
     *
     * @return mixed The decrypted configuration value or the default.
     */
    public function getEncrypted(string $path, $default = null): mixed
    {
        try {
            // Generate a unique cache key based on the configuration path and scope ID.
            $cacheKey = $this->cacheManager->generateCacheKey($path, $this->getScopeId());

            // Check if the encrypted value is cached and decrypt it if found.
            if ($this->cacheManager->has($cacheKey)) {
                $encryptedValue = $this->cacheManager->load($cacheKey);

                // Decrypt the cached encrypted value and return it.
                return Crypt::decrypt($encryptedValue);
            }

            // Retrieve the encrypted value using the `get` method.
            $encryptedValue = $this->get($path, $default);

            // Decrypt the retrieved value.
            $decryptedValue = Crypt::decrypt($encryptedValue);

            // Save the encrypted value in the cache for future retrieval.
            $this->cacheManager->save($cacheKey, $encryptedValue);

            // Return the decrypted value.
            return $decryptedValue;
        } catch (Exception $e) {
            // Log the exception with the configuration path for debugging.
            $this->logger->error('Error retrieving encrypted configuration for path: ' . $path, ['exception' => $e]);

            // Throw a localized exception to inform the caller of the failure.
            throw LocalizedException::make(__('Error retrieving encrypted configuration variable.'));
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
        return Str::contains($path, '.') ? Str::replace('.', '_', $path) : $path;
    }
}
