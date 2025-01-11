<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\DSL\Exceptions;

use Magento\Framework\Phrase;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Support\Validator;

/**
 * Class ParameterException.
 *
 * Represents an exception thrown due to invalid or missing parameters in Elasticsearch DSL operations.
 * Extends the base Exception class to include additional contextual details for debugging purposes.
 */
class ParameterException extends Exception
{
    /**
     * @var array
     * Stores additional details about the exception, such as specific parameter errors or debug information.
     */
    private array $_details;

    /**
     * ParameterException constructor.
     *
     * @param Phrase $message The exception message, which could be a Phrase object or a string.
     * @param int $code The exception code, useful for categorizing different error types.
     * @param Exception|null $previous Optional. A previous exception for exception chaining.
     * @param array $details Optional. Additional context or details about the exception.
     */
    public function __construct(
        Phrase $message,
        int $code = 0,
        ?Exception $previous = null,
        array $details = [],
    ) {
        // Convert string message to Phrase if it's a string
        if (Validator::isString($message)) {
            $message = __($message);
        }

        // Call the parent constructor to initialize the base exception properties.
        parent::__construct($message, $code, $previous);

        // Store the additional details for later retrieval.
        $this->_details = $details;
    }

    /**
     * Retrieve the additional details associated with the exception.
     *
     * @return array Returns an array of details providing further context for the exception.
     */
    public function getDetails(): array
    {
        return $this->_details;
    }
}
