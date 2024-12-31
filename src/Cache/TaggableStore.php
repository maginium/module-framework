<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use Maginium\Framework\Cache\Interfaces\StoreInterface;
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
     * TaggableStore constructor.
     *
     * This constructor injects the required dependencies for performing tagged cache operations.
     * It enables the class to manage cache operations based on tags and tag sets.
     *
     * @param  TaggedCacheFactory  $taggedCacheFactory  The factory responsible for creating tagged cache instances.
     * @param  TagSetFactory  $tagSetFactory  The factory that creates tag sets for grouping cache items.
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
     * @param  array|string  $names  A single array of tag names or multiple individual tag names.
     *                               This parameter specifies the tags that will be associated with the cache operation.
     *
     * @return TaggedCache Returns a TaggedCache instance that can be used to perform further cache operations
     *                     using the specified tags.
     */
    public function tags($names)
    {
        // Ensure $names is an array, or convert arguments into an array if not
        $tagSet = $this->tagSetFactory->create([
            'names' => Validator::isArray($names) ? $names : func_get_args(),
        ]);

        // Return a TaggedCache instance for performing cache operations with the given tags
        return $this->taggedCacheFactory->create(['tags' => $tagSet]);
    }
}
