<?php

declare(strict_types=1);

namespace Maginium\Framework\Pagination\Interfaces;

use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * Interface CursorPaginatorInterface.
 *
 * Provides methods for cursor-based pagination, allowing navigation through paginated
 * data without relying on page numbers. This interface includes methods to retrieve
 * URLs for pagination, manage query strings, and access the current cursor position.
 */
interface CursorPaginatorInterface extends CursorPaginator
{
    /**
     * Create and return a new instance of the Cursor Paginator.
     *
     * This method uses the framework's container to resolve an instance of the cursor.
     * It allows passing additional arguments that can be used during the instantiation
     * process, enabling custom configurations or logic.
     *
     * @param  mixed  ...$arguments  Additional arguments to pass to the cursor's constructor.
     *
     * @return CursorPaginatorInterface A new instance of the cursor.
     */
    public static function make(...$arguments): self;
}
