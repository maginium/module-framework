<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Validator;

/**
 * Class RequestId.
 *
 * Middlewares for appending a unique request ID to REST API requests.
 */
class RequestId extends AbstractHeaderMiddleware
{
    /**
     * Header name for the X-Request-ID header.
     *
     * @var string
     */
    private const HEADER_NAME = 'x-request-id';

    /**
     * Header name for the X-Amazon-Request-ID header.
     */
    private const AMAZON_REQUEST_ID_HEADER = 'x-amazon-request-id';

    /**
     * RequestId constructor.
     *
     * Initializes the middleware and sets the logger class name.
     */
    public function __construct()
    {
        // Set Logger class name for logging purposes
        Log::setClassName(static::class);
    }

    /**
     * Retrieves the name of the header to be added.
     *
     * @return string|null The header name.
     */
    protected function getName(): string
    {
        return self::HEADER_NAME;
    }

    /**
     * Retrieves the value of the header to be added.
     *
     * @return string|null The header value.
     */
    protected function getValue(): ?string
    {
        // Attempt to retrieve the request ID from the primary header
        $requestId = Request::header(self::HEADER_NAME);

        // If the primary header is not set, attempt to retrieve it from a secondary header (Amazon-specific)
        if (Validator::isEmpty($requestId)) {
            $requestId = Request::header(self::AMAZON_REQUEST_ID_HEADER);
        }

        // Return the request ID to be added as a header value
        return $requestId;
    }
}
