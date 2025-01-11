<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use Maginium\Framework\Cache\Interfaces\StoreInterface;
use Maginium\Framework\Support\Arr;

/**
 * Class TagSet.
 *
 * Manages a set of cache tags that are used to group cache items for easier invalidation or retrieval.
 * This class provides functionality to reset, flush, and manage unique identifiers for tags.
 * It interacts with a cache store to persist tag-related data and performs operations based on tags.
 */
class TagSet
{
    /**
     * The cache store implementation used to interact with the cache.
     */
    protected StoreInterface $store;

    /**
     * The names of the tags in this set.
     */
    protected array $names = [];

    /**
     * Create a new TagSet instance.
     *
     * This constructor initializes the tag set with a given cache store and tag names.
     * The store is used to persist and retrieve tag-related data, while the names array
     * holds the tags that belong to the set.
     *
     * @param StoreInterface $store The cache store instance to interact with.
     * @param array $names  The array of tag names.
     */
    public function __construct(StoreInterface $store, array $names = [])
    {
        $this->store = $store;
        $this->names = $names;
    }

    /**
     * Reset all the tags in the set.
     *
     * This method resets each tag in the set, generating a new unique identifier for each one.
     * The identifier is stored in the cache to uniquely identify the tag.
     *
     * @return void
     */
    public function reset(): void
    {
        // Flush Tags using store
        $this->store->flushTags($this->getNames());

        // Loop through each tag name and reset it by generating a new identifier.
        Arr::walk($this->names, [$this, 'resetTag']);
    }

    /**
     * Reset a single tag and generate a new unique identifier.
     *
     * This method generates a new unique identifier for the given tag and stores it in the cache.
     *
     * @param  string  $name  The name of the tag to reset.
     *
     * @return string The newly generated tag identifier.
     */
    public function resetTag($name): string
    {
        // Store the unique identifier for the tag in the cache, using the tag's key.
        $this->store->forever($this->tagKey($name), $id = str_replace('.', '', uniqid('', true)));

        // Return the newly generated unique identifier.
        return $id;
    }

    /**
     * Flush all tags in the set.
     *
     * This method removes each tag's identifier from the cache, effectively invalidating the tags.
     *
     * @return void
     */
    public function flush(): void
    {
        // Loop through each tag name and flush it by removing its cache entry.
        Arr::walk($this->names, [$this, 'flushTag']);
    }

    /**
     * Flush a single tag by removing its identifier from the cache.
     *
     * This method removes the tag's entry from the cache, invalidating it.
     *
     * @param  string  $name  The name of the tag to flush.
     */
    public function flushTag($name): void
    {
        // Flush Tags using store
        $this->store->flushTags($this->getNames());

        // Remove the tag's entry from the cache.
        $this->store->forget($this->tagKey($name));
    }

    /**
     * Get a unique namespace that represents the current tag set.
     *
     * The namespace is a concatenation of all tag identifiers, allowing cache keys to be
     * grouped and identified by the tags they belong to.
     *
     * @return string The namespace string representing the tag set.
     */
    public function getNamespace(): string
    {
        // Return a concatenated string of all tag identifiers in the set.
        return implode('|', $this->tagIds());
    }

    /**
     * Get the unique identifier for a given tag.
     *
     * This method retrieves the identifier for the specified tag from the cache. If the identifier
     * does not exist, it generates and stores a new identifier for the tag.
     *
     * @param  string  $name  The name of the tag.
     *
     * @return string The tag's unique identifier.
     */
    public function tagId($name): string
    {
        // Attempt to retrieve the tag's identifier from the cache, or generate a new one if it doesn't exist.
        return $this->store->get($this->tagKey($name)) ?: $this->resetTag($name);
    }

    /**
     * Get the cache key used to store the identifier for a given tag.
     *
     * This method constructs and returns the cache key that is used to store the tag's identifier.
     *
     * @param  string  $name  The name of the tag.
     *
     * @return string The cache key for the tag.
     */
    public function tagKey($name): string
    {
        // Return a string that uniquely identifies the cache entry for this tag.
        return 'tag:' . $name . ':key';
    }

    /**
     * Get the list of tag names in the set.
     *
     * This method returns the array of tag names that belong to the current tag set.
     *
     * @return array The array of tag names.
     */
    public function getNames(): array
    {
        // Return the list of tag names.
        return $this->names;
    }

    /**
     * Get an array of tag identifiers for all tags in the set.
     *
     * This method returns an array of unique identifiers for all tags in the set.
     *
     * @return array An array of tag identifiers.
     */
    protected function tagIds(): array
    {
        // Map each tag name to its corresponding identifier.
        return Arr::each([$this, 'tagId'], $this->names);
    }
}
