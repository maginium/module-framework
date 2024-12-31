<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Traits\Macroable;
use Magento\Framework\App\CacheInterface as BaseCacheInterface;
use Maginium\Foundation\Enums\CacheTTL;
use Maginium\Framework\Cache\Events\CacheHitFactory;
use Maginium\Framework\Cache\Events\CacheMissedFactory;
use Maginium\Framework\Cache\Events\ForgettingKeyFactory;
use Maginium\Framework\Cache\Events\KeyForgetFailedFactory;
use Maginium\Framework\Cache\Events\KeyForgottenFactory;
use Maginium\Framework\Cache\Events\KeyWriteFailedFactory;
use Maginium\Framework\Cache\Events\KeyWrittenFactory;
use Maginium\Framework\Cache\Events\RetrievingKeyFactory;
use Maginium\Framework\Cache\Events\RetrievingManyKeysFactory;
use Maginium\Framework\Cache\Events\WritingKeyFactory;
use Maginium\Framework\Cache\Events\WritingManyKeysFactory;
use Maginium\Framework\Cache\Interfaces\CacheInterface;
use Maginium\Framework\Defer\Interfaces\DeferInterface;
use Maginium\Framework\Event\Interfaces\EventInterface;
use Maginium\Framework\Redis\Interfaces\RedisInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use Predis\ClientInterface as RedisClientInterface;

/**
 * Class CacheManager.
 *
 * This class manages caching operations, providing a flexible interface for
 * interacting with different cache stores such as Redis. It supports time-based
 * cache operations, event dispatching, and the ability to extend functionality
 * through custom macros.
 *
 * @property RedisClientInterface $store The cache store implementation (e.g., Redis).
 * @property EventInterface $events The event dispatcher implementation for cache events.
 * @property BaseCacheInterface $cache The base cache manager for handling cache operations.
 * @property int|null $default The default time-to-live (TTL) in seconds for cached items.
 * @property array $config Configuration options for the cache store.
 */
class CacheManager implements CacheInterface
{
    // Trait for time-related operations such as converting seconds to minutes or checking expiration.
    use InteractsWithTime;
    // Macroable trait allows dynamic methods via macros.
    use Macroable {
        __call as macroCall; // Rename __call to avoid conflict with other methods.
    }

    /**
     * Default time-to-live (TTL) for cache entries.
     *
     * Represents the default duration (in seconds) for which a cache item remains valid.
     * The default value is set to one hour (CacheTTL::HOUR).
     */
    protected ?int $default = CacheTTL::HOUR;

    /**
     * Configuration options for the cache manager.
     *
     * Holds optional settings for customizing the behavior of the cache manager.
     */
    protected array $config;

    /**
     * @var DeferInterface An instance of the Defer service for deferring task execution.
     */
    protected DeferInterface $defer;

    /**
     * Factory for creating cache hit events.
     */
    protected CacheHitFactory $cacheHitFactory;

    /**
     * Factory for creating events related to writing cache keys.
     */
    protected WritingKeyFactory $writingKeyFactory;

    /**
     * Factory for creating events when a key has been written to the cache.
     */
    protected KeyWrittenFactory $keyWrittenFactory;

    /**
     * Factory for creating events when a cache miss occurs.
     */
    protected CacheMissedFactory $cacheMissedFactory;

    /**
     * Factory for creating events when a key is forgotten from the cache.
     */
    protected KeyForgottenFactory $keyForgottenFactory;

    /**
     * Factory for creating events related to forgetting cache keys.
     */
    protected ForgettingKeyFactory $forgettingKeyFactory;

    /**
     * Factory for creating events related to retrieving keys from the cache.
     */
    protected RetrievingKeyFactory $retrievingKeyFactory;

    /**
     * Factory for creating events when writing a key to the cache fails.
     */
    protected KeyWriteFailedFactory $keyWriteFailedFactory;

    /**
     * Factory for creating events when forgetting a key from the cache fails.
     */
    protected KeyForgetFailedFactory $keyForgetFailedFactory;

