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
use Maginium\Framework\Cache\Locks\ArrayLock;
use Maginium\Framework\Cache\Locks\ArrayLockFactory;
use Maginium\Framework\Cache\RetrievesMultipleKeys;
use Maginium\Framework\Cache\TaggableStore;
use Maginium\Framework\Cache\TaggedCacheFactory;
use Maginium\Framework\Cache\TagSetFactory;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Carbon;
use Maginium\Framework\Support\Facades\Serializer;

/**
 * Class ArrayStore.
 *
 * An implementation of a cache store that uses an array as the underlying storage.
 * This class supports cache locking and tagging functionality, allowing for
 * efficient management of cache items in-memory.
 *
 * It extends TaggableStore to provide cache tagging features and implements
 * LockableInterface to allow cache locking functionality, using Laravel's cache locking system.
 */
class ArrayStore extends TaggableStore implements LockableInterface, StoreInterface
{
    // Provides time-related methods for time-based cache expiry.
    use InteractsWithTime;
    // Provides functionality for retrieving multiple keys at once.
    use RetrievesMultipleKeys {
        putMany as private putManyAlias;
    }

    /**
     * The array of locks.
     */
    public array $locks = [];

    /**
     * The array of stored values.
     */
    protected array $storage = [];

    /**
     * Indicates if values are serialized within the store.
     */
    protected bool $serializesValues;

    /**
     * The factory responsible for creating array lock instances.
     */
    protected ArrayLockFactory $arrayLockFactory;

    /**
     * Cache client instance for handling cache operations.
     *
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * ArrayStore constructor.
     *
     * This constructor injects the required dependencies for performing tagged cache operations.
     * It enables the class to manage cache operations based on tags and tag sets.
     *
     * @param ArrayLockFactory $arrayLockFactory The factory responsible for creating array lock instances.
     * @param TaggedCacheFactory  $taggedCacheFactory The factory responsible for creating tagged cache instances.
     * @param TagSetFactory $tagSetFactory  The factory that creates tag sets for grouping cache items.
     * @param bool $serializesValues  Whether the values should be serialized before storing.
     */
    public function __construct(ArrayLockFactory $arrayLockFactory, TaggedCacheFactory $taggedCacheFactory, TagSetFactory $tagSetFactory, bool $serializesValues = false)
    {
        parent::__construct($taggedCacheFactory, $tagSetFactory);

        $this->arrayLockFactory = $arrayLockFactory;
        $this->serializesValues = $serializesValues;
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
        // Check if the item exists in the storage array
        if (! isset($this->storage[$key])) {
            return null;
        }

        // Retrieve the item and its expiration timestamp
        $item = $this->storage[$key];
        $expiresAt = $item['expiresAt'] ?? 0;

        // If the item has expired, remove it from the cache
        if ($expiresAt !== 0 && (Carbon::now()->getPreciseTimestamp(3) / 1000) >= $expiresAt) {
            $this->forget($key);

            return null;
        }

        // Return the value, unserializing if needed
        return $this->serializesValues ? unserialize($item['value']) : $item['value'];
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
        // Retrieve the current value and increment it
        if (null !== ($existing = $this->get($key))) {
            return tap(((int)$existing) + $value, function($incremented) use ($key) {
                // Serialize and store the incremented value
                $value = $this->serializesValues ? Serializer::serialize($incremented) : $incremented;
                $this->storage[$key]['value'] = $value;
            });
        }

        // If the item does not exist, store it indefinitely
        $this->forever($key, $value);

        return $value;
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
        // Decrementing is simply the inverse of incrementing
        return $this->increment($key, $value * -1);
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
        // Store the value with an expiration time
        $this->storage[$key] = [
            'value' => $this->serializesValues ? Serializer::serialize($value) : $value,
            'expiresAt' => $this->calculateExpiration($ttl),
            'tags' => $tags,
        ];

        return true;
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * This method will store the given value in the cache under the specified key
     * without setting an expiration time, meaning it will persist until it is manually
     * removed from the cache.
     *
     * @param string $key The cache key under which the value should be stored.
     * @param mixed $value The value to be stored in the cache.
     * @param array $tags array of tags to associate with the cached items.
     *
     * @return bool Returns true if the item was successfully stored, false otherwise.
     */
    public function forever($key, $value, $tags = []): bool
    {
        // Indefinite storage is essentially the same as storing with no expiration
        return $this->put($key, $value, 0, $tags);
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
        // Remove the item from storage if it exists
        if (Arr::keyExists($key, $this->storage)) {
            unset($this->storage[$key]);

            return true;
        }

        return false;
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
        // Clear the entire storage array
        $this->storage = [];

        return true;
    }

    /**
     * Remove all entries associated with specific tags from the cache.
     *
     * This method will remove all cache entries that are associated with the specified
     * tags. This allows for more granular cache invalidation by removing only the entries
     * associated with certain tags, while leaving other cache entries intact.
     *
     * @param array $tags An array of tags whose associated entries should be removed from the cache.
     *
     * @return bool Returns true if the cache was successfully flushed for the given tags, false otherwise.
     */
    public function flushTags(array $tags): bool
    {
        return false;
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
        return '';
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
        return $this->cache;
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
    public function lock($name, $ttl = 0, $owner = null): ArrayLock
    {
        return $this->arrayLockFactory->create(['store' => $this, 'name' => $name, 'seconds' => $ttl, 'owner' => $owner]);
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
     * @return ArrayLock An instance of the Lock object that can be used to manage the restored lock.
     */
    public function restoreLock($name, $owner): ArrayLock
    {
        return $this->lock($name, 0, $owner);
    }

    /**
     * Get the expiration time of the key.
     *
     * @param  int  $seconds  The number of seconds to expire.
     *
     * @return float The expiration timestamp.
     */
    protected function calculateExpiration($seconds): float
    {
        return $this->toTimestamp($seconds);
    }

    /**
     * Get the UNIX timestamp, with milliseconds, for the given number of seconds in the future.
     *
     * @param  int  $seconds  The number of seconds in the future.
     *
     * @return float The future timestamp, with milliseconds.
     */
    protected function toTimestamp($seconds): float|int
    {
        return $seconds > 0 ? (Carbon::now()->getPreciseTimestamp(3) / 1000) + $seconds : 0;
    }
}
