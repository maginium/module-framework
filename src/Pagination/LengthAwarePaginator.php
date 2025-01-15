<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator as BaseLengthAwarePaginator;
use Maginium\Framework\Pagination\Constants\Paginator as PaginatorConstants;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Validator;
use Override;

/**
 * Enhanced LengthAwarePaginator class for managing pagination.
 *
 * This class extends the base LengthAwarePaginator from Laravel and implements
 * the LengthAwarePaginatorInterface to ensure compatibility with custom pagination
 * logic. It also integrates with the framework's container to resolve dependencies.
 */
class LengthAwarePaginator extends BaseLengthAwarePaginator implements LengthAwarePaginatorInterface
{
    /**
     * Create and return a new instance of the LengthAwarePaginator.
     *
     * This method uses the framework's container to resolve an instance of the paginator.
     * It allows passing additional arguments that can be used during the instantiation
     * process, enabling custom configurations or logic.
     *
     * @param  mixed  ...$arguments  Additional arguments to pass to the paginator's constructor.
     *
     * @return LengthAwarePaginatorInterface A new instance of the paginator.
     */
    public static function make(...$arguments): LengthAwarePaginatorInterface
    {
        // Resolve and return a new paginator instance using the container with the provided arguments
        return Container::make(LengthAwarePaginatorInterface::class, ...$arguments);
    }

    /**
     * Render the paginator using the given view.
     *
     * @param  string|null  $view
     * @param  array  $data
     *
     * @return Htmlable
     */
    #[Override]
    public function render($view = null, $data = []): void
    {
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->items->toArray();
    }

    /**
     * Get pagination metadata as an associative array.
     *
     * This method returns the core pagination information such as the pagination path,
     * total number of items, items per page, current page, last page, and other essential metadata.
     *
     * @return array Pagination metadata array.
     */
    public function meta(): array
    {
        // Group 1: Basic pagination information
        // These fields represent the essential pagination data including path, total items, and page info.
        $meta = [
            PaginatorConstants::PATH => $this->path(), // The base path for pagination links
            PaginatorConstants::TOTAL => $this->total(), // Total number of items in the collection

            PaginatorConstants::FROM => $this->firstItem(), // The index of the first item on the current page
            PaginatorConstants::TO => $this->lastItem(), // The index of the last item on the current page

            PaginatorConstants::PER_PAGE => $this->perPage(), // Items per page
            PaginatorConstants::CURRENT_PAGE => $this->currentPage(), // The current page number
            PaginatorConstants::LAST_PAGE => $this->lastPage(), // The last page number
        ];

        // Group 2: Pagination links
        // This section handles the links (e.g., next, previous, first, last) for the pagination.
        $meta[PaginatorConstants::LINKS] = $this->linkCollection()->toArray();

        // Return the array representation of the meta.
        return $meta;
    }

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
    #[Override]
    public function toArray(bool $withData = false): array
    {
        // Get the basic pagination metadata
        $data = $this->meta();

        // Group 2: Pagination links
        // This section handles the links (e.g., next, previous, first, last) for the pagination.
        $data[PaginatorConstants::LINKS] = $this->linkCollection()->toArray();

        // Group 3: Include data items (optional)
        // If $withData is true, add the actual data items (the content) to the result.
        if ($withData) {
            $data[PaginatorConstants::DATA] = $this->items->toArray(); // The paginated data items
        }

        // Return the final result
        return $data;
    }

    /**
     * Get the paginator links as a collection (for JSON responses).
     *
     * @return Collection
     */
    #[Override]
    public function linkCollection()
    {
        // Collect the pagination elements (pages) and process them.
        return collect($this->elements())->flatMap(function($item) {
            // If the item is not an array (i.e., it is a placeholder like '...'), return it as such.
            if (! Validator::isArray($item)) {
                return [[PaginatorConstants::URL => null, PaginatorConstants::LABEL => PaginatorConstants::ELLIPSIS, PaginatorConstants::ACTIVE => false]]; // Ellipsis link.
            }

            // Otherwise, map each page in the item to a structured array with URL, label, and active status.
            return collect($item)->map(fn($url, $page) => [
                PaginatorConstants::URL => $url,  // URL for the page.
                PaginatorConstants::LABEL => (string)$page,  // Label for the page (converted to string).
                PaginatorConstants::ACTIVE => $this->currentPage() === $page,  // Check if this page is the current one.
            ]);
        })
            // Prepend the "Previous" page link.
            ->prepend([
                PaginatorConstants::URL => $this->previousPageUrl(),  // URL for the previous page.
                PaginatorConstants::LABEL => __(PaginatorConstants::PREVIOUS_LABEL),  // Label for the previous page.
                PaginatorConstants::ACTIVE => false,  // Not active, as it's always a link.
            ])
            // Append the "Next" page link.
            ->push([
                PaginatorConstants::URL => $this->nextPageUrl(),  // URL for the next page.
                PaginatorConstants::LABEL => __(PaginatorConstants::NEXT_LABEL),  // Label for the next page.
                PaginatorConstants::ACTIVE => false,  // Not active, as it's always a link.
            ]);
    }
}
