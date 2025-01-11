<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * NotBetweenFilter.
 *
 * This filter strategy applies a "not between" condition to a query.
 * It filters records where the column value is not within the specified range.
 */
class NotBetweenFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "not between" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$notBetween';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "not between" condition.
     * The filter ensures that the column value is not between the two specified values.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Apply the "not between" condition to the query
            // Ensures the column value is not between the range specified by $this->values
            $query->whereNotBetween($this->column, $this->values);
        };
    }
}
