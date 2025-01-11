<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * LessOrEqualFilter.
 *
 * This filter strategy applies a "less than or equal to" condition to a query.
 * It filters records where the column value is less than or equal to the specified value.
 */
class LessOrEqualFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "less than or equal to" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$lte';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "less than or equal to" condition.
     * The filter ensures that the column value is less than or equal to the specified value.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Loop through each value and apply the "less than or equal to" condition to the query
            foreach ($this->values as $value) {
                // Add the condition to the query where column is less than or equal to the value
                $query->where($this->column, '<=', $value);
            }
        };
    }
}
