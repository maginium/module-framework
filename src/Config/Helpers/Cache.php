<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Helpers;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\Store;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Log\Interfaces\LoggerInterface;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Serializer;
use Maginium\Framework\Support\Str;

/**
 * Helper class for managing caching related to URL Rewrites.
 *
 * @category Maginium
 */
class Cache extends AbstractHelper
{
    /**
     * Cache type identifier.
     */
    public const TYPE_IDENTIFIER = 'scope_config';

    /**
     * Cache tag.
     */
    public const CACHE_TAG = 'CONFIG';

    /**
     * Default cache lifetime in seconds.
     */
    public const DEFAULT_CACHE_LIFETIME = 7200;

    /**
     * @var array Local cache to store data temporarily.
     */
    protected array $localCache = [];

    private CacheInterface $cacheManager;

    /**
     * Logger instance for logging messages and debugging.
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param  Context  $context  Helper context.
     * @param  CacheInterface  $cacheManager  Cache manager.
     * @param  LoggerInterface  $logger  Logs system events and errors.
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        CacheInterface $cacheManager,
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->cacheManager = $cacheManager;

        // Set Log class name
        $logger->setClassName(static::class);
    }

    /**
     * Save data into the cache.
     *
     * @param  string  $cacheKey  Cache key.
     * @param  mixed  $data  Data to be cached.
     * @param  array  $cacheTags  Cache tags.
     * @param  int  $cacheLifetime  Cache lifetime in seconds.
     */
    public function save(
        string $cacheKey,
        $data,
        array $cacheTags = [self::CACHE_TAG],
        int $cacheLifetime = self::DEFAULT_CACHE_LIFETIME,
    ): void {
        // Store data in the local cache for quick retrieval
        $this->localCache[$cacheKey] = $data;

        // Serialize the data
        $serializedData = Serializer::serialize($data);

        try {
            // Save the serialized data to the cache
            $this->cacheManager->save($serializedData, $cacheKey, $cacheTags, $cacheLifetime);
        } catch (Exception $e) {
            // Log any exceptions that occur during the retrieval process
            $this->logger->error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));
        }
    }

    /**
     * Check if a cache key exists in the cache.
     *
     * @param  string  $cacheKey  Cache key.
     */
    public function has(string $cacheKey): bool
    {
        // Check if data is in the local cache
        if (isset($this->localCache[$cacheKey])) {
            return true;
        }

        // Check if data is in the cache manager
        $data = $this->cacheManager->load($cacheKey);

        return $data !== null && isset($this->localCache[$cacheKey]);
    }

    /**
     * Load data from the cache.
     *
     * @param  string  $cacheKey  Cache key.
     *
     * @return mixed|null
     */
    public function load(string $cacheKey)
    {
        // Check if data is already in the local cache
        if (isset($this->localCache[$cacheKey])) {
            return $this->localCache[$cacheKey];
        }

        // Attempt to load data from the cache manager
        $data = $this->cacheManager->load($cacheKey);

        // If data is found, unserialize it and store in local cache
        if ($data) {
            try {
                $this->localCache[$cacheKey] = Serializer::unserialize($data);
            } catch (Exception $e) {
                // Log any exceptions that occur during the retrieval process
                $this->logger->error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));
            }
        }

        return $this->localCache[$cacheKey] ?? null;
    }

    /**
     * Purge a specific model from the cache.
     *
     * @param  string  $cacheKey  The cache key to be purged from the cache.
     */
    public function purgeByKey(string $cacheKey): void
    {
        // Remove the data from the local cache
        unset($this->localCache[$cacheKey]);

        // Remove the data from the cache manager
        $this->cacheManager->remove($cacheKey);
    }

    /**
     * Clean the cache by specified tags.
     *
     * @param  array  $cacheTags  Cache tags.
     */
    public function purge(array $cacheTags = [self::CACHE_TAG]): void
    {
        // Clear the local cache
        $this->localCache = [];

        // Clean the cache by specified tags
        $this->cacheManager->clean($cacheTags);
    }

    /**
     * Generate cache key for configuration value.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  int|string|null  $storeId  The store ID to retrieve the configuration for. Default is null.
     *
     * @return string The generated cache key.
     */
    public function generateCacheKey(string $path, int|string|null $storeId = null): string
    {
        // Replace null store ID with the default store ID (0)
        $storeId ??= Store::DEFAULT_STORE_ID;

        // Replace forward slashes with underscores in the path
        $path = Str::replace(SP, '_', $path);

        // Construct the cache key
        $cacheKey = self::TYPE_IDENTIFIER . '_' . $path . '_' . $storeId;

        return $cacheKey;
    }
}
