<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Middlewares;

use Maginium\Foundation\Abstracts\Middleware\AbstractHeaderMiddleware;
use Maginium\Framework\Support\Facades\Config;
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

        // Use the getStore method to get the store
        $store = $this->getStore();

        // Get Store ID
        $storeId = $store->getId();

        // Retrieve the timezone code from configuration based on the store ID
        $timezone = Config::getString(self::XML_PATH_LOCALE_TIMEZONE, $storeId);

        // Add timezone information to the log context for debugging and tracing purposes
        Log::withContext(['localization' => ['timezone' => $timezone]]);

        // Return the determined timezone code to be added as a header value
        return $timezone;
    }
}
