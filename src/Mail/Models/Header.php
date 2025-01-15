<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models;

use Maginium\Framework\Database\ObjectModel;
use Maginium\Framework\Mail\Interfaces\Data\HeaderInterface;

/**
 * Class Header.
 *
 * A data object class that represents a single email header with a key-value pair.
 * This class provides methods to get and set both the key and the value of the header.
 * It is intended to be used for managing individual headers in email-related functionality.
 */
class Header extends ObjectModel implements HeaderInterface
{
    /**
     * Get the header key.
     *
     * This method retrieves the key of the header. The key is the identifier
     * for the header (e.g., 'Content-Type', 'Subject').
     *
     * @return string The header key.
     */
    public function getKey(): string
    {
        return $this->getData(self::KEY);
    }

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
    public function setKey(string $key): self
    {
        // Store the provided key in the internal data store
        $this->setData(self::KEY, $key);

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Get the header value.
     *
     * This method retrieves the value associated with the header. The value
     * corresponds to the data associated with the header key (e.g.,
     * 'text/html' for 'Content-Type' or the subject text for 'Subject').
     *
     * @return string|int The value of the header, which can be any data type (string, array, etc.).
     */
    public function getValue()
    {
        return $this->getData(self::VALUE);
    }

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
    public function setValue(string|int $value): self
    {
        // Store the provided value in the internal data store
        $this->setData(self::VALUE, $value);

        // Return the current instance for method chaining
        return $this;
    }
}
