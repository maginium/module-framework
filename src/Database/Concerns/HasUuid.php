<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Support\Facades\Uuid;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Maginium\Framework\Uuid\Enums\UUIDVersion;
use Maginium\Framework\Uuid\Interfaces\UuidInterface;

/**
 * Trait HasUuid.
 *
 * This trait provides functionality to automatically generate and manage UUIDs for models.
 * It includes methods for getting and setting UUIDs, initializing UUIDs on model creation,
 * and generating UUIDs based on specified versions.
 *
 * @property int|null $uuidVersion The version of the UUID to be used (e.g., V1, V3, V4, V5).
 * @property string|null $uuidKey The name of the field where the UUID will be stored.
 */
trait HasUuid
{
    /**
     * Get the UUID for the model.
     *
     * This method retrieves the UUID from the model's data storage.
     * If the UUID is not set, it returns null.
     *
     * @return string|null The UUID as a string, or null if not set.
     */
    public function getUuid(): ?string
    {
        return $this->getData($this->getUuidKey()) ?? null;
    }

    /**
     * Set the UUID for the model.
     *
     * This method allows setting a specific UUID value in the model's data storage.
     *
     * @param string $uuid The UUID to be set.
     *
     * @return $this The current instance of the model for method chaining.
     */
    public function setUuid(string $uuid): self
    {
        $this->setData($this->getUuidKey(), $uuid);

        return $this;
    }

    /**
     * Boot the UUID trait for an model.
     *
     * This method is automatically called when the trait is used.
     * It listens for the 'creating' event and ensures a valid UUID version is set.
     *
     * @throws LocalizedException If the UUID version is invalid or not set.
     */
    public function bootHasUuid(): void
    {
        // Get the UUID version from the model
        $uuidVersion = $this->getUuidVersion();

        // Validate the UUID version, ensuring it is provided and valid
        if (Validator::isEmpty($uuidVersion) || ! UUIDVersion::hasValue((string)$uuidVersion)) {
            // Throw an exception if the UUID version is invalid
            throw LocalizedException::make(__('Invalid UUID version.'));
        }
    }

    /**
     * Initializes UUID for the model if not already set.
     *
     * This method is called when the model is being created.
     * It generates a UUID based on the specified version if not already set.
     */
    public function initializeHasUuids(): void
    {
        // Enable the use of unique IDs (UUIDs) for this model.
        $this->usesUniqueIds = true;

        // Retrieve the UUID version from the model configuration
        $uuidVersion = $this->getUuidVersion();

        // Check if the UUID field name and version are set, and if the UUID is not already set
        if (! Validator::isEmpty($uuidVersion) && $this->isObject()) {
            // Generate a UUID based on the specified version
            $generatedUuid = $this->generateUuid($uuidVersion);

            // Set the generated UUID to the model
            $this->setUuid($generatedUuid);

            // Persist the model to save the UUID in the database
            $this->save();
        }
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * This method specifies which columns should use a UUID as their identifier.
     * By default, it returns the primary key of the model (`$this->getKeyName()`).
     *
     * @return array The columns that will receive UUIDs.
     */
    public function uniqueIds()
    {
        // Use the primary key column as the UUID field.
        return [$this->getKeyName()];
    }

    /**
     * Generate a new UUID for the model.
     *
     * This method generates a new ordered UUID (Universally Unique Identifier)
     * using Laravel's `Str::orderedUuid()` helper, which provides a time-based UUID.
     *
     * @return string The generated UUID.
     */
    public function newUniqueId()
    {
        // Return a new ordered UUID string.
        return (string)Str::orderedUuid();
    }

    /**
     * Get the type of the primary key.
     *
     * This method overrides the default key type to be `string` for models using UUIDs,
     * since UUIDs are non-numeric. If the primary key is listed as a UUID, the key type
     * will be set to 'string', otherwise the default key type is used.
     *
     * @return string The type of the key ('string' for UUIDs, default otherwise).
     */
    public function getKeyType()
    {
        // Return 'string' if the primary key is a UUID, otherwise return the default key type.
        if (in_array($this->getKeyName(), $this->uniqueIds())) {
            return 'string';
        }

        // Use the default key type if not a UUID.
        return $this->keyType;
    }

    /**
     * Determine if the model's primary key is auto-incrementing.
     *
     * UUIDs are not auto-incrementing, so this method returns `false` if the
     * primary key is a UUID. Otherwise, it will return the default value of the
     * `$incrementing` property.
     *
     * @return bool True if the IDs are incrementing, false if using UUIDs.
     */
    public function getIncrementing()
    {
        // Return false for UUIDs, as they are not auto-incrementing.
        if (in_array($this->getKeyName(), $this->uniqueIds())) {
            return false;
        }

        // Use the default incrementing behavior if not a UUID.
        return $this->incrementing;
    }

    /**
     * Generate a UUID based on the specified version.
     *
     * This method creates a UUID using the specified version and,
     * for versions 3 and 5, it uses the provided namespace and name.
     *
     * @param int $version The UUID version to use (e.g., V1, V3, V4, V5).
     * @param string|null $ns The namespace UUID for versions 3 and 5 (if applicable).
     * @param string|null $name The name for versions 3 and 5 (if applicable).
     *
     * @return string The generated UUID as a string.
     */
    private function generateUuid(int $version, ?string $ns = null, ?string $name = null): string
    {
        // Generate and return the UUID based on the specified version
        return match ($version) {
            UUIDVersion::V1 => Uuid::uuid1(), // Generate a UUID V1
            UUIDVersion::V3 => Uuid::uuid3($ns, $name), // Generate a UUID V3 with namespace and name
            UUIDVersion::V4 => Uuid::uuid4(), // Generate a UUID V4
            UUIDVersion::V5 => Uuid::uuid5($ns, $name), // Generate a UUID V5 with namespace and name
            default => Uuid::uuid4(),  // Default to generating a UUID V4 if the version is unrecognized
        };
    }

    /**
     * Get the UUID version to use when generating UUIDs.
     *
     * This method retrieves the UUID version set for the model,
     * defaulting to UUID V4 if not specified.
     *
     * @return int The UUID version (defaults to V4).
     */
    private function getUuidVersion(): int
    {
        // Return the UUID version or default to V4
        return static::$uuidVersion ?? UUIDVersion::V4;
    }

    /**
     * Get the name of the UUID field for the model.
     *
     * This method retrieves the name of the field where the UUID is stored,
     * defaulting to a standard interface-defined name if not specified.
     *
     * @return string The name of the UUID field (defaults to the UUID interface constant).
     */
    private function getUuidKey(): string
    {
        // Return the UUID field name or default to the interface constant
        return static::$uuidKey ?? UuidInterface::UUID;
    }
}
