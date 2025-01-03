<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Interfaces;

use Maginium\Framework\Dto\Validation\ValidationResult;

/**
 * Interface for classes that perform validation on a given value.
 *
 * The `Validator` interface defines the contract for any class that validates
 * a given value and returns a validation result. The validation result indicates
 * whether the value is valid or invalid based on the rules defined within the
 * implementing class.
 */
interface ValidatorInterface
{
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
    public function validate(mixed $value): ValidationResult;
}
