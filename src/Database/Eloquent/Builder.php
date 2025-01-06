<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder as BaseBuilder;
use Maginium\Framework\Database\Concerns\BuildsQueries;
use Maginium\Framework\Database\Query\Builder as QueryBuilder;

/**
 * Extended Eloquent Builder with additional functionalities.
 *
 * This class extends Laravel's default Eloquent query builder (`BaseBuilder`)
 * and integrates custom pagination logic using the `BuildsQueries` trait.
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
     * Create a new Eloquent query builder instance.
     *
     * @param  QueryBuilder  $query
     *
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);
    }
}
