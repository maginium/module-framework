<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Traits;

use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Trait EnumComparison.
 *
 * Provides methods for comparing enum instances or values and checking if they exist in a given collection.
 * These methods allow for equality comparisons and membership tests between enum values or instances.
 */
trait EnumComparison
{
    /**
     * Check if the enum contains a specific key, case-insensitive.
     *
     * This method will check the key in both uppercase and lowercase, making the check case-insensitive.
     *
     * @param  string  $key  Key to check.
     *
     * @return bool True if the enum contains the key (case-insensitive), false otherwise.
     */
    public static function hasKey(string $key): bool
    {
        // Get all keys for the current enum class
        $keys = static::getKeys();

        // Normalize the keys to lowercase for case-insensitive comparison
        $lowercaseKeys = Arr::each(fn($k) => Str::lower($k), $keys);

        // Convert the provided key to lowercase for case-insensitive comparison
        $key = Str::lower($key);

        // Check if the normalized key exists in the list of keys
        return Validator::inArray($key, $lowercaseKeys, true);
    }

    /**
     * Check if the enum contains a specific value.
     *
     * @param  mixed  $value  Value to check.
     * @param  bool  $strict  Whether to perform strict comparison.
     *
     * @return bool True if the enum contains the value, false otherwise.
     */
    public static function hasValue(mixed $value, bool $strict = true): bool
    {
        // Get all valid values of the enum
        $validValues = static::getValues();

        if ($strict) {
            // Strict comparison of $value in $validValues
            return Php::inArray($value, $validValues, true);
        }

        // Loose comparison by converting values to strings
        return Php::inArray((string)$value, Arr::each('strval', $validValues), true);
    }

    /**
     * Check if this instance is equal to the given enum instance or value.
     *
     * This method compares the current instance's value with the provided enum instance or value.
     * It handles both direct value comparison and instance comparison if the provided value is an instance
     * of the same enum class.
     *
     * @param  mixed  $enumValue  Enum instance or value to compare with the current instance.
     *
     * @return bool True if the current instance is equal to the provided enum instance or value, false otherwise.
     */
    public function is(mixed $enumValue): bool
    {
        // Check if the provided value is an instance of the same enum class.
        if ($enumValue instanceof static) {
            // If it's an instance, compare the values of both instances.
            return $this->value === $enumValue->value;
        }

        // If it's not an instance, directly compare the value.
        return $this->value === $enumValue;
    }

    /**
     * Check if this instance is not equal to the given enum instance or value.
     *
     * This method negates the result of the `is()` method to check for inequality.
     * It returns true if the instances or values are not equal, and false otherwise.
     *
     * @param  mixed  $enumValue  Enum instance or value to compare with the current instance.
     *
     * @return bool True if the current instance is not equal to the provided enum instance or value, false otherwise.
     */
    public function isNot(mixed $enumValue): bool
    {
        // Use the is() method to check for equality and negate the result to check for inequality.
        return ! $this->is($enumValue);
    }

    /**
     * Check if a matching enum instance or value is in the given collection of values.
     *
     * This method iterates through the provided values and checks if the current instance
     * matches any value in the collection. It supports both enum instances and raw values for comparison.
     *
     * @param  iterable<mixed>  $values  A collection of enum instances or values to check against.
     *
     * @return bool True if a matching enum instance or value is found in the collection, false otherwise.
     */
    public function in(iterable $values): bool
    {
        // Loop through each value in the provided collection.
        foreach ($values as $value) {
            // Compare the current instance with each value in the collection.
            if ($this->is($value)) {
                // Return true as soon as a match is found.
                return true;
            }
        }

        // Return false if no match is found after checking all values.
        return false;
    }

    /**
     * Check if a matching enum instance or value is not in the given collection of values.
     *
     * This method iterates through the provided values and checks if the current instance
     * is NOT equal to any value in the collection. It returns true if no match is found,
     * and false if a match is found.
     *
     * @param  iterable<mixed>  $values  A collection of enum instances or values to check against.
     *
     * @return bool True if no matching enum instance or value is found in the collection, false otherwise.
     */
    public function notIn(iterable $values): bool
    {
        // Loop through each value in the provided collection.
        foreach ($values as $value) {
            // Compare the current instance with each value in the collection.
            if ($this->is($value)) {
                // Return false as soon as a match is found (since we are checking for "not in").
                return false;
            }
        }

        // Return true if no match is found after checking all values (meaning the instance is not in the collection).
        return true;
    }
}
