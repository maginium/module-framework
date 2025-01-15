<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Cache\Enums\CacheEvents;
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
use Maginium\Framework\Cache\Interfaces\StoreInterface;
use Maginium\Framework\Defer\Interfaces\DeferInterface;
use Maginium\Framework\Event\Interfaces\EventInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Validator;
use Throwable;

class TaggedCache extends Repository
{
    use RetrievesMultipleKeys {
        putMany as putManyAlias;
    }

    /**
     * The tag set instance used for tagging cache items.
     */
    protected TagSet $tags;

    /**
     * Constructor for the TaggedCache class.
     *
     * Initializes the cache manager with all required dependencies, including the Redis client,
     * event dispatcher, and multiple factories for handling specific cache events.
     *
     * @param TagSet $tags The tag set instance for managing cache tags.
     * @param DeferInterface $defer The defer manager implementation.
     * @param StoreInterface $store The Redis client for caching operations.
     * @param EventInterface $events The event dispatcher for cache-related events.
     * @param CacheHitFactory $cacheHitFactory Factory for cache hit events.
     * @param WritingKeyFactory $writingKeyFactory Factory for writing key events.
     * @param KeyWrittenFactory $keyWrittenFactory Factory for key written events.
     * @param CacheMissedFactory $cacheMissedFactory Factory for cache miss events.
     * @param KeyForgottenFactory $keyForgottenFactory Factory for key forgotten events.
     * @param ForgettingKeyFactory $forgettingKeyFactory Factory for forgetting key events.
     * @param RetrievingKeyFactory $retrievingKeyFactory Factory for retrieving key events.
     * @param KeyWriteFailedFactory $keyWriteFailedFactory Factory for key write failure events.
     * @param KeyForgetFailedFactory $keyForgetFailedFactory Factory for key forget failure events.
     * @param WritingManyKeysFactory $writingManyKeysFactory Factory for writing multiple keys.
     * @param RetrievingManyKeysFactory $retrievingManyKeysFactory Factory for retrieving multiple keys.
     */
    public function __construct(
        TagSet $tags,
        DeferInterface $defer,
        StoreInterface $store,
        EventInterface $events,
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
    ) {
        parent::__construct(
            $store,
            $defer,
            $events,
            $cacheHitFactory,
            $writingKeyFactory,
            $keyWrittenFactory,
            $cacheMissedFactory,
            $keyForgottenFactory,
            $forgettingKeyFactory,
            $retrievingKeyFactory,
            $keyWriteFailedFactory,
            $keyForgetFailedFactory,
            $writingManyKeysFactory,
            $retrievingManyKeysFactory,
        );

        $this->tags = $tags;
    }

    /**
     * Increment the value of a cache item.
     *
     * This method increments the stored value for a specific cache item by the given value.
     * If the item doesn't exist, the operation will fail.
     *
     * @param string $key The cache key of the item to increment.
     * @param mixed $value The value to increment the item by (default is 1).
     *
     * @return int The new value of the cache item after incrementing, or false on failure.
     */
    public function increment($key, $value = 1): int
    {
        // Increments the cache item value in the Redis store.
        return $this->store->increment($this->itemKey($key), $value);
    }

    /**
     * Decrement the value of a cache item.
     *
     * This method decrements the stored value for a specific cache item by the given value.
     * If the item doesn't exist, the operation will fail.
     *
     * @param string $key The cache key of the item to decrement.
     * @param mixed $value The value to decrement the item by (default is 1).
     *
     * @return int The new value of the cache item after decrementing, or false on failure.
     */
    public function decrement($key, $value = 1): int
    {
        // Decrements the cache item value in the Redis store.
        return $this->store->decrement($this->itemKey($key), $value);
    }

    /**
     * Remove all items from the cache.
     *
     * This method clears the cache and resets the associated tags.
     *
     * @return bool Returns true after clearing the cache.
     */
    public function flush(): bool
    {
        // Reset all tags.
        $this->tags->reset();

        // Return true after cache flush.
        return true;
    }

    /**
     * Generate a fully qualified key for a tagged cache item.
     *
     * This method generates a unique key for a cache item that is associated with tags.
     * The key is prefixed with the tag namespace and hashed for uniqueness.
     *
     * @param string $key The original cache key.
     *
     * @return string The fully qualified key for the tagged cache item.
     */
    public function taggedItemKey($key): string
    {
        // Generate a unique key by hashing the tag namespace and appending the cache key.
        return sha1($this->tags->getNamespace()) . ':' . $key;
    }

