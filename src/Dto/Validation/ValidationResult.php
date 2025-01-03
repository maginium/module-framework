<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Validation;

/**
 * Class ValidationResult.
 *
 * Represents the result of a validation operation, containing a flag indicating
 * whether the validation was successful, and an optional message describing the
 * result of the validation.
 *
 * The class provides two static methods for easily creating valid and invalid results.
 */
class ValidationResult
{
    /**
     * @var bool Indicates if the validation was successful.
     */
    public bool $isValid;

    /**
     * @var string|null An optional message explaining the validation result.
     */
    public ?string $message;

    /**
     * Constructor for creating a new ValidationResult instance.
     *
     * @param bool $isValid The validation result (true for success, false for failure).
     * @param string|null $message An optional message describing the validation result.
     */
    public function __construct(
        bool $isValid,
        ?string $message = null,
    ) {
        $this->isValid = $isValid;
        $this->message = $message;
    }

    /**
     * Creates a valid validation result (isValid = true).
     *
     * This static method is a shorthand for creating a ValidationResult where
     * the validation has passed (isValid = true).
     *
     * @return self A new ValidationResult instance indicating a successful validation.
     */
    public static function valid(): self
    {
        return new self(
            isValid: true,  // Set the validation result to true (valid)
        );
    }

    /**
     * Creates an invalid validation result with a specific message.
     *
     * This static method is a shorthand for creating a ValidationResult where
     * the validation has failed (isValid = false) with a custom message.
     *
     * @param string $message A message describing the reason for failure.
     *
     * @return self A new ValidationResult instance indicating a failed validation with a message.
     */
    public static function invalid(string $message): self
    {
        return new self(
            isValid: false,   // Set the validation result to false (invalid)
            message: $message, // Provide the message describing the validation failure
        );
    }
}
