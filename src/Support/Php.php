<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use DateTime;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Support\Facades\Container;

/**
 * Class PHP.
 *
 * General-purpose utility class.
 */
class Php
{
    /**
     * Get the value of a defined constant.
     *
     * @param string $constantName The constant name.
     *
     * @return mixed|null The value of the constant or null if not defined.
     */
    public static function getDefinedValue(string $constantName)
    {
        return isset($constantName) ? constant($constantName) : null;
    }

    /**
     * Check if a property exists in an object.
     *
     * @param object|string $object The object to check.
     * @param string $property The property name.
     */
    public static function propertyExists($object, string $property): bool
    {
        return property_exists($object, $property);
    }

    /**
     * Check if a key exists in an array.
     *
     * @param string|int $key The key to check.
     * @param array $array The array to check.
     *
     * @return bool True if the key exists in the array, false otherwise.
     */
    public static function arrayKeyExists($key, array $array): bool
    {
        return Arr::keyExists($key, $array);
    }

    /**
     * Split a string by a specified delimiter.
     *
     * @param string $delimiter The boundary string.
     * @param string $string The input string.
     * @param int $limit If `limit` is set and positive, the returned array will contain a maximum of `limit` elements with the last element containing the rest of `string`. If the `limit` parameter is negative, all components except the last - `limit` are returned. If the `limit` parameter is zero, then this is treated as 1.
     *
     * @return array Returns an array of strings created by splitting the string parameter on boundaries formed by the delimiter.
     */
    public static function explode(string $delimiter, string $string, int $limit = PHP_INT_MAX): array
    {
        return explode($delimiter, $string, $limit);
    }

    /**
     * Join array elements with a specified delimiter.
     *
     * @param string $delimiter The boundary string.
     * @param string[] $array The array of strings to join.
     *
     * @return string Returns a string containing a string representation of all the array elements in the same order, with the delimiter between each element.
     */
    public static function implode(string $delimiter, array $array): string
    {
        return implode($delimiter, $array);
    }

