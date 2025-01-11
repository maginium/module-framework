<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination;

use Illuminate\Pagination\CursorPaginator as BaseCursorPaginator;
use Maginium\Framework\Pagination\Interfaces\CursorPaginatorInterface;
use Maginium\Framework\Support\Facades\Container;
use Override;

/**
 * Enhanced CursorPaginator class for managing pagination.
 *
 * This class extends the base CursorPaginator from Laravel and implements
 * the CursorPaginatorInterface to ensure compatibility with custom pagination
 * logic. It also integrates with the framework's container to resolve dependencies.
 */
class CursorPaginator extends BaseCursorPaginator implements CursorPaginatorInterface
{
    /**
     * Create and return a new instance of the CursorPaginator.
     *
     * This method uses the framework's container to resolve an instance of the cursor paginator.
     * It allows passing additional arguments that can be used during the instantiation
     * process, enabling custom configurations or logic.
     *
     * @param  mixed  ...$arguments  Additional arguments to pass to the cursor paginator's constructor.
     *
     * @return CursorPaginatorInterface A new instance of the cursor paginator.
     */
    public static function make(...$arguments): CursorPaginatorInterface
    {
        // Resolve and return a new cursor paginator instance using the container with the provided arguments
        return Container::make(CursorPaginatorInterface::class, ...$arguments);
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
}
