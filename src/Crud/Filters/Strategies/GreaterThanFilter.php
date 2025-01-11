<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * GreaterThanFilter.
 *
 * This filter strategy applies a "greater than" condition to a query.
 * It filters records where the column value is greater than the specified value.
 */
class GreaterThanFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "greater than" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$gt';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "greater than" condition.
     * The filter ensures that the column value is greater than the specified value.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Loop through each value in the filter's values and apply the condition
            foreach ($this->values as $value) {
                // Apply the "greater than" condition to the column
                $query->where($this->column, '>', $value);
            }
        };
    }
}
