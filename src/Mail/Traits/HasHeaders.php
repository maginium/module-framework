<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Traits;

use Illuminate\Contracts\Support\Arrayable;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Mail\Interfaces\Data\HeaderInterface;
use Maginium\Framework\Mail\Interfaces\MailerInterface;
use Maginium\Framework\Support\DataObject;

/**
 * Trait HasHeaders.
 *
 * This trait provides methods to manage email headers within the envelope context.
 * It allows for adding, retrieving, and setting additional headers for the email.
 * Email headers can be used for various purposes, such as tracking, custom metadata,
 * or other protocol-related information (e.g., 'Content-Type', 'Subject').
 */
trait HasHeaders
{
    /**
     * Set headers for the email.
     *
     * This method allows you to add additional headers to the email, which can be useful for tracking or other purposes.
     *
     * @param array $headers Additional headers for the email.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function headers(array $headers): MailerInterface
    {
        // Set the email headers in the internal data store.
        return $this->setHeaders($headers);
    }

    /**
     * Retrieve the additional headers for the email.
     *
     * @return HeaderInterface|null Returns an array of key-value pairs representing the headers.
     */
    public function getHeaders(): ?array
    {
        // Call denormalizeHeaders to return key-value pairs
        return $this->getData(MailerInterface::HEADERS);
    }

    /**
     * Set the additional headers for the email.
     *
     * @param HeaderInterface[]|null $headers An array of header key-value pairs.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setHeaders(?array $headers): MailerInterface
    {
        // Normalize the headers.
        $headers = $this->normalizeHeaders($headers);

        $this->setData(MailerInterface::HEADERS, $headers);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Determine if the message has the given headers.
     *
     * Checks if the provided key and value exist in the headers of the message.
     *
     * @param string $key The headers key to check.
     * @param string $value The headers value to check.
     *
     * @return bool Returns true if the headers key and value exist, otherwise false.
     */
    public function hasHeader(string $key, string $value): bool
    {
        // Retrieve the headers from the message.
        $headers = $this->getData(MailerInterface::HEADERS);

        // Check if the key exists and the value matches.
        return isset($headers[$key]) && (string)$headers[$key] === $value;
    }

    /**
     * Normalize the given header into HeaderInterface instances.
     *
     * Converts the provided data into an array of HeaderInterface objects. Handles
     * Arrayable and DataObject types by converting them into arrays, and ensures that
     * both key-value pair arrays and existing HeaderInterface objects are properly processed.
     *
     * @param array|Arrayable|DataObject|null $header The header as key-value pairs or an object.
     *
     * @return HeaderInterface[] An array of HeaderInterface instances.
     */
    private function normalizeHeaders(array|Arrayable|DataObject|null $header): array
    {
        // If the header is null or empty, return an empty array
        if (! $header) {
            return [];
        }

        // Normalize header to an array if it's a DataObject or Arrayable
        if ($header instanceof DataObject) {
            $header = $header->getData();
        } elseif ($header instanceof Arrayable) {
            $header = $header->toArray();
        } elseif (! is_iterable($header)) {
            throw InvalidArgumentException::make('Header must be an array, Arrayable, or DataObject.');
        }

        // Normalize each entry, converting it into HeaderInterface if necessary
        $normalized = [];

        foreach ($header as $key => $value) {
            // If the value is already a HeaderInterface, keep it as is
            $normalized[] = $value instanceof HeaderInterface
                ? $value
                : $this->createHeaderObject([
                    HeaderInterface::KEY => $key,
                    HeaderInterface::VALUE => $value,
                ]);
        }

        return $normalized;
    }

    /**
     * Create a HeaderInterface instance from an array or string input.
     *
     * Converts the input into a HeaderInterface instance.
     *
     * @param array $header The header as key-value pairs.
     *
     * @return HeaderInterface The created HeaderInterface instance.
     */
    private function createHeaderObject(array $header): HeaderInterface
    {
        // Create a new HeaderInterface instance using the factory.
        $headerObject = $this->headerFactory->create();

        // Set the key and value in the HeaderInterface object.
        $headerObject->setKey($header[HeaderInterface::KEY] ?? '');
        $headerObject->setValue($header[HeaderInterface::VALUE] ?? '');

        return $headerObject;
    }
}
