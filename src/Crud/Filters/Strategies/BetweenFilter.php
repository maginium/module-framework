<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * BetweenFilter.
 *
 * This filter strategy applies a "BETWEEN" condition to a query.
 * It is used to filter records where a column value falls within a specified range.
 */
class BetweenFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator identifies the filter type as "BETWEEN" in the incoming filter request.
     *
     * @var string
     */
    protected static string $operator = '$between';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "BETWEEN" condition.
     * The condition filters records where the column value falls within the range specified in `$this->values`.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Apply a "BETWEEN" condition to the query for the specified column and range values.
            // The `$this->values` array must contain exactly two elements: the lower and upper bounds of the range.
            $query->whereBetween($this->column, $this->values);
        };
    }
}
