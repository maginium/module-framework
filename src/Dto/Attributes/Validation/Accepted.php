<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Validator;

/**
 * The `Accepted` attribute is used to validate that a property value is one of the accepted values.
 *
 * This custom validation attribute is typically used on Data Transfer Object (DTO) properties
 * to ensure that their values are from a predefined set of acceptable values. If the value is not
 * one of the accepted values, a validation error is returned. This is useful in scenarios where
 * only specific, predefined values are allowed for a property.
 *
 * The `Accepted` attribute is repeatable, meaning it can be applied multiple times to the same property
 * to validate against different sets of acceptable values.
 *
 * Example usage:
 *
 * #[Accepted(['yes', 'no'])]
 * public string $status;
 *
 * @example
 * class UserDto {
 *     #[Accepted(['admin', 'user'])]
 *     public string $role; // Only 'admin' or 'user' are accepted.
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Accepted implements ValidatorInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * List of accepted values for validation.
     *
     * @var array The accepted values for the property.
     */
    private array $acceptedValues;

    /**
     * Constructor for the Accepted attribute.
     *
     * Accepts a list of values that are considered valid for the property. This can be a single value
     * or an array of values. The values are stored internally for use in validation.
     *
     * @param string|array $values A single value or an array of accepted values.
     */
    public function __construct(string|array $values)
    {
        // Ensure that the values are always stored as an array.
        $this->acceptedValues = is_array($values) ? $values : [$values];
    }

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
        // Check if the value is in the list of accepted values (case-sensitive).
        if (! Validator::inArray($value, $this->acceptedValues, true)) {
            return ValidationResult::invalid('This value is not accepted.');
        }

        // If the value is valid, return a successful validation result.
        return ValidationResult::valid();
    }
}
