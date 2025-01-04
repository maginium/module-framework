<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Validator;
use Maginium\Foundation\Abstracts\AbstractHeaderMiddleware;

/**
 * Class Currency.
 *
 * Middleware for appending the current currency to REST API requests.
 */
class Currency extends AbstractHeaderMiddleware
{
    /**
     * Header name for the X-Currency header.
     *
     * @var string
     */
    private const HEADER_NAME = 'x-currency';

    /**
     * Currency constructor.
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
        // Attempt to retrieve the currency from the primary header
        $currencyCode = Request::header(self::HEADER_NAME);

        // If the header is not empty, return the currency code
        if (! Validator::isEmpty($currencyCode)) {
            return $currencyCode;
        }

        // Return null if no value
        return null;
    }
}
