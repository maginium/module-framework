<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Validator;

/**
 * The `Text` attribute is a custom validation attribute that ensures a property value is a string.
 *
 * This attribute validates that the value of a property is of type string. It uses a custom `Validator`
 * class method `isString` to perform the validation. If the value is not a string, the validation fails.
 *
 * Example usage:
 *
 * #[Text]
 * public string $name;
 *
 * @example
 * class UserDto {
 *     #[Text]
 *     public string $name; // The value must be a string, e.g., "John Doe"
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Text implements ValidatorInterface
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
        // Use the custom Validator class to check if the value is a string.
        if (! Validator::isString($value)) {
            // Return an invalid validation result with an appropriate error message.
            return ValidationResult::invalid('This field must be a string.');
        }

        // If the value is a string, return a valid validation result.
        return ValidationResult::valid();
    }
}
