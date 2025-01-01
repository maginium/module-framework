<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Validator;

/**
 * Class Language.
 *
 * Middleware for appending the current language to REST API requests.
 */
class Language extends AbstractHeaderMiddleware
{
    /**
     * Header name for the X-Language header.
     *
     * @var string
     */
    private const HEADER_NAME = 'x-language';

    /**
     * XML path for retrieving the locale code from configuration.
     *
     * @var string
     */
    private const XML_PATH_LOCALE_CODE = 'general/locale/code';

    /**
     * Language constructor.
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
        // Attempt to retrieve the language code from the primary header
        $languageCode = Request::header(self::HEADER_NAME);

        // If the header is not empty, return the language code
        if (! Validator::isEmpty($languageCode)) {
            return $languageCode;
        }

        // Return null if no value
        return null;
    }
}
