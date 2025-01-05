<?php

declare(strict_types=1);

namespace Maginium\Framework\Swagger\Helpers;

use Maginium\Framework\Support\Facades\Config;

/**
 * Class Data.
 *
 * Helper class for swagger operations.
 */
class Data
{
    /**
     * Configuration path for Swagger status.
     */
    public const XML_PATH_SWAGGER_ENABLED = 'dev/swagger/active';

    /**
     * Configuration path for contact name.
     */
    public const XML_PATH_SWAGGER_CONTACT_NAME = 'dev/swagger/contact/name';

    /**
     * Configuration path for contact email.
     */
    public const XML_PATH_SWAGGER_CONTACT_EMAIL = 'dev/swagger/contact/email';

    /**
     * Configuration path for contact URL.
     */
    public const XML_PATH_SWAGGER_CONTACT_URL = 'dev/swagger/contact/url';

    /**
     * Configuration path for Swagger license name.
     */
    public const XML_PATH_SWAGGER_LICENSE_NAME = 'dev/swagger/license/name';

    /**
     * Configuration path for Swagger license URL.
     */
    public const XML_PATH_SWAGGER_LICENSE_URL = 'dev/swagger/license/url';

    /**
     * Check if Swagger is enabled.
     *
     * @return bool
     */
    public static function isSwaggerEnabled()
    {
        return Config::driver('scope')->getbool(self::XML_PATH_SWAGGER_ENABLED);
    }

    /**
     * Get contact name from configuration.
     *
     * @return string|null
     */
    public static function getContactName()
    {
        return Config::driver('scope')->getString(
            self::XML_PATH_SWAGGER_CONTACT_NAME,
        );
    }

    /**
     * Get contact email from configuration.
     *
     * @return string|null
     */
    public static function getContactEmail()
    {
        return Config::driver('scope')->getString(
            self::XML_PATH_SWAGGER_CONTACT_EMAIL,
        );
    }

    /**
     * Get contact URL from configuration.
     *
     * @return string|null
     */
    public static function getContactUrl()
    {
        return Config::driver('scope')->getString(self::XML_PATH_SWAGGER_CONTACT_URL);
    }

    /**
     * Get Swagger license name.
     *
     * @return string|null
     */
    public static function getLicenseName()
    {
        return Config::driver('scope')->getString(
            self::XML_PATH_SWAGGER_LICENSE_NAME,
        );
    }

    /**
     * Get Swagger license URL.
     *
     * @return string|null
     */
    public static function getLicenseUrl()
    {
        return Config::driver('scope')->getString(self::XML_PATH_SWAGGER_LICENSE_URL);
    }
}
