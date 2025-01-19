<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Models\Attributes\Response;

use Magento\Framework\Exception\AbstractAggregateException;
use Magento\Framework\Webapi\Exception as WebapiException;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Response\Interfaces\Data\ResponseInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Response as ResponseFacade;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use Throwable;

/**
 * Setter Trait for managing response data.
 *
 * This trait provides a set of setter methods that can be used to populate
 * different response attributes such as payload, meta information, status code,
 * message, errors, headers, etc. Each setter method ensures that the correct data
 * is stored in the corresponding attribute of the response.
 *
 * @method self setPayload(?array $payload = null) Sets the data payload for the response.
 * @method self setMeta(?array $meta = null) Sets the meta information for the response.
 * @method self setStatusCode(int $statusCode) Sets the HTTP status code for the response.
 * @method self setMessage(?string $message = null) Sets the response message.
 * @method self setCause(string|Exception|WebapiException|null $cause) Sets the cause (error or exception) for the response.
 * @method self setErrors(array $errors) Sets an array of errors for the response.
 * @method self setHeaders($headers) Sets the headers for the response, merging with existing headers.
 */
trait SetterAttributes
{
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
    public function setPayload(?array $payload = null): ResponseInterface
    {
        // Set the payload data
        $this->setData(ResponseInterface::PAYLOAD, $payload);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the response headers.
     *
     * This method sets the headers to be included in the response. Headers are key-value
     * pairs where each key represents the header name, and the corresponding value represents
     * the value for that header. This allows flexibility in defining various response headers,
     * such as content type, caching directives, pagination info, etc.
     *
     * @return ResponseInterface
     */
    public function setHeader(string $key, string $value): ResponseInterface
    {
        // Set the response headers
        $this->key(ResponseInterface::HEADERS)->setData($key, $value);

        // Return the current instance for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function setMeta(?array $meta = null): ResponseInterface
    {
        // Set the meta data
        $this->setData(ResponseInterface::META, $meta);

        // Return the current instance for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function setStatusCode(int $statusCode): ResponseInterface
    {
        // Set the status code data
        $this->setData(ResponseInterface::STATUS_CODE, $statusCode);

        // Return the current instance for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function setMessage(?string $message = null): ResponseInterface
    {
        // Set the message data
        $this->setData(ResponseInterface::MESSAGE, $message);

        // Return the current instance for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function setCause(string|Exception|WebapiException|null $cause): ResponseInterface
    {
        // If no cause is provided, derive it from the current exception
        if (! $cause) {
            $exception = $this->getException();

            if ($exception instanceof WebapiException) {
                $cause = $exception->getStackTrace();
            } elseif ($exception instanceof Exception) {
                $cause = $exception->getTraceAsString();
            } else {
                $cause = null;
            }
        }

        // Set the cause only if it's valid
        if (Validator::isEmpty($cause)) {
            if (Validator::isString($cause)) {
                $this->setData(ResponseInterface::CAUSE, $cause);
            } else {
                $this->setData(ResponseInterface::CAUSE, $cause);
            }
        }

        // Return the current instance for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function setException(?Throwable $exception): ResponseInterface
    {
        // Store the exception in the response data
        $this->setData(ResponseInterface::EXCEPTION, $exception);

        // Check if the exception is an instance of AbstractAggregateException
        if ($exception instanceof AbstractAggregateException) {
            $this->setData(ResponseInterface::ERRORS, $exception->getErrors());

            // Return the current instance to allow method chaining
            return $this;
        }

        // Dynamically check for a `getErrors` method in the exception
        if ($exception !== null && Reflection::methodExists($exception, 'getErrors')) {
            $errors = $exception->getErrors();

            // Ensure errors are only set if valid data is returned
            if (! empty($errors)) {
                $this->setData(ResponseInterface::ERRORS, $errors);
            }
        }

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function setErrors(?array $errors): ResponseInterface
    {
        // Set the errors data
        $this->setData(ResponseInterface::ERRORS, $errors);

        // Return the current instance for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

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
    public function setHeaders($headers): ResponseInterface
    {
        // Get the current headers
        $existingHeaders = $this->getHeaders();

        // Check if the provided headers are in an array format
        if (Validator::isArray($headers)) {
            // If headers are an array, merge them with the existing headers
            $mergedHeaders = Arr::merge($existingHeaders, $headers);
        } else {
            // If headers are a string, add them as a new entry
            $mergedHeaders = Arr::merge($existingHeaders, [$headers]);
        }

        // Set the merged headers as the new headers for the response
        $this->setData(ResponseInterface::HEADERS, $mergedHeaders);

        // Return the current instance for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the status code and headers for the response.
     *
     * @param array $headers The headers to include in the response.
     * @param int $statusCode The status code for the response.
     *
     * @return void
     */
    protected function setResponseHeaders(array $headers, int $statusCode): void
    {
        // Set the HTTP status code for the response
        $this->setStatusCode($statusCode);
        ResponseFacade::setStatusCode($statusCode);

        if (! Validator::isEmpty($headers)) {
            foreach ($headers as $key => $value) {
                // Set each header for the response
                $this->setHeader($key, $value);
                ResponseFacade::setHeader($key, $value);
            }
        }
    }
}
