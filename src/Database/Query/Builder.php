<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Query;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Query\JoinLateralClause;
use InvalidArgumentException;
use Maginium\Framework\Database\Concerns\BuildsQueries;
use Maginium\Framework\Database\Eloquent\Builder as EloquentBuilder;
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
        return parent::where($column, $operator, $value, $boolean);
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

    /**
     * Add a subselect expression to the query.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $query
     * @param  string  $as
     *
     * @throws InvalidArgumentException
     *
     * @return Builder
     */
    public function selectSub($query, $as)
    {
        // Call the parent method and pass the same parameters
        return parent::selectSub($query, $as);
    }

    /**
     * Makes "from" fetch from a subquery.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $query
     * @param  string  $as
     *
     * @throws InvalidArgumentException
     *
     * @return Builder
     */
    public function fromSub($query, $as)
    {
        // Call the parent method and pass the same parameters
        return parent::fromSub($query, $as);
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $table
     * @param  string|null  $as
     *
     * @return Builder
     */
    public function from($table, $as = null)
    {
        // Call the parent method and pass the same parameters
        return parent::from($table, $as);
    }

    /**
     * Add a subquery join clause to the query.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $query
     * @param  string  $as
     * @param  Closure|Expression|string  $first
     * @param  string|null  $operator
     * @param  Expression|string|null  $second
     * @param  string  $type
     * @param  bool  $where
     *
     * @throws InvalidArgumentException
     *
     * @return Builder
     */
    public function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        // Call the parent method and pass the same parameters
        return parent::joinSub($query, $as, $first, $operator, $second, $type, $where);
    }

    /**
     * Add a lateral join clause to the query.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $query
     * @param  string  $as
     * @param  string  $type
     *
     * @return Builder
     */
    public function joinLateral($query, string $as, string $type = 'inner')
    {
        // Call the parent method and pass the same parameters
        return parent::joinLateral($query, $as, $type);
    }

    /**
     * Add a lateral left join to the query.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $query
     * @param  string  $as
     *
     * @return Builder
     */
    public function leftJoinLateral($query, string $as)
    {
        // Call the parent method and pass the same parameters
        return parent::leftJoinLateral($query, $as);
    }

    /**
     * Add a subquery left join to the query.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $query
     * @param  string  $as
     * @param  Closure|Expression|string  $first
     * @param  string|null  $operator
     * @param  Expression|string|null  $second
     *
     * @return Builder
     */
    public function leftJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        // Call the parent method and pass the same parameters
        return parent::leftJoinSub($query, $as, $first, $operator, $second);
    }

    /**
     * Add a subquery right join to the query.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $query
     * @param  string  $as
     * @param  Closure|Expression|string  $first
     * @param  string|null  $operator
     * @param  Expression|string|null  $second
     *
     * @return Builder
     */
    public function rightJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        // Call the parent method and pass the same parameters
        return parent::rightJoinSub($query, $as, $first, $operator, $second);
    }

    /**
     * Add a subquery cross join to the query.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $query
     * @param  string  $as
     *
     * @return Builder
     */
    public function crossJoinSub($query, $as)
    {
        // Call the parent method and pass the same parameters
        return parent::crossJoinSub($query, $as);
    }

    /**
     * Create a new query instance for nested where condition.
     *
     * @return Builder
     */
    public function forNestedWhere()
    {
        // Call the parent method and pass the same parameters
        return parent::forNestedWhere();
    }

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param  Builder  $query
     * @param  string  $boolean
     *
     * @return Builder
     */
    public function addNestedWhereQuery($query, $boolean = 'and')
    {
        // Call the parent method and pass the same parameters
        return parent::addNestedWhereQuery($query, $boolean);
    }

    /**
     * Add an exists clause to the query.
     *
     * @param  Closure|Builder|EloquentBuilder  $callback
     * @param  string  $boolean
     * @param  bool  $not
     *
     * @return Builder
     */
    public function whereExists($callback, $boolean = 'and', $not = false)
    {
        // Call the parent method and pass the same parameters
        return parent::whereExists($callback, $boolean, $not);
    }

    /**
     * Add an or exists clause to the query.
     *
     * @param  Closure|Builder|EloquentBuilder  $callback
     * @param  bool  $not
     *
     * @return Builder
     */
    public function orWhereExists($callback, $not = false)
    {
        // Call the parent method and pass the same parameters
        return parent::orWhereExists($callback, $not);
    }

    /**
     * Add a where not exists clause to the query.
     *
     * @param  Closure|Builder|EloquentBuilder  $callback
     * @param  string  $boolean
     *
     * @return Builder
     */
    public function whereNotExists($callback, $boolean = 'and')
    {
        // Call the parent method and pass the same parameters
        return parent::whereNotExists($callback, $boolean);
    }

    /**
     * Add a where not exists clause to the query.
     *
     * @param  Closure|Builder|EloquentBuilder  $callback
     *
     * @return Builder
     */
    public function orWhereNotExists($callback)
    {
        // Call the parent method and pass the same parameters
        return parent::orWhereNotExists($callback);
    }

    /**
     * Add an exists clause to the query.
     *
     * @param  Builder  $query
     * @param  string  $boolean
     * @param  bool  $not
     *
     * @return Builder
     */
    public function addWhereExistsQuery(BaseBuilder $query, $boolean = 'and', $not = false)
    {
        // Call the parent method and pass the same parameters
        return parent::addWhereExistsQuery($query, $boolean, $not);
    }

    /**
     * Add another query builder as a nested having to the query builder.
     *
     * @param  Builder  $query
     * @param  string  $boolean
     *
     * @return Builder
     */
    public function addNestedHavingQuery($query, $boolean = 'and')
    {
        // Call the parent method and pass the same parameters
        return parent::addNestedHavingQuery($query, $boolean);
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  Closure|Builder|EloquentBuilder|Expression|string  $column
     * @param  string  $direction
     *
     * @throws InvalidArgumentException
     *
     * @return Builder
     */
    public function orderBy($column, $direction = 'asc')
    {
        // Delegate to the parent class's orderBy method with the same parameters
        return parent::orderBy($column, $direction);
    }

    /**
     * Add a descending "order by" clause to the query.
     *
     * @param  Closure|Builder|EloquentBuilder|Expression|string  $column
     *
     * @return Builder
     */
    public function orderByDesc($column)
    {
        // Delegate to the parent class's orderBy method with 'desc' as the direction
        return parent::orderBy($column, 'desc');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  Closure|Builder|Expression|string  $column
     *
     * @return Builder
     */
    public function latest($column = 'created_at')
    {
        // Delegate to the parent class's orderBy method with 'desc' direction
        return parent::orderBy($column, 'desc');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  Closure|Builder|Expression|string  $column
     *
     * @return Builder
     */
    public function oldest($column = 'created_at')
    {
        // Delegate to the parent class's orderBy method with 'asc' direction
        return parent::orderBy($column, 'asc');
    }

    /**
     * Remove all existing orders and optionally add a new order.
     *
     * @param  Closure|Builder|Expression|string|null  $column
     * @param  string  $direction
     *
     * @return Builder
     */
    public function reorder($column = null, $direction = 'asc')
    {
        return parent::reorder($column, $direction);
    }

    /**
     * Add a union statement to the query.
     *
     * @param  Closure|Builder|EloquentBuilder  $query
     * @param  bool  $all
     *
     * @return Builder
     */
    public function union($query, $all = false)
    {
        // Delegate to the parent class's union method with the same parameters
        return parent::union($query, $all);
    }

    /**
     * Add a union all statement to the query.
     *
     * @param  Closure|Builder|EloquentBuilder  $query
     *
     * @return Builder
     */
    public function unionAll($query)
    {
        // Delegate to the parent class's union method with 'true' for the all parameter
        return parent::union($query, true);
    }

    /**
     * Insert new records into the table using a subquery.
     *
     * @param  array  $columns
     * @param  Closure|Builder|EloquentBuilder|string  $query
     *
     * @return int
     */
    public function insertUsing(array $columns, $query)
    {
        // Delegate to the parent class's insertUsing method with the same parameters
        return parent::insertUsing($columns, $query);
    }

    /**
     * Insert new records into the table using a subquery while ignoring errors.
     *
     * @param  array  $columns
     * @param  Closure|Builder|EloquentBuilder|string  $query
     *
     * @return int
     */
    public function insertOrIgnoreUsing(array $columns, $query)
    {
        // Delegate to the parent class's insertOrIgnoreUsing method with the same parameters
        return parent::insertOrIgnoreUsing($columns, $query);
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return Builder
     */
    public function newQuery()
    {
        // Delegate to the parent class's newQuery method
        return parent::newQuery();
    }

    /**
     * Merge an array of bindings into our bindings.
     *
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function mergeBindings(BaseBuilder $query)
    {
        // Delegate to the parent class's mergeBindings method
        return parent::mergeBindings($query);
    }

    /**
     * Creates a subquery and parse it.
     *
     * @param  Closure|Builder|EloquentBuilder|string  $query
     *
     * @return array
     */
    protected function createSub($query)
    {
        // Call the parent method and pass the same parameters
        return parent::createSub($query);
    }

    /**
     * Get a new join clause.
     *
     * @param  Builder  $parentQuery
     * @param  string  $type
     * @param  string  $table
     *
     * @return JoinClause
     */
    protected function newJoinClause(BaseBuilder $parentQuery, $type, $table)
    {
        // Call the parent method and pass the same parameters
        return parent::newJoinClause($parentQuery, $type, $table);
    }

    /**
     * Get a new join lateral clause.
     *
     * @param  Builder  $parentQuery
     * @param  string  $type
     * @param  string  $table
     *
     * @return JoinLateralClause
     */
    protected function newJoinLateralClause(BaseBuilder $parentQuery, $type, $table)
    {
        // Call the parent method and pass the same parameters
        return parent::newJoinLateralClause($parentQuery, $type, $table);
    }

    /**
     * Add a full sub-select to the query.
     *
     * @param  Expression|string  $column
     * @param  string  $operator
     * @param  Closure|Builder|EloquentBuilder  $callback
     * @param  string  $boolean
     *
     * @return Builder
     */
    protected function whereSub($column, $operator, $callback, $boolean)
    {
        // Call the parent method and pass the same parameters
        return parent::whereSub($column, $operator, $callback, $boolean);
    }

    /**
     * Create a new query instance for a sub-query.
     *
     * @return Builder
     */
    protected function forSubQuery()
    {
        // Delegate to the parent class's forSubQuery method
        return parent::forSubQuery();
    }
}
