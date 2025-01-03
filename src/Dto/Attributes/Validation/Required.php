<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Validator;

/**
 * The `Required` attribute is a custom validation attribute that ensures a property value is not null or empty.
 *
 * This attribute validates that the value of a property is neither null nor an empty string. It uses a helper
 * method from the `Validator` class to check whether the value is a non-empty string. If the value is null or
 * an empty string, the validation fails.
 *
 * Example usage:
 *
 * #[Required]
 * public string $name;
 *
 * @example
 * class UserDto {
 *     #[Required]
 *     public string $name; // The value must be non-null and non-empty.
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Required implements ValidatorInterface
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
        // Check if the value is null or, if it's a string, whether it's empty (after trimming whitespace).
        if ($value === null || (Validator::isString($value) && trim($value) === '')) {
            // Return an invalid validation result with an appropriate error message if the value is invalid.
            return ValidationResult::invalid('This field is required.');
        }

        // If the value is neither null nor empty, return a valid validation result.
        return ValidationResult::valid();
    }
}
