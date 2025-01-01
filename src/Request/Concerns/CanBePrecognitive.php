<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Concerns;

use Maginium\Framework\Support\Collection;

/**
 * Trait CanBePrecognitive.
 *
 * Provides functionality for handling precognitive requests.
 * Precognitive requests allow partial rule validation and
 * the identification of request intent.
 */
trait CanBePrecognitive
{
    /**
     * Filters the given array of rules based on the `Precognition-Validate-Only` header.
     *
     * This method checks if the `Precognition-Validate-Only` header is present and filters
     * the provided validation rules accordingly. If the header is absent, the original rules are returned.
     *
     * @param array<string, mixed> $rules The array of validation rules to filter.
     *
     * @return array<string, mixed> The filtered array of rules.
     */
    public function filterPrecognitiveRules(array $rules): array
    {
        // Check if the "Precognition-Validate-Only" header exists; return all rules if absent.
        if (! $this->headers->has('Precognition-Validate-Only')) {
            return $rules;
        }

        // Extract the rule keys from the header and filter the rules accordingly.
        return Collection::make($rules)
            ->only(explode(',', $this->header('Precognition-Validate-Only'))) // Keep only specified keys.
            ->all(); // Convert the collection back to an array.
    }

    /**
     * Determines if the request is attempting to be precognitive.
     *
     * A request is considered to be "attempting precognition" if it includes a `Precognition` header
     * explicitly set to "true".
     *
     * @return bool True if the request is attempting precognition, false otherwise.
     */
    public function isAttemptingPrecognition(): bool
    {
        // Check if the "Precognition" header is set to "true".
        return $this->header('Precognition') === 'true';
    }

    /**
     * Determines if the request is precognitive.
     *
     * This method checks if the `precognitive` attribute is set in the request's attributes.
     * The attribute is typically set during request processing to identify precognitive requests.
     *
     * @return bool True if the request is precognitive, false otherwise.
     */
    public function isPrecognitive(): bool
    {
        // Retrieve the "precognitive" attribute from the request's attributes; default to false.
        return $this->attributes->get('precognitive', false);
    }
}
