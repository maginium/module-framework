<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Webapi\Rest\Response as ResponseManager;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Response service.
 *
 * This class acts as a simplified interface to access the ResponseManager.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * @method static ResponseManager setRedirect(string $url, int $code = 302)
 *     Sets a redirect URL with an optional HTTP response code (default is 302).
 *     Parameters:
 *     - $url: The URL to redirect to.
 *     - $code: Optional HTTP response code (default is 302).
 *     Returns:
 *     - ResponseManager: Instance of the response object.
 * @method static ResponseManager setHeader(string $name, string $value, bool $replace = false)
 *     Sets an HTTP header.
 *     Parameters:
 *     - $name: The name of the header.
 *     - $value: The value of the header.
 *     - $replace: Whether to replace existing headers with the same name (default is false).
 *     Returns:
 *     - ResponseManager: Instance of the response object.
 * @method static string getHeader(string $name)
 *     Retrieves the value of an HTTP header by name.
 *     Parameters:
 *     - $name: The name of the header.
 *     Returns:
 *     - string: The value of the header.
 * @method static ResponseManager setStatusCode(int $code)
 *     Sets the HTTP response status code.
 *     Parameters:
 *     - $code: The HTTP status code to set.
 *     Returns:
 *     - ResponseManager: Instance of the response object.
 * @method static int getStatusCode()
 *     Retrieves the HTTP response status code.
 *     Returns:
 *     - int: The HTTP status code.
 * @method static ResponseManager setBody(string $content)
 *     Sets the content/body of the HTTP response.
 *     Parameters:
 *     - $content: The content/body to set.
 *     Returns:
 *     - ResponseManager: Instance of the response object.
 * @method static string getBody()
 *     Retrieves the content/body of the HTTP response.
 *     Returns:
 *     - string: The content/body of the HTTP response.
 * @method static ResponseManager sendResponse()
 *     Sends the HTTP response to the client.
 *     Returns:
 *     - ResponseManager: Instance of the response object.
 * @method static bool isSent()
 *     Checks if the HTTP response has been sent.
 *     Returns:
 *     - bool: True if the response has been sent, false otherwise.
 * @method static ResponseManager setXFrameOptions(string $value)
 *     Sets the X-Frame-Options header value to control framing permissions.
 *     Parameters:
 *     - $value: The value to set for X-Frame-Options header.
 *     Returns:
 *     - ResponseManager: Instance of the response object.
 * @method static void sendVary()
 *     Sends the "Vary" header, which specifies that the response can vary based on different factors.
 * @method static void setPublicHeaders(int $ttl)
 *     Sets cache-related "public" headers with a specified time-to-live (TTL).
 *     Parameters:
 *     - $ttl: The time-to-live (TTL) value in seconds.
 * @method static void setPrivateHeaders(int $ttl)
 *     Sets cache-related "private" headers with a specified time-to-live (TTL).
 *     Parameters:
 *     - $ttl: The time-to-live (TTL) value in seconds.
 * @method static void setNoCacheHeaders()
 *     Sets headers to prevent caching of the response.
 * @method static ResponseManager representJson(string $content)
 *     Sets the content/body of the HTTP response as JSON.
 *     Parameters:
 *     - $content: The content to encode as JSON.
 *     Returns:
 *     - ResponseManager: Instance of the response object.
 *
 * @see ResponseManager
 */
class HttpResponse extends Facade
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
        return ResponseManager::class;
    }
}
