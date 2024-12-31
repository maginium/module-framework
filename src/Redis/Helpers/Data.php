<?php

declare(strict_types=1);

namespace Maginium\Framework\Redis\Helpers;

use Maginium\Framework\Support\Facades\Config;

/**
 * Helper class for managing Redis configurations.
 *
 * This class provides static methods to retrieve specific configurations related to Redis.
 * It retrieves Redis settings from the application configuration and provides default values
 * if no custom configuration is set.
 */
class Data
{
    /**
     * Redis connection scheme constant.
     * Defines the connection type (e.g., tcp, unix).
     */
    public const REDIS_SCHEME = 'REDIS_SCHEME';

    /**
     * Redis host constant.
     * The Redis server hostname or IP address.
     */
    public const REDIS_HOST = 'REDIS_HOST';

    /**
     * Redis port constant.
     * The port number used by the Redis server (default is 6379).
     */
    public const REDIS_PORT = 'REDIS_PORT';

    /**
     * Redis password constant.
     * The password required to connect to Redis, if any.
     */
    public const REDIS_PASSWORD = 'REDIS_PASSWORD';

    /**
     * Redis database constant.
     * The default Redis database to connect to (0 to 15).
     */
    public const REDIS_DATABASE = 'REDIS_DATABASE';

    /**
     * Redis connection timeout constant.
     * Timeout in seconds for the Redis connection.
     */
    public const REDIS_TIMEOUT = 'REDIS_TIMEOUT';

    /**
     * Redis read/write timeout constant.
     * Timeout in seconds for read and write operations with Redis.
     */
    public const REDIS_READ_WRITE_TIMEOUT = 'REDIS_READ_WRITE_TIMEOUT';

    /**
     * Redis persistent connection constant.
     * Whether the Redis connection should be persistent.
     */
    public const REDIS_PERSISTENT = 'REDIS_PERSISTENT';

    /**
     * Redis key prefix constant.
     * Prefix to be applied to all Redis keys.
     */
    public const REDIS_PREFIX = 'REDIS_PREFIX';

    /**
     * Redis cluster constant.
     * Specifies the cluster name if using Redis Cluster.
     */
    public const REDIS_CLUSTER = 'REDIS_CLUSTER';

    /**
     * Redis compression constant.
     * Specifies the compression algorithm used for Redis responses.
     */
    public const REDIS_COMPRESSION = 'REDIS_COMPRESSION';

    /**
     * Get the Redis connection scheme.
     *
     * @return string|null The Redis connection scheme (e.g., 'tcp', 'unix').
     * Defaults to 'tcp' if not configured.
     */
    public static function getScheme(): ?string
    {
        return Config::getString(self::REDIS_SCHEME, 'tcp');
    }

    /**
     * Get the Redis host configuration.
     *
     * @return string|null The Redis host (hostname or IP address).
     * Defaults to '127.0.0.1' if not configured.
     */
    public static function getHost(): ?string
    {
        return Config::getString(self::REDIS_HOST, '127.0.0.1');
    }

    /**
     * Get the Redis port configuration.
     *
     * @return int|null The Redis server port number.
     * Defaults to 6379 if not configured.
     */
    public static function getPort(): ?int
    {
        return Config::getInt(self::REDIS_PORT, 6379);
    }

    /**
     * Get the Redis password configuration.
     *
     * @return string|null The Redis password, or null if not set.
     */
    public static function getPassword(): ?string
    {
        return Config::getString(self::REDIS_PASSWORD, null);
    }

    /**
     * Get the Redis database configuration.
     *
     * @return int The Redis database index (0 to 15).
     * Defaults to 0 if not configured.
     */
    public static function getDatabase(): int
    {
        return Config::getInt(self::REDIS_DATABASE, 0);
    }

    /**
     * Get the Redis connection timeout configuration.
     *
     * @return float The connection timeout in seconds.
     * Defaults to 5.0 seconds if not configured.
     */
    public static function getTimeout(): float
    {
        return Config::getFloat(self::REDIS_TIMEOUT, 5.0);
    }

    /**
     * Get the Redis read/write timeout configuration.
     *
     * @return float The read/write timeout in seconds.
     * Defaults to 5.0 seconds if not configured.
     */
    public static function getReadWriteTimeout(): float
    {
        return Config::getFloat(self::REDIS_READ_WRITE_TIMEOUT, 5.0);
    }

    /**
     * Determine if the Redis connection is persistent.
     *
     * @return bool Whether the Redis connection should be persistent.
     * Defaults to false if not configured.
     */
    public static function isPersistent(): bool
    {
        return Config::getBool(self::REDIS_PERSISTENT, false);
    }

    /**
     * Get the Redis key prefix.
     *
     * @return string The key prefix to be used for Redis keys.
     * Defaults to 'APP_NAME_database_' if not configured.
     */
    public static function getPrefix(): string
    {
        // Attempt to get the Redis prefix from the configuration.
        $prefix = Config::getString(self::REDIS_PREFIX);

        // If prefix is not found, fallback to a default value using APP_NAME.
        return $prefix ?: Config::getString('APP_NAME') . '_database_';
    }

    /**
     * Get the Redis cluster configuration.
     *
     * @return string The Redis cluster name.
     * Defaults to 'redis' if not configured.
     */
    public static function getCluster(): string
    {
        return Config::getString(self::REDIS_CLUSTER, env('REDIS_CLUSTER', 'redis'));
    }

    /**
     * Get the Redis compression configuration.
     *
     * @return string The compression method used for Redis responses (e.g., 'lz4').
     * Defaults to 'lz4' if not configured.
     */
    public static function getCompression(): string
    {
        return Config::getString(self::REDIS_COMPRESSION, 'lz4');
    }

    /**
     * Get all Redis configuration settings as an associative array.
     *
     * @return array Associative array containing all Redis configurations.
     * This includes scheme, host, port, password, database, timeout, and more.
     */
    public static function getConfig(): array
    {
        // Return all Redis configurations in a key-value array with lowercase keys.
        return [
            'host' => self::getHost(),
            'port' => self::getPort(),
            'scheme' => self::getScheme(),
            'prefix' => self::getPrefix(),
            'cluster' => self::getCluster(),
            'timeout' => self::getTimeout(),
            'password' => self::getPassword(),
            'database' => self::getDatabase(),
            'persistent' => self::isPersistent(),
            'compression' => self::getCompression(),
            'read_write_timeout' => self::getReadWriteTimeout(),
        ];
    }
}
