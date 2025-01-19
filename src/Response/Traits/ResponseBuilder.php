<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Traits;

use Illuminate\Support\Traits\ForwardsCalls;
use Magento\Framework\Phrase;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Response\Interfaces\Data\ResponseInterface;
use Maginium\Framework\Response\Interfaces\Data\ResponseInterfaceFactory;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Json;
use Throwable;

/**
 * Trait ResponseBuilder.
 *
 * This trait builds API responses, including success or failure responses with optional headers,
 * metadata, and error details. It can be used in controllers or services to return standardized responses.
 *
 * @mixin ResponseInterface
 *
 * @method self setMeta(array $meta) Sets the meta information for the response.
 * @method self setPayload(?array $data = null) Sets the data (payload) for the response.
 * @method self setMessage(string|Phrase $message = 'OK') Sets the response message.
 * @method self setStatusCode(int $statusCode) Sets the HTTP status code for the response.
 * @method self setCause(string|Exception|WebapiException|null $cause) Sets the error cause and adjusts the status code if necessary.
 * @method self setHeaders(array|string $headers) Sets custom headers for the response.
 * @method self setErrors(array $errors) Sets errors for the response.
 * @method array toArray(array $keys = ['*']) Converts the response object to an array, optionally filtering the array by keys.
 * @method string toJson(array $keys = []) Converts the response object to a JSON string, optionally filtering the array by keys.
 * @method string toString(string $format = '') Converts the response object to a string, formatted as specified.
 */
trait ResponseBuilder
{
    use ForwardsCalls;

    /**
     * @var ResponseInterface|null
     */
    protected ?ResponseInterface $response = null;

