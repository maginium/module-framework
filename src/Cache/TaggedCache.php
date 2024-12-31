<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use DateInterval;
use DateTimeInterface;
use Magento\Framework\App\CacheInterface as BaseCacheInterface;
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
use Maginium\Framework\Defer\Interfaces\DeferInterface;
use Maginium\Framework\Event\Interfaces\EventInterface;
use Maginium\Framework\Redis\Interfaces\RedisInterface;

class TaggedCache extends CacheManager
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
     * @param  TagSet  $tags  The tag set instance for managing cache tags.
     * @param  DeferInterface  $defer  The defer manager implementation.
     * @param  BaseCacheInterface  $cache  The base cache manager.
     * @param  RedisInterface  $store  The Redis client for caching operations.
     * @param  EventInterface  $events  The event dispatcher for cache-related events.
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
     */
    public function __construct(
        TagSet $tags,
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
    ) {
        parent::__construct(
            $defer,
            $store,
            $events,
            $cache,
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
     * @param  string  $key  The cache key of the item to increment.
     * @param  mixed  $value  The value to increment the item by (default is 1).
     *
     * @return int|bool The new value of the cache item after incrementing, or false on failure.
     */
    public function increment($key, $value = 1)
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
     * @param  string  $key  The cache key of the item to decrement.
     * @param  mixed  $value  The value to decrement the item by (default is 1).
     *
     * @return int|bool The new value of the cache item after decrementing, or false on failure.
     */
    public function decrement($key, $value = 1)
    {
        // Decrements the cache item value in the Redis store.
        return $this->store->decrement($this->itemKey($key), $value);
    }

    /**
     * Store multiple cache items for a specific duration.
     *
     * This method allows storing multiple key-value pairs in the cache. You can optionally provide
     * a time-to-live (TTL) to specify the duration for which the items should be cached.
     *
     * @param  array  $values  An associative array of key-value pairs to store in the cache.
     * @param  array  $tags  An array of tags to associate with the cached items.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  The time-to-live for the cache items.
     *
     * @return bool Returns true if all items were successfully stored, false otherwise.
     */
    public function putMany(array $values, array $tags = [], $ttl = null): bool
    {
        // If no TTL is provided, store items indefinitely.
        if ($ttl === null) {
            return $this->putManyForever($values);
        }

        // Store items with the specified TTL.
        return $this->putManyAlias($values, $tags, $ttl);
    }

    /**
     * Remove all items from the cache.
     *
     * This method clears the cache and resets the associated tags.
     *
     * @return bool Returns true after clearing the cache.
     */
    public function flush()
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
     * @param  string  $key  The original cache key.
     *
     * @return string The fully qualified key for the tagged cache item.
     */
    public function taggedItemKey($key)
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
    public function getTags()
    {
        // Return the TagSet instance.
        return $this->tags;
    }

    /**
     * {@inheritdoc}
     *
     * Override the method to return the fully qualified key for the cache item.
     *
     * @param  string  $key  The cache key.
     *
     * @return string The fully qualified cache key.
     */
    protected function itemKey($key)
    {
        return $this->taggedItemKey($key);
    }

    /**
     * Dispatch an event for cache operations.
     *
     * This method is responsible for dispatching events related to cache operations such as
     * storing, retrieving, or removing cache items. Events provide a way to notify other
     * parts of the application about changes in cache state.
     *
     * @param  object|string  $event  The event object or string that represents the event.
     */
    protected function event($event): void
    {
        // Dispatch the event with the associated tags.
        parent::event($event->setTags($this->tags->getNames()));
    }
}
