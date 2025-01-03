<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Mappers;

use Maginium\Framework\Dto\Interfaces\NameMapperInterface;
use Maginium\Framework\Support\Str;

/**
 * Class `StudlyCaseMapper` implements the `NameMapperInterface` and provides
 * a mapping function that converts a given name to StudlyCase format.
 *
 * StudlyCase (also known as PascalCase) capitalizes the first letter of each word
 * in a string and removes spaces or other delimiters. It is commonly used for
 * class names in various programming languages.
 */
class StudlyCaseMapper implements NameMapperInterface
{
    /**
     * Maps the given name to StudlyCase (PascalCase) format.
     *
     * This method takes the input name (either a string or an integer) and
     * transforms it into StudlyCase format using the `Str::studly` method.
     *
     * For example:
     * - "user_name" becomes "UserName"
     * - "user123" remains "User123"
     *
     * @param int|string $name The name to be transformed into StudlyCase format.
     *
     * @return string The input name in StudlyCase (PascalCase) format.
     */
    public function map(int|string $name): string|int
    {
        // Use the Str helper class to convert the name into StudlyCase format.
        return Str::studly($name);
    }
}
