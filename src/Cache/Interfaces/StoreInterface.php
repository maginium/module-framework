<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Interfaces;

use DateInterval;
use DateTimeInterface;

/**
 * Interface Store.
 *
 * This interface defines the contract for a cache store implementation.
 * It provides methods to interact with the cache, including retrieving and storing items,
 * along with support for cache item expiration and other cache management features.
 * Implementing classes must provide the specific details of how cache operations are performed.
 */
interface StoreInterface
{
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
    public function get($key, $default = null): mixed;

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key  The key for the item in the cache.
     * @param  mixed  $value  The value to increment by (default is 1).
     *
     * @return int The incremented value.
     */
    public function increment($key, $value = 1): mixed;

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key  The key for the item in the cache.
     * @param  mixed  $value  The value to decrement by (default is 1).
     *
     * @return int The decremented value.
     */
    public function decrement($key, $value = 1);

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     *
     * @return array
     */
    public function many(array $keys);

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * This method allows you to store an associative array of key-value pairs in the cache.
     * If a time-to-live (TTL) is provided, it will set the duration for which the items
     * remain in the cache. If no TTL is provided, the items will be stored indefinitely.
     *
     * @param  array  $values  An associative array of key-value pairs to store in the cache.
     * @param  array  $tags  An array of tags to associate with the cached items.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  The time-to-live for the cache items.
     *
     * @return bool Returns true if all items are successfully stored, false if any item fails.
     */
    public function putMany(array $values, array $tags = [], $ttl = null): bool;

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
    public function put($key, $value, $tags = [], $ttl = null): bool;

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key  The key to store the value under.
     * @param  mixed  $value  The value to store in the cache.
     * @param  array  $tags  An array of tags to associate with the cached item.
     *
     * @return bool Returns true on successful storage, false otherwise.
     */
    public function forever($key, $value, array $tags = []): bool;

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key  The key for the item to be removed.
     *
     * @return bool Returns true if the item was removed, false otherwise.
     */
    public function forget($key): bool;

    /**
     * Remove all items from the cache.
     *
     * @return bool Returns true if all items were successfully removed.
     */
    public function flush(): bool;

    /**
     * Get the cache key prefix.
     *
     * @return string The prefix used for cache keys.
     */
    public function getPrefix(): string;
}
