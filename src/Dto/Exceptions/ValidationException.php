<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Exceptions;

use Exception;
use Maginium\Foundation\Enums\HttpStatusCodes;
use Maginium\Framework\Dto\DataTransferObject;
use Maginium\Framework\Dto\Validation\ValidationResult;

/**
 * Exception thrown when validation fails on a DataTransferObject.
 *
 * This exception is thrown when the validation of a `DataTransferObject` fails.
 * It contains the `DataTransferObject` instance and a list of validation errors that occurred during validation.
 */
class ValidationException extends Exception
{
    /**
     * Factory method to create a new instance of the `ValidationException`.
     *
     * This method generates a detailed error message listing all the validation errors for the respective fields
     * of the provided `DataTransferObject`. The errors are passed as an array of validation results.
     *
     * @param DataTransferObject $dataTransferObject The DTO instance that failed validation.
     * @param array $validationErrors The validation errors, with field names as keys and error messages as values.
     *
     * @return self A new instance of the `ValidationException`.
     */
    public static function make(
        DataTransferObject $dataTransferObject,
        array $validationErrors,
    ): self {
        // Get the class name of the DataTransferObject
        $className = $dataTransferObject::class;

        // Array to collect all validation error messages
        $messages = [];

        // Iterate through each field's validation errors
        foreach ($validationErrors as $fieldName => $errorsForField) {
            /** @var ValidationResult $errorForField */
            foreach ($errorsForField as $errorForField) {
                // Collect error message for each field and append it to the messages array
                $messages[] = "`{$fieldName}`: {$errorForField->message}";
            }
        }

        // Create and return a new instance of the exception with the detailed validation error message
        return new self(
            __('Validation errors: %1', implode(PHP_EOL, $messages))->render(),
            HttpStatusCodes::BAD_REQUEST,
        );
    }
}
