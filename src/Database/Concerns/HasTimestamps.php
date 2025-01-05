<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Reflection;

/**
 * Trait for managing timestamps.
 *
 * @property string|null $createdAtKey Custom field name for created_at timestamp
 * @property string|null $updatedAtKey Custom field name for updated_at timestamp
 */
trait HasTimestamps
{
    /**
     * Default field name for created_at.
     * This constant provides a default field name for the created_at timestamp.
     */
    private static $defaultCreatedAt = 'created_at';

    /**
     * Default field name for updated_at.
     * This constant provides a default field name for the updated_at timestamp.
     */
    private static $defaultUpdatedAt = 'updated_at';

    /**
     * Get the field name for created_at.
     *
     * This method returns the field name for the created_at timestamp.
     * If a custom field name is set, it returns that; otherwise, it returns the default value.
     *
     * @return string The field name for created_at.
     */
    public static function getCreatedAtKey(): string
    {
        // Check if the class using the trait has a custom static property set
        if (Reflection::propertyExists(static::class, 'createdAtKey')) {
            return static::$createdAtKey;
        }

        // Return the default if the property is not defined in the class
        return self::$defaultCreatedAt;
    }

    /**
     * Get the field name for updated_at.
     *
     * This method returns the field name for the updated_at timestamp.
     * If a custom field name is set, it returns that; otherwise, it returns the default value.
     *
     * @return string The field name for updated_at.
     */
    public static function getUpdatedAtKey(): string
    {
        // Check if the class using the trait has a custom static property set
        if (Reflection::propertyExists(static::class, 'updatedAtKey')) {
            return static::$updatedAtKey;
        }

        // Return the default if the property is not defined in the class
        return self::$defaultUpdatedAt;
    }

    /**
     * Initialize timestamps for the model.
     *
     * This method sets the created_at and updated_at timestamps for the model.
     * It checks if the model is new or existing and sets the appropriate timestamps.
     */
    public function initializeHasTimestamps(): void
    {
        // // Set created_at timestamp for new model instances if it's not already set
        // if ($this->isObject()) {
        //     // Get the current datetime as a string
        //     $createdAt = Date::now()->toDateTimeString();

        //     // Set the created_at timestamp to the model
        //     $this->setCreatedAt($createdAt);

        //     // Save the model to persist the "created_at" in the database
        //     $this->save();
        // }

        // // Set updated_at timestamp for existing model instances if it's not already set
        // if (! $this->isObject()) {
        //     // Get the current datetime as a string
        //     $updatedAt = Date::now()->toDateTimeString();

        //     // Set the updated_at timestamp to the model
        //     $this->setUpdatedAt($updatedAt);

        //     // Save the model to persist the "updated_at" in the database
        //     $this->save();
        // }
    }

    /**
     * Get the creation time.
     *
     * This method returns the created_at timestamp of the model.
     *
     * @return string|null The creation time or null if not set.
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(static::getCreatedAtKey());
    }

    /**
     * Set the creation time.
     *
     * This method sets the created_at timestamp for the model.
     *
     * @param string $createdAt The creation time to set.
     *
     * @return $this The current instance for method chaining.
     */
    public function setCreatedAt($createdAt): self
    {
        $this->setData(static::getCreatedAtKey(), $createdAt);

        return $this;
    }

    /**
     * Get the update time.
     *
     * This method returns the updated_at timestamp of the model.
     *
     * @return string|null The update time or null if not set.
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(static::getUpdatedAtKey());
    }

    /**
     * Set the update time.
     *
     * This method sets the updated_at timestamp for the model.
     *
     * @param string $updatedAt The update time to set.
     *
     * @return $this The current instance for method chaining.
     */
    public function setUpdatedAt($updatedAt): self
    {
        $this->setData(static::getUpdatedAtKey(), $updatedAt);

        return $this;
    }
}
