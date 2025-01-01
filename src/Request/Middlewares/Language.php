<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Middlewares;

use Maginium\Foundation\Abstracts\AbstractHeaderMiddleware;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Locale;
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
        // Retrieve the language code from the request header (if present)
        $languageCode = Request::header(self::HEADER_NAME);

        // If the language code exists in the header, return it
        if (! Validator::isEmpty($languageCode)) {
            return $languageCode;
        }

        // Determine the language code based on the user or store configuration
        $languageCode = $this->determineLanguageCode();

        // Set the language code to the request and application locales
        Request::setLanguage($languageCode);
        Locale::setLocale($languageCode);

        // Add the language information to the log context for debugging purposes
        Log::withContext(['localization' => ['language-code' => $languageCode]]);

        // Return the determined language code to be included in the header
        return $languageCode;
    }

    /**
     * Determines the appropriate language code for the request.
     * First checks if a user locale exists, and if not, falls back to the store locale.
     *
     * @return string The determined language code.
     */
    private function determineLanguageCode(): string
    {
        // Check if a user is authenticated and has a preferred locale
        $user = Request::user();

        if ($user && ! Validator::isEmpty($user->preferredLocale())) {
            // Add the language information to the log context for debugging purposes
            Log::withContext(['localization' => ['user-locale' => $user->preferredLocale()]]);

            // Use user locale if available
            return $user->preferredLocale();
        }

        // Fallback to store locale if no user locale is set
        return $this->getStoreLocale();
    }

    /**
     * Retrieves the locale code of the store.
     *
     * @return string The store's locale code.
     */
    private function getStoreLocale(): string
    {
        // Get the store and its locale code based on configuration
        $store = $this->getStore();
        $storeId = $store->getId();

        // Retrieve the locale code from the store configuration
        return Config::getString(self::XML_PATH_LOCALE_CODE, $storeId);
    }
}
