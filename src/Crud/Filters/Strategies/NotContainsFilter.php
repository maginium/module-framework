<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * NotContainsFilter.
 *
 * This filter strategy applies a "not contains" condition to a query.
 * It filters records where the column value does not contain the specified string.
 */
class NotContainsFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "not contains" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$notContains';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "not contains" condition.
     * The filter ensures that the column value does not contain the specified string.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Loop through each value in $this->values and apply the "not contains" condition
            foreach ($this->values as $value) {
                // Ensure the column value does not contain the specified string by using the "not like" condition
                $query->where($this->column, 'not like', "%{$value}%");
            }
        };
    }
}
