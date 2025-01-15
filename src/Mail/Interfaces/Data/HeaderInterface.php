<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces\Data;

/**
 * Interface HeaderInterface.
 */
interface HeaderInterface
{
    /**
     * Constant for the header key.
     */
    public const KEY = 'key';

    /**
     * Constant for the header value.
     */
    public const VALUE = 'value';

    /**
     * Get the header key.
     *
     * This method retrieves the key of the header. The key is the identifier
     * for the header (e.g., 'Content-Type', 'Subject').
     *
     * @return string The header key.
     */
    public function getKey(): string;

    /**
     * Set the header key.
     *
     * This method sets the key for the header. The key is typically the
     * header name (e.g., 'Content-Type', 'Subject').
     *
     * @param  string  $key The header key to be set.
     *
     * @return $this The current instance of the class to allow method chaining.
     */
    public function setKey(string $key): self;

    /**
     * Get the header value.
     *
     * This method retrieves the value associated with the header. The value
     * corresponds to the data associated with the header key (e.g.,
     * 'text/html' for 'Content-Type' or the subject text for 'Subject').
     *
     * @return string|int The value of the header, which can be any data type (string, array, etc.).
     */
    public function getValue();

    /**
     * Set the header value.
     *
     * This method sets the value for the header. The value is the data
     * associated with the header key (e.g., 'text/html' for 'Content-Type'
     * or the subject text for 'Subject').
     *
     * @param  string|int  $value The header value to be set. This can be a string, array, or any type of data.
     *
     * @return $this The current instance of the class to allow method chaining.
     */
    public function setValue(string|int $value): self;
}
