<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Interfaces;

/**
 * Interface for classes that map names or indices to custom names or indices.
 *
 * The `NameMapperInterface` defines the contract for any class that provides a
 * mapping functionality for transforming a given name (either a string or integer)
 * into a new name or index. This can be used for various purposes, such as mapping
 * field names in data transfer objects (DTOs) or mapping array indices.
 */
interface NameMapperInterface
{
    /**
     * Map a given name or index to a new name or index.
     *
     * This method should transform the provided name or index to a custom name or
     * index, as per the implementation of the interface. The name or index can
     * either be a string (e.g., 'user_name') or an integer (e.g., 0 for array indices).
     *
     * @param string|int $name The name or index to map.
     *
     * @return string|int The mapped name or index.
     */
    public function map(string|int $name): string|int;
}
