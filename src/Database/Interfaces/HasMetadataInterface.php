<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

/**
 * Interface HasMetadataInterface.
 *
 * This interface defines the contract for managing model metadata, including
 * methods for getting, setting, checking, and removing metadata associated
 * with a model.
 */
interface HasMetadataInterface
{
    /**
     * Constant for the metadata attribute.
     */
    public const METADATA = 'metadata';

    /**
     * Retrieve a metadata value by its key.
     *
     * This method allows access to all metadata in the model by fetching all the custom attributes,
     * which can be treated as metadata. If no metadata is set, it will return an empty array.
     *
     * @return array|null Returns the metadata value or null if not set.
     */
    public function getMetadata(): ?array;

    /**
     * Retrieve a specific metadata value by its key.
     *
     * This method will fetch the value associated with a given key in the metadata storage.
     * It will return null if the metadata doesn't exist for the specified key.
     *
     * @param string $key The key associated with the metadata.
     *
     * @return mixed Returns the metadata value or null if not set.
     */
    public function getMetaa(string $key): mixed;

    /**
     * Check if metadata exists for a given key.
     *
     * @param string $key The key associated with the metadata.
     *
     * @return bool Returns true if metadata exists, otherwise false.
     */
    public function hasMetaa(string $key): bool;

    /**
     * Set a specific piece of metadata by key and value.
     *
     * @param string $key The key for the metadata.
     * @param mixed $value The value to associate with the key.
     *
     * @return HasMetadataInterface Returns the current instance for method chaining.
     */
    public function setMetaa(string $key, mixed $value): self;

    /**
     * Set multiple metadata values at once.
     *
     * @param array $metadata Key-value pairs of metadata to be set.
     *
     * @return HasMetadataInterface Returns the current instance for method chaining.
     */
    public function setMetadata(array $metadata): self;

    /**
     * Remove a specific metadata entry by key.
     *
     * @param string $key The key of the metadata to remove.
     *
     * @return HasMetadataInterface Returns the current instance for method chaining.
     */
    public function removeMeta(string $key): self;

    /**
     * Remove all metadata.
     *
     * @return HasMetadataInterface Returns the current instance for method chaining.
     */
    public function removeMetadata(): self;
}
