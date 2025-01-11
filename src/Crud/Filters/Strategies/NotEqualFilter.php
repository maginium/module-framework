<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Filters\Strategies;

use Closure;
use Maginium\Framework\Crud\Filters\Filter;

/**
 * NotEqualFilter.
 *
 * This filter strategy applies a "not equal" condition to the query.
 * It filters records where the column value is not equal to the specified value.
 */
class NotEqualFilter extends Filter
{
    /**
     * Operator string to detect in the query parameters.
     *
     * This operator is used to identify the "not equal" condition in the filter request.
     *
     * @var string
     */
    protected static string $operator = '$ne';

    /**
     * Apply the filter logic to the query.
     *
     * This method returns a Closure that modifies the query to include a "not equal" condition.
     * The filter ensures that the column value is not equal to the specified value.
     *
     * @return Closure Returns a Closure to be executed on the query builder.
     */
    public function apply(): Closure
    {
        return function($query) {
            // Loop through each value in $this->values and apply the "not equal" condition
            foreach ($this->values as $value) {
                // Apply the "not equal" condition for the column
                $query->whereNot($this->column, $value);
            }
        };
    }
}
