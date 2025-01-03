<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;

/**
 * The `Url` attribute is a custom validation attribute that ensures a property value is a valid URL.
 *
 * This attribute validates that the value of a property matches a valid URL format, ensuring that the
 * input can be used as a legitimate web address (e.g., https://www.example.com).
 *
 * The URL format is validated using PHP's built-in `filter_var()` function with the `FILTER_VALIDATE_URL`
 * flag, which checks that the string is a valid URL.
 *
 * Example usage:
 *
 * #[Url]
 * public string $websiteUrl;
 *
 * @example
 * class UserDto {
 *     #[Url]
 *     public string $websiteUrl; // The value must be a valid URL, e.g., https://example.com
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Url implements ValidatorInterface
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
        // Check if the value is a valid URL using PHP's filter_var function with FILTER_VALIDATE_URL.
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            // Return an invalid validation result with an appropriate error message.
            return ValidationResult::invalid('This field must be a valid URL.');
        }

        // If the value is a valid URL, return a valid validation result.
        return ValidationResult::valid();
    }
}
