<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Traits;

use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Validator;

/**
 * KeyParser trait resolves key strings into namespace, group, and item.
 *
 * Example usage: "namespace::group.item" will be parsed into:
 * - namespace: "namespace"
 * - group: "group"
 * - item: "item"
 */
trait KeyParser
{
    /**
     * @var array keyParserCache is a cache of the parsed items.
     *
     * This array stores previously parsed keys to avoid redundant parsing
     * and improve performance on subsequent requests.
     */
    protected $keyParserCache = [];

    /**
     * setParsedKey stores the parsed value for a given key in the cache.
     *
     * This method allows the caller to store a parsed result associated
     * with a specific key in the key parser cache.
     *
     * @param string $key The original key string that was parsed.
     * @param array $parsed The parsed array corresponding to the key.
     *
     * @return void
     */
    public function setParsedKey(string $key, array $parsed): void
    {
        // Store the parsed result in the cache for future reference.
        $this->keyParserCache[$key] = $parsed;
    }

    /**
     * parseKey splits the key into its namespace, group, and item components.
     *
     * This method is responsible for parsing a given key string into an
     * array that contains the namespace, group, and item. If the key has
     * been parsed previously, it returns the cached result.
     *
     * @param string $key The key string to be parsed.
     *
     * @return array An array containing the namespace, group, and item.
     */
    public function parseKey(string $key): array
    {
        // Check if the key has already been parsed and cached.
        if (isset($this->keyParserCache[$key])) {
            // Return the cached result if it exists.
            return $this->keyParserCache[$key];
        }

        // Split the key into segments based on the dot separator.
        $segments = explode('.', $key);

        // Check if the key includes a namespace by looking for the double colon.
        if (! str_contains($key, '::')) {
            // Parse the segments as a basic configuration item (no namespace).
            $parsed = $this->keyParserParseBasicSegments($segments);
        } else {
            // Parse the key as a namespaced configuration item.
            $parsed = $this->keyParserParseSegments($key);
        }

        // Cache the parsed result for future look-ups.
        return $this->keyParserCache[$key] = $parsed;
    }

    /**
     * keyParserParseBasicSegments processes an array of segments.
     *
     * This method is used when the key does not have a namespace and
     * only needs to resolve the group and item from the segments.
     *
     * @param array $segments The segments obtained from splitting the key.
     *
     * @return array An array containing namespace (null), group, and item (null if no item).
     */
    protected function keyParserParseBasicSegments(array $segments): array
    {
        // The first segment is always the group.
        $group = $segments[0];

        // If there's only one segment, return the group with null values for namespace and item.
        if (Validator::isEmpty($segments) === 1) {
            return [null, $group, null];
        }

        // If there are multiple segments, extract the item from the remaining segments.
        $item = implode('.', Arr::slice($segments, 1));

        // Return the parsed result: namespace (null), group, and the item.
        return [null, $group, $item];
    }

    /**
     * keyParserParseSegments processes a namespaced key string.
     *
     * This method is used when the key has a namespace, and it extracts
     * the namespace, group, and item from the key string.
     *
     * @param string $key The key string containing the namespace and item.
     *
     * @return array An array containing the namespace, group, and item.
     */
    protected function keyParserParseSegments(string $key): array
    {
        // Split the key at the double colon to separate the namespace from the item.
        [$namespace, $item] = explode('::', $key);

        // Split the item into segments based on the dot separator.
        $itemSegments = explode('.', $item);

        // Parse the item segments into group and item details.
        $groupAndItem = Arr::slice($this->keyParserParseBasicSegments($itemSegments), 1);

        // Return an array that includes the namespace followed by group and item details.
        return Arr::merge([$namespace], $groupAndItem);
    }
}
