<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Pagination;

use Maginium\Framework\Elasticsearch\Eloquent\Model;
use Maginium\Framework\Pagination\Cursor;
use Maginium\Framework\Pagination\CursorPaginator;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Facades\Container;

/**
 * Class SearchAfterPaginator.
 *
 * Custom paginator for handling cursor-based pagination with "search_after" functionality.
 * Extends the CursorPaginator to provide advanced features like handling the next/previous cursors,
 * showing the correct pagination data, and calculating total records.
 *
 * This paginator is specifically useful for Elasticsearch or other systems that use "search_after" for pagination
 * rather than traditional page numbers.
 */
class SearchAfterPaginator extends CursorPaginator
{
    /**
     * Get the pagination parameters for a given item.
     *
     * @param  Model  $item  The item to retrieve parameters for.
     *
     * @return array  The pagination parameters for the given item.
     */
    public function getParametersForItem($item): array
    {
        // Retrieve the cursor and sort information from the item's meta data.
        $cursor = $item->getMeta()->getCursor();
        $search_after = $item->getMeta()->getSort();

        // Increment the page number.
        $cursor['page']++;

        // Add the search_after parameter for sorting.
        $cursor['next_sort'] = $search_after;

        // Return the modified cursor.
        return $cursor;
    }

    /**
     * Convert the paginator instance to an array.
     *
     * @return array  The paginator's data as an array.
     */
    public function toArray(): array
    {
        return [
            'data' => $this->items->toArray(),  // Convert items to an array.
            'path' => $this->path(),  // The URL path for the current page.
            'per_page' => $this->perPage(),  // Number of items per page.
            'next_cursor' => $this->nextCursor()?->encode(),  // Encode the next cursor if available.
            'next_page_url' => $this->nextPageUrl(),  // URL for the next page.
            'prev_cursor' => $this->previousCursor()?->encode(),  // Encode the previous cursor if available.
            'prev_page_url' => $this->previousPageUrl(),  // URL for the previous page.
            'current_page' => $this->currentPageNumber(),  // The current page number.
            'total' => $this->totalRecords(),  // Total number of records.
            'from' => $this->showingFrom(),  // The starting record number for the current page.
            'to' => $this->showingTo(),  // The ending record number for the current page.
            'last_page' => $this->lastPage(),  // The last page number.
        ];
    }

    /**
     * Get the current page number.
     *
     * @return int  The current page number.
     */
    public function currentPageNumber(): int
    {
        return $this->options['currentPage'];
    }

    /**
     * Get the total number of records.
     *
     * @return int  The total number of records.
     */
    public function totalRecords(): int
    {
        return $this->options['records'];
    }

    /**
     * Get the starting record number for the current page.
     *
     * @return int  The starting record number.
     */
    public function showingFrom(): int
    {
        $perPage = $this->perPage();
        $currentPage = $this->currentPageNumber();

        return ($currentPage - 1) * $perPage + 1;
    }

    /**
     * Get the ending record number for the current page.
     *
     * @return int  The ending record number.
     */
    public function showingTo(): int
    {
        $records = count($this->items);
        $currentPage = $this->currentPageNumber();
        $perPage = $this->perPage();

        return (($currentPage - 1) * $perPage) + $records;
    }

    /**
     * Get the last page number.
     *
     * @return int  The last page number.
     */
    public function lastPage(): int
    {
        return $this->options['totalPages'];
    }

    /**
     * Build the cursor for the previous page.
     *
     * @return Cursor|null  The cursor for the previous page or null if there is no previous page.
     */
    public function previousCursor(): ?Cursor
    {
        // Return null if no cursor is available.
        if (! $this->cursor) {
            return null;
        }

        // Get the current cursor data.
        $current = $this->cursor->toArray();

        // If we're already on the first page, there's no previous page.
        if ($current['page'] < 2) {
            return null;
        }

        // Clone the current cursor data for the previous page.
        $previousCursor = $current;

        // Remove the pointer to the next items from the previous cursor.
        unset($previousCursor['_pointsToNextItems']);

        // Decrement the page number for the previous page.
        $previousCursor['page']--;

        // Set the next sort parameter based on the sort history.
        $previousCursor['next_sort'] = Arr::pop($previousCursor['sort_history']);

        // Return the previous cursor.
        return Container::make(Cursor::class, ['parameters' => $previousCursor, 'pointsToNextItems' => false]);
    }

    /**
     * Set the items for the paginator.
     *
     * @param  mixed  $items  The items to set.
     */
    protected function setItems($items): void
    {
        // Convert items to a collection if they are not already.
        $this->items = $items instanceof Collection ? $items : Collection::make($items);

        // Determine if there are more pages based on the current page and total pages.
        $this->hasMore = $this->options['currentPage'] < $this->options['totalPages'];
    }
}
