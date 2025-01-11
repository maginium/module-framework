<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

use DateInterval;
use DateTimeInterface;
use Maginium\Framework\Support\Validator;

/**
 * Trait RetrievesMultipleKeys.
 *
 * This trait provides methods to retrieve and store multiple cache items at once.
 * It allows for bulk fetching and saving of cache items, with the ability to specify
 * time-to-live (TTL) and cache tags for the stored values.
 */
trait RetrievesMultipleKeys
{
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
        // Initialize the return array to store the cached values
        $return = [];

        // Normalize the keys to ensure they are all valid. If the keys array has both keys and values,
        // we ensure that the values are null where no default is specified.
        $keys = collect($keys)->mapWithKeys(fn($value, $key) => [
            Validator::isString($key) ? $key : $value => Validator::isString($key) ? $value : null,
        ])->all();

        // Iterate through each key and retrieve its corresponding cache value
        foreach ($keys as $key => $default) {
            // @phpstan-ignore arguments.count (some clients don't accept a default value)
            // Call the get method to retrieve the cache item, passing the default if the item is not found
            $return[$key] = $this->get($key, $default);
        }

        // Return the associative array with the results of the cache retrieval
        return $return;
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * This method allows you to store an associative array of key-value pairs in the cache.
     * If a time-to-live (TTL) is provided, it will set the duration for which the items
     * remain in the cache. If no TTL is provided, the items will be stored indefinitely.
     *
     * @param  array  $values  An associative array of key-value pairs to store in the cache.
     * @param  DateTimeInterface|DateInterval|int|null  $ttl  The time-to-live for the cache items.
     * @param array $tags array of tags to associate with the cached items.
     *
     * @return bool Returns true if all items are successfully stored, false if any item fails.
     */
    public function putMany(array $values, $ttl = null, $tags = []): bool
    {
        // Initialize a variable to track the result of each put operation
        $manyResult = null;

        // Loop through each key-value pair in the provided array
        foreach ($values as $key => $value) {
            // Store each item in the cache using the put method
            $result = $this->put($key, $value, $ttl, $tags);

            // Update the result for all cache operations, ensuring that all must succeed
            $manyResult = $manyResult === null ? $result : $result && $manyResult;
        }

        // Return the final result. If all operations succeed, true will be returned, otherwise false.
        return $manyResult ?: false;
    }
}
