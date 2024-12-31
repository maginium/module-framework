<?php

declare(strict_types=1);

namespace Maginium\Framework\Uuid;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Uuid\Enums\UUIDVersion;
use Maginium\Framework\Uuid\Interfaces\UuidInterface;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Exception\UnsupportedOperationException;
use Ramsey\Uuid\Uuid as BaseUuid;

/**
 * Class Uuid.
 *
 * The `Uuid` class implements the `UuidInterface` to provide methods for generating
 * and validating Universally Unique Identifiers (UUIDs). It supports multiple versions of
 * UUIDs, ensuring flexibility and adherence to standards for unique identifier generation.
 */
class UuidManager implements UuidInterface
{
    /**
     * Generates a UUID (Universally Unique Identifier) with the specified version.
     *
     * @param  int  $version  The UUID version to generate (1, 3, 4, or 5).
     * @param  string|null  $namespace  The namespace UUID for versions 3 and 5 (optional).
     * @param  string|null  $name  The name for which to generate the UUID for versions 3 and 5 (optional).
     *
     * @throws Exception If the specified version is invalid or there is an issue generating the UUID.
     *
     * @return string The generated UUID.
     */
    public function generate(int $version = UUIDVersion::V4, ?string $namespace = null, ?string $name = null): string
    {
        //  Handle different UUID versions
        return match ($version) {
            //  Generate UUID version 1
            UUIDVersion::V1 => $this->uuid1(),
            //  Generate UUID version 3
            UUIDVersion::V3 => $this->uuid3($namespace, $name),
            //  Generate UUID version 4
            UUIDVersion::V4 => $this->uuid4(),
            //  Generate UUID version 5
            UUIDVersion::V5 => $this->uuid5($namespace, $name),
            default => //  Throw the exception
            throw InvalidArgumentException::make(__('Invalid UUID version specified. Supported versions are 1, 3, 4, and 5.')),
        };
    }

    /**
     * Generates a UUID (Universally Unique Identifier) using the orderedUuid method.
     *
     * This method utilizes a custom method to generate a UUID in an ordered fashion, ensuring that
     * the UUIDs are lexicographically sortable, which is useful for indexing purposes in databases.
     *
     * @throws Exception If a suitable random number source is not found or if there is an issue
     *                   with UUID generation.
     *
     * @return string The generated UUID in string format.
     */
    public function orderedUuid(): string
    {
        try {
            //  Generate a UUID using the orderedUuid method, which produces a time-ordered UUID.
            $uuid = Str::orderedUuid()->toString();

            //  Validate the generated UUID to ensure it's in the correct format.
            if (! self::isValid($uuid)) {
                //  Throw an exception if the generated UUID is invalid.
                throw new InvalidUuidStringException('Generated UUID is not valid.');
            }

            return $uuid;
        } catch (InvalidUuidStringException $e) {
            //  Handle exceptions related to invalid UUIDs
            //  Throw the exception
            throw LocalizedException::make(__('Error generating UUID.'), $e);
        } catch (InvalidArgumentException|UnsupportedOperationException $e) {
            //  Handle exceptions related to unsupported UUID versions
            //  Throw the exception
            throw LocalizedException::make(__('Unable to generate a UUID version 1.'), $e);
        }
    }

    /**
     * Generates a UUID (Universally Unique Identifier) using the random_bytes function.
     *
     * This method generates a UUID version 1 based on the current timestamp and node (usually the MAC address).
     * If the UUID is invalid, an exception is thrown.
     *
     * @throws Exception If a suitable random number source is not found or if there is any issue generating the UUID.
     *
     * @return string - The generated UUID in string format.
     */
    public function uuid1(): string
    {
        try {
            // Generate a UUID version 1 using BaseUuid
            $uuid = BaseUuid::uuid1()->toString();

            // Check if the generated UUID is valid
            if (! self::isValid($uuid)) {
                // Throw an exception if the UUID is invalid
                throw new InvalidUuidStringException('Generated UUID is not valid.');
            }

            return $uuid;
        } catch (InvalidUuidStringException $e) {
            // Catch and rethrow the exception with a localized message
            throw LocalizedException::make(__('Error generating UUID.'), $e);
        } catch (InvalidArgumentException|UnsupportedOperationException $e) {
            // Catch and rethrow any argument or operation exceptions
            throw LocalizedException::make(__('Unable to generate a UUID version 1.'), $e);
        }
    }

    /**
     * Generates a UUID (Universally Unique Identifier) version 3 based on a namespace and a name.
     *
     * This method generates a UUID version 3, which is based on hashing a namespace and name using MD5.
     * If the UUID is invalid, an exception is thrown.
     *
     * @param  string  $namespace  The namespace UUID used for generating the UUID.
     * @param  string  $name  The name used to generate the UUID.
     *
     * @throws Exception If there is an issue generating the UUID.
     *
     * @return string - The generated UUID version 3 in string format.
     */
    public function uuid3(string $namespace, string $name): string
    {
        try {
            // Generate a UUID version 3 using the namespace and name
            $uuid = BaseUuid::uuid3(BaseUuid::fromString($namespace), $name)->toString();

            // Check if the generated UUID is valid
            if (! self::isValid($uuid)) {
                // Throw an exception if the UUID is invalid
                throw new InvalidUuidStringException('Generated UUID is not valid.');
            }

            return $uuid;
        } catch (InvalidUuidStringException $e) {
            // Catch and rethrow the exception with a localized message
            throw LocalizedException::make(__('Error generating UUID.'), $e);
        } catch (InvalidArgumentException|UnsupportedOperationException $e) {
            // Catch and rethrow any argument or operation exceptions
            throw LocalizedException::make(__('Unable to generate a UUID version 3.'), $e);
        }
    }

