<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Maginium\Foundation\Exceptions\InvalidArgumentException;

/**
 * Class CaseConverter.
 */
class CaseConverter
{
    /**
     * The case constant representing snake_case.
     */
    public const CASE_SNAKE = 'snake';

    /**
     * The case constant representing camelCase.
     */
    public const CASE_CAMEL = 'camel';

    /**
     * Constant for the metadata attribute.
     */
    public const METADATA = 'metadata';

    /**
     * Convert an array's keys to a given case (snake_case or camelCase).
     *
     * @param string $case The case to convert keys to (snake_case or camelCase)
     * @param mixed $data The data to convert keys of
     *
     * @throws InvalidArgumentException if the case is not supported
     *
     * @return mixed The data with keys converted to the specified case
     */
    public function convert(string $case, $data)
    {
        // Ensure the provided case is either snake or camel
        if (! in_array($case, [self::CASE_CAMEL, self::CASE_SNAKE])) {
            throw InvalidArgumentException::make(__('Case must be either snake or camel'));
        }

        // If the data is not an array or collection, return it as is
        if (! Validator::isArray($data) && ! $data instanceof Collection) {
            return $data;
        }

        // Convert the array or collection to a Collection instance for better manipulation
        $collection = collect($data);

        // Iterate over each key-value pair in the collection
        $data = $collection->mapWithKeys(function($value, $key) use ($case) {
            // Handle array with numeric indexes (indexed arrays)
            if (Validator::isArray($value) && ! $this->isMetadata($key)) {
                // If it's a subarray (with indexed keys), recursively convert its keys
                // Also handle arrays of objects like 'departure', 'destination'
                if (Arr::values($value) === $value) {
                    // Indexed array, apply conversion recursively on values
                    $value = Arr::map($value, fn($item) => $this->convert($case, $item));
                } else {
                    // Associative array, apply conversion recursively on keys
                    $value = $this->convert($case, $value);
                }
            }

            // Convert the key to the specified case (snake or camel)
            return [Str::{$case}($key) => $value];
        });

        return $data->toArray();
    }

    /**
     * Check if a key is metadata.
     *
     * @param mixed $key The key to check
     *
     * @return bool True if the key is metadata, false otherwise
     */
    private function isMetadata($key): bool
    {
        // Check if the key is "metadata"
        return (bool)($key === self::METADATA);
    }
}
