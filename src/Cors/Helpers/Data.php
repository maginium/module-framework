<?php

declare(strict_types=1);

namespace Maginium\Framework\Cors\Helpers;

use Maginium\Framework\Support\Facades\Config;

/**
 * Class Data.
 *
 * Helper class to retrieve CORS (Cross-Origin Resource Sharing) configuration from system configuration.
 */
class Data
{
    /**
     * XML path for Access-Control-Max-Age configuration.
     */
    private const XML_PATH_MAX_AGE = 'web/corsRequests/max_age';

    /**
     * XML path for Access-Control-Allow-Origin configuration.
     */
    private const XML_PATH_ORIGIN_URL = 'web/corsRequests/origin_url';

    /**
     * XML path for AMP-Access-Control-Allow-Source-Origin configuration.
     */
    private const XML_PATH_ENABLE_AMP = 'web/corsRequests/enable_amp';

    /**
     * XML path for Access-Control-Allow-Credentials configuration.
     */
    private const XML_PATH_ALLOW_CREDENTIALS = 'web/corsRequests/allow_credentials';

    /**
     * Get the origin domain the requests are allowed to come from.
     *
     * @return string The origin URL configured in system settings.
     */
    public static function getOriginUrl(): string
    {
        return Config::getString(self::XML_PATH_ORIGIN_URL, '');
    }

    /**
     * Check whether to allow credentials in CORS requests.
     *
     * @return bool Whether credentials should be allowed in CORS requests.
     */
    public static function getAllowCredentials(): bool
    {
        return Config::getBool(self::XML_PATH_ALLOW_CREDENTIALS);
    }

    /**
     * Check whether to enable AMP (Accelerated Mobile Pages) support in CORS requests.
     *
     * @return bool Whether AMP support should be enabled in CORS requests.
     */
    public static function getEnableAmp(): bool
    {
        return Config::getBool(self::XML_PATH_ENABLE_AMP);
    }

    /**
     * Get the value of Access-Control-Max-Age header for CORS requests.
     *
     * @return int The maximum age (in seconds) to cache preflight responses.
     */
    public static function getMaxAge(): int
    {
        return Config::getInt(self::XML_PATH_MAX_AGE);
    }
}