    /**
     * Generates a UUID (Universally Unique Identifier) version 4.
     *
     * This method generates a UUID version 4 using random numbers. This is the most commonly used version of UUID.
     * If the UUID is invalid, an exception is thrown.
     *
     * @throws Exception If there is an issue generating the UUID.
     *
     * @return string - The generated UUID version 4 in string format.
     */
    public function uuid4(): string
    {
        try {
            // Generate a UUID version 4 using random values
            $uuid = BaseUuid::uuid4()->toString();

            // Check if the generated UUID is valid
            if (! self::isValid($uuid)) {
                // Throw an exception if the UUID is invalid
                throw new InvalidUuidStringException('Generated UUID is not valid.');
            }

            return $uuid;
        } catch (InvalidUuidStringException $e) {
            // Catch and rethrow the exception with a localized message
            throw LocalizedException::make(__('Error generating UUID.'), $e);
        } catch (InvalidArgumentException|UnsupportedOperationException $e) {
            // Catch and rethrow any argument or operation exceptions
            throw LocalizedException::make(__('Unable to generate a UUID version 4.'), $e);
        }
    }

    /**
     * Generates a UUID (Universally Unique Identifier) version 5 based on a namespace and a name.
     *
     * This method generates a UUID version 5, which is based on hashing a namespace and name using SHA-1.
     * If the UUID is invalid, an exception is thrown.
     *
     * @param  string  $namespace  The namespace UUID used for generating the UUID.
     * @param  string  $name  The name used to generate the UUID.
     *
     * @throws Exception If there is an issue generating the UUID.
     *
     * @return string - The generated UUID version 5 in string format.
     */
    public function uuid5(string $namespace, string $name): string
    {
        try {
            // Generate a UUID version 5 using the namespace and name
            $uuid = BaseUuid::uuid5(BaseUuid::fromString($namespace), $name)->toString();

            // Check if the generated UUID is valid
            if (! self::isValid($uuid)) {
                // Throw an exception if the UUID is invalid
                throw new InvalidUuidStringException('Generated UUID is not valid.');
            }

            return $uuid;
        } catch (InvalidUuidStringException $e) {
            // Catch and rethrow the exception with a localized message
            throw LocalizedException::make(__('Error generating UUID.'), $e);
        } catch (InvalidArgumentException|UnsupportedOperationException $e) {
            // Catch and rethrow any argument or operation exceptions
            throw LocalizedException::make(__('Unable to generate a UUID version 5.'), $e);
        }
    }

    /**
     * Generates a UUID (Universally Unique Identifier) based on a namespace and a name (similar to uuid5).
     *
     * This method generates a UUID using version 5. It's essentially a convenience method to generate
     * a namespace-based UUID.
     *
     * @param  string  $namespace  The namespace UUID used for generating the UUID.
     * @param  string  $name  The name used to generate the UUID.
     *
     * @throws Exception If there is an issue generating the UUID.
     *
     * @return string - The generated namespace-based UUID in string format.
     */
    public function namespaceUuid(string $namespace, string $name): string
    {
        try {
            // Generate a UUID using the namespace and name (version 5)
            $uuid = BaseUuid::uuid5(BaseUuid::fromString($namespace), $name)->toString();

            // Check if the generated UUID is valid
            if (! self::isValid($uuid)) {
                // Throw an exception if the UUID is invalid
                throw new InvalidUuidStringException('Generated UUID is not valid.');
            }

            return $uuid;
        } catch (InvalidUuidStringException $e) {
            // Catch and rethrow the exception with a localized message
            throw LocalizedException::make(__('Error generating UUID.'), $e);
        } catch (InvalidArgumentException|UnsupportedOperationException $e) {
            // Catch and rethrow any argument or operation exceptions
            throw LocalizedException::make(__('Unable to generate a namespace-based UUID.'), $e);
        }
    }

    /**
     * Validates whether a given string is a valid UUID.
     *
     * This method attempts to parse the string as a UUID using the BaseUuid library.
     * If the parsing is successful, it returns true. Otherwise, it returns false.
     *
     * @param  string  $uuid  The UUID string to validate.
     *
     * @return bool - Returns true if the UUID is valid, false otherwise.
     */
    public function isValid(string $uuid): bool
    {
        try {
            // Try to parse the UUID string
            BaseUuid::isValid($uuid);

            // Return true if the parsing succeeds (valid UUID)
            return true;
        } catch (InvalidUuidStringException $e) {
            // Return false if an InvalidUuidStringException is thrown (invalid UUID)
            return false;
        }
    }
}
