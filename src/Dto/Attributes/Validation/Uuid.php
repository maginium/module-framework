<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Php;

/**
 * The `Uuid` attribute is used to validate that a property value is a valid UUID (Universally Unique Identifier).
 *
 * This custom validation attribute ensures that the value of a property adheres to the standard UUID format
 * (e.g., 123e4567-e89b-12d3-a456-426614174000). UUIDs are often used to uniquely identify models across different
 * systems or platforms, and this validation ensures that the value provided is correctly formatted.
 *
 * Example usage:
 *
 * #[Uuid]
 * public string $userId;
 *
 * @example
 * class UserDto {
 *     #[Uuid]
 *     public string $userId; // The value must be a valid UUID.
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Uuid implements ValidatorInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * Validate the given value.
     *
     * This method should be implemented to perform the necessary validation checks
     * on the provided value. It should return a `ValidationResult` object that
     * indicates whether the value is valid or invalid.
     *
     * @param mixed $value The value to validate. The type can vary depending on the
     *                     implementation of the validator.
     *
     * @return ValidationResult The result of the validation, indicating whether
     *                          the value is valid or invalid.
     */
    public function validate(mixed $value): ValidationResult
    {
        // Check if the value matches the UUID format (e.g., 123e4567-e89b-12d3-a456-426614174000).
        if (! Php::pregMatch('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/', (string)$value)) {
            // Return invalid result with a message if the value does not match the UUID format.
            return ValidationResult::invalid('This field must be a valid UUID.');
        }

        // Return a valid result if the value matches the UUID format.
        return ValidationResult::valid();
    }
}