    /**
     * Create a success response with optional pagination metadata.
     *
     * This method builds a success response, optionally including pagination metadata like page number,
     * page size, and total count. It returns an instance of ResponseInterface with the success details.
     *
     * @return self Returns the current instance to support method chaining.
     */
    public function response(): self
    {
        // Create a new response instance using the factory
        $this->initializeResponse();

        // Rest the response instance
        $this->reset();

        // Return the current instance to support method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the meta information.
     *
     * This method allows setting the meta information for the response, which can include
     * pagination details or other metadata. It will validate the provided meta information
     * and, if valid, apply it to the response.
     *
     * @param array $meta The meta information to be included in the response.
     *
     * @return $this Returns the current instance to support method chaining.
     */
    public function setMeta(array $meta): self
    {
        // Create a new response instance using the factory
        $this->initializeResponse();

        // Format and set pagination meta (page number, page size, total count)
        $this->response->setMeta($meta);

        // Return the current instance to support method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the data (payload).
     *
     * This method sets the payload data that will be included in the response. If no data
     * is provided, the response will have an empty payload.
     *
     * @param array|null $data The data to be included in the response. If null, no data will be set.
     *
     * @return $this Returns the current instance to support method chaining.
     */
    public function setPayload(?array $data = null): self
    {
        // Create a new response instance using the factory
        $this->initializeResponse();

        // Set the provided payload to the response
        $this->response->setPayload($data);

        // Return the current instance to support method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the response message.
     *
     * This method sets a message for the response. If the message is an instance of the Phrase class,
     * it will render the message; otherwise, the provided string will be used directly.
     *
     * @param string|null $message The response message to be set. If null, no message will be set.
     *
     * @return $this Returns the current instance to support method chaining.
     */
    public function setMessage(string|Phrase $message = 'OK'): self
    {
        // Create a new response instance using the factory
        $this->initializeResponse();

        // Set the provided message to the response
        $this->response->setMessage($message instanceof Phrase ? $message->render() : $message);

        // Return the current instance to support method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the HTTP status code.
     *
     * This method sets the HTTP status code for the response. The status code should be a valid HTTP
     * status code (e.g., 200 for OK, 404 for Not Found, etc.).
     *
     * @param int $statusCode The HTTP status code to be set in the response.
     *
     * @return $this Returns the current instance to support method chaining.
     */
    public function setStatusCode(int $statusCode): self
    {
        // Create a new response instance using the factory
        $this->initializeResponse();

        // Set the provided status code to the response
        $this->response->setStatusCode($statusCode);

        // Return the current instance to support method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the error cause.
     *
     * This method sets an optional error object (Throwable) to be included in the response.
     * It can be used to provide more detailed error information when a response fails.
     *
     * @param  string|null  $cause The error.
     *
     * @return ResponseInterface
     */
    public function setCause(?string $cause): self
    {
        // Create a new response instance using the factory
        $this->initializeResponse();

        // Set the provided cause to the response
        $this->response->setCause($cause);

        // Return the current instance to support method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set response headers.
     *
     * This method allows setting custom headers in the response. The headers can be provided as
     * an array or string, depending on the requirement. This method should be called before
     * returning the response.
     *
     * @param array|string $headers The headers to be included in the response.
     *
     * @return $this Returns the current instance to support method chaining.
     */
    public function setHeaders($headers): self
    {
        // Create a new response instance using the factory
        $this->initializeResponse();

        // Set the provided headers in the response
        $this->response->setHeaders($headers);

        // Return the current instance to support method chaining

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
    public function setErrors(?array $errors): self
    {
        // Create a new response instance using the factory
        $this->initializeResponse();

        // Set the errors in the response (this could be an array of error messages or details)
        $this->response->setErrors($errors);

        // Return the current instance to support method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Convert the response object into an array, optionally filtering the array to include only specified keys.
     *
     * This method will extract various components of the response object (e.g., cause, errors, payload, headers,
     * status code, message) and assemble them into an array. The keys to be included in the returned array can
     * be specified via the `$keys` parameter.
     *
     * @param array $keys An array of keys to be included in the output. If empty or contains '*', all available
     *                    keys will be included.
     *
     * @return array The response data, potentially filtered to include only the specified keys.
     */
    public function toArray(array $keys = ['*'])
    {
        return $this->response->toArray($keys);
    }

    /**
     * Converts the response data to JSON format, optionally filtering to include only specified keys.
     *
     * @param array $keys An array of keys to include in the JSON output.
     *
     * @throws InvalidArgumentException If JSON encoding fails or if keys array is invalid.
     *
     * @return string JSON-encoded response data, filtered to the specified keys.
     */
    public function toJson(array $keys = ['*']): string
    {
        return $this->response->toJson($keys);
    }

    /**
     * Converts object data into a string formatted using the specified template.
     *
     * Each placeholder in the format string (e.g., `{{key}}`) will be replaced by its corresponding
     * value from the response data.
     *
     * @param string $format The template format string, with placeholders in the form `{{key}}`.
     *                       If empty, a default format is used.
     *
     * @return string The formatted string with placeholders replaced by response data values.
     */
    public function toString($format = ''): string
    {
        return $this->response->toString($format);
    }

    /**
     * Initialize the response object.
     *
     * This method ensures that the `$response` object is initialized if it hasn't been already.
     * If the response object is not set, it uses the `ResponseInterfaceFactory` from the container
     * to create a new instance of the `ResponseInterface` class.
     */
    private function initializeResponse(): void
    {
        // Check if the response object has not been initialized
        if (! $this->response) {
            // Resolve the ResponseInterfaceFactory from the container and create a new response instance
            $this->response = Container::resolve(ResponseInterfaceFactory::class)->create();
        }
    }

    /**
     * Reset the response object.
     *
     * This method resets all properties of the `$response` object to their default values.
     * It clears the payload, errors, metadata, message, status code, and headers to ensure
     * that the response object is in a clean state before using it again.
     */
    private function reset(): void
    {
        // Check if the response object is initialized
        if ($this->response) {
            $this->response->unsetData(); // Clear all object data
        }
    }

    /**
     * Make dynamic calls into the collection.
     *
     * This method forwards any method calls to the underlying `$response` object.
     * If the method exists in the response object, it is called with the provided parameters.
     * If the method is part of the static call handler, it delegates to that functionality.
     *
     * @param string $method The name of the method to call.
     * @param array $parameters The parameters to pass to the method.
     *
     * @return mixed The result of the method call, forwarded to the `$response` object.
     */
    public function __call($method, $parameters)
    {
        // Otherwise, forward the method call to the `$response` object
        return $this->forwardCallTo($this->response, $method, $parameters);
    }
}
