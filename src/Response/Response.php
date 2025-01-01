<?php

declare(strict_types=1);

namespace Maginium\Framework\Response;

use Illuminate\Support\Traits\Macroable;
use Laminas\Http\Headers;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as BaseResponse;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Webapi\Rest\Response as WebapiResponse;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Response\Interfaces\ResponseInterface;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Validator;

/**
 * Represents an enhanced HTTP response with additional functionality.
 *
 * This class extends Magento's base HTTP response to provide custom features such as:
 * - Web API response handling
 * - Cookie management enhancements
 * - Additional response macros
 */
class Response extends BaseResponse implements ResponseInterface
{
    use Macroable;

    /**
     * The web API response object.
     *
     * @var WebapiResponse
     */
    protected WebapiResponse $webapiResponse;

    /**
     * Response constructor.
     *
     * Initializes the enhanced response class with necessary dependencies.
     *
     * @param Context $context The context for the response.
     * @param DateTime $dateTime The current date and time.
     * @param HttpRequest $request The HTTP request object.
     * @param ConfigInterface $sessionConfig The session configuration.
     * @param WebapiResponse $webapiResponse The web API response object.
     * @param CookieManagerInterface $cookieManager The cookie manager.
     * @param CookieMetadataFactory $cookieMetadataFactory The cookie metadata factory.
     */
    public function __construct(
        Context $context,
        DateTime $dateTime,
        HttpRequest $request,
        ConfigInterface $sessionConfig,
        WebapiResponse $webapiResponse,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
    ) {
        parent::__construct(
            $request,
            $cookieManager,
            $cookieMetadataFactory,
            $context,
            $dateTime,
            $sessionConfig,
        );

        $this->webapiResponse = $webapiResponse;
    }

    /**
     * Retrieves the parsed body of the request.
     *
     * This method returns the processed (decoded) content of the request body.
     * The content is expected to be JSON or another format that has been decoded
     * into a native PHP structure (array or object).
     *
     * @return mixed The parsed body data, such as an array or object from a JSON request body.
     */
    public function body(): mixed
    {
        // Get the content of the response
        $content = $this->getContent();

        // Ensure the content is a valid, non-empty string
        if (Validator::isEmpty($content) || ! Validator::isString($content) || Validator::isXML($content)) {
            // Early exit if content is empty or not a string
            return $content;
        }

        // Decode and return the content of the body, assuming it's in JSON format.
        return Json::decode($this->getBody());
    }

    /**
     * Retrieves the raw body of the request as a string.
     *
     * This method returns the entire raw content of the HTTP request body as a string.
     * It is useful for handling raw data, such as JSON payloads, form data, or binary streams.
     *
     * @return string The raw request body content.
     */
    public function getRawBody(): string
    {
        // Fetch and return the raw request body content.
        return $this->getContent();
    }

    /**
     * Set the HTTP status code and optionally a message.
     *
     * This method sets the HTTP status code for the response and updates the web API response accordingly.
     *
     * @param int $code The HTTP status code to set.
     *
     * @throws InvalidArgumentException If the provided status code is invalid.
     *
     * @return $this The current instance to allow method chaining.
     */
    public function setStatusCode($code)
    {
        // Set the status code in the parent class
        parent::setStatusCode($code);

        // Update the status code in the web API response object
        $this->webapiResponse->setStatusCode($code);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set a HTTP header for the response.
     *
     * This method sets the specified HTTP header key and value for the response, and updates the web API response accordingly.
     *
     * @param string $key The header key to set.
     * @param string $value The value of the header.
     *
     * @throws InvalidArgumentException If the header key or value is invalid.
     *
     * @return $this The current instance to allow method chaining.
     */
    public function setHeader($key, $value, $replace = false)
    {
        // Set the header in the parent class
        parent::setHeader($key, $value, $replace);

        // Update the header in the web API response object
        $this->webapiResponse->setHeader($key, $value, $replace);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set multiple HTTP headers for the response.
     *
     * This method sets an array of header key-value pairs for the response.
     *
     * @param Headers|array $headers An associative array of headers to set.
     *
     * @throws InvalidArgumentException If any header key or value is invalid.
     *
     * @return $this The current instance to allow method chaining.
     */
    public function setHeaders($headers)
    {
        // Set the headers in the parent class
        parent::setHeaders($headers);

        // Update the headers in the web API response object
        $this->webapiResponse->setHeaders($headers);

        // Return the current instance to allow method chaining
        return $this;
    }
}
