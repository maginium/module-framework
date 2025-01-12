<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Interfaces;

use ArrayAccess;
use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;

/**
 * Interface CacheInterface.
 */
interface CacheInterface extends ArrayAccess, Repository
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
    public function get(array|string $key, $default = null): mixed;

    /**
     * {@inheritdoc}
     *
     * This method acts as an alias for the `forget` method to maintain compatibility with the interface.
     *
     * @return bool True if the item was successfully removed; false otherwise.
     */
    public function delete(string $key): bool;

    /**
     * {@inheritdoc}
     *
     * This method clears all items from the cache.
     *
     * @return bool True if the cache was successfully cleared; false otherwise.
     */
    public function clear(): bool;

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key  The key of the item to increment.
     * @param  mixed  $value  The amount to increment by (default is 1).
     *
     * @return int The new value after incrementing, or false on failure.
     */
    public function increment($key, $value = 1): int;

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key  The key of the item to decrement.
     * @param  mixed  $value  The amount to decrement by (default is 1).
     *
     * @return int The new value after decrementing, or false on failure.
     */
    public function decrement($key, $value = 1): int;

    /**
     * Determine if an item exists in the cache.
     *
     * This method checks whether a cache item identified by the given key is present
     * in the cache store.
     *
     * @param  array|string  $key  The key(s) identifying the cached item(s).
     *
     * @return bool Returns true if the item exists in the cache; otherwise, false.
     */
    public function has(string $key): bool;

    /**
     * Determine if an item doesn't exist in the cache.
     *
     * This method checks whether a cache item identified by the given key is absent
     * from the cache store.
     *
     * @param  string  $key  The key identifying the cached item.
     *
     * @return bool Returns true if the item does not exist in the cache; otherwise, false.
     */
    public function missing(string $key): bool;

    /**
     * Retrieve multiple items from the cache by key.
     *
     * This method attempts to retrieve values associated with multiple keys.
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys  An array of keys identifying the cached items.
     *
     * @return array Returns an array of cached values, where missing items are null.
     */
    public function many(array $keys): array;

    /**
     * Retrieve multiple items from the cache by key, allowing for a default value.
     *
     * This method retrieves the values for multiple keys and applies a default value
     * for keys not found in the cache.
     *
     * @param  iterable  $keys  An iterable of keys identifying the cached items.
     * @param  mixed  $default  The default value to return for missing keys.
     *
     * @return iterable Returns an iterable of cached values or default values for missing keys.
     */
    public function getMultiple($keys, $default = null): iterable;

    /**
     * Retrieve an item from the cache and delete it.
     *
     * This method retrieves the value associated with the given key and then removes
     * the item from the cache, effectively pulling it.
     *
     * @param  array|string  $key  The key identifying the cached item.
     * @param  mixed  $default  The default value to return if the cache item is not found.
     *
     * @return mixed Returns the cached value or the default value if not found.
     */
    public function pull($key, $default = null);

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
    public function put($key, $value, $ttl = null): bool;

    /**
     * Store an item in the cache.
     *
     * This method stores a value in the cache associated with the given key.
     * The value can be stored with an optional time-to-live (TTL) duration.
     *
     * @param  string  $key  The key identifying the cached item.
     * @param  mixed  $value  The value to store in the cache.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  The time-to-live duration for the cached item.
     *
     * @return bool Returns true on success, false on failure.
     */
    public function set(string $key, $value, $ttl = null): bool;

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * This method allows you to store an associative array of key-value pairs in the cache.
     * If a time-to-live (TTL) is provided, it will set the duration for which the items
     * remain in the cache. If no TTL is provided, the items will be stored indefinitely.
     *
     * @param  array  $values  An associative array of key-value pairs to store in the cache.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  The time-to-live for the cache items.
     *
     * @return bool Returns true if all items are successfully stored, false if any item fails.
     */
    public function putMany(array $values, $ttl = null);

    /**
     * Store multiple items in the cache.
     *
     * This method accepts a set of values, converts them to an array if necessary,
     * and stores them in the cache with optional time-to-live (TTL).
     *
     * @param  mixed  $values  The values to be stored, which can be an array or an iterable.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  Optional time-to-live for the cached items.
     *
     * @return bool Returns true if all items were successfully stored, false otherwise.
     */
    public function setMultiple($values, $ttl = null): bool;

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string  $key  The key to store the value under.
     * @param  mixed  $value  The value to store in the cache.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  The time-to-live for the cache item.
     *
     * @return bool Returns true if the item was added, false if it already exists.
     */
    public function add($key, $value, $ttl = null);

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key  The key to store the value under.
     * @param  mixed  $value  The value to store in the cache.
     *
     * @return bool Returns true on successful storage, false otherwise.
     */
    public function forever($key, $value): bool;

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * This method first attempts to retrieve the cached value associated with the given key.
     * If the value is not found, it executes the provided callback function, caches the result,
     * and then returns it. The caching duration is determined by the provided TTL (Time To Live).
     *
     * @template TCacheValue
     *
     * @param  string  $key  The key of the cached item.
     * @param  Closure|DateTimeInterface|DateInterval|int|null  $ttl  The expiration time for the cache item.
     * @param  Closure(): TCacheValue  $callback  The callback to execute if the item is not found in the cache.
     *
     * @return TCacheValue The cached value.
     */
    public function remember($key, $ttl, Closure $callback): mixed;

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * This method is similar to `remember`, but it caches the result indefinitely.
     * If the item is not found in the cache, the callback function is executed,
     * and its result is stored permanently.
     *
     * @template TCacheValue
     *
     * @param  string  $key  The key of the cached item.
     * @param  Closure(): TCacheValue  $callback  The callback to execute if the item is not found in the cache.
     *
     * @return TCacheValue The cached value.
     */
    public function sear($key, Closure $callback);

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * This method attempts to retrieve the cached value associated with the specified key.
     * If it doesn't exist, the method executes the provided callback, stores the result indefinitely,
     * and returns it.
     *
     * @template TCacheValue
     *
     * @param  string  $key  The key of the cached item.
     * @param  Closure(): TCacheValue  $callback  The callback to execute if the item is not found in the cache.
     *
     * @return TCacheValue The cached value.
     */
    public function rememberForever($key, Closure $callback);

    /**
     * Retrieve an item from the cache by key, refreshing it in the background if it is stale.
     *
     * This method fetches the cached item and checks its freshness based on the provided TTL (Time To Live) values.
     * If the item is stale, it schedules a background refresh using the provided callback.
     *
     * @template TCacheValue
     *
     * @param  string  $key  The key of the cached item.
     * @param  array{0: DateTimeInterface|DateInterval|int, 1: DateTimeInterface|DateInterval|int}  $ttl  TTLs for freshness and staleness.
     * @param  callable(): TCacheValue  $callback  Callback to execute for cache refresh if stale.
     * @param  array{seconds?: int, owner?: string}|null  $lock  Locking parameters to avoid race conditions.
     *
     * @return TCacheValue The cached value.
     */
    public function flexible(string $key, array $ttl, callable $callback, ?array $lock = null): mixed;

    /**
     * Remove an item from the cache.
     *
     * This method attempts to delete the cached item associated with the specified key.
     * It also triggers events to notify about the deletion process.
     *
     * @param  string  $key  The key of the cached item to be removed.
     *
     * @return bool True if the item was successfully removed; false otherwise.
     */
    public function forget($key);

    /**
     * Remove multiple items from the cache based on the provided keys.
     *
     * This method attempts to delete the specified keys from the cache store.
     *
     * @param  array|string  $keys  An array of keys to be removed from the cache.
     *
     * @return bool True if all items were successfully removed; false otherwise.
     */
    public function deleteMultiple($keyOrKeys): bool;

    /**
     * Get the default cache time.
     *
     * @return int|null The default cache time in seconds or null if not set.
     */
    public function getDefaultCacheTime();

    /**
     * Set the default cache time in seconds.
     *
     * @param  int|null  $seconds  The number of seconds to set as default cache time.
     *
     * @return $this The current instance for method chaining.
     */
    public function setDefaultCacheTime($seconds);

    /**
     * Determine if a cached value exists.
     *
     * @param  string  $key  The key to check in the cache.
     *
     * @return bool True if the cached value exists; false otherwise.
     */
    public function offsetExists($key): bool;

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     */
    public function offsetGet($key): mixed;
}
