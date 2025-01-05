<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Maginium\Framework\Database\Concerns\BuildsQueries;
use Maginium\Framework\Pagination\Interfaces\CursorInterface;
use Maginium\Framework\Pagination\Interfaces\CursorPaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\LengthAwarePaginatorInterface;
use Maginium\Framework\Pagination\Interfaces\PaginatorInterface;

/**
 * Extended Query Builder for Maginium Framework.
 *
 * This class extends Laravel's default query builder (`BaseBuilder`) and
 * provides additional pagination functionality through the `PaginationFactory` trait.
 */
class Builder extends BaseBuilder
{
    /**
     * Include PaginationFactory for extended pagination support.
     *
     * The PaginationFactory trait provides methods for creating custom paginators,
     * including length-aware, simple, and cursor-based paginators, enhancing
     * the default query builder capabilities.
     */
    use BuildsQueries;

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int|Closure  $perPage
     * @param  array|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  Closure|int|null  $total
     *
     * @return LengthAwarePaginatorInterface
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginatorInterface
    {
        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Get a paginator only supporting simple next and previous links.
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param  int  $perPage
     * @param  array|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     *
     * @return PaginatorInterface
     */
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): PaginatorInterface
    {
        return parent::simplePaginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Get a paginator only supporting simple next and previous links.
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param  int|null  $perPage
     * @param  array|string  $columns
     * @param  string  $cursorName
     * @param  CursorInterface|string|null  $cursor
     *
     * @return CursorPaginatorInterface
     */
    public function cursorPaginate($perPage = 15, $columns = ['*'], $cursorName = 'cursor', $cursor = null): CursorPaginatorInterface
    {
        return parent::cursorPaginate($perPage, $columns, $cursorName, $cursor);
    }
}
