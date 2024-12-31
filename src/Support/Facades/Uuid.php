<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Support\Facade;
use Maginium\Framework\Uuid\Interfaces\UuidInterface;

/**
 * Facade for interacting with the UUID service.
 *
 * @method static string generate(int $version = UUIDVersion::V4, ?string $namespace = null, ?string $name = null)
 *     Generates a UUID with the specified version.
 *     Parameters:
 *     - $version: The version of the UUID to generate (default is version 4).
 *     - $namespace: Optional. The namespace for UUID version 3 or 5 generation.
 *     - $name: Optional. The name for UUID version 3 or 5 generation.
 *     Returns:
 *     - string: The generated UUID.
 * @method static string orderedUuid()
 *     Generates a UUID (Universally Unique Identifier) using the orderedUuid method.
 *     Returns:
 *     - string: The generated UUID.
 * @method static string uuid1()
 *     Generates a UUID version 1.
 *     Returns:
 *     - string: The generated UUID.
 * @method static string uuid3(string $namespace, string $name)
 *     Generates a UUID version 3 based on a namespace and a name.
 *     Parameters:
 *     - $namespace: The namespace for UUID version 3 generation.
 *     - $name: The name for UUID version 3 generation.
 *     Returns:
 *     - string: The generated UUID.
 * @method static string uuid4()
 *     Generates a UUID version 4.
 *     Returns:
 *     - string: The generated UUID.
 * @method static string uuid5(string $namespace, string $name)
 *     Generates a UUID version 5 based on a namespace and a name.
 *     Parameters:
 *     - $namespace: The namespace for UUID version 5 generation.
 *     - $name: The name for UUID version 5 generation.
 *     Returns:
 *     - string: The generated UUID.
 * @method static string namespaceUuid(string $namespace, string $name)
 *     Generates a UUID based on a namespace and a name.
 *     Parameters:
 *     - $namespace: The namespace for UUID generation.
 *     - $name: The name for UUID generation.
 *     Returns:
 *     - string: The generated UUID.
 * @method static bool isValidUuid(string $uuid)
 *     Validates whether a given string is a valid UUID.
 *     Parameters:
 *     - $uuid: The UUID string to validate.
 *     Returns:
 *     - bool: True if the UUID is valid, false otherwise.
 *
 * @see UuidInterface
 */
class Uuid extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return UuidInterface::class;
    }
}
