<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Concerns;

use Illuminate\Database\Query\Expression;
use Maginium\Framework\Pagination\Cursor;
use Maginium\Framework\Pagination\CursorPaginator;
use Maginium\Framework\Pagination\Interfaces\CursorPaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;
use Maginium\Framework\Pagination\LengthAwarePaginator;
use Maginium\Framework\Pagination\Paginator;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Str;

/**
 * Trait BuildsQueries.
 *
 * This trait contains methods related to building queries, particularly pagination, for Elasticsearch operations.
 * It provides support for different types of pagination (Cursor, LengthAware, Simple) and includes logic for
 * cursor pagination to handle large data sets efficiently.
 */
trait BuildsQueries
{
    /**
     * Paginate the given query using a cursor paginator.
     *
     * This method supports cursor pagination, which is useful when working with large datasets.
     * It allows you to paginate using a cursor, providing efficient and ordered access to data without
     * needing to remember previous page states.
     *
     * @param  int  $perPage The number of items per page.
     * @param  array|string  $columns The columns to select. Defaults to all columns ('*').
     * @param  string  $cursorName The name of the cursor. Defaults to 'cursor'.
     * @param  Cursor|string|null  $cursor The cursor to paginate with. If not provided, it will resolve the current cursor.
     *
     * @return CursorPaginatorInterface Returns the cursor paginator instance.
     */
    protected function paginateUsingCursor($perPage, $columns = ['*'], $cursorName = 'cursor', $cursor = null): CursorPaginatorInterface
    {
        // Check and resolve the cursor if it's not already an instance of Cursor
        if (! $cursor instanceof Cursor) {
            $cursor = is_string($cursor)
                ? Cursor::fromEncoded($cursor) // Decode the cursor if it's a string
                : CursorPaginator::resolveCurrentCursor($cursorName, $cursor); // Resolve current cursor
        }

        // Ensure order is correct for cursor pagination
        $orders = $this->ensureOrderForCursorPagination($cursor !== null && $cursor->pointsToPreviousItems());

        // If cursor is provided, we need to set conditions to paginate based on the cursor
        if ($cursor !== null) {
            // Reset union bindings to add cursor conditions correctly
            $this->setBindings([], 'union');

            // Function to add cursor conditions to the query
            $addCursorConditions = function(self $builder, $previousColumn, $originalColumn, $i) use (&$addCursorConditions, $cursor, $orders) {
                $unionBuilders = $builder->getUnionBuilders();

                // Add condition for the previous column if it exists
                if ($previousColumn !== null) {
                    $originalColumn ??= $this->getOriginalColumnNameForCursorPagination($this, $previousColumn);

                    // Add where condition to filter by the cursor's previous column value
                    $builder->where(
                        Str::contains($originalColumn, ['(', ')']) ? new Expression($originalColumn) : $originalColumn,
                        '=',
                        $cursor->parameter($previousColumn),
                    );

                    // Apply the same condition to the union builders
                    $unionBuilders->each(function($unionBuilder) use ($previousColumn, $cursor) {
                        $unionBuilder->where(
                            $this->getOriginalColumnNameForCursorPagination($unionBuilder, $previousColumn),
                            '=',
                            $cursor->parameter($previousColumn),
                        );

                        $this->addBinding($unionBuilder->getRawBindings()['where'], 'union');
                    });
                }

                // Add the cursor condition for the current column based on the order
                $builder->where(function(self $secondBuilder) use ($addCursorConditions, $cursor, $orders, $i, $unionBuilders) {
                    ['column' => $column, 'direction' => $direction] = $orders[$i];

                    $originalColumn = $this->getOriginalColumnNameForCursorPagination($this, $column);

                    // Apply where condition based on cursor's position (before or after)
                    $secondBuilder->where(
                        Str::contains($originalColumn, ['(', ')']) ? new Expression($originalColumn) : $originalColumn,
                        $direction === 'asc' ? '>' : '<',
                        $cursor->parameter($column),
                    );

                    // If there are more order columns, apply the cursor condition to the next one
                    if ($i < $orders->count() - 1) {
                        $secondBuilder->orWhere(function(self $thirdBuilder) use ($addCursorConditions, $column, $originalColumn, $i) {
                            $addCursorConditions($thirdBuilder, $column, $originalColumn, $i + 1);
                        });
                    }

                    // Apply the conditions to the union builders as well
                    $unionBuilders->each(function($unionBuilder) use ($column, $direction, $cursor, $i, $orders, $addCursorConditions) {
                        $unionWheres = $unionBuilder->getRawBindings()['where'];

                        $originalColumn = $this->getOriginalColumnNameForCursorPagination($unionBuilder, $column);
                        $unionBuilder->where(function($unionBuilder) use ($column, $direction, $cursor, $i, $orders, $addCursorConditions, $originalColumn, $unionWheres) {
                            // Apply where condition to the union builder
                            $unionBuilder->where(
                                $originalColumn,
                                $direction === 'asc' ? '>' : '<',
                                $cursor->parameter($column),
                            );

                            // Apply further conditions if needed
                            if ($i < $orders->count() - 1) {
                                $unionBuilder->orWhere(function(self $fourthBuilder) use ($addCursorConditions, $column, $originalColumn, $i) {
                                    $addCursorConditions($fourthBuilder, $column, $originalColumn, $i + 1);
                                });
                            }

                            // Bind union query conditions
                            $this->addBinding($unionWheres, 'union');
                            $this->addBinding($unionBuilder->getRawBindings()['where'], 'union');
                        });
                    });
                });
            };

            // Add the cursor conditions for pagination
            $addCursorConditions($this, null, null, 0);
        }

        // Set the pagination limit (one extra item to detect if there's a next page)
        $this->limit($perPage + 1);

        // Return the cursor paginator with the results
        return $this->cursorPaginator($this->get($columns), $perPage, $cursor, [
            'path' => Paginator::resolveCurrentPath(),
            'cursorName' => $cursorName,
            'parameters' => $orders->pluck('column')->toArray(),
        ]);
    }

