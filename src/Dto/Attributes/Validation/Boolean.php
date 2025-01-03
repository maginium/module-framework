<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Validator;

/**
 * The `Boolean` attribute is a custom validation attribute used to ensure that a field's value is a boolean.
 *
 * This attribute can be applied to any property that requires a boolean value (i.e., `true` or `false`).
 * It will check if the provided value is a valid boolean type, returning a validation result accordingly.
 *
 * Example usage:
 *
 * #[Boolean]
 * public bool $isActive;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Boolean implements ValidatorInterface
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
        // Check if the value is a boolean using the Validator utility.
        if (! Validator::isBool($value)) {
            // If it's not a boolean, return an invalid validation result with a relevant error message.
            return ValidationResult::invalid('This field must be a boolean value.');
        }

        // If the value is a boolean, return a valid validation result.
        return ValidationResult::valid();
    }
}
