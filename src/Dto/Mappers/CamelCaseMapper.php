<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Mappers;

use Maginium\Framework\Dto\Interfaces\NameMapperInterface;
use Maginium\Framework\Support\Str;

/**
 * Class `CamelCaseMapper` implements the `NameMapperInterface` and provides
 * a mapping function that transforms a given name to camelCase format.
 *
 * This is useful when working with data transfer objects (DTOs) where
 * the property names may need to be transformed for consistency or
 * compatibility with APIs that use camelCase.
 */
class CamelCaseMapper implements NameMapperInterface
{
    /**
     * Maps the given name to camelCase format.
     *
     * This method takes a string or integer as input and transforms it
     * into a camelCase format using the `Str::camel()` method from the
     * `Str` class. It is typically used for converting data field names
     * from snake_case, kebab-case, or any other format to camelCase.
     *
     * @param int|string $name The name to be mapped. This can either be a string
     *                         (e.g., "user_name") or an integer (e.g., 0 for array index).
     *
     * @return string|int The mapped name in camelCase format. If the input is an
     *                    integer, it is returned unchanged.
     */
    public function map(int|string $name): string|int
    {
        // Check if the input is an integer (e.g., an array index), if so return it unchanged.
        // Otherwise, use the Str::camel() function to transform the string into camelCase format.
        return Str::camel($name);
    }
}
