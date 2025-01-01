<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Interfaces;

use Magento\Framework\App\Response\HttpInterface as BaseResponseInterface;

/**
 * Interface ResponseInterface.
 *
 * @method mixed body() Retrieves the parsed body of the request.
 * @method string getBody() Retrieves the body of the request as a string.
 * @method string getRawBody() Retrieves the raw body of the request as a string.
 */
interface ResponseInterface extends BaseResponseInterface
{
    /**
     * Retrieves the parsed body of the request.
     *
     * This method returns the processed (decoded) content of the request body.
     * The content is expected to be JSON or another format that has been decoded
     * into a native PHP structure (array or object).
     *
     * @return mixed The parsed body data, such as an array or object from a JSON request body.
     */
    public function body(): mixed;

    /**
     * Retrieves the body of the request as a string.
     *
     * This method returns the entire raw content of the HTTP request body as a string.
     * It is useful for handling raw data, such as JSON payloads, form data, or binary streams.
     *
     * @return string The raw request body content.
     */
    public function getBody();

    /**
     * Retrieves the raw body of the request as a string.
     *
     * This method returns the entire raw content of the HTTP request body as a string.
     * It is useful for handling raw data, such as JSON payloads, form data, or binary streams.
     *
     * @return string The raw request body content.
     */
    public function getRawBody(): string;
}
