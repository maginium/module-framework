<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models;

use Maginium\Framework\Database\ObjectModel;
use Maginium\Framework\Mail\Interfaces\Data\MetadataInterface;

/**
 * Class Metadata.
 *
 * A data object class that represents a single email metadata with a key-value pair.
 * This class provides methods to get and set both the key and the value of the metadata.
 * It is intended to be used for managing individual metadata in email-related functionality.
 */
class Metadata extends ObjectModel implements MetadataInterface
{
    /**
     * Get the metadata key.
     *
     * This method retrieves the key of the metadata. The key is the identifier
     * for the metadata (e.g., 'Content-Type', 'Subject').
     *
     * @return string The metadata key.
     */
    public function getKey(): string
    {
        return $this->getData(self::KEY);
    }

    /**
     * Set the metadata key.
     *
     * This method sets the key for the metadata. The key is typically the
     * metadata name (e.g., 'Content-Type', 'Subject').
     *
     * @param  string  $key The metadata key to be set.
     *
     * @return $this The current instance of the class to allow method chaining.
     */
    public function setKey(string $key): self
    {
        // Store the provided key in the internal data store
        $this->setData(self::KEY, $key);

        // Return the current instance for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the metadata value.
     *
     * This method retrieves the value associated with the metadata. The value
     * corresponds to the data associated with the metadata key (e.g.,
     * 'text/html' for 'Content-Type' or the subject text for 'Subject').
     *
     * @return string|int The value of the metadata, which can be any data type (string, array, etc.).
     */
    public function getValue()
    {
        return $this->getData(self::VALUE);
    }

    /**
     * Set the metadata value.
     *
     * This method sets the value for the metadata. The value is the data
     * associated with the metadata key (e.g., 'text/html' for 'Content-Type'
     * or the subject text for 'Subject').
     *
     * @param  string|int  $value The metadata value to be set. This can be a string, array, or any type of data.
     *
     * @return $this The current instance of the class to allow method chaining.
     */
    public function setValue(string|int $value): self
    {
        // Store the provided value in the internal data store
        $this->setData(self::VALUE, $value);

        // Return the current instance for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }
}
