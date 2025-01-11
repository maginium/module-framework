<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * GreaterOrEqualFilter.
 *
 * This filter strategy applies a "greater than or equal to" condition to a query.
 * It filters records where the column value is greater than or equal to the specified value.
 */
class GreaterOrEqualFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "greater than or equal to" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$gte';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "greater than or equal to" condition.
     * The filter ensures that the column value is greater than or equal to the specified value.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Loop through each value in the filter's values and apply the condition
            foreach ($this->values as $value) {
                // Apply the "greater than or equal to" condition to the column
                $query->where($this->column, '>=', $value);
            }
        };
    }
}
