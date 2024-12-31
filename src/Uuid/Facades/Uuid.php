<?php

declare(strict_types=1);

namespace Maginium\Framework\Uuid\Facades;

use Maginium\Framework\Support\Facade;
use Maginium\Framework\Uuid\Interfaces\UuidInterface;

/**
 * Facade for interacting with the UUID service.
 *
 * Provides static access to UUID generation and validation methods.
 *
 * @method static string generate(int $version = UUIDVersion::V4, ?string $namespace = null, ?string $name = null) Generate a UUID of the specified version (1, 3, 4, or 5).
 * @method static string orderedUuid() Generate an ordered UUID for lexicographical sorting (useful for database indexing).
 * @method static string uuid1() Generate a UUID version 1 based on timestamp and node information.
 * @method static string uuid3(string $namespace, string $name) Generate a UUID version 3 using a namespace and name (MD5 hash).
 * @method static string uuid4() Generate a UUID version 4 using random numbers (most common type).
 * @method static string uuid5(string $namespace, string $name) Generate a UUID version 5 using a namespace and name (SHA-1 hash).
 * @method static string namespaceUuid(string $namespace, string $name) Generate a namespace-based UUID (similar to uuid5).
 * @method static bool isValid(string $uuid) Validate whether a given string is a valid UUID.
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
