<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Traits;

use Illuminate\Contracts\Support\Arrayable;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Mail\Interfaces\Data\MetadataInterface;
use Maginium\Framework\Mail\Interfaces\MailerInterface;
use Maginium\Framework\Support\DataObject;

/**
 * Trait HasMetadata.
 *
 * This trait provides methods to manage email metadata within the envelope context.
 * It allows for adding, retrieving, and setting additional metadata for the email.
 * Email metadata can be used for various purposes, such as tracking, custom metadata,
 * or other protocol-related information (e.g., 'Content-Type', 'Subject').
 */
trait HasMetadata
{
    /**
     * Set metadata for the email.
     *
     * This method allows you to add additional metadata to the email, which can be useful for tracking or other purposes.
     *
     * @param array $metadata Additional metadata for the email.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function metadata(array $metadata): MailerInterface
    {
        // Set the email metadata in the internal data store.
        return $this->setMetadata($metadata);
    }

    /**
     * Retrieve the additional metadata for the email.
     *
     * @return MetadataInterface|null Returns an array of key-value pairs representing the metadata.
     */
    public function getMetadata(): ?array
    {
        // Call denormalizeMetadata to return key-value pairs
        return $this->getData(MailerInterface::METADATA);
    }

    /**
     * Set the additional metadata for the email.
     *
     * @param MetadataInterface[]|null $metadata An array of metadata key-value pairs.
     *
     * @return MailerInterface Returns the current instance for method chaining.
     */
    public function setMetadata(?array $metadata): MailerInterface
    {
        // Normalize the metadata.
        $metadata = $this->normalizeMetadata($metadata);

        $this->setData(MailerInterface::METADATA, $metadata);

        return $this;
    }

    /**
     * Determine if the message has the given metadata.
     *
     * Checks if the provided key and value exist in the metadata of the message.
     *
     * @param string $key The metadata key to check.
     * @param string $value The metadata value to check.
     *
     * @return bool Returns true if the metadata key and value exist, otherwise false.
     */
    public function hasMetadata(string $key, string $value): bool
    {
        // Retrieve the metadata from the message.
        $metadata = $this->getData(MailerInterface::METADATA);

        // Check if the key exists and the value matches.
        return isset($metadata[$key]) && (string)$metadata[$key] === $value;
    }

    /**
     * Normalize the given metadata into MetadataInterface instances.
     *
     * Converts the provided data into an array of MetadataInterface objects. Handles
     * Arrayable and DataObject types by converting them into arrays, and ensures that
     * both key-value pair arrays and existing MetadataInterface objects are properly processed.
     *
     * @param array|Arrayable|DataObject|null $metadata The metadata as key-value pairs or an object.
     *
     * @return MetadataInterface[] An array of MetadataInterface instances.
     */
    private function normalizeMetadata(array|Arrayable|DataObject|null $metadata): array
    {
        // If the metadata is null or empty, return an empty array
        if (! $metadata) {
            return [];
        }

        // Normalize metadata to an array if it's a DataObject or Arrayable
        if ($metadata instanceof DataObject) {
            $metadata = $metadata->getData();
        } elseif ($metadata instanceof Arrayable) {
            $metadata = $metadata->toArray();
        } elseif (! is_iterable($metadata)) {
            throw InvalidArgumentException::make('Metadata must be an array, Arrayable, or DataObject.');
        }

        // Normalize each entry, converting it into MetadataInterface if necessary
        $normalized = [];

        foreach ($metadata as $key => $value) {
            // If the value is already a MetadataInterface, keep it as is
            $normalized[] = $value instanceof MetadataInterface
                ? $value
                : $this->createMetadataObject([
                    MetadataInterface::KEY => $key,
                    MetadataInterface::VALUE => $value,
                ]);
        }

        return $normalized;
    }

    /**
     * Create a MetadataInterface instance from an array or string input.
     *
     * Converts the input into a MetadataInterface instance.
     *
     * @param array $metadata The metadata as key-value pairs.
     *
     * @return MetadataInterface The created MetadataInterface instance.
     */
    private function createMetadataObject(array $metadata): MetadataInterface
    {
        // Create a new MetadataInterface instance using the factory.
        $metadataObject = $this->metadataFactory->create();

        // Set the key and value in the MetadataInterface object.
        $metadataObject->setKey($metadata[MetadataInterface::KEY] ?? '');
        $metadataObject->setValue($metadata[MetadataInterface::VALUE] ?? '');

        return $metadataObject;
    }
}
