<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Middlewares;

use Maginium\Framework\Support\Facades\Date;
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

        // If the header is empty, generate a new timestamp using the current date and time
        if (Validator::isEmpty($timestamp)) {
            // Generates a timestamp in the format 'YYYY-MM-DD HH:MM:SS'
            $timestamp = Date::now()->toDateTimeString();
        }

        // Add the timestamp information to the log context for debugging and tracing purposes
        Log::withContext(['technical-metadata' => ['timestamp' => $timestamp]]);

        // Return the timestamp value
        return $timestamp;
    }
}
