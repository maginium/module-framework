<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters;

use Maginium\Framework\Crud\Interfaces\FilterInterface;
use Maginium\Framework\Database\Eloquent\Builder;

/**
 * Abstract base class for implementing filters in CRUD operations.
 *
 * This class provides the foundation for creating custom filters that
 * can be applied to Eloquent queries. Each filter should define its
 * behavior and operator by extending this abstract class.
 *
 * @property string $operator Retrieve the operator used by the filter.
 */
abstract class Filter implements FilterInterface
{
    /**
     * The Eloquent query builder instance.
     *
     * @var Builder
     */
    protected Builder $query;

    /**
     * The column on which the filter will be applied.
     *
     * @var string
     */
    protected string $column;

    /**
     * The values used for filtering the specified column.
     *
     * @var array
     */
    protected array $values;

    /**
     * Resolves and applies filters to Eloquent queries by dynamically validating.
     *
     * @var Resolve
     */
    protected Resolve $resolve;

    /**
     * Constructor to initialize the filter.
     *
     * @param Resolve $resolve  Resolves and applies filters to Eloquent queries by dynamically validating.
     * @param Builder $query  The Eloquent query builder instance.
     * @param string  $column The column to apply the filter on.
     * @param array   $values The values for filtering.
     */
    public function __construct(Resolve $resolve, Builder $query, string $column, array $values)
    {
        $this->query = $query;
        $this->column = $column;
        $this->values = $values;
        $this->resolve = $resolve;
    }

    /**
     * Retrieve the operator used by the filter.
     *
     * This method must be overridden by subclasses to define the specific
     * operator (e.g., '=', '!=', 'LIKE') used for filtering.
     *
     * @return string The operator used for filtering.
     */
    public static function operator(): string
    {
        // Ensure child classes define the $operator property.
        return static::$operator;
    }
}
