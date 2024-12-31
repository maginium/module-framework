<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\InteractsWithTime;
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
 * LockProvider to allow cache locking functionality, using Laravel's cache locking system.
 */
class ArrayStore extends TaggableStore implements LockProvider
{
    // Provides time-related methods for time-based cache expiry.
    use InteractsWithTime;
    // Provides functionality for retrieving multiple keys at once.
    use RetrievesMultipleKeys;

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
     * Create a new Array store.
     *
     * @param  bool  $serializesValues  Whether the values should be serialized before storing.
     *
     * @return void
     */
    public function __construct($serializesValues = false)
    {
        $this->serializesValues = $serializesValues;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * This method attempts to retrieve the value associated with the provided key.
     * If the item is not found, a default value (if provided) is returned.
     *
     * @param  array|string  $key  The key identifying the cached item.
     * @param  mixed  $default  The default value to return if the cache item is not found.
     *
     * @return mixed Returns the cached value or the default value if not found.
     */
    public function get($key, $default = null): mixed
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
     * @param  string  $key  The key for the item in the cache.
     * @param  mixed  $value  The value to increment by (default is 1).
     *
     * @return int The incremented value.
     */
    public function increment($key, $value = 1): mixed
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
     * @param  string  $key  The key for the item in the cache.
     * @param  mixed  $value  The value to decrement by (default is 1).
     *
     * @return int The decremented value.
     */
    public function decrement($key, $value = 1)
    {
        // Decrementing is simply the inverse of incrementing
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache.
     *
     * This method stores a value in the cache associated with the given key.
     * The value can be stored with an optional time-to-live (TTL) duration.
     *
     * @param  array|string  $key  The key identifying the cached item.
     * @param  mixed  $value  The value to store in the cache.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  The time-to-live duration for the cached item.
     *
     * @return bool Returns true on success, false on failure.
     */
    public function put($key, $value, $tags = [], $ttl = null): bool
    {
        // Store the value with an expiration time
        $this->storage[$key] = [
            'value' => $this->serializesValues ? Serializer::serialize($value) : $value,
            'expiresAt' => $this->calculateExpiration($ttl),
        ];

        return true;
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key  The key to store the value under.
     * @param  mixed  $value  The value to store in the cache.
     * @param  array  $tags  An array of tags to associate with the cached item.
     *
     * @return bool Returns true on successful storage, false otherwise.
     */
    public function forever($key, $value, array $tags = []): bool
    {
        // Indefinite storage is essentially the same as storing with no expiration
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key  The key for the item to be removed.
     *
     * @return bool Returns true if the item was removed, false otherwise.
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
     * @return bool Returns true if all items were successfully removed.
     */
    public function flush(): bool
    {
        // Clear the entire storage array
        $this->storage = [];

        return true;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string The prefix used for cache keys.
     */
    public function getPrefix(): string
    {
        return '';
    }

    /**
     * Get a lock instance.
     *
     * @param  string  $name  The name of the lock.
     * @param  int  $seconds  The number of seconds the lock should be held.
     * @param  string|null  $owner  The owner of the lock (optional).
     *
     * @return Lock A lock instance.
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        return new ArrayLock($this, $name, $seconds, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name  The name of the lock.
     * @param  string  $owner  The owner of the lock.
     *
     * @return Lock A lock instance.
     */
    public function restoreLock($name, $owner)
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
    protected function calculateExpiration($seconds)
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
    protected function toTimestamp($seconds)
    {
        return $seconds > 0 ? (Carbon::now()->getPreciseTimestamp(3) / 1000) + $seconds : 0;
    }
}
