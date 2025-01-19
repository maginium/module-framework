<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Maginium\Framework\Support\Validator;

/**
 * Trait HasAttributes.
 *
 * Provides functionality to retrieve specific attributes or a subset of attributes from a model's data.
 * This trait includes methods to filter and return only the specified attributes, or return all attributes
 * if no specific ones are requested, or a wildcard ('*') is used.
 *
 * This trait can be used by models that need dynamic access to their underlying data while providing
 * a flexible way to request either all attributes or a defined subset.
 */
trait HasAttributes
{
    /**
     * Get a subset of the model's attributes.
     *
     * This method allows retrieving either all attributes or a specific subset of attributes
     * based on the provided list.
     *
     * @param  array|string|null  $attributes  The attributes to retrieve. If null or '*' is passed, returns all attributes.
     *
     * @return array  The subset of attributes or all attributes.
     */
    public function only($attributes = null): array
    {
        // Retrieve the model's data as a collection.
        $data = collect($this->getData());

        // If no attributes are provided, or '*' is passed, return all attributes.
        if (Validator::isEmpty($attributes) || $this->isWildcard($attributes)) {
            return $data->toArray();
        }

        // Ensure the attributes are provided as an array to handle multiple attributes correctly.
        $attributes = (array)$attributes;

        // Filter and return only the specified attributes.
        return $data->only($attributes)->toArray();
    }

    /**
     * Determine if the provided attributes are a wildcard ('*').
     *
     * @param  mixed  $attributes
     *
     * @return bool
     */
    protected function isWildcard($attributes): bool
    {
        return Validator::inArray('*', (array)$attributes, true);
    }
}