    /**
     * Create a new length-aware paginator instance.
     *
     * This paginator is used when you need to paginate results with an accurate count of the total items.
     * It provides information about the total number of items, the number of items per page, and the current page.
     *
     * @param  Collection  $items The items to paginate.
     * @param  int  $total The total number of items.
     * @param  int  $perPage The number of items per page.
     * @param  int  $currentPage The current page number.
     * @param  array  $options Additional options for the paginator.
     *
     * @return LengthAwarePaginatorInterface Returns the LengthAwarePaginator instance.
     */
    protected function paginator($items, $total, $perPage, $currentPage, $options): LengthAwarePaginatorInterface
    {
        return LengthAwarePaginator::make(compact(
            'items',
            'total',
            'perPage',
            'currentPage',
            'options',
        ));
    }

    /**
     * Create a new simple paginator instance.
     *
     * This paginator is used when you need to paginate results without requiring the total count of items.
     * It is more lightweight than a LengthAwarePaginator.
     *
     * @param  Collection  $items The items to paginate.
     * @param  int  $perPage The number of items per page.
     * @param  int  $currentPage The current page number.
     * @param  array  $options Additional options for the paginator.
     *
     * @return PaginatorInterface Returns the simple Paginator instance.
     */
    protected function simplePaginator($items, $perPage, $currentPage, $options): PaginatorInterface
    {
        return Paginator::make(compact(
            'items',
            'perPage',
            'currentPage',
            'options',
        ));
    }

    /**
     * Create a new cursor paginator instance.
     *
     * This paginator is used when you need to paginate results using a cursor, which is efficient for large datasets
     * as it does not require maintaining the entire state of the previous page.
     *
     * @param  Collection  $items The items to paginate.
     * @param  int  $perPage The number of items per page.
     * @param  Cursor  $cursor The cursor to paginate with.
     * @param  array  $options Additional options for the paginator.
     *
     * @return CursorPaginatorInterface Returns the CursorPaginator instance.
     */
    protected function cursorPaginator($items, $perPage, $cursor, $options): CursorPaginatorInterface
    {
        return CursorPaginator::make(compact(
            'items',
            'perPage',
            'cursor',
            'options',
        ));
    }
}
