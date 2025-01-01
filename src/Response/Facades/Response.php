<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Response\Interfaces\ResponseInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Response service.
 *
 * This class acts as a simplified interface to access the ResponseInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * Methods:
 *
 * @method static mixed body() Retrieves the parsed body of the request. Returns an array or object from a JSON request body.
 * @method static string getBody() Retrieves the raw content of the HTTP request body as a string.
 * @method static string getRawBody() Retrieves the raw body of the request as a string. Useful for handling raw data such as JSON payloads or binary streams.
 * @method static void setHttpResponseCode(int $code) Sets the HTTP status code for the response.
 * @method static int getHttpResponseCode() Retrieves the current HTTP status code.
 * @method static void setHeader(string $name, string $value, bool $replace = false) Sets a header value in the response. Optionally replaces existing headers with the same name.
 * @method static string|null getHeader(string $name) Retrieves the value of a specific header by name.
 * @method static void clearHeader(string $name) Removes a specific header from the response.
 * @method static void setStatusHeader(int $httpCode, string|null $version = null, string|null $phrase = null) Sets the status header with an HTTP code, version, and optional reason phrase.
 * @method static void appendBody(string $value) Appends content to the existing response body.
 * @method static void setBody(string $value) Replaces the current content of the response body.
 * @method static void setRedirect(string $url, int $code = 302) Sets a redirect URL with an optional HTTP status code.
 * @method static void sendResponse() Sends the complete HTTP response to the client, including headers and body content.
 *
 * @see ResponseInterface
 */
class Response extends Facade
{
    /**
     * Indicates if the resolved facade should be cached.
     *
     * @var bool
     */
    protected static $cached = false;

    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return ResponseInterface::class;
    }
}
