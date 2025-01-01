<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Models;

use InvalidArgumentException;
use Maginium\Framework\Database\ObjectModel;
use Maginium\Framework\Response\Helpers\Data as ResponseHelper;
use Maginium\Framework\Response\Interfaces\Data\ResponseInterface;
use Maginium\Framework\Response\Models\Attributes\ResponseAttributes;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Override;
use Throwable;

/**
 * Class Response.
 *
 * Data Transfer Object representing a response with optional meta information.
 *
 * @method self fill(array $data) Initializes the response object from an array of data.
 * @method array toArray(array $keys = []) Converts the response object to an array with specified keys.
 * @method string toJson(int $options = 0) Converts the response object to its JSON representation.
 * @method string __toString() Returns a string representation of the response object (via JSON).
 */
class Response extends ObjectModel implements ResponseInterface
{
    use ResponseAttributes;

    /**
     * Update or initialize the model with data from an array.
     *
     * @return $this
     */
    public function fill(array $data): self
    {
        // Assuming that keys in $data match your property names
        foreach ($data as $key => $value) {
            // Use setter methods if available or set data directly
            $setterMethod = 'set' . Str::replace('_', '', ucwords($key, '_'));

            if (Reflection::methodExists($this, $setterMethod)) {
                $this->{$setterMethod}($value);
            } else {
                $this->setData($key, $value);
            }
        }

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
    #[Override]
    public function toArray(array $keys = ['*']): array
    {
        // Get the cause (Throwable) if any, indicating an error or exception
        $exception = $this->getException();

        // Get the cause (Throwable) if any, indicating an error or exception
        $cause = $this->getCause();

        // Get any errors that may have occurred during the request
        $errors = $this->getErrors();

        // Get the main response data (payload)
        $payload = $this->getPayload() ?? [];

        // Get headers associated with the response
        $headers = $this->getHeaders();

        // Get the HTTP status code associated with the response
        $statusCode = $this->getStatusCode();

        // Retrieve the application's debugging status (whether debugging is enabled)
        $isAppDebugging = Config::getBool('APP_DEBUG');

        // Initialize the message with a default success message
        $message = $this->getResponseMessage($cause);

        // Start building the base response array
        $this->setData(static::RESPONSE, [
            static::STATUS_CODE => $statusCode,
            static::MESSAGE => $message,
            ResponseInterface::TIMESTAMP => ResponseHelper::getTimestamp(),
            ResponseInterface::REQUEST_ID => ResponseHelper::getRequestId(),
        ]);

        // Set the HTTP status code and headers in the response
        $this->setResponseHeaders($headers, $statusCode);

        // If debugging is disabled, just add the payload as the data response
        // Add the response payload (data) to the response object
        if (! $exception) {
            $this->key(static::RESPONSE)->setData(static::DATA, $payload);
        }

        if (! Validator::isEmpty($errors) && $isAppDebugging) {
            // If there are errors and no cause, include the error details for debugging
            $this->key(static::RESPONSE)->setData(static::ERRORS, $errors);
        }

        // If the application is in debug mode, add more detailed error or cause information
        if (! Validator::isEmpty($cause) && $isAppDebugging) {
            // If there is a cause (Throwable), include the stack trace for debugging
            $this->key(static::RESPONSE)->setData(static::CAUSE, $cause);
        }

        if (! Validator::isEmpty($exception) && $isAppDebugging) {
            // If there are errors and no cause, include the error details for debugging
            $this->key(static::RESPONSE)->setData(static::TRACE, $exception->getTrace());
        }

        // Add meta information if available
        $this->addMetaDataToResponse();

        // Filter the response based on requested keys
        return [$this->getData(static::RESPONSE)];
    }

    /**
     * Convert object data to JSON.
     *
     * @param array $keys array of required keys
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    #[Override]
    public function toJson(array $keys = []): string
    {
        $data = $this->toArray($keys);

        return Json::encode($data[0] ?? $data);
    }

    /**
     * Add meta information to the response object if available.
     *
     * @return void
     */
    private function addMetaDataToResponse(): void
    {
        $meta = $this->getMeta();

        if ($meta) {
            // Add the meta information if it exists
            $this->key(static::RESPONSE)->setData(static::META, $meta);
        }
    }
}
