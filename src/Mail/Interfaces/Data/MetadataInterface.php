<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces\Data;

/**
 * Interface MetadataInterface.
 */
interface MetadataInterface
{
    /**
     * Constant for the metadata key.
     */
    public const KEY = 'key';

    /**
     * Constant for the metadata value.
     */
    public const VALUE = 'value';

    /**
     * Get the metadata key.
     *
     * This method retrieves the key of the metadata. The key is the identifier
     * for the metadata (e.g., 'Content-Type', 'Subject').
     *
     * @return string The metadata key.
     */
    public function getKey(): string;

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
    public function setKey(string $key): self;

    /**
     * Get the metadata value.
     *
     * This method retrieves the value associated with the metadata. The value
     * corresponds to the data associated with the metadata key (e.g.,
     * 'text/html' for 'Content-Type' or the subject text for 'Subject').
     *
     * @return string|int The value of the metadata, which can be any data type (string, array, etc.).
     */
    public function getValue();

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
    public function setValue(string|int $value): self;
}
