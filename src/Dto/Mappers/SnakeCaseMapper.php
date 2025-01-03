<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Mappers;

use Maginium\Framework\Dto\Interfaces\NameMapperInterface;
use Maginium\Framework\Support\Str;

/**
 * Class `SnakeCaseMapper` implements the `NameMapperInterface` and provides
 * a mapping function that converts a given name to snake_case format.
 *
 * This class is useful when you need to transform names into a snake_case
 * convention, commonly used in databases and certain API formats.
 */
class SnakeCaseMapper implements NameMapperInterface
{
    /**
     * Maps the given name to a snake_case format.
     *
     * This method takes the input name (either a string or an integer) and
     * transforms it into snake_case format using the `Str::snake` method.
     *
     * For example:
     * - "UserName" becomes "user_name"
     * - "user123" remains "user123"
     *
     * @param int|string $name The name to be transformed into snake_case format.
     *
     * @return string The input name in snake_case format.
     */
    public function map(int|string $name): string|int
    {
        // Use the Str helper class to convert the name into snake_case format.
        return Str::snake($name);
    }
}
