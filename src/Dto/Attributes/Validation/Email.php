<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsObject;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Validator;

/**
 * The `Email` attribute is a custom validation attribute that ensures a value is a valid email address
 * and is not disposable. It checks both the format of the email and whether the domain is on a blacklist
 * of disposable email services.
 *
 * This validation can be used to ensure that users provide legitimate email addresses, rejecting
 * temporary or throwaway email addresses commonly used for spam or short-term purposes.
 *
 * Example usage:
 *
 * #[Email]
 * public string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Email implements ValidatorInterface
{
    use AsObject;

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
        // Check if the provided value is a valid email format using filter_var.
        // The FILTER_VALIDATE_EMAIL filter returns true if the email has a valid structure.
        if (! Validator::isEmail($value)) {
            return ValidationResult::invalid('This field must be a valid email address.');
        }

        // If both checks pass, return a valid validation result.
        return ValidationResult::valid();
    }
}
