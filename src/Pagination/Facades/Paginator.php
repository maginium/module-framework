<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination\Facades;

use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the LengthAwarePaginator service.
 *
 * @method static string url(int $page) Get the URL for a specific page.
 * @method static \Illuminate\Contracts\Support\Arrayable|\Maginium\Framework\Database\Collection|\Maginium\Framework\Support\Collection items() Get the items in the current page.
 * @method static string appends($key, $value = null) Add query string values to the URL.
 * @method static string fragment($fragment = null) Set or get the fragment identifier for the URL.
 * @method static string nextPageUrl() Get the URL for the next page of results.
 * @method static string previousPageUrl() Get the URL for the previous page of results.
 * @method static string getUrlRange(int $start, int $end) Get a range of URLs for pages.
 * @method static PaginatorInterface withQueryString() Get the query string for the current URL.
 * @method static int firstItem() Get the index of the first item in the current page.
 * @method static int lastItem() Get the index of the last item in the current page.
 * @method static LengthAwarePaginatorInterface through(callable $callback) Apply a callback to each item in the paginator.
 * @method static int perPage() Get the number of items per page.
 * @method static bool hasPages() Determine if there are multiple pages of results.
 * @method static bool onFirstPage() Check if the current page is the first page.
 * @method static bool onLastPage() Check if the current page is the last page.
 * @method static int currentPage() Get the current page number.
 * @method static string getPageName() Get the name of the page parameter.
 * @method static void setPageName(string $name) Set the name of the page parameter.
 * @method static LengthAwarePaginatorInterface withPath(string $path) Set the base URL for the paginator.
 * @method static void setPath(string $path) Set the base URL for the paginator.
 * @method static int onEachSide(int $count) Set how many pages should be shown on each side of the paginator.
 * @method static string path() Get the base URL for the paginator.
 * @method static \Traversable getIterator() Get an iterator for the paginator's items.
 * @method static bool isEmpty() Check if the paginator has no items.
 * @method static bool isNotEmpty() Check if the paginator has items.
 * @method static int count() Get the total number of items in the paginator.
 * @method static \Maginium\Framework\Support\Collection getCollection() Get the collection of items in the paginator.
 * @method static void setCollection(\Maginium\Framework\Support\Collection $collection) Set the collection of items in the paginator.
 * @method static array getOptions() Get the options for the paginator.
 * @method static bool offsetExists($key) Check if an item exists at a given offset.
 * @method static mixed offsetGet($key) Get an item at a given offset.
 * @method static void offsetSet($key, $value) Set an item at a given offset.
 * @method static void offsetUnset($key) Unset an item at a given offset.
 * @method static string toHtml() Render the pagination view as HTML.
 * @method static mixed __call(string $method, array $parameters) Magic method to handle dynamic method calls.
 * @method static string __toString() Convert the paginator to a string representation.
 * @method static string resolveCurrentPath(string $default = '/') Resolve the current path, falling back to a default if not available.
 * @method static void currentPathResolver(\Closure $resolver) Set the resolver for the current path.
 * @method static int resolveCurrentPage(string $pageName = 'page', int $default = 1) Resolve the current page number, falling back to a default if not available.
 * @method static void currentPageResolver(\Closure $resolver) Set the resolver for the current page number.
 * @method static string resolveQueryString(string $default = null) Resolve the query string, falling back to a default if not available.
 * @method static void queryStringResolver(\Closure $resolver) Set the resolver for the query string.
 * @method static void viewFactoryResolver(\Closure $resolver) Set the resolver for the view factory.
 * @method static void defaultView(string $view) Set the default view for pagination rendering.
 * @method static void defaultSimpleView(string $view) Set the default simple view for pagination rendering.
 * @method static void useTailwind() Enable the use of Tailwind CSS for pagination styling.
 * @method static void useBootstrap() Enable the use of Bootstrap 3 for pagination styling.
 * @method static void useBootstrapThree() Enable the use of Bootstrap 3 for pagination styling.
 * @method static void useBootstrapFour() Enable the use of Bootstrap 4 for pagination styling.
 * @method static void useBootstrapFive() Enable the use of Bootstrap 5 for pagination styling.
 *
 * @see PaginatorInterface
 */
class Paginator extends Facade
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
        return PaginatorInterface::class;
    }
}
