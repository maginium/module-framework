<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use Magento\Framework\App\CacheInterface;
use Maginium\Framework\Cache\Interfaces\StoreInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Serializer;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Class TaggableStore.
 *
 * This abstract class provides a base implementation for cache stores that support
 * tagging functionality. It allows cache items to be grouped and retrieved efficiently
 * based on associated tags, enabling more granular cache management and retrieval.
 *
 * Taggable cache operations enable developers to interact with cached data based on
 * meaningful categories (tags), making cache operations more efficient and organized.
 */
abstract class TaggableStore implements StoreInterface
{
    /**
     * The TaggedCache instance used to perform tagged cache operations.
     *
     * This factory class creates the necessary tagged cache instances for interacting
     * with cache items grouped by tags.
     */
    protected TaggedCacheFactory $taggedCacheFactory;

    /**
     * The TagSet instance that holds tag names for cache operations.
     *
     * This factory class is responsible for creating the set of tags that will be associated
     * with cached items, allowing operations to target specific groups of cache entries.
     */
    protected TagSetFactory $tagSetFactory;

    /**
     * The array of tags associated with the current cache operation.
     *
     * This property stores the tags that will be applied to cache items during operations.
     */
    protected array $tags = [];

    /**
     * TaggableStore constructor.
     *
     * This constructor injects the required dependencies for performing tagged cache operations.
     * It enables the class to manage cache operations based on tags and tag sets.
     *
     * @param TaggedCacheFactory $taggedCacheFactory  The factory responsible for creating tagged cache instances.
     * @param TagSetFactory $tagSetFactory  The factory that creates tag sets for grouping cache items.
     */
    public function __construct(TaggedCacheFactory $taggedCacheFactory, TagSetFactory $tagSetFactory)
    {
        // Assign the injected factory instances to the class properties
        $this->tagSetFactory = $tagSetFactory;
        $this->taggedCacheFactory = $taggedCacheFactory;
    }

    /**
     * Begin a new cache operation with tags.
     *
     * This method enables operations to be grouped and executed based on tags, allowing
     * for the efficient retrieval and management of cache items associated with the same tag(s).
     *
     * You can pass either a single array of tag names or multiple tag names as individual arguments.
     *
     * @param  array  $names  A single array of tag names or multiple individual tag names.
     *                               This parameter specifies the tags that will be associated with the cache operation.
     *
     * @return TaggedCache Returns a TaggedCache instance that can be used to perform further cache operations
     *                     using the specified tags.
     */
    public function tags($names = []): TaggedCache
    {
        // Convert the names to snake case then upper
        $names = Arr::map($names, fn($name) => Str::upper(Str::snake($name)));

        // Ensure $names is an array, or convert arguments into an array if not
        $tagSet = $this->tagSetFactory->create([
            'store' => $this,
            'names' => Validator::isArray($names) ? $names : func_get_args(),
        ]);

        // Store the tags in the class property
        $this->tags = Validator::isArray($names) ? $names : func_get_args();

        // Return a TaggedCache instance for performing cache operations with the given tags
        return $this->taggedCacheFactory->create(['store' => $this, 'tags' => $tagSet]);
    }

    /**
     * Get the tags associated with the current cache operation.
     *
     * This method retrieves the tags that were applied to the current cache operation.
     *
     * @return array The array of tags associated with the current cache operation.
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Remove all entries associated with specific tags from the cache.
     *
     * This method will remove all cache entries that are associated with the specified
     * tags. This allows for more granular cache invalidation by removing only the entries
     * associated with certain tags, while leaving other cache entries intact.
     *
     * @param array $tags array of tags to associate with the cached items.
     *
     * @return bool Returns true if the cache was successfully flushed for the given tags, false otherwise.
     */
    public function flushTags(array $tags): bool
    {
        // Get Tags
        $tags ??= $this->getTags();

        // Use the connection's clean method to remove items by tags
        return $this->connection()->clean($tags);
    }

    /**
     * Get the Redis connection instance used for cache operations.
     *
     * This method provides access to the underlying cache connection, which can
     * be used to execute additional cache operations.
     *
     * @return CacheInterface The instance of the cache connection.
     */
    abstract public function connection(): CacheInterface;

    /**
     * Serialize the given value for storage in the cache.
     *
     * Serialization is required for complex data types (such as arrays or objects) to be stored
     * in the cache, as they cannot be directly saved in their native form. This method ensures that
     * values are serialized unless they are numeric, infinity, or NaN.
     *
     * @param mixed $value The value to be serialized.
     *
     * @return mixed The serialized value, or the original value if it is numeric.
     */
    protected function serialize($value): mixed
    {
        // Check if the value is numeric or an acceptable non-serialized type
        return Validator::isNumeric($value) && ! Validator::inArray($value, [INF, -INF]) && ! is_nan((float)$value)
            ? $value  // If it's numeric, return the value as is
            : Serializer::serialize($value); // Otherwise, serialize the value
    }

    /**
     * Unserialize the given value retrieved from the cache.
     *
     * After retrieving a cached value, it may need to be unserialized back to its original
     * form, especially for non-numeric data types. This method handles the unserialization.
     *
     * @param mixed $value The value to be unserialized.
     *
     * @return mixed The unserialized value, or the original value if it is numeric.
     */
    protected function unserialize($value): mixed
    {
        // If the value is numeric, return it directly (no unserialization needed)
        return Validator::isNumeric($value) ? $value : Serializer::unserialize($value); // Unserialize for non-numeric values
    }
}