    /**
     * Factory for creating events related to writing multiple keys to the cache.
     */
    protected WritingManyKeysFactory $writingManyKeysFactory;

    /**
     * Factory for creating events related to retrieving multiple keys from the cache.
     */
    protected RetrievingManyKeysFactory $retrievingManyKeysFactory;

    /**
     * Redis client instance for cache operations.
     *
     * This is the primary storage mechanism used for caching data.
     *
     * @var RedisInterface
     */
    protected RedisClientInterface $store;

    /**
     * Event dispatcher for emitting cache-related events.
     *
     * Events are triggered for various cache operations like hits, misses, and key updates.
     */
    protected EventInterface $events;

    /**
     * Cache manager for executing caching logic.
     *
     * Provides methods for interacting with and managing cached data.
     */
    protected BaseCacheInterface $cache;

    /**
     * Constructor.
     *
     * Initializes the cache manager with all required dependencies, including the Redis client,
     * an event dispatcher, and multiple factories for handling specific events.
     *
     * @param  DeferInterface  $defer  Defer manager implementation.
     * @param  BaseCacheInterface  $cache  Cache manager implementation.
     * @param  RedisInterface  $store  Redis client for caching operations.
     * @param  EventInterface  $events  Event dispatcher for cache events.
     * @param  CacheHitFactory  $cacheHitFactory  Factory for cache hit events.
     * @param  WritingKeyFactory  $writingKeyFactory  Factory for writing key events.
     * @param  KeyWrittenFactory  $keyWrittenFactory  Factory for key written events.
     * @param  CacheMissedFactory  $cacheMissedFactory  Factory for cache miss events.
     * @param  KeyForgottenFactory  $keyForgottenFactory  Factory for key forgotten events.
     * @param  ForgettingKeyFactory  $forgettingKeyFactory  Factory for forgetting key events.
     * @param  RetrievingKeyFactory  $retrievingKeyFactory  Factory for retrieving key events.
     * @param  KeyWriteFailedFactory  $keyWriteFailedFactory  Factory for key write failure events.
     * @param  KeyForgetFailedFactory  $keyForgetFailedFactory  Factory for key forget failure events.
     * @param  WritingManyKeysFactory  $writingManyKeysFactory  Factory for writing multiple keys.
     * @param  RetrievingManyKeysFactory  $retrievingManyKeysFactory  Factory for retrieving multiple keys.
     * @param  array  $config  Optional cache configuration settings.
     */
    public function __construct(
        DeferInterface $defer,
        RedisInterface $store,
        EventInterface $events,
        BaseCacheInterface $cache,
        CacheHitFactory $cacheHitFactory,
        WritingKeyFactory $writingKeyFactory,
        KeyWrittenFactory $keyWrittenFactory,
        CacheMissedFactory $cacheMissedFactory,
        KeyForgottenFactory $keyForgottenFactory,
        ForgettingKeyFactory $forgettingKeyFactory,
        RetrievingKeyFactory $retrievingKeyFactory,
        KeyWriteFailedFactory $keyWriteFailedFactory,
        KeyForgetFailedFactory $keyForgetFailedFactory,
        WritingManyKeysFactory $writingManyKeysFactory,
        RetrievingManyKeysFactory $retrievingManyKeysFactory,
        array $config = [],
    ) {
        $this->cache = $cache;
        $this->defer = $defer;
        $this->config = $config;
        $this->events = $events;
        $this->cacheHitFactory = $cacheHitFactory;
        $this->writingKeyFactory = $writingKeyFactory;
        $this->keyWrittenFactory = $keyWrittenFactory;
        $this->cacheMissedFactory = $cacheMissedFactory;
        $this->keyForgottenFactory = $keyForgottenFactory;
        $this->forgettingKeyFactory = $forgettingKeyFactory;
        $this->retrievingKeyFactory = $retrievingKeyFactory;
        $this->keyWriteFailedFactory = $keyWriteFailedFactory;
        $this->keyForgetFailedFactory = $keyForgetFailedFactory;
        $this->writingManyKeysFactory = $writingManyKeysFactory;
        $this->retrievingManyKeysFactory = $retrievingManyKeysFactory;

        // Initialize the Redis client from the provided store instance.
        $this->store = $store->getClient();
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * This method attempts to retrieve the value associated with the provided key.
     * If the item is not found, it returns null instead of false or the provided default.
     *
     * @param  array|string  $key  The key identifying the cached item.
     * @param  mixed  $default  The default value to return if the cache item is not found.
     *
     * @return mixed Returns the cached value or the default value if not found.
     */
    public function get($key, $default = null): mixed
    {
        // If the key is an array, delegate the retrieval to the many method.
        if (Validator::isArray($key)) {
            return $this->many($key);
        }

        // Trigger an event indicating that a cache retrieval is in progress.
        $this->event(
            $this->retrievingKeyFactory->create([
                'storeName' => $this->getName(),
                'key' => $key,
            ]),
        );

        // Retrieve the value from the cache store using the item key.
        $value = $this->cache->load($this->itemKey($key));

        // If the value is null or false, consider it a cache miss and return the default (or null).
        if ($value === null || $value === false) {
            $this->event(
                $this->cacheMissedFactory->create([
                    'storeName' => $this->getName(),
                    'key' => $key,
                ]),
            );

            // Resolve the default value if it's a callable.
            return value($default);
        }

        // If the value was found, trigger a cache hit event.
        $this->event(
            $this->cacheHitFactory->create([
                'storeName' => $this->getName(),
                'key' => $key,
                'value' => $value,
            ]),
        );

        // Return the retrieved value.
        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * This method acts as an alias for the `forget` method to maintain compatibility with the interface.
     *
     * @return bool True if the item was successfully removed; false otherwise.
     */
    public function delete($key): bool
    {
        // Delegate the delete operation to the forget method
        return $this->forget($key);
    }

    /**
     * {@inheritdoc}
     *
     * This method clears all items from the cache.
     *
     * @param  array  $tags
     *
     * @return bool True if the cache was successfully cleared; false otherwise.
     */
    public function clear($tags = []): bool
    {
        // Flush all tags from the cache store
        $this->cache->clean($tags);

        // Flush all items from the cache store
        return $this->store->flushall();
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key  The key of the item to increment.
     * @param  mixed  $value  The amount to increment by (default is 1).
     *
     * @return int|bool The new value after incrementing, or false on failure.
     */
    public function increment($key, $value = 1)
    {
        // Delegate the increment operation to the store and return the result.
        return $this->store->increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key  The key of the item to decrement.
     * @param  mixed  $value  The amount to decrement by (default is 1).
     *
     * @return int|bool The new value after decrementing, or false on failure.
     */
    public function decrement($key, $value = 1)
    {
        // Delegate the decrement operation to the store and return the result.
        return $this->store->decrement($key, $value);
    }

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
    public function has($key): bool
    {
        // Returns true if the item is not null, indicating existence.
        return $this->get($key) !== null;
    }

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
    public function missing($key): bool
    {
        // Returns true if the item does not exist by calling the has method.
        return ! $this->has($key);
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * This method attempts to retrieve values associated with multiple keys.
     * Items not found in the cache or returned as false will have a null value.
     *
     * @param  array  $keys  An array of keys identifying the cached items.
     *
     * @return array Returns an array of cached values, where missing or false items are null.
     */
    public function many(array $keys): array
    {
        // Trigger an event indicating that multiple cache keys are being retrieved.
        $this->event(
            $this->retrievingManyKeysFactory->create([
                'storeName' => $this->getName(),
                'keys' => $keys,
            ]),
        );

        // Initialize an array to hold the results.
        $values = [];

        // Iterate over each key and load its corresponding value from the cache.
        foreach ($keys as $key) {
            // Load the value for the current key.
            $value = $this->cache->load($key);

            // If the value is false, set it to null to indicate a cache miss.
            $values[$key] = ($value === false) ? null : $value;
        }

        // Handle the results of the many retrieval and return the processed array.
        return collect($values)->map(fn($value, $key) => $this->handleManyResult($keys, $key, $value))->all();
    }

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
    public function getMultiple($keys, $default = null): iterable
    {
        // Create an array to hold the defaults for each key.
        $defaults = [];

        // Populate the defaults array for each key.
        foreach ($keys as $key) {
            // Assign the default value for each key.
            $defaults[$key] = $default;
        }

        // Retrieve multiple items using the many method.
        return $this->many($defaults);
    }

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
    public function pull($key, $default = null)
    {
        // Retrieve the item and delete it from the cache using tap.
        return tap($this->get($key, $default), function() use ($key): void {
            // Remove the item from the cache after retrieval.
            $this->forget($key);
        });
    }

    /**
     * Store an item in the cache.
     *
     * This method stores a value in the cache associated with the given key.
     * The value can be stored with an optional time-to-live (TTL) duration.
     *
     * @param  array|string  $key  The key identifying the cached item.
     * @param  mixed  $value  The value to store in the cache.
     * @param  array  $tags  The tags to associate with the cached item.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  The time-to-live duration for the cached item.
     *
     * @return bool Returns true on success, false on failure.
     */
    public function put($key, $value, $tags = [], $ttl = null): bool
    {
        // If the key is an array, delegate the storage to the putMany method.
        if (Validator::isArray($key)) {
            return $this->putMany($key, $value);
        }

        // If no TTL is specified, store the item indefinitely.
        if ($ttl === null) {
            return $this->forever($key, $value);
        }

        // Convert the TTL to seconds.
        $seconds = $this->getSeconds($ttl);

        // If the TTL is non-positive, forget the key.
        if ($seconds <= 0) {
            return $this->forget($key);
        }

        // Trigger an event indicating that a key is being written to the cache.
        $this->event(
            $this->writingKeyFactory->create([
                'storeName' => $this->getName(),
                'key' => $key,
                'value' => $value,
                'seconds' => $seconds,
            ]),
        );

        // Store the item in the cache and capture the result.
        $result = $this->cache->save($value, $this->itemKey($key), $tags, $seconds);

        // Trigger appropriate events based on the result of the storage operation.
        if ($result) {
            // Success event.
            $this->event(
                $this->keyWrittenFactory->create([
                    'storeName' => $this->getName(),
                    'key' => $key,
                    'value' => $value,
                    'seconds' => $seconds,
                ]),
            );
        } else {
            // Failure event.
            $this->event(
                $this->keyWriteFailedFactory->create([
                    'storeName' => $this->getName(),
                    'key' => $key,
                    'value' => $value,
                    'seconds' => $seconds,
                ]),
            );
        }

        // Return the result of the storage operation.
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $tags = [], $ttl = null): bool
    {
        // Calls the 'put' method to store the item in the cache with an optional time-to-live (TTL).
        return $this->put($key, $value, $tags, $ttl);
    }

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
    public function putMany(array $values, array $tags = [], $ttl = null): bool
    {
        // Handle indefinite storage if no TTL is specified.
        if ($ttl === null) {
            return $this->putManyForever($values, $tags);
        }

        // Convert the TTL into seconds for the storage.
        $seconds = $this->getSeconds($ttl);

        // If seconds is less than or equal to zero, delete the items instead of storing.
        if ($seconds <= 0) {
            return $this->deleteMultiple(Arr::keys($values));
        }

        // Log the operation of writing multiple keys to the cache.
        $this->event(
            $this->writingManyKeysFactory->create([
                'storeName' => $this->getName(),
                'keys' => Arr::keys($values),
                'values' => Arr::values($values),
                'seconds' => $seconds,
            ]),
        );

        // Initialize a variable to track the overall success of the operation.
        $allStored = true;

        // Loop through each key-value pair and store it in the cache.
        foreach ($values as $key => $value) {
            $result = $this->cache->save(Json::encode(data: $value), $key, $tags, $seconds);

            // Check if the storage was successful and trigger appropriate events.
            if ($result) {
                $this->event(
                    $this->keyWrittenFactory->create([
                        'storeName' => $this->getName(),
                        'key' => $key,
                        'value' => $value,
                        'seconds' => $seconds,
                    ]),
                );
            } else {
                $this->event(
                    $this->keyWriteFailedFactory->create([
                        'storeName' => $this->getName(),
                        'key' => $key,
                        'value' => $value,
                        'seconds' => $seconds,
                    ]),
                );

                // Mark as failed if any item fails to store.
                $allStored = false;
            }
        }

        // Return true only if all items were stored successfully.
        return $allStored;
    }

    /**
     * Store multiple items in the cache.
     *
     * This method accepts a set of values, converts them to an array if necessary,
     * and stores them in the cache with optional tags and a time-to-live (TTL).
     *
     * @param  mixed  $values  The values to be stored, which can be an array or an iterable.
     * @param  array  $tags  An optional array of tags to associate with the cached items.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  Optional time-to-live for the cached items.
     *
     * @return bool Returns true if all items were successfully stored, false otherwise.
     */
    public function setMultiple($values, array $tags = [], $ttl = null): bool
    {
        // Converts the input values to an array if they are not already an array.
        return $this->putMany(Validator::isArray($values) ? $values : iterator_to_array($values), $tags, $ttl);
    }

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string  $key  The key to store the value under.
     * @param  mixed  $value  The value to store in the cache.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  The time-to-live for the cache item.
     *
     * @return bool Returns true if the item was added, false if it already exists.
     */
    public function add($key, $value, $ttl = null)
    {
        // Initialize seconds to null for TTL.
        $seconds = null;

        // If a TTL is provided, convert it to seconds.
        if ($ttl !== null) {
            $seconds = $this->getSeconds($ttl);

            // If the seconds value is less than or equal to zero, the item cannot be added.
            if ($seconds <= 0) {
                return false;
            }

            // If the store has an "add" method, invoke it for potential driver-specific behavior.
            if (Reflection::methodExists($this->store, 'add')) {
                return $this->store->add(
                    $this->itemKey($key), // Generate the cache key.
                    $value,               // The value to store.
                    $seconds,              // The TTL in seconds.
                );
            }
        }

        // If the value does not exist in the cache, store it and return true.
        if ($this->get($key) === null) {
            return $this->put($key, $value, $seconds);
        }

        // If the item already exists, return false to indicate it was not added.
        return false;
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
        // Check if the value is an array and serialize it to JSON
        if (Validator::isArray($value)) {
            $value = Json::encode($value);
        }

        // Trigger an event to log that a key is being written to the cache.
        $this->event(
            $this->writingKeyFactory->create([
                'storeName' => $this->getName(),
                'key' => $key,
                'value' => $value,
            ]),
        );

        // Store the item in the cache indefinitely using the store's save method with tags.
        $result = $this->cache->save($value, $this->itemKey($key), $tags);

        // Trigger the success or failure event based on the result of the storage operation.
        if ($result) {
            // Event indicating the key was successfully written to the cache.
            $this->event(
                $this->keyWrittenFactory->create([
                    'storeName' => $this->getName(),
                    'key' => $key,
                    'value' => $value,
                ]),
            );
        } else {
            // Event indicating the key write failed.
            $this->event(
                $this->keyWriteFailedFactory->create([
                    'storeName' => $this->getName(),
                    'key' => $key,
                    'value' => $value,
                ]),
            );
        }

        // Return the result of the forever operation.
        return $result;
    }

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
    public function remember($key, $ttl, Closure $callback)
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of seconds so it's available for all subsequent requests.
        if ($value !== null) {
            return $value;
        }

        $value = $callback();

        $this->put($key, $value, value($ttl, $value));

        return $value;
    }

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
    public function sear($key, Closure $callback)
    {
        return $this->rememberForever($key, $callback);
    }

    /**
     * Retrieve an item from the cache or generate it if not found.
     *
     * This method tries to get the cached value by the given key.
     * If it doesn't exist, it executes the provided callback, stores
     * the result indefinitely, and returns it for future use.
     *
     * @template TCacheValue
     *
     * @param  string  $key  The key of the cached item.
     * @param  Closure(): TCacheValue  $callback  The function to run if the item is not cached.
     *
     * @return TCacheValue The cached value or the newly generated value.
     */
    public function rememberForever($key, Closure $callback)
    {
        // Try to get the value from the cache using the key.
        $value = $this->get($key);

        // If the value exists in the cache, return it.
        if ($value !== null) {
            return $value;
        }

        // If not found, execute the callback to create the value.
        // Store the result in the cache forever.
        $this->forever($key, $value = $callback());

        // Return the newly generated value.
        return $value;
    }

    /**
     * Retrieve an item from the cache by key, refreshing it in the background if it is stale.
     *
     * This method fetches the cached item and checks its freshness based on the provided TTL (Time To Live) values.
     * If the item is stale, it schedules a background refresh using the given callback.
     *
     * @template TCacheValue
     *
     * @param  string  $key  The key of the cached item.
     * @param  array{ 0: DateTimeInterface|DateInterval|int, 1: DateTimeInterface|DateInterval|int }  $ttl  An array containing the TTL values for the cache.
     * @param  (callable(): TCacheValue)  $callback  The callback to execute to refresh the cache if it is stale.
     * @param  array{ seconds?: int, owner?: string }|null  $lock  Optional locking mechanism parameters.
     *
     * @return TCacheValue The cached value.
     */
    public function flexible($key, $ttl, $callback, $lock = null)
    {
        // Step 1: Retrieve both the cached value and its creation timestamp using the provided key.
        // The 'many' method is used to fetch multiple items from the cache at once, improving performance.
        [
            $key => $value, // The cached value associated with the specified key.
            "magento:cache:flexible:created:{$key}" => $created, // The timestamp when the value was cached.
        ] = $this->many([$key, "magento:cache:flexible:created:{$key}"]);

        // Step 2: Check if either the cached value or the creation timestamp is missing (null).
        // If either is null, invoke the callback to generate a new value and store it in the cache.
        if (in_array(null, [$value, $created], true)) {
            // Execute the callback to get the new value.
            $value = value($callback);

            // Store the new value and the current timestamp in the cache.
            // The second TTL value (ttl[1]) is used for this cached entry.
            $this->putMany([
                $key => $value, // Store the newly generated value.
                "magento:cache:flexible:created:{$key}" => Date::now()->getTimestamp(), // Store the current timestamp.
            ], [$key], $ttl[1]);

            return $value;  // Return the newly generated value for immediate use.
        }

        // Step 3: Determine if the cached value is still valid based on the TTL.
        // Calculate the expiry time by adding the TTL to the creation timestamp.
        $expiryTime = $created + $this->getSeconds($ttl[0]);

        // If the expiry time is in the future, the cached value is still valid, so return it.
        if ($expiryTime > Date::now()->getTimestamp()) {
            // Return the valid cached value.
            return $value;
        }

        // Step 4: The cached value is stale. Schedule a background refresh using a closure.
        $refresh = function() use ($key, $ttl, $callback, $lock, $created): void {
            // Acquire a lock for this key to prevent race conditions during the refresh.
            $this->store->lock(
                "magento:cache:flexible:lock:{$key}", // Unique lock name for the key.
                $lock['seconds'] ?? 0, // Duration for which the lock should be held (default is 0).
                $lock['owner'] ?? null, // Optional owner of the lock (default is null).
            )->get(function() use ($key, $callback, $created, $ttl): void {
                // Check if the cached item's creation timestamp is still the same.
                // If it has changed, do not proceed with refreshing the cache.
                if ($created !== $this->get("magento:cache:flexible:created:{$key}")) {
                    // The item was refreshed by another process, exit early.
                    return;
                }

                // Execute the callback to generate the new value and store it in the cache.
                // Update the cache with the new value and the current timestamp.
                $this->putMany([
                    $key => value($callback), // Store the newly generated value.
                    "magento:cache:flexible:created:{$key}" => Date::now()->getTimestamp(), // Update the creation timestamp.

                    // Use the second TTL value for this cached entry.
                ], [$key], $ttl[1]);
            });
        };

        // Step 5: Schedule the refresh operation to be executed in the background using a defer mechanism.
        $this->defer->execute($refresh, "magento:cache:flexible:{$key}");

        // Return the stale cached value while the refresh happens in the background.
        return $value;
    }

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
    public function forget($key)
    {
        // Fire an event to notify that a key is being forgotten
        $this->event(
            $this->forgettingKeyFactory->create([
                'storeName' => $this->getName(),
                'key' => $key,
            ]),
        );

        // Attempt to remove the item from the cache and trigger corresponding events based on the result
        return tap($this->store->del($this->itemKey($key)), function($result) use ($key): void {
            if ($result) {
                // If removal was successful, fire a "forgotten" event
                $this->event(
                    $this->keyForgottenFactory->create([
                        'storeName' => $this->getName(),
                        'key' => $key,
                    ]),
                );
            } else {
                // If removal failed, fire a "forget failed" event
                $this->event(
                    $this->keyForgetFailedFactory->create([
                        'storeName' => $this->getName(),
                        'key' => $key,
                    ]),
                );
            }
        });
    }

    /**
     * Remove multiple items from the cache based on the provided keys.
     *
     * This method attempts to delete the specified keys from the cache store.
     *
     * @param  array|string  $keys  An array of keys to be removed from the cache.
     *
     * @return bool True if all items were successfully removed; false otherwise.
     */
    public function deleteMultiple(array|string $keyOrKeys): bool
    {
        // Attempt to delete the specified keys from the cache store.
        $result = $this->store->del($keyOrKeys);

        // Return the result of the deletion operation.
        return (bool)$result;
    }

    /**
     * Get the default cache time.
     *
     * @return int|null The default cache time in seconds or null if not set.
     */
    public function getDefaultCacheTime()
    {
        // Return the default cache time
        return $this->default;
    }

    /**
     * Set the default cache time in seconds.
     *
     * @param  int|null  $seconds  The number of seconds to set as default cache time.
     *
     * @return $this The current instance for method chaining.
     */
    public function setDefaultCacheTime($seconds)
    {
        // Set the default cache time to the provided value
        $this->default = $seconds;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Determine if a cached value exists.
     *
     * @param  string  $key  The key to check in the cache.
     *
     * @return bool True if the cached value exists; false otherwise.
     */
    public function offsetExists($key): bool
    {
        // Check if the value associated with the key exists in the cache
        return $this->has($key);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     */
    public function offsetGet($key): mixed
    {
        // Retrieve the value associated with the key from the cache
        return $this->get($key);
    }

    /**
     * Store an item in the cache by key.
     *
     * @param  string  $key  The key to store the value under.
     * @param  mixed  $value  The value to be cached.
     */
    public function offsetSet($key, $value): void
    {
        // Store the value associated with the key in the cache
        $this->put($key, $value);
    }

    /**
     * Remove an item from the cache by key.
     *
     * @param  string  $key  The key of the item to be removed.
     */
    public function offsetUnset($key): void
    {
        // Remove the value associated with the key from the cache
        $this->forget($key);
    }

    /**
     * Handle a result for the "many" method.
     *
     * @param  array  $keys
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return mixed
     */
    protected function handleManyResult($keys, $key, $value)
    {
        // If we could not find the cache value, we will fire the missed event and get
        // the default value for this cache value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
        if ($value === null) {
            $this->event(
                $this->cacheMissedFactory->create([
                    'storeName' => $this->getName(),
                    'key' => $key,
                ]),
            );

            return (isset($keys[$key]) && ! Arr::isList($keys)) ? value($keys[$key]) : null;
        }

        // If we found a valid value we will fire the "hit" event and return the value
        // back from this function. The "hit" event gives developers an opportunity
        // to listen for every possible cache "hit" throughout this application.
        $this->event(
            $this->cacheHitFactory->create([
                'storeName' => $this->getName(),
                'key' => $key,
                'value' => $value,
            ]),
        );

        return $value;
    }

    /**
     * Store multiple items in the cache indefinitely.
     *
     * This method takes an associative array of key-value pairs and attempts to store each item in the cache
     * without any expiration time (indefinitely). It returns a boolean indicating whether all items were
     * successfully stored.
     *
     * @param  array  $values  An associative array where keys are the cache item keys and values are the items to be stored.
     * @param  array  $tags  An array of tags to associate with the cached items.
     *
     * @return bool Returns true if all items were successfully stored, false if any item failed to be stored.
     */
    protected function putManyForever(array $values, array $tags = [])
    {
        // Initialize the result to true, assuming all operations will succeed
        $result = true;

        // Iterate over each key-value pair in the provided array
        foreach ($values as $key => $value) {
            // Attempt to store each item indefinitely using the 'forever' method
            if (! $this->forever($key, $value, $tags)) {
                // If any item fails to be stored, set the result to false
                $result = false;
            }
        }

        // Return the final result indicating success or failure of the operation
        return $result;
    }

    /**
     * Format the key for a cache item.
     *
     * This method is responsible for returning the formatted key that will be used to store or retrieve
     * the cache item. The implementation currently returns the key as-is but can be extended to
     * apply additional formatting rules if needed in the future.
     *
     * @param  string  $key  The original key to be formatted for the cache item.
     *
     * @return string Returns the formatted key for the cache item.
     */
    protected function itemKey($key)
    {
        // Currently, the method returns the key without any modifications
        return $key;
    }

    /**
     * Calculate the number of seconds for the given TTL (Time to Live).
     *
     * This method takes a TTL value, which can be a DateTimeInterface object, DateInterval, or integer,
     * and calculates the number of seconds it represents. If the TTL is in the form of a DateTime,
     * it computes the difference in seconds from the current time to the given time.
     *
     * @param  DateTimeInterface|DateInterval|int  $ttl  The TTL value to be parsed into seconds.
     *
     * @return int Returns the number of seconds represented by the given TTL, or 0 if the duration is negative.
     */
    protected function getSeconds($ttl)
    {
        // Parse the provided TTL value to determine its duration
        $duration = $this->parseDateInterval($ttl);

        // If the duration is a DateTime object, calculate the difference in seconds from now
        if ($duration instanceof DateTimeInterface) {
            $duration = Date::now()->diffInSeconds($duration, false);
        }

        // Return the duration in seconds, ensuring it is non-negative
        return (int)($duration > 0 ? $duration : 0);
    }

    /**
     * Fire an event for this cache instance.
     *
     * This method is responsible for dispatching events related to the cache operations.
     * It can be used to notify other parts of the application about cache changes,
     * such as when items are stored or removed.
     *
     * @param  object|string  $event  The event object or string that represents the event to be dispatched.
     */
    protected function event($event): void
    {
        // Dispatch the event using the events dispatcher
        $this->events->dispatchNow($this->getName(), [$event]);
    }

    /**
     * Get the name of the cache store.
     *
     * This method retrieves the name of the cache store from the configuration.
     * If no store name is set, it returns null.
     *
     * @return string|null Returns the name of the cache store, or null if not set.
     */
    protected function getName()
    {
        // Return the store name from the configuration, or null if not available
        return $this->config['store'] ?? 'redis';
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the store.
     *
     * This magic method allows for dynamic method calls on the cache instance.
     * If the called method is defined as a macro, it executes that macro.
     * Otherwise, it attempts to call the method on the underlying cache store instance.
     *
     * @param  string  $method  The name of the method being called dynamically.
     * @param  array  $parameters  The parameters to be passed to the method.
     *
     * @return mixed The result of the method call, either from the macro or the store.
     */
    public function __call($method, $parameters)
    {
        // Check if the called method is defined as a macro
        if (static::hasMacro($method)) {
            // If it is, call the macro with the provided parameters
            return $this->macroCall($method, $parameters);
        }

        // If not a macro, delegate the method call to the underlying store
        return $this->store->{$method}(...$parameters);
    }

    /**
     * Clone cache repository instance.
     *
     * This magic method is triggered when an instance of the cache repository is cloned.
     * It ensures that the underlying cache store instance is also cloned, preventing
     * shared references between the original and the cloned instances.
     *
     * @return void
     */
    public function __clone()
    {
        // Clone the underlying cache store instance to avoid reference sharing
        $this->store = clone $this->store;
    }
}
