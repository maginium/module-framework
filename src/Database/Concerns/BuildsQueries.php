<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Maginium\Framework\Pagination\Cursor;
use Maginium\Framework\Pagination\CursorPaginator;
use Maginium\Framework\Pagination\Interfaces\CursorPaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;
use Maginium\Framework\Pagination\LengthAwarePaginator;
use Maginium\Framework\Pagination\Paginator;
use Maginium\Framework\Support\Collection;

/**
 * Trait BuildsQueries.
 *
 * Provides methods to create various types of paginator instances,
 * enabling flexible pagination strategies including length-aware, simple, and cursor-based pagination.
 *
 * This trait is intended to be used within query-building classes
 * or repositories that require consistent and reusable pagination utilities.
 */
trait BuildsQueries
{
    /**
     * Create a new length-aware paginator instance.
     *
     * A LengthAwarePaginator provides accurate pagination details, including:
     * - Total number of items.
     * - Number of items per page.
     * - The current page number.
     * - Calculations for total pages and offsets.
     *
     * Use this paginator when you need comprehensive pagination details.
     *
     * @param  Collection  $items The items to paginate.
     * @param int $total The total number of items in the collection.
     * @param int $perPage The number of items per page.
     * @param int $currentPage The current page number.
     * @param array $options Additional options (e.g., `path`, `query`, `fragment`).
     *
     * @return LengthAwarePaginatorInterface Returns an instance of a LengthAwarePaginator.
     */
    protected function paginator($items, $total, $perPage, $currentPage, $options): LengthAwarePaginatorInterface
    {
        return LengthAwarePaginator::make([
            'items' => $items,
            'total' => $total,
            'perPage' => $perPage,
            'options' => $options,
            'currentPage' => $currentPage,
        ]);
    }

    /**
     * Create a new simple paginator instance.
     *
     * A SimplePaginator is a lightweight alternative to LengthAwarePaginator.
     * It does not calculate the total count of items, making it faster and less resource-intensive.
     *
     * Use this paginator for large datasets where a total count is unnecessary.
     *
     * @param Collection  $items The items to paginate.
     * @param int $perPage The number of items per page.
     * @param int $currentPage The current page number.
     * @param array $options Additional options (e.g., `path`, `query`, `fragment`).
     *
     * @return PaginatorInterface Returns an instance of a SimplePaginator.
     */
    protected function simplePaginator($items, $perPage, $currentPage, $options): PaginatorInterface
    {
        return Paginator::make([
            'items' => $items,
            'perPage' => $perPage,
            'options' => $options,
            'currentPage' => $currentPage,
        ]);
    }

    /**
     * Create a new cursor-based paginator instance.
     *
     * A CursorPaginator allows efficient pagination for large datasets,
     * using a cursor to determine the starting point of each page instead of relying on offsets.
     * This method avoids the performance overhead associated with offset-based pagination.
     *
     * Use this paginator when working with high-performance APIs or large databases.
     *
     * @param Collection  $items The items to paginate.
     * @param int $perPage The number of items per page.
     * @param Cursor $cursor The cursor object representing the pagination state.
     * @param array $options Additional options (e.g., `path`, `query`, `fragment`).
     *
     * @return CursorPaginatorInterface Returns an instance of a CursorPaginator.
     */
    protected function cursorPaginator($items, $perPage, $cursor, $options): CursorPaginatorInterface
    {
        return CursorPaginator::make([
            'items' => $items,
            'cursor' => $cursor,
            'perPage' => $perPage,
            'options' => $options,
        ]);
    }
}
