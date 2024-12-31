<?php

declare(strict_types=1);

namespace Maginium\Framework\Uuid\Interfaces;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Uuid\Enums\UUIDVersion;

/**
 * Interface for UUID service class.
 */
interface UuidInterface
{
    /**
     * UUID Field name.
     */
    public const UUID = 'uuid';

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
    public function generate(int $version = UUIDVersion::V4, ?string $namespace = null, ?string $name = null): string;

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
    public function orderedUuid(): string;

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
    public function uuid1(): string;

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
    public function uuid3(string $namespace, string $name): string;

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
    public function uuid4(): string;

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
    public function uuid5(string $namespace, string $name): string;

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
    public function namespaceUuid(string $namespace, string $name): string;

    /**
     * Validates whether a given string is a valid UUID.
     *
     * This method attempts to parse the string as a UUID using the CoreUuid library.
     * If the parsing is successful, it returns true. Otherwise, it returns false.
     *
     * @param  string  $uuid  The UUID string to validate.
     *
     * @return bool - Returns true if the UUID is valid, false otherwise.
     */
    public function isValid(string $uuid): bool;
}
