<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Facades;

use Magento\Framework\Webapi\Rest\Response as ResponseManager;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Response service.
 *
 * This class acts as a simplified interface to access the ResponseManager.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 * Methods:
 *
 * @method static void sendResponse() Send the complete HTTP response to the client.
 * @method static void prepareResponse(mixed $outputData = null) Prepare the response with the provided data.
 * @method static void setException(\Exception $e) Set an exception to the response.
 * @method static bool isException() Check if the response contains any exceptions.
 * @method static \Exception|null getException() Get the exception attached to the response, if any.
 * @method static bool hasExceptionOfType(string $type) Check if the response contains an exception of a specific type.
 * @method static void setMimeType(string $mimeType) Set the MIME type of the response.
 * @method static void addMessage(string $message, int $code, array $params = [], string $type = self::MESSAGE_TYPE_ERROR) Add a message to the response.
 * @method static bool hasMessages() Check if the response contains any messages.
 * @method static array getMessages() Retrieve all messages attached to the response.
 * @method static void clearMessages() Clear all messages from the response.
 * @method static void _resetState() Reset the internal state of the response.
 * @method static string|null getHeader(string $name) Retrieve the value of a specific header.
 * @method static void appendBody(string $value) Append content to the response body.
 * @method static void setBody(string $value) Set the content of the response body.
 * @method static void clearBody() Clear the content of the response body.
 * @method static void setHeader(string $name, string $value, bool $replace = false) Set a header value in the response.
 * @method static void clearHeader(string $name) Remove a specific header from the response.
 * @method static void clearHeaders() Remove all headers from the response.
 * @method static void setRedirect(string $url, int $code = 302) Set a redirect URL with an optional HTTP status code.
 * @method static void setHttpResponseCode(int $code) Set the HTTP status code for the response.
 * @method static void setStatusHeader(int $httpCode, string|null $version = null, string|null $phrase = null) Set the status header with a custom HTTP code, version, and reason phrase.
 * @method static int getHttpResponseCode() Retrieve the current HTTP status code.
 * @method static bool isRedirect() Check if the response indicates a redirection.
 * @method static array __sleep() Serialize the response object.
 * @method static string getVersion() Get the HTTP version used by the response.
 * @method static bool headersSent() Check if the response headers have already been sent.
 * @method static bool contentSent() Check if the response content has already been sent.
 * @method static void setHeadersSentHandler(callable $handler) Define a custom handler for headers already sent.
 * @method static void sendHeaders() Send the HTTP headers.
 * @method static void sendContent() Send the response content to the client.
 * @method static void send() Send the entire response, including headers and content.
 * @method static array|null getCookie() Retrieve cookie data from the response.
 * @method static void setStatusCode(int $code) Set the response status code.
 * @method static int getStatusCode() Retrieve the response status code.
 * @method static void setCustomStatusCode(int $code) Set a custom response status code.
 * @method static void setReasonPhrase(string $reasonPhrase) Set the reason phrase for the response status.
 * @method static string|null getReasonPhrase() Retrieve the reason phrase for the response status.
 * @method static string getBody() Retrieve the content of the response body.
 * @method static bool isClientError() Check if the response status indicates a client error (4xx).
 * @method static bool isForbidden() Check if the response status indicates a forbidden request (403).
 * @method static bool isInformational() Check if the response status indicates informational content (1xx).
 * @method static bool isNotFound() Check if the response status indicates a not found error (404).
 * @method static bool isGone() Check if the response status indicates a gone error (410).
 * @method static bool isOk() Check if the response status indicates a successful request (200).
 * @method static bool isServerError() Check if the response status indicates a server error (5xx).
 * @method static bool isSuccess() Check if the response status indicates a successful outcome.
 * @method static string renderStatusLine() Generate the HTTP status line as a string.
 * @method static string toString() Convert the entire response to a string representation.
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
