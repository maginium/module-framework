<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination\Facades;

use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the LengthAwarePaginator service.
 *
 * @method static string url(int $page) Get the URL for a specific page.
 * @method static $this appends(string|array $key, mixed $value = null) Add query string values to the paginator URLs.
 * @method static $this fragment(string|null $fragment = null) Set the "fragment" to be appended to URLs.
 * @method static string|null nextPageUrl() Get the URL for the next page, if available.
 * @method static string|null previousPageUrl() Get the URL for the previous page, if available.
 * @method static array items() Get the items for the current page.
 * @method static int|null firstItem() Get the index of the first item on the current page.
 * @method static int|null lastItem() Get the index of the last item on the current page.
 * @method static int perPage() Get the number of items to be displayed per page.
 * @method static int currentPage() Get the current page number.
 * @method static bool hasPages() Determine if there are enough items to split into multiple pages.
 * @method static bool hasMorePages() Determine if there are more pages after the current one.
 * @method static string path() Get the base path for paginator-generated URLs.
 * @method static bool isEmpty() Determine if the paginator is empty.
 * @method static bool isNotEmpty() Determine if the paginator is not empty.
 * @method static string render(string|null $view = null, array $data = []) Render the paginator using a view.
 * @method static array getUrlRange(int $start, int $end) Get a range of URLs for the given page range.
 * @method static int total() Get the total number of items being paginated.
 * @method static int lastPage() Get the number of the last available page.
 *
 * @see LengthAwarePaginatorInterface
 */
class LengthAwarePaginator extends Facade
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
        return LengthAwarePaginatorInterface::class;
    }
}
