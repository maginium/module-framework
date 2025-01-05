<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Maginium\Foundation\Abstracts\AbstractHeaderMiddleware;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Validator;

/**
 * Class Timezone.
 *
 * Middleware for appending the current timezone to REST API requests.
 */
class Timezone extends AbstractHeaderMiddleware
{
    /**
     * Header name for the X-Timezone header.
     *
     * @var string
     */
    private const HEADER_NAME = 'x-timezone';

    /**
     * XML path for retrieving the locale timezone from configuration.
     *
     * @var string
     */
    private const XML_PATH_LOCALE_TIMEZONE = 'general/locale/timezone';

    /**
     * Timezone constructor.
     * The store manager instance.
     */
    public function __construct()
    {
        // Set Logger class name for logging purposes
        Log::setClassName(static::class);
    }

    /**
     * Retrieves the name of the header to be added.
     *
     * @return string The header name.
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
        // Attempt to retrieve the timezone from the primary header
        $timezone = Request::header(self::HEADER_NAME);

        // If the primary header is not empty, return the timezone
        if (! Validator::isEmpty($timezone)) {
            return $timezone;
        }

        // Return the determined timezone code to be added as a header value
        return $timezone;
    }
}
