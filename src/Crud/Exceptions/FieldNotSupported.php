<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use InvalidArgumentException;

/**
 * Class FieldNotSupported.
 *
 * Custom exception to be thrown when a field is not supported for a given model.
 *
 * This exception is used to handle cases where a field provided in a request or operation
 * is not recognized as a valid or supported field for a specified model. The exception message
 * includes details about the unsupported field, the model it was applied to, and the list of
 * supported fields for that model.
 */
class FieldNotSupported extends InvalidArgumentException
{
    /**
     * Factory method to create a new instance of the exception.
     *
     * This method generates an exception with a detailed message that specifies the unsupported field,
     * the model it was attempted on, and the list of supported fields.
     *
     * @param string $field The unsupported field.
     * @param string $model The name of the model that the field was applied to.
     * @param array $supported An array of supported fields for the model.
     *
     * @return FieldNotSupported The created exception instance.
     */
    public static function make(string $field, string $model, array $supported)
    {
        // Construct the exception message that details the unsupported field and the available supported fields.
        return new static(
            "The field '{$field}' is not supported for model {$model}. Supported fields: " . implode(', ', $supported),
        );
    }
}
