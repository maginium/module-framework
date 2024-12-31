<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination\Interfaces;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Interface LengthAwarePaginatorInterface.
 *
 * Extends the basic paginator interface to include methods for retrieving
 * total items and calculating ranges, allowing pagination to be aware of the
 * full length of available items.
 */
interface LengthAwarePaginatorInterface extends ArrayAccess, Countable, IteratorAggregate, Jsonable, JsonSerializable, LengthAwarePaginator
{
    /**
     * Get all of the items in the collection.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array;

    /**
     * Convert paginator data into an associative array.
     *
     * This method prepares and returns the complete paginator data, including pagination metadata and
     * the actual data items if requested. The metadata includes the pagination path, total items, current
     * and last pages, and the links for navigation. The data items are added optionally.
     *
     * @param  bool  $withData  Whether to include the data items in the result.
     *
     * @return array Array representation of the paginator, including metadata and optionally the data items.
     */
    public function toArray(bool $withData = false): array;
}
