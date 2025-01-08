<?php

declare(strict_types=1);

namespace Maginium\Framework\Pusher\Helpers;

use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Support\Facades\Config;

/**
 * Helper class for managing Pusher configurations.
 *
 * This class provides methods to retrieve specific configurations for Pusher
 * such as app ID, app key, app secret, app cluster, TLS usage, timeout,
 * and debug settings.
 */
class Data
{
    // Constants for all configuration keys
    /**
     * Configuration key for Pusher app ID.
     */
    public const APP_ID = 'PUSHER_APP_ID';

    /**
     * Configuration key for Pusher app key.
     */
    public const APP_KEY = 'PUSHER_APP_KEY';

    /**
     * Configuration key for Pusher app secret.
     */
    public const APP_SECRET = 'PUSHER_APP_SECRET';

    /**
     * Configuration key for Pusher app cluster.
     */
    public const APP_CLUSTER = 'PUSHER_APP_CLUSTER';

    /**
     * Configuration key for Pusher TLS usage.
     */
    public const USE_TLS = 'PUSHER_USE_TLS';

    /**
     * Configuration key for Pusher timeout.
     */
    public const TIMEOUT = 'PUSHER_TIMEOUT';

    /**
     * Configuration key for application debug setting.
     */
    public const DEBUG = 'APP_DEBUG';

    /**
     * Get the Pusher app id configuration.
     *
     * This method retrieves the Pusher app ID from the configuration.
     *
     * @return string|null The Pusher app id configuration, or null if not set.
     */
    public static function getAppId(): string
    {
        return Config::driver(ConfigDrivers::ENV)->getString(self::APP_ID);
    }

    /**
     * Get the Pusher app key configuration.
     *
     * This method retrieves the Pusher app key from the configuration.
     *
     * @return string|null The Pusher app key configuration, or null if not set.
     */
    public static function getAppKey(): string
    {
        return Config::driver(ConfigDrivers::ENV)->getString(self::APP_KEY);
    }

    /**
     * Get the Pusher app secret configuration.
     *
     * This method retrieves the Pusher app secret from the configuration.
     *
     * @return string|null The Pusher app secret configuration, or null if not set.
     */
    public static function getAppSecret(): string
    {
        return Config::driver(ConfigDrivers::ENV)->getString(self::APP_SECRET);
    }

    /**
     * Get the Pusher app cluster configuration.
     *
     * This method retrieves the Pusher app cluster from the configuration.
     *
     * @return string|null The Pusher app cluster configuration, or null if not set.
     */
    public static function getAppCluster(): string
    {
        return Config::driver(ConfigDrivers::ENV)->getString(self::APP_CLUSTER);
    }

    /**
     * Get the Pusher useTLS configuration.
     *
     * This method retrieves the useTLS setting for Pusher from the configuration.
     * Defaults to true if the setting is not found in configuration.
     *
     * @return bool The Pusher useTLS configuration (true or false).
     */
    public static function getUseTLS(): bool
    {
        return Config::driver(ConfigDrivers::ENV)->getBool(self::USE_TLS);
    }

    /**
     * Get the Pusher timeout configuration.
     *
     * This method retrieves the timeout setting for Pusher from the configuration.
     * Defaults to 30 seconds if not found in configuration.
     *
     * @return int The Pusher timeout configuration in seconds.
     */
    public static function getTimeout(): int
    {
        return Config::driver(ConfigDrivers::ENV)->getInt(self::TIMEOUT);
    }

    /**
     * Get the Pusher debug configuration.
     *
     * This method retrieves the debug setting for the application, which can
     * be used to enable or disable debugging for Pusher.
     * Defaults to false if not found in configuration.
     *
     * @return bool The Pusher debug configuration (true or false).
     */
    public static function getDebug(): bool
    {
        return Config::driver(ConfigDrivers::ENV)->getBool(self::DEBUG);
    }

    /**
     * Get all Pusher configurations.
     *
     * This method retrieves all relevant Pusher configuration settings,
     * including app ID, app key, secret, and various options such as
     * cluster, TLS usage, timeout, and debug settings.
     *
     * @return array Associative array containing all Pusher configurations.
     */
    public static function getConfig(): array
    {
        // Return an array of all Pusher configuration settings
        return [
            'app_id' => self::getAppId(),
            'auth_key' => self::getAppKey(),
            'secret' => self::getAppSecret(),
            'options' => [
                'cluster' => self::getAppCluster(),
                'useTLS' => self::getUseTLS(),
                'timeout' => self::getTimeout(),
                'debug' => self::getDebug(),
            ],
        ];
    }
}