    /**
     * Perform a regular expression match.
     *
     * @param string $pattern The regular expression pattern.
     * @param string $subject The subject string to search.
     * @param string[]|null $matches An array to store the matches found (optional).
     * @param int $flags Any flags to be used in the match (optional).
     * @param int $offset The position in the subject string to start the search (optional).
     *
     * @return int|false The number of times the pattern matches (which may be zero), or false if an error occurred.
     */
    public static function pregMatch(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int|false
    {
        return preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Perform a global regular expression match and store the matches.
     *
     * @param string $pattern The regular expression pattern.
     * @param string $subject The subject string to search.
     * @param array|null $matches An array to store the matches.
     *
     * @return int The number of full pattern matches.
     */
    public static function pregMatchAll(string $pattern, string $subject, ?array &$matches): int
    {
        //   NOTE: Perform a global regular expression match and store the matches.
        return preg_match_all($pattern, $subject, $matches);
    }

    /**
     * Decode HTML models in a string.
     *
     * @param string $string The string containing HTML models.
     *
     * @return string The decoded string.
     */
    public static function htmlEntityDecode(string $string): string
    {
        return html_model_decode($string);
    }

    /**
     * Get a component or the entire parsed structure from a URL.
     *
     * This method parses a URL and retrieves a specific component or
     * the entire parsed array structure. It uses PHP's parse_url function
     * and provides additional error handling.
     *
     * @param string $url The URL to parse.
     * @param int $component The component to retrieve (e.g., PHP_URL_HOST, PHP_URL_PATH).
     *                       Defaults to -1, which returns the entire parsed array.
     *
     * @return string|array|null The requested URL component, the entire parsed array,
     *                           or null if the URL is invalid.
     */
    public static function parseUrl(string $url, int $component = -1)
    {
        // Validate URL
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            // Return null if the URL is not valid
            return;
        }

        // Parse the URL and return the requested component or the full array
        return parse_url($url, $component);
    }

    /**
     * Get the domain from a URL.
     *
     * @param string $url The URL to extract the domain from.
     *
     * @return string The extracted domain.
     */
    public static function getDomainFromUrl($url)
    {
        $parsedUrl = parse_url($url);

        //  Check if the 'host' key exists in the parsed URL array
        if (isset($parsedUrl['host'])) {
            //  Remove 'www.' from the host
            $parsedUrl['host'] = Str::replaceFirst('www.', '', $parsedUrl['host']);

            return $parsedUrl['host'];
        }

        return '';
    }

    /**
     * Perform a regular expression search and replace.
     *
     * @param string|array $pattern The pattern to search for.
     * @param string|array $replacement The string or array with which to replace.
     * @param string|array $subject The string or array to search and replace.
     * @param int $limit The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
     * @param int|null $count If specified, this variable will be filled with the number of replacements done.
     *
     * @return string|array Returns an array if the subject parameter is an array, or a string otherwise.
     */
    public static function pregReplace($pattern, $replacement, $subject, int $limit = -1, ?int &$count = null)
    {
        return preg_replace($pattern, $replacement, $subject, $limit, $count);
    }

    /**
     * Extract a slice of the array.
     *
     * @param string[] $array The input array.
     * @param int $offset If offset is non-negative, the sequence will start at that offset in the array.
     *                    If offset is negative, the sequence will start that far from the end of the array.
     * @param int|null $length If length is given and is positive, then the sequence will have up to that many elements in it.
     *                         If the array is shorter than the length, only the available array elements will be present.
     *                         If length is given and is negative then the sequence will stop that many elements from the end of the array.
     *                         If it is omitted, the sequence will have everything from offset up until the end of the array.
     * @param bool $preserveKeys Note that \Maginium\Framework\Support\Arr::slice() will reorder and reset the array indices by default.
     *                           You can change this behavior by setting preserve_keys to true.
     *
     * @return array Returns the slice.
     */
    public static function arraySlice(array $array, int $offset, ?int $length = null, bool $preserveKeys = false): array
    {
        return Arr::slice($array, $offset, $length, $preserveKeys);
    }

    /**
     * Checks if a value exists in an array.
     *
     * @param mixed $needle The searched value.
     * @param string[] $haystack The array.
     * @param bool $strict [optional] If set to TRUE, the in_array() function will also check the types of the needle in the haystack.
     *
     * @return bool Returns true if needle is found in the array, false otherwise.
     */
    public static function inArray($needle, array $haystack, bool $strict = false): bool
    {
        return in_array($needle, $haystack, $strict);
    }

    /**
     * Change the position of a specific array key to the desired position.
     *
     * @param string[] $array The input array.
     * @param string $keyName The key name to move.
     * @param int $position The desired position (starting from 0).
     *
     * @return array The array with the key moved to the desired position.
     */
    public static function changePosition(array $array, string $keyName, int $position = 999): array
    {
        if (! Arr::keyExists($keyName, $array)) {
            //  If the key does not exist in the array, return the array unchanged
            return $array;
        }

        //  Get the value of the key
        $value = $array[$keyName];

        //  Remove the key from the array
        unset($array[$keyName]);

        //  Calculate the correct position index based on 0-based index
        $actualPosition = $position - 1;

        //  Insert the key at the desired position
        $array = Arr::slice($array, 0, $actualPosition, true) +
            [$keyName => $value] +
            Arr::slice($array, $actualPosition, null, true);

        return $array;
    }

    /**
     * Generate a human-readable string representing the time elapsed since the specified date and time.
     *
     * @param string|null $datetime The date and time string to calculate the elapsed time from
     * @param int $level The level of granularity to include in the output (default is 7)
     *
     * @return string The human-readable time elapsed string
     */
    public static function timeElapsedString(?string $datetime, int $level = 7): string
    {
        if ($datetime === null) {
            return 'Never Seen';
        }

        //  Try to create a DateTime object from the provided datetime string
        try {
            $ago = Container::make(DateTime::class, ['datetime' => $datetime]);
        } catch (Exception $e) {
            return 'Invalid datetime format';
        }

        //  Create DateTime objects for the current date and time
        $now = new DateTime;
        $diff = $now->diff($ago);
        $full = false;

        //  Define the strings for each time unit
        $string = [
            'y' => 'year',
            'm' => 'month',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        //  Build the human-readable time elapsed string
        $elapsedString = [];

        foreach ($string as $key => $value) {
            $unitValue = $diff->{$key};

            if ($unitValue) {
                $elapsedString[] = $unitValue . ' ' . $value . ($unitValue > 1 ? 's' : '');
            }
        }

        //  Limit the granularity of the output
        if (! $full) {
            $elapsedString = Arr::slice($elapsedString, 0, $level);
        }

        //  Return the formatted time elapsed string
        return $elapsedString ? implode(', ', $elapsedString) . ' ago' : 'Never Seen';
    }

    /**
     * Recursively merge two arrays.
     *
     * @param array $array1 First array to merge.
     * @param array $array2 Second array to merge.
     *
     * @return array Merged array.
     */
    public static function recursiveArrayMerge(array $array1, array $array2): array
    {
        // Initialize merged array with the first array.
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            // Check if the current value from $array2 is an array.
            if (Validator::isArray($value)) {
                // If the key exists in the merged array and the corresponding value is also an array,
                // perform a recursive merge.
                if (Arr::keyExists($key, $merged) && Validator::isArray($merged[$key])) {
                    $merged[$key] = static::recursiveArrayMerge($merged[$key], $value);
                } else {
                    // If the key does not exist in the merged array or its value is not an array,
                    // simply assign the current value.
                    $merged[$key] = $value;
                }
            } else {
                // If the value is not an array, overwrite the value in the merged array.
                $merged[$key] = $value;
            }
        }

        // Return the merged array.
        return $merged;
    }

    /**
     * Gets the object variables of the given object.
     *
     * This static method returns the properties of the given object
     * as an associative array.
     *
     * @param object $object The object to retrieve properties from.
     *
     * @return array An associative array of the object's properties.
     */
    public static function getObjectVars(object $object): array
    {
        return get_object_vars($object);
    }

    /**
     * Merge two arrays, with the second array overriding the first.
     *
     * @param array $baseArray The base array.
     * @param array $overrideArray The array whose values will override the base array.
     *
     * @return array The merged array.
     */
    public static function mergeArrays(array $baseArray, array $overrideArray): array
    {
        return Arr::merge($baseArray, $overrideArray);
    }

    /**
     * Convert a given value into a boolean.
     *
     * This method handles various types of input and converts them to a boolean value.
     * - Returns `true` for truthy values (e.g., 1, 'true', 'yes', 'on', etc.).
     * - Returns `false` for falsy values (e.g., 0, 'false', 'no', 'off', etc.).
     * - Returns `false` for other unrecognized values.
     *
     * @param mixed $value The value to be converted.
     *
     * @return bool The corresponding boolean value.
     */
    public static function inBool($value): bool
    {
        // Handle string inputs with specific truthy or falsy values
        if (Validator::isString($value)) {
            $value = mb_strtolower(trim($value));

            // Consider various "truthy" and "falsy" values
            return in_array($value, ['1', 'true', 'yes', 'on'], true);
        }

        // Handle integer inputs directly
        if (Validator::isInt($value)) {
            return $value !== 0;
        }

        // Handle boolean input directly
        if (Validator::isBool($value)) {
            return $value;
        }

        // Handle other possible inputs like null, arrays, objects, etc.
        return (bool)$value; // Default fallback to PHP's standard type conversion
    }

    /**
     * Checks if the string has a minimum length.
     *
     * @param mixed $value The value to check.
     * @param int $minLength The minimum length.
     *
     * @return bool Returns true if the string meets the minimum length requirement, false otherwise.
     */
    public static function hasMinLength(mixed $value, int $minLength): bool
    {
        return Validator::isString($value) && mb_strlen($value) >= $minLength;
    }

    /**
     * Checks if the string has a maximum length.
     *
     * @param mixed $value The value to check.
     * @param int $maxLength The maximum length.
     *
     * @return bool Returns true if the string meets the maximum length requirement, false otherwise.
     */
    public static function hasMaxLength(mixed $value, int $maxLength): bool
    {
        return Validator::isString($value) && mb_strlen($value) <= $maxLength;
    }

    /**
     * Check if a class exists.
     *
     * @param string $class The class name to check.
     */
    public static function isClassExists(string $class): bool
    {
        if (! class_exists($class)) {
            //   NOTE: Attempt to add ::class suffix if it's not present
            return class_exists($class . '::class');
        }

        return true;
    }

    /**
     * Check if a function exists.
     *
     * @param string $functionName The function name to check.
     */
    public static function isFunctionExists(string $functionName): bool
    {
        return function_exists($functionName);
    }

    /**
     * Check if a method exists.
     *
     * @param object|string $object The object to check.
     * @param string $methodName The method name to check.
     */
    public static function isMethodExists($object, string $methodName): bool
    {
        return method_exists($object, $methodName);
    }

    /**
     * Check if the given value is callable.
     *
     * @param mixed $value The value to check.
     */
    public static function isCallable($value): bool
    {
        return is_callable($value);
    }

    /**
     * Format a string with placeholders dynamically replaced by provided values.
     *
     * This method supports placeholders like %1, %2 for positional replacements
     * or named placeholders like %name, %age.
     *
     * @param string $format The format string with placeholders.
     * @param mixed ...$arguments The values to replace in the format string, either indexed or associative.
     *
     * @return string The formatted string with placeholders replaced.
     */
    public static function sprintf(string $format, ...$arguments): string
    {
        // Convert arguments to an array if they are passed as variadic parameters
        $args = count($arguments) === 1 && Validator::isArray($arguments[0]) ? $arguments[0] : $arguments;

        // If arguments are provided, map them to placeholders and replace in the format string
        if ($args) {
            // Generate placeholder strings like %1, %2 or %name
            $placeholders = Arr::each([self::class, 'keyToPlaceholder'], Arr::keys($args));

            // Combine placeholders with corresponding values from arguments
            $pairs = Arr::combine($placeholders, Arr::values($args));

            // Use strtr to replace placeholders with the actual values
            $format = strtr($format, $pairs);
        }

        return $format;
    }

    /**
     * Deep merge two arrays recursively, merging nested arrays as well.
     *
     * @param array $array1 The first array to merge. This is the array that will be updated.
     * @param array $array2 The second array to merge. Its values will overwrite the values in the first array if they conflict.
     *
     * @return array The merged array, where the second array's values have been merged into the first array.
     */
    public static function deepMerge(array $array1, array $array2): array
    {
        // Iterate over each element in the second array.
        foreach ($array2 as $key => $value) {
            // If the value is an array and the same key exists in the first array and is also an array,
            // recursively merge the arrays to preserve nested structures.
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = static::deepMerge($array1[$key], $value);
            } else {
                // If the value is not an array, or the key doesn't exist in the first array, overwrite the value in the first array.
                $array1[$key] = $value;
            }
        }

        // Return the merged array after processing all keys and values.
        return $array1;
    }

    /**
     * Convert a key (either integer or string) to a placeholder.
     *
     * @param string|int $key The key from the arguments array.
     *
     * @return string The corresponding placeholder (e.g., %1, %foo).
     */
    private static function keyToPlaceholder($key): string
    {
        // If the key is an integer, convert it to a 1-based index (e.g., %1, %2)
        // Otherwise, use the string key as the placeholder (e.g., %foo, %bar)
        return '%' . (Validator::isInt($key) ? (string)($key + 1) : $key);
    }

    /**
     * Count all elements in an array or something in an object.
     *
     * @param mixed $variable The array or Countable object.
     *
     * @return int Returns the number of elements in $variable.
     */
    public static function count($variable): int
    {
        return count($variable);
    }
}
