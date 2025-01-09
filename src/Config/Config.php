<?php

declare(strict_types=1);

namespace Maginium\Framework\Config;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Config\Interfaces\ConfigInterface;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Trait Config.
 *
 * This class manages configuration settings in the application.
 * It interfaces with various configuration layers including deployment,
 * scope configuration, environment variables, and a caching mechanism.
 *
 * @method mixed get(string $path, $default = null)
 * @method static scope(?string $scope = 'default', ?int $id = null) Sets the scope and ID for the configuration and returns a new instance for method chaining.
 * @method ?int getScopeId() Retrieves the store ID the configuration should be scoped to.
 * @method $this setScopeId(?int $scopeId) Sets the store ID for the configuration scope.
 * @method string getScope() Retrieves the scope (e.g., 'store', 'website') for the configuration.
 * @method $this setScope(?string $scope) Sets the scope for the configuration.
 * @method void resetScope() Resets the scope and scope ID to their default values.
 */
class Config implements ConfigInterface
{
    /**
     * Get the value of a configuration variable or throw an exception if not defined.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(string|null))|string|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If the configuration variable is not defined.
     *
     * @return mixed The value of the configuration variable.
     */
    public function getOrThrow(string $path, mixed $default = null): mixed
    {
        try {
            // Retrieve the configuration value using the base get method
            $value = $this->get($path, $default);

            // If the value is not defined, throw a LocalizedException
            if ($value === null) {
                throw LocalizedException::make(
                    __("Environment variable '%1' is not defined.", $path),
                );
            }

            return $value;
        } catch (Exception $e) {
            // Rethrow the exception for further handling
            throw $e;
        }
    }

    /**
     * Get the value of a configuration variable as a string.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(string|null))|string|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return string|null The value of the configuration variable as a string.
     */
    public function getString(string $path, mixed $default = null): ?string
    {
        try {
            $value = $this->get($path, $default);

            // Convert the retrieved value to a string
            return ! empty($value) ? (string)$value : null;
        } catch (Exception) {
            // Handle the exception by throwing a localized error
            throw LocalizedException::make(__('Error retrieving configuration variable as string.'));
        }
    }

    /**
     * Get the value of a configuration variable as an integer.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(int|null))|int|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return int|null The value of the configuration variable as an integer.
     */
    public function getInt(string $path, mixed $default = null): ?int
    {
        try {
            // Convert the retrieved value to an integer
            return (int)$this->get($path, $default);
        } catch (Exception) {
            // Handle the exception by throwing a localized error
            throw LocalizedException::make(__('Error retrieving configuration variable as integer.'));
        }
    }

    /**
     * Get the value of a configuration variable as a float.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(float|null))|float|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return float|null The value of the configuration variable as a float.
     */
    public function getFloat(string $path, mixed $default = null): ?float
    {
        try {
            // Convert the retrieved value to a float
            return (float)$this->get($path, $default);
        } catch (Exception) {
            // Handle the exception by throwing a localized error
            throw LocalizedException::make(__('Error retrieving configuration variable as float.'));
        }
    }

    /**
     * Get the value of a configuration variable as a boolean.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(bool|null))|bool|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return bool The value of the configuration variable as a boolean.
     */
    public function getBool(string $path, mixed $default = null): bool
    {
        try {
            // Retrieve the raw value
            $rawValue = $this->get($path, $default);

            // Convert string representations of boolean values
            if (Validator::isString($rawValue)) {
                $lowercaseValue = Str::lower($rawValue);

                if ($lowercaseValue === 'true') {
                    return true;
                }

                if ($lowercaseValue === 'false') {
                    return false;
                }
            }

            // Use filter_var for standard boolean conversion
            return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        } catch (Exception) {
            // Handle the exception by throwing a localized error
            throw LocalizedException::make(__('Error retrieving configuration variable as boolean.'));
        }
    }

    /**
     * Get the value of a configuration variable as an array.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  (Closure():(array<array-key, mixed>|null))|array<array-key, mixed>|null  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return array|null The value of the configuration variable as an array.
     */
    public function getArray(string $path, mixed $default = null, ?string $separator = ','): array
    {
        try {
            // Retrieve the configuration value
            $value = $this->get($path, $default);

            if ($value) {
                // Split the string into an array using the specified separator
                return Php::explode($separator, $value);
            }

            return $value ?? [];
        } catch (Exception) {
            // Handle the exception by throwing a localized error
            throw LocalizedException::make(__('Error retrieving configuration variable as array.'));
        }
    }

    /**
     * Get the value of a configuration variable as an object.
     *
     * @param  string  $path  The key of the configuration variable.
     * @param  Closure(): object|null|object  $default  The default value to return if no configuration is found.
     *
     * @throws LocalizedException If an error occurs while retrieving the value.
     *
     * @return Collection The value of the configuration variable as an object.
     */
    public function getObject(string $path, mixed $default = null): Collection
    {
        try {
            // Retrieve and return the value as an object
            return Collection::make($this->get($path, $default));
        } catch (Exception) {
            // Handle the exception by throwing a localized error
            throw LocalizedException::make(__('Error retrieving configuration variable as object.'));
        }
    }
}
