<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder as BaseBuilder;
use Illuminate\Database\Query\Expression;
use Maginium\Framework\Database\Concerns\BuildsQueries;
use Maginium\Framework\Database\Query\Builder as QueryBuilder;

/**
 * Extended Eloquent Builder with additional functionalities.
 *
 * This class extends Laravel's default Eloquent query builder (`BaseBuilder`)
 * and integrates custom pagination logic using the `BuildsQueries` trait.
 *
 * @mixin QueryBuilder
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
     * The base query builder instance.
     *
     * @var QueryBuilder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var Model
     */
    protected $model;

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

    /**
     * Add a basic where clause to the query.
     *
     * @param  Closure|string|array|Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     *
     * @return Builder
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column instanceof Closure && $operator === null) {
            $column($query = $this->model->newQueryWithoutRelationships());

            $this->query->addNestedWhereQuery($query->getQuery(), $boolean);
        } else {
            $this->query->where(...func_get_args());
        }

        return $this;
    }
}
