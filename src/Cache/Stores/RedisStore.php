<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Stores;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\InteractsWithTime;
use Magento\Framework\App\CacheInterface;
use Maginium\Framework\Cache\Interfaces\LockableInterface;
use Maginium\Framework\Cache\Interfaces\StoreInterface;
use Maginium\Framework\Cache\Interfaces\TaggableInterface;
use Maginium\Framework\Cache\RetrievesMultipleKeys;
use Maginium\Framework\Cache\TaggableStore;
use Maginium\Framework\Cache\TaggedCacheFactory;
use Maginium\Framework\Cache\TagSetFactory;
use Maginium\Framework\Redis\Interfaces\ClientInterface;
use Maginium\Framework\Redis\Interfaces\RedisInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Str;

/**
 * RedisStore class implements the LockableInterface interface for managing Redis cache with locking capabilities.
 *
 * This class provides methods for interacting with a Redis-based cache, including retrieving,
 * incrementing cache items, and handling caching operations with support for tagging, prefixing keys,
 * and managing locks.
 */
class RedisStore extends TaggableStore implements LockableInterface, StoreInterface, TaggableInterface
{
    use InteractsWithTime;
    use RetrievesMultipleKeys {
        putMany as private putManyAlias;
    }

    /**
     * A string that should be prepended to keys for cache.
     *
     * @var string
     */
    protected string $prefix;

    /**
     * The name of the Redis connection used for locks.
     *
     * @var string
     */
    protected string $lockConnection;

    /**
     * The Redis connection instance used for managing locks.
     *
     * @var string
     */
    protected string $connection;

    /**
     * Redis client instance for cache operations.
     *
     * @var RedisInterface
     */
    protected RedisInterface $redis;

