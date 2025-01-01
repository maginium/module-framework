<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Models\Attributes\Response;

use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Framework\Response\Helpers\StackTraceFormatter;
use Maginium\Framework\Response\Interfaces\Data\ResponseInterface;
use Maginium\Framework\Support\Validator;
use Throwable;

/**
 * Getter Trait for managing response data retrieval.
 *
 * This trait provides a set of getter methods that allow retrieval of different response attributes
 * such as the payload, status code, headers, errors, message, and more. Each method fetches data
 * from the response object, providing flexibility in accessing various response components.
 *
 * @method Throwable|bool getCause() Retrieves the cause (error or exception) of the response, or false if not set.
 * @method ?array getErrors() Retrieves the errors associated with the response, or null if not set.
 * @method ?array getPayload() Retrieves the data payload of the response, or null if not set.
 * @method array getHeaders() Retrieves the headers associated with the response.
 * @method int getStatusCode() Retrieves the HTTP status code for the response.
 * @method string getMessage() Retrieves the response message.
 * @method ?array getMeta() Retrieves the meta information for the response, or null if not set.
 */
trait GetterAttributes
{
    /**
     * Get the error.
     *
     * This method retrieves the error (Throwable) that may have been set in the response.
     * If no error is set, it returns false.
     *
     * @return Throwable|bool The error object or false if not set.
     */
    public function getException(): Throwable|bool
    {
        // Get the error data from the response; return false if no error is set.
        return $this->getData(ResponseInterface::EXCEPTION) ?? false;
    }

    /**
     * Get the error cause.
     *
     * This method retrieves the error (Throwable) that may have been set in the response.
     * If no error is set, it returns null.
     *
     * @return array|null The formatted error cause as an array, or null if not set.
     */
    public function getCause(): ?array
    {
        // Retrieve the error data from the response
        $cause = $this->getData(ResponseInterface::CAUSE);

        // Return null if no error cause is set
        if ($cause === null) {
            return null;
        }

        // Split the cause into an array using newlines as delimiters
        $causeArray = explode("\n", $cause);

        // Format and return the stack trace array
        return StackTraceFormatter::formatStackTrace($causeArray);
    }

    /**
     * Get the errors.
     *
     * This method retrieves the array of errors set in the response, typically used for
     * validation or other error-related information. If no errors are set, it returns null.
     *
     * @return array|null The errors array or null if not set.
     */
    public function getErrors(): ?array
    {
        // Get the errors data from the response; return null if no errors are set.
        return $this->getData(ResponseInterface::ERRORS);
    }

    /**
     * Get the data payload.
     *
     * This method retrieves the payload (main data) set in the response. If no payload is set,
     * it returns null.
     *
     * @return array|null The data payload or null if not set.
     */
    public function getPayload(): ?array
    {
        // Get the payload data from the response; return null if no payload is set.
        return $this->getData(ResponseInterface::PAYLOAD);
    }

    /**
     * Get the headers.
     *
     * This method retrieves the headers set in the response. If no headers are set,
     * it returns an empty array.
     *
     * @return array The headers, or an empty array if no headers are set.
     */
    public function getHeaders(): array
    {
        // Get the headers data from the response; return an empty array if no headers are set.
        return $this->getData(ResponseInterface::HEADERS) ?? [];
    }

    /**
     * Get the HTTP status code.
     *
     * This method retrieves the HTTP status code set in the response. If no status code is set,
     * it returns a default status code of 200 (OK).
     *
     * @return int The HTTP status code, or 200 if not set.
     */
    public function getStatusCode(): int
    {
        // Get the status code data from the response; return a default status code of 200 (OK) if not set.
        return $this->getData(ResponseInterface::STATUS_CODE) ?? HttpStatusCode::OK;
    }

    /**
     * Get the response message.
     *
     * This method retrieves the message set in the response. The message typically provides
     * a human-readable description of the response. If no message is set, it returns an empty string.
     *
     * @return string The response message, or an empty string if not set.
     */
    public function getMessage(): string
    {
        // Get the message data from the response; return an empty string if no message is set.
        return $this->getData(ResponseInterface::MESSAGE) ?? '';
    }

    /**
     * Get the meta information.
     *
     * This method retrieves the meta information set in the response. Meta information can include
     * additional context such as pagination, filtering details, etc. If no meta information is set,
     * it returns null.
     *
     * @return array|null The meta information or null if not set.
     */
    public function getMeta(): ?array
    {
        // Get the meta data from the response; return null if no meta is set.
        return $this->getData(ResponseInterface::META);
    }

    /**
     * Determine the message to include in the response.
     *
     * @param Throwable|null $cause The cause of the response, if any.
     *
     * @return string The response message.
     */
    protected function getResponseMessage($cause): string
    {
        if ($cause instanceof Throwable) {
            // Return the exception message if a Throwable is provided
            return $cause->getMessage();
        }

        return ! Validator::isEmpty($this->getMessage()) ? $this->getMessage() : __('Request executed successfully')->render();
    }
}
