<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Drivers;

use Illuminate\Support\Env as BaseEnv;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Config\Config;
use Maginium\Framework\Config\Interfaces\DriverInterface;
use Maginium\Framework\Log\Interfaces\LoggerInterface;
use Maginium\Framework\Support\Str;

/**
 * Class Env.
 *
 * This class serves as the environment-based configuration driver. It retrieves configuration
 * values from environment variables, with fallback mechanisms to ensure that the application
 * can continue functioning even if certain settings are missing.
 *
 * The `Env` class implements the DriverInterface and provides a method to fetch values,
 * with intelligent fallbacks and logging for error handling.
 */
class EnvConfig extends Config implements DriverInterface
{
    /**
     * Logger instance for logging messages and debugging.
     */
    private LoggerInterface $logger;

    /**
     * Env constructor.
     *
     * Initializes the environment configuration manager and sets up logging context.
     *
     * @param  LoggerInterface  $logger  Logs system events and errors.
     */
    public function __construct(
        LoggerInterface $logger,
    ) {
        $this->logger = $logger;

        // Set Log class name
        $logger->setClassName(static::class);
    }

    /**
     * Retrieves the value of a configuration variable, falling back to a default value if not found.
     *
     * The method checks the environment variables first, and if not found, it checks other
     * potential sources, like cache or deployment configuration, and then falls back to the
     * default value provided.
     *
     * @param  string  $path  The key representing the configuration variable (e.g., 'web/secure/base_url').
     * @param  mixed  $default  The value to return if the configuration is not found.
     *
     * @return mixed The configuration value, or null if empty, or the default value if no configuration is found.
     */
    public function get(string $path, $default = null): mixed
    {
        try {
            // Format the configuration path for use in Env retrieval
            $formattedEnvPath = $this->formatEnvPath($path);

            // First attempt: Check environment variables
            $value = BaseEnv::get($formattedEnvPath, $default);

            // Check if the value is empty, and return null if it is
            if (empty($value)) {
                return $default ?? null;
            }

            // Return the found value or default if nothing was found
            return $value ?? $default;
        } catch (Exception $e) {
            // Log the exception with a detailed message for debugging
            $this->logger->error(
                'Error retrieving configuration for path: ' . $path,
                ['exception' => $e],
            );

            // Return the default value in case of error
            return $default;
        }
    }

    /**
     * Format the configuration path to be compatible with environment variable standards.
     *
     * Converts the configuration path (e.g., "web/secure/base_url") into an uppercase string
     * with underscores, making it suitable for use as an environment variable key (e.g., "WEB_SECURE_BASE_URL").
     *
     * @param  string  $path  The raw configuration path.
     *
     * @return string The formatted environment variable path.
     */
    private function formatEnvPath(string $path): string
    {
        // Convert dots, hyphens, and spaces to underscores, then make the string uppercase.
        return Str::upper(Str::replace(['.', '-', ' '], '_', $path));
    }
}