    /**
     * Cache client instance for handling cache operations.
     *
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * Create a new Redis store.
     *
     * This constructor initializes the RedisStore instance by injecting the required
     * Redis client, cache interface, and an optional prefix for cache keys.
     *
     * @param RedisInterface $redis The Redis client instance for cache operations.
     * @param CacheInterface $cache The cache client instance used to handle cache operations.
     * @param TaggedCacheFactory $taggedCacheFactory  The factory responsible for creating tagged cache instances.
     * @param TagSetFactory $tagSetFactory  The factory that creates tag sets for grouping cache items.
     * @param string $prefix The prefix to prepend to cache keys (optional).
     * @param string $connection The redis connection name (optional).
     */
    public function __construct(RedisInterface $redis, CacheInterface $cache, TaggedCacheFactory $taggedCacheFactory, TagSetFactory $tagSetFactory, $prefix = '', $connection = 'default')
    {
        parent::__construct($taggedCacheFactory, $tagSetFactory);

        $this->redis = $redis;
        $this->cache = $cache;

        // Set cache prefix
        $this->setPrefix($prefix ?? '');

        // Set cache connection name
        $this->setConnection($connection);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * This method attempts to retrieve the cached value associated with the specified key.
     * If the item is found, it will be unserialized and returned.
     * If not found, it will return null.
     *
     * @param  array|string  $key The key identifying the cached item.
     *
     * @return mixed Returns the cached value if found or null if not found.
     */
    public function get($key): mixed
    {
        // TODO: ALIGN WITH MAGENTO 2 PREFIX ZC::EF1
        // dd((bool) $this->getRedis()->exists("ef1_WEBAPI_CONFIG"));

        // Fetch the value from the Redis connection based on the provided key
        $value = $this->connection()->load($this->itemKey($key));

        // If the value is found, unserialize it before returning
        // If the value is null (not found), return null
        return $value ? $this->unserialize($value) : null;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * This method increments the cached value for a specific key by the specified amount.
     * The value is incremented by default by 1 if no value is provided.
     *
     * @param  string  $key The key for the item in the cache.
     * @param  mixed  $value The value to increment by (default is 1).
     *
     * @return int Returns the incremented value after the operation.
     */
    public function increment($key, $value = 1): int
    {
        // Use Redis 'incrby' command to increment the value associated with the specified key
        // Increment the value by the given amount (or 1 by default if no value is provided)
        return $this->getRedis()->incrby($this->itemKey($key, true), $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * This method reduces the value of a cached item by the given amount.
     * If no value is specified, it will decrement by 1.
     *
     * @param string $key The key for the item in the cache.
     * @param mixed $value The value to decrement by (default is 1).
     *
     * @return int The decremented value.
     */
    public function decrement($key, $value = 1): int
    {
        // Perform the decrement operation using the Redis 'decrby' command
        // This will subtract the value from the current cache value at the specified key
        return $this->getRedis()->decrby($this->itemKey($key, true), $value);
    }

    /**
     * Retrieve multiple items from the cache by their keys.
     *
     * This method fetches multiple items from the cache. If an item is not found,
     * its value will be set to null in the result.
     *
     * @param array $keys An array of cache keys to retrieve.
     *
     * @return array An associative array with keys as the original cache keys and values
     *               as the cached data (or null if not found).
     */
    public function many(array $keys): array
    {
        // If no keys are provided, return an empty array
        if (empty($keys)) {
            return [];
        }

        // Initialize an empty array to store the result
        $results = [];

        // Use the Arr::map function to iterate through the keys and load their values from cache
        // 'load' retrieves the cached value associated with the key
        $values = Arr::map($keys, fn($key) => $this->connection()->load($this->itemKey($key)));

        // Iterate through the retrieved values
        // If the value is not null, unserialize it (assuming it's serialized for storage)
        foreach ($values as $index => $value) {
            // Add the deserialized value to the results array, or null if the value wasn't found
            $results[$keys[$index]] = $value !== null ? $this->unserialize($value) : null;
        }

        // Return the array of results (key-value pairs)
        return $results;
    }

    /**
     * Store an item in the cache.
     *
     * This method stores a single value in the cache associated with the provided key.
     * Optionally, you can set a time-to-live (TTL) duration to expire the cache item.
     *
     * @param array|string $key The key identifying the cached item.
     * @param mixed $value The value to store in the cache.
     * @param DateTimeInterface|DateInterval|int|null $ttl The time-to-live duration for the cached item.
     * @param array $tags array of tags to associate with the cached items.
     *
     * @return bool Returns true on success, false on failure.
     */
    public function put($key, $value, $ttl = null, $tags = []): bool
    {
        // Call the connection's 'save' method to store the serialized value in the cache
        // 'serialize' is assumed to be a method that prepares the value for cache storage
        // The TTL is cast to an integer, and a minimum of 1 is enforced if no TTL is provided
        return $this->connection()->save(
            $this->serialize($value),
            $this->itemKey($key),
            $tags,
            $this->calculateExpiration($ttl), // Ensure the TTL is always at least 1
        );
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * This method allows you to store an array of key-value pairs in the cache.
     * You can specify a TTL to set the expiration duration for the items.
     * If no TTL is provided, the items will be stored indefinitely.
     *
     * @param array $values An associative array of key-value pairs to store in the cache.
     * @param array $tags array of tags to associate with the cached items.
     * @param DateTimeInterface|DateInterval|int|null $ttl The time-to-live for the cache items.
     * @param array $tags array of tags to associate with the cached items.
     *
     * @return bool Returns true if all items are successfully stored, false if any item fails.
     */
    public function putMany(array $values, $ttl = null, $tags = []): bool
    {
        // Get the cache connection instance
        $connection = $this->connection();

        // If the connection is a Redis Cluster, handle multi-value writes differently
        // Cluster connections do not support writing multiple values if the keys hash differently
        if ($connection instanceof PredisClusterConnection) {
            return $this->putManyAlias($values, $ttl);
        }

        // Initialize an array to store the serialized values
        $serializedValues = [];

        // Loop through the given values and serialize each one for cache storage
        foreach ($values as $key => $value) {
            $serializedValues[$key] = $this->serialize($value);
        }

        // Initialize a variable to track the success of the operation
        $manyResult = null;

        // Loop through each serialized value and attempt to store it in the cache
        foreach ($serializedValues as $key => $value) {
            // Attempt to store each serialized value in the cache
            // The result of each operation is stored in the $manyResult variable
            $result = $connection->save(
                $value,
                $this->itemKey($key),
                $tags,
                $this->calculateExpiration($ttl), // Enforce a minimum TTL of 1 second
            );

            // If the result is null, it means this is the first iteration, so set $manyResult to $result
            // Otherwise, combine the results using a logical AND operation to ensure all items succeeded
            $manyResult = $manyResult === null ? $result : $result && $manyResult;
        }

        // Return true if all items were stored successfully, or false if any failed
        return $manyResult ?: false;
    }

    /**
     * Store an item in the cache if the key doesn't already exist.
     *
     * This method will store the given value in the cache under the specified key,
     * but only if the key doesn't already exist. It uses a Lua script to check for
     * the existence of the key and sets the value with an expiration time (TTL) if the key
     * is absent. This helps to ensure that the value is only set once.
     *
     * @param string $key The cache key under which the value should be stored.
     * @param mixed $value The value to be stored in the cache.
     * @param int $ttl The time-to-live (TTL) for the cache item in seconds.
     *
     * @return bool Returns true if the item was successfully added, false if the key already exists.
     */
    public function add($key, $value, $ttl): bool
    {
        // Lua script to check if the key exists, and if not, set the value with TTL
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])";

        // Execute the Lua script to add the value to cache
        return (bool)$this->getRedis()->eval(
            $lua,
            1,
            $this->itemKey($key, true),
            $this->serialize($value), // Serialize the value before storing
            $this->calculateExpiration($ttl),         // Ensure TTL is at least 1 second
        );
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * This method will store the given value in the cache under the specified key
     * without setting an expiration time, meaning it will persist until it is manually
     * removed from the cache. Optionally, tags can be associated with the cached item
     * for more granular cache invalidation.
     *
     * @param string $key The cache key under which the value should be stored.
     * @param mixed $value The value to be stored in the cache.
     * @param array $tags array of tags to associate with the cached items.
     *
     * @return bool Returns true if the item was successfully stored, false otherwise.
     */
    public function forever($key, $value, $tags = []): bool
    {
        // Use the connection's save method to store the value permanently in the cache
        return $this->connection()->save($this->serialize($value), $this->itemKey($key), $tags);
    }

    /**
     * Get a lock instance.
     *
     * This method provides a way to acquire a lock on a given resource. The lock can
     * help prevent race conditions or concurrent access to the resource. The lock
     * can be released manually or automatically after a specified time-to-live (TTL).
     *
     * @param string $name The name of the lock to be acquired.
     * @param int $ttl The time-to-live (TTL) for the lock in seconds.
     * @param string|null $owner The identifier of the owner of the lock.
     *
     * @return Lock An instance of the Lock object that can be used to manage the lock.
     */
    public function lock($name, $ttl = 0, $owner = null): Lock
    {
        // Attempt to acquire a lock with the given name, TTL, and optional owner identifier
        return $this->redis->lock($name, $ttl, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * This method is used to restore an existing lock instance based on the owner identifier,
     * allowing the owner to re-acquire or continue holding the lock.
     *
     * @param string $name The name of the lock to be restored.
     * @param string $owner The owner identifier associated with the lock.
     *
     * @return Lock An instance of the Lock object that can be used to manage the restored lock.
     */
    public function restoreLock($name, $owner): Lock
    {
        // Restore the lock by calling the lock method with TTL as 0 for indefinite duration
        return $this->lock($name, 0, $owner);
    }

    /**
     * Remove an item from the cache.
     *
     * This method will remove the specified cache item identified by the key. If
     * the item exists in the cache, it will be removed; otherwise, it will return false.
     *
     * @param string $key The cache key of the item to be removed.
     *
     * @return bool Returns true if the item was removed, false if the item doesn't exist.
     */
    public function forget($key): bool
    {
        // Remove the specified item from the cache using the connection's remove method
        return (bool)$this->connection()->remove($this->itemKey($key));
    }

    /**
     * Remove all items from the cache.
     *
     * This method will flush the entire cache, removing all stored items. It can be useful
     * for clearing the cache when a complete reset is needed. Be cautious as this will
     * remove all cache entries without any option for selective removal.
     *
     * @return bool Returns true if the cache was successfully flushed.
     */
    public function flush(): bool
    {
        // Flush the entire cache using the Redis flushdb command
        $this->getRedis()->flushdb();

        return true;
    }

    /**
     * Get the Redis connection instance used for cache operations.
     *
     * This method provides access to the underlying cache connection, which can
     * be used to execute additional cache operations.
     *
     * @return CacheInterface The instance of the cache connection.
     */
    public function connection(): CacheInterface
    {
        // Return the cache connection object
        return $this->cache;
    }

    /**
     * Get the Redis database instance used by the cache.
     *
     * This method returns the Redis client instance that is directly interacting
     * with the Redis database. It can be used to perform Redis-specific operations.
     *
     * @return ClientInterface The Redis client instance.
     */
    public function getRedis()
    {
        // Return the Redis client instance
        return $this->redis->getClient();
    }

    /**
     * Get the cache key prefix used in cache operations.
     *
     * The cache key prefix is a string that is prepended to all cache keys. This helps
     * to avoid key collisions when the same Redis instance is shared among multiple applications.
     *
     * @return string The prefix used for cache keys.
     */
    public function getPrefix(): string
    {
        // Return the current cache key prefix
        return $this->prefix;
    }

    /**
     * Set a new cache key prefix for cache operations.
     *
     * This method allows setting a custom prefix that will be used for cache keys.
     * It can be useful for isolating caches when multiple applications are using the same cache system.
     *
     * @param string $prefix The new prefix to be used for cache keys.
     *
     * @return void
     */
    public function setPrefix(string $prefix): void
    {
        // Set the new cache key prefix
        $this->prefix = $prefix;
    }

    /**
     * Specify the name of the connection that should be used to manage locks.
     *
     * @param  string  $connection
     *
     * @return $this
     */
    public function setLockConnection($connection)
    {
        $this->lockConnection = $connection;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Specify the name of the connection that should be used to store data.
     *
     * @param  string  $connection
     *
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Format the key for a cache item by applying standard transformations.
     *
     * This method formats the provided cache key by replacing slashes ("/") and backslashes ("\\") with underscores ("_")
     * and converts the key to uppercase. Additionally, a prefix is conditionally prepended to the formatted key based on
     * the provided `withPrefix` flag. These transformations help ensure consistent formatting for cache keys and prevent
     * conflicts with special characters.
     *
     * If `withPrefix` is set to true, the prefix will be included in the final key. If false, the prefix will be omitted.
     * This provides flexibility to handle scenarios where the prefix should be included or excluded.
     *
     * The method can be extended in the future to support more complex formatting rules (e.g., truncating long keys).
     *
     * @param  string  $key  The original key to be formatted for the cache item.
     * @param  bool    $withPrefix  Whether to include the prefix in the formatted key. Defaults to false.
     *
     * @return string Returns the formatted key for the cache item, optionally including the prefix.
     */
    protected function itemKey(string $key, bool $withPrefix = false): string
    {
        // Replace forward slashes (/) with underscores (_), as slashes are often used in URL paths
        $key = Str::replace('/', '_', $key);

        // Replace backslashes (\) with underscores (_), as backslashes are not suitable for cache key formatting
        $key = Str::replace('\\', '_', $key);

        // Convert the key to uppercase to standardize the cache key format (for consistency and easy searching)
        $key = Str::upper($key);

        // Conditionally prepend the configured prefix to the formatted key based on the `withPrefix` flag
        // The prefix helps distinguish cache keys for different stores or types of caches
        if ($withPrefix) {
            return $this->prefix . $key;
        }

        // If `withPrefix` is false, return the formatted key without the prefix
        return $key;
    }

    /**
     * Get the expiration time of the key.
     *
     * @param  int  $ttl
     *
     * @return int
     */
    protected function calculateExpiration($ttl)
    {
        return $this->toTimestamp($ttl);
    }

    /**
     * Get the UNIX timestamp for the given number of ttl.
     *
     * @param  int  $ttl
     *
     * @return int
     */
    protected function toTimestamp($ttl)
    {
        return $ttl > 0 ? $this->availableAt($ttl) : 0;
    }
}
