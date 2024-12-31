<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Illuminate\Support\Arr as BaseArr;

/**
 * Class Arr.
 *
 * This class extends Arr helper functionalities, providing additional methods
 * to manipulate arrays more conveniently, especially for translation and building
 * new arrays using callbacks.
 */
class Arr extends BaseArr
{
    /**
     * Run a map over each of the items in the array.
     *
     * @param  array  $array
     * @param  callable  $callback
     *
     * @return array
     */
    public static function each(callable $callback, array $array)
    {
        return static::map($array, $callback);
    }

    /**
     * Build a new array using a callback function.
     *
     * This method iterates over each element in the provided array and applies the
     * specified callback to each key-value pair. The callback should return an array
     * containing the new key and value for the resulting array.
     *
     * @param array $array The input array to be transformed.
     * @param callable $callback A callback function that takes two parameters:
     *                           the key and value from the original array and
     *                           returns an array with the new key and value.
     *
     * @return array The newly built array with keys and values transformed
     *               according to the callback.
     */
    public static function build($array, callable $callback): array
    {
        // Initialize an empty array to hold the results.
        $results = [];

        // Iterate over each key-value pair in the input array.
        foreach ($array as $key => $value) {
            // Call the provided callback function, which returns a new key and value.
            [$innerKey, $innerValue] = call_user_func($callback, $key, $value);

            // Assign the new key-value pair to the results array.
            $results[$innerKey] = $innerValue;
        }

        // Return the newly constructed array.
        return $results;
    }

    /**
     * Translate an array of strings, typically for dropdowns and checkbox list options.
     *
     * This method recursively walks through the provided array and translates
     * any string values using translation mechanism. The translated
     * values replace the original string values in the array.
     *
     * @param array $arr The input array containing strings to be translated.
     *
     * @return array The array with translated string values.
     */
    public static function trans(array $arr): array
    {
        // Use array_walk_recursive to apply a function to each value in the array.
        array_walk_recursive($arr, function(&$value, $key): void {
            // Check if the current value is a string.
            if (Validator::isString($value)) {
                // Translate the string using Lang facade.
                $value = __($value)->render();
            }
        });

        // Return the array with the translated values.
        return $arr;
    }

    /**
     * Get all the keys from the array.
     *
     * This method is a wrapper for PHP's `array_keys`, which returns all the keys of
     * an array. Optionally, it can also filter by a specific value.
     *
     * @param array $array The input array.
     * @param mixed $value (Optional) The value to filter keys by.
     *
     * @return array The array of keys.
     */
    public static function keys(array $array, mixed $value = null): array
    {
        if ($value) {
            return array_keys($array, $value);
        }

        return array_keys($array);
    }

    /**
     * Get all the values from the array.
     *
     * This method is a wrapper for PHP's `array_values`, which returns all the values
     * from an array, discarding the keys.
     *
     * @param array $array The input array.
     *
     * @return array The array of values.
     */
    public static function values(array $array): array
    {
        return array_values($array);
    }

    /**
     * Remove and return the first element of the array.
     *
     * This method is a wrapper for PHP's `array_shift`, which removes the first
     * element of the array and returns it. The array is re-indexed after the operation.
     *
     * @param array $array The array to remove the first element from.
     *
     * @return mixed The removed element.
     */
    public static function shift(array &$array): mixed
    {
        return array_shift($array);
    }

    /**
     * Create an array by using one array for keys and another for its values.
     *
     * This method is a wrapper for PHP's `array_combine`, which creates an array
     * by using the elements of one array as keys and the elements of another as values.
     *
     * @param array $keys The array of keys.
     * @param array $values The array of values.
     *
     * @return array The combined array.
     */
    public static function combine(array $keys, array $values): array
    {
        return array_combine($keys, $values);
    }

    /**
     * Check if a key exists in the array.
     *
     * This method is a wrapper for PHP's `array_key_exists`, which checks if the
     * given key exists in the array and returns a boolean result.
     *
     * @param mixed $key The key to check for.
     * @param array $array The array to check.
     *
     * @return bool True if the key exists, otherwise false.
     */
    public static function keyExists(mixed $key, array $array): bool
    {
        return array_key_exists($key, $array);
    }

