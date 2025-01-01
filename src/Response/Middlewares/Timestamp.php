<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Validator;

/**
 * Class Timestamp.
 *
 * Middlewares for appending a timestamp to REST API requests.
 */
class Timestamp extends AbstractHeaderMiddleware
{
    /**
     * Header name for the X-Timestamp header.
     *
     * @var string
     */
    private const HEADER_NAME = 'x-timestamp';

    /**
     * Timestamp constructor.
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
        // Attempt to retrieve the timestamp from the specified header
        $timestamp = Request::header(self::HEADER_NAME);

        // If the header is not empty, return the timestamp
        if (! Validator::isEmpty($timestamp)) {
            return $timestamp;
        }

        // Return null if no value
        return null;
    }
}
