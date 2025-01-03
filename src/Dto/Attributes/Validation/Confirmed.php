<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Facades\Request;

/**
 * The `Confirmed` attribute is a custom validation attribute that ensures two values are the same.
 * Typically used for confirming that a user has entered matching values in fields such as passwords and password confirmations.
 *
 * Example usage:
 *
 * #[Confirmed('password_confirmation')]
 * public string $password;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Confirmed implements ValidatorInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The name of the confirmation field.
     * This is the field that will be compared with the original field.
     *
     * @var string
     */
    private string $confirmationField;

    /**
     * Constructor to initialize the Confirmed validation attribute.
     *
     * The constructor accepts the name of the confirmation field (e.g., 'password_confirmation') and assigns it to
     * the `$confirmationField` property.
     *
     * @param string $confirmationField The name of the confirmation field.
     */
    public function __construct(string $confirmationField)
    {
        $this->confirmationField = $confirmationField;
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
        // Fetch the confirmation value from the request using the confirmation field's name.
        $confirmationValue = Request::input($this->confirmationField);

        // Check if the confirmation value is present.
        // If the confirmation value is missing, return an invalid result.
        if ($confirmationValue === null) {
            return ValidationResult::invalid('Confirmation field is missing.');
        }

        // Check if the values match. If they don't, return an invalid result.
        if ($value !== $confirmationValue) {
            return ValidationResult::invalid('The values do not match.');
        }

        // If both values match, return a valid result.
        return ValidationResult::valid();
    }
}