    /**
     * Reduce the array to a single value.
     *
     * This method is a wrapper for PHP's `array_reduce`, which iterates over the
     * array and applies a callback function to accumulate a single result.
     *
     * @param array $array The array to reduce.
     * @param callable $callback The callback function to apply.
     * @param mixed $initial The initial value to start the reduction.
     *
     * @return mixed The final reduced value.
     */
    public static function reduce(array $array, callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($array, $callback, $initial);
    }

    /**
     * Fill an array with values, using the given keys.
     *
     * @param array $keys The keys to be used in the resulting array.
     * @param mixed $value The value to assign to each key.
     *
     * @return array The filled array.
     */
    public static function fillKeys(array $keys, mixed $value): array
    {
        return array_fill_keys($keys, $value);
    }

    /**
     * Extract a slice of the array.
     *
     * @param array $array The array to slice.
     * @param int $offset The offset to start the slice.
     * @param int|null $length The length of the slice (optional).
     * @param bool $preserveKeys Whether to preserve keys (optional).
     *
     * @return array The sliced array.
     */
    public static function slice(array $array, int $offset, ?int $length = null, bool $preserveKeys = false): array
    {
        return array_slice($array, $offset, $length, $preserveKeys);
    }

    /**
     * Filter the elements of an array using a callback function.
     *
     * @param array $array The array to filter.
     * @param ?callable $callback The callback function to determine which elements to keep.
     *
     * @return array The filtered array.
     */
    public static function filter(array $array, ?callable $callback = null, int $mode = 0): array
    {
        return array_filter($array, $callback, $mode);
    }

    /**
     * Prepend one or more elements to the beginning of an array.
     *
     * @param array $array The array to modify.
     * @param mixed ...$values The values to prepend.
     *
     * @return int The new number of elements in the array.
     */
    public static function unshift(array &$array, mixed ...$values): int
    {
        return array_unshift($array, ...$values);
    }

    /**
     * Change the case of all keys in an array.
     *
     * @param array $array The array whose keys to change.
     * @param int $case The case type (either CASE_UPPER or CASE_LOWER).
     *
     * @return array The array with changed case for keys.
     */
    public static function changeKeyCase(array $array, int $case): array
    {
        return array_change_key_case($array, $case);
    }

    /**
     * Reverse the order of the elements in an array.
     *
     * @param array $array The array to reverse.
     * @param bool $preserveKeys Whether to preserve keys (optional).
     *
     * @return array The reversed array.
     */
    public static function reverse(array $array, bool $preserveKeys = false): array
    {
        return array_reverse($array, $preserveKeys);
    }

    /**
     * Push one or more elements onto the end of an array.
     *
     * @param array $array The array to modify.
     * @param mixed ...$values The values to append.
     *
     * @return int The new number of elements in the array.
     */
    public static function push(array &$array, mixed ...$values): int
    {
        return array_push($array, ...$values);
    }

    /**
     * Pad an array to a specified length with a given value.
     *
     * @param array $array The array to pad.
     * @param int $size The desired length of the array.
     * @param mixed $value The value to pad the array with.
     *
     * @return array The padded array.
     */
    public static function pad(array $array, int $size, mixed $value): array
    {
        return array_pad($array, $size, $value);
    }

    /**
     * Replace elements in an array with new values.
     *
     * This method allows replacing elements in the original array with corresponding values
     * from one or more replacement arrays. Non-recursive replacement is performed.
     *
     * @param array $array The original array.
     * @param array ...$replacements One or more arrays containing replacement values.
     *
     * @return array The array with replaced values.
     */
    public static function replace(array $array, array ...$replacements): array
    {
        return array_replace($array, ...$replacements);
    }

