<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Facades\Date as DateFacade;

/**
 * The `Date` attribute is a custom validation attribute that ensures the value is a valid date.
 * It checks that the value can be parsed into a valid date and is formatted correctly. This validation
 * ensures that users provide a properly formatted date, allowing both date-only and datetime formats.
 *
 * Example usage:
 *
 * #[Date]
 * public string $date;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Date implements ValidatorInterface
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
        // Try to parse the value using Carbon, via the DateFacade.
        // If parsing fails, an exception is thrown and caught to return an invalid result.
        try {
            $date = DateFacade::parse($value);
        } catch (Exception $e) {
            // If parsing fails, return an invalid result indicating the value is not a valid date.
            return ValidationResult::invalid('This field must be a valid date.');
        }

        // Check if the parsed date matches the expected format of 'Y-m-d' or 'Y-m-d H:i:s'.
        // If the value does not match the parsed format, it is considered invalid.
        if ($date->format('Y-m-d H:i:s') !== $value && $date->format('Y-m-d') !== $value) {
            return ValidationResult::invalid('This field must be a valid date in the format Y-m-d or Y-m-d H:i:s.');
        }

        // If the value passes both the parsing and format checks, return a valid result.
        return ValidationResult::valid();
    }
}
