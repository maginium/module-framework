<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Interfaces\Data;

use Exception;
use Magento\Framework\Webapi\Exception as WebapiException;
use Maginium\Foundation\Interfaces\DataObjectInterface;
use Throwable;

/**
 * Interface ResponseInterface.
 */
interface ResponseInterface extends DataObjectInterface
{
    /**
     * Key for accessing the response.
     */
    public const RESPONSE = 'response';

    /**
     * Key for accessing the data in the response.
     */
    public const DATA = 'data';

    /**
     * Key for accessing the payload in the response.
     */
    public const PAYLOAD = 'payload';

    /**
     * Key for accessing the meta information in the response.
     */
    public const META = 'meta';

    /**
     * Key for accessing the HTTP status code in the response.
     */
    public const STATUS_CODE = 'status_code';

    /**
     * Key for accessing the response message in the response.
     */
    public const MESSAGE = 'message';

    /**
     * Key for accessing the headers in the response.
     */
    public const HEADERS = 'headers';

    /**
     * Key for accessing the error cause in the response.
     */
    public const CAUSE = 'cause';

    /**
     * Key for accessing the error trace in the response.
     */
    public const TRACE = 'trace';

    /**
     * Key for accessing the exception in the response.
     */
    public const EXCEPTION = 'exception';

    /**
     * Key for accessing the error in the response.
     */
    public const ERROR = 'error';

    /**
     * Key for accessing the errors in the response.
     */
    public const ERRORS = 'errors';

    /**
     * Represents the page number in pagination.
     */
    public const PAGE_KEY = 'page';

    /**
     * Represents the page size in pagination.
     */
    public const PER_PAGE_KEY = 'size';

    /**
     * Represents the total count of items in pagination.
     */
    public const TOTAL_COUNT_KEY = 'total_count';

    /**
     * Key for accessing the timestamp in the response.
     */
    public const TIMESTAMP = 'timestamp';

    /**
     * Key for accessing the request_id in the response.
     */
    public const REQUEST_ID = 'request_id';

    /**
     * Get the error.
     *
     * This method retrieves the error (Throwable) that may have been set in the response.
     * If no error is set, it returns false.
     *
     * @return array|bool The error object or false if not set.
     */
    public function getCause(): ?array;

    /**
     * Get the errors.
     *
     * This method retrieves the array of errors set in the response, typically used for
     * validation or other error-related information. If no errors are set, it returns null.
     *
     * @return array|null The errors array or null if not set.
     */
    public function getErrors(): ?array;

    /**
     * Get the data payload.
     *
     * This method retrieves the payload (main data) set in the response. If no payload is set,
     * it returns null.
     *
     * @return array|null The data payload or null if not set.
     */
    public function getPayload(): ?array;

    /**
     * Get the headers.
     *
     * This method retrieves the headers set in the response. If no headers are set,
     * it returns an empty array.
     *
     * @return array The headers, or an empty array if no headers are set.
     */
    public function getHeaders(): array;

    /**
     * Get the HTTP status code.
     *
     * This method retrieves the HTTP status code set in the response. If no status code is set,
     * it returns a default status code of 200 (OK).
     *
     * @return int The HTTP status code, or 200 if not set.
     */
    public function getStatusCode(): int;

    /**
     * Get the response message.
     *
     * This method retrieves the message set in the response. The message typically provides
     * a human-readable description of the response. If no message is set, it returns an empty string.
     *
     * @return string The response message, or an empty string if not set.
     */
    public function getMessage(): string;

    /**
     * Get the meta information.
     *
     * This method retrieves the meta information set in the response. Meta information can include
     * additional context such as pagination, filtering details, etc. If no meta information is set,
     * it returns null.
     *
     * @return array|null The meta information or null if not set.
     */
    public function getMeta(): ?array;

    /**
     * Set the data payload.
     *
     * This method sets the payload (main data) to be included in the response.
     * It allows for a nullable array, enabling flexibility when no data needs to
     * be included.
     *
     * @param  array|null  $payload The data to be included in the response.
     *
     * @return ResponseInterface
     */
    public function setPayload(?array $payload = null): self;

    /**
     * Set the meta information.
     *
     * This method sets the meta information to be included in the response.
     * Meta information is often used for additional context, such as pagination data
     * or other information related to the payload.
     *
     * @param  array|null  $meta The meta information.
     *
     * @return ResponseInterface
     */
    public function setMeta(?array $meta = null): self;

    /**
     * Set the HTTP status code.
     *
     * This method sets the status code for the response. If the provided status code
     * is invalid or outside the success range, it defaults to an internal server error
     * (HTTP 500).
     *
     * @param  int  $statusCode The HTTP status code.
     *
     * @return ResponseInterface
     */
    public function setStatusCode(int $statusCode): self;

    /**
     * Set the response message.
     *
     * This method sets the response message, which can be used to convey additional
     * information about the response, such as success or error details.
     *
     * @param  string|null  $message The response message.
     *
     * @return ResponseInterface
     */
    public function setMessage(?string $message = null): self;

    /**
     * Set the exception for the response.
     *
     * This method assigns an optional Throwable instance to the response, allowing detailed
     * error information to be included. If the exception provides additional error data
     * (via `AbstractAggregateException` or a custom `getErrors` method), it is extracted
     * and added to the response.
     *
     * @param  Throwable|null  $exception The exception to be included in the response, if any.
     *
     * @return ResponseInterface Returns the current instance for method chaining.
     */
    public function setException(?Throwable $exception): self;

    /**
     * Set the error cause.
     *
     * This method sets an optional error object (Throwable) to be included in the response.
     * It can be used to provide more detailed error information when a response fails.
     *
     * @param  string|Exception|WebapiException|null  $cause The error cause.
     *
     * @return ResponseInterface
     */
    public function setCause(string|Exception|WebapiException|null $cause): self;

    /**
     * Set the errors.
     *
     * This method sets an array of errors to be included in the response. It is useful
     * for providing multiple error details, such as validation errors or other issues.
     *
     * @param  array|null  $errors The errors to be included in the response.
     *
     * @return ResponseInterface
     */
    public function setErrors(?array $errors): self;

    /**
     * Set the headers.
     *
     * This method sets the headers for the response. If headers are provided as an array,
     * they are merged with any existing headers. If they are provided as a string, they are
     * added as a new entry to the existing headers.
     *
     * @param  array|string  $headers The headers to be included in the response.
     *
     * @return ResponseInterface
     */
    public function setHeaders($headers): self;
}