    /**
     * Retrieve the tag set instance.
     *
     * This method returns the instance of the TagSet that is used to manage cache tags.
     *
     * @return TagSet The tag set instance.
     */
    public function getTags(): TagSet
    {
        // Return the TagSet instance.
        return $this->tags;
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
     * @param  array  $tags  An array of tags to associate with the cached items.
     *
     * @return bool Returns true on success, false on failure.
     */
    public function put($key, $value, $ttl = null, $tags = []): bool
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
        $this->dispatch(CacheEvents::WRITING, $key, ['value' => $value, 'seconds' => $seconds]);

        // Check if the value is not already a string
        if (! Validator::isString($value)) {
            // If not a string, encode it as JSON
            $value = Json::encode($value);

            // If encoding fails, return false
            if ($value === false) {
                // Optionally, you can log the error if json_encode fails
                $this->logError("Failed to JSON encode value for key: {$key}");

                return false;
            }
        }

        $tags = Arr::merge($tags, $this->getTags()->getNames());

        // Store the item in the cache and capture the result.
        $result = $this->store->put($this->itemKey($key), $value, $seconds, $tags);

        // Trigger appropriate events based on the result of the storage operation.
        if ($result) {
            // Success event.
            $this->dispatch(CacheEvents::WRITTEN, $key, ['seconds' => $seconds, 'value' => $value]);
        } else {
            // Failure event.
            ${$this}->dispatch(CacheEvents::WRITE_FAILED, $key, ['seconds' => $seconds, 'value' => $value]);
        }

        // Return the result of the storage operation.
        return $result;
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
    public function set($key, $value, $ttl = null, $tags = []): bool
    {
        $tags = Arr::merge($tags, $this->getTags()->getNames());

        // Calls the 'put' method to store the item in the cache with an optional time-to-live (TTL).
        return $this->put($key, $value, $ttl, $tags);
    }

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
    public function setMultiple($values, $ttl = null, $tags = []): bool
    {
        $tags = Arr::merge($tags, $this->getTags()->getNames());

        // Converts the input values to an array if they are not already an array.
        return $this->putMany(Validator::isArray($values) ? $values : iterator_to_array($values), $ttl, $tags);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key  The key to store the value under.
     * @param  mixed  $value  The value to store in the cache.
     *
     * @return bool Returns true on successful storage, false otherwise.
     */
    public function forever($key, $value, $tags = []): bool
    {
        $tags = Arr::merge($tags, $this->getTags()->getNames());

        // Use handleClosure to process closures or other value types.
        $value = $this->handleClosure($value);

        // Check if the value is an array and serialize it to JSON.
        if (Validator::isArray($value)) {
            $value = Json::encode($value);
        }

        // Trigger an event to log that a key is being written to the cache.
        $this->dispatch(CacheEvents::WRITING, $key, ['storeName' => $this->getName(), 'value' => $value]);

        // Store the item in the cache indefinitely using the store's save method.
        $result = $this->store->put(key: $this->itemKey($key), value: $value, tags: $tags);

        // Trigger the success or failure event based on the result of the storage operation.
        if ($result) {
            // Event indicating the key was successfully written to the cache.
            $this->dispatch(CacheEvents::WRITTEN, $key, ['storeName' => $this->getName(), 'value' => $value]);
        } else {
            // Event indicating the key write failed.
            $this->dispatch(CacheEvents::WRITE_FAILED, $key, ['storeName' => $this->getName(), 'value' => $value]);
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
    public function remember($key, $ttl, Closure $callback, $tags = []): mixed
    {
        $tags = Arr::merge($tags, $this->getTags()->getNames());

        // Try to retrieve the value from the cache
        $value = $this->get($key);

        // If the cache contains the value, return it immediately
        if ($value !== null) {
            return $value;
        }

        // If the value is not in the cache, execute the callback to generate the value
        try {
            $value = $this->handleClosure($callback);
        } catch (Throwable $e) {
            // Handle any errors during closure execution (e.g., logging or rethrowing)
            throw RuntimeException::make("Failed to execute the closure for cache key '{$key}': " . $e->getMessage(), $e);
        }

        // Store the generated value in the cache, along with the TTL
        $this->put($key, $value, value($ttl, $value), $tags);

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * Override the method to return the fully qualified key for the cache item.
     *
     * @param string $key The cache key.
     *
     * @return string The fully qualified cache key.
     */
    protected function itemKey($key): string
    {
        // return $this->taggedItemKey($key);
        return $key;
    }

    /**
     * Trigger a cache event with relevant data.
     *
     * This helper method standardizes the process of firing cache-related events.
     *
     * @param string $eventType The type of event to trigger (e.g., CacheEvents::HIT, CacheEvents::MISSED, etc.).
     * @param mixed       $key       The cache key involved in the event, optional for some events.
     * @param array       $context   Contextual data such as `keys`, `value`, `seconds`, etc., required for specific events.
     *
     * @throws InvalidArgumentException If the provided event type is invalid.
     */
    protected function dispatch(
        string $eventType,
        mixed $key = null,
        array $context = [],
    ): void {
        // Dispatch the event with the associated tags.
        parent::dispatch($eventType, $key, ['tags' => $this->tags->getNames(), ...$context]);
    }
}
