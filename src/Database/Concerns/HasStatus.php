<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Support\Reflection;

/**
 * Trait HasStatus.
 *
 * Provides common methods to manage a status property on models.
 *
 * @method mixed getStatus() Get the value of the status.
 * @method $this setStatus(mixed $status) Set the value of the status.
 * @method bool hasStatus() Check if the status is set.
 * @method $this removeStatus() Remove the status from the model.
 * @method bool isStatus(mixed $value) Check if the status matches a specific value.
 * @method bool isActive() Check if the status is "active".
 * @method bool isInactive() Check if the status is "inactive".
 *
 * @property string $statusKey statuc key name
 */
trait HasStatus
{
    /**
     * Column 'status' for the customer.
     */
    public const STATUS = 'status';

    /**
     * Constant representing "active".
     */
    public const ACTIVE = 'Active';

    /**
     * Constant representing "inactive".
     */
    public const INACTIVE = 'Inactive';

    /**
     * Get the value of the status.
     *
     * @param bool $asBoolean Whether to return the status as a boolean value (defaults to false for string).
     *
     * @throws LocalizedException If the status is not set.
     *
     * @return mixed The status value (either string or boolean).
     */
    public function getStatus(bool $asBoolean = false)
    {
        // Check if the model has a status
        if (! $this->hasData($this->getStatusKey())) {
            // If the status is not set, throw an exception
            throw LocalizedException::make(message: __('Status is not set.'));
        }

        // Retrieve the status value
        $status = (bool)$this->getData($this->getStatusKey());

        // If the user wants a boolean value, return whether the status is "active" or "inactive"
        if ($asBoolean) {
            return $status;
        }

        // Return the string representation of the status
        return $status ? __(static::ACTIVE)->render() : __(static::INACTIVE)->render();
    }

    /**
     * Set the value of the status.
     *
     * @param bool $status The status value to set.
     *
     * @return $this The current model instance for fluent interface.
     */
    public function setStatus($status)
    {
        // Set the status value in the model's data
        $this->setData($this->getStatusKey(), $status);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Check if the status is set.
     *
     * @return bool True if the status is set, otherwise false.
     */
    public function hasStatus(): bool
    {
        // Check if the model contains the status
        return (bool)$this->hasData($this->getStatusKey());
    }

    /**
     * Remove the status from the model.
     *
     * @throws LocalizedException If the status is not set.
     *
     * @return $this The current model instance for fluent interface.
     */
    public function removeStatus()
    {
        // Check if the status exists before attempting to remove it
        if (! $this->hasStatus()) {
            // If the status does not exist, throw an exception
            throw LocalizedException::make(__('Status is not set.'));
        }

        // Unset the status data
        $this->unsetData($this->getStatusKey());

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Check if the status matches a specific value.
     *
     * @param bool $value The status value to check against.
     *
     * @return bool True if the status matches the value, otherwise false.
     */
    public function isStatus(bool $value): bool
    {
        // Check if the current status matches the specified value
        return $this->getStatus(true) === $value;
    }

    /**
     * Check if the status is "active".
     *
     * @return bool True if the status is active, otherwise false.
     */
    public function isActive(): bool
    {
        // Check if the status is set and matches the value for "active"
        return $this->isStatus(true);
    }

    /**
     * Check if the status is "inactive".
     *
     * @return bool True if the status is inactive, otherwise false.
     */
    public function isInactive(): bool
    {
        // Check if the status is set and matches the value for "inactive"
        return $this->isStatus(false);
    }

    /**
     * Get the name of the status field for the model.
     *
     * This method retrieves the name of the field where the status is stored.
     * If no specific field name is provided, it defaults to the interface-defined constant.
     *
     * @return string The name of the status field (defaults to the STATUS constant).
     */
    private function getStatusKey(): string
    {
        // Check if the class using the trait has a custom static property set
        if (Reflection::propertyExists(static::class, 'statusKey')) {
            return static::$statusKey;
        }

        // Return the default if the property is not defined in the class
        return static::STATUS;
    }
}
