<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination;

use Maginium\Framework\Request\Interfaces\RequestInterface;

/**
 * Class PaginationState.
 *
 * This class is responsible for resolving the pagination state in a web application.
 * It binds the pagination system to the request input, ensuring proper handling of pagination
 * components such as the current URL, current page, query parameters, and cursor.
 * The class allows you to configure the pagination behavior in a flexible and reusable manner,
 * making it ideal for scenarios where pagination is needed in a web application.
 *
 * Methods:
 * - resolveUsing(RequestInterface $request): void - Configures pagination resolvers using the given request.
 */
class PaginationState
{
    /**
     * Bind the pagination state resolvers using the given application container as a base.
     * This method configures how the pagination system resolves the current URL, current page,
     * query parameters, and cursor from the request input. It ensures the pagination components
     * work correctly in a web application context.
     */
    public static function resolveUsing(RequestInterface $request): void
    {
        // Resolve the current path for pagination, using the current request URL.
        Paginator::currentPathResolver(fn() => $request->url());

        // Resolve the current page based on the 'page' query parameter in the URL.
        // If the 'page' parameter is a valid integer and greater than or equal to 1, use it; otherwise, default to 1.
        Paginator::currentPageResolver(function(string $pageName = 'page') use ($request): int {
            // Get the 'page' parameter from the request.
            $page = $request->input($pageName);

            // Validate if the 'page' is an integer and ensure it's at least 1.
            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
                return (int)$page;
            }

            // Return default page 1 if invalid.
            return 1;
        });

        // Resolve the query string parameters, which can be used to maintain state across paginated requests.
        Paginator::queryStringResolver(fn() => $request->query()->toArray());

        // Resolve the current cursor from the 'cursor' query parameter.
        // This is used for cursor-based pagination (e.g., in APIs) to paginate by using a cursor instead of page numbers.
        CursorPaginator::currentCursorResolver(function(string $cursorName = 'cursor') use ($request) {
            // Decode the 'cursor' parameter from the request input and return a Cursor object.
            return Cursor::fromEncoded($request->input($cursorName));
        });
    }
}
