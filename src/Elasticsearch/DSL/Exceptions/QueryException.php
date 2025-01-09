<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\DSL\Exceptions;

use Magento\Framework\Phrase;
use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Foundation\Exceptions\LocalizedException;
use Throwable;

/**
 * Class CouldNotDeleteException.
 *
 * Represents a CouldNotDeleteException error, indicating that an model could not be deleted from the database.
 */
class QueryException extends LocalizedException
{
    /**
     * The type of the error (QueryException).
     *
     * @var string
     */
    protected string $type = 'QueryException';

    /**
     * @var array
     * Stores additional details about the exception, such as error context or specific query parameters.
     */
    private array $_details;

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @param Phrase $message The error message encapsulated in a Phrase object.
     * @param Throwable|null $cause The original exception that caused this exception (optional).
     * @param int|null $statusCode The status code for the error (optional).
     * @param string|int|null $code The error code associated with the exception (optional).
     * @param string[]|null $context Additional context or data related to the exception (optional).
     * @param array $details Optional. An array of additional details providing context for the exception.
     */
    public function __construct(
        Phrase $message,
        ?Throwable $cause = null,
        ?int $statusCode = null,
        string|int|null $code = null,
        ?array $context = null,
        array $details = [],
    ) {
        $this->_details = $details;

        // Call the parent constructor with necessary parameters
        parent::__construct(
            $message, // The error message
            $cause,   // The cause of the error
            $statusCode ?? HttpStatusCode::INTERNAL_SERVER_ERROR, // Default to 500 if no statusCode provided
            $code,    // The error code
            $context,   // Additional context
        );
    }

    /**
     * Creates a new array representing a solution.
     *
     * @return array An associative array containing the solution details.
     */
    public function solution(): array
    {
        return [
            'title' => $this->type,
            'description' => $this->message,
            'links' => [
                'More Info' => 'https://docs.,aginium.com/errors/' . $this->type, // Example link
            ],
        ];
    }

    /**
     * Retrieve additional details associated with the exception.
     *
     * @return array An array of details providing further context or debugging information.
     */
    public function getDetails(): array
    {
        return $this->_details;
    }
}
