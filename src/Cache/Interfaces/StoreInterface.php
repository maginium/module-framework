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
     * This method attempts to retrieve the cached value associated with the specified key.
     * If the item is found, it will be unserialized and returned.
     * If not found, it will return null.
     *
     * @param  array|string  $key The key identifying the cached item.
     *
     * @return mixed Returns the cached value if found or null if not found.
     */
    public function get($key): mixed;

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
    public function increment($key, $value = 1): int;

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
    public function decrement($key, $value = 1): int;

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
    public function many(array $keys): array;

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
    public function put($key, $value, $ttl = null, $tags = []): bool;

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * This method allows you to store an array of key-value pairs in the cache.
     * You can specify a TTL to set the expiration duration for the items.
     * If no TTL is provided, the items will be stored indefinitely.
     *
     * @param array $values An associative array of key-value pairs to store in the cache.
     * @param DateTimeInterface|DateInterval|int|null $ttl The time-to-live for the cache items.
     * @param array $tags array of tags to associate with the cached items.
     *
     * @return bool Returns true if all items are successfully stored, false if any item fails.
     */
    public function putMany(array $values, $ttl = null, $tags = []): bool;

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
    public function forever($key, $value, $tags = []): bool;

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
    public function forget($key): bool;

    /**
     * Remove all items from the cache.
     *
     * This method will flush the entire cache, removing all stored items. It can be useful
     * for clearing the cache when a complete reset is needed. Be cautious as this will
     * remove all cache entries without any option for selective removal.
     *
     * @return bool Returns true if the cache was successfully flushed.
     */
    public function flush(): bool;

    /**
     * Get the cache key prefix used in cache operations.
     *
     * The cache key prefix is a string that is prepended to all cache keys. This helps
     * to avoid key collisions when the same Redis instance is shared among multiple applications.
     *
     * @return string The prefix used for cache keys.
     */
    public function getPrefix(): string;
}
