<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * EqualFilter.
 *
 * This filter strategy applies an "EQUAL" condition to a query.
 * It filters records where the column value exactly matches the specified value, without case sensitivity.
 */
class EqualFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify "EQUAL" conditions in incoming filter requests.
     * It ensures that the value matches exactly without considering case sensitivity.
     *
     * @var string
     */
    protected static string $operator = '$eq';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include "EQUAL" conditions.
     * The filter will use the standard equality check without case sensitivity.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Loop through each value in the filter's values and apply the condition
            foreach ($this->values as $value) {
                // Apply the equality condition to the column, without considering case sensitivity
                $query->where($this->column, $value);
            }
        };
    }
}