    /**
     * Recursively replace elements in an array with new values.
     *
     * This method performs a recursive replacement of elements in the original array
     * with corresponding values from one or more replacement arrays.
     *
     * @param array $array The original array.
     * @param array ...$replacements One or more arrays containing replacement values.
     *
     * @return array The array with recursively replaced values.
     */
    public static function replaceRecursive(array $array, array ...$replacements): array
    {
        return array_replace_recursive($array, ...$replacements);
    }

    /**
     * Get a column from a multi-dimensional array.
     *
     * @param array $array The input array.
     * @param mixed $columnKey The column to retrieve.
     * @param mixed $indexKey (Optional) The index to use for the resulting array.
     *
     * @return array The array containing the column's values.
     */
    public static function column(array $array, mixed $columnKey, mixed $indexKey = null): array
    {
        return array_column($array, $columnKey, $indexKey);
    }

    /**
     * Get a random key or value from an array.
     *
     * @param array $array The input array.
     * @param int $num (Optional) The number of random elements to retrieve.
     *
     * @return mixed The random element(s) from the array.
     */
    public static function rand(array $array, int $num = 1): mixed
    {
        return array_rand($array, $num);
    }

    /**
     * Remove duplicate values from an array.
     *
     * @param array $array The input array.
     *
     * @return array The array with duplicate values removed.
     */
    public static function unique(array $array): array
    {
        return array_unique($array);
    }

    /**
     * Compute the difference of arrays.
     *
     * @param array $array The array to compare.
     * @param array ...$arrays The arrays to compare against.
     *
     * @return array The array containing the values that are not present in the other arrays.
     */
    public static function diff(array $array, array ...$arrays): array
    {
        return array_diff($array, ...$arrays);
    }

    /**
     * Fill an array with values.
     *
     * @param int $count The number of elements to insert.
     * @param mixed $value The value to fill the array with.
     *
     * @return array The filled array.
     */
    public static function fill(int $start_index, int $count, mixed $value): array
    {
        return array_fill($start_index, $count, $value);
    }

    /**
     * Pop the last element from an array.
     *
     * @param array $array The array to pop from.
     *
     * @return mixed The popped element.
     */
    public static function pop(array &$array): mixed
    {
        return array_pop($array);
    }

    /**
     * Check if an array is a list.
     *
     * @param array $array The input array.
     *
     * @return bool True if the array is a list, otherwise false.
     */
    public static function isList($array): bool
    {
        return array_is_list($array);
    }

    /**
     * Get the last key of an array.
     *
     * @param array $array The input array.
     *
     * @return mixed The last key in the array.
     */
    public static function keyLast(array $array): mixed
    {
        return array_key_last($array);
    }

    /**
     * Get the intersection of arrays.
     *
     * @param array $array The array to compare.
     * @param array ...$arrays The arrays to compare against.
     *
     * @return array The array containing the intersection of the arrays.
     */
    public static function intersect(array $array, array ...$arrays): array
    {
        return array_intersect($array, ...$arrays);
    }

    /**
     * Walk through the array and apply a callback function to each element.
     *
     * @param array $array The input array.
     * @param callable $callback The callback function to apply.
     *
     * @return void
     */
    public static function walk(array &$array, callable $callback): void
    {
        array_walk($array, $callback);
    }

    /**
     * Search for a value in an array and return the key if found.
     *
     * @param mixed $needle The value to search for.
     * @param array $haystack The array to search in.
     * @param bool $strict Whether to use strict comparison (optional).
     *
     * @return mixed The key of the found element, or false if not found.
     */
    public static function search(mixed $needle, array $haystack, bool $strict = false): mixed
    {
        return array_search($needle, $haystack, $strict);
    }

    /**
     * Merge one or more arrays into the original array.
     *
     * This method merges the given arrays into the current array using `array_merge`.
     * It combines the input arrays into a single array, with later arrays overriding
     * values from earlier ones if they have the same keys.
     *
     * @param array ...$arrays Arrays to be merged with the current array.
     *
     * @return array The resulting array after merging all input arrays.
     */
    public static function merge(array ...$arrays): array
    {
        // Use array_merge to merge all input arrays and return the result.
        return array_merge(...$arrays);
    }
}
